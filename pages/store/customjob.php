<?php

global $db, $message;

$customjob = (bool) actions::get($_GET['pid'], 'customjob', '');

if (!$customjob) {
    die('This package does not have a custom job action!');
}

$credits_enabled = gateways::enabled('credits');

$verify = new verification('none', $_SESSION['uid'], $_GET['pid']);
$price = $verify->getPrice('package', null, true);

$cur = $db->getOne("SELECT c.cc FROM currencies c JOIN settings s ON (s.value2 = c.id) WHERE s.name = 'dashboard_main_cc'");

$credits = 0;
if ($credits_enabled) {
    $credits = $verify->getPrice('package', 'credits', true);
}

if (is_numeric(actions::delivered('customjob', $_SESSION['uid']))) {
    $credits = 0;
    $price = 0;
}

$pre = prepurchase::hasPre($_SESSION['uid'], 'customjob');
$alreadyHas = false;

if ($pre !== false) {
    $alreadyHas = true;
}

if (isset($_POST['checkout'])) {
    $error = false;

    $name = $_POST['name'];
    if (empty($_POST['name'])) {
        $error = true;
        $message->add("danger", "You need to specify a job name!");
    } else {
        $name_alreadyUsed = $db->getAll(
            "SELECT * FROM actions WHERE actions LIKE ? AND actions LIKE ? AND active = 1",
            ['%"customjob":%', '%"'.$_POST['name'].'"%']
        );

        if ($name_alreadyUsed) {
            $error = true;
            $message->add("danger", "This custom job name is already in use!");
        }
    }

    $cmd = $_POST['cmd'];
    if (empty($cmd)) {
        $error = true;
        $message->add("danger", "You need to specify a job chat command!");
    } else {
        $cmd_alreadyUsed = $db->getAll(
            "SELECT * FROM actions WHERE actions LIKE ? AND actions LIKE ? AND active = 1",
            ['%customjob%', '%'.$_POST['cmd'].'%']
        );

        if ($cmd_alreadyUsed) {
            $error = true;
            $message->add("danger", "This custom job command is already in use!");
        }
    }

    preg_match('/[a-zA-Z\d+]+/', $_POST['cmd'], $match);
    if (strlen($match[0]) !== strlen($_POST['cmd'])) {
        $error = true;
        $message->add("danger", "You can only put letters and numbers in your job command!");
    }

    $description = $_POST['description'];
    if (empty($description)) {
        $error = true;
        $message->add("danger", "You need to specify a job description!");
    }

    $colour = $_POST['colour'];
    $colourExp = explode(',', $colour);

    if (empty($colour)) {
        $error = true;
        $message->add("danger", "You need to specify a job colour!");
    } elseif (count($colourExp) !== 3
        || !is_numeric($colourExp[0])
        || !is_numeric($colourExp[1])
        || !is_numeric($colourExp[2])) {
        $error = true;
        $message->add("danger", "You need to specify a valid job colour!");
    }

    $weapons_check = actions::get($_GET['pid'], 'customjob', 'weapons');
    $weapons_price = 0.0;
    $weapons_credits = 0.0;
    $weapons_list = '';

    if ($weapons_check) {
        if (isset($_POST['weapon'])) {
            $weapons = $_POST['weapon'];

            $weapons_max = actions::get($_GET['pid'], 'customjob', 'weapons_max');
            if ($weapons_max != 0 && !empty($weapons_max) && count($weapons) > $weapons_max) {
                $error = true;

                $message->add("danger", "You are not allowed to have this many weapons. The max is ".$weapons_max);
            }

            if (!$error) {
                $Wprice = actions::get($_GET['pid'], 'customjob', 'weapons_price');
                $Wcredits = actions::get($_GET['pid'], 'customjob', 'weapons_credits');

                $Wprice = json_decode($Wprice, true);
                $Wcredits = json_decode($Wcredits, true);

                foreach ($weapons as $key => $value) {
                    $weapons_price += (float) $Wprice[$key];
                    $weapons_credits += (float) $Wcredits[$key];

                    $weapons_list .= $key.',';
                }

                $weapons_list = rtrim($weapons_list, ',');
            }
        }
    } else {
        $weapons_list = actions::get($_GET['pid'], 'customjob', 'weapons_static');
    }

    $models_check = actions::get($_GET['pid'], 'customjob', 'models');
    $models_price = 0.0;
    $models_credits = 0.0;
    $models_list = '';

    if ($models_check) {
        if (isset($_POST['model'])) {
            $models = $_POST['model'];

            $models_max = actions::get($_GET['pid'], 'customjob', 'models_max');
            if ($models_max != 0 && !empty($models_max) && count($models) > $models_max) {
                $error = true;

                $message->add("danger", "You are not allowed to have this many models. The max is ".$models_max);
            }

            if (!$error) {
                $Mprice = actions::get($_GET['pid'], 'customjob', 'models_price');
                $Mcredits = actions::get($_GET['pid'], 'customjob', 'models_credits');

                $Mprice = json_decode($Mprice, true);
                $Mcredits = json_decode($Mcredits, true);

                foreach ($models as $key => $value) {
                    $models_price += (float) $Mprice[$key];
                    $models_credits += (float) $Mcredits[$key];

                    $models_list .= $key.',';
                }

                $models_list = rtrim($models_list, ',');
            }
        }
    } else {
        $models_list = actions::get($_GET['pid'], 'customjob', 'models_static');
    }

    $friends_check = actions::get($_GET['pid'], 'customjob', 'friends');
    $friends_list = '';
    $friends_price = 0.0;
    $friends_credits = 0.0;

    if ($friends_check) {
        if (isset($_POST['friends'])) {
            $friends = $_POST['friends'];
            $friends_amt = actions::get($_GET['pid'], 'customjob', 'friends_amount');

            if (count($friends) !== 0) {
                if (count($friends) > $friends_amt) {
                    $error = true;
                    $message->add("danger",
                        "Too many friends specified. You are not allowed this many. Stop trying to break things!");
                } else {
                    $Fprice = actions::get($_GET['pid'], 'customjob', 'friends_scaleprice');
                    $Fcredits = actions::get($_GET['pid'], 'customjob', 'friends_scalecredits');

                    foreach ($friends as $key => $value) {
                        $friends_price += (float) $Fprice;
                        $friends_credits += (float) $Fcredits;

                        $friends_list .= $value.',';
                    }

                    $friends_list = rtrim($friends_list, ',');
                }
            }
        }
    } else {
        $friends_amt = actions::get($_GET['pid'], 'customjob', 'friends_static');

        if (($friends_amt != 0) && isset($_POST['friends'])) {
            $friends = $_POST['friends'];

            if (count($friends) > $friends_amt) {
                $error = true;
                $message->add("danger",
                    "Too many friends specified. You are not allowed this many. Stop trying to break things!");
            } else {
                foreach ($friends as $key => $value) {
                    $friends_list .= $value.',';
                }

                $friends_list = rtrim($friends_list, ',');
            }
        }
    }

    $salary_check = actions::get($_GET['pid'], 'customjob', 'salary');
    if ($salary_check) {
        $salary_base = (float) actions::get($_GET['pid'], 'customjob', 'salary_amount');
        $salary = $salary_base;

        $salary_price = 0.0;
        $salary_credits = 0.0;

        $SPrice = actions::get($_GET['pid'], 'customjob', 'salary_scaleprice');
        $SCredits = actions::get($_GET['pid'], 'customjob', 'salary_scalecredits');

        $salary_max = actions::get($_GET['pid'], 'customjob', 'salary_max');
        $salary_value = (float) $_POST['salary'];

        if ($salary_value <= $salary_max) {
            if ($salary_value != 0) {
                for ($i = 0; $i < $salary_value; $i++) {
                    $salary += $salary_base;

                    $salary_price += (float) $SPrice;
                    $salary_credits += (float) $SCredits;
                }
            }
        } else {
            $error = true;
            $message->add("danger", "Salary scale exceeded limit. Stop trying to break things!");
        }
    } else {
        $salary = actions::get($_GET['pid'], 'customjob', 'salary_static');

        $salary_price = 0.0;
        $salary_credits = 0.0;
    }

    $license_check = actions::get($_GET['pid'], 'customjob', 'license');
    if ($license_check) {
        $license_price = 0.0;
        $license_credits = 0.0;

        if (isset($_POST['license'])) {
            $license = 1;

            $license_price = (float) actions::get($_GET['pid'], 'customjob', 'license_scaleprice');
            $license_credits = (float) actions::get($_GET['pid'], 'customjob', 'license_scalecredits');
        } else {
            $license = 0;
        }
    } else {
        $license = (int) actions::get($_GET['pid'], 'customjob', 'license_static');
        $license_price = 0.0;
        $license_credits = 0.0;
    }

    $fullTotalPrice = $price + $weapons_price + $models_price + $friends_price + $salary_price + $license_price;
    $fullTotalCredits = $credits + $weapons_credits + $models_credits + $friends_credits + $salary_credits + $license_credits;

    $info = '
        Base price: '.$price.'<br>
        Base credits: '.$credits.'<br>
        <br>
        Weapons: '.$weapons_list.'<br>
        Weapons price: '.$weapons_price.'<br>
        Weapons credits: '.$weapons_credits.'<br>
        <br>
        Models: '.$models_list.'<br>
        Models price: '.$models_price.'<br>
        Models credits: '.$models_credits.'<br>
        <br>
        Friends: '.$friends_list.'<br>
        Friends price: '.$friends_price.'<br>
        Friends credits: '.$friends_credits.'<br>
        <br>
        Salary: '.$salary.'<br>
        Salary price: '.$salary_price.'<br>
        Salary credits: '.$salary_credits.'<br>
        <br>
        License: '.$license.'<br>
        License price: '.$license_price.'<br>
        License credits: '.$license_credits.'<br>
        <br>
        Full total price: '.$fullTotalPrice.'<br>
        Full total credits: '.$fullTotalCredits.'<br>
    ';

    $name = preg_replace("/[^a-zA-Z0-9\s]+/", "", $name);
    $name = rtrim($name, ' ');

    $cmd = str_replace(['"', '\\', '{', '}', '..', '(', ')', '[', ']'], '', $cmd);
    $description = str_replace(['"', '\\', '{', '}', '..', '(', ')', '[', ']'], '', $description);

    if (!$error) {
        $array = [
            "name" => $name,
            "cmd" => $cmd,
            "desc" => $description,
            "colour" => $colour,

            "weapons" => $weapons_list,
            "weapons_price" => $weapons_price,
            "weapons_credits" => $weapons_credits,

            "models" => $models_list,
            "models_price" => $models_price,
            "models_credits" => $models_credits,

            "friends" => $friends_list,
            "friends_price" => $friends_price,
            "friends_credits" => $friends_credits,

            "salary" => $salary,
            "salary_price" => $salary_price,
            "salary_credits" => $salary_credits,

            "license" => $license,
            "license_price" => $license_price,
            "license_credits" => $license_credits,

            "fullTotalPrice" => $fullTotalPrice,
            "fullTotalCredits" => $fullTotalCredits,
        ];

        $json = json_encode($array);

        $db->execute("INSERT INTO prepurchase SET type = ?, uid = ?, json = ?", [
            'customjob', $_SESSION['uid'], $json
        ]);

        $message->add("success", "Redirecting");
    }
}

if (isset($_POST['skip_raffle'])) {
    $db->execute("UPDATE actions SET active = 0 WHERE package = ? AND server = ''", $_GET['pid']);
}
?>

<script type="text/javascript">
    $(function () {
        $(".form").on('submit', (function (e) {
            e.preventDefault();
            $.ajax({
                type: "POST",
                url: document.location.href,
                data: new FormData(this),
                contentType: false,
                cache: false,
                processData: false,
                success: function (data) {
                    const msg = $(data).find('.bs-callout');
                    const msgText = $(data).find('.bs-callout').text().replace('×\n', '');

                    $("#message-location").html(msg);
                    $("html, body").animate({scrollTop: 0}, "slow");

                    const pid = getUrlParameter("pid");

                    if (msgText.length === 12) {
                        setTimeout(function () {
                            window.location = "store.php?page=purchase&type=pkg&pid=" + pid;
                        }, 3000);
                    }
                }
            });
        }));
    });
</script>

<div id="uid" style="display: none;"><?= $_SESSION['uid']; ?></div>

<div class="row">
    <div class="col-12">
        <div class="header">
            <?= lang('custom_job', 'Custom job'); ?>
        </div>
        <div id="message-location">
            <?php $message->display(); ?>
        </div>
    </div>
</div>

<?php if ($alreadyHas) { ?>
    <div class="darker-box">
        <?php

        echo lang('job_already_created',
            'You have already created a custom job. If you want to proceed straight to checkout click $1. Otherwise you can create a new one',
            [
                '<a href="store.php?page=purchase&type=pkg&pid='.$_GET['pid'].'">'.lang('here', 'here').'</a>'
            ]);

        ?>
    </div>
<?php } ?>

<form method="POST" class="form">
    <div class="row mt-3">
        <div class="col-12">
            <h5><?= lang('general_info', 'General information'); ?></h5>
        </div>
    </div>
    <div class="darker-box">
        <div class="row">
            <div class="col-12">
                <input type="text" name="name" class="form-control" placeholder="<?= lang('job_name', 'Job name'); ?>">
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <input type="text" name="cmd" class="form-control"
                       placeholder="<?= lang('chat_cmd', 'Chat command to become this job') ?>"
                       style="margin-top: 5px">
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <textarea class="form-control" name="description" style="margin-top: 5px"
                          placeholder="<?= lang('job_desc', 'Job description') ?>"></textarea>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <input type="text" class="form-control color_box" style="margin-top: 5px"
                       placeholder="<?= lang('job_colour', 'Job colour') ?>" name="colour"
                       style="border-left: 3px solid #000">
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12">
            <h5><?= lang('preferances', 'Preferences') ?></h5>
        </div>
    </div>

    <?php

    $weapons_check = actions::get($_GET['pid'], 'customjob', 'weapons');
    if ($weapons_check) {
        // Display weapons to choose from

        $weapons_max = actions::get($_GET['pid'], 'customjob', 'weapons_max');

        echo '
			<div class="row mt-3">
				<div class="col-12">
					<h6>'.lang('weapons', 'Weapons').' - Max: '.$weapons_max.'</h6>
				</div>
			</div>
		';

        echo '<div class="row">';
        echo '<div class="col-12">';
        echo '<div class="darker-box">';

        echo customjob::getWeapons($_GET['pid'], true);
    } else {
        // Display default string of weapons

        echo '
			<div class="row mt-3">
				<div class="col-12">
					<h6>'.lang('weapons', 'Weapons').'</h6>
				</div>
			</div>
		';

        echo '<div class="row">';
        echo '<div class="col-12">';
        echo '<div class="darker-box">';

        echo customjob::getWeapons($_GET['pid'], false);
    }
    echo '</div>';
    echo '</div>';
    echo '</div>';

    $models_check = actions::get($_GET['pid'], 'customjob', 'models');
    if ($models_check) {
        // Display models to choose from and price

        $models_max = actions::get($_GET['pid'], 'customjob', 'models_max');

        echo '
			<div class="row mt-3">
				<div class="col-12">
					<h6>'.lang('models', 'Models').' - Max: '.$models_max.'</h6>
				</div>
			</div>
		';

        echo '<div class="row">';
        echo '<div class="col-12">';
        echo '<div class="darker-box">';

        echo customjob::getModels($_GET['pid'], true);

    } else {
        // Display default model

        echo '
			<div class="row mt-3">
				<div class="col-12">
					<h6>'.lang('model', 'Model').'</h6>
				</div>
			</div>
		';

        echo '<div class="row">';
        echo '<div class="col-12">';
        echo '<div class="darker-box">';

        echo customjob::getModels($_GET['pid'], false);

    }
    echo '</div>';
    echo '</div>';
    echo '</div>';

    $friends_check = actions::get($_GET['pid'], 'customjob', 'friends');
    $friends_amt = actions::get($_GET['pid'], 'customjob', 'friends_amount');

    if ($friends_amt === null) {
        $friends_amt = 0;
    }

    if ($friends_check) {
        // Display friends slider and price scaling

        $friends_price = actions::get($_GET['pid'], 'customjob', 'friends_scaleprice');
        $friends_credits = actions::get($_GET['pid'], 'customjob', 'friends_scalecredits');

        if (!$friends_credits) {
            $friends_credits = 0;
        }

        echo '
			<div class="row mt-3">
				<div class="col-12">
					'.lang('friends_max', '<h6>Friends - $1 max</h6>', [$friends_amt]).'
				</div>
			</div>
		';

        echo '<div class="row">';
        echo '<div class="col-12">';
        echo '<div class="darker-box">';

        if (gateways::enabled('credits')) {
            echo lang('friends_add', 'For every friend you add it adds $1 or $2 credits to the final price', [
                $friends_price.' '.$cur,
                $friends_credits
            ]);
        } else {
            echo lang('friends_add_money', 'For every friend you add it adds $1 to the final price', [
                $friends_price.' '.$cur
            ]);
        }

        echo '<br><br>';

        echo '<input id="friends_slider" price="'.$friends_price.'" credits="'.$friends_credits.'" data-slider-id="friends_slider" type="text" data-slider-id="RC" data-slider-handle="square" data-slider-min="0" data-slider-max="'.$friends_amt.'" data-slider-step="1" data-slider-value="0">';
        echo '<div class="friends"></div>';


    } else {
        // Display default amount of friends, if any, show fields of SteamIDs. Otherwise none

        echo '
			<div class="row mt-3">
				<div class="col-12">
					'.lang('friends_max', '<h6>Friends - $1 max</h6>', [$friends_amt]).'
				</div>
			</div>
		';

        echo '<div class="row">';
        echo '<div class="col-12">';
        echo '<div class="darker-box">';

        $friends_amt = actions::get($_GET['pid'], 'customjob', 'friends_static');
        if (!empty($friends_amt)) {
            // Display fields

            $fields = '';

            for ($i = 0; $i < $friends_amt; $i++) {
                $fields .= '<input type="text" name="friends['.$i.']" placeholder="SteamID of friend (Leave blank for none)" class="form-control" style="margin-top: 5px">';
            }

            echo $fields;
        } else {
            // Not allowed any
            echo '<h6>'.lang('friends', 'Friends').'</h6>';
            echo lang('friends_not', 'You are not allowed to add any friends to this package');
        }

    }
    echo '</div>';
    echo '</div>';
    echo '</div>';

    $salary_check = actions::get($_GET['pid'], 'customjob', 'salary');
    if ($salary_check) {
        // Display salary slider and price scaling

        $salary_base = actions::get($_GET['pid'], 'customjob', 'salary_amount');
        $salary_max = actions::get($_GET['pid'], 'customjob', 'salary_max');
        $salary_price = actions::get($_GET['pid'], 'customjob', 'salary_scaleprice');
        $salary_credits = actions::get($_GET['pid'], 'customjob', 'salary_scalecredits');

        if (!$salary_credits) {
            $salary_credits = 0;
        }

        echo '
			<div class="row mt-3">
				<div class="col-12">
				    '.lang('salary_max', '<h6>Salary - $1 max times to scale</h6>', [$salary_max]).'
				</div>
			</div>
		';

        echo '<div class="row">';
        echo '<div class="col-12">';
        echo '<div class="darker-box">';

        if (gateways::enabled('credits')) {
            echo lang('salary_base',
                'The base salary is $1. For every number you up it by it adds $2 or $3 credits to the final price', [
                    $salary_base,
                    $salary_price.' '.$cur,
                    $salary_credits
                ]);
        } else {
            echo lang('salary_base_money',
                'The base salary is $1. For every number you up it by it adds $2 to the final price', [
                    $salary_base,
                    $salary_price.' '.$cur
                ]);
        }

        echo '<br><br>';

        echo '<input id="salary_slider" name="salary" base="'.$salary_base.'" price="'.$salary_price.'" credits="'.$salary_credits.'" data-slider-id="friends_slider" type="text" data-slider-id="RC" data-slider-handle="square" data-slider-min="0" data-slider-max="'.$salary_max.'" data-slider-step="1" data-slider-value="0"><br><br>';

        echo lang('salary_current', 'Your current salary: $1', [
            '<span id="finalSalary">'.$salary_base.'</span>'
        ]);

    } else {
        // Display default salary

        echo '
			<div class="row mt-3">
				<div class="col-12">
					<h6>'.lang('salary', 'Salary').'</h6>
				</div>
			</div>
		';

        $salary_static = actions::get($_GET['pid'], 'customjob', 'salary_static');
        echo '<div class="row">';
        echo '<div class="col-12">';
        echo '<div class="darker-box">';

        echo lang('salary_static', 'Your salary will be $1', [
            $salary_static
        ]);

    }
    echo '</div>';
    echo '</div>';
    echo '</div>';

    $license_check = actions::get($_GET['pid'], 'customjob', 'license');
    if ($license_check) {
        // License price

        $license_price = actions::get($_GET['pid'], 'customjob', 'license_scaleprice');
        $license_credits = actions::get($_GET['pid'], 'customjob', 'license_scalecredits');

        if (!$license_credits) {
            $license_credits = 0;
        }

        echo '
			<div class="row mt-3">
				<div class="col-12">
					<h6>'.lang('license', 'License').'</h6>
				</div>
			</div>
		';

        echo '<div class="row">';
        echo '<div class="col-12">';
        echo '<div class="darker-box">';

        if (gateways::enabled('credits')) {
            $license_lang = lang('license_include', 'Include license - Costs $1 or $2 credits',
                [isset($license_price) ? $license_price.' '.$cur : '', $license_credits]);
        } else {
            $license_lang = lang('license_include_money', 'Include license - Costs $1',
                    [$license_price ?? '']).' '.$cur;
        }

        echo '
            <div class="checkbox">
                <input type="checkbox" class="license" name="license" price="'.$license_price.'" credits="'.$license_credits.'">
                <label>'.$license_lang.'</label>
            </div>
        ';

    } else {
        $license_static = actions::get($_GET['pid'], 'customjob', 'license_static');
        echo '
            <div class="row mt-3">
                <div class="col-12">
                    <h6>'.lang('license', 'License').'</h6>
                </div>
            </div>
        ';
        echo '<div class="row">';
        echo '<div class="col-12">';
        echo '<div class="darker-box">';

        if ($license_static) {
            // Yes you get a license

            echo lang('license_included', 'You get a license included in the job');

        } else {
            // No you don't get a license

            echo lang('license_no', 'You do not get a license');

        }
    }
    echo '</div>';
    echo '</div>';
    echo '</div>';

    ?>

    <div class="row mt-3">
        <div class="col-12">
            <h5><?= lang('payment_confirmation', 'Payment confirmation') ?></h5>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="darker-box">
                <?php

                if (gateways::enabled('credits') && !getSetting('credits_only', 'value2')) {
                    echo lang('job_total', 'Your current total is $1 or $2 credits', [
                        '<span id="totalPrice" style="font-weight: bold;">'.$price.'</span> '.$cur,
                        '<span id="totalCredits" style="font-weight: bold;">'.$credits.'</span>'
                    ]);
                } elseif (!getSetting('credits_only', 'value2') && !gateways::enabled('credits')) {
                    echo lang('job_total_nocredits', 'Your current total is $1', [
                        '<span id="totalPrice" style="font-weight: bold;">'.$price.'</span> '.$cur
                    ]);
                } elseif (getSetting('credits_only', 'value2') && gateways::enabled('credits')) {
                    echo lang('job_total_onlycredits', 'Your current total is $1 credits', [
                        '<span id="totalCredits" style="font-weight: bold;">'.$credits.'</span>'
                    ]);
                }

                ?>
                <br><br>

                <input type="hidden" value="true" name="checkout">
                <button type="submit" class="btn buy-btn" name="checkout"><i
                            class="fa fa-shopping-cart"></i> <?= lang('job_proceed', 'Proceed to checkout') ?></button>
            </div>
        </div>
    </div>
</form>
<?php

if (is_numeric(actions::delivered('customjob', $_SESSION['uid']))) {
    echo '
			<form method="POST">
				<button type="submit" name="skip_raffle" class="btn btn-danger">'.lang('job_skip_prize',
            'Skip - Only click this if you do not want to claim this raffle prize').'</button>
			</form>
		';
}

?>

<script type="text/javascript">
    $(".weapon").on("ifChanged", function () {
        checkboxAddMoney(this);
    });

    $(".model").on("ifChanged", function () {
        checkboxAddMoney(this);
    });

    $(".license").on("ifChanged", function () {
        checkboxAddMoney(this);
    });

    function checkboxAddMoney(that) {
        let newprice;
        let newcredits;

        const done = !!($(that).is(':checked'));

        const price = parseInt($('#totalPrice').text());
        const credits = parseInt($('#totalCredits').text());

        if (done) {
            newprice = price + parseInt($(that).attr('price'));
            newcredits = credits + parseInt($(that).attr('credits'));
        } else {
            newprice = price - parseInt($(that).attr('price'));
            newcredits = credits - parseInt($(that).attr('credits'));
        }

        $('#totalPrice').text(newprice);
        $('#totalCredits').text(newcredits);
    }

    const friends_slider = $('#friends_slider').bootstrapSlider({
        formatter: function (value) {
            return 'Friends amount: ' + value;
        }
    });

    const salary_slider = $('#salary_slider').bootstrapSlider({
        formatter: function (value) {
            return 'Scale amount: ' + value;
        }
    });

    $(friends_slider).on('change', function () {
        let newcredits;
        let newprice;

        const value = $(this).bootstrapSlider('getValue');

        const price = parseInt($('#totalPrice').text());
        const credits = parseInt($('#totalCredits').text());

        let count = 0;
        $('.friend').each(function (i, obj) {
            count++;
        });

        if (value != 0 && value > count) {
            const input = '<input type="text" name="friends[' + count + ']" placeholder="SteamID of friend (Leave blank for none)" class="form-control friend" style="margin-top: 5px">';
            $('.friends').append(input);

            newprice = price + parseInt($(this).attr('price'));
            newcredits = credits + parseInt($(this).attr('credits'));
        }

        if (value < count) {
            $('.friends input:last-child').remove();

            newprice = price - parseInt($(this).attr('price'));
            newcredits = credits - parseInt($(this).attr('credits'));
        }

        $('#totalPrice').text(newprice);
        $('#totalCredits').text(newcredits);
    });

    window.lastvalue = 0;
    let finalSalary = 0;

    $(salary_slider).on('change', function () {
        let newcredits;
        let newprice;

        const value = $(this).bootstrapSlider('getValue');
        const base = parseInt($(this).attr('base'));

        if (finalSalary === 0) {
            finalSalary = base;
        }

        const price = parseInt($('#totalPrice').text());
        const credits = parseInt($('#totalCredits').text());

        if (value > window.lastvalue) {
            finalSalary = finalSalary + base;

            newprice = price + parseInt($(this).attr('price'));
            newcredits = credits + parseInt($(this).attr('credits'));
        } else {
            finalSalary = finalSalary - base;

            newprice = price - parseInt($(this).attr('price'));
            newcredits = credits - parseInt($(this).attr('credits'));
        }

        if (value === 0) {
            finalSalary = base;
        }

        $('#totalPrice').text(newprice);
        $('#totalCredits').text(newcredits);

        $('#finalSalary').text(finalSalary);

        window.lastvalue = value;
    });

</script>