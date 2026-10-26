<?php
/**
 * Plugin Name: Grove Cloud
 * Description: Bezpečnostní výchozí nastavení Grove Cloudu — automatické aktualizace pluginů a šablon, vypnuté XML-RPC, skrytá verze WordPressu, žádný výčet uživatelů pro nepřihlášené, automatická aktualizace databáze po nové verzi jádra.
 * Author: Grove Tech
 */
if (!defined('ABSPATH')) {
    exit;
}

// Bezpečnostní opravy pluginů a šablon hned, ne až si někdo vzpomene.
add_filter('auto_update_plugin', '__return_true');
add_filter('auto_update_theme', '__return_true');
add_filter('auto_update_translation', '__return_true');

// XML-RPC: brute-force hesel a pingback DDoS. Grove Cloud ho blokuje i v Caddy.
add_filter('xmlrpc_enabled', '__return_false');
add_filter('wp_headers', function ($h) {
    unset($h['X-Pingback']);
    return $h;
});

// Verze WordPressu nepatří do HTML (usnadňuje hledání známých děr).
remove_action('wp_head', 'wp_generator');
add_filter('the_generator', '__return_empty_string');

// Verze jádra ani v adresách stylů a skriptů (?ver=7.1.3) — sken ji jinak
// najde i bez meta generatoru. Verze pluginů a šablon necháváme (cache).
$grove_bez_verze = function ($src) {
    $v = preg_quote((string) get_bloginfo('version'), '/');
    if (is_string($src) && $v !== '' && preg_match('/[?&]ver=' . $v . '(&|$)/', $src)) {
        return remove_query_arg('ver', $src);
    }
    return $src;
};
add_filter('style_loader_src', $grove_bez_verze, 9999);
add_filter('script_loader_src', $grove_bez_verze, 9999);

// Zobrazované jméno = přihlašovací jméno → navenek ho neukazujeme (u
// příspěvku, v oEmbed, ve feedu). Útočník by měl polovinu přihlášení zadarmo.
// Správce si může jméno kdykoli změnit v profilu („Zobrazovat jméno jako").
$grove_jmeno = function ($jmeno, $user_id = null) {
    $u = $user_id ? get_userdata($user_id) : null;
    if (!$u) {
        global $authordata;
        $u = is_object($authordata) ? $authordata : null;
    }
    if ($u && is_string($jmeno) && $jmeno !== '' && strcasecmp($jmeno, $u->user_login) === 0) {
        return strpos((string) get_locale(), 'cs') === 0 || strpos((string) get_locale(), 'sk') === 0 ? 'Redakce' : 'Editorial team';
    }
    return $jmeno;
};
add_filter('the_author', function ($jmeno) use ($grove_jmeno) {
    return $grove_jmeno($jmeno);
});
add_filter('get_the_author_display_name', function ($jmeno, $user_id) use ($grove_jmeno) {
    return is_admin() ? $jmeno : $grove_jmeno($jmeno, $user_id);
}, 10, 2);
add_filter('oembed_response_data', function ($data, $post) use ($grove_jmeno) {
    if (isset($data['author_name']) && $post) {
        $data['author_name'] = $grove_jmeno($data['author_name'], (int) $post->post_author);
    }
    return $data;
}, 10, 2);

// Výčet uživatelských jmen pro nepřihlášené (?author=1, /wp-json/wp/v2/users).
add_action('template_redirect', function () {
    if (!is_user_logged_in() && isset($_GET['author'])) {
        wp_safe_redirect(home_url('/'), 301);
        exit;
    }
});
add_filter('rest_endpoints', function ($e) {
    if (!is_user_logged_in()) {
        unset($e['/wp/v2/users'], $e['/wp/v2/users/(?P<id>[\d]+)']);
    }
    return $e;
});

// Po aktualizaci jádra (nová image od Grove Cloudu) dokončí aktualizaci
// databáze sama — jinak by administrace čekala na kliknutí „Aktualizovat
// databázi" a do té doby by se do ní nikdo nedostal. Stejné jako `wp core update-db`.
add_action('init', function () {
    global $wp_db_version;
    if (wp_installing() || (int) get_option('db_version') >= (int) $wp_db_version) {
        return;
    }
    if (get_transient('grove_db_upgrade')) {
        return;
    }
    set_transient('grove_db_upgrade', 1, 300);
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    wp_upgrade();
    delete_transient('grove_db_upgrade');
}, 1);
