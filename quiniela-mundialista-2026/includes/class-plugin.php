<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM2026_Plugin {
	private static ?QM2026_Plugin $instance = null;
	public static function instance(): QM2026_Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	public function run(): void {
		( new QM2026_Admin() )->hooks();
		( new QM2026_Shortcodes() )->hooks();
		( new QM2026_Ajax() )->hooks();
		add_action( 'init', array( $this, 'maybe_sync_fixture_data' ) );
		add_action( 'init', array( $this, 'capture_private_token' ) );
	}
	public function maybe_sync_fixture_data(): void {
		if ( QM2026_FIXTURE_DATA_VERSION === qm2026_get_setting( 'fixture_data_version', '' ) ) {
			return;
		}

		QM2026_Activator::seed_data();
		qm2026_update_setting( 'fixture_data_version', QM2026_FIXTURE_DATA_VERSION );
	}

	public function capture_private_token(): void {
		if ( ! empty( $_GET['qm2026_token'] ) ) {
			$token = sanitize_text_field( wp_unslash( $_GET['qm2026_token'] ) );
			setcookie( 'qm2026_token', $token, time() + YEAR_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), true );
			$_COOKIE['qm2026_token'] = $token;
		}
	}
}
