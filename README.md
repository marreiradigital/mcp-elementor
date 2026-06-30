<div align="center">

# ⚡ MarreiraMCP Elementor

**Servidor MCP para criar e editar páginas do Elementor com IA — de forma nativa, segura e reversível.**

[![Versão](https://img.shields.io/badge/versão-0.1.0-3a8bfd.svg)](marreira-mcp-elementor/CHANGELOG.md)
[![WordPress](https://img.shields.io/badge/WordPress-6.4%2B-21759b.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4.svg)](https://www.php.net/)
[![Elementor](https://img.shields.io/badge/Elementor-requerido-92003b.svg)](https://elementor.com/)
[![Licença](https://img.shields.io/badge/licença-GPL--2.0%2B-green.svg)](#-licença)
[![MCP](https://img.shields.io/badge/protocolo-MCP%20JSON--RPC%202.0-7b5cff.svg)](https://modelcontextprotocol.io/)

</div>

---

## ✨ O que é

**MarreiraMCP Elementor** é um plugin WordPress que liga um agente de IA (como
Claude, Cursor e outros clientes MCP) diretamente ao **Elementor**. Em vez de
você arrastar widgets manualmente, a IA monta e ajusta as páginas pra você —
escrevendo **no formato nativo do Elementor**, o mesmo que o editor visual usa.

O plugin expõe um **servidor MCP (Model Context Protocol)** por uma rota REST
**oculta** e protegida por **token**, com um catálogo de ferramentas (tools)
para páginas, templates, elementos e estilos globais.

> 🔁 **Compatibilidade bidirecional (round-trip):** páginas geradas pela IA
> abrem e editam normalmente no editor Elementor — e páginas feitas no editor
> podem ser refinadas pela IA sem corromper nada. As gravações passam pelo
> **Document API do Elementor**, que roda kses, regeneração de CSS e
> atualização de versão automaticamente.

---

## 🎯 Por que usar

| | |
|---|---|
| 🤖 **IA nativa no Elementor** | A IA cria containers, headings, botões, imagens — no formato real do Elementor (árvore aninhada JSON), não em HTML "colado". |
| 🔁 **Sem lock-in, sem corrupção** | Tudo continua editável no editor visual. A IA respeita o que já existe. |
| 🛡️ **Seguro por padrão** | Token + HTTPS + rate limit + bloqueio anti-RCE. Endpoints fora da descoberta pública de `/wp-json/`. |
| 🎨 **Estilos globais (Kit)** | Lê e cria cores e tipografias globais do Kit ativo; referencia tokens via `__globals__`. |
| 🧩 **Templates** | Templates de seção, container, página e — com Elementor Pro — header, footer, single, archive, loop-item e popup, com condições de exibição. |
| ⚡ **CSS sempre em dia** | Gravação via Document API garante regeneração automática; `regenerate_css` disponível quando necessário. |
| 📖 **Skill embutida** | Cada site instala uma URL pública com a documentação em Markdown, pronta para a IA ler. |
| 🖥️ **Painel próprio** | Menu de topo com painel 100% AJAX: token, segurança, métricas e catálogo de ferramentas. |

---

## 📦 Requisitos

- WordPress **6.4+**
- PHP **7.4+**
- Plugin **Elementor** (versão free) ativo
- **Elementor Pro** opcional — desbloqueia tipos de template do Theme Builder
  (header, footer, single, archive, loop-item, popup) e condições de exibição
- **HTTPS** no site (exigido por padrão)
- Um **cliente MCP** (ex.: Claude Desktop, Cursor) para consumir o servidor

---

## 🚀 Instalação rápida

1. Copie a pasta `marreira-mcp-elementor/` para `wp-content/plugins/` (ou
   instale o `.zip` pelo painel do WordPress).
2. Ative em **Plugins**.
3. Vá em **Configurações → MarreiraMCP Elementor**.
4. Escolha um **usuário de serviço** (Editor ou Administrador) — as permissões
   dele definem o que a IA pode fazer.
5. Clique em **Gerar token** e copie-o (ele aparece **uma única vez**).
6. Configure seu cliente MCP com a URL do endpoint e o cabeçalho:

   ```http
   POST https://SEU-SITE/wp-json/marreira-mcp-elementor/v1/mcp
   Authorization: Bearer mme_xxxxxxxx...
   Content-Type: application/json
   ```

---

## 🔌 Como funciona (visão geral)

```
┌──────────────┐   JSON-RPC 2.0 / HTTPS     ┌──────────────────────────┐
│  Cliente IA  │ ────── Bearer token ──────▶ │  WordPress + este plugin  │
│ (MCP client) │                             │   (rota REST oculta)      │
└──────────────┘ ◀───── resultado JSON ───── └────────────┬─────────────┘
                                                          │
                   ┌──────────────────────────────────────┤
                   │              Camadas internas         │
                   ▼                                       │
         ┌──────────────────┐                             │
         │   Auth Guard     │ token + capability           │
         │   Token Manager  │ Bearer / hash SHA-256        │
         │   Code Guard     │ anti-RCE (html/shortcode)    │
         │   Sanitizer      │ escape + kses                │
         └────────┬─────────┘                             │
                  │                                        │
                  ▼                                        │
         ┌──────────────────┐                             │
         │ Elementor Gateway│ Document API (read/write)    │
         │ Element Tree     │ árvore aninhada (nested)     │
         │ Element Inspector│ introspeccao de widgets      │
         │ Global Styles    │ Kit (cores/fontes)           │
         │ Css Regenerator  │ cache/CSS pós-escrita        │
         └────────┬─────────┘                             │
                  │                                        │
                  ▼                                        │
         ┌──────────────────────┐                         │
         │   Elementor          │                         │
         │ (páginas/templates)  │                         │
         └──────────────────────┘                         │
```

- O cliente faz o handshake MCP (`initialize`), lista as ferramentas
  (`tools/list`) e as executa (`tools/call`).
- Cada chamada passa pela camada de segurança antes de tocar no Elementor.
- As alterações refletem no front e continuam editáveis no builder.

> 📖 Para o detalhamento das rotas, ferramentas e do que é (ou não) possível,
> consulte **[SKILL.md](SKILL.md)**.

---

## 📖 Skill para a IA (URL pública por instalação)

O `SKILL.md` vai **embutido no plugin** e é servido numa **URL pública**
(somente leitura, sem token) em qualquer site onde o plugin esteja instalado:

```
GET https://SEU-SITE/wp-json/marreira-mcp-elementor/v1/skill
```

Ela devolve a documentação em **Markdown** — basta copiar a URL (há um botão de
copiar no painel, aba **Conexão**) e mandar para a IA/IDE ler como referência do
que o plugin permite. Útil quando a skill ainda não está baixada no cliente.

---

## 🧰 Capacidades (resumo)

- **Páginas:** listar, ler, criar, atualizar, ajustar page settings (layout,
  SEO, CSS personalizado), excluir.
- **Templates:** listar, criar (section, container, page e tipos Pro do Theme
  Builder), atualizar, definir condições de exibição (Pro).
- **Elementos:** inserir, atualizar settings (merge ou substituição), mover,
  duplicar, excluir — com validação de integridade da árvore aninhada.
- **Estilos globais (Kit):** cores globais (listar/criar/atualizar/excluir),
  tipografias globais (listar/criar), leitura das settings do Kit ativo.
- **Utilitários:** inspecionar o ambiente, validar uma árvore (dry-run),
  regenerar o CSS, **descobrir widgets + schema de settings de cada um**
  (`list_elements` / `get_element_schema`).

---

## 🔒 Segurança

Pensado para **não abrir brechas** no seu WordPress:

- ✅ **Endpoints ocultos** do índice público de `/wp-json/`.
- ✅ **Token** armazenado apenas como **hash SHA-256** (nunca em texto puro),
  em option não-autoload.
- ✅ **HTTPS obrigatório** e **rate limiting** por token.
- ✅ **Permissões reais**: cada operação respeita as capabilities do usuário de
  serviço — nada de acesso irrestrito.
- ✅ **Modelo de usuário de serviço**: as capabilities checadas são as do
  usuário configurado, não do token em si.
- ✅ **Anti-RCE (Code Guard)**: os widgets `html` e `shortcode` **executam**
  no frontend do Elementor e **não possuem etapa de assinatura** (diferente do
  Bricks). Com o bloqueio anti-RCE **ligado (padrão)**, eles são **recusados
  (403)**. Com o bloqueio desligado, podem ser criados, mas a tool retorna um
  **aviso explícito** de que o código rodará de fato no frontend. Injeção de
  `<script>` em widgets normais ou em `custom_css` é **sempre recusada**,
  independentemente do toggle.
- ✅ **Rotação e revogação** de token a um clique.

Encontrou algo? Reporte de forma responsável (não abra issue pública com
detalhes sensíveis).

---

## ⚠️ Nota importante sobre anti-RCE no Elementor

> O Elementor **não possui** uma etapa de assinatura de código como o Bricks
> Builder. Isso significa que widgets `html` e `shortcode`, quando presentes na
> página, **executam imediatamente** no frontend — não há aprovação humana no
> builder. Por isso o bloqueio anti-RCE é **ligado por padrão** e deve ser
> desligado apenas com plena consciência do risco.

---

## 🗺️ Roadmap

Já entregue nesta versão inicial:

- **0.1.0** — Servidor MCP completo com 26 tools, gravação via Document API
  do Elementor, Code Guard anti-RCE, introspecção de widgets, estilos globais
  (Kit), skill pública e painel administrativo AJAX.

Planejado para versões futuras (sujeito a ajustes):

- **0.2.x** — Escrita de presets de tipografia global e estilos de tema.
- **0.3.x** — Suporte a Custom Post Types e Dynamic Tags básicos.
- **0.4.x** — Multi-token (um token por agente/ambiente).
- **1.0.0** — Estabilização, cobertura de testes automatizados e publicação no
  diretório WordPress.org.

> O versionamento segue [SemVer](https://semver.org/lang/pt-BR/). Cada mudança
> é registrada em **[CHANGELOG.md](marreira-mcp-elementor/CHANGELOG.md)**.

---

## 🧾 Versionamento & Changelog

- Versão atual: **0.1.0**.
- A versão precisa ficar **sincronizada em três lugares**:
  1. Header `Version:` + constante `MME_VERSION` em `marreira-mcp-elementor.php`.
  2. `Stable tag:` em `readme.txt`.
  3. Nova entrada em `CHANGELOG.md` (espelhada em `readme.txt`).
- Toda alteração incrementa a versão e é documentada no
  [CHANGELOG](marreira-mcp-elementor/CHANGELOG.md).

---

## 🤝 Contribuição

Sugestões, issues e PRs são bem-vindos. Antes de contribuir:

1. Abra uma issue descrevendo a ideia/bug.
2. Siga os padrões de código do projeto (prefixos `mme_`/`MME_`, sanitização/
   escape, pt-BR acentuado na interface).
3. Atualize o **CHANGELOG** e o **SKILL.md** quando o comportamento mudar.

---

## 📄 Licença

**Software livre e gratuito.** Você pode **usar, copiar, modificar e distribuir**
este plugin à vontade — inclusive em projetos comerciais — **desde que mantenha
os devidos créditos** ao autor original (**Paulo Marreira / Marreira Digital**).

Formalmente licenciado sob **GPL-2.0-or-later** (exigência do diretório
WordPress.org), que já garante essas liberdades e a preservação dos avisos de
autoria/licença ao redistribuir. Mantenha o cabeçalho do plugin e este crédito.

Resumo: faça o que quiser com o código, só **dê o crédito**. 🙌

---

<div align="center">

Feito com ⚡ por **[Paulo Marreira](https://marreiradigital.com.br)** · MarreiraDigital

</div>
