<?php

return [
    'account' => [
        'registration' => [
            'title' => 'Registrierung',
            'confirmButton' => 'Registrieren',
            'acceptTos' => 'Ich akzeptiere die {{termsOfService}}.',
            'alreadyHaveAccount' => 'Bereits ein Konto? {{loginHere}}.',
            'loginHereLinkText' => 'Hier anmelden',
            'messages' => [
                'success' => 'Das Benutzerkonto wurde erfolgreich angelegt.',
                'failure' => 'Das Benutzerkonto konnte aufgrund eines Fehlers nicht angelegt werden.',
                'csrfFailure' => 'Das Benutzerkonto konnte aufgrund eines CSRF Fehlers nicht angelegt werden.',
                'doesNotAcceptTerms' => 'Das Benutzerkonto wurde nicht angelegt, da den Nutzungsbedingungen nicht zugestimmt wurde.'
            ]
        ],
        'login' => [
            'title' => 'Anmeldung',
            'confirmButton' => 'Anmelden',
            'notHaveAccount' => 'Noch kein Konto? {{registerHere}}.',
            'registerHereLinkText' => 'Hier Registrieren',
            'forgotPassword' => 'Passwort vergessen?',
        ],
        'forgotPassword' => [
            'title' => 'Passwort vergessen',
            'notForgot' => 'Doch nicht vergessen? {{loginHere}}',
            'loginHereLinkText' => 'Hier anmelden',
            'confirm' => 'Passwort zurücksetzen',
            'messages' => [
                'csrfFailure' => 'Aufgrund eines Fehlers in der CSRF Validierung kann die Anfrage nicht abgeschlossen werden.',
            ]
        ],
        'resetPassword' => [
            'title' => 'Passwort zurücksetzen',
            'passwordResetInvalid' => 'Der Passwort Reset kann nicht durchgeführt werden. Möglicherweise wurde der Token bereits verwendet.',
            'messages' => [
                'success' => 'Dein Passwort wurde erfolgreich zurückgesetzt. Du kannst dich nun mit deinem neuen Passwort anmelden.',
                'csrfFailure' => 'Aufgrund eines Fehlers in der CSRF Validierung kann die Anfrage nicht abgeschlossen werden.',
                'tokenWasInvalidated' => 'Der Token wurde bereits ungültig gemacht. Bitte fordere einen neuen Token an.',
                'tokenStillValid' => 'Aufgrund eines Fehlers ist das Ändern des Passwortes fehlgeschlagen. Bitte versuche es erneut.'
            ]
        ],
        'general' => [
            'email' => 'E-Mail',
            'password' => 'Passwort',
            'repeatPassword' => 'Passwort wiederholen',
            'messages' => [
                'passwordTooShort' => 'Das angegebene Passwort ist zu kurz.',
                'passwordRepeatWrong' => 'Die angegebenen Passwörter stimmen nicht überein.',
                'emailTooLong' => 'Die Länge der angegebenen E-Mail-Adresse überschreitet das zulässige Maximum.',
                'invalidEmail' => 'Die angegebene E-Mail-Adresse ist ungültig.'
            ]
        ]
    ],
    'legal' => [
        'termsOfService' => 'Nutzungsbedingungen',
    ]
];
