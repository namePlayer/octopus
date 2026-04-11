<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; padding: 20px;">
    <h1>{{ appName }} - Passwort zurücksetzen</h1>
    <p>Hallo {{ account->firstname }},</p>
    <p>Jemand hat auf der Plattform {{ appName }} einen Link zur Änderung des Passworts geklickt.</p>
    <p>Klicken Sie auf den folgenden Link zum Zurücksetzen Ihres Passworts:</p>
    <p>
        <a href="https://{{ host }}/reset/{{ token }}" style="background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
            Passwort zurücksetzen
        </a>
    </p>
    <p>Der Link ist bis {{ token|date:'%d.%m.%Y %H:%M' }} in der lokalen Zeitzone abgelaufen.</p>
    <p>Falls Sie keinen Passwort-Reset angefordert haben, können Sie diesen E-Mail ignorieren. Ihr Passwort bleibt weiterhin unverändert.</p>
    <p>Viele Grüße,<br>das {{ appName }}-Team</p>
</body>
</html>
