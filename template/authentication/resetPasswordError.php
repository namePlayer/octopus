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
                    <?= $this->translate('account.resetPassword.title') ?>
                </h2>
                <span>
                    <?= $this->translate('account.resetPassword.passwordResetInvalid') ?>
                </span>
            </div>
        </div>
        <div class="text-center text-secondary mt-3">
            <?= $this->translate('account.forgotPassword.notForgot', [
                    '{{loginHere}}' => '<a href="/authentication/login" tabindex="-1">'.$this->translate('account.forgotPassword.loginHereLinkText').'</a>']) ?>
    </div>
</div>
