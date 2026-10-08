import "./style.css"
import {
  formatBriefing,
  INSTAGRAM_URL,
  LINKTREE_URL,
  spaces,
  WHATSAPP_DISPLAY,
  WHATSAPP_E164,
  WHATSAPP_URL,
  type BriefingFields,
} from "./portfolio.ts"

const cardClass = [
  "group relative h-[70vh] min-h-80 w-[min(82%,24rem)] shrink-0 snap-start scroll-mt-28",
  "overflow-hidden rounded-box text-left sm:w-[28rem]",
  "focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-base-content",
].join(" ")

type Piece = { kind: "image" | "video" | "youtube" | "vimeo"; src: string; alt: string }
type ViewSpace = {
  id: string
  title: string
  summary: string
  exemplo: boolean
  cover: Piece | null
  pieces: Piece[]
}

function youtubeId(url: string) {
  return url.match(/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([A-Za-z0-9_-]{6,})/)?.[1] ?? ""
}

function vimeoId(url: string) {
  return url.match(/vimeo\.com\/(?:video\/)?(\d+)/)?.[1] ?? ""
}

function mediaUrl(src: string) {
  if (src.startsWith("http://") || src.startsWith("https://") || src.startsWith("./")) return src
  return `./${src.replace(/^\//, "")}`
}

function asPiece(src: string, kind: string, alt: string): Piece | null {
  if (!src) return null
  if (kind === "youtube" || youtubeId(src)) return { kind: "youtube", src, alt }
  if (kind === "vimeo" || vimeoId(src)) return { kind: "vimeo", src, alt }
  if (kind === "video" || /\.(mp4|webm|mov|m4v)(\?|$)/i.test(src)) return { kind: "video", src: mediaUrl(src), alt }
  return { kind: "image", src: mediaUrl(src), alt }
}

function coverUrl(piece: Piece) {
  if (piece.kind === "youtube") {
    const id = youtubeId(piece.src)
    return id ? `https://i.ytimg.com/vi/${id}/hqdefault.jpg` : ""
  }
  if (piece.kind === "image") return piece.src
  return ""
}

function renderSpaces(list: HTMLElement, view: ViewSpace[]) {
  const fragment = document.createDocumentFragment()
  const shortcuts = document.querySelector("#espacos-atalhos")
  if (shortcuts) shortcuts.replaceChildren()

  view.forEach((space, position) => {
    if (shortcuts) {
      const link = document.createElement("a")
      link.className = "link link-hover"
      link.href = `#espaco-${space.id}`
      link.textContent = space.title
      shortcuts.append(link)
    }

    const button = document.createElement("button")
    button.type = "button"
    button.id = `espaco-${space.id}`
    button.className = `${cardClass} reveal`
    button.dataset.reveal = ""
    button.dataset.revealDelay = String(position)
    button.addEventListener("click", () => openGallery(space))

    if (space.cover?.kind === "video") {
      const video = document.createElement("video")
      video.src = space.cover.src
      video.muted = true
      video.playsInline = true
      video.preload = "metadata"
      video.className = "absolute inset-0 h-full w-full bg-base-300 object-cover"
      button.append(video)
    } else {
      const image = document.createElement("img")
      image.src = space.cover ? coverUrl(space.cover) : ""
      image.alt = ""
      image.className =
        "absolute inset-0 h-full w-full bg-base-300 object-cover transition duration-700 motion-safe:group-hover:scale-105"
      image.decoding = "async"
      button.append(image)
    }

    const shade = document.createElement("span")
    shade.className = "absolute inset-0 bg-linear-to-t from-black/80 via-black/20 to-black/10"
    shade.setAttribute("aria-hidden", "true")

    const copy = document.createElement("span")
    copy.className = "absolute inset-x-0 bottom-0 flex flex-col gap-2 p-6 pr-16 text-white"

    const index = document.createElement("span")
    index.className = "text-xs tracking-[0.28em]"
    index.textContent = String(position + 1).padStart(2, "0")

    const title = document.createElement("span")
    title.className = "text-4xl font-medium tracking-tight"
    title.textContent = space.title

    const hint = document.createElement("span")
    hint.className = "text-sm text-white/80"
    hint.textContent = space.exemplo
      ? "Fotos fictícias. Espaço para os trabalhos do Cauã."
      : "Toque para ver"

    copy.append(index, title, hint)
    button.append(shade, copy)
    fragment.append(button)
  })

  list.replaceChildren(fragment)
}

function openGallery(space: ViewSpace) {
  const dialog = document.querySelector<HTMLDialogElement>("#galeria")
  const title = document.querySelector("#galeria-titulo")
  const summary = document.querySelector("#galeria-texto")
  const note = document.querySelector("#galeria-nota")
  const grid = document.querySelector("#galeria-grid")
  const box = dialog?.querySelector(".modal-box")
  if (!dialog || !title || !summary || !grid) return

  title.textContent = space.title
  summary.textContent = space.summary
  if (note) {
    note.textContent = space.exemplo
      ? "Fotos fictícias. Este espaço fica para as fotos dos trabalhos do Cauã."
      : "Fotos e vídeos deste espaço."
  }
  grid.replaceChildren()

  space.pieces.forEach((piece, index) => {
    const figure = document.createElement("figure")
    figure.className = "reveal overflow-hidden rounded-box bg-base-300"
    figure.style.transitionDelay = `${index * 70}ms`
    if (piece.kind === "youtube" || piece.kind === "vimeo") {
      const frame = document.createElement("iframe")
      const id = piece.kind === "youtube" ? youtubeId(piece.src) : vimeoId(piece.src)
      frame.src = piece.kind === "youtube" ? `https://www.youtube.com/embed/${id}` : `https://player.vimeo.com/video/${id}`
      frame.title = piece.alt
      frame.allowFullscreen = true
      frame.className = "aspect-video w-full"
      figure.append(frame)
    } else if (piece.kind === "video") {
      const video = document.createElement("video")
      video.src = piece.src
      video.controls = true
      video.playsInline = true
      video.className = "aspect-video w-full bg-base-300"
      figure.append(video)
    } else {
      const img = document.createElement("img")
      img.src = piece.src
      img.alt = piece.alt
      img.loading = "lazy"
      img.decoding = "async"
      img.className = "aspect-3/4 w-full object-cover"
      figure.append(img)
    }
    grid.append(figure)
  })

  box?.scrollTo(0, 0)
  if (!dialog.open) dialog.showModal()
  requestAnimationFrame(() => {
    grid.querySelectorAll("figure").forEach((figure) => {
      if (figure instanceof HTMLElement) figure.dataset.revealed = "true"
    })
  })
}

function fillSelect(select: HTMLSelectElement, values: string[]) {
  for (const value of values) {
    const option = document.createElement("option")
    option.value = value
    option.textContent = value
    select.append(option)
  }
}

function hourLabels() {
  const labels: string[] = []
  for (let minutes = 8 * 60; minutes <= 20 * 60; minutes += 30) {
    const hour = Math.floor(minutes / 60)
    const minute = minutes % 60
    labels.push(`${String(hour).padStart(2, "0")}:${String(minute).padStart(2, "0")}`)
  }
  return labels
}

function readBriefing(form: HTMLFormElement): BriefingFields {
  const data = new FormData(form)
  const value = (key: string) => String(data.get(key) ?? "").trim()
  return {
    nome: value("nome"),
    marca: value("marca"),
    tipo: value("tipo"),
    prazo: value("prazo"),
    diaSemana: value("diaSemana"),
    diaMes: value("diaMes"),
    horario: value("horario"),
    projeto: value("projeto"),
  }
}

async function copyText(text: string) {
  try {
    await navigator.clipboard.writeText(text)
    return true
  } catch {
    return false
  }
}

function setupIntro() {
  const skip = document.querySelector<HTMLInputElement>("#skip-intro")
  const intro = document.querySelector<HTMLElement>("#intro")
  const count = document.querySelector<HTMLElement>("#intro-count")
  if (!skip || !intro || !count) return

  const sync = () => {
    intro.inert = skip.checked
    intro.setAttribute("aria-hidden", String(skip.checked))
    if (skip.checked) document.dispatchEvent(new Event("intro:done"))
  }

  skip.addEventListener("change", sync)

  if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
    skip.checked = true
    sync()
    return
  }

  let value = 0
  const timer = window.setInterval(() => {
    value = Math.min(99, value + 1)
    count.textContent = String(value).padStart(2, "0")
    if (value < 99) return
    window.clearInterval(timer)
    window.setTimeout(() => {
      skip.checked = true
      sync()
    }, 280)
  }, 18)
}

function setupDrawer() {
  const toggle = document.querySelector<HTMLInputElement>("#nav-drawer")
  if (!toggle) return
  document.querySelectorAll("[data-close-drawer]").forEach((link) => {
    link.addEventListener("click", () => {
      toggle.checked = false
    })
  })
}

function setupContact() {
  const form = document.querySelector<HTMLFormElement>("#briefing")
  const status = document.querySelector("#briefing-status")
  const copyOut = document.querySelector<HTMLTextAreaElement>("#briefing-copia")
  const note = document.querySelector("#contato-aviso")
  const day = document.querySelector<HTMLSelectElement>("#dia-mes")
  const hour = document.querySelector<HTMLSelectElement>("#horario")
  if (!form || !status || !copyOut || !note || !day || !hour) return

  fillSelect(
    day,
    Array.from({ length: 31 }, (_, index) => String(index + 1)),
  )
  fillSelect(hour, hourLabels())

  note.textContent = WHATSAPP_E164
    ? "O briefing abre uma conversa no WhatsApp."
    : "O número de WhatsApp ainda não está neste site teste. A mensagem é copiada e o Linktree abre para você enviar no WhatsApp de orçamentos."

  form.addEventListener("submit", async (event) => {
    event.preventDefault()
    const text = formatBriefing(readBriefing(form))
    copyOut.hidden = false
    copyOut.value = text
    const copied = await copyText(text)
    const destination = WHATSAPP_E164
      ? `https://wa.me/${WHATSAPP_E164}?text=${encodeURIComponent(text)}`
      : LINKTREE_URL

    if (WHATSAPP_E164) {
      status.textContent = "Abrindo o WhatsApp com o briefing."
    } else if (copied) {
      status.textContent = "Briefing copiado. Abri o Linktree para você colar no WhatsApp."
    } else {
      status.textContent = "Selecione a mensagem abaixo, copie e cole no WhatsApp do Linktree."
      copyOut.focus()
      copyOut.select()
    }

    window.open(destination, "_blank", "noopener,noreferrer")
  })
}

function setupPortrait() {
  const img = document.querySelector<HTMLImageElement>("[data-portrait]")
  const label = document.querySelector<HTMLElement>("[data-portrait-label]")
  if (!img || !label) return
  img.addEventListener("load", () => {
    if (!img.naturalWidth) return
    img.classList.remove("hidden")
    label.classList.add("hidden")
  })
  img.src = "./caua.jpg"
}

function setupInstagramLinks() {
  document.querySelectorAll<HTMLAnchorElement>("[data-instagram]").forEach((link) => {
    link.href = INSTAGRAM_URL
  })
  document.querySelectorAll<HTMLAnchorElement>("[data-linktree]").forEach((link) => {
    link.href = LINKTREE_URL
  })
  document.querySelectorAll<HTMLAnchorElement>("[data-whatsapp]").forEach((link) => {
    link.href = WHATSAPP_URL
    if (link.hasAttribute("data-whatsapp-label")) link.textContent = WHATSAPP_DISPLAY
  })
}

function setupReveal() {
  const reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches
  let started = false

  const start = () => {
    if (started) return
    started = true
    const items = [...document.querySelectorAll<HTMLElement>("[data-reveal]")]
    if (reduce) {
      items.forEach((item) => {
        item.dataset.revealed = "true"
      })
      return
    }

    const observer = new IntersectionObserver(
      (entries) => {
        for (const entry of entries) {
          if (!entry.isIntersecting) continue
          const item = entry.target as HTMLElement
          item.dataset.revealed = "true"
          observer.unobserve(item)
        }
      },
      { threshold: 0.18, rootMargin: "0px 0px -8% 0px" },
    )

    items.forEach((item) => {
      const step = Number(item.dataset.revealDelay ?? "0")
      if (step > 0) item.style.transitionDelay = `${step * 90}ms`
      observer.observe(item)
    })
  }

  const skip = document.querySelector<HTMLInputElement>("#skip-intro")
  if (!skip || skip.checked) {
    start()
    return
  }
  document.addEventListener("intro:done", start, { once: true })
}

function fallbackSpaces(): ViewSpace[] {
  return spaces.map((space) => {
    const pieces = space.images
      .map((image) => asPiece(image.src, "image", image.alt))
      .filter((piece): piece is Piece => piece !== null)
    return {
      id: space.id,
      title: space.title,
      summary: space.summary,
      exemplo: true,
      cover: pieces[0] ?? null,
      pieces,
    }
  })
}

function publishedSpaces(payload: unknown): ViewSpace[] | null {
  if (!payload || typeof payload !== "object" || !("spaces" in payload) || !Array.isArray(payload.spaces)) return null
  const view = payload.spaces.flatMap((item): ViewSpace[] => {
    if (!item || typeof item !== "object") return []
    const record = item as Record<string, unknown>
    const title = typeof record.title === "string" ? record.title : ""
    const id = typeof record.id === "string" ? record.id : ""
    if (!title || !id) return []
    const banner = record.banner && typeof record.banner === "object" ? (record.banner as Record<string, unknown>) : {}
    const photos = Array.isArray(record.photos) ? record.photos : []
    const videos = Array.isArray(record.videos) ? record.videos : []
    const pieces = [
      ...photos.flatMap((photo) => {
        const src = photo && typeof photo === "object" && "src" in photo ? String(photo.src) : ""
        const piece = asPiece(src, "image", title)
        return piece ? [piece] : []
      }),
      ...videos.flatMap((video) => {
        if (!video || typeof video !== "object" || !("src" in video)) return []
        const src = String(video.src)
        const kind = "kind" in video ? String(video.kind) : ""
        const piece = asPiece(src, kind, title)
        return piece ? [piece] : []
      }),
    ]
    const cover = asPiece(typeof banner.src === "string" ? banner.src : "", typeof banner.kind === "string" ? banner.kind : "", title) ?? pieces[0] ?? null
    if (cover && !pieces.some((piece) => piece.src === cover.src)) pieces.unshift(cover)
    if (!cover && pieces.length === 0) return []
    return [{
      id,
      title,
      summary: "Fotos e vídeos deste espaço.",
      exemplo: record.exemplo === true,
      cover,
      pieces: pieces.length > 0 ? pieces : cover ? [cover] : [],
    }]
  })
  return view.length > 0 ? view : null
}

async function loadPublishedSpaces() {
  for (const path of ["./conteudo/espacos.json", "./conteudo/espacos.exemplo.json"]) {
    try {
      const response = await fetch(path)
      if (!response.ok) continue
      const view = publishedSpaces(await response.json())
      if (view) return view
    } catch {
      /* o site segue com os espaços de exemplo */
    }
  }
  return fallbackSpaces()
}

function applySpaceNotes(view: ViewSpace[]) {
  const exemplo = view.every((space) => space.exemplo)
  const note = document.querySelector("#espacos-nota")
  if (note && !exemplo) {
    note.textContent = "Toque em um espaço para ver as fotos e os vídeos dos trabalhos do Cauã."
  }
}

setupIntro()
setupDrawer()
setupContact()
setupPortrait()
setupInstagramLinks()

const spaceList = document.querySelector<HTMLElement>("#espacos-lista")
if (spaceList) renderSpaces(spaceList, fallbackSpaces())
setupReveal()

void loadPublishedSpaces().then((view) => {
  if (!spaceList) return
  renderSpaces(spaceList, view)
  applySpaceNotes(view)
  spaceList.querySelectorAll<HTMLElement>("[data-reveal]").forEach((item) => {
    item.dataset.revealed = "true"
  })
})
