<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="qm2026 qm2026-landing">
	<h2><?php esc_html_e( 'Quiniela Mundialista 2026', QM2026_TEXT_DOMAIN ); ?></h2>
	<?php if ( $participant ) : ?>
		<div class="qm2026-notice">
			<?php printf( esc_html__( 'Bienvenido/a %s. Ya puedes guardar tus predicciones.', QM2026_TEXT_DOMAIN ), esc_html( $participant->name ) ); ?>
		</div>
	<?php else : ?>
		<?php if ( $is_logged_in ) : ?>
			<p><?php esc_html_e( 'Ingresa tu nombre y código privado para unirte. Usaremos el correo de tu cuenta.', QM2026_TEXT_DOMAIN ); ?></p>
		<?php else : ?>
			<p><?php esc_html_e( 'Ingresa tu nombre, email y código privado para unirte.', QM2026_TEXT_DOMAIN ); ?></p>
		<?php endif; ?>
		<form class="qm2026-join-form">
			<div class="qm2026-grid">
				<label>
					<?php esc_html_e( 'Nombre', QM2026_TEXT_DOMAIN ); ?>
					<input name="name" value="<?php echo esc_attr( $default_name ); ?>" required>
				</label>
				<?php if ( ! $is_logged_in ) : ?>
					<label>
						<?php esc_html_e( 'Email', QM2026_TEXT_DOMAIN ); ?>
						<input type="email" name="email" required>
					</label>
				<?php endif; ?>
				<label>
					<?php esc_html_e( 'Código', QM2026_TEXT_DOMAIN ); ?>
					<input name="code" required>
				</label>
			</div>
			<button class="qm2026-button"><?php esc_html_e( 'Unirme', QM2026_TEXT_DOMAIN ); ?></button>
			<span class="qm2026-message"></span>
		</form>
	<?php endif; ?>
</div>
