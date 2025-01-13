<?php
/*
 * khoaofgod@gmail.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://faster.phpfastcache.com
 */

class phpfastcache_wincache extends BasePhpFastCache implements phpfastcache_driver
{
    public function checkdriver()
    {
        if (extension_loaded('wincache') && function_exists("wincache_ucache_set")) {
            return true;
        }
        $this->fallback = true;
        return false;
    }

    public function __construct($config = array())
    {
        $this->setup($config);
        if (!$this->checkdriver() && !isset($config['skipError'])) {
            $this->fallback = true;
        }
    }

    public function driver_set($keyword, $value = "", $time = 300, $option = array())
    {
        if (isset($option['skipExisting']) && $option['skipExisting'] == true) {
            return wincache_ucache_add($keyword, $value, $time);
        } else {
            return wincache_ucache_set($keyword, $value, $time);
        }
    }

    public function driver_get($keyword, $option = array())
    {
        // return null if no caching
        // return value if in caching

        $x = wincache_ucache_get($keyword, $suc);

        if ($suc == false) {
            return null;
        } else {
            return $x;
        }
    }

    public function driver_delete($keyword, $option = array())
    {
        return wincache_ucache_delete($keyword);
    }

    public function driver_stats($option = array())
    {
        $res = array(
            "info"  =>  "",
            "size"  =>  "",
            "data"  =>  wincache_scache_info(),
        );
        return $res;
    }

    public function driver_clean($option = array())
    {
        wincache_ucache_clear();
        return true;
    }

    public function driver_isExisting($keyword)
    {
        if (wincache_ucache_exists($keyword)) {
            return true;
        } else {
            return false;
        }
    }
}
