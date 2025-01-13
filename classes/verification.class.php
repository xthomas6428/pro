<?php

class verification
{
    private $itemID;
    private $gateway;
    private $uid;
    private $buyer_id;

    /**
     * @var bool|string
     */
    private $coupon;

    /**
     * verification constructor.
     * @param $gateway
     * @param $uid
     * @param $itemID
     * @param  bool|string  $coupon
     */
    public function __construct($gateway, $uid, $itemID, $coupon = false)
    {
        $this->gateway = $gateway;
        $this->uid = $uid;
        $this->itemID = $itemID;
        $this->coupon = $coupon;
    }

    /**
     * @param $buyer_id
     */
    public function setBuyerId($buyer_id): void
    {
        $this->buyer_id = $buyer_id;
    }

    /**
     * verifyPackage
     * @param  int or null $price
     * @return array containing error(bool) and msg(string)
     */
    public function verifyPackage($price = null): array
    {
        $for = $this->uid;
        $itemID = $this->itemID;

        $error = false;
        $msg = '';

        if (packages::notCompatible($itemID, $for)) {
            $error = true;

            if ($for === null) {
                $msg = lang('buy_not_compatible');
            } else {
                $msg = lang('buy_they_not_compatible');
            }
        }

        if (packages::alreadyOwn($itemID, $for) && !packages::rebuyable($itemID)) {
            $error = true;
            $msg = lang('buy_already_own');

            if ($for !== null) {
                $msg = lang('buy_they_already_own');
            }
        }

        if (packages::ownedOnce($itemID, $for) && (int) getEditPackage($itemID, 'once') === 1) {
            $error = true;
            $msg = 'You can not buy this package more than once';
        }

        if (packages::disabled($itemID)) {
            $error = true;
            $msg = 'This package is disabled!';
        }

        if ($price !== null && (int) getEditPackage($itemID, 'custom_price') === 1) {
            if ($price <= 0) {
                $error = true;
                $msg = 'This package can not have a price of 0';
            }

            if (!packages::isLegalMinPrice($itemID, $price)) {
                $error = true;
                $msg = 'Attempted minimum price bypass!';
            }
        }

        $customJob = isset($_GET['pid']) && actions::get($_GET['pid'], 'customjob', '');
        if ($customJob) {
            $pre = prepurchase::hasPre($_SESSION['uid'], 'customjob');
            if ($pre === false) {
                $error = true;

                $msg = 'You have not created a custom job for this package!';
            }
        }

        $hide = packages::hide($itemID, $for);

        if ($hide['hide']) {
            $error = true;

            if ($for === null) {
                $msg = lang('package_cantbuy', null, [
                    $hide['packages']
                ]);
            } else {
                $msg = lang('package_they_cantbuy', null, [
                    $hide['packages']
                ]);
            }
        }

        return [
            'error' => $error,
            'msg' => $msg
        ];
    }

    /**
     * @param  $POST array
     * @param  $e array; price, custom_price, cur
     * @return boolean
     */
    public function verify(array $POST, array $e): bool
    {
        global $db;

        $gateway = $this->gateway;
        $itemID = $this->itemID;

        if ($gateway === 'paypal') {
            /**
             * Package code
             */
            $price = $e['price'];
            $custom_price = $e['custom_price'];
            $cur = $e['cur'];
            $alt_pp = $e['alt_pp'];

            $min_price = 0;
            if ($custom_price) {
                $min_price = $db->getOne("SELECT custom_price_min FROM packages WHERE id = ?", $itemID);
            }

            $receiver_email = $POST['receiver_email'] ?? $POST['business'];
            $store_emails = [strtolower($alt_pp), strtolower(getSetting('paypal_email', 'value'))];

            if (!in_array($receiver_email, $store_emails)) {
                return false;
            }

            if ((!$custom_price && $POST['payment_status'] === "Completed" && $POST['mc_currency'] === $cur && ($POST['mc_gross'] >= $price || $POST['payment_gross'] >= $price)) // Normal payment, no custom and no recurring
                || ($custom_price && $POST['payment_status'] === "Completed" && $POST['mc_currency'] === $cur && $POST['mc_gross'] >= $min_price) // Normal w/custom price
                || ($custom_price && $POST['txn_type'] === 'recurring_payment' && $POST['mc_currency'] === $cur && $POST['mc_amount3'] >= $min_price) // Recurring payment w/custom price
                || (!$custom_price && $POST['txn_type'] === 'recurring_payment' && $POST['mc_amount3'] === $price) // Normal recurring payment
            ) {
                $db->execute("INSERT INTO requests SET error = 0, msg = 'PayPal verification successful - POST', debug = ?",
                    json_encode($POST));
                $db->execute("INSERT INTO requests SET error = 0, msg = 'PayPal verification successful - EXTRA', debug = ?",
                    "Custom Price: $custom_price | Currency: $cur | PKG Price: $price");

                return true;
            }

            $db->execute("INSERT INTO requests SET error = 1, msg = 'PayPal verification failed - POST', debug = ?",
                json_encode($POST));
            $db->execute("INSERT INTO requests SET error = 1, msg = 'PayPal verification failed - EXTRA', debug = ?",
                "Custom Price: $custom_price | Currency: $cur | PKG Price: $price");

            return false;
        }

        return false;
    }

    /**
     * @param  $POST array
     * @return boolean
     */
    public function isChargeback($POST): bool
    {
        $gateway = $this->gateway;

        if ($gateway === 'paypal') {
            return ($POST['payment_status'] === 'Reversed' && ($POST['reason_code'] === 'chargeback'
                    || $POST['reason_code'] === 'buyer-complaint'
                    || $POST['reason_code'] === 'refund'
                    || $POST['reason_code'] === 'unauthorized_claim'
                    || $POST['reason_code'] === 'unauthorized_spoof')
            );
        }

        return false;
    }

    /**
     * @param $type
     * @param  null  $moneyType
     * @param  bool  $cjob
     * @return float
     * @throws Exception
     */
    public function getPrice($type, $moneyType = null, bool $cjob = false): float
    {
        global $db;

        $itemID = $this->itemID;
        $user_id = $this->uid;
        $coupon = $this->coupon;
        $price = false;

        if ($type === 'package') {
            if ($moneyType === null) {
                $price = $db->getOne("SELECT price FROM packages WHERE id = ?", $itemID);

                if ($price != 0) {
                    $upgrade = packages::upgradeable($itemID, null, $user_id);

                    if ($upgrade && $user_id !== null) {
                        $pkg = packages::upgradeable($itemID, 'list', $user_id);
                        $price = packages::upgradeInfo($itemID, $pkg, 'price', $price);
                    }

                    $sale_ar = getSetting('sale_packages', 'value');
                    $sale_ar = json_decode($sale_ar, true);
                    $perc = getSetting('sale_percentage', 'value2');

                    try {
                        $sale_end = new datetime(getSetting('sale_enddate', 'value'));
                        $valid_date = true;
                    } catch (Exception $e) {
                        $sale_end = null;
                        $valid_date = false;
                    }

                    if (!is_array($sale_ar)) {
                        $sale_ar = array();
                    }

                    if (in_array($itemID, $sale_ar) && $valid_date && $sale_end > new datetime()) {
                        $orgprice = $price;
                        $price = $perc / 100 * $orgprice;
                        $price = $orgprice - $price;
                        $price = number_format($price, 2, '.', '');
                    }
                }

                if (!$cjob) {
                    $customjob = actions::get($itemID, 'customjob', '', $user_id);

                    if ($customjob) {
                        $buyer_id = $_SESSION['uid'] ?? $this->buyer_id;
                        $pre = prepurchase::hasPre($buyer_id, 'customjob');

                        if ($pre !== false) {
                            $json = prepurchase::getJson($pre);
                            $array = json_decode($json, true);

                            $price = $array['fullTotalPrice'];
                        }
                    }
                }
            }

            if ($moneyType === 'credits') {
                $price = (float) $db->getOne("SELECT credits FROM packages WHERE id = ?", $itemID);

                if (!gateways::enabled('credits')) {
                    die('This system does not have credits enabled');
                }

                if ($price != 0) {
                    $upgrade = packages::upgradeable($itemID, null, $user_id);
                    if ($upgrade) {
                        $pkg = packages::upgradeable($itemID, 'list', $user_id);
                        $price = packages::upgradeInfo($itemID, $pkg, 'credits', $price);
                    }

                    $sale_ar = getSetting('sale_packages', 'value');
                    $sale_ar = json_decode($sale_ar, true);
                    $perc = getSetting('sale_percentage', 'value2');

                    if (!is_array($sale_ar)) {
                        $sale_ar = array();
                    }

                    if (in_array($itemID, $sale_ar) && new datetime(getSetting('sale_enddate',
                            'value')) > new datetime()) {
                        $orgprice = $price;
                        $price = $perc / 100 * $orgprice;
                        $price = $orgprice - $price;
                    }
                }

                if (!$cjob) {
                    $customJob = actions::get($itemID, 'customjob', '');

                    if ($customJob) {
                        $pre = prepurchase::hasPre($_SESSION['uid'], 'customjob');
                        if ($pre !== false) {
                            $json = prepurchase::getJson($pre);
                            $array = json_decode($json, true);

                            $price = $array['fullTotalCredits'];
                        }
                    }
                }
            }

            if ($coupon && getSetting('enable_coupons', 'value2') && coupon::isValid($coupon,
                    $itemID)) {
                $coupon_id = coupon::getIdByCode($coupon);

                $coupon_percent = coupon::getValue($coupon_id, 'percent');

                if ($coupon_percent === 100) {
                    $price = 0;
                } else {
                    $orgprice = $price;
                    $price = $coupon_percent / 100 * $orgprice;
                    $price = $orgprice - $price;
                }
            }

            $price = number_format($price, 2, '.', '');
        } elseif ($type === 'raffle') {
            if ($moneyType === null) {
                $price = $db->getOne("SELECT price FROM raffles WHERE id = ?", $itemID);
            }

            if ($moneyType === 'credits') {
                $price = $db->getOne("SELECT credits FROM raffles WHERE id = ?", $itemID);
            }
        } elseif ($type === 'credits') {
            $price = $db->getOne("SELECT price FROM credit_packages WHERE id = ?", $itemID);
        }

        return (float) $price;
    }
}
