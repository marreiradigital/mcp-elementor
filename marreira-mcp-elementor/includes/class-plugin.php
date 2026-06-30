<?php
/**
 * Bootstrap central do plugin.
 *
 * @package Marreira\MCP_Elementor
 */

namespace Marreira\MCP_Elementor;

use Marreira\MCP_Elementor\MCP\MCP_Server;
use Marreira\MCP_Elementor\Admin\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Singleton que registra os modulos e seus hooks.
 */
final class Plugin {

	/**
	 * Instancia unica.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Servidor MCP.
	 *
	 * @var MCP_Server|null
	 */
	private $mcp_server = null;

	/**
	 * Retorna a instancia unica.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Construtor privado (singleton).
	 */
	private function __construct() {}

	/**
	 * Inicializa o plugin.
	 *
	 * @return void
	 */
	public function boot() {
		// Servidor MCP: registra a rota REST oculta e o dispatch JSON-RPC.
		$this->mcp_server = new MCP_Server();
		$this->mcp_server->register_hooks();

		// Tela de administracao (somente no admin).
		if ( is_admin() ) {
			( new Admin() )->register_hooks();
		}

		// i18n.
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Carrega as traducoes.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'marreira-mcp-elementor', false, dirname( MME_PLUGIN_BASENAME ) . '/languages' );
	}

	/**
	 * Indica se o Elementor esta ativo.
	 *
	 * @return bool
	 */
	public static function is_elementor_active() {
		return defined( 'ELEMENTOR_VERSION' );
	}
}
