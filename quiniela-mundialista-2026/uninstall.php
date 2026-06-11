<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
global $wpdb;
$settings_table = $wpdb->prefix . 'qm2026_settings';
$delete = $wpdb->get_var( $wpdb->prepare( "SELECT setting_value FROM $settings_table WHERE setting_key=%s", 'delete_on_uninstall' ) );
if ( '1' === $delete ) {
	foreach ( array( 'pools','participants','teams','matches','predictions','results','scores','settings','logs' ) as $table ) {
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'qm2026_' . $table );
	}
	delete_option( 'qm2026_landing_page_id' );
}
