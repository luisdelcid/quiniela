<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM2026_Import_Export {
	public function import_teams_json( string $json ): int {
		global $wpdb;
		$data = json_decode( $json, true );
		if ( ! is_array( $data ) ) {
			return 0;
		}
		$count = 0;
		foreach ( $data as $team ) {
			$wpdb->replace(
				qm2026_table( 'teams' ),
				array(
					'team_code'   => sanitize_text_field( $team['code'] ?? '' ),
					'name'        => sanitize_text_field( $team['name'] ?? '' ),
					'group_code'  => sanitize_text_field( $team['group'] ?? '' ),
					'flag'        => sanitize_text_field( $team['flag'] ?? '' ),
					'qualified'   => empty( $team['placeholder'] ) ? 1 : 0,
					'placeholder' => ! empty( $team['placeholder'] ) ? 1 : 0,
				),
				array( '%s', '%s', '%s', '%s', '%d', '%d' )
			);
			$count++;
		}
		return $count;
	}

	public function import_fixture_json( string $json ): int {
		global $wpdb;
		$data = json_decode( $json, true );
		if ( ! is_array( $data ) ) {
			return 0;
		}
		$count = 0;
		foreach ( $data as $match ) {
			$home = $this->team_id_by_code( $match['home_code'] ?? '' );
			$away = $this->team_id_by_code( $match['away_code'] ?? '' );
			$fifa = isset( $match['fifa_match_no'] ) ? absint( $match['fifa_match_no'] ) : null;
			$existing_id = $fifa ? $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . qm2026_table( 'matches' ) . ' WHERE fifa_match_no=%d', $fifa ) ) : 0;
			$row = array(
				'fifa_match_no'         => $fifa,
				'phase'                 => sanitize_key( $match['phase'] ?? 'group' ),
				'group_code'            => sanitize_text_field( $match['group'] ?? '' ),
				'home_team_id'          => $home,
				'away_team_id'           => $away,
				'home_placeholder'      => sanitize_text_field( $match['home_placeholder'] ?? '' ),
				'away_placeholder'       => sanitize_text_field( $match['away_placeholder'] ?? '' ),
				'match_datetime'        => sanitize_text_field( $match['datetime'] ?? current_time( 'mysql' ) ),
				'stadium'               => sanitize_text_field( $match['stadium'] ?? '' ),
				'city'                  => sanitize_text_field( $match['city'] ?? '' ),
				'status'                => sanitize_key( $match['status'] ?? 'scheduled' ),
				'prediction_deadline'   => ! empty( $match['prediction_deadline'] ) ? sanitize_text_field( $match['prediction_deadline'] ) : null,
				'created_at'            => current_time( 'mysql' ),
				'updated_at'            => current_time( 'mysql' ),
			);
			if ( $existing_id ) {
				unset( $row['created_at'] );
				$wpdb->update( qm2026_table( 'matches' ), $row, array( 'id' => $existing_id ) );
			} else {
				$wpdb->insert( qm2026_table( 'matches' ), $row );
			}
			$count++;
		}
		return $count;
	}

	private function team_id_by_code( string $code ): ?int {
		global $wpdb;
		if ( '' === $code ) {
			return null;
		}
		$id = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . qm2026_table( 'teams' ) . ' WHERE team_code=%s', sanitize_text_field( $code ) ) );
		return $id ? (int) $id : null;
	}

	public function export_participants_csv(): void {
		global $wpdb;
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=participantes-quiniela.csv' );
		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( 'id', 'pool', 'nombre', 'email', 'registro', 'activo' ) );
		$rows = $wpdb->get_results( 'SELECT p.*, pool.name pool_name FROM ' . qm2026_table( 'participants' ) . ' p LEFT JOIN ' . qm2026_table( 'pools' ) . ' pool ON pool.id=p.pool_id ORDER BY p.created_at' );
		foreach ( $rows as $row ) {
			fputcsv( $out, array( $row->id, $row->pool_name, $row->name, $row->email, $row->created_at, $row->active ) );
		}
		exit;
	}

	public function export_predictions_csv(): void {
		global $wpdb;
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=predicciones-quiniela.csv' );
		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( 'pool_id', 'participante', 'match_id', 'local', 'visitante', 'pred_local', 'pred_visitante', 'actualizado' ) );
		$rows = $wpdb->get_results( 'SELECT pr.*, p.name participant_name, ht.name home_name, at.name away_name FROM ' . qm2026_table( 'predictions' ) . ' pr JOIN ' . qm2026_table( 'participants' ) . ' p ON p.id=pr.participant_id JOIN ' . qm2026_table( 'matches' ) . ' m ON m.id=pr.match_id LEFT JOIN ' . qm2026_table( 'teams' ) . ' ht ON ht.id=m.home_team_id LEFT JOIN ' . qm2026_table( 'teams' ) . ' at ON at.id=m.away_team_id ORDER BY pr.updated_at' );
		foreach ( $rows as $row ) {
			fputcsv( $out, array( $row->pool_id, $row->participant_name, $row->match_id, $row->home_name, $row->away_name, $row->home_score, $row->away_score, $row->updated_at ) );
		}
		exit;
	}

	public function export_ranking_csv( int $pool_id ): void {
		$scoring = new QM2026_Scoring();
		$rows    = $scoring->ranking( $pool_id );
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=ranking-quiniela-' . $pool_id . '.csv' );
		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( 'posicion', 'nombre', 'puntos', 'marcadores_exactos', 'ganadores', 'diferencia_acumulada' ) );
		foreach ( $rows as $row ) {
			fputcsv( $out, array( $row->position, $row->name, $row->total_points, $row->exact_scores, $row->winners_correct, $row->goal_diff_abs ) );
		}
		exit;
	}
}
