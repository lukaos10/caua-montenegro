<?php

declare(strict_types=1);

const CAUA_PHOTO_MAX = 30;
const CAUA_VIDEO_MAX = 20;
const CAUA_SPACE_MAX = 30;

function caua_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}

function caua_root(): string
{
    return dirname(__DIR__);
}

function caua_store_path(): string
{
    return caua_root() . "/conteudo/espacos.json";
}

function caua_example_path(): string
{
    return caua_root() . "/conteudo/espacos.exemplo.json";
}

function caua_media_dir(): string
{
    return caua_root() . "/midia";
}

function caua_new_id(): string
{
    return bin2hex(random_bytes(8));
}

function caua_youtube_id(string $url): string
{
    if (preg_match("~(?:youtu\\.be/|youtube\\.com/(?:watch\\?v=|embed/|shorts/))([A-Za-z0-9_-]{6,})~", $url, $match) === 1) {
        return $match[1];
    }
    return "";
}

function caua_vimeo_id(string $url): string
{
    if (preg_match("~vimeo\\.com/(?:video/)?(\\d+)~", $url, $match) === 1) {
        return $match[1];
    }
    return "";
}

function caua_kind_from_url(string $url, bool $image_ok = false): string
{
    if (caua_youtube_id($url) !== "") {
        return "youtube";
    }
    if (caua_vimeo_id($url) !== "") {
        return "vimeo";
    }
    if (preg_match("/\\.(mp4|webm|mov|m4v)(\\?|$)/i", $url) === 1) {
        return "video";
    }
    if ($image_ok && preg_match("/\\.(jpe?g|png|webp|gif)(\\?|$)/i", $url) === 1) {
        return "image";
    }
    return $url !== "" ? "video" : "";
}

function caua_normalize_space(mixed $space): array
{
    $space = is_array($space) ? $space : [];
    $photos = [];
    foreach (array_slice(is_array($space["photos"] ?? null) ? $space["photos"] : [], 0, CAUA_PHOTO_MAX) as $photo) {
        $src = is_array($photo) ? (string) ($photo["src"] ?? "") : "";
        $photos[] = ["src" => $src];
    }
    if ($photos === []) {
        $photos[] = ["src" => ""];
    }
    $videos = [];
    foreach (array_slice(is_array($space["videos"] ?? null) ? $space["videos"] : [], 0, CAUA_VIDEO_MAX) as $index => $video) {
        $video = is_array($video) ? $video : [];
        $src = (string) ($video["src"] ?? "");
        $kind = (string) ($video["kind"] ?? "");
        if (!in_array($kind, ["video", "youtube", "vimeo"], true)) {
            $kind = caua_kind_from_url($src);
        }
        $title = trim((string) ($video["title"] ?? ""));
        $videos[] = [
            "src" => $src,
            "kind" => $kind,
            "title" => $title !== "" ? $title : "Vídeo " . str_pad((string) ($index + 1), 2, "0", STR_PAD_LEFT),
        ];
    }
    if ($videos === []) {
        $videos[] = ["src" => "", "kind" => "", "title" => "Vídeo 01"];
    }
    $banner = is_array($space["banner"] ?? null) ? $space["banner"] : [];
    $banner_src = (string) ($banner["src"] ?? "");
    $banner_kind = (string) ($banner["kind"] ?? "");
    if (!in_array($banner_kind, ["image", "video", "youtube", "vimeo"], true)) {
        $banner_kind = caua_kind_from_url($banner_src, true);
    }
    $id = preg_replace("/[^a-zA-Z0-9_-]/", "", (string) ($space["id"] ?? "")) ?? "";
    $title = trim(strip_tags((string) ($space["title"] ?? "")));
    return [
        "id" => $id !== "" ? $id : caua_new_id(),
        "title" => $title !== "" ? (function_exists("mb_substr") ? mb_substr($title, 0, 80) : substr($title, 0, 80)) : "Novo espaço",
        "exemplo" => !empty($space["exemplo"]),
        "banner" => ["src" => $banner_src, "kind" => $banner_kind],
        "photos" => $photos,
        "videos" => $videos,
    ];
}

function caua_load_spaces(): array
{
    $path = caua_store_path();
    if (!is_file($path) && is_file(caua_example_path())) {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        copy(caua_example_path(), $path);
    }
    if (!is_file($path)) {
        return [caua_normalize_space(["title" => "Novo espaço", "exemplo" => false])];
    }
    $decoded = json_decode((string) file_get_contents($path), true);
    $list = is_array($decoded["spaces"] ?? null) ? $decoded["spaces"] : [];
    $spaces = array_map("caua_normalize_space", $list);
    return $spaces !== [] ? $spaces : [caua_normalize_space(["title" => "Novo espaço", "exemplo" => false])];
}

function caua_save_spaces(array $spaces): bool
{
    $spaces = array_slice(array_map("caua_normalize_space", array_values($spaces)), 0, CAUA_SPACE_MAX);
    $dir = dirname(caua_store_path());
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return false;
    }
    $json = json_encode(["spaces" => $spaces], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return false;
    }
    return file_put_contents(caua_store_path(), $json . "\n", LOCK_EX) !== false;
}

function caua_find_space(array $spaces, string $id): int
{
    foreach ($spaces as $index => $space) {
        if ($space["id"] === $id) {
            return (int) $index;
        }
    }
    return -1;
}

function caua_public_src(string $src): string
{
    if ($src === "" || preg_match("#^https?://#i", $src) === 1) {
        return $src;
    }
    return "../" . ltrim($src, "/");
}

function caua_upload_error(string $field): string
{
    $length = (int) ($_SERVER["CONTENT_LENGTH"] ?? 0);
    if ($length > 0 && $_POST === [] && $_FILES === []) {
        return "big";
    }
    $error = (int) ($_FILES[$field]["error"] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
        return "big";
    }
    if ($error === UPLOAD_ERR_NO_FILE) {
        return "empty";
    }
    if ($error !== UPLOAD_ERR_OK) {
        return "upload";
    }
    return "";
}

function caua_store_upload(string $field, array $extensions, array $mimes): string|array
{
    $problem = caua_upload_error($field);
    if ($problem !== "") {
        return $problem;
    }
    $file = $_FILES[$field];
    $ext = strtolower((string) pathinfo((string) $file["name"], PATHINFO_EXTENSION));
    if (!in_array($ext, $extensions, true)) {
        return "type";
    }
    $mime = "";
    if (class_exists("finfo")) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file((string) $file["tmp_name"]);
    }
    if ($mime !== "" && !in_array($mime, $mimes, true)) {
        return "type";
    }
    if (!is_dir(caua_media_dir()) && !mkdir(caua_media_dir(), 0755, true)) {
        return "upload";
    }
    $name = bin2hex(random_bytes(8)) . "." . $ext;
    $target = caua_media_dir() . "/" . $name;
    if (!move_uploaded_file((string) $file["tmp_name"], $target)) {
        return "upload";
    }
    return ["src" => "midia/" . $name, "kind" => str_starts_with($mime, "image/") || in_array($ext, ["jpg", "jpeg", "png", "webp", "gif"], true) ? "image" : "video"];
}

function caua_image_upload(string $field): string|array
{
    return caua_store_upload(
        $field,
        ["jpg", "jpeg", "png", "webp", "gif"],
        ["image/jpeg", "image/png", "image/webp", "image/gif"]
    );
}

function caua_video_upload(string $field): string|array
{
    return caua_store_upload(
        $field,
        ["mp4", "webm", "mov", "m4v"],
        ["video/mp4", "video/webm", "video/quicktime", "video/x-m4v"]
    );
}

function caua_mark_real(array $space): array
{
    $space["exemplo"] = false;
    return $space;
}
