<?php

if (!permissions::has("gateways")) {
    die(lang('no_perm'));
}

if (isset($_POST['stripe_submit'])) {
    if (!csrf_check()) {
        return util::error("Invalid CSRF token!");
    }

    if (isset($_POST['enable_stripe'])) {
        $enable_stripe = true;
    } else {
        $enable_stripe = false;
    }

    if ($enable_stripe && gateways::enabled('stripe')) {
        // Do success stuff
        $stripe_apiKey = strip_tags($_POST['stripe_apiKey']);
        $stripe_publishableKey = strip_tags($_POST['stripe_publishableKey']);
        $stripe_webhookSecret = strip_tags($_POST['stripe_webhookSecret']);

        setSetting($stripe_apiKey, 'stripe_apiKey', 'value');
        setSetting($stripe_publishableKey, 'stripe_publishableKey', 'value');
        setSetting($stripe_webhookSecret, 'stripe_webhookSecret', 'value');
    } else {
        gateways::setState('stripe', false);
    }

    if ($enable_stripe && !gateways::enabled('stripe')) {
        gateways::setState('stripe', true);
    }

    $message->Add('success', 'Successfully updated stripe settings!');
    prometheus::log('Modified the stripe settings', $_SESSION['uid']);

    cache::clear('settings');
}

?>

<h2>Stripe</h2>
Stripe Payment Gateway settings. Stripe allows you to pay with a credit, debit card or Apple Pay depending on your Stripe settings.<br><br>
<a href="http://wiki.prometheusipn.com/index.php?title=Integration:gateways#Stripe" target="_blank">Click here for setup instructions.</a><br><br>

<form method="POST" style="width: 100%;" class="form-horizontal" role="form">
    <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">

    <div class="row">
      <div class="col-12">
        <div class="form-group">
            <?php $message->Display(); ?>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-12">
        <div class="form-group">
          <div class="checkbox">
            <input type="checkbox" name="enable_stripe" <?php echo gateways::enabled('stripe') ? 'checked' : ''; ?>>
            <label>Enable Stripe</label>
          </div>
        </div>
      </div>
    </div>

    <?php if (gateways::enabled('stripe')) { ?>
        <hr>
        <div class="form-group row">
            <label class="col-2 control-label">API Key</label>

            <div class="col-10">
                <input type="text" class="form-control" name="stripe_apiKey" placeholder="Stripe API Key"
                       value="<?= getSetting('stripe_apiKey', 'value'); ?>">
            </div>
        </div>
        <div class="form-group row">
            <label class="col-2 control-label">Publishable Key</label>

            <div class="col-10">
                <input type="text" class="form-control" name="stripe_publishableKey"
                       placeholder="Stripe Publishable Key"
                       value="<?= getSetting('stripe_publishableKey', 'value'); ?>">
            </div>
        </div>
        <div class="form-group row">
            <label class="col-2 control-label">Webhook Secret</label>

            <div class="col-10">
                <input type="text" class="form-control" name="stripe_webhookSecret"
                       placeholder="Stripe Webhook Secret Key"
                       value="<?= getSetting('stripe_webhookSecret', 'value'); ?>">
            </div>
        </div>
        <hr>
    <?php } ?>
    <div class="form-group row">
        <div class="col-10">
            <input type="submit" name="stripe_submit" value="<?= lang('submit'); ?>" class="btn btn-success"
                   style="margin-top: 5px;">
        </div>
    </div>
</form>
