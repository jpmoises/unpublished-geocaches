<?php

require dirname(__DIR__) . '/config.php';

if (!array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') {
    header("HTTP/1.0 400 Bad Request");
    exit(0);
}

// Sign out
if (array_key_exists('signout', $_POST)) {
    @unlink(sprintf(COOKIE_FILENAME, md5($_SESSION['username'])));
    $_SESSION = array();
    session_destroy();
    renderAjax(array('success' => true, 'redirect' => true));
}

if (!array_key_exists('username', $_POST) || !array_key_exists('username', $_POST)) {
    header("HTTP/1.0 400 Bad Request");
    exit(0);
}

$_SESSION['cookie'] = sprintf(COOKIE_FILENAME, md5(strtolower(trim($_POST['username']))));

$hd = fopen($_SESSION['cookie'], 'w');
fclose($hd);
$postdata = array('__EVENTTARGET'      => '',
                  '__EVENTARGUMENT'    => '',
                  'ctl00$ContentBody$tbUsername'   => $_POST['username'],
                  'ctl00$ContentBody$tbPassword'   => $_POST['password'],
                  'ctl00$ContentBody$cbRememberMe' => 'On',
                  'ctl00$ContentBody$btnSignIn'    => 'Login');
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, URL_LOGIN);
curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_COOKIEJAR, $_SESSION['cookie']);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postdata));
$res = curl_exec($ch);
if (!$res) {
    renderAjax(array('success' => false, 'message' => 'Request error: ' . curl_error($ch)));
}
curl_close($ch);

if (!preg_match('/ctl00_ContentBody_lbUsername">.*<strong>(.*)<\/strong>/', $res, $username)) {
    @unlink($_SESSION['cookie']);
    renderAjax(array('success' => false, 'message' => 'Your username/password combination does not match. Make sure you entered your information correctly.'));
}

$_SESSION['username'] = trim($username[1]);

renderAjax(array('success' => true, 'username' => $_SESSION['username']));
