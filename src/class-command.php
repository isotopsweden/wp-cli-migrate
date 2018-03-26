<?php

namespace Isotop\Migration;

use ReflectionClass;
use WP_CLI;

class Command extends \WP_CLI_Command {

	/**
	 * Run migration files up.
	 *
	 * ## CONSTANTS
	 *
	 * MIGRATION_DIR
	 * Directory for migration-files.
	 *
	 * ## OPTIONS
	 *
	 * [--file=<file>]
	 * : Specific file to migrate.
	 *
	 * [--force]
	 * : Force migration.
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function up( $args, $assoc_args ) {
		$assoc_args['type'] = 'up';

		$this->run( $assoc_args, function( $class ) {
			return $class->up();
		} );
	}

	/**
	 * Run migration files down.
	 *
	 * ## CONSTANTS
	 *
	 * MIGRATION_DIR
	 * Directory for migration-files.
	 *
	 * ## OPTIONS
	 *
	 * [--file=<file>]
	 * : Specific file to migrate.
	 *
	 * [--force]
	 * : Force migration.
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function down( $args, $assoc_args ) {
		$assoc_args['type'] = 'down';

		$this->run( $assoc_args, function( $class ) {
			return $class->down();
		} );
	}

	/**
	 * Get migration files.
	 *
	 * @return array
	 */
	protected function get_files() {
		$dir = defined( 'MIGRATION_DIR' ) ? MIGRATION_DIR : 'migrations';

		if ( ! is_dir( $dir ) ) {
			WP_CLI::error( 'Migration dir not found' );
		}

		return glob( $dir . '/*.php' );
	}

	/**
	 * Run files.
	 *
	 * @param string $type
	 * @param bool   $force
	 * @param object $callback
	 */
	protected function run( array $args, callable $callback ) {
		$migrations_done = [];

		if ( empty( $args['force'] ) ) {
			$migrations_done = get_site_option( 'migrations_done', [] );
		}

		foreach ( $this->get_files() as $file ) {
			// Run only specific file if any.
			if ( ! empty( $args['file'] ) ) {
				$f = basename( $args['file'] );

				if ( $f !== basename( $file ) ) {
					continue;
				}
			}

			$class_name = $this->get_class_name( $file );

			// Require class if not exists.
			if ( ! class_exists( $class_name ) ) {
				require_once $file;
			}

			// Bail if not class exists.
			if ( ! class_exists( $class_name ) ) {
				continue;
			}

			$rc = new ReflectionClass( $class_name );

			// Bail if not instantiable.
			if ( ! $rc->isInstantiable() ) {
				continue;
			}

			$type  = $args['type'];
			$key   = basename( $file );
			$old   = in_array( $key, $migrations_done, true ); // old array with only basename values.
			$class = $rc->newinstance();

			if ( $old || ( isset( $migrations_done[$key . '_up'] ) && $migrations_done[$key . '_up'] ) ) {
				WP_CLI::log( 'Skipping: ' . $key );
			} else {
				WP_CLI::log( 'Running: ' . $key );

				$output = $callback( $class );

				if ( ! empty( $output ) ) {
					WP_CLI::log( $output );
				}

				if ( $type === 'up' ) {
					$migrations_done[$key] = true;
				} else if ( isset( $migrations_done[$key] ) ) {
					unset( $migrations_done[$key] );
				}
			}

			if ( $old ) {
				$migrations_done = array_diff( $migrations_done, [$key] );

				if ( $type === 'up' ) {
					$migrations_done[$key] = true;
				}
			}
		}

		update_site_option( 'migrations_done', $migrations_done );
	}

	/**
	 * Get class name from file.
	 *
	 * @param  string $file
	 *
	 * @return string
	 */
	protected function get_class_name( $file ) {
		if ( ! is_string( $file ) || ! file_exists( $file ) ) {
			return '';
		}

		$content         = file_get_contents( $file );
		$tokens          = token_get_all( $content );
		$class_name      = '';
		$namespace_name  = '';
		$i               = 0;
		$len             = count( $tokens );

		for ( ; $i < $len; $i++ ) {
			if ( $tokens[$i][0] === T_NAMESPACE ) {
				for ( $j = $i + 1; $j < $len; $j++ ) {
					if ( $tokens[$j][0] === T_STRING ) {
						 $namespace_name .= '\\' . $tokens[$j][1];
					} else if ( $tokens[$j] === '{' || $tokens[$j] === ';' ) {
						 break;
					}
				}
			}

			if ( $tokens[$i][0] === T_CLASS ) {
				for ( $j = $i + 1; $j < $len; $j++ ) {
					if ( $tokens[$j] === '{' ) {
						$class_name = $tokens[$i + 2][1];
					}
				}
			}
		}

		if ( empty( $class_name ) ) {
			return '';
		}

		if ( empty( $namespace_name ) ) {
			return $class_name;
		}

		return $namespace_name . '\\' . $class_name;
	}
}
