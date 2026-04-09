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
                    <?= $this->translate('account.login.title') ?>
                </h2>
                <form action="" method="post">
                    <div class="mb-3">
                        <label class="form-label" for="loginEmail">
                            <?= $this->translate('account.general.email') ?>
                        </label>
                        <input type="email" class="form-control" placeholder="info@<?= $_ENV['SOFTWARE_HOST'] ?>" id="loginEmail" name="loginEmail" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label" for="loginPassword">
                            <?= $this->translate('account.general.password') ?>
                            <span class="form-label-description">
                                <a href="">
                                    <?= $this->translate('account.login.forgotPassword') ?>
                                </a>
                            </span>
                        </label>
                        <div class="input-group input-group-flat">
                            <input type="password" class="form-control" placeholder="<?= $this->translate('account.general.password') ?>" id="loginPassword" name="loginPassword" required>
                        </div>
                    </div>
                    <div class="form-footer">
                        <button type="submit" class="btn btn-primary w-100">
                            <?= $this->translate('account.login.confirmButton') ?>
                        </button>
                    </div>
                    <?= $this->generateCsrfField('login') ?>
                </form>
            </div>
        </div>
        <div class="text-center text-secondary mt-3">
            <?= $this->translate('account.login.notHaveAccount', [
                    '{{registerHere}}' => '<a href="/authentication/registration" tabindex="-1">'.$this->translate('account.login.registerHereLinkText').'</a>']) ?>
    </div>
</div>
