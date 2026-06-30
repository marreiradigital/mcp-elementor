<?php
/**
 * Rotina de desinstalacao: remove options e transients do plugin.
 *
 * @package Marreira\MCP_Elementor
 */

// Executado pelo WordPress apenas durante a desinstalacao.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$mme_options = array(
	'mme_settings',
	'mme_version',
	'mme_token_hash',
	'mme_token_meta',
);

foreach ( $mme_options as $mme_option ) {
	delete_option( $mme_option );
}

// Limpa transients de rate limit remanescentes.
global $wpdb;
$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_mme_rl_%' OR option_name LIKE '_transient_timeout_mme_rl_%' OR option_name LIKE '_transient_mme_flash_token' OR option_name LIKE '_transient_timeout_mme_flash_token'"
);
