#!/usr/bin/env python3
"""Demo: cria uma pagina Elementor via o endpoint MCP do MarreiraMCP Elementor.

Uso:
    python3 scripts/demo-create-page.py https://SEU-SITE/wp-json/marreira-mcp-elementor/v1/mcp mme_SEU_TOKEN

Sem dependencias externas (usa apenas a stdlib). Monta uma arvore aninhada
(container -> heading + texto + botao) e envia um unico tools/call
create_elementor_page.
"""

import json
import sys
import urllib.request


def rpc(endpoint, token, method, params, req_id=1):
    """Faz uma chamada JSON-RPC 2.0 ao endpoint MCP."""
    payload = json.dumps(
        {"jsonrpc": "2.0", "id": req_id, "method": method, "params": params}
    ).encode("utf-8")

    req = urllib.request.Request(
        endpoint,
        data=payload,
        headers={
            "Content-Type": "application/json",
            "Accept": "application/json",
            "Authorization": "Bearer " + token,
        },
        method="POST",
    )
    with urllib.request.urlopen(req, timeout=30) as resp:
        return json.loads(resp.read().decode("utf-8"))


def build_tree():
    """Arvore de exemplo: 1 container com heading, texto e botao."""
    return [
        {
            "elType": "container",
            "settings": {
                "content_width": "boxed",
                "flex_direction": "column",
                "flex_gap": {"unit": "px", "size": 24},
                "padding": {
                    "unit": "px",
                    "top": "80",
                    "right": "20",
                    "bottom": "80",
                    "left": "20",
                    "isLinked": False,
                },
            },
            "elements": [
                {
                    "elType": "widget",
                    "widgetType": "heading",
                    "settings": {
                        "title": "Pagina criada via MCP",
                        "header_size": "h1",
                        "align": "center",
                    },
                    "elements": [],
                },
                {
                    "elType": "widget",
                    "widgetType": "text-editor",
                    "settings": {
                        "editor": "<p>Esta pagina foi gerada pelo MarreiraMCP Elementor.</p>",
                        "align": "center",
                    },
                    "elements": [],
                },
                {
                    "elType": "widget",
                    "widgetType": "button",
                    "settings": {
                        "text": "Saiba mais",
                        "align": "center",
                        "link": {"url": "#"},
                    },
                    "elements": [],
                },
            ],
        }
    ]


def main():
    if len(sys.argv) < 3:
        print(__doc__)
        sys.exit(1)

    endpoint, token = sys.argv[1], sys.argv[2]

    # 1) Handshake.
    init = rpc(
        endpoint,
        token,
        "initialize",
        {
            "protocolVersion": "2025-03-26",
            "capabilities": {},
            "clientInfo": {"name": "demo-create-page", "version": "1.0"},
        },
        req_id=1,
    )
    print("initialize:", json.dumps(init.get("result", init), ensure_ascii=False))

    # 2) Cria a pagina.
    result = rpc(
        endpoint,
        token,
        "tools/call",
        {
            "name": "create_elementor_page",
            "arguments": {
                "title": "Demo MCP Elementor",
                "status": "draft",
                "elements": build_tree(),
            },
        },
        req_id=2,
    )

    content = result.get("result", {}).get("content", [])
    text = content[0]["text"] if content else json.dumps(result, ensure_ascii=False)
    print("create_elementor_page:\n", text)


if __name__ == "__main__":
    main()
