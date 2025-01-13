<div class="content-page-top">
    <span><i class="fas fa-cogs fa-fw"></i> <?= lang('general_settings'); ?></span>
</div>
<div class="content-outer-hbox">
    <div class="scrollable content-inner">
        <div class="container-fluid">
            <?php
            if (isset($_GET['p'])) {
                include('admin/gen/' . $_GET['p'] . '.php');
            } else {
                include('admin/gen/nav.php');
            }
            ?>
        </div>
    </div>
</div>
