<?php $this->layout('pageBase') ?>

<div class="page page-center">
    <div class="container container-tight py-4">
        <div class="text-center mb-4">
            <h1><?= $_ENV['SOFTWARE_TITLE'] ?></h1>
        </div>

        <?= $this->outputAlerts() ?>

        <div class="card card-md">
            <div class="card-body">
                <h2 class="h2 text-center mb-4">
                    <?= $this->translate('account.forgotPassword.title') ?>
                </h2>
                <form action="" method="post">
                    <div class="mb-3">
                        <label class="form-label" for="forgotPasswordEmail">
                            <?= $this->translate('account.general.email') ?>
                        </label>
                        <input type="email" class="form-control" placeholder="info@<?= $_ENV['SOFTWARE_HOST'] ?>" id="forgotPasswordEmail" name="forgotPasswordEmail" required>
                    </div>
                    <div class="form-footer">
                        <button type="submit" class="btn btn-primary w-100">
                            <?= $this->translate('account.forgotPassword.confirm') ?>
                        </button>
                    </div>
                    <?= $this->generateCsrfField('forgotPassword') ?>
                </form>
            </div>
        </div>
        <div class="text-center text-secondary mt-3">
            <?= $this->translate('account.forgotPassword.notForgot', [
                    '{{loginHere}}' => '<a href="/authentication/login" tabindex="-1">'.$this->translate('account.forgotPassword.loginHereLinkText').'</a>']) ?>
    </div>
</div>
