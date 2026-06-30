<?php
/**
 * Admin: menu proprio (top-level) + painel 100% AJAX no estilo do Elementor.
 *
 * @package Marreira\MCP_Elementor
 */

namespace Marreira\MCP_Elementor\Admin;

use Marreira\MCP_Elementor\Activator;
use Marreira\MCP_Elementor\Auth\Token_Manager;
use Marreira\MCP_Elementor\MCP\MCP_Server;
use Marreira\MCP_Elementor\Elementor\Elementor_Gateway;
use Marreira\MCP_Elementor\Elementor\Global_Styles;
use Marreira\MCP_Elementor\Elementor\Code_Guard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Painel administrativo com menu proprio e interacoes via AJAX (admin-ajax),
 * sem recarregar a pagina.
 */
class Admin {

	const MENU_SLUG  = 'marreira-mcp-elementor';
	const NONCE      = 'mme_admin';
	const CAP        = 'manage_options';

	/**
	 * Hook da pagina (para carregar assets so nela).
	 *
	 * @var string
	 */
	private $page_hook = '';

	/**
	 * Registra hooks de admin e AJAX.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		add_action( 'wp_ajax_mme_status', array( $this, 'ajax_status' ) );
		add_action( 'wp_ajax_mme_save_settings', array( $this, 'ajax_save_settings' ) );
		add_action( 'wp_ajax_mme_generate_token', array( $this, 'ajax_generate_token' ) );
		add_action( 'wp_ajax_mme_revoke_token', array( $this, 'ajax_revoke_token' ) );
		add_action( 'wp_ajax_mme_selftest', array( $this, 'ajax_selftest' ) );
	}

	/**
	 * Cria o menu de topo proprio + submenu.
	 *
	 * @return void
	 */
	public function add_menu() {
		$this->page_hook = add_menu_page(
			__( 'MarreiraMCP Elementor', 'marreira-mcp-elementor' ),
			__( 'MarreiraMCP', 'marreira-mcp-elementor' ),
			self::CAP,
			self::MENU_SLUG,
			array( $this, 'render' ),
			$this->menu_icon(),
			58 // logo abaixo de "Aparencia".
		);

		// Submenu apontando para a mesma SPA (abas internas via AJAX).
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Painel', 'marreira-mcp-elementor' ),
			__( 'Painel', 'marreira-mcp-elementor' ),
			self::CAP,
			self::MENU_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Icone do menu (SVG inline em data URI, grade de quadrados).
	 *
	 * @return string
	 */
	private function menu_icon() {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="#a7aaad">'
			. '<rect x="1" y="1" width="8" height="8" rx="1.5"/>'
			. '<rect x="11" y="1" width="8" height="8" rx="1.5"/>'
			. '<rect x="1" y="11" width="8" height="8" rx="1.5"/>'
			. '<rect x="11" y="11" width="8" height="8" rx="1.5"/></svg>';
		return 'data:image/svg+xml;base64,' . base64_encode( $svg );
	}

	/**
	 * Enfileira CSS/JS apenas na nossa pagina.
	 *
	 * @param string $hook Hook atual.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		if ( $hook !== $this->page_hook ) {
			return;
		}
		wp_enqueue_style( 'mme-admin', MME_PLUGIN_URL . 'assets/admin.css', array(), MME_VERSION );
		wp_enqueue_script( 'mme-admin', MME_PLUGIN_URL . 'assets/admin.js', array(), MME_VERSION, true );
		wp_localize_script(
			'mme-admin',
			'MME',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( self::NONCE ),
			)
		);
	}

	/**
	 * Shell da SPA. Todo o conteudo e renderizado pelo JS via AJAX.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Acesso negado.', 'marreira-mcp-elementor' ) );
		}
		echo '<div class="mme-app" id="mme-app"><div class="mme-loading">' . esc_html__( 'Carregando painel…', 'marreira-mcp-elementor' ) . '</div></div>';
	}

	// ---------------------------------------------------------------------
	// AJAX
	// ---------------------------------------------------------------------

	/**
	 * Valida nonce + capability em toda chamada AJAX.
	 *
	 * @return void
	 */
	private function verify() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_send_json_error( array( 'message' => __( 'Acesso negado.', 'marreira-mcp-elementor' ) ), 403 );
		}
		check_ajax_referer( self::NONCE );
	}

	/**
	 * Monta o payload de status do painel.
	 *
	 * @return array
	 */
	private function status_payload() {
		$settings = wp_parse_args( get_option( Activator::SETTINGS_OPTION, array() ), Activator::default_settings() );
		$meta     = Token_Manager::meta();

		$service_user    = (int) $settings['service_user_id'];
		$service_user_ok = $service_user > 0 && get_userdata( $service_user ) && user_can( $service_user, 'edit_pages' );

		// Catalogo de tools (fonte unica reusada do servidor MCP).
		$definitions = MCP_Server::build_registry()->definitions();

		// Usuarios candidatos a usuario de servico.
		$users = array();
		foreach ( get_users( array( 'number' => 200, 'fields' => array( 'ID', 'display_name' ) ) ) as $u ) {
			if ( user_can( $u->ID, 'edit_pages' ) ) {
				$users[] = array( 'id' => (int) $u->ID, 'name' => $u->display_name );
			}
		}

		// Deteccao do Elementor (sem acoplar a classes do plugin).
		$elementor_active  = defined( 'ELEMENTOR_VERSION' );
		$elementor_version = $elementor_active ? ELEMENTOR_VERSION : null;
		$elementor_pro     = defined( 'ELEMENTOR_PRO_VERSION' );
		$elementor_pro_ver = $elementor_pro ? ELEMENTOR_PRO_VERSION : null;

		// Contagens reais (so quando o Elementor esta ativo, para evitar custo a toa).
		$pages         = 0;
		$templates     = 0;
		$global_colors = 0;
		if ( $elementor_active ) {
			try {
				$pages     = count( Elementor_Gateway::list_pages( array( 'limit' => 200 ) ) );
				$templates = count( Elementor_Gateway::list_templates() );
				if ( Global_Styles::available() ) {
					$colors        = Global_Styles::list_global_colors();
					$global_colors = count( $colors['system_colors'] ) + count( $colors['custom_colors'] );
				}
			} catch ( \Throwable $e ) {
				// Mantem zeros em caso de falha.
			}
		}

		return array(
			'plugin_version'      => MME_VERSION,
			'mcp_protocol'        => MME_MCP_PROTOCOL_VERSION,
			'elementor_active'    => $elementor_active,
			'elementor_version'   => $elementor_version,
			'elementor_pro'       => $elementor_pro,
			'elementor_pro_ver'   => $elementor_pro_ver,
			'code_blocking'       => Code_Guard::is_blocking(),
			'endpoint_url'        => esc_url_raw( rest_url( MME_REST_NAMESPACE . MME_REST_ROUTE ) ),
			'skill_url'           => esc_url_raw( rest_url( MME_REST_NAMESPACE . '/skill' ) ),
			'index_hidden'        => true,
			'has_token'           => Token_Manager::has_token(),
			'token_last_used'     => ! empty( $meta['last_used'] ) ? wp_date( 'd/m/Y H:i', (int) $meta['last_used'] ) : '',
			'token_created'       => ! empty( $meta['created_at'] ) ? wp_date( 'd/m/Y H:i', (int) $meta['created_at'] ) : '',
			'settings'            => array(
				'service_user_id' => $service_user,
				'https_only'      => ! empty( $settings['https_only'] ),
				'block_code'      => ! empty( $settings['block_code'] ),
				'rate_limit'      => (int) $settings['rate_limit'],
				'rate_window'     => (int) $settings['rate_window'],
			),
			'service_user_ok'     => (bool) $service_user_ok,
			'users'               => $users,
			'counts'              => array(
				'pages'         => $pages,
				'templates'     => $templates,
				'global_colors' => $global_colors,
				'tools'         => count( $definitions ),
			),
			'tools'               => $definitions,
		);
	}

	/**
	 * AJAX: status do painel.
	 *
	 * @return void
	 */
	public function ajax_status() {
		$this->verify();
		wp_send_json_success( $this->status_payload() );
	}

	/**
	 * AJAX: salvar configuracoes.
	 *
	 * @return void
	 */
	public function ajax_save_settings() {
		$this->verify();

		$settings = wp_parse_args( get_option( Activator::SETTINGS_OPTION, array() ), Activator::default_settings() );

		$settings['service_user_id'] = isset( $_POST['service_user_id'] ) ? absint( wp_unslash( $_POST['service_user_id'] ) ) : 0;
		$settings['https_only']      = ! empty( $_POST['https_only'] ) && 'false' !== $_POST['https_only'];
		$settings['block_code']      = ! empty( $_POST['block_code'] ) && 'false' !== $_POST['block_code'];
		$settings['rate_limit']      = isset( $_POST['rate_limit'] ) ? max( 0, absint( wp_unslash( $_POST['rate_limit'] ) ) ) : 60;
		$settings['rate_window']     = isset( $_POST['rate_window'] ) ? max( 1, absint( wp_unslash( $_POST['rate_window'] ) ) ) : 60;

		update_option( Activator::SETTINGS_OPTION, $settings, false );
		wp_send_json_success( $this->status_payload() );
	}

	/**
	 * AJAX: gerar/rotacionar token (retorna o token uma unica vez).
	 *
	 * @return void
	 */
	public function ajax_generate_token() {
		$this->verify();
		$token = Token_Manager::generate();
		wp_send_json_success(
			array(
				'token'  => $token,
				'status' => $this->status_payload(),
			)
		);
	}

	/**
	 * AJAX: revogar token.
	 *
	 * @return void
	 */
	public function ajax_revoke_token() {
		$this->verify();
		Token_Manager::revoke();
		wp_send_json_success( $this->status_payload() );
	}

	/**
	 * AJAX: autoteste interno (executa get_capabilities pela fonte unica).
	 *
	 * @return void
	 */
	public function ajax_selftest() {
		$this->verify();
		$registry = MCP_Server::build_registry();
		$result   = $registry->call( 'get_capabilities', array() );
		$ok       = empty( $result['isError'] );
		$text     = isset( $result['content'][0]['text'] ) ? $result['content'][0]['text'] : '';
		wp_send_json_success(
			array(
				'ok'   => $ok,
				'data' => json_decode( $text, true ),
			)
		);
	}
}
