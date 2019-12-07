<?php

namespace Posterno\CLI\Command;

use WP_CLI;

/**
 * Manage tools.
 */
class UsersGenerator extends \WP_CLI_Command {

	/**
	 * Generate random users for Posterno.
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp posterno users generate
	 */
	public function generate( $args, $assoc_args ) {

		if ( is_multisite() ) {
			WP_CLI::error( 'Multisite is not supported!' );
		}

		$defaults   = array(
			'number' => 30,
			'key'    => false,
		);
		$assoc_args = wp_parse_args( $assoc_args, $defaults );

		$number = absint( $assoc_args['number'] );
		$key    = $assoc_args['key'];

		$avatars = $this->get_avatars( $number, $key );

		$notify = \WP_CLI\Utils\make_progress_bar( "Generating $number users(s)", $number );

		foreach ( range( 0, $number ) as $i ) {
			$notify->tick();
			$this->register_user( $avatars );
		}

		$notify->finish();

		WP_CLI::success( 'Done.' );

	}

	/**
	 * Create a random user.
	 *
	 * @param array $avatars list of avatars found from the api.
	 * @return void
	 */
	private function register_user( $avatars = [] ) {

		$faker = \Faker\Factory::create();

		$password = wp_generate_password( 12, false );
		$username = $faker->userName;
		$email    = $faker->safeEmail;

		$create_user = wp_create_user( $username, $password, $email );

		if ( ! is_wp_error( $create_user ) ) {

			$random_avatar = \Faker\Provider\Base::randomElements( $avatars, 1 );
			$avatar        = false;

			if ( isset( $random_avatar[0] ) && ! empty( $random_avatar[0] ) ) {
				$avatar = pno_rest_upload_image_from_url( $random_avatar[0] );
			}

			if ( is_array( $avatar ) ) {
				carbon_set_user_meta( $create_user, 'current_user_avatar', $avatar['url'] );
				update_user_meta( $create_user, 'current_user_avatar_path', $avatar['file'] );
			}

			wp_update_user(
				array(
					'ID'         => $create_user,
					'first_name' => $faker->firstName,
					'last_name'  => $faker->lastName,
				)
			);
		}

	}

	/**
	 * Get avatars from the api.
	 *
	 * @param integer $number the number of avatars to load.
	 * @param string  $key the api key.
	 * @return array
	 */
	private function get_avatars( $number = 30, $key ) {

		$avatars = [];

		$query = wp_remote_get(
			'https://uifaces.co/api?limit=' . $number,
			[
				'headers' => [
					'X-API-KEY'     => $key,
					'Accept'        => 'application/json',
					'Cache-Control' => 'no-cache',
				],
			]
		);

		$response = json_decode( wp_remote_retrieve_body( $query ) );

		if ( ! empty( $response ) && is_array( $response ) ) {
			foreach ( $response as $profile ) {
				$avatars[] = esc_url( $profile->photo );
			}
		}

		return $avatars;

	}

}
