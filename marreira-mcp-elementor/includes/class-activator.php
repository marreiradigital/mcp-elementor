<?php
/**
 * Rotinas de ativacao e desativacao.
 *
 * @package Marreira\MCP_Elementor
 */

namespace Marreira\MCP_Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ciclo de vida do plugin. Na v0.1.0 nao cria tabelas: apenas semeia
 * as options padrao de seguranca e marca a versao instalada.
 */
class Activator {

	/**
	 * Option que guarda os toggles de seguranca/configuracao.
	 *
	 * @var string
	 */
	const SETTINGS_OPTION = MME_OPTION_PREFIX . 'settings';

	/**
	 * Option que guarda a versao do plugin instalada (para futuras migracoes).
	 *
	 * @var string
	 */
	const VERSION_OPTION = MME_OPTION_PREFIX . 'version';

	/**
	 * Defaults seguros das configuracoes.
	 *
	 * @return array
	 */
	public static function default_settings() {
		return array(
			'https_only'      => true,  // Exige HTTPS no endpoint MCP.
			'rate_limit'      => 60,    // Requisicoes por janela.
			'rate_window'     => 60,    // Janela em segundos.
			'block_code'      => true,  // Recusa elementos/settings que executam codigo.
			'service_user_id' => 0,     // Usuario de servico para as capabilities.
		);
	}

	/**
	 * Ativacao.
	 *
	 * @return void
	 */
	public static function activate() {
		// Semeia configuracoes apenas se ainda nao existirem (nao sobrescreve).
		if ( false === get_option( self::SETTINGS_OPTION, false ) ) {
			// Option nao-autoload por conter dados de seguranca.
			add_option( self::SETTINGS_OPTION, self::default_settings(), '', false );
		}

		update_option( self::VERSION_OPTION, MME_VERSION, false );

		// Garante que as rotas REST sejam reconhecidas.
		flush_rewrite_rules();
	}

	/**
	 * Desativacao.
	 *
	 * @return void
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
