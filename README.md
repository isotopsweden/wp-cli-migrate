# wp-cli-migrate

Simple data migration for WordPress.

## Using

Create a migration file `migrations/example.php` or the directory of your choice defined in `MIGRATION_DIR`

```php
use Isotop\Migration\Migration;

class MyClassName extends Migration {
	public function up() {
		update_option( 'key', 'value' );
	}

	public function down() {
		delete_option( 'key' );
	}
}
```

Run the migration up:

```
wp migrate up
```

Run the migration down:

```
wp migrate down
```

## Installing

Installing this package requires WP-CLI v1.1.0 or greater. Update to the latest stable release with `wp cli update`.

Once you've done so, you can install this package with:

```
wp package install git@github.com:isotopsweden/wp-cli-migrate.git
```

## License

MIT © Isotop
