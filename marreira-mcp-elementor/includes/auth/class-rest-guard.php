<?php
/**
 * Guarda de seguranca do endpoint MCP (permission_callback).
 *
 * @package Marreira\MCP_Elementor
 */

namespace Marreira\MCP_Elementor\Auth;

use Marreira\MCP_Elementor\Activator;
use WP_Error;
use WP_REST_Request;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Centraliza a autenticacao e o rate limiting do endpoint MCP.
 *
 * Fluxo: HTTPS obrigatorio -> token Bearer valido -> rate limit -> assume
 * o usuario de servico para que as checagens de capability funcionem.
 */
class Rest_Guard {

	/**
	 * permission_callback da rota MCP.
	 *
	 * @param WP_REST_Request $request Requisicao.
	 * @return true|WP_Error
	 */
	public static function check( WP_REST_Request $request ) {
		$settings = self::settings();

		// 1) HTTPS obrigatorio (a menos que explicitamente desligado).
		if ( ! empty( $settings['https_only'] ) && ! is_ssl() ) {
			return new WP_Error(
				'mme_https_required',
				__( 'O endpoint MCP exige HTTPS.', 'marreira-mcp-elementor' ),
				array( 'status' => 403 )
			);
		}

		// 2) Token Bearer.
		$token = self::extract_bearer( $request );
		if ( '' === $token ) {
			return new WP_Error(
				'mme_missing_token',
				__( 'Token Bearer ausente.', 'marreira-mcp-elementor' ),
				array( 'status' => 401 )
			);
		}

		if ( ! Token_Manager::validate( $token ) ) {
			return new WP_Error(
				'mme_invalid_token',
				__( 'Token invalido.', 'marreira-mcp-elementor' ),
				array( 'status' => 403 )
			);
		}

		// 3) Rate limit por token.
		$limited = self::rate_limit( $token, $settings );
		if ( is_wp_error( $limited ) ) {
			return $limited;
		}

		// 4) Assume o usuario de servico para as capabilities.
		$service_user_id = isset( $settings['service_user_id'] ) ? (int) $settings['service_user_id'] : 0;
		if ( $service_user_id > 0 && get_userdata( $service_user_id ) ) {
			wp_set_current_user( $service_user_id );
		}

		return true;
	}

	/**
	 * Extrai o token do header Authorization: Bearer xxx.
	 *
	 * @param WP_REST_Request $request Requisicao.
	 * @return string Token ou string vazia.
	 */
	private static function extract_bearer( WP_REST_Request $request ) {
		$header = $request->get_header( 'authorization' );

		// Alguns ambientes (CGI/FastCGI) escondem o header; tenta fallback.
		if ( ! $header && isset( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
			$header = wp_unslash( $_SERVER['HTTP_AUTHORIZATION'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		}
		if ( ! $header && isset( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ) {
			$header = wp_unslash( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		}

		if ( ! is_string( $header ) || 0 !== stripos( $header, 'Bearer ' ) ) {
			return '';
		}

		return trim( substr( $header, 7 ) );
	}

	/**
	 * Aplica rate limit por token usando transients.
	 *
	 * @param string $token    Token em texto puro.
	 * @param array  $settings Configuracoes.
	 * @return true|WP_Error
	 */
	private static function rate_limit( $token, $settings ) {
		$max    = isset( $settings['rate_limit'] ) ? (int) $settings['rate_limit'] : 60;
		$window = isset( $settings['rate_window'] ) ? (int) $settings['rate_window'] : 60;

		if ( $max <= 0 ) {
			return true; // Rate limit desativado.
		}

		$key   = 'mme_rl_' . substr( hash( 'sha256', $token ), 0, 20 );
		$count = (int) get_transient( $key );

		if ( $count >= $max ) {
			return new WP_Error(
				'mme_rate_limited',
				__( 'Limite de requisicoes excedido. Tente novamente em instantes.', 'marreira-mcp-elementor' ),
				array( 'status' => 429 )
			);
		}

		set_transient( $key, $count + 1, $window );
		return true;
	}

	/**
	 * Retorna as configuracoes do plugin com os defaults aplicados.
	 *
	 * @return array
	 */
	public static function settings() {
		$settings = get_option( Activator::SETTINGS_OPTION, array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}
		return wp_parse_args( $settings, Activator::default_settings() );
	}
}
