<?php

declare(strict_types=1);

$htmlFile = __DIR__ . "/index.html";
$html = is_file($htmlFile) ? (string) file_get_contents($htmlFile) : "";

function caua_logged_in_cookie(): bool
{
    foreach ($_COOKIE as $name => $value) {
        if (strpos((string) $name, "wordpress_logged_in_") === 0) {
            return true;
        }
    }
    return false;
}

function caua_admin_bar_assets(): array
{
    global $wp_admin_bar;

    if (!function_exists("is_user_logged_in") || !is_user_logged_in() || !function_exists("wp_admin_bar_render")) {
        return ["", ""];
    }

    add_filter("show_admin_bar", "__return_true", 1000);
    if (!is_object($wp_admin_bar) && function_exists("_wp_admin_bar_init")) {
        _wp_admin_bar_init();
    }
    if (!is_object($wp_admin_bar)) {
        return ["", ""];
    }

    $css = function_exists("includes_url") ? includes_url("css/admin-bar.min.css") : "";
    $icons = function_exists("includes_url") ? includes_url("css/dashicons.min.css") : "";
    $script = function_exists("includes_url") ? includes_url("js/admin-bar.min.js") : "";
    $head = '<style>html{margin-top:32px!important}@media screen and (max-width:782px){html{margin-top:46px!important}}</style>';
    if ($css !== "") {
        $head .= '<link rel="stylesheet" href="' . esc_url($css) . '" />';
    }
    if ($icons !== "") {
        $head .= '<link rel="stylesheet" href="' . esc_url($icons) . '" />';
    }

    ob_start();
    wp_admin_bar_render();
    $bar = (string) ob_get_clean();
    if ($script !== "") {
        $bar .= '<script src="' . esc_url($script) . '"></script>';
    }

    return [$head, $bar];
}

$wp = __DIR__ . "/wp-load.php";
if ($html !== "" && caua_logged_in_cookie() && is_file($wp)) {
    try {
        require_once $wp;
        [$head, $bar] = caua_admin_bar_assets();
        if ($bar !== "") {
            $html = preg_replace("/\\boverflow-x-clip\\b\\s*/", "", $html, 2) ?? $html;
            $html = preg_replace("/<body([^>]*class=\")/", "<body$1admin-bar ", $html, 1) ?? $html;
            $html = str_replace("</head>", $head . "</head>", $html);
            $html = str_replace("</body>", $bar . "</body>", $html);
        }
    } catch (Throwable $error) {
        // A página do Cauã continua no ar se a barra do WordPress falhar.
    }
}

header("Content-Type: text/html; charset=UTF-8");
echo $html;
