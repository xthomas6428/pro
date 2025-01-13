<?php

class prometheus
{
    public static function loggedIn(): bool
    {
        global $UID;

        return $UID !== null;
    }

    public static function isAdmin(): bool
    {
        global $db;
        global $UID;

        $admin = $db->getOne("SELECT admin FROM players WHERE uid = ?", [$UID]);

        return isset($admin['admin']) && (int) $admin['admin'];
    }

    public static function log($msg, $uid): void
    {
        global $db;

        $db->execute("INSERT INTO logs SET action = ?, uid = ?", [$msg, $uid]);
    }

    public static function updateCheck($return = false)
    {
        global $version;

        $nVersion = str_replace('.', '', $version);

        $key = (string) getSetting('api_key', 'value');
        $url = 'https://updates.nmscripts.com/prometheus/'.$key.'/'.$nVersion.'/';

        $json = cache::get("update_array");

        if ($json === null) {
            $json = file_get_contents_curl($url);

            cache::set("update_array", $json, '6h');
        }

        if ($return === false) {
            return $json;
        }

        if ($return === 'web') {
            return 'https://updates.nmscripts.com/prometheus/'.$key.'/'.$nVersion.'/web/';
        }

        if ($return === 'lua') {
            return 'https://updates.nmscripts.com/prometheus/'.$key.'/'.$nVersion.'/lua/';
        }

        return false;
    }

    public static function licenseCheck($key = null): bool
    {
        if ($key === null) {
            $key = getSetting('api_key', 'value');
        }

        $json = cache::get('licenseKey');
        if ($json === null) {
            $json = file_get_contents_curl("https://api.nmscripts.com/prometheus/valid/$key");
            $a = json_decode($json, true);
            if ($a['valid']) {
                cache::set('licenseKey', $json, '6h');
            }
        }

        $a = json_decode($json, true);

        if ($a['valid']) {
            return true;
        }

        return false;
    }
}
