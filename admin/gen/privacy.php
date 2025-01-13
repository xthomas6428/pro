<?php

if (!permissions::has("privacy")) {
    die(lang('no_perm'));
}

if (isset($_POST['privacy_submit'])) {
    if (!csrf_check()) {
        return util::error("Invalid CSRF token!");
    }

    if (isset($_POST['privacy_enable'])) {
        $enable = 1;
    } else {
        $enable = 0;
    }

    setSetting($enable, 'privacy_enable', 'value2');

    if (isset($_POST['privacy_policy'])) {
        page::edit('privacy', $_POST['privacy_policy']);
    }

    $message->Add('success', 'Successfully updated the privacy policy!');
    cache::clear('settings');

    prometheus::log('Modified the privacy policy', $_SESSION['uid']);
}

?>

<form method="POST" style="width: 100%;" class="form-horizontal form" role="form">
    <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
    <div class="form-group">
        <div class="col-12">
            <h2><?= lang('privacy', 'Privacy Policy'); ?></h2>

			<span id="message-location">
				<?php $message->Display(); ?>
			</span>
        </div>
    </div>

    <div class="form-group form-row">
        <div class="col-11">
            <div class="checkbox">
                <input type="checkbox" id="privacy_enable"
                       name="privacy_enable" <?php echo getSetting('privacy_enable', 'value2') == 1 ? 'checked' : ''; ?>>
                <label><?= lang('enable_privacy', 'Enable Privacy Policy'); ?></label>
            </div>
        </div>

        <div class="col-1">
            <button type="button" class="help-box" style="margin-top: 7px;" data-toggle="tooltip"
                    data-placement="bottom" title="Do you want to enable the privacy policy page(Link in the footer etc)">?
            </button>
        </div>
    </div>

    <div class="form-group form-row">
        <div class="col-12">
            <textarea id="privacy_policy" name="privacy_policy" class="tinymce"><?php echo page::get('privacy'); ?></textarea>
        </div>
    </div>

    <div class="form-group form-row">
        <div class="col-12">
            <input type="hidden" name="privacy_submit" value="true">
            <input type="submit" name="privacy_submit" value="<?= lang('submit'); ?>" class="btn btn-success"
                   style="margin-top: 5px;">
        </div>
    </div>
</form>