<?php
/**
 * Plugin Name: Quiniela Mundialista 2026
 * Description: Quinielas privadas para el Mundial FIFA 2026 con predicciones, resultados, puntajes y ranking.
 * Version: 1.0.0
 * Author: OpenAI
 * Text Domain: quiniela-mundialista-2026
 * Domain Path: /languages
 * Requires PHP: 8.0
 * Requires at least: 6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'QM2026_VERSION', '1.0.0' );
define( 'QM2026_FIXTURE_DATA_VERSION', '2026-06-11-official-fifa-schedule-guatemala-time' );
define( 'QM2026_FILE', __FILE__ );
define( 'QM2026_PATH', plugin_dir_path( __FILE__ ) );
define( 'QM2026_URL', plugin_dir_url( __FILE__ ) );
define( 'QM2026_TEXT_DOMAIN', 'quiniela-mundialista-2026' );

require_once QM2026_PATH . 'includes/helpers.php';
require_once QM2026_PATH . 'includes/class-db.php';
require_once QM2026_PATH . 'includes/class-activator.php';
require_once QM2026_PATH . 'includes/class-security.php';
require_once QM2026_PATH . 'includes/class-scoring.php';
require_once QM2026_PATH . 'includes/class-import-export.php';
require_once QM2026_PATH . 'includes/class-admin.php';
require_once QM2026_PATH . 'includes/class-shortcodes.php';
require_once QM2026_PATH . 'includes/class-ajax.php';
require_once QM2026_PATH . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( 'QM2026_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'QM2026_Activator', 'deactivate' ) );

add_action(
	'plugins_loaded',
	function () {
		load_plugin_textdomain( QM2026_TEXT_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		QM2026_Plugin::instance()->run();
	}
);
