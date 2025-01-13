<?php

session_start();

$page = 'logout';
require_once('inc/functions.php');

if (!csrf_check()) {
    return util::error("Invalid CSRF token!");
}

unset($_COOKIE["uid"]);
unset($_COOKIE["token"]);
setcookie('uid', null, -1, '/');
setcookie('token', null, -1, '/');

unset($_SESSION['uid']);

session_destroy();

util::redirect('.');