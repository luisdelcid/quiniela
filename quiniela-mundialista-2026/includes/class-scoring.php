<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM2026_Scoring {
	public function calculate_prediction( object $match, object $prediction, array $rules ): array {
		$points = 0;
		$exact  = ( (int) $prediction->home_score === (int) $match->home_score && (int) $prediction->away_score === (int) $match->away_score );
		$real_diff = (int) $match->home_score - (int) $match->away_score;
		$pred_diff = (int) $prediction->home_score - (int) $prediction->away_score;
		$real_side = $real_diff <=> 0;
		$pred_side = $pred_diff <=> 0;
		$winner_correct = $real_side === $pred_side;
		$goal_diff_correct = $real_diff === $pred_diff;
		$team_goals_correct = ( (int) $prediction->home_score === (int) $match->home_score ) || ( (int) $prediction->away_score === (int) $match->away_score );

		if ( $exact ) {
			$points += absint( $rules['exact_score'] ?? 5 );
		} elseif ( $winner_correct ) {
			$points += 0 === $real_side ? absint( $rules['correct_draw'] ?? 3 ) : absint( $rules['correct_winner'] ?? 3 );
		}
		if ( ! $exact && $winner_correct && 0 !== absint( $rules['goal_difference_bonus'] ?? 0 ) && $goal_diff_correct ) {
			$points += absint( $rules['goal_difference_bonus'] );
		}
		if ( ! $exact && 0 !== absint( $rules['team_goals_bonus'] ?? 0 ) && $team_goals_correct ) {
			$points += absint( $rules['team_goals_bonus'] );
		}

		return array(
			'points'             => $points,
			'exact_score'        => $exact ? 1 : 0,
			'winner_correct'     => $winner_correct ? 1 : 0,
			'goal_diff_correct'  => $goal_diff_correct ? 1 : 0,
			'team_goals_correct' => $team_goals_correct ? 1 : 0,
			'goal_diff_abs'      => abs( $real_diff - $pred_diff ),
			'details'            => array( 'rules' => $rules ),
		);
	}

	public function recalculate_match( int $match_id ): void {
		global $wpdb;
		$match = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . qm2026_table( 'matches' ) . ' WHERE id = %d AND status = %s AND home_score IS NOT NULL AND away_score IS NOT NULL', $match_id, 'finished' ) );
		if ( ! $match ) {
			return;
		}
		$predictions = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . qm2026_table( 'predictions' ) . ' WHERE match_id = %d', $match_id ) );
		foreach ( $predictions as $prediction ) {
			$pool  = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . qm2026_table( 'pools' ) . ' WHERE id = %d', $prediction->pool_id ) );
			$rules = $pool && $pool->rules ? json_decode( $pool->rules, true ) : qm2026_default_rules();
			$score = $this->calculate_prediction( $match, $prediction, is_array( $rules ) ? $rules : qm2026_default_rules() );
			$wpdb->replace(
				qm2026_table( 'scores' ),
				array(
					'pool_id'            => $prediction->pool_id,
					'participant_id'     => $prediction->participant_id,
					'match_id'           => $match_id,
					'points'             => $score['points'],
					'exact_score'        => $score['exact_score'],
					'winner_correct'     => $score['winner_correct'],
					'goal_diff_correct'  => $score['goal_diff_correct'],
					'team_goals_correct' => $score['team_goals_correct'],
					'goal_diff_abs'      => $score['goal_diff_abs'],
					'details'            => qm2026_json_encode( $score['details'] ),
					'updated_at'         => current_time( 'mysql' ),
				),
				array( '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s' )
			);
		}
		qm2026_log( 'scores_recalculated', __( 'Puntaje recalculado', QM2026_TEXT_DOMAIN ), array( 'match_id' => $match_id ) );
	}

	public function recalculate_all(): void {
		global $wpdb;
		$ids = $wpdb->get_col( 'SELECT id FROM ' . qm2026_table( 'matches' ) . " WHERE status = 'finished'" );
		foreach ( $ids as $id ) {
			$this->recalculate_match( (int) $id );
		}
	}

	public function ranking( int $pool_id, int $limit = 0 ): array {
		global $wpdb;
		$sql = 'SELECT p.id,p.name,p.created_at, COALESCE(SUM(s.points),0) total_points, COALESCE(SUM(s.exact_score),0) exact_scores, COALESCE(SUM(s.winner_correct),0) winners_correct, COALESCE(SUM(s.goal_diff_correct),0) goal_diffs, COALESCE(SUM(s.goal_diff_abs),0) goal_diff_abs FROM ' . qm2026_table( 'participants' ) . ' p LEFT JOIN ' . qm2026_table( 'scores' ) . ' s ON p.id=s.participant_id WHERE p.pool_id=%d AND p.active=1 GROUP BY p.id ORDER BY total_points DESC, exact_scores DESC, winners_correct DESC, goal_diff_abs ASC, p.created_at ASC';
		if ( $limit > 0 ) {
			$sql .= ' LIMIT ' . absint( $limit );
		}
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $pool_id ) );
		$position = 0;
		$display  = 0;
		$previous = null;
		foreach ( $rows as $row ) {
			$position++;
			$key = implode( '|', array( $row->total_points, $row->exact_scores, $row->winners_correct, $row->goal_diff_abs ) );
			if ( $key !== $previous ) {
				$display = $position;
			}
			$row->position = $display;
			$previous = $key;
		}
		return $rows;
	}
}
