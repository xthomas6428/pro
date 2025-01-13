<?php

    if (getSetting('christmas_advent', 'value2') == 0) {
        die('This feature is disabled');
    }

    if (!prometheus::loggedIn()) {
        die('You must be logged in to view the advent calendar');
    }

    if (isset($_GET['claim'])) {
        $notBeen = false;
        $pkg = '';

        if (!advent::claimed($_GET['claim']) && advent::canClaim($_GET['claim'])) {
            $results = advent::claim($_GET['claim']);

            $pkg = null;
            $credits = $results['credits_amount'];

            if ($results['package_id'] !== null) {
                $pkg = $db->getOne("SELECT title FROM packages WHERE id = ?", $results['package_id']);
            }
        } else {
            $notBeen = true;
        }
    }

?>

<?php if (isset($_GET['claim']) && !$notBeen) { ?>
    <div class="darker-box">        
        <?php

            if ($pkg !== null && $credits === null) {
                echo lang('advent_opened', '', [
                    $_GET['claim'],
                    $pkg
                ]);
            } elseif ($pkg !== null && $credits !== null) {
                echo lang('advent_opened_both', 'You have opened day $1 and received $2 and $3 credits!', [
                    $_GET['claim'],
                    $pkg,
                    $credits
                ]);
            } elseif ($pkg === null && $credits !== null) {
                echo lang('advent_opened_credits', 'You have opened day $1 and received $2 credits!', [
                    $_GET['claim'],
                    $credits
                ]);
            } else {
                echo lang('advent_opened_nothing', 'You have opened day $1 unfortunately received nothing :(', [
                    $_GET['claim']
                ]);
            }

        ?>
    </div>
<?php } elseif (isset($_GET['claim']) && $pkg == '' && !$notBeen) { ?>
    <div class="darker-box">
        <?= lang('advent_nopkg'); ?>
    </div>
<?php } elseif (isset($_GET['claim']) && $notBeen) { ?>
    <div class="darker-box">
        Trying to fool the system? This day hasn't been yet, be patient
    </div> 
<?php } ?>

<div class="darker-box">
    <?= lang('advent_text'); ?>
</div>

<div class="darker-box">
    <div class="header">
        <?= lang('advent_calendar'); ?>
    </div>
    <div class="row">
        <?= advent::getPage(); ?>       
    </div>
</div>