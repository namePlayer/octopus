<?php declare(strict_types=1); ?>

<?= $this->include('pageBase.php'); ?>

<div class="page page-center">
    <div class="container container-tight py-4">
        <div class="text-center mb-4">
            <h1><?= $_ENV['SOFTWARE_TITLE'] ?></h1>
        </div>

        <?= $this->outputAlerts() ?>

        <?php if (!empty($account)): ?>
            <div class="alert alert-success mb-4">
                <?= $this->translate('account.resetPassword.description'); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($account)): ?>
            <div class="alert alert-error mb-4">
                <?= $this->translate('account.resetPassword.tokenMissing'); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($account)): ?>
            <div class="card card-md">
                <div class="card-body">
                    <h2 class="card-title text-center mb-4">
                        <?= $this->translate('account.resetPassword.title') ?>
                    </h2>
                    <form action="" method="post">
                        <div class="mb-3">
                            <label class="form-label" for="password">
                                <?= $this->translate('account.general.password') ?>
                            </label>
                            <div class="input-group input-group-flat">
                                <input type="password" class="form-control" placeholder="<?= $this->translate('account.general.password') ?>"
                                       id="password" name="password" required minlength="8">
                            </div>
                        </div>
                        <div class="form-footer">
                            <button type="submit" class="btn btn-primary w-100">
                                <?= $this->translate('account.resetPassword.submit') ?>
                            </button>
                        </div>
                        <?= $this->generateCsrfField('reset-password') ?>
                    </form>
                </div>
            </div>
            <div class="text-center text-secondary mt-3">
                <?= $this->translate('account.resetPassword.backToLogin', [
                        '{{loginHere}}' => '<a href="/authentication/login" tabindex="-1">'.$this->translate('account.login.title').'</a>']) ?>
            </div>
        <?php endif; ?>
    </div>
</div>
