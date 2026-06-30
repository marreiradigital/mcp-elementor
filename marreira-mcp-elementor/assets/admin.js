/**
 * MarreiraMCP Elementor — painel admin 100% AJAX (sem reloads).
 */
( function () {
	'use strict';

	var API = ( window.MME && MME.ajaxurl ) || '';
	var NONCE = ( window.MME && MME.nonce ) || '';
	var root = document.getElementById( 'mme-app' );
	if ( ! root ) { return; }

	var state = { status: null, tab: 'painel', flashToken: null };

	// ---- utils ----
	function esc( s ) {
		return String( s == null ? '' : s ).replace( /[&<>"']/g, function ( c ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ];
		} );
	}

	function post( action, extra ) {
		var fd = new FormData();
		fd.append( 'action', action );
		fd.append( '_ajax_nonce', NONCE );
		if ( extra ) {
			Object.keys( extra ).forEach( function ( k ) {
				var v = extra[ k ];
				if ( typeof v === 'boolean' ) { v = v ? '1' : '0'; }
				fd.append( k, v );
			} );
		}
		return fetch( API, { method: 'POST', credentials: 'same-origin', body: fd } )
			.then( function ( r ) { return r.json(); } );
	}

	var toastEl;
	function toast( msg, type ) {
		if ( ! toastEl ) {
			toastEl = document.createElement( 'div' );
			toastEl.className = 'mme-toast';
			document.body.appendChild( toastEl );
		}
		toastEl.textContent = msg;
		toastEl.className = 'mme-toast ' + ( type || '' );
		// reflow para reiniciar a animacao
		void toastEl.offsetWidth;
		toastEl.classList.add( 'show' );
		clearTimeout( toastEl._t );
		toastEl._t = setTimeout( function () { toastEl.classList.remove( 'show' ); }, 2200 );
	}

	function copy( text, btn ) {
		var done = function () {
			if ( btn ) {
				var o = btn.textContent; btn.textContent = 'Copiado!';
				setTimeout( function () { btn.textContent = o; }, 1500 );
			}
			toast( 'Copiado para a área de transferência.', 'ok' );
		};
		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( text ).then( done, function () { toast( 'Falha ao copiar.', 'err' ); } );
		} else {
			var ta = document.createElement( 'textarea' ); ta.value = text; document.body.appendChild( ta );
			ta.select(); try { document.execCommand( 'copy' ); done(); } catch ( e ) { toast( 'Falha ao copiar.', 'err' ); }
			document.body.removeChild( ta );
		}
	}

	// ---- render ----
	function badge( cls, label ) { return '<span class="mme-badge ' + cls + '">' + esc( label ) + '</span>'; }

	function topbar() {
		var s = state.status;
		var b = '';
		b += s.elementor_active ? badge( 'is-on', '● Elementor ' + ( s.elementor_version || 'ativo' ) ) : badge( 'is-off', '○ Elementor inativo' );
		b += s.elementor_pro ? badge( 'is-on', '● Pro ' + ( s.elementor_pro_ver || '' ) ) : badge( 'is-info', '○ Pro não instalado' );
		b += s.has_token ? badge( 'is-on', '● Token ativo' ) : badge( 'is-off', '○ Sem token' );
		b += s.service_user_ok ? badge( 'is-on', '● Usuário OK' ) : badge( 'is-warn', '▲ Defina o usuário' );
		b += badge( 'is-info', 'v' + esc( s.plugin_version ) );
		return '<header class="mme-topbar">' +
			'<div class="mme-logo" aria-hidden="true"><span></span><span></span><span></span><span></span></div>' +
			'<div><h1>MarreiraMCP Elementor</h1><p class="mme-sub">Servidor MCP para criar e editar páginas Elementor via IA</p></div>' +
			'<div class="mme-badges">' + b + '</div></header>';
	}

	var TABS = [
		[ 'painel', 'Painel' ],
		[ 'conexao', 'Conexão' ],
		[ 'seguranca', 'Segurança' ],
		[ 'ferramentas', 'Ferramentas' ],
	];

	function tabsNav() {
		return '<nav class="mme-tabs">' + TABS.map( function ( t ) {
			return '<button class="mme-tab' + ( state.tab === t[ 0 ] ? ' is-active' : '' ) + '" data-tab="' + t[ 0 ] + '">' + esc( t[ 1 ] ) + '</button>';
		} ).join( '' ) + '</nav>';
	}

	function stat( icon, num, label ) {
		return '<div class="mme-stat"><span class="mme-stat-icon">' + icon + '</span>' +
			'<span class="mme-stat-num">' + esc( num ) + '</span>' +
			'<span class="mme-stat-label">' + esc( label ) + '</span></div>';
	}

	function viewPainel() {
		var s = state.status, c = s.counts;
		var stats = '<div class="mme-stats">' +
			stat( '📄', c.pages, 'Páginas Elementor' ) +
			stat( '🧩', c.templates, 'Templates' ) +
			stat( '🎨', c.global_colors, 'Cores globais' ) +
			stat( '🛠️', c.tools, 'Ferramentas MCP' ) + '</div>';

		var env = '<section class="mme-card"><h2>Ambiente</h2>' +
			'<dl class="mme-kv">' +
			'<dt>Elementor</dt><dd>' + ( s.elementor_active ? esc( s.elementor_version || 'ativo' ) : 'inativo' ) + '</dd>' +
			'<dt>Elementor Pro</dt><dd>' + ( s.elementor_pro ? esc( s.elementor_pro_ver || 'ativo' ) : 'não instalado' ) + '</dd>' +
			'<dt>Anti-RCE</dt><dd>' + ( s.code_blocking ? 'ativo (bloqueando código)' : 'desativado' ) + '</dd>' +
			'<dt>Protocolo MCP</dt><dd>' + esc( s.mcp_protocol ) + '</dd>' +
			'<dt>Índice /wp-json</dt><dd>endpoints ocultos ✔</dd>' +
			'</dl>' +
			'<div class="mme-actions"><button class="mme-btn mme-btn-primary" data-action="selftest">Testar conexão interna</button></div>' +
			'<div id="mme-selftest"></div></section>';

		return '<div class="mme-grid cols-2">' +
			'<section class="mme-card"><h2>Visão geral</h2><p class="mme-hint">Resumo do que a IA pode gerenciar neste site.</p>' + stats + '</section>' +
			env + '</div>';
	}

	function viewConexao() {
		var s = state.status;
		var flash = '';
		if ( state.flashToken ) {
			flash = '<div class="mme-flash"><h2>Token gerado — copie agora!</h2>' +
				'<p class="mme-hint">Exibido apenas uma vez; o WordPress guarda só o hash.</p>' +
				'<div class="mme-copy-row"><code class="mme-code is-token" id="mme-tok">' + esc( state.flashToken ) + '</code>' +
				'<button class="mme-btn mme-copy" data-copy="#mme-tok">Copiar</button></div></div>';
			state.flashToken = null;
		}

		var curl = 'curl -X POST "' + s.endpoint_url + '" \\\n' +
			'  -H "Content-Type: application/json" \\\n' +
			'  -H "Authorization: Bearer SEU_TOKEN" \\\n' +
			"  -d '{\"jsonrpc\":\"2.0\",\"id\":1,\"method\":\"tools/list\",\"params\":{}}'";

		var tokenMeta = s.has_token
			? '<dl class="mme-kv"><dt>Criado</dt><dd>' + esc( s.token_created || '—' ) + '</dd><dt>Último uso</dt><dd>' + esc( s.token_last_used || 'nunca' ) + '</dd></dl>'
			: '<p class="mme-hint">Nenhum token configurado.</p>';

		var tokenBtns = '<div class="mme-actions">' +
			'<button class="mme-btn mme-btn-primary" data-action="gen-token">' + ( s.has_token ? 'Rotacionar token' : 'Gerar token' ) + '</button>' +
			( s.has_token ? '<button class="mme-btn mme-btn-danger" data-action="revoke-token">Revogar</button>' : '' ) +
			'</div>';

		return flash + '<div class="mme-grid cols-2">' +
			'<section class="mme-card"><h2>Endpoint MCP</h2>' +
			'<div class="mme-field"><label>URL (POST, JSON-RPC 2.0)</label>' +
			'<div class="mme-copy-row"><code class="mme-code" id="mme-ep">' + esc( s.endpoint_url ) + '</code><button class="mme-btn mme-copy" data-copy="#mme-ep">Copiar</button></div></div>' +
			'<div class="mme-field"><label>Autenticação</label><code class="mme-code">Authorization: Bearer &lt;token&gt;</code></div>' +
			'<div class="mme-field"><label>Exemplo (cURL)</label><div class="mme-copy-row"><code class="mme-code" id="mme-curl">' + esc( curl ) + '</code><button class="mme-btn mme-copy" data-copy="#mme-curl">Copiar</button></div></div>' +
			'</section>' +
			'<section class="mme-card"><h2>Token de acesso</h2><p class="mme-hint">Autentica o agente de IA. Guardado apenas como hash.</p>' + tokenMeta + tokenBtns + '</section>' +
			'<section class="mme-card mme-span-2"><h2>Skill para a IA</h2>' +
			'<p class="mme-hint">URL pública (somente leitura) com a documentação do plugin em Markdown. Copie e mande para a IA/IDE ler como referência do que é possível fazer — útil quando a skill ainda não está baixada.</p>' +
			'<div class="mme-copy-row"><code class="mme-code" id="mme-skill">' + esc( s.skill_url ) + '</code><button class="mme-btn mme-copy" data-copy="#mme-skill">Copiar</button></div></section>' +
			'</div>';
	}

	function viewSeguranca() {
		var st = state.status.settings;
		var users = state.status.users.map( function ( u ) {
			return '<option value="' + u.id + '"' + ( u.id === st.service_user_id ? ' selected' : '' ) + '>' + esc( u.name ) + '</option>';
		} ).join( '' );

		return '<div class="mme-grid cols-2">' +
			'<section class="mme-card"><h2>Proteções</h2><p class="mme-hint">Aplicadas a cada requisição. Salvas automaticamente.</p>' +
			toggle( 'https_only', 'Exigir HTTPS', 'Recusa chamadas sem TLS.', st.https_only ) +
			toggle( 'block_code', 'Bloquear widgets de código (anti-RCE)', 'Ligado: recusa os widgets HTML e Shortcode. Desligado: a IA pode criar esses widgets, mas no Elementor NÃO há etapa de assinatura — eles executam direto no frontend. Injeção de script em outros widgets continua sempre bloqueada.', st.block_code ) +
			'<div class="mme-field"><label for="mme-rl">Limite de requisições</label>' +
			'<div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">' +
			'<input type="number" min="0" id="mme-rl" value="' + st.rate_limit + '"> <span class="mme-toggle-sub">por</span> ' +
			'<input type="number" min="1" id="mme-rw" value="' + st.rate_window + '"> <span class="mme-toggle-sub">segundos</span>' +
			'<button class="mme-btn mme-btn-primary" data-action="save-rate">Salvar</button></div>' +
			'<p class="description">Use 0 no limite para desativar.</p></div>' +
			'</section>' +
			'<section class="mme-card"><h2>Usuário de serviço</h2><p class="mme-hint">As permissões deste usuário definem o que a IA pode fazer.</p>' +
			'<div class="mme-field"><label for="mme-user">Usuário</label><select id="mme-user" data-action="save-user">' +
			'<option value="0">— selecione —</option>' + users + '</select>' +
			'<p class="description">' + ( state.status.service_user_ok ? 'Usuário válido com permissão de edição. ✔' : 'Escolha um usuário com permissão de editar páginas.' ) + '</p></div>' +
			'</section></div>';
	}

	function toggle( id, label, sub, checked ) {
		return '<label class="mme-toggle"><input type="checkbox" data-toggle="' + id + '"' + ( checked ? ' checked' : '' ) + '>' +
			'<span class="mme-switch" aria-hidden="true"></span>' +
			'<span><span class="mme-toggle-label">' + esc( label ) + '</span><span class="mme-toggle-sub">' + esc( sub ) + '</span></span></label>';
	}

	function viewFerramentas() {
		var tools = state.status.tools || [];
		var cards = tools.map( function ( t ) {
			var props = ( t.inputSchema && t.inputSchema.properties ) || {};
			var req = ( t.inputSchema && t.inputSchema.required ) || [];
			var args = Object.keys( props ).map( function ( k ) {
				var isReq = req.indexOf( k ) !== -1;
				return '<span class="mme-arg' + ( isReq ? ' req' : '' ) + '">' + esc( k ) + ( isReq ? '*' : '' ) + '</span>';
			} ).join( '' );
			return '<div class="mme-tool" data-name="' + esc( t.name ) + '">' +
				'<div class="mme-tool-name">' + esc( t.name ) + '</div>' +
				'<div class="mme-tool-desc">' + esc( t.description ) + '</div>' +
				'<div class="mme-tool-args">' + ( args || '<span class="mme-arg">sem argumentos</span>' ) + '</div></div>';
		} ).join( '' );

		return '<section class="mme-card"><h2>Catálogo de ferramentas MCP (' + tools.length + ')</h2>' +
			'<p class="mme-hint">Tudo que a IA pode chamar. Campos com <strong>*</strong> são obrigatórios.</p>' +
			'<input type="search" class="mme-tool-search" id="mme-search" placeholder="Filtrar ferramentas…">' +
			'<div class="mme-tools" id="mme-tool-list">' + cards + '</div></section>';
	}

	function viewFor( tab ) {
		if ( tab === 'conexao' ) { return viewConexao(); }
		if ( tab === 'seguranca' ) { return viewSeguranca(); }
		if ( tab === 'ferramentas' ) { return viewFerramentas(); }
		return viewPainel();
	}

	function render() {
		root.innerHTML = topbar() + tabsNav() +
			'<div class="mme-panel-view" id="mme-view">' + viewFor( state.tab ) + '</div>';
	}

	// ---- actions ----
	function gatherSettings( overrides ) {
		var st = state.status.settings;
		var data = {
			service_user_id: st.service_user_id,
			https_only: st.https_only,
			block_code: st.block_code,
			rate_limit: st.rate_limit,
			rate_window: st.rate_window,
		};
		if ( overrides ) { Object.keys( overrides ).forEach( function ( k ) { data[ k ] = overrides[ k ]; } ); }
		return data;
	}

	function saveSettings( overrides, okMsg ) {
		return post( 'mme_save_settings', gatherSettings( overrides ) ).then( function ( res ) {
			if ( res && res.success ) {
				state.status = res.data;
				toast( okMsg || 'Configurações salvas.', 'ok' );
				render();
			} else {
				toast( ( res && res.data && res.data.message ) || 'Falha ao salvar.', 'err' );
			}
		} ).catch( function () { toast( 'Erro de rede.', 'err' ); } );
	}

	// delegação de eventos
	root.addEventListener( 'click', function ( ev ) {
		var tabBtn = ev.target.closest( '.mme-tab' );
		if ( tabBtn ) { state.tab = tabBtn.getAttribute( 'data-tab' ); render(); return; }

		var copyBtn = ev.target.closest( '[data-copy]' );
		if ( copyBtn ) {
			var el = document.querySelector( copyBtn.getAttribute( 'data-copy' ) );
			if ( el ) { copy( el.textContent, copyBtn ); }
			return;
		}

		var act = ev.target.closest( '[data-action]' );
		if ( ! act ) { return; }
		var action = act.getAttribute( 'data-action' );

		if ( action === 'selftest' ) {
			act.disabled = true;
			post( 'mme_selftest' ).then( function ( res ) {
				act.disabled = false;
				var box = document.getElementById( 'mme-selftest' );
				if ( res && res.success && res.data.ok ) {
					box.innerHTML = '<code class="mme-code is-token" style="margin-top:12px">✔ OK — ' + esc( JSON.stringify( res.data.data ) ) + '</code>';
					toast( 'Conexão interna OK.', 'ok' );
				} else {
					box.innerHTML = '<code class="mme-code" style="margin-top:12px">Falha no autoteste.</code>';
					toast( 'Falha no autoteste.', 'err' );
				}
			} ).catch( function () { act.disabled = false; toast( 'Erro de rede.', 'err' ); } );
		}

		if ( action === 'gen-token' ) {
			if ( state.status.has_token && ! window.confirm( 'Rotacionar o token? O token atual deixará de funcionar.' ) ) { return; }
			act.disabled = true;
			post( 'mme_generate_token' ).then( function ( res ) {
				act.disabled = false;
				if ( res && res.success ) {
					state.status = res.data.status;
					state.flashToken = res.data.token;
					state.tab = 'conexao';
					render();
					toast( 'Token gerado.', 'ok' );
				} else { toast( 'Falha ao gerar token.', 'err' ); }
			} ).catch( function () { act.disabled = false; toast( 'Erro de rede.', 'err' ); } );
		}

		if ( action === 'revoke-token' ) {
			if ( ! window.confirm( 'Revogar o token? Integrações existentes deixarão de funcionar.' ) ) { return; }
			post( 'mme_revoke_token' ).then( function ( res ) {
				if ( res && res.success ) { state.status = res.data; render(); toast( 'Token revogado.', 'ok' ); }
				else { toast( 'Falha ao revogar.', 'err' ); }
			} );
		}

		if ( action === 'save-rate' ) {
			var rl = document.getElementById( 'mme-rl' );
			var rw = document.getElementById( 'mme-rw' );
			saveSettings( { rate_limit: Math.max( 0, parseInt( rl.value, 10 ) || 0 ), rate_window: Math.max( 1, parseInt( rw.value, 10 ) || 1 ) }, 'Limite atualizado.' );
		}
	} );

	root.addEventListener( 'change', function ( ev ) {
		var tg = ev.target.closest( '[data-toggle]' );
		if ( tg ) {
			var key = tg.getAttribute( 'data-toggle' );
			var ov = {}; ov[ key ] = tg.checked;
			saveSettings( ov, tg.checked ? 'Ativado.' : 'Desativado.' );
			return;
		}
		var us = ev.target.closest( '[data-action="save-user"]' );
		if ( us ) { saveSettings( { service_user_id: parseInt( us.value, 10 ) || 0 }, 'Usuário de serviço atualizado.' ); }
	} );

	root.addEventListener( 'input', function ( ev ) {
		if ( ev.target.id === 'mme-search' ) {
			var q = ev.target.value.toLowerCase();
			document.querySelectorAll( '#mme-tool-list .mme-tool' ).forEach( function ( el ) {
				var name = el.getAttribute( 'data-name' ).toLowerCase();
				var txt = el.textContent.toLowerCase();
				el.style.display = ( name.indexOf( q ) !== -1 || txt.indexOf( q ) !== -1 ) ? '' : 'none';
			} );
		}
	} );

	// ---- init ----
	post( 'mme_status' ).then( function ( res ) {
		if ( res && res.success ) { state.status = res.data; render(); }
		else { root.innerHTML = '<div class="mme-loading">Falha ao carregar o painel.</div>'; }
	} ).catch( function () { root.innerHTML = '<div class="mme-loading">Erro de rede ao carregar o painel.</div>'; } );
} )();
