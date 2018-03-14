<?php

namespace Isotop\WP_CLI\Commands;

use WP_CLI;

class Migrate_Command extends \WP_CLI_Command {

	/**
	 * Run migration files.
	 *
	 * ## CONSTANTS
	 *
	 * MIGRATION_DIR
	 * Directory for migration-files.
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function run( $args, $assoc_args ) {
		$migration_dir = defined( 'MIGRATION_DIR' ) ? MIGRATION_DIR : 'migrations';

		if ( ! is_dir( $migration_dir ) ) {
			WP_CLI::error( 'Migration dir not found' );
		}

		$done_migrations = [];
		if ( empty( $assoc_args['force'] ) ) {
			$done_migrations = get_site_option( 'migrations_done', [] );
		}
		
		if ( ! is_array( $done_migrations ) ) {
			$done_migrations = [];
		}

		foreach ( glob( $migration_dir . '/*.php' ) as $migration_file ) {
			if ( ! empty( $args ) ) {
				preg_match( '/^([^.]+)\.php$/', basename( $migration_file ), $name );
				if ( ! in_array( $args[0], $name, true ) ) {
					continue;
				}
			}

			if ( in_array( basename( $migration_file ), $done_migrations, true ) ) {
				WP_CLI::log( 'Skipping ' . basename( $migration_file ) );
			} else {
				WP_CLI::log( 'Running ' . basename( $migration_file ) );
				include $migration_file;
				$done_migrations[] = basename( $migration_file );
			}
		}

		update_site_option( 'migrations_done', $done_migrations );
	}
}
