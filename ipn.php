<?php

/**
 * Require all the main functions
 */
$page = 'ipn';
require_once('inc/functions.php');

global $db;

/**
 * Tell it where to write error logs to
 */
ini_set('log_errors', true);
ini_set('error_log', dirname(__FILE__).'/ipn_errors.log');

/**
 * Include the IPNListener module
 */
include('ipnlistener.php');
$listener = new IpnListener();

/**
 * Check whether or not sandbox mode is enabled
 */
$use_sandbox = getSetting('paypal_sandbox', 'value2') == 1;

$listener->use_sandbox = $use_sandbox;
$verified = $listener->processIpn();

/**
 * If the PayPal $paypal_data is valid, continue
 */
if ($verified) {
    $pData = $listener->getPostData();

    $itemID = !isset($pData['item_number']) ? $pData['item_number1'] : $pData['item_number'];
    $txn_id = $pData['txn_id'];

    $customArray = explode('|', $pData['custom']);

    $uid_array = explode(',', $customArray[0]);
    $custom = $uid_array[0];
    $buyer_id = $uid_array[0];

    if (count($uid_array) > 1) {
        $buyer_id = $uid_array[1];
    }

    $coupon = false;
    if (isset($customArray[2])) {
        $coupon = $customArray[2];
        coupon::useCoupon($coupon);
    }

    $type = $customArray[1];

    $verify = new verification('paypal', $custom, $itemID, $coupon);

    $curID = getSetting('dashboard_main_cc', 'value2');
    $cur = $db->getOne("SELECT cc FROM currencies WHERE id = ?", $curID);
    $custom_price = 0;

    if (!isset($pData['item_name'])) {
        $item_name = $pData['item_name1'];
    } else {
        $item_name = $pData['item_name'];
    }

    $item_name = str_replace('+', ' ', $item_name);

    if ($type == 'package') {
        $pkgInfo = $db->getAll("SELECT * FROM packages WHERE id = ?", $itemID);
        foreach ($pkgInfo as $info) {
            $title = $info['title'];
            $custom_price = $info['custom_price'];
            $alt_pp = $info['alternative_paypal'];
        }
    }

    if ($type == 'credits') {
        $pkgInfo = $db->getAll("SELECT * FROM credit_packages WHERE id = ?", $itemID);
        foreach ($pkgInfo as $info) {
            $credits = $info['amount'];
        }

        $custom_price = 0;
    }

    if ($type == 'raffle') {
        $pkg = $db->getOne("SELECT package FROM raffles WHERE id = ?", $itemID);
    }

    /**
     * Get the price of the product after sale price, upgrade price, etc
     */
    $verify->setBuyerId($buyer_id);
    $price = $verify->getPrice($type);

    /**
     * Check if the transaction is a chargeback
     */
    $chargeback = $verify->isChargeback($pData);

    /**
     * If the transaction is a chargeback request, fuck it up
     */
    if ($chargeback) {
        if ($type === 'package') {
            $db->execute("UPDATE actions SET active = 0, delivered = 0 WHERE uid = ? AND package = ?", [
                $custom, $itemID
            ]);

            $db->execute("DELETE FROM transactions WHERE txn_id = ?", $txn_id);
            prometheus::log('Package disabled due to a chargeback!', $custom);
        }

        if ($type === 'credits') {
            $credits_amt = $db->getOne("SELECT amount FROM credit_packages WHERE id = ?", $itemID);
            $credits_has = $db->getOne("SELECT credits FROM players WHERE uid = ?", $custom);

            $amt = $credits_has - $credits_amt;

            credits::set($custom, $amt);

            $db->execute("UPDATE actions SET active = 0, delivered = 0 WHERE uid = ?", $custom);
            $db->execute("DELETE FROM transactions WHERE txn_id = ?", $txn_id);

            prometheus::log($credits_amt.' credits revoked due to a chargeback!', $custom);
            prometheus::log('All packages revoked due to a chargeback!', $custom);
        }

//        if ($type == 'raffle') {
//            // TODO -> But how the fuck do I detect the exact package someone won in a raffle?
//            // Perhaps create a timestamp for when the raffle ended in an entirely new table?
//        }
    }

    /**
     * If the transaction is an eCheck, straight up deny it
     */
    if ($pData['payment_type'] === 'echeck') {
        die('eChecks are not allowed'); /* todo: allow this */
    }

    /**
     * Check if the transaction is valid
     */
    if (!isset($alt_pp)) {
        $alt_pp = '';
    }

    $e = [
        'price' => $price,
        'custom_price' => $custom_price,
        'cur' => $cur,
        'alt_pp' => $alt_pp
    ];

    $valid = $verify->verify($pData, $e);

    /**
     * Assign product only if valid
     */
    if (!$valid) {
        return;
    }
    /**
     * If the transaction is a recurring payment, use a different price amount
     */
    $price = $txn_id == 'recurring_payment' ? $pData['mc_amount3'] : (!isset($pData['mc_gross']) ? $pData['mc_gross_1'] : $pData['mc_gross']);

    /**
     * If the Steam64 is 0 then die
     */
    if ($custom == 0) {
        $db->execute("INSERT INTO requests SET error = 1, msg = 'Attempted steam64 fraud! (Steam64 ID is 0)'");
        die('Invalid Steam64 ID');
    }

    /**
     * Check for transaction ID re-usage
     */
    $stmt = $db->getOne("SELECT email FROM transactions WHERE txn_id = ?", $pData['txn_id']);
    if ($stmt) {
        $db->execute("INSERT INTO requests SET error = 1, msg = 'txn_id fraud warning!'");
        die('Reused txn_id');
    }

    if ($type == 'package') {
        $packageVerify = $verify->verifyPackage((float) $price);
        if ($packageVerify['error']) {
            $db->execute("INSERT INTO requests SET error = 1, msg = 'Package verification failed - attempted spoof?', debug = ?",
                "User: $custom | Item ID: $itemID | Msg: {$packageVerify['msg']}");
            die('Attempted package purchase spoof');
        }

        $db->execute("INSERT INTO transactions SET name = ?, email = ?, uid = ?, buyer_uid = ?, package = ?, currency = ?, price = ?, txn_id = ?, gateway = 'paypal'",
            [
                $pData['first_name']." ".$pData['last_name'], $pData['payer_email'], $custom, $buyer_id, $itemID,
                $pData['mc_currency'], $price, $pData['txn_id']
            ]);

        $trans = $db->getOne("SELECT id FROM transactions WHERE txn_id = ?", $pData['txn_id']);

        $p_array = array(
            "id" => $itemID,
            "trans_id" => $trans,
            "uid" => $custom,
            "buyer_id" => $buyer_id,
            "type" => 1
        );
        addAction($p_array);
    }

    if ($type == 'credits') {
        $db->execute("INSERT INTO transactions SET name = ?, email = ?, uid = ?, buyer_uid = ?, credit_package = ?, currency = ?, price = ?, credits = ?, txn_id = ?, gateway = 'paypal'",
            array(
                $pData['first_name']." ".$pData['last_name'], $pData['payer_email'], $custom, $buyer_id, $itemID,
                $pData['mc_currency'], $price, $credits, $pData['txn_id']
            ));

        $credits_old = $db->getOne("SELECT credits FROM players WHERE uid = ?", $custom);
        $credits_new = $credits_old + $credits;
        credits::set($custom, $credits_new);

        $p_array = array(
            "id" => 0,
            "trans_id" => 0,
            "uid" => $custom,
            "amount" => $credits,
            "type" => 2
        );
        addAction($p_array);
    }

    if ($type == 'raffle') {
        $count = $db->getOne("SELECT count(*) FROM raffle_tickets WHERE raffle_id = ? AND uid = ?", [
            $itemID, $custom
        ]);
        $max_per_person = $db->getOne("SELECT max_per_person FROM raffles WHERE id = ?", $itemID);

        if ($count != $max_per_person) {
            $db->execute("INSERT INTO transactions SET name = ?, email = ?, uid = ?, buyer_uid = ?, currency = ?, price = ?, txn_id = ?, raffle_package = ?, gateway = 'paypal'",
                [
                    $pData['first_name']." ".$pData['last_name'], $pData['payer_email'], $custom, $buyer_id,
                    $pData['mc_currency'], $price, $pData['txn_id'], $itemID
                ]);

            $db->execute("INSERT INTO raffle_tickets SET raffle_id = ?, uid = ?", [
                $itemID, $custom
            ]);
        }
    }

    cache::clear('purchase', $custom);
} else {

    /**
     * Insert error if the request wasn't a valid PayPal response
     */
    $db->execute("INSERT INTO requests SET error = 1, msg = 'PayPal verification failed! The request is not a valid PayPal request'");
}
