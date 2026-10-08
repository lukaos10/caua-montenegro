<?php

declare(strict_types=1);

require __DIR__ . "/lib.php";

$wp = dirname(__DIR__) . "/wp-load.php";
if (is_file($wp)) {
    require_once $wp;
}

function caua_can_edit(): bool
{
    return function_exists("is_user_logged_in") && is_user_logged_in() && current_user_can("upload_files");
}

function caua_redirect(string $status = "ok", string $spot = ""): void
{
    $query = $status === "ok" ? "ok=1" : "erro=" . rawurlencode($status);
    if ($spot !== "") {
        $query .= "&spot=" . rawurlencode($spot);
    }
    header("Location: ./?" . $query);
    exit;
}

function caua_nonce_ok(): bool
{
    return function_exists("wp_verify_nonce") && wp_verify_nonce((string) ($_POST["caua_nonce"] ?? ""), "caua_espacos");
}

if (isset($_POST["caua_login"]) && function_exists("wp_signon")) {
    $user = wp_signon([
        "user_login" => sanitize_user((string) ($_POST["log"] ?? "")),
        "user_password" => (string) ($_POST["pwd"] ?? ""),
        "remember" => true,
    ], function_exists("is_ssl") ? is_ssl() : false);
    if ($user instanceof WP_User) {
        wp_set_current_user($user->ID);
        caua_redirect();
    }
    $login_error = $user instanceof WP_Error ? wp_strip_all_tags($user->get_error_message()) : "Não entrou. Confira usuário e senha.";
} else {
    $login_error = "";
}

if (caua_can_edit() && isset($_POST["caua_action"])) {
    if (!caua_nonce_ok()) {
        caua_redirect("nonce");
    }
    $action = preg_replace("/[^a-z_]/", "", (string) $_POST["caua_action"]) ?? "";
    $spaces = caua_load_spaces();
    $spot = (string) ($_POST["event_id"] ?? "");

    if ($action === "add") {
        if (count($spaces) >= CAUA_SPACE_MAX) {
            caua_redirect("limit");
        }
        $created = caua_normalize_space([
            "title" => "Espaço " . str_pad((string) (count($spaces) + 1), 2, "0", STR_PAD_LEFT),
            "exemplo" => false,
        ]);
        $spaces[] = $created;
        if (!caua_save_spaces($spaces)) {
            caua_redirect("save", $spot);
        }
        caua_redirect("ok", $created["id"]);
    }

    $index = caua_find_space($spaces, $spot);
    if ($index < 0) {
        caua_redirect("upload");
    }

    if ($action === "title") {
        $title = trim(strip_tags((string) ($_POST["event_title"] ?? "")));
        if ($title !== "") {
            $spaces[$index]["title"] = $title;
            $spaces[$index]["exemplo"] = false;
            if (!caua_save_spaces($spaces)) {
                caua_redirect("save", $spot);
            }
        }
        caua_redirect("ok", $spot);
    }

    if ($action === "delete") {
        if (count($spaces) <= 1) {
            caua_redirect("last", $spot);
        }
        array_splice($spaces, $index, 1);
        if (!caua_save_spaces($spaces)) {
            caua_redirect("save", $spot);
        }
        caua_redirect();
    }

    if ($action === "up" && $index > 0) {
        [$spaces[$index - 1], $spaces[$index]] = [$spaces[$index], $spaces[$index - 1]];
        if (!caua_save_spaces($spaces)) {
            caua_redirect("save", $spot);
        }
        caua_redirect("ok", $spot);
    }

    if ($action === "down" && $index < count($spaces) - 1) {
        [$spaces[$index + 1], $spaces[$index]] = [$spaces[$index], $spaces[$index + 1]];
        if (!caua_save_spaces($spaces)) {
            caua_redirect("save", $spot);
        }
        caua_redirect("ok", $spot);
    }

    if ($action === "banner_clear") {
        $spaces[$index]["banner"] = ["src" => "", "kind" => ""];
        $spaces[$index] = caua_mark_real($spaces[$index]);
        if (!caua_save_spaces($spaces)) {
            caua_redirect("save", $spot);
        }
        caua_redirect("ok", $spot);
    }

    if ($action === "banner_link") {
        $url = trim((string) ($_POST["banner_url"] ?? ""));
        if ($url !== "" && filter_var($url, FILTER_VALIDATE_URL) === false) {
            caua_redirect("type", $spot);
        }
        $spaces[$index]["banner"] = ["src" => $url, "kind" => caua_kind_from_url($url, true)];
        $spaces[$index] = caua_mark_real($spaces[$index]);
        if (!caua_save_spaces($spaces)) {
            caua_redirect("save", $spot);
        }
        caua_redirect("ok", $spot);
    }

    if ($action === "banner_upload") {
        $saved = caua_store_upload(
            "banner_file",
            ["jpg", "jpeg", "png", "webp", "gif", "mp4", "webm", "mov", "m4v"],
            ["image/jpeg", "image/png", "image/webp", "image/gif", "video/mp4", "video/webm", "video/quicktime", "video/x-m4v"]
        );
        if (is_string($saved)) {
            caua_redirect($saved, $spot);
        }
        $spaces[$index]["banner"] = $saved;
        $spaces[$index] = caua_mark_real($spaces[$index]);
        if (!caua_save_spaces($spaces)) {
            caua_redirect("save", $spot);
        }
        caua_redirect("ok", $spot);
    }

    $slot = (int) ($_POST["slot_index"] ?? -1);

    if ($action === "add_photo") {
        if (count($spaces[$index]["photos"]) >= CAUA_PHOTO_MAX) {
            caua_redirect("limit", $spot);
        }
        $spaces[$index]["photos"][] = ["src" => ""];
        if (!caua_save_spaces($spaces)) {
            caua_redirect("save", $spot);
        }
        caua_redirect("ok", $spot);
    }

    if ($action === "add_video") {
        if (count($spaces[$index]["videos"]) >= CAUA_VIDEO_MAX) {
            caua_redirect("limit", $spot);
        }
        $number = count($spaces[$index]["videos"]) + 1;
        $spaces[$index]["videos"][] = [
            "src" => "",
            "kind" => "",
            "title" => "Vídeo " . str_pad((string) $number, 2, "0", STR_PAD_LEFT),
        ];
        if (!caua_save_spaces($spaces)) {
            caua_redirect("save", $spot);
        }
        caua_redirect("ok", $spot);
    }

    if ($action === "photo_upload" && isset($spaces[$index]["photos"][$slot])) {
        $saved = caua_image_upload("photo_file");
        if (is_string($saved)) {
            caua_redirect($saved, $spot);
        }
        $spaces[$index]["photos"][$slot] = ["src" => $saved["src"]];
        $spaces[$index] = caua_mark_real($spaces[$index]);
        if (!caua_save_spaces($spaces)) {
            caua_redirect("save", $spot);
        }
        caua_redirect("ok", $spot);
    }

    if ($action === "photo_clear" && isset($spaces[$index]["photos"][$slot])) {
        $spaces[$index]["photos"][$slot] = ["src" => ""];
        $spaces[$index] = caua_mark_real($spaces[$index]);
        if (!caua_save_spaces($spaces)) {
            caua_redirect("save", $spot);
        }
        caua_redirect("ok", $spot);
    }

    if ($action === "video_upload" && isset($spaces[$index]["videos"][$slot])) {
        $saved = caua_video_upload("video_file");
        if (is_string($saved)) {
            caua_redirect($saved, $spot);
        }
        $spaces[$index]["videos"][$slot]["src"] = $saved["src"];
        $spaces[$index]["videos"][$slot]["kind"] = "video";
        $spaces[$index] = caua_mark_real($spaces[$index]);
        if (!caua_save_spaces($spaces)) {
            caua_redirect("save", $spot);
        }
        caua_redirect("ok", $spot);
    }

    if ($action === "video_link" && isset($spaces[$index]["videos"][$slot])) {
        $url = trim((string) ($_POST["video_url"] ?? ""));
        if ($url !== "" && filter_var($url, FILTER_VALIDATE_URL) === false) {
            caua_redirect("type", $spot);
        }
        $spaces[$index]["videos"][$slot]["src"] = $url;
        $spaces[$index]["videos"][$slot]["kind"] = caua_kind_from_url($url);
        $spaces[$index] = caua_mark_real($spaces[$index]);
        if (!caua_save_spaces($spaces)) {
            caua_redirect("save", $spot);
        }
        caua_redirect("ok", $spot);
    }

    if ($action === "video_clear" && isset($spaces[$index]["videos"][$slot])) {
        $spaces[$index]["videos"][$slot]["src"] = "";
        $spaces[$index]["videos"][$slot]["kind"] = "";
        $spaces[$index] = caua_mark_real($spaces[$index]);
        if (!caua_save_spaces($spaces)) {
            caua_redirect("save", $spot);
        }
        caua_redirect("ok", $spot);
    }

    caua_redirect("upload", $spot);
}

$can_edit = caua_can_edit();
$spaces = $can_edit ? caua_load_spaces() : [];
$css_files = glob(dirname(__DIR__) . "/assets/index-*.css") ?: [];
$css = $css_files !== [] ? "../assets/" . basename($css_files[0]) : "";

function caua_hidden(string $id = ""): void
{
    if (function_exists("wp_nonce_field")) {
        wp_nonce_field("caua_espacos", "caua_nonce");
    }
    if ($id !== "") {
        echo '<input type="hidden" name="event_id" value="' . caua_h($id) . '" />';
    }
}

function caua_preview(string $src, string $kind): string
{
    $url = caua_public_src($src);
    if ($url === "") {
        return '<div class="flex h-36 items-center justify-center rounded-box bg-base-300 text-sm text-base-content/60">Vazio</div>';
    }
    if ($kind === "youtube") {
        $id = caua_youtube_id($src);
        if ($id !== "") {
            $url = "https://i.ytimg.com/vi/" . rawurlencode($id) . "/hqdefault.jpg";
        }
    }
    if ($kind === "image" || $kind === "youtube") {
        return '<img class="h-36 w-full rounded-box object-cover" src="' . caua_h($url) . '" alt="" />';
    }
    return '<video class="h-36 w-full rounded-box bg-base-300 object-cover" src="' . caua_h($url) . '" muted playsinline preload="metadata"></video>';
}

function caua_error_text(string $error): string
{
    return match ($error) {
        "type" => "Esse arquivo não serve. Foto: JPG, PNG ou WEBP. Vídeo: MP4, WEBM ou MOV.",
        "empty" => "Escolha um arquivo antes de enviar.",
        "limit" => "Chegou no limite deste espaço.",
        "last" => "Deixe pelo menos um espaço.",
        "big" => "O arquivo é grande demais para a hospedagem. Foto: diminua. Vídeo grande: cole o link do YouTube ou Vimeo.",
        "nonce" => "A página ficou aberta tempo demais. Atualize e envie de novo.",
        "save" => "Não deu para salvar. A pasta conteudo precisa permitir gravação.",
        default => "Não deu para enviar. Tente um arquivo menor ou o link do YouTube.",
    };
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="cinema">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex,nofollow" />
    <title>Painel — Cauã Montenegro</title>
    <link rel="icon" type="image/svg+xml" href="../favicon.svg" />
    <?php if ($css !== "") : ?>
      <link rel="stylesheet" href="<?php echo caua_h($css); ?>" />
    <?php endif; ?>
  </head>
  <body class="bg-base-100 font-sans text-base-content antialiased">
    <header class="navbar border-b border-base-300 bg-base-200 px-4 md:px-8">
      <div class="navbar-start flex-col items-start">
        <p class="text-xs tracking-[0.32em] text-base-content/70 uppercase">Painel</p>
        <h1 class="text-lg font-medium tracking-tight">Cauã Montenegro</h1>
      </div>
      <div class="navbar-end gap-2">
        <a class="btn btn-sm" href="../">Ver site</a>
        <?php if ($can_edit && function_exists("wp_logout_url")) : ?>
          <a class="btn btn-sm" href="<?php echo caua_h(wp_logout_url(home_url("/admin/"))); ?>">Sair</a>
        <?php endif; ?>
      </div>
    </header>

    <main class="mx-auto flex max-w-3xl flex-col gap-6 px-4 py-8 md:px-8">
      <?php if (!function_exists("is_user_logged_in")) : ?>
        <section class="card border border-base-300 bg-base-200">
          <div class="card-body">
            <h2 class="card-title">WordPress não encontrado</h2>
            <p>O painel entra com o usuário do WordPress. Ele precisa estar na mesma pasta do site, na hospedagem.</p>
          </div>
        </section>
      <?php elseif (!$can_edit) : ?>
        <section class="card border border-base-300 bg-base-200">
          <div class="card-body gap-4">
            <h2 class="card-title">Entrar</h2>
            <p class="text-base-content/70">Use o usuário e a senha do WordPress. Depois dá para criar espaços com nome, banner, fotos e vídeos.</p>
            <form method="post" class="flex flex-col gap-4">
              <label class="flex w-full flex-col gap-2">
                <span class="text-sm">Usuário</span>
                <input class="input w-full" type="text" name="log" autocomplete="username" required />
              </label>
              <label class="flex w-full flex-col gap-2">
                <span class="text-sm">Senha</span>
                <input class="input w-full" type="password" name="pwd" autocomplete="current-password" required />
              </label>
              <button class="btn btn-primary w-fit" type="submit" name="caua_login" value="1">Entrar</button>
              <?php if ($login_error !== "") : ?>
                <p class="text-error"><?php echo caua_h($login_error); ?></p>
              <?php endif; ?>
            </form>
          </div>
        </section>
      <?php else : ?>
        <?php if (isset($_GET["ok"])) : ?>
          <p class="text-success">Salvo. Abra o site e dê Ctrl + F5.</p>
        <?php endif; ?>
        <?php if (isset($_GET["erro"])) : ?>
          <p class="text-error"><?php echo caua_h(caua_error_text((string) $_GET["erro"])); ?></p>
        <?php endif; ?>

        <section class="card border border-base-300 bg-base-200">
          <div class="card-body">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <h2 class="card-title">Espaços</h2>
              <form method="post">
                <?php caua_hidden(); ?>
                <button class="btn btn-primary btn-sm" type="submit" name="caua_action" value="add">Adicionar espaço</button>
              </form>
            </div>
            <p class="text-base-content/70">Crie quantos espaços quiser. Em cada um: nome, banner, e depois as fotos e os vídeos daquela parte. O envio começa sozinho.</p>
          </div>
        </section>

        <?php foreach ($spaces as $event_index => $event) : ?>
          <section class="card border border-base-300 bg-base-200" id="espaco-<?php echo caua_h($event["id"]); ?>">
            <div class="card-body gap-5">
              <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="card-title"><?php echo caua_h($event["title"]); ?></h2>
                <div class="flex gap-2">
                  <?php if ($event_index > 0) : ?>
                    <form method="post">
                      <?php caua_hidden($event["id"]); ?>
                      <button class="btn btn-sm" type="submit" name="caua_action" value="up">Subir</button>
                    </form>
                  <?php endif; ?>
                  <?php if ($event_index < count($spaces) - 1) : ?>
                    <form method="post">
                      <?php caua_hidden($event["id"]); ?>
                      <button class="btn btn-sm" type="submit" name="caua_action" value="down">Descer</button>
                    </form>
                  <?php endif; ?>
                </div>
              </div>

              <form method="post" class="flex flex-col gap-3">
                <?php caua_hidden($event["id"]); ?>
                <input type="hidden" name="caua_action" value="title" />
                <label class="flex w-full flex-col gap-2">
                  <span class="text-sm">Nome deste espaço</span>
                  <input class="input w-full" type="text" name="event_title" value="<?php echo caua_h($event["title"]); ?>" maxlength="80" required />
                </label>
                <button class="btn w-fit" type="submit">Salvar nome</button>
              </form>

              <div class="flex flex-col gap-3">
                <h3 class="text-lg font-medium">Banner deste espaço</h3>
                <?php echo caua_preview($event["banner"]["src"], $event["banner"]["kind"]); ?>
                <?php if ($event["banner"]["src"] !== "") : ?>
                  <p class="text-sm text-success">No site</p>
                <?php endif; ?>
                <form method="post" enctype="multipart/form-data" class="auto-upload flex flex-col gap-3">
                  <?php caua_hidden($event["id"]); ?>
                  <input type="hidden" name="caua_action" value="banner_upload" />
                  <input class="file-input w-full" type="file" name="banner_file" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime,.mp4,.webm,.mov,.m4v" required />
                  <button class="btn w-fit" type="submit"><?php echo $event["banner"]["src"] !== "" ? "Trocar banner" : "Enviar banner"; ?></button>
                </form>
                <form method="post" class="flex flex-col gap-3">
                  <?php caua_hidden($event["id"]); ?>
                  <input type="hidden" name="caua_action" value="banner_link" />
                  <label class="flex w-full flex-col gap-2">
                    <span class="text-sm">Ou cole o link do banner (YouTube / Vimeo)</span>
                    <input class="input w-full" type="url" name="banner_url" value="<?php echo caua_h($event["banner"]["kind"] !== "image" ? $event["banner"]["src"] : ""); ?>" placeholder="https://youtube.com/..." />
                  </label>
                  <button class="btn w-fit" type="submit">Usar link</button>
                </form>
                <?php if ($event["banner"]["src"] !== "") : ?>
                  <form method="post">
                    <?php caua_hidden($event["id"]); ?>
                    <button class="btn btn-sm" type="submit" name="caua_action" value="banner_clear">Tirar banner</button>
                  </form>
                <?php endif; ?>
              </div>

              <div class="flex flex-col gap-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                  <h3 class="text-lg font-medium">Fotos deste espaço</h3>
                  <form method="post">
                    <?php caua_hidden($event["id"]); ?>
                    <button class="btn btn-sm" type="submit" name="caua_action" value="add_photo">Adicionar foto</button>
                  </form>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                  <?php foreach ($event["photos"] as $slot => $photo) : ?>
                    <article class="flex flex-col gap-3 rounded-box border border-base-300 p-3">
                      <p class="text-xs tracking-[0.2em] uppercase">Foto <?php echo caua_h(str_pad((string) ($slot + 1), 2, "0", STR_PAD_LEFT)); ?></p>
                      <?php echo caua_preview($photo["src"], $photo["src"] !== "" ? "image" : ""); ?>
                      <?php if ($photo["src"] !== "") : ?><p class="text-sm text-success">No site</p><?php endif; ?>
                      <form method="post" enctype="multipart/form-data" class="auto-upload flex flex-col gap-3">
                        <?php caua_hidden($event["id"]); ?>
                        <input type="hidden" name="caua_action" value="photo_upload" />
                        <input type="hidden" name="slot_index" value="<?php echo caua_h((string) $slot); ?>" />
                        <input class="file-input w-full" type="file" name="photo_file" accept="image/jpeg,image/png,image/webp,image/gif" required />
                        <button class="btn btn-sm w-fit" type="submit"><?php echo $photo["src"] !== "" ? "Trocar arquivo" : "Enviar foto"; ?></button>
                      </form>
                      <?php if ($photo["src"] !== "") : ?>
                        <form method="post">
                          <?php caua_hidden($event["id"]); ?>
                          <input type="hidden" name="slot_index" value="<?php echo caua_h((string) $slot); ?>" />
                          <button class="btn btn-sm" type="submit" name="caua_action" value="photo_clear">Tirar desta vaga</button>
                        </form>
                      <?php endif; ?>
                    </article>
                  <?php endforeach; ?>
                </div>
              </div>

              <div class="flex flex-col gap-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                  <h3 class="text-lg font-medium">Vídeos deste espaço</h3>
                  <form method="post">
                    <?php caua_hidden($event["id"]); ?>
                    <button class="btn btn-sm" type="submit" name="caua_action" value="add_video">Adicionar vídeo</button>
                  </form>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                  <?php foreach ($event["videos"] as $slot => $video) : ?>
                    <article class="flex flex-col gap-3 rounded-box border border-base-300 p-3">
                      <p class="text-xs tracking-[0.2em] uppercase"><?php echo caua_h($video["title"]); ?></p>
                      <?php echo caua_preview($video["src"], $video["kind"]); ?>
                      <?php if ($video["src"] !== "") : ?><p class="text-sm text-success">No site</p><?php endif; ?>
                      <form method="post" enctype="multipart/form-data" class="auto-upload flex flex-col gap-3">
                        <?php caua_hidden($event["id"]); ?>
                        <input type="hidden" name="caua_action" value="video_upload" />
                        <input type="hidden" name="slot_index" value="<?php echo caua_h((string) $slot); ?>" />
                        <input class="file-input w-full" type="file" name="video_file" accept="video/mp4,video/webm,video/quicktime,.mp4,.webm,.mov,.m4v" required />
                        <button class="btn btn-sm w-fit" type="submit"><?php echo $video["src"] !== "" ? "Trocar arquivo" : "Enviar vídeo"; ?></button>
                      </form>
                      <form method="post" class="flex flex-col gap-3">
                        <?php caua_hidden($event["id"]); ?>
                        <input type="hidden" name="caua_action" value="video_link" />
                        <input type="hidden" name="slot_index" value="<?php echo caua_h((string) $slot); ?>" />
                        <label class="flex w-full flex-col gap-2">
                          <span class="text-sm">Ou cole o link (YouTube / Vimeo)</span>
                          <input class="input w-full" type="url" name="video_url" value="<?php echo caua_h($video["kind"] === "youtube" || $video["kind"] === "vimeo" ? $video["src"] : ""); ?>" placeholder="https://youtube.com/..." />
                        </label>
                        <button class="btn btn-sm w-fit" type="submit">Usar link</button>
                      </form>
                      <?php if ($video["src"] !== "") : ?>
                        <form method="post">
                          <?php caua_hidden($event["id"]); ?>
                          <input type="hidden" name="slot_index" value="<?php echo caua_h((string) $slot); ?>" />
                          <button class="btn btn-sm" type="submit" name="caua_action" value="video_clear">Tirar desta vaga</button>
                        </form>
                      <?php endif; ?>
                    </article>
                  <?php endforeach; ?>
                </div>
              </div>

              <?php if (count($spaces) > 1) : ?>
                <form method="post" class="confirmar">
                  <?php caua_hidden($event["id"]); ?>
                  <button class="btn btn-sm" type="submit" name="caua_action" value="delete">Remover este espaço</button>
                </form>
              <?php endif; ?>
            </div>
          </section>
        <?php endforeach; ?>
      <?php endif; ?>
    </main>
    <script>
      document.querySelectorAll("form.auto-upload input[type=file]").forEach((input) => {
        input.addEventListener("change", () => {
          if (input.files && input.files.length) input.form?.requestSubmit()
        })
      })
      document.querySelectorAll("form.confirmar").forEach((form) => {
        form.addEventListener("submit", (event) => {
          if (!window.confirm("Remover este espaço do site?")) event.preventDefault()
        })
      })
      const spot = new URLSearchParams(window.location.search).get("spot")
      if (spot) document.getElementById("espaco-" + spot)?.scrollIntoView({ block: "start" })
    </script>
  </body>
</html>
