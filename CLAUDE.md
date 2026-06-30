# MarreiraMCP Elementor — regras do projeto

Plugin WordPress que expõe um servidor **MCP (Model Context Protocol)** para
criar e editar páginas/templates do **Elementor** de forma nativa via IA.
Destino: diretório oficial **WordPress.org**.

Código do plugin em [`marreira-mcp-elementor/`](marreira-mcp-elementor/).

## 1. Versionamento automático (OBRIGATÓRIO)

O plugin começou em **0.1.0**. **A cada modificação lógica** do código do
plugin, incremente a versão e registre no changelog — não espere o usuário
pedir.

- **patch** (`0.x.x`) para correções e ajustes pequenos.
- **minor** (`0.x.0`) para novas features/tools.
- **major** (`x.0.0`) só para quebras de compatibilidade.

A versão precisa ficar **sincronizada nos três lugares**, sempre juntos no
mesmo commit:

1. Header `Version:` em [`marreira-mcp-elementor/marreira-mcp-elementor.php`](marreira-mcp-elementor/marreira-mcp-elementor.php)
   **e** a constante `MME_VERSION` logo abaixo.
2. `Stable tag:` em [`marreira-mcp-elementor/readme.txt`](marreira-mcp-elementor/readme.txt).
3. Nova entrada em [`marreira-mcp-elementor/CHANGELOG.md`](marreira-mcp-elementor/CHANGELOG.md)
   **e** na seção `== Changelog ==` do `readme.txt`.

A entrada do changelog descreve o **porquê** da mudança (não só o "o quê"),
no formato Keep a Changelog (Adicionado/Alterado/Corrigido/Removido).

## 2. Compatibilidade round-trip com o Elementor (inquebrável)

Toda escrita no Elementor é **read-modify-write**: ler o estado atual via
Document API, alterar só o necessário e regravar preservando IDs e campos
desconhecidos. Nunca sobrescrever a árvore cega. Páginas criadas pela IA têm
que abrir no editor Elementor e vice-versa.

- **Prefira sempre o Document API** do Elementor
  (`\Elementor\Plugin::$instance->documents->get($id)->save(...)`) para
  gravações — ele roda kses, regeneração de CSS e versão automaticamente.
  O fallback direto em postmeta (`_elementor_data`) só deve ser usado quando
  o Document API não estiver disponível.
- **Centralize** qualquer acesso ao Elementor em `Elementor_Gateway`
  (`includes/elementor/class-elementor-gateway.php`) e a manipulação de
  árvore aninhada em `Element_Tree` (`includes/elementor/class-element-tree.php`)
  — não duplicar leitura/escrita de postmeta em outros lugares.
- A árvore do Elementor é **aninhada** (cada nó tem `elements[]`), não plana
  como no Bricks. Qualquer travessia/modificação vai em `Element_Tree`.

## 3. Segurança não-negociável

- Endpoints **fora** do índice público de `/wp-json/` (`show_in_index => false`
  + filtros de índice).
- Escrita só com token válido + capability do usuário de serviço. Nunca
  `permission_callback => __return_true` em escrita.
- `Code_Guard` recusa execução de código (anti-RCE). O Elementor **não possui
  etapa de assinatura** — widgets `html` e `shortcode` executam diretamente no
  frontend. Com o toggle **ligado (padrão)**, eles são recusados (403). Com o
  toggle desligado, são permitidos, mas a tool **sempre retorna aviso**. Injeção
  de `<script>` em widgets normais ou `custom_css` é recusada em qualquer caso.
  Não criar caminhos que contornem isso.
- Token apenas como hash SHA-256, em option não-autoload.

## 4. Padrões de código (WordPress.org)

- Prefixo `mme_` / `MME_` em options, hooks e constantes; namespace
  `Marreira\MCP_Elementor`.
- Sanitizar toda entrada; escapar toda saída no admin (`esc_html`, `esc_attr`,
  `esc_url`). Texto de UI em **pt-BR com acentuação correta** (á, é, ç, ã…).
  Comentários de código em pt-BR **sem acentos** (evita problemas de encoding).
- Sem execução de código arbitrário, sem `eval`, sem assets minificados sem
  fonte. Licença GPL-2.0-or-later.
- Validar antes de commitar: `php -l` nos arquivos tocados (e Plugin Check
  quando possível).

## 5. Commits

Seguir a disciplina de commits incrementais (um commit por preocupação,
header em pt-BR sem acentos, conventional commits, corpo explicando o porquê).
O bump de versão + changelog entra **no mesmo commit** da mudança que o motivou.
