# SKILL — MarreiraMCP Elementor

Guia de uso do servidor MCP do plugin **MarreiraMCP Elementor** para um agente de
IA criar e editar páginas e templates do **Elementor** e **Elementor Pro**. Este
documento descreve a **superfície pública** (conexão, rotas, ferramentas e o
formato de dados) — não a implementação interna.

> Sempre que o comportamento do plugin mudar, **este arquivo deve ser
> atualizado** junto (rotas/ferramentas/limites). Veja o CHANGELOG.

---

## 1. Conexão

| Item | Valor |
|---|---|
| **Endpoint** | `https://SEU-SITE/wp-json/marreira-mcp-elementor/v1/mcp` |
| **Método** | `POST` (JSON-RPC 2.0). `GET` retorna 405. |
| **Transporte** | Streamable HTTP (uma resposta JSON por POST) |
| **Auth** | Header `Authorization: Bearer <token>` |
| **TLS** | HTTPS obrigatório (por padrão) |
| **Descoberta** | A rota é **oculta** do índice público de `/wp-json/` |
| **Skill pública** | `GET /wp-json/marreira-mcp-elementor/v1/skill` (este arquivo) |

O token é gerado em **Configurações → MarreiraMCP Elementor** no WP Admin. As
permissões efetivas são as do **usuário de serviço** configurado.

```http
POST /wp-json/marreira-mcp-elementor/v1/mcp HTTP/1.1
Content-Type: application/json
Accept: application/json
Authorization: Bearer mme_xxxxxxxx...
Origin: https://SEU-SITE
```

---

## 2. Handshake MCP

### 2.1 `initialize`
```json
{ "jsonrpc": "2.0", "id": 1, "method": "initialize",
  "params": { "protocolVersion": "2025-03-26", "capabilities": {}, "clientInfo": { "name": "meu-agente", "version": "1.0" } } }
```
Resposta inclui `serverInfo` (`MarreiraMCP Elementor`) e `capabilities.tools`.

### 2.2 `tools/list`
```json
{ "jsonrpc": "2.0", "id": 2, "method": "tools/list", "params": {} }
```

### 2.3 `tools/call`
```json
{ "jsonrpc": "2.0", "id": 3, "method": "tools/call",
  "params": { "name": "create_elementor_page", "arguments": { "title": "Landing", "status": "draft", "elements": [ /* ... */ ] } } }
```
O resultado vem em `result.content[0].text` (JSON) e `result.isError`. Também há
`ping` e a notificação `notifications/initialized`.

---

## 3. Modelo de dados do Elementor (essencial)

Diferente de builders de árvore plana, o Elementor usa uma **árvore aninhada**:
cada elemento tem um array `elements` com seus filhos. Cada elemento:

```json
{
  "id": "a1b2c3d",          // 7 caracteres [0-9a-f], unico no documento (gerado pelo plugin se faltar)
  "elType": "container",    // section | column | widget | container
  "widgetType": "heading",  // SOMENTE quando elType === "widget"
  "settings": { /* ... */ },
  "elements": [ /* filhos */ ],
  "isInner": false
}
```

### Dois modelos de layout
- **Containers (recomendado, flexbox/grid):** `elType: "container"` aninha
  containers e widgets diretamente. É o padrão moderno do Elementor.
- **Legado section/column/widget:** `section` contém `column` (com setting
  `_column_size`), que contém `widget`. Use só ao editar páginas antigas.

### Regras de ouro
- **`settings` é um objeto** com as chaves do widget (descubra via
  `get_element_schema`). Ex.: heading usa `title`, `header_size` (`h1`..`h6`,
  `div`, `span`, `p`), `align`.
- **Responsivo por sufixo:** `align_tablet`, `align_mobile`,
  `padding_tablet`, etc.
- **Dimensões** (padding/margin): `{ "unit": "px", "top": "20", "right": "0",
  "bottom": "20", "left": "0", "isLinked": false }`.
- **Sliders/tamanhos:** `{ "unit": "px", "size": 100, "sizes": [] }`.
- **Cores/fontes globais:** referenciadas pela chave especial `__globals__` no
  `settings`, mapeando controle → token global:
  `"__globals__": { "title_color": "globals/colors?id=primary",
  "typography_typography": "globals/typography?id=primary" }`.
  Use `list_global_colors` / `list_global_fonts` para obter os `id`.
- **IDs:** pode omitir/duplicar IDs ao enviar; o plugin **gera/regenera** IDs
  para garantir unicidade (e evita colisão ao inserir subárvores).

### Exemplo mínimo (Container → Heading + Botão)
```json
[
  {
    "id": "c0000a1", "elType": "container", "isInner": false,
    "settings": { "content_width": "boxed", "flex_direction": "column", "flex_gap": { "unit": "px", "size": 24 } },
    "elements": [
      { "id": "w0000h1", "elType": "widget", "widgetType": "heading", "isInner": false,
        "settings": { "title": "Bem-vindo", "header_size": "h1", "align": "center" }, "elements": [] },
      { "id": "w0000b1", "elType": "widget", "widgetType": "button", "isInner": false,
        "settings": { "text": "Começar", "link": { "url": "#" }, "align": "center" }, "elements": [] }
    ]
  }
]
```
> Também é aceito o formato de export/clipboard: `{ "content": [ ... ] }` ou
> `{ "elements": [ ... ] }`.

---

## 4. Catálogo de ferramentas

Capability indica a permissão mínima exigida do usuário de serviço.

### 4.1 Páginas
| Tool | Argumentos | Faz | Capability |
|---|---|---|---|
| `list_pages` | `post_type?`, `status?`, `limit?` | Lista posts/páginas Elementor | `edit_pages` |
| `get_page` | `post_id` | Árvore aninhada + page settings | `edit_pages` |
| `create_elementor_page` | `title`, `post_type?`, `status?`, `slug?`, `elements?` | Cria página Elementor | `publish_pages` |
| `update_elementor_page` | `post_id`, `elements` | Substitui a árvore (Document API) | `edit_post` |
| `set_page_settings` | `post_id`, `settings` | Layout/SEO/custom_css (scripts recusados) | `edit_post` |
| `delete_page` | `post_id`, `force?` | Lixeira (ou exclusão definitiva) | `delete_post` |

### 4.2 Templates (`elementor_library`)
| Tool | Argumentos | Faz | Capability |
|---|---|---|---|
| `list_templates` | `type?` | Lista templates | `edit_pages` |
| `create_template` | `title`, `type`, `status?`, `elements?` | Cria template | `publish_pages` |
| `update_template` | `template_id`, `elements` | Substitui a árvore | `edit_post` |
| `set_template_conditions` | `template_id`, `conditions` | Onde o template aparece (**Pro**) | `edit_theme_options` |

Tipos free: `page`, `section`, `container`. Tipos **Elementor Pro** (Theme
Builder): `header`, `footer`, `single-post`, `single-page`, `archive`,
`search-results`, `error-404`, `loop-item`, `popup`. `conditions` é uma lista de
strings, ex.: `["include/general"]`, `["include/singular/post"]`.

### 4.3 Elementos (edição fina)
| Tool | Argumentos | Faz | Capability |
|---|---|---|---|
| `insert_element` | `post_id`, `parent_id?`, `position?`, `element` **ou** `elements` | Insere node/subárvore (gera IDs) | `edit_post` |
| `update_element_settings` | `post_id`, `element_id`, `settings`, `replace?` | Merge (ou substitui) settings | `edit_post` |
| `move_element` | `post_id`, `element_id`, `new_parent?`, `position?` | Move para outro pai/posição | `edit_post` |
| `delete_element` | `post_id`, `element_id` | Remove o elemento e a subárvore | `edit_post` |
| `duplicate_element` | `post_id`, `element_id` | Duplica como irmão (novos IDs) | `edit_post` |

### 4.4 Estilos globais (Kit ativo)
| Tool | Argumentos | Faz | Capability |
|---|---|---|---|
| `list_global_colors` | — | Cores globais (system + custom) | `edit_pages` |
| `list_global_fonts` | — | Tipografias globais (system + custom) | `edit_pages` |
| `get_kit_settings` | — | Settings do Kit (design system) | `edit_pages` |
| `upsert_global_color` | `title`, `color`, `id?` | Cria/atualiza cor global custom | `edit_theme_options` |
| `delete_global_color` | `id` | Remove cor global custom | `edit_theme_options` |
| `upsert_global_font` | `title`, `typography`, `id?` | Cria/atualiza tipografia global custom | `edit_theme_options` |

### 4.5 Utilitários
| Tool | Argumentos | Faz | Capability |
|---|---|---|---|
| `get_capabilities` | — | Ambiente: versões, flags, Kit, breakpoints | — |
| `validate_tree` | `elements` | Valida integridade/IDs/anti-RCE (dry-run) | — |
| `regenerate_css` | `post_id?` | Regenera CSS de um post (ou limpa cache) | `edit_pages` |
| `list_elements` | — | Lista todos os widgets registrados (inclui Pro/terceiros) | `edit_pages` |
| `get_element_schema` | `widget_type`, `include_full?` | Schema de controles de um widget | `edit_pages` |

> **Descobrir settings de um widget:** use `list_elements` para os `widgetType`
> e `get_element_schema` para os controles (tipos, defaults, opções) — assim
> você sabe exatamente o que `form`, `posts`, `nav-menu`, `image-carousel` etc.
> aceitam. Grupos de controle (ex.: `typography_`, `_padding`) expandem em
> várias chaves.

---

## 5. O que é possível ✅ / não é possível ❌

**Possível:**
- Criar/editar páginas e templates Elementor no formato nativo, com round-trip
  (gravação via Document API do Elementor: kses + regeneração de CSS + versão).
- Montar layouts completos (containers/sections, widgets), estilos,
  responsividade e referências a cores/fontes globais.
- Inserir/mover/duplicar/excluir elementos individualmente.
- Ler e criar cores/fontes globais do Kit; ler as settings do Kit.
- Validar uma árvore antes de gravar.
- Descobrir os widgets disponíveis e o schema de cada um.

**Código — pode criar, mas EXECUTA de fato (importante):**
- Os widgets **`html`** e **`shortcode`** executam código no frontend. No
  Elementor **não há etapa de assinatura** (diferente do Bricks).
  - **Bloqueio anti-RCE ligado (padrão):** esses widgets são **recusados (403)**.
  - **Desligado:** a IA **pode criar** `html`/`shortcode`, e eles **rodam
    diretamente** no frontend. O resultado da tool retorna um aviso.
- ⚠️ Avise o usuário sempre que criar uma página com `html`/`shortcode`.

**Nunca é possível (não há proteção posterior):**
- ❌ Injeção de `<script>` em widgets comuns ou em `custom_css` — **sempre
  recusada (403)**, mesmo com o toggle desligado.
- ❌ Operar sem token válido ou sem HTTPS.
- ❌ Ultrapassar as permissões do usuário de serviço.

---

## 6. Erros
- **JSON-RPC**: `-32700` (parse), `-32601` (método), `-32602` (tool/args).
- **Auth/guard** (HTTP): `401` (sem token), `403` (token inválido / HTTPS /
  anti-RCE / origin), `429` (rate limit).
- **Tool**: erros voltam com `result.isError = true` e a mensagem em
  `result.content[0].text`.

---

## 7. Fluxo recomendado para a IA
1. `get_capabilities` → confirmar Elementor/Pro ativos e flags.
2. `list_global_colors` / `list_global_fonts` / `get_kit_settings` → conhecer o
   design system do site.
3. (Opcional) `list_elements` / `get_element_schema` → settings dos widgets.
4. `validate_tree` → checar a árvore antes de gravar.
5. `create_elementor_page` / `update_elementor_page` → publicar o layout.
6. Ajustes finos com `update_element_settings`, `insert_element`, etc.
7. `regenerate_css` se o frontend não refletir a mudança.

---

## 8. Boas práticas de design (parecer nativo do site)

O objetivo é que a página gerada pareça **nativa do site** — siga o design
system do Kit, não invente o seu.

### Tipografia — prefira tokens globais a valores fixos
- Use a **tag semântica** correta (`header_size: h1` no título principal, `h2`
  nas seções, `h3` em cards) e **referencie a tipografia global** via
  `__globals__` (`typography_typography: globals/typography?id=...`) em vez de
  cravar `font-size`/`font-family` no elemento. Assim a página acompanha a
  escala do Kit.
- Para cor de texto, prefira `__globals__` apontando para uma cor global do Kit
  (`globals/colors?id=primary`).

### Reaproveite os tokens do site (leia antes de criar)
- `list_global_colors` → use os `id` das cores reais via `__globals__`.
- `list_global_fonts` → use os `id` das tipografias reais.
- `get_kit_settings` → confira largura do container, espaçamentos e breakpoints.
- Prefira **containers** (flexbox) para layouts novos.

### Editando páginas existentes
- **Leia primeiro** com `get_page` e **preserve os elementos funcionais**
  (formulários, loops/`posts`, shortcodes legítimos) — reaproveite esses nodes
  com os mesmos `settings`; **não os recrie do zero**. Enriqueça **ao redor**.
- Páginas com widget `html`/`shortcode` não podem ser regravadas enquanto o
  bloqueio anti-RCE estiver ligado (o guard recusa a árvore inteira). Edite no
  editor visual ou ajuste a flag conscientemente.

### Documento limpo
- Dê títulos claros às páginas/templates.
- Para landing em tela cheia, use `set_page_settings` com
  `{ "template": "elementor_canvas" }` (layout Canvas, sem header/footer do
  tema) ou `"elementor_header_footer"` para manter header/footer.
- Evite `<script>` em qualquer texto/HTML — é recusado pelo guard anti-RCE.

---

_Documento de referência da skill — mantenha sincronizado com o código a cada
versão._
