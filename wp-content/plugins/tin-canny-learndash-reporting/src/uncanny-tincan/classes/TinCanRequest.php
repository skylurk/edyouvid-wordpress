<?php
/**
 * Processing Request
 *
 * @package    Tin Canny Reporting for LearnDash
 * @subpackage TinCan Module
 * @author     Uncanny Owl
 * @since      1.0.0
 */

namespace UCTINCAN;

if ( ! defined( 'UO_ABS_PATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

/**
 *
 */
abstract class TinCanRequest {
	use \UCTINCAN\Modules;

	//phpcs:disable WordPress.NamingConventions
	//phpcs:disable WordPress.PHP.DontExtract

	/**
	 * TinCan Objects
	 *
	 * @access protected
	 * @since  1.0.0
	 */
	protected $TC_Agent;
	/**
	 * @var
	 */
	protected $TC_Actitity;
	/**
	 * @var
	 */
	protected $TC_Context;
	/**
	 * @var
	 */
	protected $TC_Verb;
	/**
	 * @var
	 */
	protected $TC_Result;

	/**
	 * Content ID
	 *
	 * @access protected
	 * @since  1.0.0
	 */
	protected $content_id;

	/**
	 * @var
	 */
	protected $lesson_id;
	/**
	 * @var
	 */
	protected $user_id;

	/**
	 * Set TinCan Objects from Requested Array
	 *
	 * @access protected
	 *
	 * @param  array $decoded
	 *
	 * @return void
	 * @since  1.0.0
	 */
	protected function init_tincan_objects( $decoded ) {
		// Preprocess actor data to handle arrays.
		if ( ! empty( $decoded['actor'] ) ) {
			// Handle mbox as array.
			if ( isset( $decoded['actor']['mbox'] ) && is_array( $decoded['actor']['mbox'] ) && ! empty( $decoded['actor']['mbox'] ) ) {
				$decoded['actor']['mbox'] = $decoded['actor']['mbox'][0];
			}

			// Handle name as array.
			if ( isset( $decoded['actor']['name'] ) && is_array( $decoded['actor']['name'] ) && ! empty( $decoded['actor']['name'] ) ) {
				$decoded['actor']['name'] = $decoded['actor']['name'][0];
			}

			// Handle mbox_sha1sum as array.
			if ( isset( $decoded['actor']['mbox_sha1sum'] ) && is_array( $decoded['actor']['mbox_sha1sum'] ) && ! empty( $decoded['actor']['mbox_sha1sum'] ) ) {
				$decoded['actor']['mbox_sha1sum'] = $decoded['actor']['mbox_sha1sum'][0];
			}

			// Handle openid as array.
			if ( isset( $decoded['actor']['openid'] ) && is_array( $decoded['actor']['openid'] ) && ! empty( $decoded['actor']['openid'] ) ) {
				$decoded['actor']['openid'] = $decoded['actor']['openid'][0];
			}

			// Handle account properties if they're arrays.
			if ( isset( $decoded['actor']['account'] ) ) {
				// Handle account name as array.
				if ( isset( $decoded['actor']['account']['name'] ) && is_array( $decoded['actor']['account']['name'] ) && ! empty( $decoded['actor']['account']['name'] ) ) {
					$decoded['actor']['account']['name'] = $decoded['actor']['account']['name'][0];
				}

				// Handle account homePage as array.
				if ( isset( $decoded['actor']['account']['homePage'] ) && is_array( $decoded['actor']['account']['homePage'] ) && ! empty( $decoded['actor']['account']['homePage'] ) ) {
					$decoded['actor']['account']['homePage'] = $decoded['actor']['account']['homePage'][0];
				}
			}
		}

		$this->TC_Agent    = ( ! empty( $decoded['actor'] ) ) ? new \TinCan\Agent( $decoded['actor'] ) : new \TinCan\Agent();
		$this->TC_Actitity = ( ! empty( $decoded['object'] ) ) ? new \TinCan\Activity( $decoded['object'] ) : new \TinCan\Activity();
		$this->TC_Context  = ( ! empty( $decoded['context'] ) ) ? new \TinCan\Context( $decoded['context'] ) : new \TinCan\Context();
		$this->TC_Verb     = ( ! empty( $decoded['verb'] ) ) ? new \TinCan\Verb( $decoded['verb'] ) : new \TinCan\Verb();

		if ( isset( $decoded['result'] ) ) {
			$result = $this->vaidate_result_parameters( $decoded['result'] );
		}

		$this->TC_Result = ( ! empty( $result ) ) ? new \TinCan\Result( $result ) : new \TinCan\Result();
		$account         = $this->TC_Agent->getAccount();

		if ( ! empty( $account ) && ! empty( $account->getName() ) && filter_var( $account->getName(), FILTER_VALIDATE_EMAIL ) ) {
			return $account->getName();
		}
		return $this->TC_Agent->getMbox();
	}

	/**
	 * Save Data
	 *
	 * @access protected
	 * @return array|void
	 * @since  1.0.0
	 */
	protected function save() {
		/*error_reporting(E_ALL);
		ini_set('display_errors', 1);*/
		global $post;

		if ( ! $this->TC_Agent ) {
			return;
		}

		// Agent
		$userEmail = $this->TC_Agent->getMbox();
		if ( is_null( $userEmail ) ) {
			$account = $this->TC_Agent->getAccount();

			if ( ! empty( $account->getName() ) && filter_var( $account->getName(), FILTER_VALIDATE_EMAIL ) ) {
				$userEmail = $account->getName();
			}
		}

		if ( ! $userEmail ) {
			return;
		}

		$userEmail = str_replace( 'mailto:', '', $userEmail );
		$wpUser    = apply_filters( 'uo_tincanny_reporting_validate_wp_user' , get_user_by( 'email', $userEmail ), $userEmail);

		if ( ! is_object($wpUser) || ! $wpUser->ID || ! ($wpUser instanceof \WP_User) ) {
			return;
		}

		$user_id       = $wpUser->ID;
		$this->user_id = $user_id;

		// Lesson, Course, Group
		$grouping = $this->TC_Context->getContextActivities()->getGrouping();
		$grouping = array_pop( $grouping );
		// Group and Parent
		$auth = null;
		if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
			parse_str( $_SERVER['HTTP_REFERER'], $referer );
			if ( strstr( $_SERVER['HTTP_REFERER'], '&client=' ) !== false ) {
				if ( ! empty( $referer['auth'] ) ) {
					$auth = $referer['auth'];
				}
			}
		}

		if ( empty( $auth ) ) {
			// Try to read all headers first.
			if ( function_exists( 'getallheaders' ) ) {
				$all_headers = getallheaders();
				if ( isset( $all_headers['Authorization'] ) ) {
					$auth = $all_headers['Authorization'];
				}
			}
		}

		if ( empty( $auth ) ) {
			$contents = file_get_contents( 'php://input' );
			$decoded  = json_decode( $contents, true );
			if ( ! is_array( $decoded ) ) {
				parse_str( $contents, $decoded_2 );
			}
			if ( isset( $decoded_2['Authorization'] ) ) {
				$auth = $decoded_2['Authorization'];
			}
		}

		if ( ! empty( $auth ) ) {
			$lesson_id = substr( $auth, 11 );
		}

		if ( empty( $lesson_id ) || ! is_numeric( $auth ) ) {
			$lesson_id = get_user_meta( $user_id, 'tincan_last_known_ld_module', true );
		}

		$this->lesson_id = $lesson_id;
		$course_id       = ultc_get_filter_var( 'course_id', '' );
		if ( empty( $course_id ) ) {
			$course_id = get_user_meta( $user_id, 'tincan_last_known_ld_course', true );
		}

		$group_id  = $this->get_learndash_user_enrolled_group_id( $wpUser->ID, $course_id );
		$group_id  = ( $group_id ) ? $group_id : 0;
		$course_id = ( $course_id ) ? $course_id : 0;

		// Verb
		$verb = $this->TC_Verb->getId();

		if ( is_string( $verb ) ) {
			$verb = array_filter( explode( '/', $verb ) );
			$verb = array_pop( $verb );
			$verb = strtolower( $verb );
		}

		// GET ACTIVITY Details
		$activity_id = $this->TC_Actitity->getId();
		$activity    = $this->TC_Actitity->getDefinition();
		if ( ! empty( $activity ) ) {
			$activity_data = $activity->getDescription();

			// Lets define lang iso codes for lookup.
			$lang_keys = array( 'en-US', 'en-GB', 'en' );

			/**
			 * Filter: uo_tincanny_reporting_module_request_language_keys
			 *
			 * @param array $lang_keys
			 * @param array $args
			 *
			 * @return array
			 */
			$lang_keys_filtered = apply_filters(
				'uo_tincanny_reporting_module_request_language_keys',
				$lang_keys,
				array(
					'activity'  => $activity,
					'group_id'  => $group_id,
					'course_id' => $course_id,
					'lesson_id' => $lesson_id,
					'user_id'   => $user_id,
				)
			);
			if ( is_array( $lang_keys_filtered ) && ! empty( $lang_keys_filtered ) ) {
				$lang_keys = $lang_keys_filtered;
			}

			$activity_name = $this->get_localized_activity_text( $activity_data, $lang_keys );

			if ( empty( $activity_name ) && method_exists( $activity, 'getName' ) ) {
				$activity_name = $this->get_localized_activity_text( $activity->getName(), $lang_keys );
			}

			extract( $this->parse_responses() );
		}

		// Module and Target
		extract( $this->get_module_and_target() );

		// Result
		$completion   = false;
		$result       = false;
		$maximum      = false;
		$max_score    = false;
		$min_score    = false;
		$raw_score    = false;
		$scaled_score = false;
		$duration     = 0;

		if ( $this->TC_Result->getScore() ) {

			// Check if we should try to auto calculate null scaled scores.
			if ( is_null( $this->TC_Result->getScore()->getScaled() ) ) {
				if ( true === apply_filters(
					'uo_tincanny_reporting_auto_calculate_scaled_score',
					false,
					array(
						'activity'    => $activity,
						'group_id'    => $group_id,
						'course_id'   => $course_id,
						'lesson_id'   => $lesson_id,
						'module_name' => $module_name,
						'verb'        => $verb,
					)
				) ) {
					$this->maybe_set_scaled_score_from_raw( $this->TC_Result->getScore() );
				}
			}

			if ( ! is_null( $this->TC_Result->getScore()->getScaled() ) ) {
				$scaled_score = $this->TC_Result->getScore()->getScaled();
				$result       = $scaled_score * 100;
				$maximum      = 100;
			}

			if ( false === $result && $this->TC_Result->getScore()->getRaw() ) {
				$result = $this->TC_Result->getScore()->getRaw();
				//$result = ($result > 0 ) ? 100 : 0;
			}

			if ( ! is_null( $this->TC_Result->getScore()->getMax() ) ) {
				$max_score = $this->TC_Result->getScore()->getMax();
			}

			if ( ! is_null( $this->TC_Result->getScore()->getMin() ) ) {
				$min_score = $this->TC_Result->getScore()->getMin();
			}

			if ( ! is_null( $this->TC_Result->getScore()->getRaw() ) ) {
				$raw_score = $this->TC_Result->getScore()->getRaw();
			}
		}

		if ( $this->TC_Result->getSuccess() ) {
			if ( ! is_null( $this->TC_Result->getScore() ) && ! is_null( $this->TC_Result->getScore()->getScaled() ) ) {
				$result = $this->TC_Result->getScore()->getScaled() * 100;
			}

			if ( false === $result && ! is_null( $this->TC_Result->getScore() ) && $this->TC_Result->getScore()->getRaw() ) {
				$result = $this->TC_Result->getScore()->getRaw();
			}
		}

		if ( ! is_null( $this->TC_Result->getDuration() ) ) {
			$duration = 0;//self::ISO8601ToSeconds( $this->TC_Result->getDuration() );
		}

		if ( ! is_null( $this->TC_Result->getSuccess() ) ) {
			$completion = ( $this->TC_Result->getSuccess() ) ? 1 : 0;
		}

		if ( $this->TC_Result->getCompletion() ) {
			$completion = 1;
		}

		if ( ! $verb ) {
			return;
		}

		// Fix - Unset result if answer is incorrect.
		if ( $result && ! $this->TC_Result->getSuccess() && ! $this->TC_Result->getCompletion() && 'answered' === $verb ) {
			$result = false;
		}

		$db_data = compact(
			'group_id',
			'course_id',
			'lesson_id',
			'module',
			'module_name',
			'target',
			'target_name',
			'verb',
			'result',
			'maximum',
			'completion',
			'user_id'
		);
		$allow_db_capture = apply_filters( 'tincanny_module_allow_db_capture', true, $db_data );

		if ( true !== $allow_db_capture ) {
			return $db_data;
		}

		// Save
		$database = new Database();
		$database->set_report( $group_id, $course_id, $lesson_id, $module, $module_name, $target, $target_name, $verb, $result, $maximum, $completion, $user_id );

		$module_match = $database->get_slide_id_from_module( $module );
		if ( isset( $module_match[1] ) ) {

			$module_id = $module_match[1];
			if ( ( $this->TC_Result->getCompletion() || in_array( strtolower( $verb ), array( 'passed', 'completed', 'failed' ), true ) ) && $result >= 0 ) {
				do_action( 'tincanny_module_result_processed', $module_id, $user_id, $result );
			}
		}

		if ( ! is_null( $this->TC_Result->getResponse() ) ) {

			if ( $this->TC_Result->getSuccess() ) {
				if ( false === $result && is_null( $this->TC_Result->getScore() ) ) {
					$result = 1;
				}
			}

			$tincanny_result_override = apply_filters( 'tincanny_result_override', null, $this->TC_Result );
			if ( ! is_null( $tincanny_result_override ) ) {
				$result = $tincanny_result_override;
			}


			$database->set_quiz_data(
				$group_id,
				$course_id,
				$lesson_id,
				$module,
				$module_name,
				$activity_id,
				$activity_name,
				$result,
				$user_id,
				$available_responses_string,
				$correct_response,
				$user_response,
				$max_score,
				$min_score,
				$raw_score,
				$scaled_score,
				$duration
			);
		}

		return compact( 'group_id', 'course_id', 'lesson_id', 'module', 'module_name', 'target', 'target_name', 'verb', 'result', 'maximum', 'completion', 'user_id' );
	}

	/**
	 * Maybe set the scaled score from the raw score.
	 *
	 * @param \TinCan\Score $tin_can_score
	 */
	private function maybe_set_scaled_score_from_raw( $tin_can_score ) {

		$raw = $tin_can_score->getRaw();
		$min = $tin_can_score->getMin();
		$max = $tin_can_score->getMax();

		// Validate props.
		if ( is_null( $raw ) || is_null( $min ) || is_null( $max ) ) {
			return $tin_can_score;
		}

		// Ensure that division by zero can not occur.
		if ( $max - $min == 0 ) {
			return $tin_can_score;
		}

		// Calculate the scaled value.
		$tcs_class = get_class( $tin_can_score );
		$scaled    = $tcs_class::SCALE_MIN + ( ( $raw - $min ) * ( $tcs_class::SCALE_MAX - $tcs_class::SCALE_MIN ) / ( $max - $min ) );

		// Set the scaled value.
		return $tin_can_score->setScaled( $scaled );
	}

	/**
	 * Get the localized activity text from a TinCan language map.
	 *
	 * @param object $data      TinCan language map or object with asVersion.
	 * @param array  $lang_keys Preferred language order.
	 *
	 * @return string
	 */
	protected function get_localized_activity_text( $data, $lang_keys ) {

		if ( empty( $data ) ) {
			return '';
		}

		$localized_text = '';

		if ( isset( $data->_map ) && is_array( $data->_map ) ) {
			$localized_text = isset( $data->_map['und'] ) ? $data->_map['und'] : '';

			foreach ( $lang_keys as $lang_key ) {
				if ( isset( $data->_map[ $lang_key ] ) ) {
					$localized_text = $data->_map[ $lang_key ];
					break;
				}
			}
		}

		if ( empty( $localized_text ) && method_exists( $data, 'asVersion' ) ) {
			$versions = $data->asVersion();
			if ( ! empty( $versions ) && is_array( $versions ) ) {
				foreach ( $lang_keys as $lang_key ) {
					if ( isset( $versions[ $lang_key ] ) ) {
						$localized_text = $versions[ $lang_key ];
						break;
					}
				}

				if ( empty( $localized_text ) ) {
					$localized_text = reset( $versions );
				}
			}
		}

		return $localized_text;
	}

	/**
	 * Get Module and Target
	 *
	 * @access abstract protected
	 * @return void
	 * @since  1.0.0
	 */
	abstract protected function get_module_and_target();

	/**
	 * Get Group ID from Course ID
	 *
	 * @access private
	 *
	 * @param  int $user_id
	 * @param  int $course_id
	 *
	 * @return void
	 * @since  1.0.0
	 */
	private function get_learndash_user_enrolled_group_id( $user_id, $course_id ) {
		$group_ids = learndash_get_users_group_ids( $user_id );

		foreach ( $group_ids as $group_id ) {
			if ( learndash_group_has_course( $group_id, $course_id ) ) {
				return $group_id;
			}
		}

		return false;
	}

	/**
	 * @param $grouping
	 *
	 * @return int
	 */
	private function get_lesson_id( $grouping ) {
		if ( ! $grouping ) {
			return 0;
		}

		if ( ! $grouping->getId() ) {
			return 0;
		}

		if ( url_to_postid( $grouping->getId() ) ) {
			return url_to_postid( $grouping->getId() );
		}

		parse_str( $grouping->getId(), $request_url );
		$queried_post = get_page_by_path(
			array_pop( $request_url ),
			OBJECT,
			array(
				'post',
				'sfwd-quiz',
				'sfwd-lessons',
				'sfwd-topic',
			)
		);

		if ( $queried_post && $queried_post->ID ) {
			return $queried_post->ID;
		}

		return 0;

	}

	/**
	 * Convert ISO 8601 values like P2DT15M33S to a total value of seconds.
	 *
	 * @param   string $ISO8601 .
	 *
	 * @return  string
	 */
	protected static function ISO8601ToSeconds( $ISO8601 ) {
		$ISO8601  = preg_replace( "'/.([0-9]{2})/'", '', $ISO8601 );
		$interval = new \DateInterval( $ISO8601 );

		return ( $interval->d * 24 * 60 * 60 ) + ( $interval->h * 60 * 60 ) + ( $interval->i * 60 ) + $interval->s;
	}

	/**
	 * Resolve display text from an xAPI interaction component description (language map).
	 *
	 * Matching and likert parsing used only the "und" key. Articulate Storyline
	 * multi-language packages typically use locale keys (en-US, fr-FR, etc.) and
	 * omit "und", which produced empty labels in the xAPI Quiz Report.
	 *
	 * @param mixed $description Language map array, string, or null.
	 * @return string
	 */
	private function get_interaction_description_string( $description ) {
		if ( is_string( $description ) ) {
			$trimmed = trim( $description );

			return ! empty( $trimmed ) ? $trimmed : '';
		}
		if ( ! is_array( $description ) || empty( $description ) ) {
			return '';
		}
		if ( isset( $description['und'] ) ) {
			$und = trim( (string) $description['und'] );
			if ( ! empty( $und ) ) {
				return $und;
			}
		}
		foreach ( $description as $value ) {
			if ( is_string( $value ) && ! empty( trim( $value ) ) ) {
				return trim( $value );
			}
		}

		return '';
	}

	/**
	 * @return array
	 */
	private function parse_responses() {

		$activity_defination = $this->TC_Actitity->getDefinition();
		$interaction_type    = $activity_defination->getInteractionType();
		switch ( $interaction_type ) {
			case 'true-false':
				extract( $this->correct_response_generic() );
				break;
			case 'choice':
				extract( $this->correct_response_choices() );
				break;
			case 'fill-in':
				extract( $this->correct_response_generic() );
				break;
			case 'long-fill-in':
				extract( $this->correct_response_generic() );
				break;
			case 'likert':
				extract( $this->correct_response_likert() );
				break;
			case 'matching':
				extract( $this->correct_response_matching() );
				break;
			case 'performance':
				break;
			case 'sequencing':
				extract( $this->correct_response_choices() );
				break;
			case 'numeric':
				extract( $this->correct_response_generic() );
				break;
			case 'other':
				extract( $this->correct_response_generic() );
				break;
			default:
				extract( $this->correct_response_generic() );
				break;

		}

		return compact( 'correct_response', 'available_responses', 'available_responses_string', 'user_response' );
	}

	/**
	 * @return array
	 */
	private function correct_response_generic() {
		$correct_response           = null;
		$available_responses        = null;
		$available_responses_string = null;
		$user_response              = null;
		$activity_defination        = $this->TC_Actitity->getDefinition();

		if ( ! is_null( $activity_defination->getCorrectResponsesPattern() ) ) {
			$correct_response = $activity_defination->getCorrectResponsesPattern();
			if ( is_array( $correct_response ) ) {
				$correct_response = implode( ', ', $correct_response );
			}
		}

		if ( ! is_null( $this->TC_Result->getResponse() ) ) {
			$user_response = $this->TC_Result->getResponse();
		}

		return compact( 'correct_response', 'available_responses', 'available_responses_string', 'user_response' );
	}

	/**
	 * @return array
	 */
	private function correct_response_matching() {
		$correct_response           = null;
		$available_responses        = null;
		$available_responses_string = null;
		$user_response              = null;
		$available_sources          = null;
		$available_targets          = null;
		$activity_defination        = $this->TC_Actitity->getDefinition();

		if ( ! is_null( $activity_defination->getSource() ) ) {
			$sources = $activity_defination->getSource();
			if ( ! empty( $sources ) ) {
				foreach ( $sources as $source ) {
					$label                                   = $this->get_interaction_description_string( isset( $source['description'] ) ? $source['description'] : null );
					$available_sources[ $source['id'] ]     = $label;
					$available_responses_string['source'][] = $label;
				}
			}
		}

		if ( ! is_null( $activity_defination->getTarget() ) ) {
			$targets = $activity_defination->getTarget();

			if ( ! empty( $targets ) ) {
				foreach ( $targets as $target ) {
					$label                                   = $this->get_interaction_description_string( isset( $target['description'] ) ? $target['description'] : null );
					$available_sources[ $target['id'] ]     = $label;
					$available_responses_string['target'][] = $label;
				}
			}
		}
		if ( ! empty( $available_responses_string ) ) {
			$available_responses_string = wp_json_encode( $available_responses_string );
		}
		if ( ! is_null( $activity_defination->getCorrectResponsesPattern() ) ) {
			$correct_response_string = '';
			$correct_response        = $activity_defination->getCorrectResponsesPattern();

			if ( is_array( $correct_response ) ) {
				$correct_response = $correct_response[0];
			}
			$matches = explode( '[,]', $correct_response );
			if ( ! empty( $matches ) ) {
				foreach ( $matches as $match ) {
					$pair = explode( '[.]', $match );
					if ( ! empty( $available_sources ) ) {
						$correct_response_string .= ( '' !== $correct_response_string ? ';' : '' ) . $available_sources[ $pair[0] ] . '=>' . $available_sources[ $pair[1] ];
					} else {
						$correct_response_string .= ( '' !== $correct_response_string ? ';' : '' ) . str_replace( 'urn:scormdriver:', '', $pair[0] ) . '=>' . str_replace( 'urn:scormdriver:', '', $pair[1] );
					}
				}
			}

			$correct_response = $correct_response_string;
		}

		if ( ! is_null( $this->TC_Result->getResponse() ) ) {
			$user_response        = $this->TC_Result->getResponse();
			$user_response_string = '';
			$matches              = explode( '[,]', $user_response );
			if ( ! empty( $matches ) ) {
				foreach ( $matches as $match ) {
					$pair = explode( '[.]', $match );
					if ( ! empty( $available_sources ) ) {
						$user_response_string .= ( '' !== $user_response_string ? ';' : '' ) . $available_sources[ $pair[0] ] . '=>' . $available_sources[ $pair[1] ];
					} else {
						$user_response_string .= ( '' !== $user_response_string ? ';' : '' ) . str_replace( 'urn:scormdriver:', '', $pair[0] ) . '=>' . str_replace( 'urn:scormdriver:', '', $pair[1] );
					}
				}
			}

			$user_response = $user_response_string;
		}

		return compact( 'correct_response', 'available_responses', 'available_responses_string', 'user_response' );
	}

	/**
	 * @return array
	 */
	private function correct_response_choices() {
		$correct_response           = null;
		$available_responses        = null;
		$available_responses_string = null;
		$user_response              = null;
		$available_sources          = null;
		$available_targets          = null;
		$activity_defination        = $this->TC_Actitity->getDefinition();

		if ( ! is_null( $activity_defination->getChoices() ) ) {
			$available_responses = $activity_defination->getChoices();
			if ( ! empty( $available_responses ) ) {
				foreach ( $available_responses as $respons ) {
					$available_responses_string[] = implode( ',', $respons['description'] );
				}
			}
			if ( ! empty( $available_responses_string ) ) {
				$available_responses_string = implode( ', ', $available_responses_string );
			}
		}

		if ( ! is_null( $activity_defination->getCorrectResponsesPattern() ) ) {
			$correct_responses = $activity_defination->getCorrectResponsesPattern();
			if ( ! empty( $correct_responses ) ) {
				if ( is_array( $correct_responses ) && 1 === count( $correct_responses ) ) {
					$correct_responses = explode( '[,]', $correct_responses[0] );
				}
				foreach ( $correct_responses as $crp ) {
					foreach ( $available_responses as $respons ) {
						if ( $respons['id'] === $crp ) {
							$correct_response[] = implode( ',', $respons['description'] );
						}
					}
				}
			}
			if ( ! empty( $correct_response ) ) {
				$correct_response = implode( ', ', $correct_response );
			} else {
				$correct_response = str_replace( 'urn:scormdriver:', '', implode( ', ', $correct_responses ) );
			}
		}

		if ( ! is_null( $this->TC_Result->getResponse() ) ) {
			$user_responses = $this->TC_Result->getResponse();
			$matches        = explode( '[,]', $user_responses );
			if ( ! empty( $available_responses ) && ! empty( $matches ) ) {
				foreach ( $matches as $match ) {
					foreach ( $available_responses as $respons ) {
						if ( $respons['id'] === $match ) {
							$user_response[] = implode( ',', $respons['description'] );
						}
					}
				}
			}

			if ( ! empty( $user_response ) ) {
				$user_response = implode( ', ', $user_response );
			} else {
				$user_response = str_replace( 'urn:scormdriver:', '', $user_responses );
			}
		}

		return compact( 'correct_response', 'available_responses', 'available_responses_string', 'user_response' );
	}

	/**
	 * @return array
	 */
	private function correct_response_likert() {
		$correct_response           = null;
		$available_responses        = null;
		$available_responses_string = null;
		$user_response              = null;
		$available_sources          = null;
		$available_targets          = null;
		$activity_defination        = $this->TC_Actitity->getDefinition();

		if ( ! is_null( $activity_defination->getScale() ) ) {
			$sources = $activity_defination->getScale();
			if ( ! empty( $sources ) ) {
				$scale_choice_parts = array();
				foreach ( $sources as $source ) {
					$label                              = $this->get_interaction_description_string( isset( $source['description'] ) ? $source['description'] : null );
					$available_sources[ $source['id'] ] = $label;
					if ( '' !== $label ) {
						$scale_choice_parts[] = $label;
					}
				}
				if ( ! empty( $scale_choice_parts ) ) {
					$available_responses_string = implode( ',', $scale_choice_parts );
				}
			}
		}

		if ( ! is_null( $activity_defination->getCorrectResponsesPattern() ) ) {
			$correct_response_string = '';
			$correct_response        = $activity_defination->getCorrectResponsesPattern();

			if ( is_array( $correct_response ) && ! empty( $correct_response ) ) {
				$correct_response = $correct_response[0];
			}

			if ( ! empty( $available_sources ) && is_string( $correct_response ) && isset( $available_sources[ $correct_response ] ) ) {
				$correct_response_string = $available_sources[ $correct_response ];
			}

			$correct_response = $correct_response_string;
		}

		if ( ! is_null( $this->TC_Result->getResponse() ) ) {
			$user_response        = $this->TC_Result->getResponse();
			$user_response_string = '';
			if ( ! empty( $user_response ) && ! empty( $available_sources ) && isset( $available_sources[ $user_response ] ) ) {
				$user_response_string = $available_sources[ $user_response ];
			}
			if ( ! empty( $user_response_string ) ) {
				$user_response = $user_response_string;
			} else {
				$user_response = str_replace( 'urn:scormdriver:', '', $user_response );
			}
		}

		return compact( 'correct_response', 'available_responses', 'available_responses_string', 'user_response' );
	}

	/**
	 * Validate and unset invalid parameters
	 *
	 * @access protected
	 *
	 * @param  array $result
	 *
	 * @return void
	 */
	protected function vaidate_result_parameters( $result ) {

		if ( isset( $result['score'] ) && is_array( $result['score'] ) && isset( $result['score']['min'] ) && isset( $result['score']['max'] ) && $result['score']['min'] >= $result['score']['max'] ) {
			unset( $result['score'] );
		}

		$dirty_parameters = array();
		if ( isset( $result['score'] ) ) {

			// remove invalid scaled value.
			if ( isset( $result['score']['scaled'] ) && ( $result['score']['scaled'] < -1 || $result['score']['scaled'] > 1 ) ) {
				$dirty_parameters[] = 'scaled';
			}

			// remove invalid raw value.
			if ( isset( $result['score']['min'] ) && isset( $result['score']['raw'] ) && $result['score']['raw'] < $result['score']['min'] ) {
				$dirty_parameters[] = 'raw';
			}

			if ( isset( $result['score']['max'] ) && isset( $result['score']['raw'] ) && $result['score']['raw'] > $result['score']['max'] ) {
				$dirty_parameters[] = 'raw';
			}

			// remove invalid min value.
			if ( isset( $result['score']['min'] ) && isset( $result['score']['raw'] ) && $result['score']['min'] > $result['score']['raw'] ) {
				$dirty_parameters[] = 'min';
			}

			if ( isset( $result['score']['max'] ) && isset( $result['score']['min'] ) && $result['score']['min'] >= $result['score']['max'] ) {
				$dirty_parameters[] = 'min';
			}

			// remove invalid max value.
			if ( isset( $result['score']['max'] ) && isset( $result['score']['raw'] ) && $result['score']['max'] < $result['score']['raw'] ) {
				$dirty_parameters[] = 'max';
			}

			if ( isset( $result['score']['max'] ) && isset( $result['score']['min'] ) && $result['score']['max'] <= $result['score']['min'] ) {
				$dirty_parameters[] = 'max';
			}

			if ( ! empty( $dirty_parameters ) ) {
				$dirty_parameters = array_unique( $dirty_parameters );
				foreach ( $dirty_parameters as $dirty_parameter ) {
					if ( isset( $result['score'][ $dirty_parameter ] ) ) {
						unset( $result['score'][ $dirty_parameter ] );
					}
				}
			}
		}

		return $result;
	}

	//phpcs:enable WordPress.NamingConventions
	//phpcs:enable WordPress.PHP.DontExtract
}
