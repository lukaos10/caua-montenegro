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

$wp = __DIR__ . "/wp-load.php";
if ($html !== "" && caua_logged_in_cookie() && is_file($wp)) {
    require_once $wp;
    if (function_exists("is_user_logged_in") && is_user_logged_in()) {
        add_filter("show_admin_bar", "__return_true");

        ob_start();
        wp_head();
        $head = (string) ob_get_clean();

        ob_start();
        wp_footer();
        $footer = (string) ob_get_clean();

        $html = preg_replace("/\\boverflow-x-clip\\b\\s*/", "", $html, 2) ?? $html;
        $html = preg_replace("/<body([^>]*class=\")/", "<body$1admin-bar ", $html, 1) ?? $html;
        $html = str_replace("</head>", $head . "</head>", $html);
        $html = str_replace("</body>", $footer . "</body>", $html);
    }
}

header("Content-Type: text/html; charset=UTF-8");
echo $html;
