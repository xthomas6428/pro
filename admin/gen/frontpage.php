<?php

if (!permissions::has("frontpage")) {
    die(lang('no_perm'));
}

if (isset($_POST['frontpage_submit'])) {
    if (!csrf_check()) {
        return util::error("Invalid CSRF token!");
    }
        
    page::edit('frontpage', $_POST['frontpage_text']);
    $message->add('success', 'Successfully updated front page!');

    cache::clear();
}

?>

<h2>Main page</h2>
<span id="message-location">
	<?php $message->Display(); ?>
</span>
Modify the information displayed on the frontpage<br><br>
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
    <textarea id="frontpage" name="frontpage_text"
              class="form-control tinymce"><?php echo page::get('frontpage', true); ?></textarea>

    <input type="hidden" name="frontpage_submit" value="true">
    <input type="submit" name="frontpage_submit" value="<?= lang('submit'); ?>" class="btn btn-success">
</form>