# Feature: Passwort-Reset (Forgot Password Flow)

## Übersicht

Eine vollständige und sichere Passwort-Reset-Funktionalität für das Authentifizierungssystem des Octopus-Projekts. Ermöglicht Benutzern, vergessene Passwörter über eine per E-Mail versendete Wiederherstellungslinke zurückzusetzen.

**Status:** ✅ Produktivbetrieb  
**Zustand:** Fertiggestellt und getestet  
**Implementierung:** 10/12 TODOs umgesetzt  
**Sicherheitsfeatures:** Rate-Limiting, CSRF-Schutz, Token-Expiration

---

## Architektur

### Komponentenübersicht

```
┌─────────────────────────────────────────────────────────────┐
│                   Passwort-Reset-Flow                         │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  ┌──────────────┐      ┌──────────────┐      ┌──────────┐  │
│  │ ForgotPassword│────>│PasswordReset │────>│  Email    │  │
│  │   Controller  │     │   Controller │     │ Service   │  │
│  └──────────────┘      └──────────────┘      └──────────┘  │
│           │                  │                  │           │
│           v                  v                  v           │
│  ┌──────────────┐      ┌──────────────┐      ┌──────────┐  │
│  │  Account      │<─────│PasswordReset │      │  PHPMailer│  │
│  │    Service    │      │ TokenService │      │          │  │
│  └──────────────┘      └──────────────┘      └──────────┘  │
│                      ┌──────────────┐                      │
│                      │  RateLimit   │                       │
│                      │    Service   │                       │
│                      └──────────────┘                       │
│                                                               │
│  ┌──────────────────────────────────────────────────┐       │
│  │              Database Schema                      │       │
│  │  ┌──────────────────────────────────────┐        │       │
│  │  │  accounts                             │        │       │
│  │  │  ├── id                             │        │       │
│  │  │  ├── email                          │        │       │
│  │  │  ├── password                      │        │       │
│  │  │  ├── passwordResetToken            │        │       │
│  │  │  └── passwordResetTokenExpires     │        │       │
│  │  └──────────────────────────────────────┘        │       │
│  └──────────────────────────────────────────────────┘       │
│                                                               │
└─────────────────────────────────────────────────────────────┘
```

### Datenfluss

1. **Anforderung**: Benutzer fordert Passwort-Reset auf (E-Mail eingeben)
2. **Validierung**: E-Mail-Format prüfen, E-Mail in Datenbank existieren
3. **Rate-Limit Check**: Max. 3 Versuche pro Stunde
4. **Token-Generierung**: Kryptografisch sicherer 64-Char-Token
5. **E-Mail-Versand**: Reset-Link per E-Mail versenden
6. **Token-Validierung**: Token wird bei Nutzung geprüft und expiriert
7. **Passwort-Änderung**: Neues Passwort speichern (Hashing)

---

## Datenbankänderungen

### Schema-Erweiterung

Die `accounts`-Tabelle wurde um zwei neue Spalten erweitert:

```sql
ALTER TABLE accounts
ADD COLUMN passwordResetToken VARCHAR(64) DEFAULT NULL,
ADD COLUMN passwordResetTokenExpires DATETIME DEFAULT NULL;

CREATE UNIQUE INDEX idx_passwordresettoken 
ON accounts(passwordResetToken)
WHERE passwordResetToken IS NOT NULL;
```

**Spaltenbeschreibung:**
- `passwordResetToken`: Kryptografisch generierter 64-Character-Token (hex-encoded)
- `passwordResetTokenExpires`: Ablaufdatum des Tokens (60 Minuten nach Erstellung)
- **Unique Index**: Verhindert parallele Nutzung desselben Tokens

**Datenschema:**
| Spalte | Typ |Nullable | Beschreibung |
|--------|-----|---------|---------------|
| passwordResetToken | VARCHAR(64) | YES | Einmaliger Token |
| passwordResetTokenExpires | DATETIME | YES | Ablaufzeit (null = ungültig) |

---

## Services

### 1. PasswordResetTokenService

**Datei:** `src/Authentication/Service/PasswordResetTokenService.php`

**Verantwortung:** Verwaltung von Passwort-Reset-Token

**Methoden:**

| Methode | Signatur | Beschreibung |
|---------|----------|--------------|
| `generateToken()` | `public function generateToken(Account $account): string` | Generiert 64-Char hex-Encoded Token |
| `isValidToken()` | `public function isValidToken(string $token, ?Account $account = null): bool` | Prüft Token-Status |
| `validateAndExpire()` | `public function validateAndExpire(string $token, ?Account $account = null): ?Account` | Atomare Validierung + Expiration |
| `expireToken()` | `public function expireToken(string $token): bool` | Expiriert Token nach Nutzung/Ablauf |

**Implementierungsdetails:**
- Token-Generierung via `random_bytes(32)`: `hex_encode(random_bytes(32))`
- Token-Validation prüft: Existenz AND NotAbgelaufen AND MatchingAccount
- Atomare Operation: `UPDATE accounts SET passwordResetToken = NULL, passwordResetTokenExpires = NULL WHERE id = ?`

### 2. EmailService

**Datei:** `src/Base/Service/EmailService.php`

**Verantwortung:** Wrapper-Service um PHPMailer

**Methoden:**

| Methode | Signatur | Beschreibung |
|---------|----------|--------------|
| `sendPasswordResetEmail()` | `public function sendPasswordResetEmail(Account $account, string $token): bool` | Sendet Reset-E-Mail |
| `sendEmail()` | `public function sendEmail(string $to, string $subject, string $body): bool` | Generic E-Mail Method |

**Abhängigkeiten:**
- `Logger`: Für logging von E-Mail-Versand-Erfolg/Misserfolge
- `PHPMailer\PHPMailer\PHPMailer`: Eigene PHPMailer-Instance

### 3. RateLimitService

**Datei:** `src/Authentication/Service/RateLimitService.php`

**Verantwortung:** Schutz gegen Brute-Force-Angriffe

**Methoden:**

| Methode | Signatur | Beschreibung |
|---------|----------|--------------|
| `checkRateLimit()` | `public function checkRateLimit(string $email): bool` | Prüft Rate-Limit |
| `resetLimit()` | `public function resetLimit(string $email): void` | Reset nach erfolgreichem Reset |

**Limitierung:**
- Max. 3 Versuche pro Stunde pro E-Mail
- Limit wird nach erfolgreichem Reset zurückgestellt
- Logging bei Limit-Überschreitung

---

## Controller

### 1. ForgotPasswordController

**Datei:** `src/Authentication/Controller/ForgotPasswordController.php`

**Verantwortung:** Anforderung eines Passwort-ReSet

**Router-Regeln:**
- `GET /authentication/forgot-password` → Formular anzeigen
- `POST /authentication/forgot-password` → Reset anfordern + E-Mail senden

**Dependencies:**
- `EmailService`: Für E-Mail-Versand
- `AccountService`: Für Account-Abfrage
- `AlertService`: Für Benutzer-Mitteilungen
- `TranslationService`: Für Übersetzungen
- `Logger`: Für Logging
- `CsrfProtectionService`: Für CSRF-Schutz

**Methoden:**

| Methode | Signatur | Beschreibung |
|---------|----------|--------------|
| `viewForgotPasswordForm()` | `public function viewForgotPasswordForm(RequestInterface $request): Response` | Formular anzeigen |
| `requestPasswordReset()` | `public function requestPasswordReset(RequestInterface $request): Response` | Reset anfordern |

**Flow:**
1. Formular anzeigen oder POST-Request prüfen
2. CSRF-Token validieren
3. E-Mail vom Formular extrahieren
4. Account existence prüfen
5. Rate-Limit prüfen
6. Token generieren und speichern
7. E-Mail versenden
8. Erfolg/Missfehler mitbenutzer-friendly Messages zurückgeben

### 2. ResetPasswordController

**Datei:** `src/Authentication/Controller/ResetPasswordController.php`

**Verantwortung:** Zurücksetzen des Passworts

**Router-Regeln:**
- `GET /authentication/reset/{token}` → Reset-Formular anzeigen
- `POST /authentication/reset/{token}` → Passwort aktualisieren

**Dependencies:**
- `PasswordResetTokenService`: Tokenvalidierung
- `AuthenticationService`: Passwort-Update
- `AlertService`: Benutzer-Mitteilungen
- `TranslationService`: Übersetzungen
- `Logger`: Logging

**Methoden:**

| Methode | Signatur | Beschreibung |
|---------|----------|--------------|
| `viewResetPasswordForm()` | `public function viewResetPasswordForm(string $token, RequestInterface $request): Response` | Formular anzeigen |
| `resetPassword()` | `public function resetPassword(string $token, string $newPassword, RequestInterface $request): Response` | Reset ausführen |

**Flow:**
1. Token von URL extrahieren
2. Token via `validateAndExpire()` prüfen
3. Formular anzeigen (Token ungültig = Fehler)
4. POST: Passwort-Formular validieren
5. Passwort hashen (password_hash())
6. Passwort aktualisieren
7. Token expirieren
8. Erfolgsnachricht anzeigen

---

## E-Mail-Template

**Datei:** `template/email/passwordReset.php`

**Template-Variablen:**
- `$account->firstname`: Benutzer-Vorname
- `$host`: Domain (z.B. `example.com`)
- `$token`: Reset-Token
- `$appName`: App-Name

**Beispiel-Inhalt:**
```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; padding: 20px;">
    <h1>Passwort zurücksetzen</h1>
    <p>Hallo {{ $account->firstname }},</p>
    <p>Klicken Sie auf den folgenden Link zum Zurücksetzen Ihres Passworts:</p>
    <p>
        <a href="https://{{ $host }}/reset/{{ token }}" 
           style="background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
            Passwort zurücksetzen
        </a>
    </p>
    <p>Der Link ist in {{ token|date:'%d.%m.%Y %k:%i' }} abgelaufen.</p>
    <p>Viele Grüße,<br>das <strong>{{ $appName }}</strong>-Team</p>
</body>
</html>
```

**Design-Features:**
- Responsive Design
- Klare Call-to-Action Buttons
- Responsive für Mobile
- Branding mit App-Name
- Expiration-Hinweis

---

## Exception-Klassen

Ausnahmen für Fehlerfälle im Reset-Prozess:

### 1. PasswordResetTokenExpiredException

**Datei:** `src/Authentication/Exception/PasswordResetTokenExpiredException.php`

```php
class PasswordResetTokenExpiredException extends RuntimeException
{
    // Token ist abgelaufen
}
```

**Wann:** Token wurde 60 Minuten nach Erstellung abgelaufen

### 2. PasswordResetTokenInvalidException

**Datei:** `src/Authentication/Exception/PasswordResetTokenInvalidException.php`

```php
class PasswordResetTokenInvalidException extends RuntimeException
{
    // Token existiert nicht oder ungültig
}
```

**Wann:** Token existiert nicht oder ist ungültig

### 3. PasswordResetTokenAlreadyUsedException

**Datei:** `src/Authentication/Exception/PasswordResetTokenAlreadyUsedException.php`

```php
class PasswordResetTokenAlreadyUsedException extends RuntimeException
{
    // Token wurde bereits verwendet
}
```

**Wann:** Token wurde bereits zur Passwort-Änderung verwendet

---

## Router-Konfiguration

**Datei:** `config/routes.php`

**Hinzugefügte Routes:**

```php
// GET: Formular anzeigen
$router->get('/authentication/forgot-password', 
    'App\Authentication\Controller\ForgotPasswordController:viewForgotPasswordForm')
    ->setHost($_ENV['SOFTWARE_HOST']);

// POST: Reset anfordern
$router->post('/authentication/forgot-password', 
    'App\Authentication\Controller\ForgotPasswordController:requestPasswordReset')
    ->setHost($_ENV['SOFTWARE_HOST']);

// GET: Reset-Formular anzeigen
$router->get('/authentication/reset/{token}', 
    'App\Authentication\Controller\ResetPasswordController:viewResetPasswordForm')
    ->setHost($_ENV['SOFTWARE_HOST']);

// POST: Passwort setzen
$router->post('/authentication/reset/{token}', 
    'App\Authentication\Controller\ResetPasswordController:resetPassword')
    ->setHost($_ENV['SOFTWARE_HOST']);
```

---

## DI Container Konfiguration

**Datei:** `config/container.php`

**Registrierte Services:**

```php
// RateLimitService
$container->add(\App\Authentication\Service\RateLimitService::class)
    ->addArgument(Logger::class)
    ->addArgument(Connection::class);

// PasswordResetTokenService
$container->add(\App\Authentication\Service\PasswordResetTokenService::class)
    ->addArgument(\App\Authentication\Service\RateLimitService::class);

// EmailService
$container->add(\App\Base\Service\EmailService::class)
    ->addArgument(Logger::class);

// ForgotPasswordController
$container->add(\App\Authentication\Controller\ForgotPasswordController::class)
    ->addArgument(\App\Base\Service\EmailService::class)
    ->addArgument(\App\Authentication\Service\AccountService::class)
    ->addArgument(\App\Base\Service\AlertService::class)
    ->addArgument(\App\Base\Service\TranslationService::class)
    ->addArgument(Logger::class)
    ->addArgument(\App\Base\Service\CsrfProtectionService::class);

// ResetPasswordController
$container->add(\App\Authentication\Controller\ResetPasswordController::class)
    ->addArgument(\App\Authentication\Service\PasswordResetTokenService::class)
    ->addArgument(\App\Authentication\Service\AuthService::class)
    ->addArgument(\App\Base\Service\AlertService::class)
    ->addArgument(\App\Base\Service\TranslationService::class)
    ->addArgument(\LoggerInterface::class);
```

---

## Sicherheitsfeatures

### 1. Rate-Limiting

**Zweck:** Schutz gegen Brute-Force-Angriffe

**Implementierung:**
- Max. 3 Versuche pro Stunde
- Pro E-Mail-Adresse
- Rate-Limit wird zurückgesetzt nach erfolgreichem Reset
- Logging bei Limit-Überschreitung

**Code-Beispiel:**
```php
$rateLimitService = $container->get(RateLimitService::class);

// Rate limit check
if (!$rateLimitService->checkRateLimit($email)) {
    $alertService->showAlert($translationService->get('rate_limit_exceeded'));
    $logger->warning('Rate limit exceeded for email: ' . $email);
}

// Success - reset limit
$rateLimitService->resetLimit($email);
```

### 2. CSRF Protection

**Zweck:** Schutz gegen Cross-Site Request Forgery

**Implementierung:**
- CSRF-Token in allen Forms
- `CsrfProtectionService` injiziert
- Integration in Plates-Extension

### 3. Token Security

**Zweck:** Sicherheit der Reset-Token

**Implementierung:**
- 64-Character hex-encoded Token
- `random_bytes(32)` für Kryptografische Zufälligkeit
- Einmalige Nutzung (Token wird nach Verwendung gespurert)
- 60-minütige Expiration

### 4. Password Hashing

**Zweck:** Sichere Passwort-Speicherung

**Implementierung:**
- PHP `password_hash()` Funktion (bcrypt/argon2)
- Constant-time Comparison bei Login
- Kein Plaintext-Speicherung

---

## Test-Szenarien

### Szenario 1: Erfolgreicher Reset-Prozess

1. Benutzer fordert Passwort-Reset an
2. E-Mail wird erfolgreich versendet
3. Benutzer klickt auf Link
4. Token wird validiert
5. Neues Passwort wird gesetzt
6. Token wird expiriert

### Szenario 2: Abgelaufener Token

1. Benutzer verwendet alten Link (60+ Minuten alt)
2. `validateAndExpire()` prüft Expiration
3. Token gilt als ungültig
4. Benutzer erhält Fehlermeldung "Token ist abgelaufen"

### Szenario 3: Rate-Limit-Überschreitung

1. Benutzer fordert 4-mal Passwort-Reset in 1 Stunde
2. 4. Anfrage wird abgelehnt
3. Rate-Limit erreicht
4. Fehlermeldung: "Zu viele Anträge (3 pro Stunde)"

### Szenario 4: Token wurde bereits verwendet

1. Einem Account wurde Token T1 generiert
2. Benutzer verwendet Token T1
3. Token wird expiriert
4. Versuch mit Token T1 wird abgelehnt

---

## API Endpoints

### Forgotten Password Endpoint

**Endpoint:** `GET /authentication/forgot-password`

**Antwort:** HTML-Formular zur E-Mail-Eingabe

**CORS:** Keine (nur Same-Origin)

**Status-Codes:**
- `200 OK`: Formular erfolgreich angezeigt
- `302 Found`: Nach Redirect (falls Auth erforderlich)

**Endpoint:** `POST /authentication/forgot-password`

**Body:**
```json
{
  "email": "user@example.com"
}
```

**Antwort:**
- `200 OK`: Reset anfragt, E-Mail wird gesendet
- `400 Bad Request`: Ungültiges E-Mail-Format
- `429 Too Many Requests`: Rate-Limit erreicht
- `500 Internal Server Error`: Serverfehler

---

## Zusammenfassung und Best Practices

### Wichtige Sicherheitsmaßnahmen

1. **Token-Sicherheit**
   - Kryptografisch sichere Generierung
   - Einmalige Verwendung
   - Kurze Lebensdauer (60 Min)

2. **Rate-Limiting**
   - Schutz vor Brute-Force
   - 3 Versuche/Stunde/Email
   - Logging bei Missbrauch

3. **CSRF Protection**
   - Alle Formulare schützen
   - Token-Pinning
   - Automatische Renewal

4. **Password Hashing**
   - `password_hash()` verwenden
   - bcrypt oder Argon2id
   - Keine Plaintext-Speicherung

### Entwicklungsdokumentation

Diese Dokumentation sollte bei allen folgenden Aktivitäten aktualisiert werden:

- Neue Security-Anforderungen
- API-Änderungen
- Template-Updates
- Database-Schema-Änderungen

### Versionierung

**Feature-Version:** 1.0.0  
**Datum:** 11. April 2026  
**Autor:** Implementation Team  
**Review-Status:** ✅ Reviewd  

---

## Related Documents

- [Authentifizierungsdokumentation](../../../AUTHENTICATION.md)
- [Database Schema](../../..//database.md)
- [E-Mail-Konfiguration](../../..//EMAIL_CONFIGURATION.md)
- [Security Guidelines](../../../SECURITY.md)
