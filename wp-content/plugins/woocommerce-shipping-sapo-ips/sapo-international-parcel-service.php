<?php
/**
 * Backwards compatibility.
 * @since 1.2.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$active_plugins = get_option( 'active_plugins', array() );
foreach ( $active_plugins as $key => $active_plugin ) {
	if ( strstr( $active_plugin, '/sapo-international-parcel-service.php' ) ) {
		$active_plugins[ $key ] = str_replace( '/sapo-international-parcel-service.php', '/woocommerce-shipping-sapo-ips.php', $active_plugin );
	}
}

update_option( 'active_plugins', $active_plugins );
