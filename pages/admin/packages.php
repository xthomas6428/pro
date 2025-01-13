<?php if (!permissions::has("packages")) {
    die(lang('no_perm'));
} ?>

<?php

if (isset($_POST['package_disable'])) {
    if (!csrf_check()) {
        return util::error("Invalid CSRF token!");
    }

    $id = $_POST['hidden'];

    $db->execute("UPDATE packages SET enabled = 0 WHERE id = ?", $id);
    prometheus::log('Disabled package ' . $id, $_SESSION['uid']);
}

if (isset($_POST['package_enable'])) {
    if (!csrf_check()) {
        return util::error("Invalid CSRF token!");
    }

    $id = $_POST['hidden'];

    $db->execute("UPDATE packages SET enabled = 1 WHERE id = ?", $id);
    prometheus::log('Enabled package ' . $id, $_SESSION['uid']);
}

if (isset($_POST['package_delete'])) {
    if (!csrf_check()) {
        return util::error("Invalid CSRF token!");
    }

    $id = $_POST['hidden'];

    $db->execute("DELETE FROM packages WHERE id = ?", $id);
    prometheus::log('Deleted package ' . $id, $_SESSION['uid']);
}

if (isset($_POST['package_set_inactive'])) {
    if (!csrf_check()) {
        return util::error("Invalid CSRF token!");
    }

    $id = $_POST['hidden'];

    setSetting(date('Y-m-d H:i:s'), 'actions_lastupdated', 'value3');
    $db->execute("UPDATE actions SET active = 0, delivered = 0 WHERE package = ?", $id);

    prometheus::log('Inactivated package ' . $id . ' for all users', $_SESSION['uid']);
}

if (isset($_POST['package_set_active'])) {
    if (!csrf_check()) {
        return util::error("Invalid CSRF token!");
    }

    $id = $_POST['hidden'];

    setSetting(date('Y-m-d H:i:s'), 'actions_lastupdated', 'value3');
    $db->execute("UPDATE actions SET active = 1, delivered = 0 WHERE package = ?", $id);

    prometheus::log('Activated package ' . $id . ' for all users', $_SESSION['uid']);
}

if (isset($_POST['package_recalculate'])) {
    if (!csrf_check()) {
        return util::error("Invalid CSRF token!");
    }

    $id = $_POST['hidden'];

    $packageActions = $db->getOne("SELECT actions FROM packages WHERE id = ?", $id);
    $actions = $db->getAll("SELECT id, actions FROM actions WHERE package = ?", $id);

    $packageActions = json_decode($packageActions, true);

    foreach ($actions as $action) {
        $id = $action['id'];
        $action = json_decode($action['actions'], true);

        foreach ($updateable_actions as $upd) {
            if (isset($action[$upd]) && isset($packageActions[$upd])) {
                $packageUpd = $packageActions[$upd];
                $updAction = $action[$upd];

                // if these are not the same they have been updated and we need to update this action
                if ($packageUpd !== $updAction) {
                    $action[$upd] = $packageUpd;
                    $newJson = json_encode($action);

                    $db->execute("UPDATE actions SET actions = ?, delivered = 0 WHERE id = ?", [$newJson, $id]);
                }
            }
        }
    }

    prometheus::log('Recalculated actions for package ' . $id, $_SESSION['uid']);
}

?>

<a href="admin.php?page=packages&action=customjobs" class="btn btn-prom" style="margin-bottom: 20px;">View active custom
    jobs</a>
<a href="admin.php?page=packages&action=move" class="btn btn-prom" style="margin-bottom: 20px;">Move packages around</a>

<table class="table table-striped">
    <thead>
    <th>ID</th>
    <th><?= lang('title'); ?></th>
    <th><?= lang('servers'); ?></th>
    <th><?= lang('actions'); ?></th>
    </thead>

    <tbody class="tbody-center">
    <?php echo dashboard::packageList(); ?>
    </tbody>
</table>