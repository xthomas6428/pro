<?php

use Stripe\Checkout\Session;

class stripe
{
    /**
     * @throws Exception
     */
    public static function checkout($type, $pid, $uid, $q_price = null)
    {
        global $db;

        if (!$uid) {
            die('Attempted Steam64ID fraud');
        }

        $apiKey = getSetting('stripe_apiKey', 'value');

        $bad = false;

        \Stripe\Stripe::setApiKey($apiKey);

        $coupon = false;
        if (isset($_GET['coupon'])) {
            $coupon = $_GET['coupon'];
            coupon::useCoupon($coupon);
        }

        $verify = new verification('stripe', $uid, $pid, $coupon);

        $title = '';
        $price = 0;

        if ($type === 'pkg') {
            $res = $db->getOne("SELECT * FROM packages WHERE id = ?", $pid);

            $title = $res['title'];
            $custom_price = $res['custom_price'];
            $custom_price_min = $res['custom_price_min'];

            $price = $custom_price ? $q_price : $verify->getPrice('package');
            if ($custom_price && $custom_price_min > $q_price) {
                $bad = true;
            }
        }

        if ($type === 'credits') {
            $res = $db->getOne("SELECT * FROM credit_packages WHERE id = ?", $pid);
            $title = $res['title'];
            $price = $res['price'];
        }

        if ($type === 'raffle') {
            $res = $db->getAll("SELECT * FROM raffles WHERE id = ?", $pid);
            $title = $res['title'];
            $price = $res['price'];
        }

        $curID = getSetting('dashboard_main_cc', 'value2');
        $currency = $db->getOne("SELECT cc FROM currencies WHERE id = ?", $curID);

        $charge_price = $price * 100;

        if (!$bad) {
            $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http")."://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";

            $actual_link = explode('store.php', $actual_link);
            $success_url = $actual_link[0].'profile.php?cm';
            $cancel_url = $actual_link[0].'store.php';

            $buyer_id = $_SESSION['uid'];
            $clientReferenceData = [
                "type" => $type,
                "price" => $charge_price,
                "itemID" => $pid,
                "uid" => $uid,
                "buyer_id" => $buyer_id,
            ];

            $metadata = [
                'item.type' => $type,
                'item.id' => $pid,
                'user.id' => $buyer_id,
                'receiver.id' => $uid,
            ];

            $customer = $db->getOne("SELECT customer_id FROM stripe_customers WHERE uid = ?", $buyer_id);
            $customerCreation = null;

            if (!$customer) {
                $customer = null;
                $customerCreation = "always";
            }

            $session = Session::create([
                "success_url" => $success_url,
                "cancel_url" => $cancel_url,
                "payment_method_types" => ["card"],
                "client_reference_id" => implode(',', $clientReferenceData),
                "metadata" => $metadata,
                "customer" => $customer,
                "customer_creation" => $customerCreation,
                "payment_intent_data" => [
                    "metadata" => $metadata,
                ],
                'mode' => 'payment',
                'line_items' => [[
                    'price_data' => [
                        'unit_amount' => $charge_price,
                        'currency' => $currency,
                        'product_data' => [
                            'name' => $title,
                        ],
                    ],
                    'quantity' => 1
                ]]
            ]);

            return json_encode([
                'id' => $session['id'],
            ]);
        }
    }
}
