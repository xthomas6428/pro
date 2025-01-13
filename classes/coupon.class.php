<?php

class coupon
{
    /**
     * @throws Exception
     */
    public static function getValue($id = '', $col = '')
    {
        global $db;

        if (empty($id)) {
            return false;
        }

        $res = $db->getOne("SELECT $col FROM coupons WHERe id = ?", $id);

        if ($col === 'expires') {
            if ($res === "0000-00-00 00:00:00") {
                $val = 0;
            } else {
                $date = new DateTime($res);
                $val = $date->format('m/d/Y');
            }
        } else {
            $val = $res;
        }

        return $val;
    }

    public static function isValid($code, $pid): bool
    {
        global $db;

        $res = $db->getAll("SELECT * FROM coupons WHERE coupon = ? AND packages LIKE ? AND (uses != max_uses+1 OR max_uses = 0) AND (expires > now() or expires = '1000-01-01 00:00:00')", [
            $code, '%"' . $pid . '"%'
            ]);

        if ($res) {
            return true;
        }

        return false;
    }

    public static function useCoupon($code): void
    {
        global $db;

        $db->execute("UPDATE coupons SET uses = uses+1 WHERE coupon = ?", $code);
    }

    public static function getIdByCode($code)
    {
        global $db;

        return $db->getOne("SELECT id FROM coupons WHERE coupon = ?", $code);
    }
}
