<?php

if (getSetting('disable_news', 'value2') == 0) {
    $news_width = 9; ?>
    <div class="col-12 col-md-3">
        <div class="header">
            <?= lang('news'); ?>
        </div>
        <?php echo news::get(); ?>
    </div>
    <?php
} else {
        $news_width = 12;
    }

?>
