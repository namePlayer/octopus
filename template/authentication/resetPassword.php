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
                <form action="" method="post">
                    <div class="mb-3">
                        <label class="form-label" for="resetNewPassword">
                            <?= $this->translate('account.general.password') ?>
                        </label>
                        <input type="password" class="form-control" placeholder="<?= $this->translate('account.general.password') ?>"
                               id="resetNewPassword" name="resetNewPassword" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="resetNewPassword">
                            <?= $this->translate('account.general.repeatPassword') ?>
                        </label>
                        <input type="password" class="form-control" placeholder="<?= $this->translate('account.general.password') ?>"
                               id="resetNewPasswordRepeat" name="resetNewPasswordRepeat" required>
                    </div>
                    <div class="form-footer">
                        <button type="submit" class="btn btn-primary w-100">
                            <?= $this->translate('account.forgotPassword.confirm') ?>
                        </button>
                    </div>
                    <?= $this->generateCsrfField('resetPassword') ?>
                </form>
            </div>
        </div>
        <div class="text-center text-secondary mt-3">
            <?= $this->translate('account.forgotPassword.notForgot', [
                    '{{loginHere}}' => '<a href="/authentication/login" tabindex="-1">'.$this->translate('account.forgotPassword.loginHereLinkText').'</a>']) ?>
    </div>
</div>
