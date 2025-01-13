<?php

/**
 * Require all the main functions
 */

use Stripe\Error\SignatureVerification;
use Stripe\Webhook;

$page = 'stripe';
require_once('inc/functions.php');

/**
 * Tell it where to write error logs to
 */
ini_set('log_errors', true);
ini_set('error_log', __DIR__.'/stripe_errors.log');

\Stripe\Stripe::setApiKey(getSetting('stripe_apiKey', 'value'));
$endpoint_secret = getSetting('stripe_webhookSecret', 'value');

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$event = null;

try {
    $event = Webhook::constructEvent(
        $payload,
        $sig_header,
        $endpoint_secret
    );
} catch (\UnexpectedValueException $e) {
    // Invalid payload
    http_response_code(400);
    exit();
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    // Invalid signature
    http_response_code(400);
    exit();
}

global $db;

// Handle the event
$eventType = $event->type;
if ($eventType === 'checkout.session.completed') {
    $session = $event->data->object;

    [$type, $price, $itemId, $uid, $buyerId] = explode(',', $session->client_reference_id);

    // ternary due to backwards compatibility with earlier Stripe API versions
    $currency = isset($session->display_items[0]->currency)
        ? strtoupper($session->display_items[0]->currency)
        : strtoupper($session->currency);

    $customerId = $session->customer;
    $customerInDb = $db->getOne("SELECT customer_id FROM stripe_customers WHERE uid = ?", $buyerId);

    if ($customerId && !$customerInDb) {
        $db->execute("INSERT INTO stripe_customers (uid, customer_id) VALUES (?, ?)", [$buyerId, $customerId]);
    }

    $transactionId = $session->payment_intent;

    $price /= 100;

    if ($type === 'pkg') {
        $name = $db->getOne("SELECT name FROM players WHERE uid = ?", $uid);

        $db->execute(
            "INSERT INTO transactions SET name = ?, buyer = ?, email = ?, uid = ?, buyer_uid = ?, package = ?, currency = ?, price = ?, txn_id = ?, gateway = 'stripe'",
            array($name, $name, '', $uid, $buyerId, $itemId, $currency, $price, $transactionId)
        );

        $trans = $db->getOne("SELECT id FROM transactions WHERE txn_id = ?", $transactionId);

        $purchaseArray = array(
            "id" => $itemId,
            "trans_id" => $trans,
            "uid" => $uid,
            "buyer_id" => $buyerId,
            "type" => 1,
        );
        addAction($purchaseArray);
    }

    if ($type === 'credits') {
        $name = $db->getOne("SELECT name FROM players WHERE uid = ?", $uid);
        $credits = $db->getOne("SELECT amount FROM credit_packages WHERE id = ?", $itemId);

        $db->execute("INSERT INTO transactions SET name = ?, buyer = ?, email = ?, uid = ?, buyer_uid = ?, credit_package = ?, currency = ?, price = ?, credits = ?, txn_id = ?, gateway = 'stripe'",
            array(
                $name, $name, '', $uid, $buyerId, $itemId, $currency, $price, $credits, $transactionId,
            ));

        $creditsOld = $db->getOne("SELECT credits FROM players WHERE uid = ?", $uid);
        $creditsNew = $creditsOld + $credits;
        credits::set($uid, $creditsNew);

        $purchaseArray = array(
            "id" => 0,
            "trans_id" => 0,
            "uid" => $uid,
            "buyer_id" => $buyerId,
            "amount" => $credits,
            "type" => 2,
        );
        addAction($purchaseArray);
    }

    if ($type === 'raffle') {
        $name = $db->getOne("SELECT name FROM players WHERE uid = ?", $uid);
        $credits = $db->getOne("SELECT credits FROM raffles WHERE id = ?", $itemId);

        $count = $db->getOne("SELECT count(*) AS value FROM raffle_tickets WHERE raffle_id = ?, uid = ?",
            array($itemId, $uid))['value'];
        $maxPerPerson = $db->getOne("SELECT max_per_person FROM raffles WHERE id = ?", [$itemId])['max_per_person'];

        if ($count !== $maxPerPerson) {
            $db->execute("INSERT INTO transactions SET name = ?, buyer = ?, email = ?, uid = ?, buyer_uid = ?, raffle_package = ?, currency = ?, price = ?, credits = ?, txn_id = ?, gateway = 'stripe'",
                array(
                    $name, $name, '', $uid, $buyerId, $itemId, $currency, $price, $credits, $transactionId,
                ));

            $db->execute("INSERT INTO raffle_tickets SET raffle_id = ?, uid = ?", array($itemId, $uid));
        }
    }

    cache::clear('purchase', $uid);
} else {
    // Unexpected event type
    http_response_code(400);
    exit();
}

http_response_code(200);
