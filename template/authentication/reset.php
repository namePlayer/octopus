<?php declare(strict_types=1); ?>

<?= $this->include('pageBase.php'); ?>

<h2><?= $this->translate('resetFormTitle'); ?></h2>

<?php if (!empty($account)): ?>
    <?= $this->alert('<?= $this->translate('resetFormMessage'); ?>'); ?>
<?php endif; ?>

<form method="post" action="<?= $this->host() ?>/authentication/reset/{token}" class="form">
    <div class="form-group">
        <label for="password"><?= $this->translate('resetFormPassword'); ?></label>
        <input type="password" name="password" id="password" required minlength="8">
    </div>
    <div class="form-group">
        <button type="submit" class="btn-primary"><?= $this->translate('resetFormSubmit'); ?></button>
    </div>
</form>

<?php if (isset($_SESSION['message'])): ?>
    <?= $this->alert($_SESSION['message']); ?>
    <?php unset($_SESSION['message']); ?>
<?php endif; ?>

<script>
    // <?= $this->translate('resetFormInstructions'); ?>
    document.querySelector('form').addEventListener('submit', function(e) {
        const password = document.querySelector('input[name="password"]').value;
        if (password.length < 8) {
            e.preventDefault();
            alert('<?= $this->translate('resetFormPasswordTooShort'); ?>');
        }
    });
</script>
