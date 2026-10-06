export const INSTAGRAM_URL = "https://instagram.com/cauamontenegroo"
export const LINKTREE_URL = "https://linktr.ee/cauamontenegroo"

/** Número com DDI, só dígitos. */
export const WHATSAPP_E164 = "558597063967"
export const WHATSAPP_DISPLAY = "+55 85 9706-3967"
export const WHATSAPP_URL = `https://wa.me/${WHATSAPP_E164}`

export type GalleryImage = {
  src: string
  alt: string
}

export type Space = {
  id: string
  index: string
  title: string
  summary: string
  images: GalleryImage[]
}

export type BriefingFields = {
  nome: string
  marca: string
  tipo: string
  prazo: string
  diaSemana: string
  diaMes: string
  horario: string
  projeto: string
}

const photo = (id: string, alt: string): GalleryImage => ({
  src: `https://images.unsplash.com/${id}?auto=format&fit=crop&w=1400&q=80`,
  alt,
})

export const spaces: Space[] = [
  {
    id: "eventos",
    index: "01",
    title: "Eventos",
    summary: "Cobertura de presença, palco e bastidor, pronta para as redes.",
    images: [
      photo("photo-1492684223066-81342ee5ff30", "Foto fictícia: público em um evento"),
      photo("photo-1540575467063-178a50c2df87", "Foto fictícia: plateia em um auditório"),
      photo("photo-1511578314322-379afb476865", "Foto fictícia: salão preparado para evento"),
      photo("photo-1429962714451-bb934ecdc4ec", "Foto fictícia: show com luzes"),
    ],
  },
  {
    id: "conteudo",
    index: "02",
    title: "Conteúdo",
    summary: "Peças curtas pensadas para o celular: ritmo, fala e corte.",
    images: [
      photo("photo-1516035069371-29a1b244cc32", "Foto fictícia: câmera em close"),
      photo("photo-1492691527719-9d1e07e534b4", "Foto fictícia: pessoa filmando"),
      photo("photo-1478720568477-152d9b164e26", "Foto fictícia: sala de cinema"),
      photo("photo-1485846234645-a62644f84728", "Foto fictícia: set de filmagem"),
    ],
  },
  {
    id: "comercial",
    index: "03",
    title: "Comercial",
    summary: "Comunicação visual para marcas que precisam aparecer.",
    images: [
      photo("photo-1441986300917-64674bd600d8", "Foto fictícia: vitrine de loja"),
      photo("photo-1523275335684-37898b6baf30", "Foto fictícia: produto em fundo limpo"),
      photo("photo-1542744173-8e7e53415bb0", "Foto fictícia: equipe em reunião"),
      photo("photo-1556742049-0cfed4f6a45d", "Foto fictícia: atendimento em balcão"),
    ],
  },
  {
    id: "casamentos",
    index: "04",
    title: "Casamentos",
    summary: "O dia, os detalhes e as pessoas, com cara de filme curto.",
    images: [
      photo("photo-1519741497674-611481863552", "Foto fictícia: casamento ao ar livre"),
      photo("photo-1511285560929-80b456fea0bc", "Foto fictícia: casal em celebração"),
      photo("photo-1465495976277-4387d4b0b4c6", "Foto fictícia: mesa de casamento"),
      photo("photo-1520854221256-17451cc331bf", "Foto fictícia: pista de dança"),
    ],
  },
  {
    id: "aniversarios",
    index: "05",
    title: "Aniversários",
    summary: "Festas e encontros com registro leve, direto para publicar.",
    images: [
      photo("photo-1530103862676-de8c9debad1d", "Foto fictícia: festa de aniversário"),
      photo("photo-1464349095431-e9a21285b5f3", "Foto fictícia: bolo de aniversário"),
      photo("photo-1513151233558-d860c5398176", "Foto fictícia: confete em festa"),
      photo("photo-1527529482837-4698179dc6ce", "Foto fictícia: pessoas em uma festa"),
    ],
  },
]

export function formatBriefing(fields: BriefingFields): string {
  const when = [fields.diaSemana, fields.diaMes ? `dia ${fields.diaMes}` : "", fields.horario]
    .filter(Boolean)
    .join(", ")

  return [
    "Olá, Cauã! Quero falar de um vídeo.",
    "",
    `Nome: ${fields.nome}`,
    `Empresa ou marca: ${fields.marca || "—"}`,
    `Tipo de projeto: ${fields.tipo}`,
    `Prazo: ${fields.prazo || "—"}`,
    `Melhor horário: ${when || "—"}`,
    "",
    "Projeto:",
    fields.projeto,
  ].join("\n")
}
