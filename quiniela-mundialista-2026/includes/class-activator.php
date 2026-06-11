<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM2026_Activator {
	public static function activate(): void {
		QM2026_DB::schema();
		self::add_capabilities();
		self::seed_settings();
		self::seed_data();
		qm2026_update_setting( 'fixture_data_version', QM2026_FIXTURE_DATA_VERSION );
		self::create_default_pool();
		self::create_landing_page();
	}

	public static function deactivate(): void {}

	private static function add_capabilities(): void {
		$role = get_role( 'administrator' );
		if ( $role ) {
			$role->add_cap( 'qm2026_manage_pools' );
		}
	}

	private static function seed_settings(): void {
		if ( null === qm2026_get_setting( 'default_rules', null ) ) {
			qm2026_update_setting( 'default_rules', qm2026_default_rules() );
		}
		if ( null === qm2026_get_setting( 'delete_on_uninstall', null ) ) {
			qm2026_update_setting( 'delete_on_uninstall', '0' );
		}
	}

	public static function seed_data(): void {
		$importer = new QM2026_Import_Export();
		$teams    = QM2026_PATH . 'assets/data/teams-2026.json';
		$fixture  = QM2026_PATH . 'assets/data/fixture-2026.json';
		if ( file_exists( $teams ) ) {
			$importer->import_teams_json( file_get_contents( $teams ) );
		}
		if ( file_exists( $fixture ) ) {
			$importer->import_fixture_json( file_get_contents( $fixture ) );
		}
	}

	private static function create_default_pool(): void {
		global $wpdb;
		$exists = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . qm2026_table( 'pools' ) );
		if ( $exists > 0 ) {
			return;
		}
		$wpdb->insert(
			qm2026_table( 'pools' ),
			array(
				'name'         => __( 'Quiniela Mundialista 2026', QM2026_TEXT_DOMAIN ),
				'description'  => __( 'Quiniela privada inicial lista para compartir.', QM2026_TEXT_DOMAIN ),
				'access_code'  => qm2026_generate_code(),
				'status'       => 'open',
				'rules'        => qm2026_json_encode( qm2026_default_rules() ),
				'allow_public' => 1,
				'allow_guests' => 1,
				'created_by'   => get_current_user_id(),
				'created_at'   => current_time( 'mysql' ),
				'updated_at'   => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s' )
		);
	}

	private static function create_landing_page(): void {
		$page_id = (int) get_option( 'qm2026_landing_page_id', 0 );
		if ( $page_id && get_post( $page_id ) ) {
			return;
		}
		$existing = get_page_by_path( 'quiniela-mundialista' );
		if ( $existing ) {
			update_option( 'qm2026_landing_page_id', $existing->ID );
			return;
		}
		$page_id = wp_insert_post(
			array(
				'post_title'   => __( 'Quiniela Mundialista', QM2026_TEXT_DOMAIN ),
				'post_name'    => 'quiniela-mundialista',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => "[quiniela_mundialista]\n\n[quiniela_mundialista_predicciones]\n\n[quiniela_mundialista_ranking]",
			)
		);
		if ( ! is_wp_error( $page_id ) ) {
			update_option( 'qm2026_landing_page_id', $page_id );
		}
	}
}
