<?php

class api
{
    public static function validHash($hash): bool
    {
        return $hash === getSetting('api_hash', 'value');
    }

    public static function validSteam($id): bool
    {
        return is_numeric($id) && strlen($id) === 17 or strpos($id, 'STEAM_0:') !== false;
    }

    public static function packageExists($id): bool
    {
        global $db;

        $res = (int) $db->getOne("SELECT count(*) AS value FROM packages WHERE id = ?", [$id])['value'];

        return $res === 1;
    }
}
