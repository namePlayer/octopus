<?php $this->layout('pageBase') ?>

<div class="page page-center">
    <div class="container container-tight py-4">
        <div class="text-center mb-4">
            <h1><?= $_ENV['SOFTWARE_TITLE'] ?></h1>
        </div>

        <?php foreach($messages as $message): ?>
            <?php $this->insert('element/alert', ['alert' => $message]); ?>
        <?php endforeach; ?>

        <div class="card card-md">
            <div class="card-body">
                <h2 class="h2 text-center mb-4">Anmelden</h2>
                <form action="" method="post">
                    <div class="mb-3">
                        <label class="form-label" for="loginEmail">E-Mail</label>
                        <input type="email" class="form-control" placeholder="info@<?= $_ENV['SOFTWARE_HOST'] ?>" id="loginEmail" name="loginEmail" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label" for="loginPassword">
                            Password
                            <span class="form-label-description">
                                <a href="">Passwort vergessen</a>
                            </span>
                        </label>
                        <div class="input-group input-group-flat">
                            <input type="password" class="form-control" placeholder="Passwort" id="loginPassword" name="loginPassword" required>
                        </div>
                    </div>
                    <div class="form-footer">
                        <button type="submit" class="btn btn-primary w-100">Anmelden</button>
                    </div>
                    <?= $this->generateCsrfField('login') ?>
                </form>
            </div>
        </div>
        <div class="text-center text-secondary mt-3">Noch kein Benutzerkonto? <a href="/authentication/registration" tabindex="-1">Hier Registrieren</a></div>
    </div>
</div>
