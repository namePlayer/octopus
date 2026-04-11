# Feature: Passwort-Verlust (Password Reset)

## Übersicht
Implementierung einer vollständigen Passwort-Reset-Funktionalität für das Authentifizierungssystem.

**Zustand:** Not gestartet

## Todo Liste

- [x] **1. Datenbank-Erweiterung**

  **Zusammenfassung:**
  Die Accounts-Tabelle benötigt neue Spalten für Passwort-Reset-Token und Ablaufzeit.
  
  **Dateien:**
  - `database.sql` anpassen (ALTER TABLE Statements hinzufügen)
  
  **Details:**
  - Spalte `passwordResetToken` (VARCHAR(64), nullable, UNIQUE Index)
  - Spalte `passwordResetTokenExpires` (DATETIME, nullable)
  
  **Anforderungen:**
  - PRIMARY KEY soll `id` bleiben
  - UNIQUE INDEX auf `passwordResetToken` für schnelle Lookups

---

- [ ] **2. PasswordResetTokenService erstellen**

  **Zusammenfassung:**
  Service für die Verwaltung von Passwort-Reset-Token mit Generierung, Validierung und Expiration.
  
  **Dateien:**
  - `src/Authentication/Service/PasswordResetTokenService.php` (erstellen)
  
  **Details:**
  - Constructor injiziert `Logger`
  - `generateToken($account)` - 64-char hex token via `random_bytes()`
  - `isValidToken($token)` - existiert UND nicht abgelaufen
  - `expireToken($token)` - nach erfolgreichem Reset oder Ablauf
  - Optional: `validateAndExpire($token, $account)` - atomare Operation
  
  **Methodensignaturen:**
  ```php
  public function generateToken(Account $account): string
  public function isValidToken(string $token, ?Account $account = null): bool
  public function expireToken(string $token): bool
  public function validateAndExpire(string $token, ?Account $account = null): ?Account
  ```

---

- [x] **2. PasswordResetTokenService erstellen** ✅

  **Zusammenfassung:**
  Service für die Verwaltung von Passwort-Reset-Token mit Generierung, Validierung und Expiration.
  
  **File:**
  `- src/Authentication/Service/PasswordResetTokenService.php` (existiert)
  
  **Details:**
  - `generateToken(Account $account)` - 64-char hex token via `random_bytes()`
  - `isValidToken(string $token, ?Account $account = null): bool` - existiert UND nicht abgelaufen
  - `expireToken(string $token)` - nach erfolgreichem Reset oder Ablauf  
  - `validateAndExpire(string $token, ?Account $account = null): ?Account` - atomare Operation
  - `Connection` und `Logger` werden injiziert
  - Token wird direkt via UPDATE in DB gespeichert
  
  **Status:** Kompletter Implementierung vorhanden
  
---

- [x] **3. EmailService erstellen**

  **Zusammenfassung:**
  Wrapper-Service um PHPMailer für E-Mail-Versand.
  
  **Dateien:**
  - `src/Base/Service/EmailService.php` (erstellen)
  
  **Details:**
  - Constructor injiziert `Logger`
  - Dependency: `PHPMailer\PHPMailer\PHPMailer` (via container oder direkte Instanziierung)
  - `sendPasswordResetEmail(Account $account, string $token)`
  - `sendEmail(string $to, string $subject, string $body)` (optionaler Generalzweck-Method)
  
  **Methodensignaturen:**
  ```php
  public function sendPasswordResetEmail(Account $account, string $token): bool
  public function setEmailAddress(string $email)
  public function setSubject(string $subject)
  public function setBody(string $body)
  public function send(): bool
  ```

---

- [ ] **4. EmailTemplate erstellen**

  **Zusammenfassung:**
  HTML-Vorlage für die Passwort-Reset-E-Mail.
  
  **Dateien:**
  - `template/email/passwordReset.php` (erstellen)
  
  **Details:**
  - HTML-E-Mail mit Reset-Link (als Bild-Link für Client-Kompatibilität)
  - Platzhalter: `{{ $account->firstname }}`, `{{ $token }}`, `{{ $host }}`
  - Responsive Design
  - Branding mit Unternehmens-Logo (optional)
  
  **Beispiel-Inhalt:**
  ```php
  {{ /* template variable: account, token, host, appName */ }}
  
  <!DOCTYPE html>
  <html>
  <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
  </head>
  <body style="font-family: Arial, sans-serif; padding: 20px;">
      <h1>Passewort-Zurücksetzen</h1>
      <p>Hallo {{ account->firstname }},</p>
      <p>Klicken Sie auf den folgenden Link zum Zurücksetzen Ihres Passworts:</p>
      <p>
          <a href="https://{{ host }}/reset/{{ token }}" style="background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
              Passwort zurücksetzen
          </a>
      </p>
      <p>Der Link ist in {{ token|date:'%d.%m.%Y %k:%i' }} abgelaufen.</p>
      <p>Viele Grüße,<br>das {{ appName }}-Team</p>
  </body>
  </html>
  ```

---

- [x] **5. ForgotPasswordController erstellen** ✅

  **Zusammenfassung:**
  Controller für die Anforderung eines Passwort-Reset.
  
  **Dateien:**
  - `src/Authentication/Controller/ForgotPasswordController.php` (erfolgt)
  
  **Status:** Implemented
  
  **Details:**
  - Dependencies: `EmailService`, `AccountService`, `AlertService`, `TranslationService`, `Logger`, `CsrfProtectionService`
  - GET `/authentication/forgot-password` - Formular anzeigen
  - POST `/authentication/forgot-password` - Reset anfordern + E-Mail senden
  - Email wird automatisch nach erfolgreicher Anfrage versendet (Account gefunden)
  
  **Methodensignaturen:**
  ```php
  public function __construct(
      EmailService $emailService,
      AccountService $accountService,
      AlertService $alertService,
      TranslationService $translationService,
      Logger $logger
  )
  
  public function viewForgotPasswordForm(RequestInterface $request): Response
  public function requestPasswordReset(RequestInterface $request): Response
  ```

---

- [ ] **6. ResetPasswordController erstellen**

  **Zusammenfassung:**
  Controller für das Zurücksetzen des Passworts.
  
  **Dateien:**
  - `src/Authentication/Controller/ResetPasswordController.php` (erstellen)
  
  **Details:**
  - Dependencies: `PasswordResetTokenService`, `AuthenticationService`, `AlertService`, `TranslationService`, `Logger`
  - GET `/authentication/reset/:uuid` - Formular anzeigen
  - POST `/authentication/reset/:uuid` - Passwort aktualisieren
  
  **Methodensignaturen:**
  ```php
  public function __construct(
      PasswordResetTokenService $tokenService,
      AuthenticationService $authenticationService,
      AlertService $alertService,
      TranslationService $translationService,
      Logger $logger
  )
  
  public function viewResetPasswordForm(string $token, RequestInterface $request): Response
  public function resetPassword(string $token, string $newPassword, RequestInterface $request): Response
  ```

---

- [ ] **7. Exception-Klassen erstellen**

  **Zusammenfassung:**
  Ausnahmen für Fehlerfälle im Reset-Prozess.
  
  **Dateien:**
  - `src/Authentication/Exception/PasswordResetTokenExpiredException.php` (erstellen)
  - `src/Authentication/Exception/PasswordResetTokenInvalidException.php` (erstellen)
  - `src/Authentication/Exception/PasswordResetTokenAlreadyUsedException.php` (erstellen)

  **Details:**
  ```php
  class PasswordResetTokenExpiredException extends RuntimeException
  class PasswordResetTokenInvalidException extends RuntimeException
  class PasswordResetTokenAlreadyUsedException extends RuntimeException
  ```

---

- [ ] **8. Dependency Injection Konfigurieren**

  **Zusammenfassung:**
  Register die neuen Services und Controller im DI Container.
  
  **Dateien:**
  - `config/container.php` (anpassen)
  
  **Änderungen:**
  ```php
  // EmailService
  $container->add(\App\Base\Service\EmailService::class)
      ->addArgument(Logger::class);
  
  // PasswordResetTokenService
  $container->add(\App\Authentication\Service\PasswordResetTokenService::class)
      ->addArgument(Logger::class);
  
  // Controllers
  $router->post('/authentication/forgot-password', '\App\Authentication\Controller\ForgotPasswordController:requestPasswordReset');
  
  // Oder als alternative Syntax:
  $router->get('/authentication/forgot-password', '\App\Authentication\Controller\ForgotPasswordController:viewForgotPasswordForm');
  ```

---

- [ ] **9. Router Konfiguration anpassen**

  **Zusammenfassung:**
  Neue Routes für Passwort-Reset Funktionalität hinzufügen.
  
  **Dateien:**
  - `config/routes.php` (anpassen)
  
  **Details:**
  ```php
  // GET: Formular anzeigen
  $router->get('/authentication/forgot-password', 'App\Authentication\Controller\ForgotPasswordController:viewForgotPasswordForm')
      ->setHost($_ENV['SOFTWARE_HOST']);
  
  // POST: Reset anfordern
  $router->post('/authentication/forgot-password', 'App\Authentication\Controller\ForgotPasswordController:requestPasswordReset')
      ->setHost($_ENV['SOFTWARE_HOST']);
  
  // GET: Reset-Formular anzeigen
  $router->get('/authentication/reset/{token}', 'App\Authentication\Controller\ResetPasswordController:viewResetPasswordForm')
      ->setHost($_ENV['SOFTWARE_HOST']);
  
  // POST: Passwort setzen
  $router->post('/authentication/reset/{token}', 'App\Authentication\Controller\ResetPasswordController:resetPassword')
      ->setHost($_ENV['SOFTWARE_HOST']);
  ```
  
  **Hinweis:** Route für `/reset/{token}` entweder als GET + POST (als get/setHost) oder mit Param-Handling implementieren

---

- [ ] **10. Login-Seite anpassen**

  **Zusammenfassung:**
  "Vergessenes Kennwort"-Link in Login-Template hinzufügen.
  
  **Dateien:**
  - `template/authentication/login.php` (anpassen)
  - `template/authentication/login.php` (anpassen - deutsche Übersetzungen)
  
  **Details:**
  - Link zu `/authentication/forgot-password` hinzufügen
- [ ] **11. E-Mail-Template für Reset-Link hinzufügen**

  **Zusammenfassung:**
  E-Mail-Template für den Reset-Link.

  **Dateien:**
  - `template/email/passwordReset.php` (erstellen)

  **Details:**
  - HTML-Vorlage mit Platzhaltern
  - Reset-Link mit Token
  - Ablaufdatum angeben

---

- [ ] **12. Rate-Limitierung und Sicherheitsmaßnahmen**

  **Zusammenfassung:**
  Sicherheitsmaßnahmen gegen brute-force attacks implementieren.
  
  **Dateien neu zu erstellen:**
  - `src/Authentication/Service/RateLimitService.php` (erstellen)
  
  **Details:**
  ```php
  public function checkRateLimit(string $email): bool
  public function resetLimit(string $email): void
  ```

  **Umsetzung:**
  - Maximal 3 Versuche pro Stunde pro E-Mail
  - Rate-Limit-Service injizieren in Controller
  - Log nach Rate-Limit-Verstoß

---

### Projekt-Status
Gesamte Liste von Aufgaben:

| Aufgabe                                                            | Status   |
|--------------------------------------------------------------------|----------|
| 1. Datenbank-Erweiterung                                            | ✅ |
| 2. PasswordResetTokenService erstellen                              | ⬜  |
| 3. EmailService erstellen                                            | ✅ |
| 4. EmailTemplate erstellen                                           | ✅ |
| 5. ForgotPasswordController erstellen                                | ⬜  |
| 6. ResetPasswordController erstellen                                 | ⬜  |
| 7. Exception-Klassen erstellen                                        | ✅ |
| 7.5 DI Container anpassen                                            | ⬜  |
| 8. Router Konfiguration anpassen                                     | ⬜  |
| 9. Login-Seite anpassen                                              | ⬜  |
| 10. Sicherheitsmaßnahmen (Rate-Limiting)                             | ⬜  |
