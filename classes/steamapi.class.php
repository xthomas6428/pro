<?php

class steamapi
{
    public static function userinfo($uid, $info)
    {
        global $cache, $steam_api;

        $ret = '';
        if ($steam_api !== null) {
            $ret = $cache->get("steam_".$uid."_".$info);

            if ($ret === null) {
                $ret = file_get_contents_curl('https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key='.$steam_api.'&steamids='.$uid);
                $ret = json_decode($ret, true);

                $ret = $ret['response']['players'][0][$info];

                $cache->set("steam_".$uid."_".$info, (string) $ret, 3600 * 24);
            }
        }

        return $ret;
    }
}
