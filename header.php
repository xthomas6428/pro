<?php

    ob_start();

    if (!isset($devmode)) {
        $devmode = false;
    }

    if ($devmode) {
        $time = microtime(true);
    }

    include 'lib/mix.php';

?><!DOCTYPE html>

<html lang="en" dir="<?= $dir; ?>">
	<head>
		<!-- Include required CSS and scripts -->
		<meta charset="utf-8">
		<title><?= getSetting('site_title', 'value'); ?> - <?= $page_title; ?></title>
		<meta http-equiv="content-type" value="text/html; charset=UTF-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1">

		<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700" rel="stylesheet" type="text/css" />
		<link rel="icon" href="favicon.ico" type="image/x-icon"/>

		<link rel="stylesheet" type="text/css" href="compiled/css/site.css">

		<?php

            if (isset($_GET['a']) && $_GET['a'] == 'theme') {
                if (isset($_GET['default'])) {
                    setSetting('', 'theme', 'value');
                }
            }

            $theme = theme::current();
            if (isset($_COOKIE['prometheus_theme']) && getSetting('disable_theme_selector', 'value2') == 0) {
                $theme = $_COOKIE['prometheus_theme'];
            }

            if ($theme != '') {
                echo '<link rel="stylesheet" type="text/css" href="themes/'.$theme.'/style.css">';
            }

        ?>

        <script src="compiled/js/essential.js"></script>

		<script type="text/javascript">
			<?php if ($page != 'admin' && getSetting('christmas_things', 'value2') == 1) { ?>
				setTimeout(function(){
					snowStorm.start();
				}, 500);
			<?php } ?>
		</script>

      <?php if (gateways::enabled('stripe')) { ?>
        <script src="https://js.stripe.com/v3/"></script>
      <?php } ?>
	</head>

	<body>
	<?php if ($page != 'admin') { ?>
		<?php if (getSetting('paypal_sandbox', 'value2') == 1 && getSetting('warning_sandbox', 'value2') == 0 && prometheus::isAdmin() && gateways::enabled('paypal')) { ?>
			<div class="notSetup d-flex align-items-center p-0">
				<div class="container">
					<?= lang('header_sandbox'); ?>
				</div>
			</div>
		<?php } ?>

		<?php if (actions::missing() && !getSetting('warning_missingactions', 'value2')  && prometheus::isAdmin()) { ?>
			<div class="notSetup">
				<div class="container">
					<?= lang('missing_action'); ?>
				</div>
			</div>
		<?php } ?>

        <?php

            try {
                $sale_end = new datetime(getSetting('sale_enddate', 'value'));
                $valid_date = true;
            } catch (Exception $e) {
                $sale_end = null;
                $valid_date = false;
            }

        ?>

		<?php if ($valid_date && $sale_end > new datetime()) { ?>
		<div class="sale-box d-flex align-items-center p-0">
			<div class="container">
				<div class="row">
					<div class="col text-left">
						<?= htmlentities(getSetting('sale_message', 'value')); ?>
					</div>
					<div class="col text-right">
						<div id="countdown"></div>
					</div>
				</div>
			</div>
		</div>
		<script>
			var target_date = new Date("<?= getSetting('sale_enddate', 'value'); ?>").getTime();
			var days, hours, minutes, seconds;

			var countdown = document.getElementById("countdown");

			setInterval(function () {
				var current_date = new Date().getTime();
				var seconds_left = (target_date - current_date) / 1000;

				days = parseInt(seconds_left / 86400);
				seconds_left = seconds_left % 86400;

				hours = parseInt(seconds_left / 3600);
				seconds_left = seconds_left % 3600;

				minutes = parseInt(seconds_left / 60);
				seconds = parseInt(seconds_left % 60);

				if (hours < 10)
				{
					hours = '0' + hours;
				}

				if (days < 10)
				{
					days = '0' + days;
				}

				if (minutes < 10)
				{
					minutes = '0' + minutes;
				}

				if (seconds < 10)
				{
					seconds = '0' + seconds;
				}

				if(current_date < target_date)
				{
					countdown.innerHTML = "<i class='fas fa-clock'></i> " + days + ":" + hours + ":"
					+ minutes + ":" + seconds + "";
				}

			}, 1000);
		</script>
		<?php } ?>
	<?php } ?>
	<div class="wrap">
		<?php if ($page != 'admin' && getSetting('site_banner', 'value') != '') { ?>
		<div class="banner">
			<div class="container">

                <div class="row">

                    <div class="col">
                        <?php if (getSetting('site_banner', 'value') != '') { ?>
                            <img src="<?= getSetting('site_banner', 'value'); ?>"/>
                        <?php } ?>
                    </div>

                    <div class="col d-flex justify-content-end align-items-start">
                        <?php if (gateways::enabled('credits')) { ?>
                            <?php if (prometheus::loggedIn()) { ?>
                                <div class="credits d-block">
                                    <i class="fas fa-coins fa-fw"></i>
                                    <?php echo credits::get($_SESSION['uid']); ?> CR
                                </div>
                            <?php } ?>
                        <?php } ?>
                    </div>
                </div>
			</div>
		</div>
		<?php } ?>

		<nav class="navbar navbar-inverse navbar-expand-lg <?= $page == 'admin' ? 'fixed-top' : ''; ?>" role="navigation">
			<?= $page == 'admin' ? '' : '<div class="container">'; ?>
            <div class="navbar-header d-flex justify-content-between justify-content-md-start align-items-center">
                <?php if ($page == 'admin') { ?>
                    <button class="navbar-toggler toggle-menu visible-xs-inline-block">
                        <i class="fas fa-bars fa-fw text-white"></i>
                    </button>
                <?php } ?>


                <div class="ml-auto ml-md-0">
                    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                        <i class="fas fa-bars fa-fw text-white"></i>
                    </button>

                    <?php if (getSetting('site_logo', 'value') != '') { ?>
                        <a class="navbar-brand" href="."><img src="<?= getSetting('site_logo', 'value'); ?>" width="64px" height="64px"/></a>
                    <?php } ?>
                </div>
            </div>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="nav flex-column flex-md-row">
                    <li class="nav-item <?php echo $page == 'home' ? 'active' : ''; ?>">
                        <a class="nav-link" href=".">
                            <i class="fas fa-home fa-fw"></i> <?= lang('home'); ?>
                        </a>
                    </li>
                    <?php if (getSetting('installed', 'value2') == 1) { ?>
                    <li class="nav-item dropdown
						<?php echo $page == 'store' ? 'active' : ''; ?>
						<?php echo $page == 'credits' ? 'active' : ''; ?>
						<?php echo $page == 'raffle' ? 'active' : ''; ?>
						">
                        <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                            <?= lang('store'); ?>
                        </a>
                        <ul class="dropdown-menu" role="menu">
                            <li class="<?php echo $page == 'store' ? 'active' : ''; ?>">
                                <a class="dropdown-item" href="store.php?page=server">
                                    <i class="fas fa-shopping-cart pr-1 fa-fw"></i>
                                    <?= lang('store'); ?>
                                </a>
                            </li>
                            <?php if (gateways::enabled('credits')) { ?>
                                <li class="<?php echo $page == 'credits' ? 'active' : ''; ?>">
                                    <a class="dropdown-item" href="store.php?page=credits">
                                        <i class="fas fa-money-bill pr-1 fa-fw"></i>
                                        <?= lang('buy_credits'); ?>
                                    </a>
                                </li>
                            <?php } ?>

                            <?php if (getSetting('enable_raffle', 'value2') == 1) { ?>
                                <li class="<?php echo $page == 'raffle' ? 'active' : ''; ?>">
                                    <a class="dropdown-item" href="store.php?page=raffle">
                                        <i class="fas fa-ticket-alt pr-1 fa-fw"></i>
                                        <?= lang('raffles'); ?>
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                    </li>

                    <?php if (prometheus::loggedIn() && getSetting('christmas_advent', 'value2') == 1) { ?>
                        <li class="nav-item <?php echo $page == 'advent' ? 'active' : ''; ?>">
                            <a class="nav-link" href="store.php?page=advent">
                                <i class="fas fa-tree fa-fw"></i>
                                <?= lang('advent'); ?>
                            </a>
                        </li>
                    <?php } ?>

                    <?php if (prometheus::loggedIn()) { ?>
                        <li class="nav-item <?php echo $page == 'profile' ? 'active' : ''; ?>">
                            <a class="nav-link" href="profile.php">
                                <i class="fas fa-user fa-fw"></i>
                                <?= lang('profile'); ?>
                            </a>
                        </li>
                    <?php } ?>
                </ul>

                <ul class="nav ml-auto flex-column flex-md-row">
                    <?php if (prometheus::loggedIn()) { ?>
                        <?php if (getSetting('support_tickets', 'value2') == 1) { ?>
                            <li class="nav-item position-relative <?php echo $page == 'support' ? 'active' : ''; ?>"><?php if (tickets::read(0) != 0) { ?><div class="notify-icon"><?= tickets::read(0); ?></div><?php } ?><a class="nav-link" href="support.php"><i class="fas fa-question-circle fa-fw"></i> <?= lang('support'); ?></a></li>
                        <?php } ?>
                        <?php if (prometheus::isAdmin()) { ?>
                            <li class="nav-item position-relative <?php echo $page == 'admin' ? 'active' : ''; ?>"><?php if (tickets::read(1) != 0) { ?><div class="notify-icon"><?= tickets::read(1); ?></div><?php } ?><a class="nav-link" href="admin.php"><i class="fas fa-cog fa-fw"></i> <?= lang('admin'); ?></a></li>
                        <?php } ?>
                        <li class="nav-item"><a class="nav-link" href="logout.php?csrf_token=<?= csrf_token(); ?>"><i class="fas fa-sign-out-alt fa-fw"></i> <?= lang('sign_out'); ?></a></li>
                    <?php } ?>
                    <?php if (!prometheus::loggedIn()) { ?>
                        <?php echo '<li class="nav-item"><a class="nav-link" href="'.steamLogin::genUrl().'"><i class="fas fa-sign-in-alt fa-fw"></i> ' . lang("sign_in") . '</a></li>'; ?>
                    <?php } ?>
                </ul>
                <?php } ?>
            </div>
			<?= $page === 'admin' ? '' : '</div>' ?>
		</nav>
