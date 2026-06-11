<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM2026_Security {
	public static function can_manage(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( 'qm2026_manage_pools' );
	}

	public static function rate_limit( string $action, int $limit = 8, int $window = 300 ): bool {
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$key = 'qm2026_rate_' . md5( $action . '|' . $ip );
		$hit = get_transient( $key );
		if ( false === $hit ) {
			set_transient( $key, 1, $window );
			return true;
		}
		if ( (int) $hit >= $limit ) {
			return false;
		}
		set_transient( $key, (int) $hit + 1, $window );
		return true;
	}
}
