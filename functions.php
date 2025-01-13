<?php

// Set all the global statements and includes
// Rev 518360635
$version = '1.9.6';

// This is ONLY enabled in the Prometheus demo
$demo = false;

/**
 * Language setting
 */

$language = 'en-gb'; // To make a new language file, copy the lang/en-gb.php file and rename it to for example "fr.php"

/**
 * Use cache setting
 */
$use_cache = true;

/**
 * Timezone
 */
if (isset($timezone)) {
    date_default_timezone_set($timezone);
}

if (isset($_SESSION['uid'])) {
    $UID = $_SESSION['uid'];
}

/**
 * Check requirements
 */

if (!defined('PHP_VERSION_ID')) {
    $phpv = explode('.', PHP_VERSION);

    define('PHP_VERSION_ID', ($phpv[0] * 10000 + $phpv[1] * 100 + $phpv[2]));
}

$canRun = true;
$runMsg = '<b style="color: darkgreen;">Your PHP installation is able to run Prometheus</b>';
$extra = '';

if (PHP_VERSION_ID < 70100) {
    $php = 'Your PHP version does NOT support Prometheus. A minimum version of PHP 7.1 is required.';
    $canRun = false;
    $extra .= '<br>Your PHP version is massively out of date and has already reached End Of Life.';
} else {
    $php = 'Your PHP version supports Prometheus';
}

$curl = function_exists('curl_version') ? '<b style="color: darkgreen;">Yes</b>' : '<b style="color: darkred;">No</b>';
if (!function_exists('curl_version')) {
    $canRun = false;
}

$fopen = ini_get('allow_url_fopen') ? '<b style="color: darkgreen;">Yes</b>' : '<b style="color: darkred;">No</b>';
if (!ini_get('allow_url_fopen')) {
    $canRun = false;
    $extra .= 'You will not be able to sign in because url_allow_fopen is disabled in your php.ini. Get your host to enable it';
}

$xml = function_exists('simplexml_load_file') ? '<b style="color: darkgreen;">Yes</b>' : '<b style="color: darkred;">No</b>';
if (!function_exists('simplexml_load_file')) {
    $canRun = false;
}

$mbString = function_exists('mb_convert_encoding') ? '<b style="color: darkgreen;">Yes</b>' : '<b style="color: darkred;">No</b>';
if (!function_exists('mb_convert_encoding')) {
    $extra .= '<br>You will not be able to successfully edit the frontpage/news because you are missing <b>mbstring</b>';
}

$pdo = class_exists('PDO') ? '<b style="color: darkgreen;">Yes</b>' : '<b style="color: darkred;">No</b>';
if (!class_exists('PDO')) {
    $extra .= '<br>You will not be able to connect to the database because you are missing <b>PDO</b>';
    $canRun = false;
}

$w = stream_get_wrappers();
$https_wrapper = in_array('https', $w, true) ? '<b style="color: darkgreen;">Yes</b>' : '<b style="color: darkred;">No</b>';
if (!in_array('https', $w, true)) {
    $extra .= '<br>The <b>HTTPS wrapper</b> is not enabled in your php.ini configuration. You will not be able to sign in';
    $canRun = false;
}

$openssl = extension_loaded('openssl') ? '<b style="color: darkgreen;">Yes</b>' : '<b style="color: darkred;">No</b>';
if (!extension_loaded('openssl')) {
    $extra .= '<br>The <b>OpenSSL</b> is not loaded. You will not be able to sign in';
    $canRun = false;
}

if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc() === 1) {
    $extra .= '<br>Your PHP version is so old that it still supports <b>magic quotes</b>, please upgrade!';
    $canRun = false;
}

if (!$canRun) {
    $runMsg = '<b style="color: darkred;">Your PHP installation is NOT able to run Prometheus, because:</b>';
}

$html = '
		<!DOCTYPE html>
		<html>
		<head>
			<title>Prometheus checker</title>

			<style type="text/css">
				body {
					background: #1d1d1d;
					color: gray;
					text-align: center;
					padding-top: 80px;
				}
			</style>
		</head>
			<body>
				'.$php.'<br>
				Curl: '.$curl.'<br>
				PHP-XML: '.$xml.'<br>
				MBString: '.$mbString.'<br>
				PDO: '.$pdo.'<br>
				url_allow_fopen: '.$fopen.'<br>
				OpenSSL: '.$openssl.'<br>
				HTTPS wrapper: '.$https_wrapper.'<br>
				<br>
				'.$runMsg.'
				'.$extra.'
			</body>
		</html>
	';

if (!$canRun) {
    die($html);
}

/**
 * Page stuff
 */

if (!isset($page)) {
    $page = '';
}

if ($page === 'inc') {
    $spl = '';
    $spl_root = '../';
} elseif ($page === 'ajax') {
    $spl = '../';
    $spl_root = '../../';
} elseif ($page === 'mods') {
    $spl = '../inc/';
    $spl_root = '../';
} else {
    $spl = 'inc/';
    $spl_root = '';
}

// check if config does not exist, then attempt to copy it
if (!file_exists($spl_root.'config.php')) {
    @copy($spl_root.'config.example.php', $spl_root.'config.php');
}

// if it still does not exist, tell the user to copy it manually
if (!file_exists($spl_root.'config.php')) {
    die('<strong>No config file found!</strong><br><br>Please rename config.example.php to config.php and configure it before continuing.');
}

include $spl_root.'config.php';

// unlink this malicious file that unfortunately got uploaded to some Prometheus installations due to a breach
// on my update server
if (file_exists($spl_root.'buystats.php')) {
    @unlink($spl_root.'buystats.php');
}

if (!isset($steam_api)) {
    $steam_api = null;
}

if (isset($lang)) {
    $language = $lang;
}

if (!isset($dir)) {
    $dir = 'ltr';
}

if (isset($enable_cache)) {
    $use_cache = $enable_cache;
}

if (!isset($using_ssl)) {
    $using_ssl = false;
}

require $spl_root.'vendor/autoload.php';

global $devmode;

if ($devmode) {
    $whoops = new \Whoops\Run;
    $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
    $whoops->register();
}

// CACHING
require_once $spl_root.'cache/phpfastcache.php';
phpFastCache::setup("storage", "auto");
phpFastCache::setup("path", dirname(__FILE__, 2).DIRECTORY_SEPARATOR.'cache');
$cache = phpFastCache();

if (!isset($depends)) {
    $depends = [];
}

if (in_array('paymentwall', $depends, true)) {
    require_once $spl.'lib/paymentwall.php';
}

if (in_array('teamspeak', $depends, true)) {
    require_once $spl.'lib/TeamSpeak3/TeamSpeak3.php';
}

require_once $spl.'lib/SourceQuery/SourceQuery.class.php';
define('SQ_TIMEOUT', 1);
define('SQ_ENGINE', SourceQuery::SOURCE);

/**
 * Pointshop, darkrp money,
 * ^ Must not be updateable
 */
$updateable_actions = [
    "weapons",
    "rank",
];

/**
 * @param $class_name
 */
function autoload_classes($class_name)
{
    global $spl;

    $file = $spl.'classes/'.$class_name.'.class.php';

    if (file_exists($file)) {
        require_once $file;
    }
}

spl_autoload_register('autoload_classes');

global $db_host, $db_user, $db_pass, $db_database, $db_port;
$db = new db($db_host, $db_user, $db_pass, $db_database, $db_port);
$message = new FlashMessages();

if (isset($_COOKIE['prometheus_language']) && !empty($_COOKIE['prometheus_language'])
    && !getSetting('disable_language_selector', 'value2')) {
    $language = $_COOKIE['prometheus_language'];
}

/**
 * Check if already logged in
 */

if (!prometheus::loggedIn()) {
    require_once $spl.'classes/steamLogin.class.php';
}

if (!isset($enableCookies)) {
    $enableCookies = false;
}

if (!prometheus::loggedIn()
    && $enableCookies
    && $page !== 'api'
    && $page !== 'logout'
    && $page !== 'install'
    && isset($_COOKIE['uid'], $_COOKIE['token'])) {
    $db_token = $db->getOne("SELECT session_token FROM players WHERE uid = ?", $_COOKIE['uid']);

    if ($_COOKIE['token'] === $db_token) {
        $_SESSION['uid'] = $_COOKIE['uid'];

        util::redirect('.');
    } else {
        util::redirect('logout.php?csrf_token='.csrf_token());
    }
}

/**
 * Check mod configs
 */
mods::load();

/**
 * Language
 */
include_once $spl_root.'lang/en-gb.php';

if ($language !== 'swedish-chef' && $language !== 'norwegian-chef') {
    include_once $spl_root.'lang/'.$language.".php";
}

function lang($str, $fallback = null, $extra = [])
{
    global $lang, $language, $db, $english_lang;

    if ($language === 'swedish-chef') {
        return 'Bork';
    }

    if ($language === 'norwegian-chef') {
        return 'Børk';
    }

    if (isset($lang[$str])) {
        $str = $lang[$str];

        if (prometheus::loggedIn()) {
            $name = $db->getOne("SELECT name FROM players WHERE uid = ?", $_SESSION['uid']);
            $str = str_replace("%u%", $name, $str);
        }

        $ret = $str;
    } else {
        $ret = $english_lang[$str] ?? $fallback;
    }

    if (count($extra) !== 0) {
        preg_match_all('/[$][\d]*/', $ret, $matches);

        $count = 0;
        foreach ($matches[0] as $match) {
            $ret = str_replace($match, $extra[$count], $ret);

            $count++;
        }
    }

    return $ret;
}

function display($string)
{
    return $string;
}

function checkboxArrayStrip($array)
{
    if (!isset($array)) {
        $properjson = '[]';
    } else {
        $array = json_encode($array);

        $array = str_replace(array(':"on"', '{'), array('', '['), $array);
        $properjson = str_replace('}', ']', $array);
    }

    return $properjson;
}

function loggedin(): bool
{
    if (isset($_SESSION['uid'])) {
        return true;
    }

    return false;
}

/*
 * Get domain URL
 */
function getUrl($p = null): ?string
{
    if ($p === null) {
        if (isset($_SERVER['HTTP_HOST'])) {
            $page_url = $_SERVER['HTTP_HOST'];
        } elseif (isset($_SERVER['SERVER_NAME'])) {
            $page_url = $_SERVER['SERVER_NAME'];
        } else {
            $page_url = '';
        }

        $info = parse_url($page_url);

        $page_url = $info['path'] ?? $info['host'];

        if ($page_url === "localhost") {
            return $page_url;
        }

        $page_url = explode('.', $page_url);
        $page_url = array_reverse($page_url);
        return "$page_url[1].$page_url[0]";
    }

    if ($p === 'full') {
        return $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'];
    }

    return null;
}

/**
 * Check if folder or file is writeable
 */
function is__writable($path): bool
{
    if ($path[strlen($path) - 1] === '/') {
        return is__writable($path.uniqid(mt_rand(), true).'.tmp');
    }

    if (file_exists($path)) {
        if (!($f = @fopen($path, 'rb+'))) {
            return false;
        }

        fclose($f);
        return true;
    }

    if (!($f = @fopen($path, 'wb'))) {
        return false;
    }

    fclose($f);
    unlink($path);
    return true;
}

function recursiveDelete($str)
{
    if (is_file($str)) {
        return @unlink($str);
    }

    if (is_dir($str)) {
        $scan = glob(rtrim($str, '/').'/*');
        foreach ($scan as $path) {
            recursiveDelete($path);
        }
        return @rmdir($str);
    }
}

function convertCommunityIdToSteamId($communityId)
{
    $steamId1 = substr($communityId, -1) % 2;
    $steamId2a = (int) substr($communityId, 0, 4) - 7656;
    $steamId2b = substr($communityId, 4) - 1197960265728;
    $steamId2b -= $steamId1;

    return "STEAM_0:$steamId1:".(($steamId2a + $steamId2b) / 2);
}

function convertSteamIdToCommunityId($id)
{
    if (0 === strpos($id, "STEAM_")) {
        $parts = explode(':', $id);
        return bcadd(bcadd(bcmul($parts[2], '2'), '76561197960265728'), $parts[1]);
    }

    if (is_numeric($id) && strlen($id) < 16) {
        return bcadd($id, '76561197960265728');
    }

    return $id;
}

function generateUniqueId($maxLength = null)
{
    $entropy = '';

    if (function_exists('openssl_random_pseudo_bytes')) {
        $entropy = openssl_random_pseudo_bytes(64, $strong);
        if ($strong !== true) {
            $entropy = '';
        }
    }

    $entropy .= uniqid(mt_rand(), true);

    if (class_exists('COM')) {
        try {
            $com = new COM('CAPICOM.Utilities.1');
            $entropy .= base64_decode($com->GetRandom(64, 0));
        } catch (Exception $ex) {
        }
    }

    if (@is_readable('/dev/urandom')) {
        $h = fopen('/dev/urandom', 'rb');
        $entropy .= fread($h, 64);
        fclose($h);
    }

    $hash = hash('whirlpool', $entropy);
    if ($maxLength) {
        return substr($hash, 0, $maxLength);
    }
    return $hash;
}

function file_get_contents_curl($url)
{
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

    $data = curl_exec($ch);
    curl_close($ch);

    return $data;
}

// OTHER 518360635
class tos
{
    public static function agree(): void
    {
        global $db, $UID;

        $date = new DateTime();
        $date = $date->format('Y-m-d H:i:s');

        $db->execute("UPDATE players SET tos_lastread = ? WHERE uid = ?", [
            $date, $UID
        ]);

        cache::clear();
    }

    public static function getLast()
    {
        global $db, $UID;

        if (!getSetting('disable_tos', 'value2')) {
            return $db->getOne("SELECT tos_lastread FROM players WHERE uid = ?", $UID);
        }

        return (new DateTime())->format('Y-m-d H:i:s');
    }
}

// NON ORGANISED
function getFeatured($p): string
{
    global $db;

    if ($p === '') {
        $id = getSetting('featured_package', 'value2');
        $pkg = $db->getAll("SELECT * FROM packages WHERE id = ?", [$id]);
        $ret = '';

        foreach ($pkg as $row) {
            $id = $row['id'];
            $servers = $row['servers'];

            $server = json_decode($servers, true);

            if (isset($server[0])) {
                $server = $server[0];

                $img = $row['img'];

                $title = $row['title'];

                if ($img === null) {
                    $img = '';
                } else {
                    $img = '<img src="'.$img.'" />';
                }

                $ret .= '
                    <div class="featured-pkg">
                        <div class="featured-header">
                            '.lang("featured_pkg", "Featured package").'
                        </div>
                        <div class="store-box-header">
                            '.$title.'
                        </div>
                        '.$img.'
                        <form method="POST">
                            <a class="buy-btn" href="store.php?page=packages&id='.$server.'">'.lang("find_more",
                        "Find out more!").'</a>
                        </form>
                    </div>
                ';
            }
        }
    }

    return $ret;
}

function toCur($from, $to, $t = null, $e = null, $uid = null)
{
    /*
    $url = 'http://api.fixer.io/latest?base=' . $from . '&symbols=' . $to;

    $content = file_get_contents_curl($url);
    $content = json_decode($content, true);

    if (isset($content['rates'])) {
        $ret = $content['rates'][$to];
    } else {
        $ret = '';
    }

    $ret = dashboard::getCurrency($from, $t, $e, $uid) * $ret;

    return $ret;
    */

    return 1;
}

function convertCur($from, $from_amt, $to)
{
    /*
    $url = 'http://api.fixer.io/latest?base=' . $from . '&symbols=' . $to;

    $ret = cache::get($url);

    if ($ret == null) {
        $content = file_get_contents_curl($url);
        $content = json_decode($content, true);

        if (isset($content['rates'])) {
            $ret = $content['rates'][$to];
        } else {
            $ret = '';
        }

        cache::set($url, $ret, '1d');
    }

    return $from_amt * $ret;
    */

    return $from_amt * 1;
}

function getUsers(): string
{
    global $db;

    if (isset($_GET['q'])) {
        $q = ltrim(rtrim($_GET['q'], ' '), ' ');

        if (is_numeric($q) && strlen($q) === 17) {
            $res = $db->getAll("SELECT * FROM players WHERE uid LIKE ? ORDER BY id ASC LIMIT ".dashboard::getMax('users').",10",
                [$q]);
        } elseif (strpos($q, 'STEAM_0:') !== false) {
            $s = convertSteamIdToCommunityId($q);

            $res = $db->getAll("SELECT * FROM players WHERE uid LIKE ? ORDER BY id ASC LIMIT ".dashboard::getMax('users').",10",
                [$s]);
        } else {
            $res = $db->getAll("SELECT * FROM players WHERE name LIKE ? ORDER BY id ASC LIMIT ".dashboard::getMax('users').",10",
                ['%'.$q.'%']);
        }
    } else {
        $res = $db->getAll("SELECT * FROM players ORDER BY id ASC LIMIT ".dashboard::getMax('users').",10");
    }

    $ret = '';
    if ($res) {
        foreach ($res as $row) {
            $id = $row['id'];
            $p_uid = $row['uid'];
            $admin = $row['admin'];
            $name = htmlspecialchars($row['name']);

            $disabled = '';

            $admin = '
                <a href="admin.php?page=users&action=permissions&id='.$id.'" class="btn btn-prom '.$disabled.'">'.lang("permissions").'</a>
            ';

            $ret .= '
                <form method="POST">
                    <input type="hidden" value="'.$p_uid.'" name="hidden">
                    <tr>
                        <td>'.$id.'</td>
                        <td>'.$name.'</td>
                        <td>
                            <span class="ids">
                                <span class="userid">'.$p_uid.'</span>
                                <span class="steam64" style="display: none; visibility: hidden;">'.$p_uid.'</span>
                                <span class="steamid" style="display: none; visibility: hidden;">'.convertCommunityIdToSteamId($p_uid).'</span>
                            </span>
                        </td>
                        <td>'.$admin.'</td>
                        <td><a href="admin.php?page=users&action=profile&id='.$id.'" class="btn btn-prom">'.lang("view_profile",
                    "View profile").'</a> <a href="admin.php?page=users&action=packages&id='.$id.'" class="btn btn-prom">'.lang("manage",
                    "Mange").'</a></td>
                    </tr>
                </form>
             ';
        }
    } else {
        $ret = '
            <tr>
                <td>'.lang("no_users", "No users found").'</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        ';
    }

    return $ret;
}

function getPurchaseLog(): string
{
    global $db;

    $res = $db->getAll("SELECT * FROM transactions ORDER BY id DESC LIMIT ".dashboard::getMax('log').",10");
    $ret = '';

    if ($res !== null) {
        foreach ($res as $row) {
            $id = $row['id'];
            $p_uid = $row['uid'];
            $package = $row['package'];
            $credit_package = $row['credit_package'];
            $raffle_package = $row['raffle_package'];
            $price = $row['price'];
            $cur = $row['currency'];
            $timestamp = $row['timestamp'];
            $credits = $row['credits'];
            $gateway = $row['gateway'];
            $userid = $db->getOne("SELECT id FROM players WHERE uid = ?", $p_uid);

            $package_name = 'None';
            $type = 'Unknown';

            if ($gateway === null) {
                $gateway = 'Unspecified';
            }

            if ($package !== null) {
                $type = 'Package';
                $package_name = $db->getOne("SELECT title FROM packages WHERE id = ?", $package);
            }
            if ($credit_package !== null) {
                $type = 'Credits';
                $package_name = $db->getOne("SELECT title FROM credit_packages WHERE id = ?", $credit_package);
            }
            if ($raffle_package !== null) {
                $type = 'Raffle';
                $package_name = $db->getOne("SELECT title FROM raffles WHERE id = ?", $raffle_package);
            }

            $name = htmlspecialchars($db->getOne("SELECT name FROM players WHERE uid = ?", $p_uid));

            if ($price !== null) {
                $price_block = $price.' '.$cur;
            } elseif ($credits !== null) {
                $price_block = $credits.' '.lang('credits', 'Credits');
            }

            $actions = '
                <form method="POST">
                    <input type="hidden" value="'.csrf_token().'" name="csrf_token">
                    <input type="hidden" value="'.$id.'" name="hidden">
                    <a href="admin.php?page=users&action=profile&id='.$userid.'" class="btn btn-prom">'.lang("view_profile",
                    "View profile").'</a>
                    <button class="btn btn-danger" name="transaction_delete" type="submit">'.lang("del", "Del").'</button>
                </form>
            ';

            if (empty($name)) {
                $name = 'Unnamed';
            }

            $ret .= '
                <form method="POST">
                    <input type="hidden" value="'.$p_uid.'" name="hidden">
                    <tr>
                        <td>'.$id.'</td>
                        <td>'.$name.'</td>
                        <td>'.$package_name.'</td>
                        <td>'.$price_block.'</td>
                        <td>'.$type.'</td>
                        <td>'.$gateway.'</td>
                        <td>'.$timestamp.'</td>
                        <td>'.$actions.'</td>
                    </tr>
                </form>
             ';
        }
    } else {
        $ret = '
            <tr>
                <td>There are no purchases yet</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        ';
    }

    return $ret;
}

function getPurchasedPackages($p): string
{
    global $db;

    $res = $db->getAll("SELECT max(a.id) as id, a.timestamp, a.package, a.active, a.delivered, a.server, a.transaction 
        FROM actions a 
        LEFT OUTER JOIN transactions t ON (t.uid = a.uid AND t.package = a.package AND t.credit_package IS NULL AND t.package != 0)
        WHERE a.uid = ?
        GROUP BY a.package, a.active, a.delivered, a.server, a.timestamp, a.transaction", $p);

    $ret = '';
    $timestamps = [];

    if ($res !== null) {
        foreach ($res as $pack) {
            if (!in_array($pack['timestamp'], $timestamps)) {
                $title = $db->getOne("SELECT title FROM packages WHERE id = ?", [$pack['package']])['title'];

                $active = (int) $pack['active'];
                $delivered = (int) $pack['delivered'];

                $del = '';
                if ($delivered && $active === 0) {
                    $del = '<input type="submit" value="Del" class="btn btn-danger" name="action_del">';
                }

                if ($active) {
                    $astring = '<input type="submit" value="Active" class="btn btn-success" name="toggle_active">';
                } else {
                    $astring = '<input type="submit" value="Inactive" class="btn btn-prom" name="toggle_inactive">';
                }

                if ($delivered) {
                    $delivered = '<font color="green">Yes</font>';
                } else {
                    $delivered = '<font color="red">No</font>';
                }

                $servers = str_replace(array('[', ']', '"', ','), array('', '', '', ', '), $pack['server']);

                $ret .= '
                    <form method="POST">
                        <input type="hidden" name="hidden" value="'.$pack['id'].'">
                        <tr>
                            <td>'.$pack['id'].'</td>
                            <td>'.$pack['transaction'].'</td>
                            <td>'.$title.'</td>
                            <td>'.$servers.'</td>
                            <td>'.$delivered.'</td>
                            <td>'.$astring.' '.$del.'</td>
                        </tr>
                    </form>
                ';

                $timestamps[] = $pack['timestamp'];
            }
        }
    } else {
        $ret = '
            <td>This user has no packages</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        ';
    }

    return $ret;
}

function table_getTickets(): string
{
    global $db;

    $res = $db->getAll("SELECT * FROM tickets ORDER BY id DESC");
    $ret = '';

    if ($res !== null) {
        foreach ($res as $row) {
            $id = $row['id'];
            $uid = $row['uid'];
            $descr = $row['descr'];
            $time = $row['timestamp'];
            $seen = (int) $row['seen'];

            if ($seen === 0) {
                $style = 'style="color: #c10000;"';
            } else {
                $style = '';
            }

            $name = $db->getOne("SELECT name FROM players WHERE uid = ?", [$uid])['name'];
            $replies = $db->getAll("SELECT * FROM ticket_replies WHERE ticket_id = ?", [$id]);
            $replies = count($replies);

            $ret .= '
                <tr>
                    <td>'.$id.'</td>
                    <td>'.$name.'</td>
                    <td>'.$descr.'</td>
                    <td>'.$time.'</td>
                    <td>'.$replies.'</td>
                    <td><a href="admin.php?a=sup&view='.$id.'" class="btn btn-prom" '.$style.'>'.lang('view').'</a></td>
                </tr>
            ';
        }
    } else {
        $ret = '
            <tr>
                <td>There are no support tickets yet</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        ';
    }

    return $ret;
}

function user_getTickets(): string
{
    global $db;
    global $UID;

    $res = $db->getAll("SELECT * FROM tickets WHERE uid = ? ORDER BY id DESC", [$UID]);
    $ret = '';

    if ($res) {
        foreach ($res as $row) {
            $id = $row['id'];
            $descr = $row['descr'];
            $time = $row['timestamp'];
            $seen = (int) $row['client_seen'];

            if ($seen === 0) {
                $style = 'style="color: #c10000;"';
            } else {
                $style = '';
            }

            $replies = $db->getAll("SELECT * FROM ticket_replies WHERE ticket_id = ?", [$id]);
            $replies = count($replies);

            $ret .= '
                <tr>
                    <td>'.$id.'</td>
                    <td>'.$descr.'</td>
                    <td>'.$time.'</td>
                    <td>'.$replies.'</td>
                    <td><a href="support.php?view='.$id.'" class="btn btn-prom" '.$style.'>'.lang('view').'</a></td>
                </tr>
            ';
        }
    } else {
        $ret = '
            <tr>
                <td>'.lang('no_support', 'You have no support tickets').'</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        ';
    }

    return $ret;
}

function getUserSetting($p, $user)
{
    global $db;
    global $UID;

    if ($user === '') {
        $id = $UID;
    } else {
        $id = $user;
    }

    $res = cache::get("getUserSetting_".$id."_".$p);

    if ($res === null) {
        if ($p === 'name') {
            $res = $db->getOne("SELECT name FROM players WHERE uid = ?", $id);
        }

        if ($p === 'steam_avatar') {
            $xml = simplexml_load_file("https://steamcommunity.com/profiles/".$id."/?xml=1");

            $res = (string) $xml->avatarFull;
            $res = str_replace('http://cdn.akamai.steamstatic.com/', 'https://steamcdn-a.akamaihd.net/', $res);
        }

        cache::set("getUserSetting_".$id."_".$p, (string) $res, '1y');
    }

    if ($p === 'admin') {
        $res = $db->getOne("SELECT admin FROM players WHERE uid = ?", [$id])['admin'];
    }

    return $res;
}

function userExists($uid): bool
{
    global $db;

    $res = $db->getOne("SELECT * FROM players WHERE uid = ?", [$uid]);

    return $res !== false;
}

function steam_userExists($uid)
{
    $xml = simplexml_load_string(file_get_contents("https://steamcommunity.com/profiles/".$uid."/?xml=1"));

    return !empty($xml->steamID);
}

function getPackageHistory($p = null): string
{
    global $db;
    global $UID;

    if ($p === null) {
        $p = $UID;
    }

    $res = $db->getAll("SELECT package, timestamp, credit_package FROM transactions WHERE uid = ? AND raffle_package IS NULL ORDER BY id DESC LIMIT 10",
        [$p]);

    $ret = '';
    foreach ($res as $row) {
        $package = $row['package'];
        $timestamp = $row['timestamp'];
        $credit_package = $row['credit_package'];

        if ($credit_package === null) {
            $packs = $db->getOne("SELECT title FROM packages WHERE id = ?", $package)." (Package)";
        } else {
            $credits = $db->getOne("SELECT amount FROM credit_packages WHERE id = ?", $credit_package);
            $packs = $db->getOne("SELECT title FROM credit_packages WHERE id = ?",
                    $credit_package)."(".$credits." Credits)";
        }

        $ret .= '
            <tr>
                <td>'.$packs.'</td>
                <td>'.$timestamp.'</td>
            </tr>
        ';
    }

    if ($res === null) {
        $ret = '
            <tr>
                <td>'.lang("no_history", "No history").'</td>
                <td></td>
            </tr>
        ';
    }


    return $ret;
}

function getPermanentPackages($p = null): string
{
    global $db;
    global $UID;

    if ($p === null) {
        $p = $UID;
    }

    $res = $db->getAll("SELECT DISTINCT package, timestamp FROM actions WHERE uid = ? AND active = 1 AND expiretime = '1000-01-01 00:00:00' AND package != 0 ORDER BY timestamp DESC LIMIT 5",
        [$p]);
    $ret = '';

    foreach ($res as $pack) {
        $perm = (int) $db->getOne("SELECT permanent FROM packages WHERE id = ?", [$pack['package']])['permanent'];

        if ($perm) {
            $ret .= '<tr><td>'.$db->getOne("SELECT title FROM packages WHERE id = ?",
                    [$pack['package']])['title'].'</td></tr>';
        }
    }

    if (!$ret) {
        $ret = '<tr><td>'.lang('you_have_none', 'You have none!').'</td></tr>';
    }

    return $ret;
}

function getNonPermanentPackages($p = null): string
{
    global $db, $UID;

    if ($p === null) {
        $p = $UID;
    }

    $res = $db->getAll("
        SELECT DISTINCT a.package, a.timestamp, a.expiretime
        FROM actions a
        LEFT JOIN packages p ON(a.package = p.id)
        WHERE a.uid = ?
        AND a.active = 1
        AND a.expiretime != '1000-01-01 00:00:00'
        AND a.expiretime >= now()
        AND a.package != 0
        AND p.permanent = 0
        ORDER BY a.timestamp
        DESC LIMIT 5",
        [$p]
    );

    $ret = '';
    foreach ($res as $pack) {
        $date = new DateTime($pack['expiretime']);
        $expire = $date->format('d M Y');

        $ret .= '<tr><td>'.$db->getOne("SELECT title FROM packages WHERE id = ?",
                [$pack['package']])['title'].' [Expires: '.$expire.']</td></tr>';
    }

    if ($ret === null) {
        $ret = '<tr><td>'.lang('you_have_none', 'You have none!').'</td></tr>';
    }


    return $ret;
}

function checkbox_getPackages($p, $t = null): string
{
    global $db;

    $where = '';

    if ($p !== 0 && $p !== 'sale') {
        if ($t === 'expire') {
            $where = ' AND permanent = 0';
        }

        $res = $db->getAll("SELECT * FROM packages WHERE id != ?$where", [
            $p
        ]);

        $ret = '';

        if ($res) {
            $ret .= '<select name="'.$t.'[]" class="selectpicker" data-style="btn-prom" data-live-search="true" multiple>';

            foreach ($res as $row) {
                $id = $row['id'];
                $title = $row['title'];

                $selected = '';

                if ($t === 'comp') {
                    $comp = $db->getOne("SELECT non_compatible FROM packages WHERE id = ?", $p);

                    if ($comp) {
                        $comp = json_decode($comp, true);
                        if (in_array($id, $comp)) {
                            $selected = 'selected';
                        }
                    }
                }

                if ($t === 'hide') {
                    $hide = $db->getOne("SELECT hide FROM packages WHERE id = ?", $p);

                    if ($hide) {
                        $hide = json_decode($hide, true);
                        if (in_array($id, $hide)) {
                            $selected = 'selected';
                        }
                    }
                }

                if ($t === 'upgrade') {
                    $upgradeable = $db->getOne("SELECT upgradeable FROM packages WHERE id = ?", $p);

                    if ($upgradeable) {
                        $upgradeable = json_decode($upgradeable, true);
                        if (in_array($id, $upgradeable)) {
                            $selected = 'selected';
                        }
                    }
                }

                if ($t === 'disable') {
                    $disable = $db->getOne("SELECT bought_disable FROM packages WHERE id = ?", $p);

                    if ($disable) {
                        $disable = json_decode($disable, true);
                        if (in_array($id, $disable)) {
                            $selected = 'selected';
                        }
                    }
                }

                if ($t === 'expire') {
                    $expire = $db->getOne("SELECT expire_linked FROM packages WHERE id = ?", $p);
                    $expire = json_decode($expire, true);

                    if ($expire) {
                        if (in_array($id, $expire)) {
                            $selected = 'selected';
                        }
                    }
                }

                $ret .= '
                    <option value="'.$id.'" '.$selected.'>'.$id.' - '.$title.'</option>
				';
            }

            $ret .= '</select>';
        } else {
            $ret = '<p style="padding-top: 8px;">'.lang('no_other_available', 'No other packages available').'</p>';
        }
    }

    if ($p === 0) {
        $res = $db->getAll("SELECT * FROM packages");
        $ret = '';

        if ($res) {
            $ret .= '<select name="'.$t.'[]" class="selectpicker" data-style="btn-prom" data-live-search="true" multiple>';

            foreach ($res as $row) {
                $id = $row['id'];
                $title = $row['title'];

                $ret .= '
					<option value="'.$id.'">'.$id.' - '.$title.'</option>
				';
            }

            $ret .= '</select>';
        } else {
            $ret = '<p style="padding-top: 8px;">'.lang('no_other_available', 'No other packages available').'</p>';
        }
    }

    if ($p === 'sale') {
        $res = $db->getAll("SELECT * FROM packages");
        $ret = '';

        if ($res) {
            $ret .= '<select name="packages[]" class="selectpicker" data-style="btn-prom" data-live-search="true" multiple>';

            foreach ($res as $row) {
                $id = $row['id'];
                $title = $row['title'];

                $packages = getSetting('sale_packages', 'value');
                $packages = json_decode($packages, true);

                $selected = is_array($packages) && in_array($id, $packages) ? 'selected' : '';

                $ret .= '
					<option value="'.$id.'" '.$selected.'>'.$id.' - '.$title.'</option>
				';
            }

            $ret .= '</select>';
        } else {
            $ret = '<p style="padding-top: 8px;">'.lang('no_other_available', 'No other packages available').'</p>';
        }
    }

    return $ret;
}

function checkbox_getServers($p, $id)
{
    global $db;

    if ($p === 'packages' && $id !== '') {
        $res = $db->getAll("SELECT * FROM servers");
        $ret = '';

        if ($res) {
            $ret .= '<select name="servers[]" class="selectpicker" data-style="btn-prom" data-live-search="true" multiple>';

            foreach ($res as $row) {
                $pkg = $row['id'];
                $name = $row['name'];

                $servers = $db->getOne("SELECT servers FROM packages WHERE id = ?", [$id])['servers'];
                $servers = json_decode($servers, true);
                if (in_array($pkg, $servers)) {
                    $selected = 'selected';
                } else {
                    $selected = '';
                }

                $ret .= '
                    <option value="'.$pkg.'" '.$selected.'>'.$name.'</option>
    			';
            }

            $ret .= '</select>';
        }
    }

    if ($p === 'packages' && $id === '') {
        $res = $db->getAll("SELECT * FROM servers");
        $ret = '';

        if ($res) {
            $ret .= '<select name="servers[]" class="selectpicker" data-style="btn-prom" data-live-search="true" multiple>';

            foreach ($res as $row) {
                $id = $row['id'];
                $name = $row['name'];

                $ret .= '
                    <option value="'.$id.'">'.$name.'</option>
    			';
            }

            $ret .= '</select>';
        }
    }

    return $ret;
}

function getCategories($p)
{
    global $db;

    $res = $db->getAll("SELECT * FROM categories");
    if ($res) {
        $ret = '';
        foreach ($res as $row) {
            $id = $row['id'];
            $name = $row['name'];

            if ($p !== '*') {
                $amt = (int) $db->getOne("SELECT COUNT(*) AS value FROM packages WHERE category = ? AND servers LIKE ?", [
                    $id, '%"'.$p.'"%'
                ])['value'];

                if ($amt !== 0) {
                    if (!isset($_GET['uid'])) {
                        $ret .= '
								<a href="store.php?id='.$_GET['id'].'&cat='.$id.'" class="categoryLink">'.$name.' <span>['.$amt.']</span></a>
							';
                    } else {
                        $ret .= '
								<a href="store.php?id='.$_GET['id'].'&uid='.$_GET['uid'].'&cat='.$id.'" class="categoryLink">'.$name.' <span>['.$amt.']</span></a>
							';
                    }
                }
            }

            if ($p === '*') {
                $servers = $db->getAll("SELECT * FROM servers");
                $all_servers = '[';
                foreach ($servers as $server) {
                    $s_id = $server['id'];
                    $all_servers .= '"'.$s_id.'",';
                }
                $all_servers = rtrim($all_servers, ',');
                $all_servers .= ']';

                $amt = (int) $db->getOne("SELECT COUNT(*) AS value FROM packages WHERE category = ? AND servers = ?", [
                    $id, $all_servers
                ])['value'];

                if ($amt !== 0) {
                    if (!isset($_GET['uid'])) {
                        $ret .= '
								<a href="store.php?global&cat='.$id.'" class="categoryLink">'.$name.' <span>['.$amt.']</span></a>
							';
                    } else {
                        $ret .= '
								<a href="store.php?global&uid='.$_GET['uid'].'&cat='.$id.'" class="categoryLink">'.$name.' <span>['.$amt.']</span></a>
							';
                    }
                }
            }
        }

        return $ret;
    }
}

function assignPackage($user, $pkg, $state = 1, $clone = false, $real = 0)
{
    global $db;

    $uid = $db->getOne("SELECT uid FROM players WHERE id = ?", $user);
    $currency = $db->getOne("SELECT currency FROM packages WHERE id = ?", $pkg);
    $currency = $db->getOne("SELECT cc FROM currencies WHERE id = ?", $currency);
    $price = $db->getOne("SELECT price FROM packages WHERE id = ?", $pkg);

    if ($state == 1) {
        $p_array = [
            "id" => $pkg,
            "trans_id" => 0,
            "uid" => $uid,
            "type" => 1,
            "clone" => $clone,
        ];
        addAction($p_array);
    }

    if (!$real) {
        $name = 'Assigned by Admin';
        $email = 'Assigned by Admin';
        $txn_id = 'Assigned by Admin';
    } else {
        $name = $db->getOne("SELECT name FROM players WHERE id = ?", $user);
        $email = 'placeholder@domain.tld'; // Just a "real" but fake email
        $txn_id = generateUniqueId(12);
    }

    $db->execute("INSERT INTO transactions SET name = ?, email = ?, uid = ?, package = ?, currency = ?, price = ?, txn_id = ?",
        [
            $name, $email, $uid, $pkg, $currency, $price, $txn_id,
        ]);

    cache::clear('purchase', $uid);
}

function getEditPackage($id, $val)
{
    global $db;

    if ($val === 'curname') {
        return $db->getOne("SELECT c.cc FROM currencies c JOIN settings s ON (s.value2 = c.id) WHERE s.name = 'dashboard_main_cc'");
    }

    $ret = false;

    static $cache = [];

    if (!empty($id)) {
        if (isset($cache[$id])) {
            $row = $cache[$id];
        } else {
            $row = $db->getOne("SELECT * FROM packages WHERE id = ?", $id);
            $cache[$id] = $row;
        }

        $title = $row['title'];
        $labels = $row['labels'];
        $descr = $row['lower_text'];
        $cur = $row['currency'];
        $price = $row['price'];
        $permanent = $row['permanent'];
        $rebuyable = $row['rebuyable'];
        $days = $row['days'];
        $enabled = $row['enabled'];
        $credits = $row['credits'];
        $img = $row['img'];
        $custom_price = $row['custom_price'];
        $custom_price_min = $row['custom_price_min'];
        $subscription = $row['subscription'];
        $no_owned = $row['no_owned'];
        $order_id = $row['order_id'];
        $alternative_pp = $row['alternative_paypal'];
        $once = $row['once'];

        $cur_name = $db->getOne("SELECT cc FROM currencies WHERE id = ?", $cur);

        $labels_a = json_decode($labels, true);
        $labels_count = count($labels_a);

        if ($val === 'title') {
            $ret = $title;
        }
        if ($val === 'price') {
            $ret = $price;
        }
        if ($val === 'credits') {
            $ret = $credits;
        }
        if ($val === 'desc') {
            $ret = $descr;
        }
        if ($val === 'labels_count') {
            $ret = $labels_count;
        }
        if ($val === 'labels') {
            $ret = $labels_a;
        }
        if ($val === 'permanent') {
            $ret = $permanent;
        }
        if ($val === 'rebuyable') {
            $ret = $rebuyable;
        }
        if ($val === 'days') {
            $ret = $days;
        }
        if ($val === 'enabled') {
            $ret = $enabled;
        }
        if ($val === 'imageurl') {
            $ret = str_replace('\\', '/', $img);
        }
        if ($val === 'custom_price') {
            $ret = $custom_price;
        }
        if ($val === 'custom_price_min') {
            $ret = $custom_price_min;
        }
        if ($val === 'subscription') {
            $ret = $subscription;
        }
        if ($val === 'no_owned') {
            $ret = $no_owned;
        }
        if ($val === 'order_id') {
            $ret = $order_id;
        }
        if ($val === 'curname') {
            $ret = $cur_name;
        }
        if ($val === 'alternative_pp') {
            $ret = $alternative_pp;
        }
        if ($val === 'once') {
            $ret = $once;
        }
    }

    return $ret;
}

function getServerName($id)
{
    global $db;

    $ret = htmlspecialchars($db->getOne("SELECT name FROM servers WHERE id = ?", $id));
    return $ret;
}

function getEditServer($id, $val)
{
    global $db;

    if ($id !== '') {
        $ret = $db->getOne("SELECT $val FROM servers WHERE id = ?", $id);
    } else {
        $ret = false;
    }

    return $ret;
}

function getEditCategory($id, $val)
{
    global $db;

    if ($id !== '') {
        $ret = $db->getOne("SELECT $val FROM categories WHERE id = ?", $id);
    } else {
        $ret = false;
    }

    return $ret;
}

function getEditCurrency($id, $val)
{
    global $db;

    if ($id !== '') {
        $ret = $db->getOne("SELECT $val FROM currencies WHERE id = ?", $id);
    } else {
        $ret = false;
    }

    return $ret;
}

function findSetting($settings, $find, $val)
{
    foreach ($settings as $key => $value) {
        if ($settings[$key]['name'] === $find) {
            return $settings[$key][$val];
        }
    }
    return false;
}

/**
 * @param $val
 * @param $p
 * @return array|bool|null|string
 */
function getSetting($val, $p)
{
    global $db;

    $settings = $db->getAll("SELECT * FROM settings");

    $settings = findSetting($settings, $val, $p);

    if ($p === 'value2' && $settings !== null) {
        $settings = (int) $settings;
    }

    return $settings;
}

/**
 * @param $val
 * @param $p
 * @param $var
 * @param  bool  $strict
 */
function setSetting($val, $p, $var, bool $strict = true)
{
    global $db;

    if ((prometheus::loggedIn()
            && prometheus::isAdmin())
        || !getSetting('installed', 'value2')
        || $p === 'actions_lastupdated'
        || !$strict) {
        if (getSetting('installed', 'value2')) {
            cache::clear('settings');
        }

        $db->execute(
            "
            INSERT INTO settings (name, $var)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE $var = ?
        ",
            [$p, $val, $val]
        );
    }
}

function bought_disable($uid, $pid)
{
    global $db;

    $disable = $db->getOne("SELECT bought_disable FROM packages WHERE id = ?", $pid);

    if ($disable !== '[]' && $disable !== null) {
        $disable = json_decode($disable, true);

        foreach ($disable as $id) {
            $db->execute("UPDATE actions SET active = 0, delivered = 0 WHERE package = ? AND uid = ?", [
                $id, $uid,
            ]);
        }
    }
}

/*
if !TEAM_%JOBNAME_UPPER% then
TEAM_%JOBNAME_UPPER% = DarkRP.createJob("%JOBNAME%", {
color = Color(%RGB%,255),
model = {%MODELS%},
description = [[ %DESCRIPTION% ]],
weapons = {%WEAPONS%},
command = "%CHATCMD%",
max = %MAX%,
salary = %SALARY%,
admin = 0,
vote = false,
hasLicense = %LICENSE%,
customCheck = function(ply) return %CUSTOM_CHECK% end,
CustomCheckFailMsg = "This is a custom donator job. You are not authorised to join this job",
PlayerSetModel = function(ply) return "%MODEL%" end,
category = "Donator Jobs"
})
end
 */

/**
 * @param $p
 * @throws Exception
 */
function addAction($p)
{
    global $db, $updateable_actions;

    /*

    $p['id'] = package id
    $p['trans_id'] = trans id
    $p['uid'] = player uid
    $p['type'] = 1(normal), 2(credits)
    $p['clone'] = clone timestamp of latest package of same time, true/false
    $p['extra'] = like raffle_winner

     */

    if (!isset($p['clone'])) {
        $p['clone'] = false;
    }

    if (!isset($p['extra'])) {
        $p['extra'] = '';
    }

    $timestamp = date('Y-m-d H:i:s');

    if ($p['type'] == 1) {
        $package = $db->getOne("SELECT actions, permanent, servers, title FROM packages WHERE id = ?", $p['id']);
        $actions = $package['actions'];
        $perm = (int) $package['permanent'];
        $servers = $package['servers'];
        $title = $package['title'];
        $expire = '1000-01-01 00:00:00';

        $actions_a = json_decode($actions, true);
        $servers = str_replace('{', '[', $servers);
        $servers = str_replace('}', ']', $servers);

        // Disable packages which are set to be disabled upon buying this package, if there is any
        bought_disable($p['uid'], $p['id']);

        // Check for runtype 1 or 2
        $runonce = 1;

        foreach ($actions_a as $value) {
            if (isset($value['runtype']) && $value['runtype'] == 1 or isset($value['runtype']) && $value['runtype'] == 2) {
                $runonce = 0;
                break;
            }
        }

        // Update expiration date and deactivate old package if actions are 100% similar and a package is active.
        if ($perm == 0) {
            $days = $db->getOne("SELECT days FROM packages WHERE id = ?", $p['id']);
            $date = new DateTime();

            // Do magic shit to add new days onto current package if exists(stack it)
            $alreadyExists = $db->getOne("SELECT a.package, a.expiretime FROM actions a LEFT JOIN packages p ON(a.package = p.id)
                WHERE (a.package = ? OR p.expire_linked LIKE ?) AND a.uid = ? AND a.active = 1 AND a.expiretime != '1000-01-01 00:00:00' GROUP BY a.package, a.expiretime ORDER BY a.expiretime DESC LIMIT 1",
                [
                    $p['id'],
                    '%"'.$p['id'].'"%',
                    $p['uid'],
                ]);

            if ($alreadyExists && !$p['clone']) {
                $expires = $alreadyExists['expiretime'];

                $expiredate = new DateTime($expires);

                $interval = new DateInterval('P'.$days.'D');
                $expiredate->add($interval);
                $expire = $expiredate->format('Y-m-d 00:00:00');

                $db->execute("UPDATE actions SET active = 0, delivered = 1 WHERE package = ? AND uid = ? AND active = 1 ORDER BY id DESC",
                    [
                        $alreadyExists['package'], $p['uid']
                    ]);
            } else {
                $interval = new DateInterval('P'.$days.'D');
                $date->add($interval);
                $expire = $date->format('Y-m-d 00:00:00');
            }
        }

        if ($perm == 0 && $p['clone']) {
            $cloneExpire = $db->getOne("SELECT expiretime FROM actions WHERE package = ? AND uid = ? ORDER BY id DESC",
                [
                    $p['id'], $p['uid'],
                ]);

            if ($cloneExpire !== false) {
                $expire = $cloneExpire['expiretime'];
            }
        }

        /**
         * Add Prometheus.Temp.MoneySpent to custom_code
         * Add Prometheus.Temp.CashSpent to custom_code
         * Add Prometheus.Temp.CreditsSpent to custom_code
         */
        if (isset($actions_a['custom_action'])) {
            $moneySpent = $db->getOne("SELECT price as cash, credits FROM transactions WHERE package = ? AND uid = ? ORDER BY id DESC",
                [$p['id'], $p['uid']]);

            $cashSpent = (float) $moneySpent['cash'];
            $creditsSpent = (float) $moneySpent['credits'];
            $moneySpent = $cashSpent + $creditsSpent;

            if ($actions_a['custom_action']['code_when'] != '') {
                $actions_a['custom_action']['code_when'] = 'Prometheus.Temp.MoneySpent = '.$moneySpent.chr(13).chr(10).' '.$actions_a['custom_action']['code_when'];
                $actions_a['custom_action']['code_when'] = 'Prometheus.Temp.CashSpent = '.$cashSpent.chr(13).chr(10).' '.$actions_a['custom_action']['code_when'];
                $actions_a['custom_action']['code_when'] = 'Prometheus.Temp.CreditsSpent = '.$creditsSpent.chr(13).chr(10).' '.$actions_a['custom_action']['code_when'];
            }

            if ($actions_a['custom_action']['code_after'] != '') {
                $actions_a['custom_action']['code_after'] = 'Prometheus.Temp.MoneySpent = '.$moneySpent.chr(13).chr(10).' '.$actions_a['custom_action']['code_after'];
                $actions_a['custom_action']['code_after'] = 'Prometheus.Temp.CashSpent = '.$cashSpent.chr(13).chr(10).' '.$actions_a['custom_action']['code_after'];
                $actions_a['custom_action']['code_after'] = 'Prometheus.Temp.CreditsSpent = '.$creditsSpent.chr(13).chr(10).' '.$actions_a['custom_action']['code_after'];
            }

            /**
             * MoneyEverSpent
             */

            $moneyEverSpent = $db->getOne("SELECT sum(price) AS value FROM transactions WHERE (package IS NOT NULL OR credit_package IS NOT NULL) AND uid = ?",
                $p['uid']);

            if ($moneyEverSpent == '' or $moneyEverSpent == null or !isset($moneyEverSpent)) {
                $moneyEverSpent = 0;
            }

            if ($actions_a['custom_action']['code_when'] != '') {
                $actions_a['custom_action']['code_when'] = 'Prometheus.Temp.MoneyEverSpent = '.$moneyEverSpent.chr(13).chr(10).' '.$actions_a['custom_action']['code_when'];
            }

            if ($actions_a['custom_action']['code_after'] != '') {
                $actions_a['custom_action']['code_after'] = 'Prometheus.Temp.MoneyEverSpent = '.$moneyEverSpent.chr(13).chr(10).' '.$actions_a['custom_action']['code_after'];
            }
        }

        /**
         * Split runtype 3
         */
        if (isset($actions_a['custom_action']) && $actions_a['custom_action']['runtype'] == 3 or isset($actions_a['console']) && $actions_a['console']['runtype'] == 3) {
            $runtype3_servers = json_decode($servers, true);

            $runtype3_console = [];
            $runtype3_custom = [];

            if (isset($actions_a['custom_action']) && $actions_a['custom_action']['runtype'] == 3) {
                $runtype3_custom = ["custom_action" => $actions_a['custom_action']];

                unset($actions_a['custom_action']);
            }

            if (isset($actions_a['console']) && $actions_a['console']['runtype'] == 3) {
                $runtype3_console = ["console" => $actions_a['console']];

                unset($actions_a['console']);
            }

            $runtype_array = $runtype3_console + $runtype3_custom;
            $runtype_actions = json_encode($runtype_array);

            if ($perm == 1) {
                foreach ($runtype3_servers as $server) {
                    $server = [$server];
                    $server = json_encode($server);

                    $db->execute(
                        "INSERT INTO actions SET transaction = ?, uid = ?, actions = ?, server = ?, package = ?, runonce = ?, timestamp = ?",
                        [$p['trans_id'], $p['uid'], $runtype_actions, $server, $p['id'], 2, $timestamp]
                    );
                }
            } else {
                foreach ($runtype3_servers as $server) {
                    $server = [$server];
                    $server = json_encode($server);

                    $db->execute(
                        "INSERT INTO actions SET transaction = ?, uid = ?, actions = ?, server = ?, expiretime = ?, package = ?, runonce = ?, timestamp = ?",
                        [$p['trans_id'], $p['uid'], $runtype_actions, $server, $expire, $p['id'], 2, $timestamp]
                    );
                }
            }
        }

        /**
         * Split out any updateable_actions if set
         */
        $updateable_a = [];

        foreach ($updateable_actions as $action) {
            if (isset($actions_a[$action])) {
                $updateable_a[$action] = $actions_a[$action];

                unset($actions_a[$action]);
            }
        }

        if (count($updateable_a) !== 0) {
            $updateable = 1;

            $updateable_a = json_encode($updateable_a);
            $updateable_servers = json_decode($servers, true);

            foreach ($updateable_servers as $server) {
                $server = [$server];
                $server = json_encode($server);

                if ($perm) {
                    $db->execute(
                        "INSERT INTO actions SET transaction = ?, uid = ?, actions = ?, server = ?, package = ?, runonce = ?, updateable = ?, timestamp = ?",
                        [$p['trans_id'], $p['uid'], $updateable_a, $server, $p['id'], $runonce, $updateable, $timestamp]
                    );
                } else {
                    $db->execute(
                        "INSERT INTO actions SET transaction = ?, uid = ?, actions = ?, server = ?, expiretime = ?, package = ?, runonce = ?, updateable = ?, timestamp = ?",
                        [
                            $p['trans_id'], $p['uid'], $updateable_a, $server, $expire, $p['id'], $runonce, $updateable,
                            $timestamp
                        ]
                    );
                }
            }
        }

        /**
         * Split out pointshop2 from the json if mysql is set
         */
        if (isset($actions_a['pointshop2']) && $actions_a['pointshop2']['mysql'] == 1) {
            $actions_p = [
                "pointshop2" => $actions_a['pointshop2'],
            ];

            $actions_p = json_encode($actions_p);

            if ($perm) {
                $db->execute(
                    "INSERT INTO actions SET transaction = ?, uid = ?, actions = ?, server = ?, package = ?, timestamp = ?",
                    [$p['trans_id'], $p['uid'], $actions_p, $servers, $p['id'], $timestamp]
                );
            } else {
                $db->execute(
                    "INSERT INTO actions SET transaction = ?, uid = ?, actions = ?, server = ?, expiretime = ?, package = ?, timestamp = ?",
                    [$p['trans_id'], $p['uid'], $actions_p, $servers, $expire, $p['id'], $timestamp]
                );
            }

            unset($actions_a['pointshop2']);
        }

        /**
         * Split out pointshop1 from the json if mysql is set
         */
        if (isset($actions_a['pointshop1']) && $actions_a['pointshop1']['mysql'] == 1) {
            $actions_p = [
                "pointshop1" => $actions_a['pointshop1'],
            ];

            $actions_p = json_encode($actions_p);

            if ($perm) {
                $db->execute(
                    "INSERT INTO actions SET transaction = ?, uid = ?, actions = ?, server = ?, package = ?, timestamp = ?",
                    [$p['trans_id'], $p['uid'], $actions_p, $servers, $p['id'], $timestamp]
                );
            } else {
                $db->execute(
                    "INSERT INTO actions SET transaction = ?, uid = ?, actions = ?, server = ?, expiretime = ?, package = ?, timestamp = ?",
                    [$p['trans_id'], $p['uid'], $actions_p, $servers, $expire, $p['id'], $timestamp]
                );
            }

            unset($actions_a['pointshop1']);
        }

        /**
         * Split out teamspeak from the json if set
         */
        if (isset($actions_a['teamspeak'])) {
            $actions_p = [
                "teamspeak" => $actions_a['teamspeak'],
            ];
            $actions_p = json_encode($actions_p);

            if ($perm) {
                $db->execute(
                    "INSERT INTO actions SET transaction = ?, uid = ?, actions = ?, server = ?, package = ?, timestamp = ?",
                    [$p['trans_id'], $p['uid'], $actions_p, $servers, $p['id'], $timestamp]
                );
            } else {
                $db->execute(
                    "INSERT INTO actions SET transaction = ?, uid = ?, actions = ?, server = ?, expiretime = ?, package = ?, timestamp = ?",
                    [$p['trans_id'], $p['uid'], $actions_p, $servers, $expire, $p['id'], $timestamp]
                );
            }

            unset($actions_a['teamspeak']);
        }

        /**
         * Split out sourcemod
         */

        if (isset($actions_a['sourcemod'])) {
            $fg = $actions_a['sourcemod']['fg'];

            $sm_servers = json_decode($servers, true);

            $name = $db->getOne("SELECT name FROM players WHERE uid = ?", $p['uid']);
            $steam32 = convertCommunityIdToSteamId($p['uid']);

            $moneySpent = $db->getOne("SELECT price FROM transactions WHERE package = ? AND uid = ?", [
                $p['id'], $p['uid'],
            ])['price'];

            if ($moneySpent == '' || $moneySpent === null or !isset($moneySpent)) {
                $moneySpent = 0;
            }

            $unix_expires = 0;
            if ($perm !== 1) {
                $unix_expires = strtotime($expire);
            }

            $package_name = $db->getOne("SELECT title FROM packages WHERE id = ?", $p['id']);

            $cmd = 'sm_prometheus "'.$name.'" "'.$steam32.'" "'.$moneySpent.'" "'.$unix_expires.'" "'.$fg.'" "'.$package_name.'"';

            foreach ($sm_servers as $server) {
                $serverInfo = $db->getAll("SELECT ip, port, rcon, game FROM servers WHERE id = ?", $server);
                $serverInfo = $serverInfo[0];

                if ($serverInfo['game'] != 'gmod') {
                    $ip = $serverInfo['ip'];
                    $port = $serverInfo['port'];
                    $rcon = $serverInfo['rcon'];

                    $q = new SourceQuery();
                    try {
                        $q->Connect($ip, $port, SQ_TIMEOUT, SQ_ENGINE);

                        $q->SetRconPassword($rcon);
                        $q->Rcon($cmd);
                    } catch (Exception $e) {
                        //die($e->getMessage());
                    }

                    $q->Disconnect();
                }
            }

            unset($actions_a['sourcemod']);
        }

        /**
         * Split out custom job if the user won a raffle
         */
        if (isset($actions_a['customjob']) && $p['extra'] == 'raffle_winner') {
            $job_action = [
                "customjob" => [
                    "raffle" => true,
                    "pid" => $p['id'],
                ],
            ];

            $job_action = json_encode($job_action);

            $db->execute(
                "INSERT INTO actions SET transaction = ?, uid = ?, actions = ?, server = ?, package = ?, runonce = ?, expiretime = '1000-01-01 00:00:00', timestamp = ?",
                [$p['trans_id'], $p['uid'], $job_action, '', $p['id'], 0, $timestamp]
            );

            unset($actions_a['customjob']);
        }

        /**
         * Split out custom job
         */
        if (isset($actions_a['customjob']) && $p['extra'] != 'raffle_winner') {
            if (actions::delivered('customjob', $p['uid']) == $p['id']) {
                $db->execute("UPDATE actions SET active = 0 WHERE package = ? AND server = ''", $p['id']);
            }

            $jobcode = '
                if !TEAM_%JOBNAME_UPPER% then
                    TEAM_%JOBNAME_UPPER% = DarkRP.createJob("%JOBNAME%", {
                        color = Color(%RGB%,255),
                        model = {%MODELS%},
                        description = [[ %DESCRIPTION% ]],
                        weapons = {%WEAPONS%},
                        command = "%CHATCMD%",
                        max = %MAX%,
                        salary = %SALARY%,
                        admin = 0,
                        vote = false,
                        hasLicense = %LICENSE%,
                        customCheck = function(ply) return %CUSTOM_CHECK% end,
                        CustomCheckFailMsg = "This is a custom donator job. You are not authorised to join this job",
                        category = "Donator Jobs"
                    })
                end
            ';

            $customjob_servers = json_decode($servers, true);

            $pre = prepurchase::hasPre($p['buyer_id'], 'customjob');
            $json = prepurchase::getJson($pre);
            $array = json_decode($json, true);

            if ($array['friends'] == '') {
                $friends = [];
                $max = 1;
            } else {
                $friends = explode(',', $array['friends']);
                $max = count($friends) + 1;
            }

            $upper_name = str_replace(' ', '_', strtoupper($array['name']));
            $upper_name = $p['uid'].'_'.$upper_name;

            $jobcode = str_replace('%JOBNAME_UPPER%', $upper_name, $jobcode);
            $jobcode = str_replace('%JOBNAME%', $array['name'], $jobcode);
            $jobcode = str_replace('%DESCRIPTION%', $array['desc'], $jobcode);
            $jobcode = str_replace('%RGB%', $array['colour'], $jobcode);
            $jobcode = str_replace('%CHATCMD%', $array['cmd'], $jobcode);
            $jobcode = str_replace('%SALARY%', $array['salary'], $jobcode);
            $jobcode = str_replace('%MAX%', $max, $jobcode);

            $license = $array['license'] == 1 ? 'true' : 'false';
            $jobcode = str_replace('%LICENSE%', $license, $jobcode);

            if ($array['weapons'] != '') {
                $weaponsA = explode(',', $array['weapons']);
                $weapons = '';

                if (is_numeric($weaponsA[0])) {
                    $WList = actions::get($p['id'], 'customjob', 'weapons_list');
                    $WList = json_decode($WList, true);

                    foreach ($weaponsA as $key => $wep) {
                        $weapons .= '"'.$WList[$wep].'",';
                    }

                    $weapons = rtrim($weapons, ',');
                } else {
                    $weapons = $array['weapons'];
                    $WExplode = explode(',', $weapons);

                    if (count($WExplode) > 2) {
                        $weapons = '';
                        foreach ($WExplode as $weapon) {
                            $weapons .= '"'.$weapon.'",';
                        }

                        $weapons = rtrim($weapons, ',');
                    } else {
                        $weapons = '"'.$weapons.'"';
                    }
                }
            } else {
                $weapons = '';
            }

            $jobcode = str_replace('%WEAPONS%', $weapons, $jobcode);
            $firstModel = '';

            if ($array['models'] != '') {
                $modelsA = explode(',', $array['models']);
                $models = '';

                if (is_numeric($modelsA[0])) {
                    $MList = actions::get($p['id'], 'customjob', 'models_model');
                    $MList = json_decode($MList, true);

                    foreach ($modelsA as $key => $model) {
                        $firstModel = $MList[$model];
                        $models .= '"'.$MList[$model].'",';
                    }
                } else {
                    $models = $array['models'];

                    $MExplode = explode(',', $models);

                    if (count($MExplode) > 2) {
                        $models = '';
                        foreach ($MExplode as $model) {
                            $firstModel = $model;
                            $models .= '"'.$model.'",';
                        }

                        $models = rtrim($models, ',');
                    } else {
                        $firstModel = $models[0];
                        $models = '"'.$models.'"';
                    }
                }
            } else {
                $models = '';
            }

            $models = rtrim($models, ',');

            $jobcode = str_replace('%MODELS%', $models, $jobcode);
            $jobcode = str_replace('%MODEL%', $firstModel, $jobcode);

            $steamid = convertCommunityIdToSteamId($p['uid']);
            $custom_check = 'ply:SteamID() == "'.$steamid.'"';

            foreach ($friends as $friend) {
                $custom_check .= ' or ply:SteamID() == "'.$friend.'"';
            }

            $jobcode = str_replace('%CUSTOM_CHECK%', $custom_check, $jobcode);

            if (getSetting('disable_customjob', 'value2') == 0) {
                $jobcode_un = str_replace("\t", "", $jobcode);

                $jobcode_un = rtrim($jobcode_un, "\n");
                $jobcode_un = ltrim($jobcode_un, "\n");

                $job_action = [
                    "customjob" => [
                        "code" => $jobcode_un,
                        "code_end" => "
                            if TEAM_".$upper_name." then if SERVER && Prometheus.Temp.Ply:Team() == TEAM_".$upper_name." then Prometheus.Temp.Ply:changeTeam(GAMEMODE.DefaultTeam, true) end DarkRP.removeJob(TEAM_".$upper_name.") TEAM_".$upper_name." = nil end
                        ",
                        "runtype" => "1",
                    ],
                ];

                $job_action = json_encode($job_action);

                if ($perm == 1) {
                    $expire = '1000-01-01 00:00:00';
                }

                foreach ($customjob_servers as $server) {
                    $server = [$server];
                    $server = json_encode($server);

                    $db->execute(
                        "INSERT INTO actions SET transaction = ?, uid = ?, actions = ?, server = ?, package = ?, runonce = ?, expiretime = ?, timestamp = ?",
                        [$p['trans_id'], $p['uid'], $job_action, $server, $p['id'], 0, $expire, $timestamp]
                    );

                    foreach ($friends as $friend) {
                        $friend_uid = convertSteamIdToCommunityId($friend);

                        $db->execute(
                            "INSERT INTO actions SET transaction = ?, uid = ?, actions = ?, server = ?, package = ?, runonce = ?, expiretime = ?, timestamp = ?",
                            [$p['trans_id'], $friend_uid, $job_action, $server, $p['id'], 0, $expire, $timestamp]
                        );
                    }
                }
            }

            prepurchase::setFinished($pre, $jobcode);

            unset($actions_a['customjob']);
        }

        $message_array = [
            "message" => [
                "text" => $title,
                "type" => 0,
            ],
        ];
        $message_action = json_encode($message_array);

        $servers = json_decode($servers, true);
        $actions = json_encode($actions_a);

        if ($perm == 1) {
            foreach ($servers as $server) {
                $server = [$server];
                $server = json_encode($server);

                if ($p['extra'] != 'raffle_winner') {
                    $db->execute(
                        "INSERT INTO actions SET transaction = ?, uid = ?, actions = ?, server = ?, package = ?, timestamp = ?",
                        [$p['trans_id'], $p['uid'], $message_action, $server, $p['id'], $timestamp]
                    );
                }

                // Normal actions
                if ($actions != '[]') {
                    $db->execute(
                        "INSERT INTO actions SET transaction = ?, uid = ?, actions = ?, server = ?, package = ?, runonce = ?, timestamp = ?",
                        [$p['trans_id'], $p['uid'], $actions, $server, $p['id'], $runonce, $timestamp]
                    );
                }
            }
        }

        if ($perm == 0) {
            foreach ($servers as $server) {
                $server = [$server];
                $server = json_encode($server);

                // Message action
                if ($p['extra'] != 'raffle_winner') {
                    $db->execute(
                        "INSERT INTO actions SET transaction = ?, uid = ?, actions = ?, server = ?, expiretime = ?, package = ?, timestamp = ?",
                        [$p['trans_id'], $p['uid'], $message_action, $server, $expire, $p['id'], $timestamp]
                    );
                }

                // Normal actions
                if ($actions != '[]') {
                    $db->execute(
                        "INSERT INTO actions SET transaction = ?, uid = ?, actions = ?, server = ?, expiretime = ?, package = ?, runonce = ?, timestamp = ?",
                        [$p['trans_id'], $p['uid'], $actions, $server, $expire, $p['id'], $runonce, $timestamp]
                    );
                }
            }
        }
    }

    if ($p['type'] == 2) {
        $message_array = [
            "message" => [
                "text" => $p['amount'],
                "type" => 2,
            ],
        ];

        $message_action = json_encode($message_array);

        $db->execute(
            "INSERT INTO actions SET transaction = ?, uid = ?, actions = ?, server = ?, package = ?, timestamp = ?",
            [$p['trans_id'], $p['uid'], $message_action, '["0"]', $p['id'], $timestamp]
        );
    }

    if ($p['type'] == 3) {
        $message_array = [
            "message" => [
                "text" => $p['text'],
                "type" => 3,
            ],
        ];

        $message_action = json_encode($message_array);

        $db->execute(
            "INSERT INTO actions SET transaction = ?, uid = ?, actions = ?, server = ?, package = ?, timestamp = ?",
            [$p['trans_id'], $p['uid'], $message_action, '["0"]', $p['id'], $timestamp]
        );
    }

    setSetting($timestamp, 'actions_lastupdated', 'value3');
    cache::clear();
}

function addTransaction($p)
{
    global $db;

    $currency = $db->getOne("SELECT currency FROM packages WHERE id = ?", [$p['package']])['currency'];
    $currency = $db->getOne("SELECT cc FROM currencies WHERE id = ?", [$currency])['cc'];
    $price = $db->getOne("SELECT price FROM packages WHERE id = ?", [$p['package']])['price'];
    $title = $db->getOne("SELECT title FROM packages WHERE id = ?", [$p['package']])['title'];

    $expire = $p['expire'];

    if ($expire != '00/00/0000') {
        $date = DateTime::createFromFormat('m/d/Y', $p['expire']);
        $expire = $date->format('Y-m-d H:i:s');
    } else {
        $expire = '1000-01-01 00:00:00';
    }

    $p_array = [
        "id" => $p['package'],
        "trans_id" => 0,
        "uid" => $p['uid'],
        "expire" => $expire,
        "type" => 1,
    ];
    addAction($p_array);

    $db->execute(
        "INSERT INTO transactions SET name = ?, email = ?, uid = ?, package = ?, currency = ?, price = ?, txn_id = ?",
        ['Added by system', 'Added by system', $p["uid"], $p["package"], $currency, $price, 'Assigned by system']
    );
}

class rows
{
    public static function isEmpty()
    {
        global $db;

        $res = (int) $db->getOne("SELECT count(*) AS value FROM players")['value'];

        return $res === 0;
    }
}

class page
{
    public static function get($p, $e = false)
    {
        global $db;

        $ret = null;

        if ($p == 'frontpage') {
            $ret = cache::get('frontpage');
        }

        if ($ret == null) {
            if ($e) {
                $ret = $db->getOne("SELECT content FROM pages WHERE page = ?", $p);
            } else {
                $ret = display($db->getOne("SELECT content FROM pages WHERE page = ?", $p));
            }

            if ($p == 'frontpage') {
                cache::set('frontpage', $ret, '1y');
            }
        }

        return $ret;
    }

    public static function edit($p, $t)
    {
        global $db;

        $t = new parser($t);
        $t = $t->parseHtml();

        $db->execute("UPDATE pages SET content = ? WHERE page = ?", [$t, $p]);
        cache::clear('frontpage');
    }
}

function isBlacklisted($uid)
{
    global $db;

    $steam64 = $db->getAll("SELECT * FROM blacklist WHERE steam64 = ?", $uid);
    $steamid = $db->getAll("SELECT * FROM blacklist WHERE steamid = ?", $uid);

    if ($steam64 or $steamid) {
        return true;
    } else {
        return false;
    }
}

function create_zip($files = [], $destination = '', $overwrite = false)
{
    if (file_exists($destination) && !$overwrite) {
        return false;
    }

    $valid_files = [];

    if (is_array($files)) {
        foreach ($files as $file) {
            if (file_exists($file)) {
                $valid_files[] = $file;
            }
        }
    }

    if (count($valid_files)) {
        $zip = new ZipArchive();
        if ($zip->open($destination, $overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
            return false;
        }

        foreach ($valid_files as $file) {
            $zip->addFile($file, $file);
        }

        $zip->close();

        return file_exists($destination);
    } else {
        return false;
    }
}

function timeStrToInt($str)
{
    $times = 1;

    preg_match('/[a-zA-Z]/', $str, $actualString);
    $actualString = $actualString[0];

    preg_match('/[0-9]+/', $str, $actualNum);
    $actualNum = $actualNum[0];

    if ($actualString == 'h') {
        $times = 3600;
    } elseif ($actualString == 'd') {
        $times = 86400;
    } elseif ($actualString == 'w') {
        $times = 604800;
    } elseif ($actualString == 'm') {
        $times = 2592000;
    } elseif ($actualString == 'y') {
        $times = 31536000;
    }

    return $actualNum * $times;
}

function formatJson($json)
{
    if (!is_string($json)) {
        if (phpversion() && phpversion() >= 5.4) {
            return json_encode($json, JSON_PRETTY_PRINT);
        }

        $json = json_encode($json);
    }

    $result = '';
    $position = 0;
    $strCount = strlen($json);
    $strIndent = "\t";
    $newLine = "\n";
    $prevChar = '';
    $noQuotes = true;

    for ($i = 0; $i < $strCount; $i++) {
        $dupLength = strcspn($json, $noQuotes ? " \t\r\n\",:[{}]" : "\\\"", $i);

        if ($dupLength >= 1) {
            $copyStr = substr($json, $i, $dupLength);
            $prevChar = '';
            $result .= $copyStr;
            $i += $dupLength - 1;
            continue;
        }

        $char = substr($json, $i, 1);

        if (!$noQuotes && $prevChar === '\\') {
            $result .= $char;
            $prevChar = '';
            continue;
        }

        if ($char === '"' && $prevChar !== '\\') {
            $noQuotes = !$noQuotes;
        } elseif ($noQuotes && ($char === '}' || $char === ']')) {
            $result .= $newLine;
            $position--;
            for ($j = 0; $j < $position; $j++) {
                $result .= $strIndent;
            }
        } elseif ($noQuotes && false !== strpos(" \t\r\n", $char)) {
            continue;
        }

        $result .= $char;

        if ($noQuotes && $char === ':') {
            $result .= ' ';
        } elseif ($noQuotes && ($char === ',' || $char === '{' || $char === '[')) {
            $result .= $newLine;
            if ($char === '{' || $char === '[') {
                $position++;
            }

            for ($j = 0; $j < $position; $j++) {
                $result .= $strIndent;
            }
        }

        $prevChar = $char;
    }

    return $result;
}

/**
 * @param  array  $args
 */
function dd(...$args)
{
    die(var_dump(...$args));
}

/**
 * @param  string  $path
 *
 * @return string
 */
function url($path = '')
{
    return rtrim((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ?
                "https" : "http")."://".$_SERVER['SERVER_NAME'].dirname($_SERVER['PHP_SELF']), '/').$path;
}

/**
 * @return bool
 */
function csrf_check()
{
    return isset($_SESSION['csrf_token'])
        && isset($_REQUEST['csrf_token'])
        && ($_SESSION['csrf_token'] === $_REQUEST['csrf_token']);
}

/**
 * @return mixed|string
 */
function csrf_token()
{
    if (isset($_SESSION['csrf_token'])) {
        return $_SESSION['csrf_token'];
    }

    $token = base64_encode(randomString(32));
    $_SESSION['csrf_token'] = $token;

    return $token;
}

/**
 * @param $length
 * @return string
 */
function randomString($length)
{
    $seed = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijqlmnopqrtsuvwxyz0123456789';
    $max = strlen($seed) - 1;
    $string = '';

    for ($i = 0; $i < $length; ++$i) {
        $string .= $seed[mt_rand(0.0, $max)];
    }

    return $string;
}
