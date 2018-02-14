# isotopsweden/wp-cli-migrate

Simple data migration for WordPress.

## Using

Create a migration file `migrations/example.php` or the directory of your choice defined in `MIGRATION_DIR`

```php
foreach ( get_sites() as $site ) {
	switch_to_blog( $site->blog_id );
	update_option( 'hello', 'world' );
	restore_current_blog();
}
```

Run the migration with wp cli

```
wp migrate run
```

## Installing

Installing this package requires WP-CLI v1.1.0 or greater. Update to the latest stable release with `wp cli update`.

Once you've done so, you can install this package with:

```
wp package install git@github.com:isotopsweden/wp-cli-migrate.git
```

## License

MIT © Isotop
