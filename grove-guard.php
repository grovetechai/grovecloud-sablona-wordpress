<?php
// Grove WordPress: dokud web není nainstalovaný, instalační stránku otevře jen
// vlastník odkazem s klíčem z Grove Cloudu (časová osa appky). Bez toho by si
// nový web mohl „nainstalovat" kdokoli, kdo na adresu narazí dřív.
// Mimo Grove Cloud (bez GROVE_WP_INSTALL_KLIC) nedělá nic.
(function () {
    $klic = getenv('GROVE_WP_INSTALL_KLIC');
    if (!is_string($klic) || $klic === '') {
        return;
    }
    $skript = isset($_SERVER['SCRIPT_NAME']) ? (string) $_SERVER['SCRIPT_NAME'] : '';
    if ($skript !== '/wp-admin/install.php' && $skript !== '/wp-admin/setup-config.php') {
        return;
    }
    $dany = isset($_GET['klic']) ? $_GET['klic'] : (isset($_COOKIE['grove_install']) ? $_COOKIE['grove_install'] : '');
    if (is_string($dany) && hash_equals($klic, $dany)) {
        if (!isset($_COOKIE['grove_install'])) {
            setcookie('grove_install', $klic, array('path' => '/wp-admin/', 'secure' => true, 'httponly' => true, 'samesite' => 'Strict'));
        }
        return;
    }
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Instalaci tohoto WordPressu otevře jen vlastník webu odkazem z Grove Cloudu.\n";
    exit;
})();
