<?php
/*
 * khoaofgod@gmail.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://faster.phpfastcache.com
 */

require_once(__DIR__."/abstract.php");
require_once(__DIR__."/driver.php");

// short function
if ( ! function_exists("__c")) {
    /**
     * @throws Exception
     */
    function __c($storage = "", $option = array())
    {
        return phpFastCache($storage, $option);
    }
}

// main function
if ( ! function_exists("phpFastCache")) {
    /**
     * @throws Exception
     */
    function phpFastCache($storage = "auto", $config = array())
    {
        $storage = strtolower($storage);
        if (empty($config)) {
            $config = phpFastCache::$config;
        }

        if (empty($storage) || $storage === "auto") {
            $storage = phpFastCache::getAutoClass($config);
        }

        $instance = md5(json_encode($config).$storage);
        if ( ! isset(phpFastCache_instances::$instances[$instance])) {
            $class = "phpfastcache_".$storage;
            phpFastCache::required($storage);
            phpFastCache_instances::$instances[$instance] = new $class($config);
        }

        return phpFastCache_instances::$instances[$instance];
    }
}

class phpFastCache_instances
{
    public static $instances = array();
}

// main class
class phpFastCache
{
    public static $disabled = false;
    public static $config = array(
        "storage"       => "", // blank for auto
        "default_chmod" => 0777, // 0777 , 0666, 0644
        /*
         * Fall back when old driver is not support
         */
        "fallback"      => "files",

        "securityKey" => "auto",
        "htaccess"    => true,
        "path"        => "",

        "memcache" => array(
            array("127.0.0.1", 11211, 1),
            //  array("new.host.ip",11211,1),
        ),

        "redis" => array(
            "host"     => "127.0.0.1",
            "port"     => "",
            "password" => "",
            "database" => "",
            "timeout"  => ""
        ),

        "ssdb" => array(
            "host"     => "127.0.0.1",
            "port"     => 8888,
            "password" => "",
            "timeout"  => ""
        ),

        "extensions" => array(),
    );

    protected static $tmp = array();
    public $instance;

    /**
     * @throws Exception
     */
    public function __construct($storage = "", $config = array())
    {
        if (empty($config)) {
            $config = self::$config;
        }
        $config['storage'] = $storage;

        $storage = strtolower($storage);
        if (empty($storage) || $storage === "auto") {
            $storage = self::getAutoClass($config);
        }

        $this->instance = phpFastCache($storage, $config);
    }

    public function __call($name, $args)
    {
        return call_user_func_array(array($this->instance, $name), $args);
    }

    /*
     * Cores
     */

    /**
     * @throws Exception
     */
    public static function getAutoClass($config): string
    {
        $path = self::getPath(false, $config);
        if (is_writable($path)) {
            $driver = "files";
        } elseif (extension_loaded('apc') && ini_get('apc.enabled') && strpos(PHP_SAPI, "CGI") === false) {
            $driver = "apc";
        } elseif (class_exists("memcached")) {
            $driver = "memcached";
        } elseif (function_exists("wincache_ucache_set") && extension_loaded('wincache')) {
            $driver = "wincache";
        } elseif (function_exists("xcache_get") && extension_loaded('xcache')) {
            $driver = "xcache";
        } elseif (function_exists("memcache_connect")) {
            $driver = "memcache";
        } elseif (class_exists("Redis")) {
            $driver = "redis";
        } else {
            $driver = "files";
        }

        return $driver;
    }

    /**
     * @throws Exception
     */
    public static function getPath($skip_create_path = false, $config = [])
    {
        if (empty($config['path'])) {

            // revision 618
            if (self::isPHPModule()) {
                $tmp_dir = ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
                $path    = $tmp_dir;
            } else {
                $path = isset($_SERVER['DOCUMENT_ROOT']) ? rtrim($_SERVER['DOCUMENT_ROOT'], "/")."/../" : rtrim(__DIR__,
                        "/")."/";
            }

            if (self::$config['path'] !== "") {
                $path = $config['path'];
            }
        } else {
            $path = $config['path'];
        }

        $securityKey = $config['securityKey'];
        if (empty($securityKey) || $securityKey === "auto") {
            $securityKey = self::$config['securityKey'];
            if ($securityKey === "auto" || empty($securityKey)) {
                $securityKey = isset($_SERVER['HTTP_HOST']) ? ltrim(strtolower($_SERVER['HTTP_HOST']),
                    "www.") : "default";
                $securityKey = preg_replace("/[^a-zA-Z0-9]+/", "", $securityKey);
            }
        }

        if ( ! empty($securityKey)) {
            $securityKey .= "/";
        }

        $full_path  = $path."/".$securityKey;
        $full_pathx = md5($full_path);

        if (!$skip_create_path && ! isset(self::$tmp[$full_pathx])) {
            if ( ! @file_exists($full_path) || ! @is_writable($full_path)) {
                if ( ! @file_exists($full_path) && ! mkdir($full_path,
                        self::setChmodAuto($config)) && ! is_dir($full_path)) {
                            throw new \RuntimeException(sprintf('Directory "%s" was not created', $full_path));
                        }
                if ( ! @is_writable($full_path)) {
                    @chmod($full_path, self::setChmodAuto($config));
                }
                if ( ! @file_exists($full_path) || ! @is_writable($full_path)) {
                    throw new \RuntimeException("PLEASE CREATE OR CHMOD ".$full_path." - 0777 OR ANY WRITABLE PERMISSION!", 92);
                }
            }

            self::$tmp[$full_pathx] = true;
            self::htaccessGen($full_path, $config['htaccess']);
        }

        return realpath($full_path);
    }

    public static function setChmodAuto($config)
    {
        if (empty($config['default_chmod'])) {
            return 0777;
        }

        return $config['default_chmod'];
    }

    public static function isPHPModule(): bool
    {
        if (PHP_SAPI === "apache2handler") {
            return true;
        }

        if (strpos(PHP_SAPI, "handler") !== false) {
            return true;
        }

        return false;
    }

    /**
     * @throws Exception
     */
    protected static function htaccessGen($path, $create = true): void
    {
        if ($create) {
            if ( ! is_writable($path)) {
                try {
                    chmod($path, 0777);
                } catch (Exception $e) {
                    throw new \RuntimeException("PLEASE CHMOD ".$path." - 0777 OR ANY WRITABLE PERMISSION!", 92);
                }
            }
            if ( ! @file_exists($path."/.htaccess")) {
                //   echo "write me";
                $html = "order deny, allow \r\n
deny from all \r\n
allow from 127.0.0.1";

                $f = @fopen($path."/.htaccess", 'wb+');
                if ( ! $f) {
                    throw new \RuntimeException("PLEASE CHMOD ".$path." - 0777 OR ANY WRITABLE PERMISSION!", 92);
                }
                fwrite($f, $html);
                fclose($f);
            }
        }
    }


    public static function setup($name, $value = ""): void
    {
        if (is_array($name)) {
            self::$config = $name;
        } else {
            self::$config[$name] = $value;
        }
    }

    public static function debug($something): void
    {
        echo "Starting Debugging ...<br>\r\n ";
        if (is_array($something)) {
            echo "<pre>";
            print_r($something);
            echo "</pre>";
            var_dump($something);
        } else {
            echo $something;
        }
        echo "\r\n<br> Ended";
        exit;
    }

    public static function required($class): void
    {
        require_once(__DIR__."/drivers/$class.php");
    }
}
