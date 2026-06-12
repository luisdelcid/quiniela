<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$participants = $wpdb->get_results( 'SELECT p.*,pool.name pool_name,COALESCE(SUM(s.points),0) pts FROM ' . qm2026_table( 'participants' ) . ' p LEFT JOIN ' . qm2026_table( 'pools' ) . ' pool ON pool.id=p.pool_id LEFT JOIN ' . qm2026_table( 'scores' ) . ' s ON s.participant_id=p.id GROUP BY p.id ORDER BY p.created_at DESC' );
?>
<div class="wrap qm2026">
	<h1><?php esc_html_e( 'Participantes', QM2026_TEXT_DOMAIN ); ?></h1>
	<?php if ( ! empty( $_GET['qm2026_notice'] ) ) : ?>
		<div class="notice notice-success"><p><?php echo esc_html( wp_unslash( $_GET['qm2026_notice'] ) ); ?></p></div>
	<?php endif; ?>
	<table class="widefat striped">
		<thead><tr><th><?php esc_html_e( 'Nombre', QM2026_TEXT_DOMAIN ); ?></th><th><?php esc_html_e( 'Email', QM2026_TEXT_DOMAIN ); ?></th><th><?php esc_html_e( 'Quiniela', QM2026_TEXT_DOMAIN ); ?></th><th><?php esc_html_e( 'Registro', QM2026_TEXT_DOMAIN ); ?></th><th><?php esc_html_e( 'Puntos', QM2026_TEXT_DOMAIN ); ?></th><th><?php esc_html_e( 'Link privado', QM2026_TEXT_DOMAIN ); ?></th><th><?php esc_html_e( 'Acciones', QM2026_TEXT_DOMAIN ); ?></th></tr></thead>
		<tbody>
			<?php foreach ( $participants as $p ) : $url = add_query_arg( 'qm2026_token', $p->token, home_url( '/' ) ); ?>
				<tr>
					<td><?php echo esc_html( $p->name ); ?></td>
					<td><?php echo esc_html( $p->email ); ?></td>
					<td><?php echo esc_html( $p->pool_name ); ?></td>
					<td><?php echo esc_html( $p->created_at ); ?></td>
					<td><?php echo esc_html( $p->pts ); ?></td>
					<td><code><?php echo esc_html( $url ); ?></code></td>
					<td>
						<form method="post" onsubmit="return confirm('<?php echo esc_js( __( '¿Seguro que quieres eliminar este participante? También se eliminarán sus predicciones y puntajes.', QM2026_TEXT_DOMAIN ) ); ?>');">
							<?php wp_nonce_field( 'qm2026_admin_action' ); ?>
							<input type="hidden" name="qm2026_action" value="delete_participant">
							<input type="hidden" name="id" value="<?php echo esc_attr( $p->id ); ?>">
							<button type="submit" class="button button-small button-link-delete"><?php esc_html_e( 'Eliminar', QM2026_TEXT_DOMAIN ); ?></button>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
