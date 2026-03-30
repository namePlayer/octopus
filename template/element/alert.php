<?php if ($alert['type'] == 'primary') : ?>
    <div class="alert alert-primary" role="alert">
        <?= $alert['message'] ?>
    </div>
<?php elseif ($alert['type'] == 'secondary') : ?>
    <div class="alert alert-secondary" role="alert">
        <?= $alert['message'] ?>
    </div>
<?php elseif ($alert['type'] == 'success') : ?>
    <div class="alert alert-success" role="alert">
        <?= $alert['message'] ?>
    </div>
<?php elseif ($alert['type'] == 'danger') : ?>
    <div class="alert alert-danger" role="alert">
        <?= $alert['message'] ?>
    </div>
<?php elseif ($alert['type'] == 'warning') : ?>
    <div class="alert alert-warning" role="alert">
        <?= $alert['message'] ?>
    </div>
<?php elseif ($alert['type'] == 'info') : ?>
    <div class="alert alert-info" role="alert">
        <?= $alert['message'] ?>
    </div>
<?php elseif ($alert['type'] == 'light') : ?>
    <div class="alert alert-light" role="alert">
        <?= $alert['message'] ?>
    </div>
<?php elseif ($alert['type'] == 'dark') : ?>
    <div class="alert alert-dark" role="alert">
        <?= $alert['message'] ?>
    </div>
<?php else: ?>
    <div class="alert alert-<?= $alert['type'] ?>" role="alert">
        <?= $alert['message'] ?>
    </div>
<?php endif; ?>
