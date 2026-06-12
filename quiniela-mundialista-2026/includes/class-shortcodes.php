<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM2026_Shortcodes {
	public function hooks(): void {
		add_shortcode( 'quiniela_mundialista', array( $this, 'landing' ) );
		add_shortcode( 'quiniela_mundialista_predicciones', array( $this, 'predictions' ) );
		add_shortcode( 'quiniela_mundialista_ranking', array( $this, 'ranking' ) );
		add_shortcode( 'quiniela_mundialista_mis_puntos', array( $this, 'points' ) );
		add_shortcode( 'quiniela_mundialista_bracket', array( $this, 'bracket' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
	}

	public function assets(): void {
		wp_enqueue_style( 'qm2026-public', QM2026_URL . 'public/css/qm2026-public.css', array(), QM2026_VERSION );
		wp_enqueue_script( 'qm2026-public', QM2026_URL . 'public/js/qm2026-public.js', array( 'jquery' ), QM2026_VERSION, true );
		wp_localize_script( 'qm2026-public', 'QM2026', array( 'ajaxUrl' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'qm2026_ajax' ), 'saved' => __( 'Guardado', QM2026_TEXT_DOMAIN ) ) );
	}

	private function render( string $view, array $vars = array() ): string {
		extract( $vars, EXTR_SKIP );
		ob_start();
		require QM2026_PATH . 'public/views/' . $view . '.php';
		return ob_get_clean();
	}

	public function landing(): string {
		global $wpdb;
		$participant  = qm2026_current_participant();
		$is_logged_in = is_user_logged_in();
		$default_name = '';
		if ( $is_logged_in ) {
			$current_user = wp_get_current_user();
			$default_name = $current_user instanceof WP_User ? (string) $current_user->first_name : '';
		}
		$pools = $wpdb->get_results( 'SELECT id,name,description,access_code FROM ' . qm2026_table( 'pools' ) . " WHERE status='open' AND allow_public=1 ORDER BY name" );
		return $this->render( 'landing', compact( 'participant', 'pools', 'is_logged_in', 'default_name' ) );
	}

	public function predictions(): string {
		global $wpdb;
		$participant = qm2026_current_participant();
		if ( ! $participant ) {
			return '<div class="qm2026 qm2026-notice">' . esc_html__( 'Únete a una quiniela para capturar predicciones.', QM2026_TEXT_DOMAIN ) . '</div>';
		}
		$pool = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . qm2026_table( 'pools' ) . ' WHERE id=%d', $participant->pool_id ) );
		$rules = $pool && $pool->rules ? json_decode( $pool->rules, true ) : qm2026_default_rules();
		$matches = $wpdb->get_results( 'SELECT m.*, ht.name home_name, at.name away_name FROM ' . qm2026_table( 'matches' ) . ' m LEFT JOIN ' . qm2026_table( 'teams' ) . ' ht ON ht.id=m.home_team_id LEFT JOIN ' . qm2026_table( 'teams' ) . ' at ON at.id=m.away_team_id ORDER BY m.match_datetime ASC, m.id ASC' );
		$predictions = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . qm2026_table( 'predictions' ) . ' WHERE participant_id=%d', $participant->id ), OBJECT_K );
		return $this->render( 'predictions', compact( 'participant', 'pool', 'rules', 'matches', 'predictions' ) );
	}

	public function ranking( $atts = array() ): string {
		global $wpdb;
		$participant = qm2026_current_participant();
		$pool_id = $participant ? (int) $participant->pool_id : absint( shortcode_atts( array( 'pool_id' => 0 ), $atts )['pool_id'] );
		if ( ! $pool_id ) {
			$pool_id = (int) $wpdb->get_var( 'SELECT id FROM ' . qm2026_table( 'pools' ) . " WHERE status='open' ORDER BY id LIMIT 1" );
		}
		$ranking = $pool_id ? ( new QM2026_Scoring() )->ranking( $pool_id ) : array();
		return $this->render( 'ranking', compact( 'ranking', 'pool_id' ) );
	}

	public function points(): string {
		global $wpdb;
		$participant = qm2026_current_participant();
		if ( ! $participant ) {
			return '';
		}
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT s.*,m.fifa_match_no, ht.name home_name, at.name away_name FROM ' . qm2026_table( 'scores' ) . ' s JOIN ' . qm2026_table( 'matches' ) . ' m ON m.id=s.match_id LEFT JOIN ' . qm2026_table( 'teams' ) . ' ht ON ht.id=m.home_team_id LEFT JOIN ' . qm2026_table( 'teams' ) . ' at ON at.id=m.away_team_id WHERE s.participant_id=%d ORDER BY m.match_datetime', $participant->id ) );
		return $this->render( 'participant-points', compact( 'rows', 'participant' ) );
	}

	public function bracket(): string {
		global $wpdb;
		$matches = $wpdb->get_results( 'SELECT * FROM ' . qm2026_table( 'matches' ) . " WHERE phase <> 'group' ORDER BY match_datetime" );
		return '<div class="qm2026 qm2026-bracket"><h3>' . esc_html__( 'Bracket', QM2026_TEXT_DOMAIN ) . '</h3><p>' . esc_html__( 'Visualización simple de fase eliminatoria.', QM2026_TEXT_DOMAIN ) . '</p><div class="qm2026-grid">' . implode( '', array_map( fn( $m ) => '<div class="qm2026-card"><strong>' . esc_html( qm2026_phases()[ $m->phase ] ?? $m->phase ) . '</strong><br>' . esc_html( $m->home_placeholder ?: 'Por definir' ) . ' vs ' . esc_html( $m->away_placeholder ?: 'Por definir' ) . '</div>', $matches ) ) . '</div></div>';
	}
}
