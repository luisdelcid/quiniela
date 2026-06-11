<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM2026_DB {
	public static function schema(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();

		$sql = array();
		$sql[] = 'CREATE TABLE ' . qm2026_table( 'pools' ) . " (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(190) NOT NULL,
			description TEXT NULL,
			access_code VARCHAR(40) NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'open',
			rules LONGTEXT NULL,
			general_deadline DATETIME NULL,
			allow_public TINYINT(1) NOT NULL DEFAULT 1,
			allow_guests TINYINT(1) NOT NULL DEFAULT 1,
			created_by BIGINT UNSIGNED NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY access_code (access_code)
		) $charset;";
		$sql[] = 'CREATE TABLE ' . qm2026_table( 'participants' ) . " (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			pool_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NULL,
			name VARCHAR(190) NOT NULL,
			email VARCHAR(190) NOT NULL,
			token VARCHAR(80) NOT NULL,
			active TINYINT(1) NOT NULL DEFAULT 1,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY pool_email (pool_id,email),
			UNIQUE KEY token (token),
			KEY pool_id (pool_id),
			KEY user_id (user_id)
		) $charset;";
		$sql[] = 'CREATE TABLE ' . qm2026_table( 'teams' ) . " (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			team_code VARCHAR(10) NOT NULL,
			name VARCHAR(190) NOT NULL,
			group_code VARCHAR(2) NULL,
			flag VARCHAR(20) NULL,
			qualified TINYINT(1) NOT NULL DEFAULT 1,
			placeholder TINYINT(1) NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			UNIQUE KEY team_code (team_code),
			KEY group_code (group_code)
		) $charset;";
		$sql[] = 'CREATE TABLE ' . qm2026_table( 'matches' ) . " (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			fifa_match_no INT NULL,
			phase VARCHAR(30) NOT NULL,
			group_code VARCHAR(2) NULL,
			home_team_id BIGINT UNSIGNED NULL,
			away_team_id BIGINT UNSIGNED NULL,
			home_placeholder VARCHAR(190) NULL,
			away_placeholder VARCHAR(190) NULL,
			match_datetime DATETIME NOT NULL,
			stadium VARCHAR(190) NULL,
			city VARCHAR(190) NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'scheduled',
			home_score INT NULL,
			away_score INT NULL,
			penalty_winner_team_id BIGINT UNSIGNED NULL,
			prediction_deadline DATETIME NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY phase (phase),
			KEY group_code (group_code),
			KEY status (status),
			KEY match_datetime (match_datetime)
		) $charset;";
		$sql[] = 'CREATE TABLE ' . qm2026_table( 'predictions' ) . " (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			pool_id BIGINT UNSIGNED NOT NULL,
			participant_id BIGINT UNSIGNED NOT NULL,
			match_id BIGINT UNSIGNED NOT NULL,
			home_score INT NULL,
			away_score INT NULL,
			predicted_winner_team_id BIGINT UNSIGNED NULL,
			locked_at DATETIME NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY participant_match (participant_id,match_id),
			KEY pool_match (pool_id,match_id)
		) $charset;";
		$sql[] = 'CREATE TABLE ' . qm2026_table( 'results' ) . " (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			match_id BIGINT UNSIGNED NOT NULL,
			home_score INT NOT NULL,
			away_score INT NOT NULL,
			winner_team_id BIGINT UNSIGNED NULL,
			penalty_winner_team_id BIGINT UNSIGNED NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY match_id (match_id)
		) $charset;";
		$sql[] = 'CREATE TABLE ' . qm2026_table( 'scores' ) . " (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			pool_id BIGINT UNSIGNED NOT NULL,
			participant_id BIGINT UNSIGNED NOT NULL,
			match_id BIGINT UNSIGNED NOT NULL,
			points INT NOT NULL DEFAULT 0,
			exact_score TINYINT(1) NOT NULL DEFAULT 0,
			winner_correct TINYINT(1) NOT NULL DEFAULT 0,
			goal_diff_correct TINYINT(1) NOT NULL DEFAULT 0,
			team_goals_correct TINYINT(1) NOT NULL DEFAULT 0,
			goal_diff_abs INT NOT NULL DEFAULT 0,
			details LONGTEXT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY participant_match (participant_id,match_id),
			KEY ranking (pool_id,participant_id)
		) $charset;";
		$sql[] = 'CREATE TABLE ' . qm2026_table( 'settings' ) . " (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			setting_key VARCHAR(190) NOT NULL,
			setting_value LONGTEXT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY setting_key (setting_key)
		) $charset;";
		$sql[] = 'CREATE TABLE ' . qm2026_table( 'logs' ) . " (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			event VARCHAR(80) NOT NULL,
			message TEXT NOT NULL,
			context LONGTEXT NULL,
			user_id BIGINT UNSIGNED NULL,
			ip_address VARCHAR(80) NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY event (event),
			KEY created_at (created_at)
		) $charset;";

		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}
	}
}
