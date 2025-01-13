<?php

class credits
{
    public static function hasEnough($uid, $pkg, $type, $coupon = false, $receiver_uid = null, $price = null): bool
    {
        global $db;

        if ($receiver_uid === null) {
            $receiver_uid = $uid;
        }

        $custom_price = (bool)$db->getOne("SELECT custom_price FROM packages WHERE id = ?", $pkg);

        if ($custom_price) {
            $cost = $price;
        } else {
            $verify = new verification('credits', $receiver_uid, $pkg, $coupon);
            $verify->setBuyerId($uid);
            $cost = $verify->getPrice($type, 'credits');
        }

        $amt = $db->getOne("SELECT credits FROM players WHERE uid = ?", $uid);

        return $amt >= $cost;
    }

    /**
     * @throws Exception
     */
    public static function withdraw($uid, $pkg, $type, $coupon = false, $receiver_uid = null, $price = null): void
    {
        global $db;

        if ($receiver_uid === null) {
            $receiver_uid = $uid;
        }

        $custom_price = (bool)$db->getOne("SELECT custom_price FROM packages WHERE id = ?", $pkg);

        if ($custom_price) {
            $cost = $price;
        } else {
            $verify = new verification('credits', $receiver_uid, $pkg, $coupon);
            $verify->setBuyerId($uid);
            $cost = $verify->getPrice($type, 'credits');
        }

        $amt = $db->getOne("SELECT credits FROM players WHERE uid = ?", $uid);
        $new = $amt - $cost;
        $db->execute("UPDATE players SET credits = ? WHERE uid = ?", [
            $new, $uid
        ]);
    }

    public static function add($p): void
    {
        global $db;

        $db->execute("INSERT INTO credit_packages SET title = ?, descr = ?, amount = ?, price = ?", [
            $p['title'], $p['descr'], $p['amt'], $p['price']
        ]);
    }

    public static function get($uid)
    {
        global $db;

        $ret = cache::get('credits', $uid);

        if ($ret === null) {
            $ret = $db->getOne("SELECT credits FROM players WHERE uid = ?", $uid);

            cache::set('credits', $ret, '1y', $uid);
        }

        return $ret;
    }

    public static function transfer($uid, $amt): void
    {
        global $db;

        $self_amt = $db->getOne("SELECT credits FROM players WHERE uid = ?", $_SESSION['uid']);
        $self_amt_after = $self_amt - $amt;
        $db->execute("UPDATE players SET credits = ? WHERE uid = ?", [
            $self_amt_after, $_SESSION['uid']
        ]);

        $other_amt = $db->getOne("SELECT credits FROM players WHERE uid = ?", $uid);
        $other_amt_after = $other_amt + $amt;
        $db->execute("UPDATE players SET credits = ? WHERE uid = ?", [
            $other_amt_after, $uid
        ]);

        cache::del('credits', $_SESSION['uid']);
        cache::del('credits', $uid);
    }

    public static function set($uid, $amt): void
    {
        global $db;

        $db->execute("UPDATE players SET credits = ? WHERE uid = ?", [
            $amt, $uid
        ]);

        cache::del('credits', $uid);
    }

    public static function getValue($id, $val)
    {
        global $db;

        if (!empty($id)) {
            return $db->getOne("SELECT $val FROM credit_packages WHERE id = ?", $id);
        }

        return false;
    }

    public static function del($id): void
    {
        global $db;

        $db->execute("DELETE FROM credit_packages WHERE id = ?", [$id]);
    }

    public static function update($p): void
    {
        global $db;

        $db->execute("UPDATE credit_packages SET title = ?, descr = ?, amount = ?, price = ? WHERE id = ?", [
            $p['title'], $p['descr'], $p['amt'], $p['price'], $p['id']
        ]);
    }
}
