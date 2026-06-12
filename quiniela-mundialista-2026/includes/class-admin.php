<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM2026_Admin {
	public function hooks(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
	}

	public function assets(): void {
		wp_enqueue_style( 'qm2026-admin', QM2026_URL . 'public/css/qm2026-public.css', array(), QM2026_VERSION );
	}

	public function menu(): void {
		add_menu_page( __( 'Quiniela Mundialista', QM2026_TEXT_DOMAIN ), __( 'Quiniela Mundialista', QM2026_TEXT_DOMAIN ), 'qm2026_manage_pools', 'qm2026', array( $this, 'dashboard' ), 'dashicons-awards', 26 );
		add_submenu_page( 'qm2026', __( 'Dashboard', QM2026_TEXT_DOMAIN ), __( 'Dashboard', QM2026_TEXT_DOMAIN ), 'qm2026_manage_pools', 'qm2026', array( $this, 'dashboard' ) );
		add_submenu_page( 'qm2026', __( 'Quinielas', QM2026_TEXT_DOMAIN ), __( 'Quinielas', QM2026_TEXT_DOMAIN ), 'qm2026_manage_pools', 'qm2026-pools', array( $this, 'pools' ) );
		add_submenu_page( 'qm2026', __( 'Participantes', QM2026_TEXT_DOMAIN ), __( 'Participantes', QM2026_TEXT_DOMAIN ), 'qm2026_manage_pools', 'qm2026-participants', array( $this, 'participants' ) );
		add_submenu_page( 'qm2026', __( 'Equipos', QM2026_TEXT_DOMAIN ), __( 'Equipos', QM2026_TEXT_DOMAIN ), 'qm2026_manage_pools', 'qm2026-teams', array( $this, 'teams' ) );
		add_submenu_page( 'qm2026', __( 'Partidos', QM2026_TEXT_DOMAIN ), __( 'Partidos', QM2026_TEXT_DOMAIN ), 'qm2026_manage_pools', 'qm2026-matches', array( $this, 'matches' ) );
		add_submenu_page( 'qm2026', __( 'Resultados', QM2026_TEXT_DOMAIN ), __( 'Resultados', QM2026_TEXT_DOMAIN ), 'qm2026_manage_pools', 'qm2026-results', array( $this, 'matches' ) );
		add_submenu_page( 'qm2026', __( 'Reglas de puntaje', QM2026_TEXT_DOMAIN ), __( 'Reglas de puntaje', QM2026_TEXT_DOMAIN ), 'qm2026_manage_pools', 'qm2026-rules', array( $this, 'settings' ) );
		add_submenu_page( 'qm2026', __( 'Importar/Exportar', QM2026_TEXT_DOMAIN ), __( 'Importar/Exportar', QM2026_TEXT_DOMAIN ), 'qm2026_manage_pools', 'qm2026-import-export', array( $this, 'import_export' ) );
		add_submenu_page( 'qm2026', __( 'Ajustes', QM2026_TEXT_DOMAIN ), __( 'Ajustes', QM2026_TEXT_DOMAIN ), 'manage_options', 'qm2026-settings', array( $this, 'settings' ) );
	}

	public function handle_actions(): void {
		if ( ! QM2026_Security::can_manage() ) {
			return;
		}
		if ( isset( $_GET['qm2026_export_ranking'], $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'qm2026_export_ranking' ) ) {
			( new QM2026_Import_Export() )->export_ranking_csv( absint( $_GET['pool_id'] ?? 0 ) );
		}
		if ( isset( $_GET['qm2026_export_participants'], $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'qm2026_export_participants' ) ) {
			( new QM2026_Import_Export() )->export_participants_csv();
		}
		if ( isset( $_GET['qm2026_export_predictions'], $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'qm2026_export_predictions' ) ) {
			( new QM2026_Import_Export() )->export_predictions_csv();
		}
		if ( empty( $_POST['qm2026_action'] ) ) {
			return;
		}
		check_admin_referer( 'qm2026_admin_action' );
		$action = sanitize_key( wp_unslash( $_POST['qm2026_action'] ) );
		if ( 'save_pool' === $action ) {
			$this->save_pool();
		} elseif ( 'delete_pool' === $action ) {
			$this->delete_pool();
		} elseif ( 'delete_participant' === $action ) {
			$this->delete_participant();
		} elseif ( 'save_match' === $action || 'save_result' === $action ) {
			$this->save_match();
		} elseif ( 'save_settings' === $action ) {
			$this->save_settings();
		} elseif ( 'import_fixture' === $action && ! empty( $_FILES['fixture']['tmp_name'] ) ) {
			$count = ( new QM2026_Import_Export() )->import_fixture_json( file_get_contents( $_FILES['fixture']['tmp_name'] ) );
			wp_safe_redirect( add_query_arg( 'qm2026_notice', rawurlencode( sprintf( __( '%d partidos importados.', QM2026_TEXT_DOMAIN ), $count ) ) ) );
			exit;
		} elseif ( 'recalculate_all' === $action ) {
			( new QM2026_Scoring() )->recalculate_all();
			wp_safe_redirect( add_query_arg( 'qm2026_notice', rawurlencode( __( 'Puntajes recalculados.', QM2026_TEXT_DOMAIN ) ) ) );
			exit;
		}
	}

	private function save_pool(): void {
		global $wpdb;
		$id = absint( $_POST['id'] ?? 0 );
		$rules = array_map( 'absint', $_POST['rules'] ?? qm2026_default_rules() );
		$row = array(
			'name'             => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
			'description'      => sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ),
			'access_code'      => sanitize_text_field( wp_unslash( $_POST['access_code'] ?? qm2026_generate_code() ) ),
			'status'           => sanitize_key( wp_unslash( $_POST['status'] ?? 'open' ) ),
			'rules'            => qm2026_json_encode( array_merge( qm2026_default_rules(), $rules ) ),
			'general_deadline' => ! empty( $_POST['general_deadline'] ) ? sanitize_text_field( wp_unslash( $_POST['general_deadline'] ) ) : null,
			'allow_public'     => empty( $_POST['allow_public'] ) ? 0 : 1,
			'allow_guests'     => empty( $_POST['allow_guests'] ) ? 0 : 1,
			'updated_at'       => current_time( 'mysql' ),
		);
		if ( $id ) {
			$wpdb->update( qm2026_table( 'pools' ), $row, array( 'id' => $id ) );
		} else {
			$row['created_by'] = get_current_user_id();
			$row['created_at'] = current_time( 'mysql' );
			$wpdb->insert( qm2026_table( 'pools' ), $row );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=qm2026-pools&qm2026_notice=' . rawurlencode( __( 'Quiniela guardada.', QM2026_TEXT_DOMAIN ) ) ) );
		exit;
	}


	private function delete_pool(): void {
		global $wpdb;
		$id = absint( $_POST['id'] ?? 0 );
		if ( $id ) {
			$participant_ids = $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM ' . qm2026_table( 'participants' ) . ' WHERE pool_id=%d', $id ) );
			if ( ! empty( $participant_ids ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $participant_ids ), '%d' ) );
				$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . qm2026_table( 'predictions' ) . ' WHERE participant_id IN (' . $placeholders . ')', array_map( 'absint', $participant_ids ) ) );
				$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . qm2026_table( 'scores' ) . ' WHERE participant_id IN (' . $placeholders . ')', array_map( 'absint', $participant_ids ) ) );
			}
			$wpdb->delete( qm2026_table( 'predictions' ), array( 'pool_id' => $id ), array( '%d' ) );
			$wpdb->delete( qm2026_table( 'scores' ), array( 'pool_id' => $id ), array( '%d' ) );
			$wpdb->delete( qm2026_table( 'participants' ), array( 'pool_id' => $id ), array( '%d' ) );
			$wpdb->delete( qm2026_table( 'pools' ), array( 'id' => $id ), array( '%d' ) );
			qm2026_log( 'pool_deleted', __( 'Quiniela eliminada', QM2026_TEXT_DOMAIN ), array( 'pool_id' => $id ) );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=qm2026-pools&qm2026_notice=' . rawurlencode( __( 'Quiniela eliminada.', QM2026_TEXT_DOMAIN ) ) ) );
		exit;
	}

	private function delete_participant(): void {
		global $wpdb;
		$id = absint( $_POST['id'] ?? 0 );
		if ( $id ) {
			$wpdb->delete( qm2026_table( 'predictions' ), array( 'participant_id' => $id ), array( '%d' ) );
			$wpdb->delete( qm2026_table( 'scores' ), array( 'participant_id' => $id ), array( '%d' ) );
			$wpdb->delete( qm2026_table( 'participants' ), array( 'id' => $id ), array( '%d' ) );
			qm2026_log( 'participant_deleted', __( 'Participante eliminado', QM2026_TEXT_DOMAIN ), array( 'participant_id' => $id ) );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=qm2026-participants&qm2026_notice=' . rawurlencode( __( 'Participante eliminado.', QM2026_TEXT_DOMAIN ) ) ) );
		exit;
	}

	private function save_match(): void {
		global $wpdb;
		$id = absint( $_POST['id'] ?? 0 );
		$row = array(
			'fifa_match_no'       => absint( $_POST['fifa_match_no'] ?? 0 ) ?: null,
			'phase'               => sanitize_key( wp_unslash( $_POST['phase'] ?? 'group' ) ),
			'group_code'          => sanitize_text_field( wp_unslash( $_POST['group_code'] ?? '' ) ),
			'home_team_id'        => absint( $_POST['home_team_id'] ?? 0 ) ?: null,
			'away_team_id'         => absint( $_POST['away_team_id'] ?? 0 ) ?: null,
			'home_placeholder'    => sanitize_text_field( wp_unslash( $_POST['home_placeholder'] ?? '' ) ),
			'away_placeholder'     => sanitize_text_field( wp_unslash( $_POST['away_placeholder'] ?? '' ) ),
			'match_datetime'      => str_replace( 'T', ' ', sanitize_text_field( wp_unslash( $_POST['match_datetime'] ?? current_time( 'mysql' ) ) ) ),
			'stadium'             => sanitize_text_field( wp_unslash( $_POST['stadium'] ?? '' ) ),
			'city'                => sanitize_text_field( wp_unslash( $_POST['city'] ?? '' ) ),
			'status'              => sanitize_key( wp_unslash( $_POST['status'] ?? 'scheduled' ) ),
			'home_score'          => ( '' !== ( $_POST['home_score'] ?? '' ) ) ? intval( $_POST['home_score'] ) : null,
			'away_score'          => ( '' !== ( $_POST['away_score'] ?? '' ) ) ? intval( $_POST['away_score'] ) : null,
			'prediction_deadline' => ! empty( $_POST['prediction_deadline'] ) ? str_replace( 'T', ' ', sanitize_text_field( wp_unslash( $_POST['prediction_deadline'] ) ) ) : null,
			'updated_at'          => current_time( 'mysql' ),
		);
		if ( $id ) {
			$wpdb->update( qm2026_table( 'matches' ), $row, array( 'id' => $id ) );
		} else {
			$row['created_at'] = current_time( 'mysql' );
			$wpdb->insert( qm2026_table( 'matches' ), $row );
			$id = (int) $wpdb->insert_id;
		}
		if ( 'finished' === $row['status'] ) {
			( new QM2026_Scoring() )->recalculate_match( $id );
			qm2026_log( 'result_updated', __( 'Resultado editado', QM2026_TEXT_DOMAIN ), array( 'match_id' => $id ) );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=qm2026-matches&qm2026_notice=' . rawurlencode( __( 'Partido guardado.', QM2026_TEXT_DOMAIN ) ) ) );
		exit;
	}

	private function save_settings(): void {
		$rules = array_merge( qm2026_default_rules(), array_map( 'absint', $_POST['rules'] ?? array() ) );
		qm2026_update_setting( 'default_rules', $rules );
		qm2026_update_setting( 'delete_on_uninstall', empty( $_POST['delete_on_uninstall'] ) ? '0' : '1' );
		wp_safe_redirect( add_query_arg( 'qm2026_notice', rawurlencode( __( 'Ajustes guardados.', QM2026_TEXT_DOMAIN ) ) ) );
		exit;
	}

	public function dashboard(): void { require QM2026_PATH . 'admin/views/dashboard.php'; }
	public function pools(): void { require QM2026_PATH . 'admin/views/pools.php'; }
	public function matches(): void { require QM2026_PATH . 'admin/views/matches.php'; }
	public function participants(): void { require QM2026_PATH . 'admin/views/participants.php'; }
	public function settings(): void { require QM2026_PATH . 'admin/views/settings.php'; }
	public function teams(): void { require QM2026_PATH . 'admin/views/teams.php'; }
	public function import_export(): void { require QM2026_PATH . 'admin/views/import-export.php'; }
}
