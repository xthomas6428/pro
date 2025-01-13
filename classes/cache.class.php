<?php

class cache
{
    public static function set($identifier, $value, $time, ?string $uid = '', ?string $extra = ''): bool
    {
        global $cache, $enable_cache;

        if (!$enable_cache) {
            return false;
        }

        if (!empty($extra)) {
            $extra = '_' . $extra;
        }

        if (!empty($uid)) {
            $uid = '_' . $uid;
        }

        preg_match('/\d[h]|\d[d]|\d[w]|\d[m]|\d[y]/', $time, $match);
        if (isset($match[0])) {
            $time = timeStrToInt($match[0]);
        }

        $fullIdentifier = $identifier . $uid . $extra;

        $cache->set($fullIdentifier, $value, $time);

        return true;
    }

    public static function get($identifier, $uid = '', $extra = '')
    {
        global $cache;

        if (!empty($extra)) {
            $extra = '_' . $extra;
        }

        if (!empty($uid)) {
            $uid = '_' . $uid;
        }

        $fullIdentifier = $identifier . $uid . $extra;

        return $cache->get($fullIdentifier);
    }

    /**
     * @param  string identifier
     * @param  string
     * @param  string
     */
    public static function del($identifier, $uid = '', $extra = ''): void
    {
        global $cache;

        if (!empty($extra)) {
            $extra = '_' . $extra;
        }

        if (!empty($uid)) {
            $uid = '_' . $uid;
        }

        $fullIdentifier = $identifier . $uid . $extra;

        $cache->delete($fullIdentifier);
    }

    public static function clear($action = null, $uid = ''): void
    {
        global $cache;

        if (!empty($uid) && prometheus::loggedIn()) {
            $uid = $_SESSION['uid'];
        }

        if ($action === null) {
            $cache->clean();
        }

        if ($action === 'purchase') {
            self::del('getPackageSales');
            self::del('getServerSales');
            self::del('dashboard_currencies');
            self::del('topDonators');
            self::del('recentDonators');
            self::del('getRevenue_money');
            self::del('getRevenue_credits');

            if (!empty($uid)) {
                self::del('getRevenue_money', $uid);
                self::del('getRevenue_credits', $uid);
                self::del('credits', $uid);
                self::del('getPackageHistory', $uid);
                self::del('getPermanentPackages', $uid);
                self::del('getNonPermanentPackages', $uid);
            }
        }

        if ($action === 'settings') {
            self::del('settings');
        }

        if ($action === 'actions') {
            self::del('actions');
        }

        if ($action === 'servers') {
            self::del('servers');
        }

        if ($action === 'news') {
            self::del('news_sidebar');
        }

        if ($action === 'frontpage') {
            self::del('frontpage');
        }
    }
}
