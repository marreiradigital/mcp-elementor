<?php
/**
 * Gerenciamento do token de acesso ao endpoint MCP.
 *
 * @package Marreira\MCP_Elementor
 */

namespace Marreira\MCP_Elementor\Auth;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gera, valida, rotaciona e revoga o token Bearer.
 *
 * O token em texto puro NUNCA e gravado: guardamos apenas o hash SHA-256
 * numa option nao-autoload. O texto puro e exibido uma unica vez ao admin
 * no momento da geracao.
 */
class Token_Manager {

	/**
	 * Option (nao-autoload) que guarda o hash do token.
	 *
	 * @var string
	 */
	const HASH_OPTION = MME_OPTION_PREFIX . 'token_hash';

	/**
	 * Option que guarda metadados do token (criado em, ultimo uso).
	 *
	 * @var string
	 */
	const META_OPTION = MME_OPTION_PREFIX . 'token_meta';

	/**
	 * Gera um novo token, grava o hash e retorna o token em texto puro.
	 *
	 * Chamado pela tela de admin. O retorno deve ser exibido uma unica vez.
	 *
	 * @return string Token em texto puro (prefixado para legibilidade).
	 */
	public static function generate() {
		$raw  = 'mme_' . bin2hex( random_bytes( 32 ) );
		$hash = hash( 'sha256', $raw );

		// Option nao-autoload.
		update_option( self::HASH_OPTION, $hash, false );
		update_option(
			self::META_OPTION,
			array(
				'created_at' => time(),
				'last_used'  => 0,
			),
			false
		);

		return $raw;
	}

	/**
	 * Indica se ja existe um token configurado.
	 *
	 * @return bool
	 */
	public static function has_token() {
		$hash = get_option( self::HASH_OPTION, '' );
		return is_string( $hash ) && '' !== $hash;
	}

	/**
	 * Valida um token em texto puro contra o hash armazenado.
	 *
	 * Usa hash_equals para evitar timing attacks.
	 *
	 * @param string $raw Token recebido na requisicao.
	 * @return bool
	 */
	public static function validate( $raw ) {
		$stored = get_option( self::HASH_OPTION, '' );
		if ( ! is_string( $stored ) || '' === $stored || ! is_string( $raw ) || '' === $raw ) {
			return false;
		}

		$ok = hash_equals( $stored, hash( 'sha256', $raw ) );

		if ( $ok ) {
			self::touch_last_used();
		}

		return $ok;
	}

	/**
	 * Atualiza o carimbo de ultimo uso do token.
	 *
	 * @return void
	 */
	private static function touch_last_used() {
		$meta = get_option( self::META_OPTION, array() );
		if ( ! is_array( $meta ) ) {
			$meta = array();
		}
		$meta['last_used'] = time();
		update_option( self::META_OPTION, $meta, false );
	}

	/**
	 * Revoga o token atual.
	 *
	 * @return void
	 */
	public static function revoke() {
		delete_option( self::HASH_OPTION );
		delete_option( self::META_OPTION );
	}

	/**
	 * Retorna metadados do token (sem expor o token).
	 *
	 * @return array
	 */
	public static function meta() {
		$meta = get_option( self::META_OPTION, array() );
		return is_array( $meta ) ? $meta : array();
	}
}
