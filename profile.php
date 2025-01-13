<?php

global $message, $db;

session_start();

$page = 'profile';
$page_title = 'Profile';

require_once('inc/functions.php');

raffle::end();

if (isset($_GET['page'])) {
    $_GET['page'] = '';
}

if (isset($_POST['transfer_submit'])) {
    if (!csrf_check()) {
        return util::error("Invalid CSRF token!");
    }

    $error = false;

    if ($_POST['transfer_amount'] < 1 or !is_numeric($_POST['transfer_amount'])) {
        $error = true;
        $message->add('danger', 'You need to enter an amount above 1 that is numeric!');
    }

    if (credits::get($_SESSION['uid']) < $_POST['transfer_amount']) {
        $error = true;
        $message->add('danger', 'You can\'t afford this!');
    }

    if (!$error) {
        credits::transfer($_GET['transfer'], $_POST['transfer_amount']);

        $name = $db->getOne("SELECT name FROM players WHERE uid = ?", $_SESSION['uid']);
        prometheus::log($name . ' sent ' . $_POST['transfer_amount'] . ' credits to ' . $_GET['transfer'], $_SESSION['uid']);

        $message->add('success', 'Successfully transfered some credits!');
    }
}

if (prometheus::loggedIn() && !actions::delivered() && $page != 'required') {
    util::redirect('store.php?page=required');
}

if (prometheus::loggedIn() && is_numeric(actions::delivered('customjob', $_SESSION['uid'])) && $_GET['page'] != 'customjob') {
    util::redirect('store.php?page=customjob&pid=' . actions::delivered('customjob', $_SESSION['uid']));
}

?>

<?php include('inc/header.php'); ?>
<div class="content">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <?php $message->display(); ?>
            </div>
        </div>
        <?php if (prometheus::loggedIn()) { ?>
            <div class="row">
                <div class="col-12">
                    <div class="ticket-header" style="margin-bottom: 0px;">
                        <img src="<?= getUserSetting('steam_avatar', ''); ?>" width="50px" height="50px"></img>
                        <?= htmlspecialchars(getUserSetting("name", '')); ?>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <?php if (isset($_GET['cm'])) { ?>
                        <div class="row">
                            <div class="col-md-12">
                                <h2 class="mt-3"><?= lang('payment_success', 'Payment successful!'); ?></h2>

                                <p class="bs-callout bs-callout-info alert" role="alert">
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                            aria-hidden="true">&times;</span></button>
                                    <?= lang('profile_updated', 'Your profile has been updated.'); ?><br>
                                </p>
                            </div>
                        </div>
                    <?php } ?>
                    <?php if (isset($_GET['fail'])) { ?>
                        <div class="row">
                            <div class="col-12">
                                <h2><?= lang('payment_failed', 'Payment failed!'); ?></h2>

                                <p class="bs-callout bs-callout-info alert" role="alert">
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                            aria-hidden="true">&times;</span></button>
                                    <?= lang('check_credits', 'Please check that you have enough credits to buy this package!'); ?>
                                    <br>
                                </p>
                            </div>
                        </div>
                    <?php } ?>

                    <div class="row">
                        <div class="col-12">
                            <div class="stat-box-header"><?= lang('acc_info'); ?></div>
                            <div class="stat-box">
                                <div class="stat-box-inner">
                                    <b>User ID:</b> <?= $UID; ?><br>
                                    <b>SteamID:</b> <?= convertCommunityIdToSteamId($UID); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (getSetting('profile_nostats', 'value2') == 0) { ?>
                        <div class="row" style="margin-top: 15px;">
                            <div class="col-12 col-md-4">
                                <div class="dashboard-widget-small-box">
                                    <div class="float-left">
                                        <h2 class="element"><?php echo dashboard::getTotalCurrency(getSetting('dashboard_main_cc', 'value2'), 'total', null, false, $_SESSION['uid']); ?></h2>

                                        <p class="caption"><?= lang('spent_total'); ?></p>
                                    </div>
                                    <i class="float-right fas fa-money-bill fa-4x fa-fw"></i>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="dashboard-widget-small-box">
                                    <div class="float-left">
                                        <h2 class="element"><?php echo dashboard::getTotalCurrency(getSetting('dashboard_main_cc', 'value2'), 'week', null, false, $_SESSION['uid']); ?></h2>

                                        <p class="caption"><?= lang('spent_week'); ?></p>
                                    </div>
                                    <i class="float-right fas fa-clock fa-4x fa-fw"></i>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="dashboard-widget-small-box">
                                    <div class="float-left">
                                        <h2 class="element"><?php echo dashboard::getTotalCurrency(getSetting('dashboard_main_cc', 'value2'), 'month', null, false, $_SESSION['uid']); ?></h2>

                                        <p class="caption"><?= lang('spent_month'); ?></p>
                                    </div>

                                    <i class="float-right fas fa-check-circle fa-4x fa-fw"></i>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="panel-body" style="margin-bottom: 0px !important;">
                                    <div class="panel-header">
                                        <div class="float-left"><?= lang('your_spendings'); ?></div>
                                    </div>
                                    <div class="panel-inner">
                                        <?php

                                        echo lang('spendings_currency', null, [
                                            $db->getOne("SELECT cc FROM currencies WHERE id = ?", getSetting('dashboard_main_cc', 'value2'))
                                        ]);

                                        ?>
                                        <br><br>
                                        <canvas id="adminChart" style="width: 100%; height: 400px;"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="adminChart_money" style="display: none;">
                            <?php
                            echo dashboard::getRevenue('money', $_SESSION['uid']);
                            ?>
                        </div>

                        <div id="adminChart_credits" style="display: none;">
                            <?php
                            echo dashboard::getRevenue('credits', $_SESSION['uid']);
                            ?>
                        </div>

                        <script>
                            function getDates(startDate /*moment.js date object*/) {
                                nowNormalized = moment().startOf("month"),
                                    startDateNormalized = startDate.clone().startOf("month").add(1, "M"),
                                    months = [];


                                while (startDateNormalized.isBefore(nowNormalized)) {
                                    months.push(startDateNormalized.format("MMMM"));
                                    startDateNormalized.add(1, "M");
                                }

                                return months;
                            }

                            var thisMonth = moment();
                            thisMonth = thisMonth.format('MMMM');

                            var earlierMonths = moment();
                            earlierMonths.subtract(5, 'months');
                            earlierMonths = String(getDates(earlierMonths));
                            earlierMonths = earlierMonths.split(',');

                            var money = $('#adminChart_money').text();
                            var moneyJson = $.parseJSON(money);

                            var credits = $('#adminChart_credits').text();
                            var creditsJson = $.parseJSON(credits);

                            var adminChartData = {
                                labels: [earlierMonths[0], earlierMonths[1], earlierMonths[2], earlierMonths[3], thisMonth],
                                datasets: [
                                    {
                                        label: "Money spent",
                                        fillColor: "rgba(60,60,60,0.2)",
                                        strokeColor: "rgba(193,0,0,1)",
                                        pointBackgroundColor: "rgba(193,0,0,1)",
                                        pointBorderColor: "#c10000",
                                        pointHoverBackgroundColor: "#c10000",
                                        pointHoverBorderColor: "rgba(193,0,0,1)",
                                        data: [moneyJson[4], moneyJson[3], moneyJson[2], moneyJson[1], moneyJson[0]],
                                        backgroundColor: [
                                            'rgba(193,0,0,0.2)'
                                        ],
                                        borderColor: [
                                            'rgba(193,0,0,1)'
                                        ],
                                    },
                                    {
                                        label: "Credits spent",
                                        fillColor: "rgba(60,60,60,0.2)",
                                        strokeColor: "rgba(156,156,156,1)",
                                        pointBackgroundColor: "rgba(156,156,156,1)",
                                        pointBorderColor: "#9c9c9c",
                                        pointHoverBackgroundColor: "#9c9c9c",
                                        pointHoverBorderColor: "rgba(156,156,156,1)",
                                        data: [creditsJson[4], creditsJson[3], creditsJson[2], creditsJson[1], creditsJson[0]],
                                        backgroundColor: [
                                            'rgba(60,60,60,0.2)'
                                        ],
                                        borderColor: [
                                            'rgba(60,60,60,1)'
                                        ],
                                    }
                                ]
                            }

                            window.onload = function () {
                                var ctx = document.getElementById("adminChart").getContext("2d");
                                window.myLine = new Chart(ctx, {
                                    type: "line",
                                    data: adminChartData,
                                    responsive: true
                                });

                            }
                        </script>
                    <?php } ?>

                    <div class="row">
                        <div class="col-12 col-md-6">
                            <?php if (gateways::enabled('credits')) { ?>
                                <div class="stat-box-header" style="border-bottom: 0px;"><?= lang('credits'); ?></div>
                                <table class="table table-striped">
                                    <tbody>
                                    <tr>
                                        <td><?= credits::get($_SESSION['uid']); ?></td>
                                    </tr>
                                    </tbody>
                                </table>
                            <?php } ?>
                            <div class="stat-box-header"><?= lang('pkg_history'); ?></div>
                            <table class="table table-striped">
                                <thead style="border-top: 0px;">
                                <th><?= lang('package'); ?></th>
                                <th><?= lang('timestamp'); ?></th>
                                </thead>

                                <tbody>
                                <?= getPackageHistory(); ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-12 col-md-6">
                            <?php if (gateways::enabled('credits') && getSetting('credits_cantransfer', 'value2') == 1) { ?>
                                <div class="stat-box-header"
                                     style="border-bottom: 0px;"><?= lang('transfer_credits'); ?></div>
                                <table class="table table-striped">
                                    <tbody>
                                    <tr>
                                        <td>
                                            <form method="GET">
                                                <?php if (!isset($_GET['transfer'])) { ?>
                                                    <div class="input-group">
                                                        <input type="text" name="transfer" placeholder="Steam64 ID"
                                                               class="form-control">
												      <span class="input-group-btn">
												        <input type="submit" class="btn btn-prom" value="Submit">
												      </span>
                                                    </div>
                                                <?php } ?>
                                            </form>

                                            <form method="POST">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
                                                <?php if (isset($_GET['transfer']) && $_GET['transfer'] != $UID && userExists($_GET['transfer'])) { ?>
                                                    <b><?= lang('credits_transferringto', 'Transferring to:'); ?></b> <?php echo getUserSetting('name', $_GET['transfer']); ?>
                                                    <div class="input-group" style="margin-top: 10px;">
                                                        <input type="text" name="transfer_amount"
                                                               placeholder="<?= lang('amount', 'Amount'); ?>"
                                                               class="form-control">
												      <span class="input-group-btn">
												        <input type="submit" name="transfer_submit" class="btn btn-prom"
                                                               value="Send">
												      </span>
                                                    </div>
                                                <?php } ?>
                                            </form>

                                            <?php if (isset($_GET['transfer']) && !userExists($_GET['transfer'])) { ?>
                                                <?= lang('credits_doesntexist', 'This user does not exist on this system.'); ?>
                                                <a href="profile.php"><?= lang('credits_steamid', 'Try another SteamID'); ?></a>
                                            <?php } ?>

                                            <?php if (isset($_GET['transfer']) && $_GET['transfer'] == $UID) { ?>
                                                <?= lang('credits_yourself', "You can't transfer credits to yourself."); ?>
                                                <a href="profile.php"><?= lang('credits_steamid', 'Try another SteamID'); ?></a>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            <?php } ?>

                            <?php if (getSetting('profile_nononperm', 'value2') == 0) { ?>
                                <div class="stat-box-header"
                                     style="border-bottom: 0px;"><?= lang('non_perm_pkg'); ?></div>
                                <table class="table table-striped">
                                    <tbody>
                                    <?= getNonPermanentPackages(); ?>
                                    </tbody>
                                </table>
                            <?php } ?>

                            <?php if (getSetting('profile_noperm', 'value2') == 0) { ?>
                                <div class="stat-box-header" style="border-bottom: 0px;"><?= lang('perm_pkg'); ?></div>
                                <table class="table table-striped">
                                    <tbody>
                                    <?= getPermanentPackages(); ?>
                                    </tbody>
                                </table>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        } else {
            echo lang('not_authorised', 'You are not authorized to view this area. Sign in first!');
        }
        ?>

    </div>
</div>
<?php include('inc/footer.php'); ?>
