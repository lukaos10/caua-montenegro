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
  type Space,
} from "./portfolio.ts"

const cardClass = [
  "group relative h-[70vh] min-h-[28rem] w-[82vw] shrink-0 snap-start scroll-mt-28",
  "overflow-hidden rounded-box text-left sm:w-[28rem]",
  "focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-base-content",
].join(" ")

function renderSpaces(list: HTMLElement) {
  const fragment = document.createDocumentFragment()

  for (const space of spaces) {
    const button = document.createElement("button")
    button.type = "button"
    button.id = `espaco-${space.id}`
    button.className = `${cardClass} reveal`
    button.dataset.reveal = ""
    button.dataset.revealDelay = String(spaces.indexOf(space))
    button.addEventListener("click", () => openGallery(space))

    const image = document.createElement("img")
    image.src = space.images[0]?.src ?? ""
    image.alt = ""
    image.className =
      "absolute inset-0 h-full w-full bg-base-300 object-cover transition duration-700 motion-safe:group-hover:scale-105"
    image.decoding = "async"

    const shade = document.createElement("span")
    shade.className = "absolute inset-0 bg-linear-to-t from-black/80 via-black/20 to-black/10"
    shade.setAttribute("aria-hidden", "true")

    const copy = document.createElement("span")
    copy.className = "absolute inset-x-0 bottom-0 flex flex-col gap-2 p-6 text-white"

    const index = document.createElement("span")
    index.className = "text-xs tracking-[0.28em]"
    index.textContent = space.index

    const title = document.createElement("span")
    title.className = "text-4xl font-medium tracking-tight"
    title.textContent = space.title

    const hint = document.createElement("span")
    hint.className = "text-sm text-white/80"
    hint.textContent = "Toque para ver fotos"

    copy.append(index, title, hint)
    button.append(image, shade, copy)
    fragment.append(button)
  }

  list.replaceChildren(fragment)
}

function openGallery(space: Space) {
  const dialog = document.querySelector<HTMLDialogElement>("#galeria")
  const title = document.querySelector("#galeria-titulo")
  const summary = document.querySelector("#galeria-texto")
  const grid = document.querySelector("#galeria-grid")
  const box = dialog?.querySelector(".modal-box")
  if (!dialog || !title || !summary || !grid) return

  title.textContent = space.title
  summary.textContent = space.summary
  grid.replaceChildren()

  space.images.forEach((image, index) => {
    const figure = document.createElement("figure")
    figure.className = "reveal overflow-hidden rounded-box bg-base-300"
    figure.style.transitionDelay = `${index * 70}ms`
    const img = document.createElement("img")
    img.src = image.src
    img.alt = image.alt
    img.loading = "lazy"
    img.decoding = "async"
    img.className = "aspect-3/4 w-full object-cover"
    figure.append(img)
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

setupIntro()
setupDrawer()
setupContact()
setupInstagramLinks()

const spaceList = document.querySelector<HTMLElement>("#espacos-lista")
if (spaceList) renderSpaces(spaceList)
setupReveal()
