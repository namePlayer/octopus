<?php declare(strict_types=1); ?>

<?= $this->include('pageBase.php'); ?>

<h2><?= $this->translate('forgotFormTitle'); ?></h2>

<form method="post" action="<?= $this->host() ?>/authentication/forgot-password" class="form">
    <p><?= $this->translate('forgotFormDescription'); ?></p>
    <div class="form-group">
        <label for="email"><?= $this->translate('forgotFormEmail'); ?></label>
        <input type="email" name="email" id="email" value="<?= $view->email ?? '' ?>" required>
    </div>
    <div class="form-group">
        <button type="submit" class="btn-primary"><?= $this->translate('forgotFormSubmit'); ?></button>
    </div>
</form>

<?php if (isset($_SESSION['message'])): ?>
    <?= $this->alert($_SESSION['message']); ?>
    <?php unset($_SESSION['message']); ?>
<?php endif; ?>

<script>
    // <?= $this->translate('forgotFormInstructions'); ?>
    document.querySelector('form').addEventListener('submit', function(e) {
        const email = document.querySelector('input[name="email"]').value;
        if (email === '') {
            e.preventDefault();
            alert('<?= $this->translate('forgotFormEmailEmpty'); ?>');
        }
    });
</script>
