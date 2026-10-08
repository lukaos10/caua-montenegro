<?php

declare(strict_types=1);

$htmlFile = __DIR__ . "/index.html";
$html = is_file($htmlFile) ? (string) file_get_contents($htmlFile) : "";

function caua_logged_in_cookie(): bool
{
    foreach ($_COOKIE as $name => $value) {
        if (preg_match("/^wordpress_(?:logged_in|sec)_/", (string) $name) === 1) {
            return true;
        }
    }
    return false;
}

function caua_admin_bar_markup(): string
{
    global $wp_admin_bar;

    if (!function_exists("is_user_logged_in") || !is_user_logged_in()) {
        return "";
    }

    add_filter("show_admin_bar", "__return_true", PHP_INT_MAX);
    if (function_exists("show_admin_bar")) {
        show_admin_bar(true);
    }

    if (!is_object($wp_admin_bar)) {
        if (!class_exists("WP_Admin_Bar")) {
            require_once ABSPATH . WPINC . "/class-wp-admin-bar.php";
        }
        $wp_admin_bar = new WP_Admin_Bar();
        $wp_admin_bar->initialize();
        $wp_admin_bar->add_menus();
    }

    if (!did_action("admin_bar_menu")) {
        do_action_ref_array("admin_bar_menu", [&$wp_admin_bar]);
    }

    ob_start();
    $wp_admin_bar->render();
    return (string) ob_get_clean();
}

$wp = __DIR__ . "/wp-load.php";
$loggedIn = caua_logged_in_cookie();
if ($loggedIn) {
    header("Cache-Control: private, no-store, no-cache, must-revalidate");
    header("X-LiteSpeed-Cache-Control: no-cache");
}
header("Vary: Cookie");

if ($html !== "" && $loggedIn && is_file($wp)) {
    try {
        require_once $wp;
        $bar = caua_admin_bar_markup();
        if ($bar !== "") {
            $head = '<link rel="stylesheet" href="/wp-includes/css/dashicons.min.css" />'
                . '<link rel="stylesheet" href="/wp-includes/css/admin-bar.min.css" />'
                . '<style>html{margin-top:32px!important}@media screen and (max-width:782px){html{margin-top:46px!important}}#wpadminbar{position:fixed!important;top:0;left:0;right:0;z-index:99999!important}.admin-bar header.navbar.sticky{top:32px}@media screen and (max-width:782px){.admin-bar header.navbar.sticky{top:46px}}</style>';
            $html = preg_replace("/\\boverflow-x-clip\\b\\s*/", "", $html, 2) ?? $html;
            $html = preg_replace("/<body([^>]*class=\")/", "<body$1admin-bar ", $html, 1) ?? $html;
            $html = str_replace("</head>", $head . "</head>", $html);
            $html = preg_replace("/<body([^>]*)>/", "<body$1>" . $bar, $html, 1) ?? $html;
        }
    } catch (Throwable $error) {
        // A página do Cauã continua no ar se a barra do WordPress falhar.
    }
}

header("Content-Type: text/html; charset=UTF-8");
echo $html;
