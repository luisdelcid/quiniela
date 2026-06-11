<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function qm2026_table( string $name ): string {
	global $wpdb;
	return $wpdb->prefix . 'qm2026_' . $name;
}

function qm2026_default_rules(): array {
	return array(
		'exact_score'           => 5,
		'correct_winner'        => 3,
		'correct_draw'          => 3,
		'goal_difference_bonus' => 1,
		'team_goals_bonus'      => 0,
		'champion_bonus'        => 0,
		'finalist_bonus'        => 0,
		'lock_minutes'          => 15,
	);
}

function qm2026_phases(): array {
	return array(
		'group'         => __( 'Fase de grupos', QM2026_TEXT_DOMAIN ),
		'round_32'      => __( 'Ronda de 32', QM2026_TEXT_DOMAIN ),
		'round_16'      => __( 'Octavos de final', QM2026_TEXT_DOMAIN ),
		'quarter_final' => __( 'Cuartos de final', QM2026_TEXT_DOMAIN ),
		'semi_final'    => __( 'Semifinal', QM2026_TEXT_DOMAIN ),
		'third_place'   => __( 'Tercer lugar', QM2026_TEXT_DOMAIN ),
		'final'         => __( 'Final', QM2026_TEXT_DOMAIN ),
	);
}

function qm2026_statuses(): array {
	return array(
		'scheduled' => __( 'Programado', QM2026_TEXT_DOMAIN ),
		'live'      => __( 'En vivo', QM2026_TEXT_DOMAIN ),
		'finished'  => __( 'Finalizado', QM2026_TEXT_DOMAIN ),
	);
}

function qm2026_pool_statuses(): array {
	return array(
		'open'     => __( 'Abierta', QM2026_TEXT_DOMAIN ),
		'closed'   => __( 'Cerrada', QM2026_TEXT_DOMAIN ),
		'finished' => __( 'Finalizada', QM2026_TEXT_DOMAIN ),
	);
}

function qm2026_generate_code( int $length = 8 ): string {
	$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
	$code  = '';
	for ( $i = 0; $i < $length; $i++ ) {
		$code .= $chars[ wp_rand( 0, strlen( $chars ) - 1 ) ];
	}
	return $code;
}

function qm2026_json_encode( $data ): string {
	return wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
}

function qm2026_get_setting( string $key, $default = null ) {
	global $wpdb;
	$row = $wpdb->get_row( $wpdb->prepare( 'SELECT setting_value FROM ' . qm2026_table( 'settings' ) . ' WHERE setting_key = %s', $key ) );
	if ( ! $row ) {
		return $default;
	}
	$decoded = json_decode( $row->setting_value, true );
	return ( JSON_ERROR_NONE === json_last_error() ) ? $decoded : $row->setting_value;
}

function qm2026_update_setting( string $key, $value ): void {
	global $wpdb;
	$wpdb->replace(
		qm2026_table( 'settings' ),
		array(
			'setting_key'   => $key,
			'setting_value' => is_scalar( $value ) ? (string) $value : qm2026_json_encode( $value ),
			'updated_at'    => current_time( 'mysql' ),
		),
		array( '%s', '%s', '%s' )
	);
}

function qm2026_log( string $event, string $message, array $context = array() ): void {
	global $wpdb;
	$wpdb->insert(
		qm2026_table( 'logs' ),
		array(
			'event'      => sanitize_key( $event ),
			'message'    => sanitize_text_field( $message ),
			'context'    => qm2026_json_encode( $context ),
			'user_id'    => get_current_user_id(),
			'ip_address' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
			'created_at' => current_time( 'mysql' ),
		),
		array( '%s', '%s', '%s', '%d', '%s', '%s' )
	);
}

function qm2026_current_participant(): ?object {
	global $wpdb;
	$token = isset( $_COOKIE['qm2026_token'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['qm2026_token'] ) ) : '';
	if ( $token ) {
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . qm2026_table( 'participants' ) . ' WHERE token = %s AND active = 1', $token ) );
		if ( $row ) {
			return $row;
		}
	}
	if ( is_user_logged_in() ) {
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . qm2026_table( 'participants' ) . ' WHERE user_id = %d AND active = 1 ORDER BY id DESC LIMIT 1', get_current_user_id() ) );
	}
	return null;
}

function qm2026_match_is_locked( object $match, ?array $rules = null ): bool {
	$rules        = $rules ?: qm2026_default_rules();
	$lock_minutes = isset( $rules['lock_minutes'] ) ? absint( $rules['lock_minutes'] ) : 0;
	$deadline     = ! empty( $match->prediction_deadline ) ? strtotime( $match->prediction_deadline ) : ( strtotime( $match->match_datetime ) - ( $lock_minutes * MINUTE_IN_SECONDS ) );
	return current_time( 'timestamp' ) >= $deadline || 'finished' === $match->status || 'live' === $match->status;
}
