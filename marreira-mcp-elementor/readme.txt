=== MarreiraMCP Elementor ===
Contributors: paulomarreira
Tags: elementor, mcp, ai, page builder, rest api
Requires at least: 6.4
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Servidor MCP para criar e editar paginas e templates do Elementor de forma nativa via IA, com autenticacao por token e endpoints ocultos do indice publico.

== Description ==

MarreiraMCP Elementor expoe um servidor **MCP (Model Context Protocol)** dentro do WordPress para que um agente de IA (Claude, Cursor, etc.) possa criar, ler, editar e excluir paginas e templates feitos com o **Elementor**, gerando exatamente o mesmo formato de dados que o editor visual usa (arvore aninhada JSON no `_elementor_data`).

Isso garante **compatibilidade bidirecional**: paginas criadas pela IA abrem e editam normalmente no editor Elementor, e paginas criadas no editor podem ser editadas pela IA sem corromper nada. As gravacoes passam pelo **Document API do Elementor** (kses + regeneracao de CSS + versao automatica).

= Principais recursos =

* Endpoint MCP (JSON-RPC 2.0) sobre uma rota REST **oculta** do indice publico de `/wp-json/`.
* Autenticacao por **token Bearer** (apenas o hash SHA-256 e armazenado), HTTPS obrigatorio e rate limiting.
* Operacoes de pagina: listar, ler, criar, atualizar, definir page settings e excluir.
* Operacoes de template: listar, criar (section, container, page e tipos Pro do Theme Builder), atualizar e definir condicoes de exibicao (Pro).
* Edicao fina de elementos: inserir, atualizar settings, mover, excluir e duplicar na arvore aninhada.
* Estilos globais (Kit): cores globais (listar/criar/atualizar/excluir), tipografias globais e leitura do Kit ativo.
* Introspeccao de widgets: `list_elements` lista todos os widgets registrados; `get_element_schema` retorna o schema de controles de cada widget.
* Utilitarios: capacidades do ambiente, validacao de arvore (dry-run) e regeneracao de CSS.
* **Seguranca em primeiro lugar**: bloqueio anti-RCE (widgets `html`/`shortcode` que executam no frontend sao recusados por padrao), checagem de capabilities e usuario de servico configuravel.

= Elementor Pro =

Com o **Elementor Pro** ativo, a tool `create_template` aceita os tipos do Theme Builder (`header`, `footer`, `single-post`, `single-page`, `archive`, `search-results`, `error-404`, `loop-item`, `popup`) e a tool `set_template_conditions` permite definir as condicoes de exibicao.

= Requisitos =

* Plugin **Elementor** (versao free) ativo.
* WordPress 6.4+ e PHP 7.4+.
* HTTPS no site (recomendado e exigido por padrao).
* Elementor Pro opcional (desbloqueia tipos de template do Theme Builder).

== Installation ==

1. Envie a pasta `marreira-mcp-elementor` para `/wp-content/plugins/` (ou instale o .zip pelo painel).
2. Ative o plugin em **Plugins**.
3. Acesse **Configuracoes > MarreiraMCP Elementor**.
4. Defina um **usuario de servico** (Editor ou Administrador) e gere um **token**.
5. Configure seu cliente MCP com a URL do endpoint e o cabecalho `Authorization: Bearer <token>`.

== Frequently Asked Questions ==

= O endpoint aparece na listagem publica de /wp-json/? =

Nao. A rota e registrada com `show_in_index => false` e o namespace e removido do indice via filtros, ficando fora da descoberta publica.

= A IA pode executar codigo no meu site? =

Por padrao, nao. O plugin recusa qualquer widget que execute codigo no frontend (`html` e `shortcode`) quando o bloqueio anti-RCE esta ligado (configuracao padrao). Com o bloqueio desligado, esses widgets podem ser criados, porem a tool retorna um aviso explicito de que o codigo sera executado de fato no frontend. Injecao de `<script>` em widgets normais ou em `custom_css` e sempre recusada, independentemente do toggle.

= Qual a diferenca do anti-RCE em relacao ao plugin Bricks? =

No Bricks Builder, o elemento de codigo pode ser criado pela IA e requer uma assinatura humana no editor antes de executar. No Elementor, nao ha essa etapa de assinatura — widgets `html` e `shortcode` executam imediatamente. Por isso o bloqueio padrao e mais restritivo: eles sao recusados ate que o administrador conscientemente desabilite a protecao.

= Preciso do Elementor Pro? =

Nao. O plugin funciona integralmente com o Elementor free. O Pro e opcional e desbloqueia os tipos de template do Theme Builder e as condicoes de exibicao.

= Preciso regenerar o CSS apos a IA editar uma pagina? =

Normalmente nao — as gravacoes passam pelo Document API do Elementor, que ja regenera o CSS. Nos casos em que o cache nao for invalidado automaticamente, o plugin expoe a tool `regenerate_css`.

== Changelog ==

= 0.1.0 =
* Versao inicial: servidor MCP sobre rota REST oculta (JSON-RPC 2.0).
* Autenticacao por token Bearer (hash SHA-256), HTTPS obrigatorio e rate limiting.
* 26 tools para paginas, templates, elementos, estilos globais e utilitarios.
* Gravacao via Document API do Elementor (kses + CSS + versao automaticos).
* Edicao fina da arvore aninhada: inserir, mover, duplicar, excluir elementos.
* Guard anti-RCE (html/shortcode bloqueados por padrao; <script> sempre recusado).
* Estilos globais: cores e fontes do Kit ativo (listar/criar/atualizar/excluir).
* Introspeccao de widgets: list_elements + get_element_schema.
* Suporte a templates do Elementor Pro (Theme Builder + condicoes).
* Skill publica: GET /wp-json/marreira-mcp-elementor/v1/skill.
* Painel administrativo 100% AJAX com token, seguranca, metricas e catalogo.

== Upgrade Notice ==

= 0.1.0 =
Versao inicial.
