<?php

$S64 = steamLogin::validate();

if ($S64 != null) {
    $_SESSION['uid'] = $S64;

    if ($S64 == 0) {
        die('Invalid Steam64 ID. Steam64 ID can\'t be 0!');
    }

    $url = "https://steamcommunity.com/profiles/" . $S64 . "/?xml=1";
    $xml = simplexml_load_file($url);

    $name = (string)$xml->steamID;
    if ($name == null || $name == '') {
        $name = steamapi::userinfo($S64, 'personaname');
    }

    $q = $db->getAll("SELECT * FROM players WHERE uid = ?", $S64);
    if ($q == null) {
        if (rows::isEmpty()) {
            $db->execute("INSERT INTO players SET uid = ?, name = ?, admin = 1, perm_group = 1, ip = ?", [$S64, $name, $_SERVER['REMOTE_ADDR']]);
        } else {
            $db->execute("INSERT INTO players SET uid = ?, name = ?, ip = ?", [$S64, $name, $_SERVER['REMOTE_ADDR']]);
        }
    } else {
        $db->execute("UPDATE players SET name = ?, ip = ? WHERE uid = ?", [
            $name, $_SERVER['REMOTE_ADDR'], $S64
        ]);
    }

    $q2 = $db->getOne("SELECT session_token FROM players WHERE uid = ?", $S64);
    if ($q2 == null) {
        $token = generateUniqueId(128);
        $db->execute("UPDATE players SET session_token = ?, ip = ? WHERE uid = ?", [$token, $_SERVER['REMOTE_ADDR'], $S64]);
    } else {
        $token = $db->getOne("SELECT session_token FROM players WHERE uid = ?", $S64);
    }

    $db->execute("UPDATE transactions SET name = ? WHERE uid = ?", [
        $name, $S64
    ]);

    if ($enableCookies) {
        setcookie('uid', $S64, time() + (86400 * 30), "/");
        setcookie('token', $token, time() + (86400 * 30), "/");
    }

    $_SESSION['uid'] = $S64;

    util::redirect('.');
}
