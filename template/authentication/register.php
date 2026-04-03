<?php $this->layout('pageBase') ?>

<div class="page page-center">
    <div class="container container-tight py-4">
        <div class="text-center mb-4">
            <h1><?= $_ENV['SOFTWARE_TITLE'] ?></h1>
        </div>

        <?= $this->outputAlerts() ?>

        <form class="card card-md" action="" method="post">
            <div class="card-body">
                <h2 class="card-title text-center mb-4">Registrieren</h2>
                <div class="mb-3">
                    <label class="form-label" for="registrationEmail">E-Mail</label>
                    <input type="email" class="form-control" placeholder="info@<?= $_ENV['SOFTWARE_HOST'] ?>"
                           id="registrationEmail" name="registrationEmail" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="registrationPassword">Passwort</label>
                    <div class="input-group input-group-flat">
                        <input type="password" class="form-control" placeholder="Passwort" id="registrationPassword"
                               name="registrationPassword" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="registrationRepeatPassword">Passwort wiederholen</label>
                    <div class="input-group input-group-flat">
                        <input type="password" class="form-control" placeholder="Passwort" id="registrationRepeatPassword"
                               name="registrationRepeatPassword" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-check">
                        <input type="checkbox" class="form-check-input" id="registrationAcceptTerms" name="registrationAcceptTerms" required>
                        <span class="form-check-label">Ich akzeptiere die <a href="" tabindex="-1">Nutzungsbedingungen</a>.</span>
                    </label>
                </div>
                <div class="form-footer">
                    <button type="submit" class="btn btn-primary w-100">Registrieren</button>
                </div>
                <?= $this->generateCsrfField('registration') ?>
            </div>
        </form>
        <div class="text-center text-secondary mt-3">Bereits ein Konto? <a href="/authentication/login" tabindex="-1">Hier Anmelden</a></div>
    </div>
</div>
