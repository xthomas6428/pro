<?php

global $UID, $message, $db;

if (!prometheus::loggedIn()) {
    die('You must be signed in to purchase a package');
}

$error = false;
$msg = '';
$customjob = false;

if (!isset($_GET['uid'])) {
    $for = null;
} else {
    $for = $_GET['uid'];
}

$price = $_GET['price'] ?? null;

if ($_GET['type'] == 'pkg') {
    $verify = new verification('none', $for, $_GET['pid']);
    $verifyArray = $verify->verifyPackage($price);

    $error = $verifyArray['error'];
    $msg = $verifyArray['msg'];
}

$userEmail = null;
if (isset($_GET['gateway']) && $_GET['gateway'] === 'paymentwall') {
    $userEmail = $db->getOne("SELECT email FROM players WHERE uid = ?", $UID);
}

if (isset($_POST['email_submit'])) {
    $error = false;

    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $error = true;
        $message->add('danger', 'This email address is invalid!');
    }

    if (!$error) {
        $db->execute("UPDATE players SET email = ? WHERE uid = ?", [$_POST['email'], $UID]);
        $userEmail = $_POST['email'];
    }
}

?>

<?php if (prometheus::loggedIn() && isset($_GET['pid']) && !isset($_GET['gateway']) && tos::getLast() > getSetting('tos_lastedited', 'value3')) { ?>
    <div class="header">
        <?= lang('select_gateway', 'Select payment method'); ?>
    </div>

    <?php $message->display(); ?>

    <div class="row mb-5">
        <?php if (getSetting('enable_coupons', 'value2') && $_GET['type'] == 'pkg' && getEditPackage($_GET['pid'], 'custom_price') == 0) { ?>
            <div class="col-md-<?php echo getSetting('buy_others', 'value2') ? '6' : '12'; ?>">
                <div class="info-box h-100">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
                        <h2><?= lang('coupon_text'); ?></h2>
                        <input type="text" placeholder="..." class="form-control" name="coupon"
                               value="<?= $_GET['coupon'] ?? ''; ?>">
                        <input type="submit" class="btn btn-prom" value="<?= lang('submit'); ?>" style="margin-top: 5px;" name="coupon_submit">
                    </form>
                </div>
            </div>
        <?php } ?>

        <?php if (!$customjob && getSetting('buy_others', 'value2')) { ?>
            <div class="col-md-<?php echo getSetting('enable_coupons', 'value2') ? '6' : '12'; ?>">
                <div class="info-box h-100">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
                        <h2><?= lang('buying_someone_else'); ?></h2>
                        <input type="text" placeholder="Steam Community ID(7656119xxxxxxxxxx)" class="form-control" name="cuid"
                               value="<?= $_GET['uid'] ?? ''; ?>">
                        <input type="submit" class="btn btn-prom" value="<?= lang('submit'); ?>" name="cuid_submit"
                               style="margin-top: 5px;">
                        <?php echo isset($_GET['uid']) ? '<br><br>' . lang('buying_for') . ' &nbsp;&nbsp;<img src="' . getUserSetting('steam_avatar', $_GET['uid']) . '" width="30px" height="30px"></img>&nbsp;&nbsp;' . getUserSetting('name', $_GET['uid']) . '' : '<br><br>' . lang('buying_yourself'); ?>
                    </form>
                </div>
            </div>
        <?php } ?>
    </div>

    <div class="row">
        <?php

        if (!$error) {
            if (isset($_GET['uid']) && isBlacklisted($_GET['uid'])) {
                echo '<div class="col-12">' . lang("blacklisted_them", "This person is blacklisted from this community, you can not purchase for them") . '</div>';
            } elseif (isset($_GET['uid']) && !getSetting('buy_others', 'value2')) {
                echo '<div class="col-12">' . lang("buy_others_disabled", "Buying for others is disabled on this system") . '</div>';
            } else {
                $gateways = new gateways($_GET['type']);
                $gateways->setId($_GET['pid']);

                $gateways->setPrice($price);
                $gateways->setPlayer($for);

                echo $gateways->display();
            }
        } else {
            echo '<div class="col-12">';
            echo '<h2>' . $msg . '</h2>';
            if (!$customjob) {
                echo '<br>' . lang('someone_else', 'However, you can still buy it for someone else');
            }
            echo '</div>';
        }

        ?>
    </div>

  <script type="text/javascript">
    var stripe = Stripe('<?php echo getSetting('stripe_publishableKey', 'value') ?>');

    $('.buy-with-stripe').on('click', function(e) {
      e.preventDefault();

       $.post($(this).attr('href'), function (data) {
         stripe.redirectToCheckout({
           sessionId: data.trim()
         }).then(function(result) {
           console.log(result);
         });
       });
    });
  </script>
<?php } elseif (isset($_GET['pid']) && isset($_GET['gateway'])) { ?>

    <?php if (prometheus::loggedIn()) { ?>
        <?php if ($_GET['gateway'] == 'paymentwall' && $userEmail !== null) { ?>
            <div class="header">
                Paymentwall
            </div>

            <?php

            if (isset($_GET['uid'])) {
                $uid = $_GET['uid'];
            } else {
                $uid = $_SESSION['uid'];
            }

            echo paymentwall::displayWidget($_GET['pid'], $_GET['uid'], $_GET['type']);

            ?>
        <?php } ?>

        <?php if ($_GET['gateway'] == 'paymentwall' && $userEmail == null) { ?>
            <div class="header">
                Paymentwall
            </div>

            <?php $message->display(); ?>

            <form method="POST">
                <div class="form-group">
                    We need your email address before we continue
                </div>

                <div class="form-group">
                    <input type="email" placeholder="Email" class="form-control" name="email">
                </div>

                <input type="submit" name="email_submit" class="btn btn-default" value="<?= lang('submit'); ?>">
            </form>
        <?php } ?>

        <?php if ($_GET['gateway'] == 'paysafecard') { ?>
            <div class="header">
                Paysafecard
            </div>

            Not implemented yet
        <?php } ?>
    <?php } ?>
<?php } else { ?>
    <div class="header">
        Invalid
    </div>
    Either you have no package id set, or you are not signed in!
<?php } ?>