<?php

if (!csrf_check()) {
    return util::error("Invalid CSRF token!");
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $i = $_POST['pkg_label'];
}

$error = false;
$labels_a = [];

include('admin/pkg/actions.php');

if (isset($_POST['upgrade'])) {
    $upgrade = checkboxArrayStrip($_POST['upgrade']);

    $upgrade_c = json_decode($upgrade, true);
    $upgrade_c = count($upgrade_c);
} else {
    $upgrade = '[]';

    $upgrade_c = 0;
}

if ($upgrade_c > 1) {
    $error = true;

    $message->add('danger', 'You can only select one upgradeable package');
}

$hide = isset($_POST['hide']) ? checkboxArrayStrip($_POST['hide']) : '[]';
$disable = isset($_POST['disable']) ? checkboxArrayStrip($_POST['disable']) : '[]';

if (isset($_POST['custom_price'])) {
    $custom_price = 1;

    if ($_POST['custom_price_min'] < 0) {
        $error = true;
        $message->add('danger', 'The custom price minimum can not be less than 0!');
    }
} else {
    $custom_price = 0;
}

if (isset($_POST['pkg_title']) && $_POST['pkg_title'] == '') {
    $error = true;
    $message->add('danger', 'You need to specify a title!');
}

if (isset($_POST['servers'])) {
    $servers = checkboxArrayStrip($_POST['servers']);
} else {
    $error = true;
    $message->add('danger', 'You need to specify a server!');
}

if ($custom_price == 0) {
    if (getSetting('credits_only', 'value2') == 0) {
        if ($_POST['pkg_price'] == '' or !is_numeric($_POST['pkg_price'])) {
            $error = true;
            $message->add('danger', 'You need to specify a price that is a numeric value!');
        }

        if ($_POST['pkg_price'] < 0) {
            $error = true;
            $message->add('danger', 'The price can not be less than 0!');
        }
    }

    if (gateways::enabled('credits')) {
        if ($_POST['pkg_credits'] == '' or !is_numeric($_POST['pkg_credits'])) {
            $error = true;
            $message->add('danger', 'You need to specify credits that is a numeric value!');
        }

        if ($_POST['pkg_credits'] < 0) {
            $error = true;
            $message->add('danger', 'The credits can not be less than 0!');
        }
    }
}

if (!isset($_POST['pkg_permanent']) && $_POST['pkg_days'] == '' && !is_numeric($_POST['pkg_days'])) {
    $error = true;
    $message->add('danger', 'You need to specify a days value that is numeric!');
}

if (!$error) {
    if ($_POST['pkg_label'] != 'none' && !empty($_POST['pkg_label'])) {
        $labels = json_encode($_POST['labels']);
    } else {
        $labels = '[]';
    }

    if (!isset($_POST['custom_price'])) {
        $price = $_POST['pkg_price'];
        $custom_price_min = null;
    } else {
        $price = 0;
        $custom_price_min = $_POST['custom_price_min'];
    }

    if ($custom_price == 0) {
        if (isset($_POST['pkg_price'])) {
            $price = $_POST['pkg_price'];
        } else {
            if (isset($_GET['id'])) {
                $price = $db->getOne("SELECT price FROM packages WHERE id = ?", $id);
            } else {
                $price = 0;
            }
        }

        if (isset($_POST['pkg_credits'])) {
            $credits = $_POST['pkg_credits'];
        } else {
            if (isset($_GET['id'])) {
                $credits = $db->getOne("SELECT credits FROM packages WHERE id = ?", $id);
            } else {
                $credits = 0;
            }
        }
    } else {
        $price = 0;
        $credits = 0;
    }

    $pkg_hide = isset($_POST['pkg_hide']) ? 1 : 0;
    $title = strip_tags($_POST['pkg_title']);

    $imageurl = strip_tags($_POST['pkg_imageurl']);
    $category = $_POST['pkg_category'];

    $img = '';
    if (isset($_POST['display_check'])) {
        if ($_FILES['pkg_img']["name"] != null) {
            $allowedExts = array("jpg", "png");
            $temp = explode(".", $_FILES["pkg_img"]["name"]);
            $extension = end($temp);

            if (in_array($extension, $allowedExts)) {
                $rand = chr(mt_rand(97, 122)) . substr(md5(time()), 1);
                $uploadfile = 'img' . DIRECTORY_SEPARATOR . 'pkgs' . DIRECTORY_SEPARATOR . $rand . '.' . $extension;
                move_uploaded_file($_FILES['pkg_img']['tmp_name'], $uploadfile);
                $img = $uploadfile;
            } else {
                $message->add('danger', 'The image must be a .png or .jpg');
            }
        } else {
            if (isset($_GET['id'])) {
                $img = $db->getOne("SELECT img FROM packages WHERE id = ?", $id);
            } else {
                $img = 'img/default_img.png';
            }
        }

        if ($_FILES['pkg_img']["name"] == null && $imageurl != '') {
            $img = $imageurl;
        }
    }

    $desc = $_POST['pkg_desc'] == '' ? lang('no_description', 'No description available.') : $_POST['pkg_desc'];

    $subscription = 0;
    if (isset($_POST['pkg_permanent'])) {
        $perm = 1;
        $days = null;
    } else {
        $perm = 0;
        $days = $_POST['pkg_days'];

        if (isset($_POST['pkg_subscription'])) {
            $subscription = 1;
        }
    }

    $expire = isset($_POST['expire']) && $perm == 0 ? checkboxArrayStrip($_POST['expire']) :  '[]';
    $rebuyable = isset($_POST['pkg_rebuyable']) ? 1 : 0;
    $once = isset($_POST['pkg_once']) ? 1 : 0;

    $alternative_pp = '';
    if (gateways::enabled('paypal')) {
        $alternative_pp = isset($_POST['alternative_pp_check']) ? $_POST['alternative_pp'] : '';
    }

    if (isset($_GET['id'])) {
        $db->execute("UPDATE packages SET title = ?, servers = ?, labels = ?, lower_text = ?, price = ?, img = ?, permanent = ?, rebuyable = ?, days = ?, credits = ?, actions = ?, non_compatible = ?, category = ?, custom_price = ?, custom_price_min = ?, upgradeable = ?, hide = ?, expire_linked = ?, subscription = ?, no_owned = ?, bought_disable = ?, alternative_paypal = ?, once = ? WHERE id = ?", array(
            $title, $servers, $labels, $desc, $price, $img, $perm, $rebuyable, $days, $credits, $combined_json, $comp, $category, $custom_price, $custom_price_min, $upgrade, $hide, $expire, $subscription, $pkg_hide, $disable, $alternative_pp, $once, $id
        ));

        $message->add('success', 'Successfully updated a package!');
        prometheus::log('Edited a package', $_SESSION['uid']);

    //actions::updateExistingActions($id);
    } else {
        $db->execute("INSERT INTO packages SET title = ?, servers = ?, labels = ?, lower_text = ?, price = ?, img = ?, permanent = ?, rebuyable = ?, days = ?, credits = ?, actions = ?, non_compatible = ?, category = ?, custom_price = ?, custom_price_min = ?, upgradeable = ?, hide = ?, expire_linked = ?, subscription = ?, no_owned = ?, bought_disable = ?, alternative_paypal = ?, once = ?", array(
            $title, $servers, $labels, $desc, $price, $img, $perm, $rebuyable, $days, $credits, $combined_json, $comp, $category, $custom_price, $custom_price_min, $upgrade, $hide, $expire, $subscription, $pkg_hide, $disable, $alternative_pp, $once
        ));

        $message->add('success', 'Successfully added a package!');
        prometheus::log('Added a package', $_SESSION['uid']);
    }

    cache::clear('purchase');
}
