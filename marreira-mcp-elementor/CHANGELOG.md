# Changelog

Todas as mudanças relevantes deste projeto são documentadas aqui.

O formato segue [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/)
e o projeto adota [Versionamento Semântico](https://semver.org/lang/pt-BR/).

A cada modificação, a versão é incrementada e sincronizada em três lugares:
o header `Version:` do arquivo principal, o `Stable tag:` do `readme.txt` e
uma nova entrada neste arquivo (espelhada na seção `== Changelog ==` do
`readme.txt`).

## [0.1.0] - 2026-06-29

### Adicionado

- **Servidor MCP** (Model Context Protocol) sobre uma rota REST oculta
  (`marreira-mcp-elementor/v1/mcp`), falando JSON-RPC 2.0 (`initialize`,
  `tools/list`, `tools/call`, `ping`). A rota é removida do índice público de
  `/wp-json/` via `show_in_index => false` e filtros de `rest_index` /
  `rest_namespace_index`.

- **Autenticação por token Bearer** (apenas o hash SHA-256 é armazenado, em
  option não-autoload), com HTTPS obrigatório, rate limiting por token e
  adoção de um **usuário de serviço** para as checagens de capability.

- **26 tools MCP** organizadas em cinco grupos:
  - *Páginas* (`list_pages`, `get_page`, `create_elementor_page`,
    `update_elementor_page`, `set_page_settings`, `delete_page`).
  - *Templates* (`list_templates`, `create_template`, `update_template`,
    `set_template_conditions`).
  - *Elementos* (`insert_element`, `update_element_settings`, `move_element`,
    `delete_element`, `duplicate_element`).
  - *Estilos globais* (`list_global_colors`, `list_global_fonts`,
    `get_kit_settings`, `upsert_global_color`, `delete_global_color`,
    `upsert_global_font`).
  - *Utilitários* (`get_capabilities`, `validate_tree`, `regenerate_css`,
    `list_elements`, `get_element_schema`).

- **Gravação via Document API do Elementor**
  (`\Elementor\Plugin::$instance->documents->get($id)->save(...)`) como
  caminho principal — garante kses, regeneração de CSS e atualização de versão
  automaticamente. Fallback direto em `_elementor_data` quando o Document API
  não estiver disponível.

- **Compatibilidade bidirecional (round-trip):** páginas criadas pela IA abrem
  no editor Elementor e páginas criadas no editor podem ser refinadas pela IA
  sem corrupção. A árvore aninhada (cada nó com `elements[]`) é lida,
  modificada e regravada preservando IDs e campos desconhecidos.

- **Suporte a layouts modernos e legados:** containers (flexbox/grid,
  `elType: container`) para layouts novos; section/column/widget para editar
  páginas antigas.

- **Elementor Pro — Theme Builder:** `create_template` aceita os tipos
  `header`, `footer`, `single-post`, `single-page`, `archive`,
  `search-results`, `error-404`, `loop-item` e `popup` quando o Pro está
  ativo. `set_template_conditions` define onde cada template aparece via
  `_elementor_conditions`.

- **Guard anti-RCE (`Code_Guard`):** os widgets `html` e `shortcode` executam
  código no frontend e no Elementor não há etapa de assinatura. Com o bloqueio
  **ligado (padrão)**, eles são **recusados (403)**; com o bloqueio desligado,
  são permitidos, mas a tool retorna um **aviso explícito**. Injeção de
  `<script>` em qualquer widget ou em `custom_css` é sempre recusada.

- **Introspecção de widgets:** `list_elements` lista todos os widgets
  registrados (incluindo Pro e de terceiros); `get_element_schema` retorna o
  schema de controles de um widget (tipos, defaults, opções, grupos), lido
  direto do registro do Elementor — somente leitura, sem efeitos colaterais.

- **Estilos globais (Kit ativo):** leitura e escrita de cores globais e
  tipografias globais via `_elementor_global_colors` / `_elementor_global_fonts`
  do Kit; `get_kit_settings` expõe largura do container, breakpoints e demais
  configurações do Kit.

- **Skill pública:** `GET /wp-json/marreira-mcp-elementor/v1/skill` serve o
  `SKILL.md` em Markdown (somente leitura, sem token) para a IA/IDE ler.

- **Painel administrativo 100% AJAX** (sem recarregar a página) com abas
  Painel, Conexão, Segurança e Ferramentas; métricas (páginas Elementor,
  templates, tools), status do ambiente (Elementor, Pro, anti-RCE, protocolo),
  geração/rotação/revogação de token e catálogo de ferramentas com busca.

[0.1.0]: https://marreiradigital.com.br/marreira-mcp-elementor
