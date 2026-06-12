<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$phase_labels = qm2026_phases();
?>
<div class="qm2026 qm2026-predictions">
	<h2><?php esc_html_e( 'Mis predicciones', QM2026_TEXT_DOMAIN ); ?></h2>
	<p><?php echo esc_html( $pool->name ); ?></p>
	<?php
	$current = '';
	foreach ( $matches as $match ) :
		$group = $match->phase . '-' . $match->group_code . '-' . qm2026_format_site_datetime( $match->match_datetime, 'Y-m-d' );
		if ( $group !== $current ) {
			$current = $group;
			echo '<h3>' . esc_html( ( $phase_labels[ $match->phase ] ?? $match->phase ) . ' ' . $match->group_code . ' · ' . qm2026_format_site_datetime( $match->match_datetime, get_option( 'date_format' ) ) ) . '</h3>';
		}
		$prediction = $predictions[ (int) $match->id ] ?? null;
		$locked     = qm2026_match_is_locked( $match, $rules );
		?>
		<form class="qm2026-card qm2026-prediction-form <?php echo $locked ? 'is-locked' : ''; ?>" data-match-id="<?php echo esc_attr( $match->id ); ?>">
			<input type="hidden" name="match_id" value="<?php echo esc_attr( $match->id ); ?>">
			<div class="qm2026-match-head">
				<strong><?php echo esc_html( ( $match->home_name ?: $match->home_placeholder ?: __( 'Por definir', QM2026_TEXT_DOMAIN ) ) . ' vs ' . ( $match->away_name ?: $match->away_placeholder ?: __( 'Por definir', QM2026_TEXT_DOMAIN ) ) ); ?></strong>
				<span><?php echo esc_html( qm2026_format_site_datetime( $match->match_datetime, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ); ?></span>
			</div>
			<div class="qm2026-score-row">
				<label>
					<?php echo esc_html( $match->home_name ?: $match->home_placeholder ); ?>
					<input type="number" min="0" name="home_score" value="<?php echo esc_attr( $prediction->home_score ?? '' ); ?>" <?php disabled( $locked ); ?>>
				</label>
				<span>—</span>
				<label>
					<?php echo esc_html( $match->away_name ?: $match->away_placeholder ); ?>
					<input type="number" min="0" name="away_score" value="<?php echo esc_attr( $prediction->away_score ?? '' ); ?>" <?php disabled( $locked ); ?>>
				</label>
			</div>
			<?php if ( 'group' !== $match->phase ) : ?>
				<label>
					<?php esc_html_e( 'Ganador si hay empate', QM2026_TEXT_DOMAIN ); ?>
					<select name="predicted_winner_team_id" <?php disabled( $locked ); ?>>
						<option value="">—</option>
						<?php
						if ( $match->home_team_id ) {
							echo '<option value="' . esc_attr( $match->home_team_id ) . '" ' . selected( $prediction->predicted_winner_team_id ?? 0, $match->home_team_id, false ) . '>' . esc_html( $match->home_name ) . '</option>';
						}
						if ( $match->away_team_id ) {
							echo '<option value="' . esc_attr( $match->away_team_id ) . '" ' . selected( $prediction->predicted_winner_team_id ?? 0, $match->away_team_id, false ) . '>' . esc_html( $match->away_name ) . '</option>';
						}
						?>
					</select>
				</label>
			<?php endif; ?>
			<button class="qm2026-button" <?php disabled( $locked ); ?>><?php echo $locked ? esc_html__( 'Cerrado', QM2026_TEXT_DOMAIN ) : esc_html__( 'Guardar', QM2026_TEXT_DOMAIN ); ?></button>
			<span class="qm2026-message"><?php echo $locked ? esc_html__( 'Predicción cerrada', QM2026_TEXT_DOMAIN ) : ''; ?></span>
		</form>
	<?php endforeach; ?>
</div>
