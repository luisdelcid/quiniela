<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM2026_Ajax {
	public function hooks(): void {
		add_action( 'wp_ajax_qm2026_join_pool', array( $this, 'join_pool' ) );
		add_action( 'wp_ajax_nopriv_qm2026_join_pool', array( $this, 'join_pool' ) );
		add_action( 'wp_ajax_qm2026_save_prediction', array( $this, 'save_prediction' ) );
		add_action( 'wp_ajax_nopriv_qm2026_save_prediction', array( $this, 'save_prediction' ) );
	}

	public function join_pool(): void {
		global $wpdb;
		check_ajax_referer( 'qm2026_ajax', 'nonce' );
		if ( ! QM2026_Security::rate_limit( 'join_pool' ) ) {
			wp_send_json_error( __( 'Demasiados intentos. Intenta más tarde.', QM2026_TEXT_DOMAIN ), 429 );
		}
		$name            = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		$current_user_id = get_current_user_id();
		$current_user    = $current_user_id ? wp_get_current_user() : null;
		$email           = $current_user instanceof WP_User ? sanitize_email( $current_user->user_email ) : sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$code            = sanitize_text_field( wp_unslash( $_POST['code'] ?? '' ) );
		$pool            = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . qm2026_table( 'pools' ) . ' WHERE access_code=%s AND status=%s', $code, 'open' ) );
		if ( ! $pool || ! is_email( $email ) || '' === $name ) {
			wp_send_json_error( __( 'Datos inválidos o código incorrecto.', QM2026_TEXT_DOMAIN ), 400 );
		}
		if ( $current_user_id && '' === trim( (string) get_user_meta( $current_user_id, 'first_name', true ) ) ) {
			update_user_meta( $current_user_id, 'first_name', $name );
		}
		$existing = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . qm2026_table( 'participants' ) . ' WHERE pool_id=%d AND email=%s', $pool->id, $email ) );
		$token    = $existing ? $existing->token : wp_generate_password( 40, false, false );
		if ( ! $existing ) {
			$wpdb->insert( qm2026_table( 'participants' ), array( 'pool_id' => $pool->id, 'user_id' => $current_user_id ?: null, 'name' => $name, 'email' => $email, 'token' => $token, 'active' => 1, 'created_at' => current_time( 'mysql' ), 'updated_at' => current_time( 'mysql' ) ), array( '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s' ) );
			qm2026_log( 'participant_created', __( 'Participante creado', QM2026_TEXT_DOMAIN ), array( 'pool_id' => $pool->id ) );
		} elseif ( $current_user_id && empty( $existing->user_id ) ) {
			$wpdb->update( qm2026_table( 'participants' ), array( 'user_id' => $current_user_id, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => $existing->id ), array( '%d', '%s' ), array( '%d' ) );
		}
		setcookie( 'qm2026_token', $token, time() + YEAR_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), true );
		wp_send_json_success( array( 'message' => __( 'Ingreso correcto. Recargando...', QM2026_TEXT_DOMAIN ) ) );
	}

	public function save_prediction(): void {
		global $wpdb;
		check_ajax_referer( 'qm2026_ajax', 'nonce' );
		$participant = qm2026_current_participant();
		if ( ! $participant ) {
			wp_send_json_error( __( 'Sesión de participante inválida.', QM2026_TEXT_DOMAIN ), 403 );
		}
		$match_id = absint( $_POST['match_id'] ?? 0 );
		$match = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . qm2026_table( 'matches' ) . ' WHERE id=%d', $match_id ) );
		$pool = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . qm2026_table( 'pools' ) . ' WHERE id=%d', $participant->pool_id ) );
		$rules = $pool && $pool->rules ? json_decode( $pool->rules, true ) : qm2026_default_rules();
		if ( ! $match || qm2026_match_is_locked( $match, is_array( $rules ) ? $rules : qm2026_default_rules() ) ) {
			wp_send_json_error( __( 'Este partido ya está cerrado.', QM2026_TEXT_DOMAIN ), 400 );
		}
		$home = max( 0, intval( $_POST['home_score'] ?? 0 ) );
		$away = max( 0, intval( $_POST['away_score'] ?? 0 ) );
		$winner = absint( $_POST['predicted_winner_team_id'] ?? 0 ) ?: null;
		$wpdb->replace( qm2026_table( 'predictions' ), array( 'pool_id' => $participant->pool_id, 'participant_id' => $participant->id, 'match_id' => $match_id, 'home_score' => $home, 'away_score' => $away, 'predicted_winner_team_id' => $winner, 'created_at' => current_time( 'mysql' ), 'updated_at' => current_time( 'mysql' ) ), array( '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s' ) );
		qm2026_log( 'prediction_saved', __( 'Predicción creada/editada', QM2026_TEXT_DOMAIN ), array( 'participant_id' => $participant->id, 'match_id' => $match_id ) );
		wp_send_json_success( array( 'message' => __( 'Guardado', QM2026_TEXT_DOMAIN ) ) );
	}
}
