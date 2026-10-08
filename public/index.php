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

function caua_esc(string $value): string
{
    if (function_exists("esc_html")) {
        return esc_html($value);
    }
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}

function caua_simple_bar(): string
{
    $user = function_exists("wp_get_current_user") ? wp_get_current_user() : null;
    $name = $user ? (string) ($user->display_name ?: $user->user_login) : "";
    $blog = function_exists("get_bloginfo") ? (string) get_bloginfo("name") : "Cauã Montenegro";
    $admin = function_exists("admin_url") ? admin_url() : "/wp-admin/";
    $home = function_exists("home_url") ? home_url("/") : "/";
    $logout = function_exists("wp_logout_url") ? wp_logout_url($home) : "/wp-login.php?action=logout";
    $profile = function_exists("admin_url") ? admin_url("profile.php") : "/wp-admin/profile.php";
    $newPost = function_exists("admin_url") ? admin_url("post-new.php") : "/wp-admin/post-new.php";

    return '<div id="wpadminbar" class="nojq"><div class="quicklinks" id="wp-toolbar" role="navigation" aria-label="Barra de ferramentas">'
        . '<ul role="menu" id="wp-admin-bar-root-default" class="ab-top-menu">'
        . '<li role="group" id="wp-admin-bar-wp-logo"><a class="ab-item" role="menuitem" href="' . caua_esc($admin) . '"><span class="ab-icon" aria-hidden="true"></span><span class="screen-reader-text">WordPress</span></a></li>'
        . '<li role="group" id="wp-admin-bar-site-name"><a class="ab-item" role="menuitem" href="' . caua_esc($admin) . '">' . caua_esc($blog) . '</a></li>'
        . '<li role="group" id="wp-admin-bar-view-site"><a class="ab-item" role="menuitem" href="' . caua_esc($home) . '">Ver site</a></li>'
        . '<li role="group" id="wp-admin-bar-new-content"><a class="ab-item" role="menuitem" href="' . caua_esc($newPost) . '"><span class="ab-icon" aria-hidden="true"></span><span class="ab-label">Novo</span></a></li>'
        . '</ul>'
        . '<ul role="menu" id="wp-admin-bar-top-secondary" class="ab-top-secondary">'
        . '<li role="group" id="wp-admin-bar-my-account"><a class="ab-item" role="menuitem" href="' . caua_esc($profile) . '">Olá, ' . caua_esc($name) . '</a></li>'
        . '<li role="group" id="wp-admin-bar-logout"><a class="ab-item" role="menuitem" href="' . caua_esc($logout) . '">Sair</a></li>'
        . '</ul></div></div>';
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
        $bar = "";
        try {
            $bar = caua_admin_bar_markup();
        } catch (Throwable $error) {
            $bar = "";
        }
        if (strpos($bar, "wpadminbar") === false && function_exists("is_user_logged_in") && is_user_logged_in()) {
            $bar = caua_simple_bar();
        }
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
