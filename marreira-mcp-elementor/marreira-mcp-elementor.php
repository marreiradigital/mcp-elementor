<?php
/**
 * Plugin Name:       MarreiraMCP Elementor
 * Plugin URI:        https://marreiradigital.com.br/marreira-mcp-elementor
 * Description:        Servidor MCP (Model Context Protocol) para criar e editar paginas e templates do Elementor e Elementor Pro de forma nativa via IA, com seguranca por token e endpoints ocultos do indice publico do REST.
 * Version:           0.1.1
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            Paulo Marreira
 * Author URI:        https://marreiradigital.com.br
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       marreira-mcp-elementor
 * Domain Path:       /languages
 *
 * @package Marreira\MCP_Elementor
 */

// Bloqueia acesso direto ao arquivo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---------------------------------------------------------------------------
// Constantes do plugin.
// ---------------------------------------------------------------------------
define( 'MME_VERSION', '0.1.1' );
define( 'MME_PLUGIN_FILE', __FILE__ );
define( 'MME_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MME_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MME_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Namespace e rota do endpoint MCP (rota oculta do indice publico).
// Namespace proprio para coexistir com o MarreiraMCP Bricks no mesmo site.
define( 'MME_REST_NAMESPACE', 'marreira-mcp-elementor/v1' );
define( 'MME_REST_ROUTE', '/mcp' );

// Prefixo das options no banco.
define( 'MME_OPTION_PREFIX', 'mme_' );

// Versao do protocolo MCP suportada.
define( 'MME_MCP_PROTOCOL_VERSION', '2025-03-26' );

// ---------------------------------------------------------------------------
// Autoloader simples (PSR-ish) mapeando o namespace para /includes.
// Marreira\MCP_Elementor\Auth\Token_Manager => includes/auth/class-token-manager.php
// ---------------------------------------------------------------------------
spl_autoload_register(
	static function ( $class ) {
		$prefix = 'Marreira\\MCP_Elementor\\';
		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}

		$relative = substr( $class, strlen( $prefix ) );
		$parts    = explode( '\\', $relative );
		$class_nm = array_pop( $parts );

		// Sub-namespaces viram diretorios em minusculo.
		$sub_path = '';
		if ( ! empty( $parts ) ) {
			$sub_path = strtolower( implode( '/', $parts ) ) . '/';
		}

		// Foo_Bar => class-foo-bar.php
		$file_name = 'class-' . strtolower( str_replace( '_', '-', $class_nm ) ) . '.php';
		$file      = MME_PLUGIN_DIR . 'includes/' . $sub_path . $file_name;

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);

// ---------------------------------------------------------------------------
// Hooks de ciclo de vida.
// ---------------------------------------------------------------------------
register_activation_hook( __FILE__, array( '\Marreira\MCP_Elementor\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( '\Marreira\MCP_Elementor\Activator', 'deactivate' ) );

// ---------------------------------------------------------------------------
// Bootstrap.
// ---------------------------------------------------------------------------
add_action(
	'plugins_loaded',
	static function () {
		\Marreira\MCP_Elementor\Plugin::instance()->boot();
	}
);
