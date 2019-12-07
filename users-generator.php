<?php
/*
Plugin Name: posterno-users-generator
Plugin URI:  https://posterno.com
Description:
Version: 1.0.0
Author:      Alessandro Tesoro
Author URI:  https://alessandrotesoro.me
License:     GPLv2+
*/

namespace Posterno\CLI;

use WP_CLI;

// Bail if WP-CLI is not present.
if ( ! defined( '\WP_CLI' ) ) {
	return;
}

WP_CLI::add_hook(
	'before_wp_load',
	function() {
		require __DIR__ . '/vendor/autoload.php';
		require_once __DIR__ . '/commands/users-generator.php';

		WP_CLI::add_command(
			'posterno users',
			__NAMESPACE__ . '\\Command\\UsersGenerator',
			array(
				'before_invoke' => function() {
					if ( ! class_exists( 'Posterno' ) ) {
						WP_CLI::error( 'The Posterno plugin is not active.' );
					}
				},
			)
		);

	}
);
