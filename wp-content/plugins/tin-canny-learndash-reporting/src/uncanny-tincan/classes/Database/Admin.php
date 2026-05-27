<?php
/**
 * Database\Admin
 *
 * @since      1.3.0
 * @author     Uncanny Owl
 * @package    Tin Canny Reporting for LearnDash
 * @subpackage TinCan Module
 */

namespace UCTINCAN\Database;

use uncanny_learndash_reporting\Cache;
use uncanny_learndash_reporting\TinCannyShortcode;

if ( ! defined( 'UO_ABS_PATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

/**
 *
 */
class Admin extends \UCTINCAN\Database {
	// Storing Filters
	/**
	 * @var array
	 */
	private $filters = array();

	/**
	 * Delete User's Data
	 *
	 * @access public
	 *
	 * @param int $user_id
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public static function delete_by_user( $user_id ) {
		global $wpdb;

		$query = sprintf(
			'
			DELETE FROM %s%s
				WHERE `user_id` = %s;
			',
			$wpdb->prefix,
			self::TABLE_REPORTING,
			$user_id
		);

		$wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$query = sprintf(
			'
			DELETE FROM %s%s
				WHERE `user_id` = %s;
			',
			$wpdb->prefix,
			self::TABLE_QUIZ,
			$user_id
		);

		$wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$query = sprintf(
			'
			DELETE FROM %s%s
				WHERE `user_id` = %s;
			',
			$wpdb->prefix,
			self::TABLE_RESUME,
			$user_id
		);

		$wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	}

	/**
	 * Setter
	 *
	 * @access public
	 *
	 * @param string $name
	 * @param mixed $value
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public function __set( $name, $value ) {
		$this->filters[ $name ] = $value;
	}

	/**
	 * @return string
	 */
	public function get_tincan_data_query() {
		global $wpdb;
		$query = "SELECT
    reporting.id,
    reporting.group_id,
    reporting.lesson_id,
    reporting.course_id,
    reporting.user_id,
    reporting.module,
    reporting.module_name,
    reporting.target,
    reporting.target_name,
    reporting.verb,
    reporting.xstored,
    reporting.result,
    reporting.minimum,
    reporting.completion,
    g.post_title as group_name,
    c.post_title as course_name,
    l.post_title as lesson_name,
    u.display_name as user_name
FROM
    {$wpdb->prefix}uotincan_reporting reporting
LEFT JOIN
    {$wpdb->posts} g ON g.ID = reporting.group_id
LEFT JOIN
    {$wpdb->users} u ON u.ID = reporting.user_id
LEFT JOIN
    {$wpdb->posts} c ON c.ID = reporting.course_id
LEFT JOIN
    {$wpdb->posts} l ON l.ID = reporting.lesson_id";

		$where = $this->get_tincan_where_query();
		$query = "{$query}
WHERE {$where}";

		return $query;
	}

	/**
	 * @return array|false|object|\stdClass[]|null
	 */
	public function get_tincan_data( $start, $length, $count = false ) {
		if ( ! $this->set_user_id() ) {
			return false;
		}

		if ( ! current_user_can( apply_filters( 'uo_tincanny_reporting_capability', 'tincanny_reporting' ) ) ) {
			return false;
		}

		global $wpdb;

		$query = $this->get_tincan_data_query();

		$sort_direction = strtoupper( $this->filters['order'] ?? 'DESC' ); // Default to 'DESC'

		// Validate the sorting direction
		$valid_sort_directions = array( 'ASC', 'DESC' );
		if ( ! in_array( $sort_direction, $valid_sort_directions, true ) ) {
			$sort_direction = 'DESC';
		}

		// Get the column to order by from the filters
		$order_by_column = $this->sorting_columns_mapping();

		$orderby = " ORDER BY $order_by_column $sort_direction ";

		if ( false === $count ) {

			if ( intval( '-1' ) !== intval( $length ) ) {

				$orderby .= " LIMIT $start, $length";
			}
		}

		$query = $query . $orderby;

		// Lets cache the query
		$cache_key = md5( $query );

		$cached_query = wp_cache_get( $cache_key, 'tin-canny-reporting' );

		if ( false !== $cached_query && intval( '-1' ) !== intval( $length ) && true === apply_filters( 'uo_tincanny_reporting_cached_query', true, $query, $cached_query ) ) {
			return true === $count ? count( $cached_query ) : $cached_query;
		}

		$results = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		wp_cache_set( $cache_key, $results, 'tin-canny-reporting', 300 );

		return true === $count ? count( $results ) : $this->build_tincan_report_data( $results );
	}

	/**
	 * @param $data
	 *
	 * @return array
	 */
	public function build_tincan_report_data( $data ) {
		$is_csv  = $this->filters['is_csv'] ?? false; // Check if CSV export is enabled
		$results = array();
		foreach ( $data as $row ) {
			$result   = $row->result;
			$site_url = site_url();

			if ( ! is_null( $row->result ) && $row->minimum ) {
				$result = $row->result . ' / ' . $row->minimum;
			}

			$completion = '';

			if ( ! is_null( $row->result ) ) {
				$completion = ( $row->result > 0 ) ? '<span class="dashicons dashicons-yes"></span>' : '<span class="dashicons dashicons-no"></span>';
			}

			$_row = array(
				'group_name'  => ! empty( $row->group_name ) ? sprintf( '<a href="%s" target="_blank">%s</a>', TinCannyShortcode::make_absolute( $row->group_id, $site_url ), $row->group_name ) : 'n/a',
				'user_name'   => sprintf( '<a href="%s" target="_blank">%s</a>', admin_url( "user-edit.php?user_id={$row->user_id}" ), $row->user_name ),
				'course_name' => ! empty( $row->course_name ) ? $row->course_name : 'n/a',
				'module_name' => sprintf( '<a href="%s" target="_blank">%s</a>', TinCannyShortcode::make_absolute( $row->module, $site_url ), $row->module_name ),
				'target_name' => sprintf( '<a href="%s" target="_blank">%s</a>', TinCannyShortcode::make_absolute( $row->target, $site_url ), $row->target_name ),
				'verb'        => ucfirst( $row->verb ),
				'result'      => '<span class="tclr-reporting-datatable__no-wrap">' . $result . '</span>',
				'completion'  => $completion,
				'xstored'     => $row->xstored,
			);

			$_row = apply_filters( 'tincanny_row_data', $_row );

			// Clean up HTML if exporting to CSV
			if ( $is_csv ) {
				$_row = array_map( function ( $value ) {
					if ( is_string( $value ) ) {
						// Strip HTML tags and decode special characters
						return html_entity_decode( wp_strip_all_tags( $value ) );
					}

					return $value;
				}, $_row );
			}

			$results[] = $_row;
		}

		return $results;
	}

	/**
	 * @return string
	 */
	public function get_tincan_where_query() {
		$where = array( '1 = 1' );

		if ( ! empty( $this->filters['group'] ) ) {
			$where[] = "reporting.`group_id` = {$this->filters[ 'group' ]}";
		}

		if ( ! empty( $this->filters['actor'] ) ) {
			$where[] = "( u.`user_nicename` LIKE '%{$this->filters[ 'actor' ]}%' OR u.`user_email` LIKE '%{$this->filters[ 'actor' ]}%' OR u.`display_name` LIKE '%{$this->filters[ 'actor' ]}%' )";
		}

		if ( ! empty( $this->filters['user_id'] ) ) {
			$where[] = "reporting.`user_id` = {$this->filters[ 'user_id' ]}";
		}

		if ( ! empty( $this->filters['course'] ) ) {
			$where[] = "reporting.`course_id` = {$this->filters[ 'course' ]}";
		}

		if ( ! empty( $this->filters['lesson'] ) ) {
			$where[] = "reporting.`lesson_id` = {$this->filters[ 'lesson' ]}";
		}

		if ( ! empty( $this->filters['module'] ) ) {
			$where[] = "reporting.`module` = '{$this->filters[ 'module' ]}'";
		}

		if ( ! empty( $this->filters['verb'] ) ) {
			$where[] = "reporting.`verb` LIKE '%{$this->filters[ 'verb' ]}%'";
		}

		if ( ! empty( $this->filters['dateStart'] ) ) {
			$where[] = "reporting.`xstored` >= '{$this->filters[ 'dateStart' ]}'";
		}

		if ( ! empty( $this->filters['dateEnd'] ) ) {
			$where[] = "reporting.`xstored` <= '{$this->filters[ 'dateEnd' ]}'";
		}

		$where = ( ! empty( $where ) ) ? implode( "\nAND ", $where ) : '';
		$where .= ' AND (' . $this->get_group_leader_groups_query_string() . ')';

		return $where;
	}

	/**
	 * Return TinCan Data
	 *
	 * @access public
	 *
	 * @param int $per_page optional default 0
	 * @param string $mode optional default ''
	 *
	 * @return bool|array
	 * @since  1.0.0
	 */
	public function get_data( $per_page = 0, $mode = '' ) {
		if ( ! $this->set_user_id() ) {
			return false;
		}

		if ( ! current_user_can( apply_filters( 'uo_tincanny_reporting_capability', 'tincanny_reporting' ) ) ) {
			return false;
		}

		global $wpdb;

		if ( 'csv' === $mode ) {
			$query = $this->get_query_string(
				'
				xgrouping.post_title as "Group Name",
				user.ID as "User ID",
				user.display_name as "User Name",
				course.post_title as "Course Name",
				reporting.module as "Module URL",
				reporting.module_name as "Module Name",
				reporting.target_name as "Target Name",
				reporting.verb as "Action",
				case
					when ( reporting.minimum = 100 ) then CONCAT( reporting.result, " / 100")
					else reporting.result
				end as "Result",
				case
					when ( reporting.completion = 1 ) then "Yes"
					when ( reporting.completion = 0 ) then "No"
					else ""
				end as "Completion",
				reporting.xstored as "Timestamp"'
			);
		} else {
			$query = $this->get_query_string( 'reporting.*, xgrouping.post_title as group_name, course.post_title as course_name, lesson.post_title as lesson_name, user.display_name as user_name' );
		}

		$orderby = '';

		if ( ! empty( $this->filters['orderby'] ) ) {
			$orderby = " ORDER BY reporting.`{$this->filters[ 'orderby' ]}` ";

			if ( ! empty( $this->filters['order'] ) ) {
				$orderby .= " {$this->filters[ 'order' ]} ";
			}
		}

		if ( 0 !== $per_page ) {
			if ( ! $this->filters['paged'] ) {
				$this->filters['paged'] = 1;
			}

			$limit   = ( $this->filters['paged'] - 1 ) * $per_page;
			$orderby .= "LIMIT {$limit}, {$per_page}";
		}

		$query = $wpdb->get_results( $query . $orderby, 'ARRAY_A' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return $query;
	}

	/**
	 * Return XAPI QUIZ Data
	 *
	 * @access public
	 *
	 * @param int $per_page optional default 0
	 * @param string $mode optional default ''
	 *
	 * @return bool|array
	 * @since  3.2.0
	 */
	public function get_xapi_data( $start, $length, $count = false ) {
		if ( ! $this->set_user_id() ) {
			return false;
		}

		if ( ! current_user_can( apply_filters( 'uo_tincanny_reporting_capability', 'tincanny_reporting' ) ) ) {
			return false;
		}

		global $wpdb;

		$query = $this->get_xapi_query_string();

		$sort_direction = strtoupper( $this->filters['order'] ?? 'DESC' ); // Default to 'DESC'

		// Validate the sorting direction
		$valid_sort_directions = array( 'ASC', 'DESC' );
		if ( ! in_array( $sort_direction, $valid_sort_directions, true ) ) {
			$sort_direction = 'DESC';
		}

		// Get the column to order by from the filters
		$order_by_column = $this->sorting_columns_mapping();

		$orderby = " ORDER BY $order_by_column $sort_direction ";

		if ( false === $count ) {

			if ( intval( '-1' ) !== intval( $length ) ) {

				$orderby .= " LIMIT $start, $length";
			}
		}

		$query = $query . $orderby;

		// Lets cache the query
		$cache_key = md5( $query );

		$cached_query = wp_cache_get( $cache_key, 'xapi-quiz-reporting' );

		if ( false !== $cached_query && '-1' !== intval( $length ) && true === apply_filters( 'uo_tincanny_xapi_report_cached_query', true, $query, $cached_query ) ) {
			return true === $count ? count( $cached_query ) : $cached_query;
		}

		$results = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		wp_cache_set( $cache_key, $results, 'tin-canny-reporting', 300 );

		return true === $count ? count( $results ) : $this->build_xapi_quiz_report_data( $results );
	}

	/**
	 * @param $column
	 *
	 * @return mixed|string
	 */
	public function sorting_columns_mapping() {
		$column = isset( $this->filters['orderby'] ) ? $this->filters['orderby'] : '';

		$column_mapping = array(
			'group_name'       => 'reporting.group_id',
			'user_name'        => 'user.display_name', // or another user field
			'course_name'      => 'reporting.course_id',
			'module_name'      => 'reporting.module',
			'question'         => 'reporting.activity_name',
			'result'           => 'reporting.result',
			'score'            => 'reporting.result',
			'xstored'          => 'reporting.xstored',
			'choices'          => 'reporting.choices', // Assuming it exists in the DB
			'correct_response' => 'reporting.correct_response',
			'user_response'    => 'reporting.user_response',
			'verb'             => 'reporting.verb',
		);

		return array_key_exists( $column, $column_mapping ) ? $column_mapping[ $column ] : 'reporting.xstored';
	}

	/**
	 * @param $data
	 *
	 * @return array
	 */
	public function build_xapi_quiz_report_data( $data ) {
		$results = array();
		$is_csv  = $this->filters['is_csv'] ?? false; // Check if CSV export is enabled

		$tincan_post_types = array(
			'sfwd-courses',
			'sfwd-lessons',
			'sfwd-topic',
			'sfwd-quiz',
			'sfwd-certificates',
			'sfwd-assignment',
			'groups',
		);

		$consider_scaled_score = (bool) apply_filters( 'uo_tincanny_data_consider_scaled_score', false );

		foreach ( $data as $row ) {
			$site_url = site_url();

			$lesson = get_post( $row->lesson_id );

			if ( ! empty( $lesson ) && in_array( $lesson->post_type, $tincan_post_types, true ) ) {
				$group_link = admin_url( "post.php?post={$row->group_id}&action=edit" );
				$group_name = $row->group_name;
				$group      = sprintf( '<a href="%s">%s</a>', $group_link, $group_name );

				$course_link = admin_url( "post.php?post={$row->course_id}&action=edit" );
				$course_name = $row->course_name;
				$course      = sprintf( '<a href="%s">%s</a>', $course_link, $course_name );
			} else {
				$group  = __( 'n/a', 'uncanny-learndash-reporting' );
				$course = __( 'n/a', 'uncanny-learndash-reporting' );
			}

			$result = $row->result;
			if ( true === $consider_scaled_score && ! is_null( $row->scaled_score ) ) {
				$result = $row->scaled_score * 100;
			}

			if ( ! is_null( $result ) ) {
				$completion = ( $result > 0 ) ? 'Correct' : 'Incorrect';
			} else {
				$completion = 'Incorrect';
			}

			if ( isset( $row->minimum ) ) {
				if ( ! is_null( $result ) && $row->minimum ) {
					$result = $result . ' / ' . $row->minimum;
				}
			}

			$_row = array(
				'group_name'       => $group,
				'user_name'        => sprintf( '<a href="%s" target="_blank">%s</a>', admin_url( "user-edit.php?user_id={$row->user_id}" ), $row->user_name ),
				'course_name'      => $course,
				'module_name'      => sprintf( '<a href="%s" target="_blank">%s</a>', TinCannyShortcode::make_absolute( $row->module, $site_url ), $row->module_name ),
				'question'         => ucfirst( $row->activity_name ),
				'result'           => $completion,
				'score'            => (int) $result,
				'xstored'          => $row->xstored,
				'choices'          => $row->available_responses,
				'correct_response' => $row->correct_response,
				'user_response'    => $row->user_response,
			);

			$_row = apply_filters( 'tincanny_row_data', $_row );

			// Clean up HTML if exporting to CSV
			if ( $is_csv ) {
				$_row = array_map( function ( $value ) {
					if ( is_string( $value ) ) {
						// Strip HTML tags and decode special characters
						return html_entity_decode( wp_strip_all_tags( $value ) );
					}

					return $value;
				}, $_row );
			}

			$results[] = $_row;
		}

		return $results;
	}

	/**
	 * Return TinCan Data Row Count
	 *
	 * @access public
	 * @return int
	 * @since  1.0.0
	 */
	public function get_count() {
		if ( ! $this->set_user_id() ) {
			return false;
		}

		if ( ! current_user_can( apply_filters( 'uo_tincanny_reporting_capability', 'tincanny_reporting' ) ) ) {
			return false;
		}

		global $wpdb;

		$query = $wpdb->get_results( $this->get_query_string( 'COUNT(reporting.id), reporting.verb' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return $wpdb->num_rows;
	}

	/**
	 * Return TinCan Data Row Count
	 *
	 * @access public
	 * @return int
	 * @since  1.0.0
	 * @deprecated 5.0
	 */
	public function get_count_xapi() {
		if ( ! $this->set_user_id() ) {
			return false;
		}

		if ( ! current_user_can( apply_filters( 'uo_tincanny_reporting_capability', 'tincanny_reporting' ) ) ) {
			return false;
		}

		global $wpdb;

		$num_query = $wpdb->get_var( $this->get_xapi_query_string( 'COUNT(reporting.id)' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return $num_query;
	}

	/**
	 * Return List of Group
	 *
	 * @access public
	 * @return array
	 * @since  1.0.0
	 */
	public function get_groups( $table_type = '' ) {

		if ( ! $this->set_user_id() ) {
			return array();
		}

		if ( ! current_user_can( apply_filters( 'uo_tincanny_reporting_capability', 'tincanny_reporting' ) ) ) {
			return array();
		}

		$where         = $this->get_group_leader_groups_query_string();
		$encoded_where = md5( json_encode( $where ) );
		$key           = __FUNCTION__ . 'TINCAN_GROUPS_' . $encoded_where;
		$cache         = Cache::get( $key );
		if ( ! empty( $cache ) ) {
			return $cache;
		}

		$table_name = self::TABLE_REPORTING;
		if ( 'quiz' === $table_type ) {
			$table_name = self::TABLE_QUIZ;
		}
		global $wpdb;

		$query = sprintf(
			"SELECT reporting.group_id, xgrouping.post_title as group_name
				FROM {$wpdb->prefix}{$table_name} reporting
					INNER JOIN %s xgrouping ON xgrouping.ID = reporting.group_id
				WHERE %s
				GROUP BY reporting.group_id",
			$wpdb->posts,
			$where
		);

		$results = $wpdb->get_results( $query, 'ARRAY_A' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		Cache::create( $key, $results );

		return $results;
	}

	/**
	 * Return List of Course
	 *
	 * @access public
	 * @return array
	 * @since  1.0.0
	 */
	public function get_courses( $table_type = '' ) {
		if ( ! $this->set_user_id() ) {
			return array();
		}

		if ( ! current_user_can( apply_filters( 'uo_tincanny_reporting_capability', 'tincanny_reporting' ) ) ) {
			return array();
		}

		$where = $this->get_group_leader_groups_query_string();

		$encoded_where = md5( json_encode( $where ) );
		$key           = __FUNCTION__ . 'TINCAN_COURSES' . $encoded_where;

		$cache = Cache::get( $key );

		if ( ! empty( $cache ) ) {
			return $cache;
		}

		$table_name = self::TABLE_REPORTING;
		if ( 'quiz' === $table_type ) {
			$table_name = self::TABLE_QUIZ;
		}

		global $wpdb;

		$query = sprintf(
			"
			SELECT reporting.course_id, course.post_title as course_name
				FROM {$wpdb->prefix}{$table_name} reporting
					INNER JOIN %s course ON course.ID = reporting.course_id
				WHERE %s
				GROUP BY reporting.course_id",
			$wpdb->posts,
			$where
		);

		$results = $wpdb->get_results( $query, 'ARRAY_A' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		Cache::create( $key, $results );

		return $results;
	}

	/**
	 * Return List of Lesson.
	 *
	 * @access public
	 * @return array
	 * @since  1.0.0
	 */
	public function get_lessons( $table_type = '' ) {
		if ( ! $this->set_user_id() ) {
			return array();
		}

		if ( ! current_user_can( apply_filters( 'uo_tincanny_reporting_capability', 'tincanny_reporting' ) ) ) {
			return array();
		}

		$where = $this->get_group_leader_groups_query_string();

		$encoded_where = md5( json_encode( $where ) );
		$key           = __FUNCTION__ . 'TINCAN_LESSONS' . $encoded_where;

		$cache = Cache::get( $key );

		if ( ! empty( $cache ) ) {
			return $cache;
		}

		$table_name = self::TABLE_REPORTING;
		if ( 'quiz' === $table_type ) {
			$table_name = self::TABLE_QUIZ;
		}

		global $wpdb;

		$query = sprintf(
			"SELECT reporting.lesson_id, lesson.post_title as lesson_name
				FROM {$wpdb->prefix}{$table_name} reporting
					INNER JOIN %s lesson ON lesson.ID = reporting.lesson_id
				WHERE %s
				GROUP BY reporting.lesson_id",
			$wpdb->posts,
			$where
		);

		$results = $wpdb->get_results( $query, 'ARRAY_A' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		Cache::create( $key, $results );

		return $results;
	}

	/**
	 * Return List of Module
	 *
	 * @access public
	 * @return array
	 * @since  1.0.0
	 */
	public function get_modules( $table_type = '' ) {
		if ( ! $this->set_user_id() ) {
			return array();
		}

		if ( ! current_user_can( apply_filters( 'uo_tincanny_reporting_capability', 'tincanny_reporting' ) ) ) {
			return array();
		}

		$where = $this->get_group_leader_groups_query_string();

		$encoded_where = md5( json_encode( $where ) );
		$key           = __FUNCTION__ . 'TINCAN_MODULES' . $encoded_where;

		$cache = Cache::get( $key );

		if ( ! empty( $cache ) ) {
			return $cache;
		}

		$table_name = self::TABLE_REPORTING;
		if ( 'quiz' === $table_type ) {
			$table_name = self::TABLE_QUIZ;
		}

		global $wpdb;

		$query = sprintf(
			"SELECT reporting.module, reporting.module_name
				FROM {$wpdb->prefix}{$table_name} reporting
				WHERE %s
				GROUP BY reporting.module",
			$where
		);

		$results = $wpdb->get_results( $query, 'ARRAY_A' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		Cache::create( $key, $results );

		return $results;
	}

	/**
	 * Return List of Verb
	 *
	 * @access public
	 * @return array
	 * @since  1.0.0
	 */
	public function get_actions() {
		if ( ! $this->set_user_id() ) {
			return array();
		}

		if ( ! current_user_can( apply_filters( 'uo_tincanny_reporting_capability', 'tincanny_reporting' ) ) ) {
			return array();
		}

		global $wpdb;

		$where = $this->get_group_leader_groups_query_string();

		$encoded_where = md5( json_encode( $where ) );
		$key           = __FUNCTION__ . 'TINCAN_ACTIONS' . $encoded_where;

		$cache = Cache::get( $key );

		if ( ! empty( $cache ) ) {
			return $cache;
		}

		$query = sprintf(
			"SELECT reporting.verb FROM {$wpdb->prefix}" . self::TABLE_REPORTING . " reporting
				WHERE %s
				GROUP BY reporting.verb",
			$where
		);

		$results = $wpdb->get_results( $query, 'ARRAY_A' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		Cache::create( $key, $results );

		return $results;
	}

	/**
	 * Return List of Verb
	 *
	 * @access public
	 * @return array
	 * @since  1.0.0
	 */
	public function get_questions( $q = '' ) {
		if ( ! $this->set_user_id() ) {
			return false;
		}

		if ( ! current_user_can( apply_filters( 'uo_tincanny_reporting_capability', 'tincanny_reporting' ) ) ) {
			return false;
		}

		global $wpdb;

		$where = $this->get_group_leader_groups_query_string();

		if ( ! empty( $q ) ) {
			$where = ( ! empty( $where ) ) ? '(' . $where . ") AND ( reporting.activity_name LIKE '%" . $q . "%' ) " : $where;
		}

		$query = sprintf(
			'
			SELECT reporting.activity_name, reporting.activity_name_hash FROM %s%s reporting
				WHERE %s
				GROUP BY reporting.activity_name',
			$wpdb->prefix,
			self::TABLE_QUIZ,
			$where
		);

		return $wpdb->get_results( $query, 'ARRAY_A' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}


	/**
	 * @param $hash
	 *
	 * @return string|null
	 */
	public function get_question_by_hash( $hash ) {

		if ( empty( $hash ) ) {
			return '';
		}

		global $wpdb;

		$query = $wpdb->prepare( "SELECT reporting.activity_name
FROM $wpdb->prefix" . self::TABLE_QUIZ . " AS reporting
WHERE reporting.activity_name_hash = %s",
			$hash
		);

		return $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Return List of Date
	 *
	 * @access public
	 * @return array
	 * @since  1.0.0
	 */
	public function get_dates() {
		if ( ! $this->set_user_id() ) {
			return false;
		}

		if ( ! current_user_can( apply_filters( 'uo_tincanny_reporting_capability', 'tincanny_reporting' ) ) ) {
			return false;
		}

		global $wpdb;

		$where = $this->get_group_leader_groups_query_string();
		$query = sprintf(
			'
			SELECT DATE(reporting.xstored) as date
				FROM %s%s reporting
				WHERE %s
				GROUP BY DATE(reporting.xstored)',
			$wpdb->prefix,
			self::TABLE_REPORTING,
			$where
		);

		return $wpdb->get_results( $query, 'ARRAY_A' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Print Module <option>s from Filter
	 *
	 * @access private
	 *
	 * @param int $course_id
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public function print_modules_form_from_url_parameter( $type = '' ) {

		if ( ! current_user_can( apply_filters( 'uo_tincanny_reporting_capability', 'manage_options' ) ) && ! current_user_can( apply_filters( 'uo_tincanny_reporting_capability', 'tincanny_reporting' ) ) ) {
			return false;
		}

		$get_selected_course = ultc_get_filter_var( 'tc_filter_course', false );
		$selected_course     = empty( $get_selected_course ) ? ultc_get_filter_var( 'tc_filter_course', false, INPUT_POST ) : $get_selected_course;
		$get_selected_module = ultc_get_filter_var( 'tc_filter_module', false );
		$selected_module     = empty( $get_selected_module ) ? ultc_get_filter_var( 'tc_filter_module', false, INPUT_POST ) : $get_selected_module;
		$get_type            = ultc_get_filter_var( 'type', $type );
		$type                = empty( $get_type ) ? ultc_get_filter_var( 'type', $type, INPUT_POST ) : $get_type;

		$modules = $this->get_modules_by_course( $selected_course, $type );
		if ( ! empty( $modules ) ) {
			foreach ( $modules as $module ) {
				printf( '<option value="%s" %s>%s</option>', esc_attr( $module->module ), ( $selected_module === $module->module ) ? 'selected="selected"' : '', esc_html( $module->module_name ) );
			}
		}
	}

	/**
	 * Reset Data
	 *
	 * @access public
	 * @return bool
	 * @since  1.0.0
	 */
	public function reset() {
		if ( ! $this->set_user_id() ) {
			return false;
		}

		global $wpdb;
		$query = sprintf(
			'TRUNCATE TABLE %s%s;',
			$wpdb->prefix,
			self::TABLE_REPORTING
		);
		$wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( $wpdb->last_error ) {
			return false;
		}

		return true;
	}

	/**
	 * Reset Data
	 *
	 * @access public
	 * @return bool
	 * @since  1.0.0
	 */
	public function reset_quiz() {
		if ( ! $this->set_user_id() ) {
			return false;
		}

		global $wpdb;
		$query = sprintf(
			'TRUNCATE TABLE %s%s;',
			$wpdb->prefix,
			self::TABLE_QUIZ
		);
		$wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( $wpdb->last_error ) {
			return false;
		}

		return true;
	}

	/**
	 * Reset Data
	 *
	 * @access public
	 *
	 * @param $user_id
	 * @peram $type
	 *
	 * @return
	 * @since  1.0.0
	 */
	public function reset_user( $user_id = 0, $type = array() ) {

		if ( ! $this->set_user_id() || 0 === $user_id ) {
			return;
		}

		if ( in_array( 'reporting', $type, true ) ) {
			global $wpdb;
			$query = sprintf(
				'DELETE FROM %s%s WHERE user_id = %d',
				$wpdb->prefix,
				self::TABLE_REPORTING,
				$user_id
			);

			$wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			// reseting quiz data too...
			$query = sprintf(
				'DELETE FROM %s%s WHERE user_id = %d',
				$wpdb->prefix,
				self::TABLE_QUIZ,
				$user_id
			);

			$wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		if ( in_array( 'resume', $type, true ) ) {
			global $wpdb;
			$query = sprintf(
				'DELETE FROM %s%s WHERE user_id = %d',
				$wpdb->prefix,
				self::TABLE_RESUME,
				$user_id
			);

			$wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

	}

	/**
	 * Reset Resume Data by Course ID
	 *
	 * @access public
	 *
	 * @param $user_id
	 * @param $course_id
	 *
	 * @return
	 */
	public function reset_user_course( $user_id, $course_id  ) {

		if ( 0 === absint($user_id) || 0 === absint($course_id) ) {
			return;
		}

		global $wpdb;
		$query = sprintf(
			'DELETE FROM %s%s WHERE user_id = %d AND course_id = %d',
			$wpdb->prefix,
			self::TABLE_RESUME,
			$user_id,
			$course_id
		);

		return $wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
	/**
	 * Reset Data
	 *
	 * @access public
	 * @return bool
	 * @since  1.0.0
	 */
	public function reset_bookmark_data() {
		if ( ! $this->set_user_id() ) {
			return false;
		}

		global $wpdb;
		$query = sprintf(
			'TRUNCATE TABLE %s%s;',
			$wpdb->prefix,
			self::TABLE_RESUME
		);
		$wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( $wpdb->last_error ) {
			return false;
		}

		return true;
	}

	/**
	 * Create Query String based on Search Fields
	 *
	 * @access private
	 * @return string
	 * @since  1.0.0
	 */
	private function get_query_string( $columns = 'id' ) {
		global $wpdb;

		$where = array();

		if ( ! empty( $this->filters['group'] ) ) {
			$where[] = "reporting.`group_id` = {$this->filters[ 'group' ]}";
		}

		if ( ! empty( $this->filters['actor'] ) ) {
			$where[] = "( user.`user_nicename` LIKE '%{$this->filters[ 'actor' ]}%' OR user.`user_email` LIKE '%{$this->filters[ 'actor' ]}%' OR user.`display_name` LIKE '%{$this->filters[ 'actor' ]}%' )";
		}

		if ( ! empty( $this->filters['user_id'] ) ) {
			$where[] = "reporting.`user_id` = {$this->filters[ 'user_id' ]}";
		}

		if ( ! empty( $this->filters['course'] ) ) {
			$where[] = "reporting.`course_id` = {$this->filters[ 'course' ]}";
		}

		if ( ! empty( $this->filters['lesson'] ) ) {
			$where[] = "reporting.`lesson_id` = {$this->filters[ 'lesson' ]}";
		}

		if ( ! empty( $this->filters['module'] ) ) {
			$where[] = "reporting.`module` = '{$this->filters[ 'module' ]}'";
		}

		if ( ! empty( $this->filters['verb'] ) ) {
			$where[] = "reporting.`verb` LIKE '%{$this->filters[ 'verb' ]}%'";
		}

		if ( ! empty( $this->filters['dateStart'] ) ) {
			$where[] = "reporting.`xstored` >= '{$this->filters[ 'dateStart' ]}'";
		}

		if ( ! empty( $this->filters['dateEnd'] ) ) {
			$where[] = "reporting.`xstored` <= '{$this->filters[ 'dateEnd' ]}'";
		}

		$where = ( ! empty( $where ) ) ? implode( ' AND ', $where ) : '1 = 1';
		$where .= ' AND (' . $this->get_group_leader_groups_query_string() . ')';

		$query = sprintf(
			'SELECT %s
			FROM %s%s reporting
			LEFT OUTER JOIN %s xgrouping ON xgrouping.ID = reporting.group_id
			LEFT OUTER JOIN %s user     ON user.ID     = reporting.user_id
			LEFT OUTER JOIN %s course   ON course.ID   = reporting.course_id
			LEFT OUTER JOIN %s lesson   ON lesson.ID   = reporting.lesson_id

			WHERE %s',
			$columns,
			$wpdb->prefix,
			self::TABLE_REPORTING,
			$wpdb->posts,
			$wpdb->users,
			$wpdb->posts,
			$wpdb->posts,
			$where
		);

		//$query .= " GROUP BY (case when reporting.verb = 'passed' then reporting.user_id, reporting.course_id, reporting.module, reporting.verb, DATE_FORMAT(reporting.xstored,'%Y-%m-%d %H:%i')  end) ";
		$query .= " GROUP BY
		(
    	    case when reporting.verb = 'passed' then reporting.verb else reporting.verb end
		),(
		    case when reporting.verb = 'passed' then ROUND((CEILING(UNIX_TIMESTAMP(reporting.xstored) / 10) * 10)) else reporting.xstored end
		), (
		    case when reporting.verb = 'passed' then reporting.user_id else reporting.user_id end
		), (
		    case when reporting.verb = 'passed' then reporting.module else reporting.module end
		), (
		    case when reporting.verb = 'passed' then reporting.course_id else reporting.course_id end
		) ";

		$query = apply_filters( 'tc_tincan_report_query', $query, $this->filters, self::TABLE_REPORTING, $columns, $where );

		return $query;
	}

	/**
	 * Check the Current User is a Group Leader and Return Assigned Groups Query String
	 *
	 * @access private
	 * @return bool
	 * @since  1.0.0
	 */
	private function get_group_leader_groups_query_string() {
		if ( ! learndash_is_group_leader_user( get_current_user_id() ) ) {
			return ' 1=1 ';
		}

		$groups = learndash_get_administrators_group_ids( get_current_user_id() );
		$where  = array();

		if ( empty( $groups ) ) {
			return ' 1=1 ';
		}

		foreach ( $groups as $group ) {
			$where[] = "reporting.group_id = {$group}";
		}

		return implode( ' OR ', $where );
	}

	/**
	 * Create XAPI Query String based on Search Fields
	 *
	 * @access private
	 * @return string
	 * @since  3.2.0
	 */
	private function get_xapi_query_string( $columns = 'id' ) {
		global $wpdb;

		$where = array();

		// Group
		if ( ! empty( $this->filters['group'] ) ) {
			$where[] = "reporting.`group_id` = " . intval( $this->filters['group'] );
		}

		// Actor
		if ( ! empty( $this->filters['actor'] ) ) {
			$actor   = esc_sql( $this->filters['actor'] );
			$where[] = "( user.`user_nicename` LIKE '%$actor%' OR user.`user_email` LIKE '%$actor%' OR user.`display_name` LIKE '%$actor%' )";
		}

		// User ID
		if ( ! empty( $this->filters['user_id'] ) ) {
			$where[] = "reporting.`user_id` = " . intval( $this->filters['user_id'] );
		}

		// Course
		if ( ! empty( $this->filters['course'] ) ) {
			$where[] = "reporting.`course_id` = " . intval( $this->filters['course'] );
		}

		// Lesson
		if ( ! empty( $this->filters['lesson'] ) ) {
			$where[] = "reporting.`lesson_id` = " . intval( $this->filters['lesson'] );
		}

		// Module
		if ( ! empty( $this->filters['module'] ) ) {
			$module  = esc_sql( $this->filters['module'] );
			$where[] = "reporting.`module` = '$module'";
		}

		// Question
		if ( ! empty( $this->filters['question'] ) ) {
			$question = esc_sql( $this->filters['question'] );
			$where[]  = "reporting.activity_name_hash = '$question'";
		}

		// Results
		if ( ! empty( $this->filters['results'] ) ) {
			if ( '1' === (string) $this->filters['results'] ) {
				$where[] = "reporting.`result` > 0";
			} elseif ( '-1' === (string) $this->filters['results'] ) {
				$where[] = "( reporting.`result` = 0 OR reporting.`result` IS NULL )";
			}
		}

		// Date Start
		if ( ! empty( $this->filters['dateStart'] ) ) {
			$dateStart = esc_sql( $this->filters['dateStart'] );
			$where[]   = "reporting.`xstored` >= '$dateStart'";
		}

		// Date End
		if ( ! empty( $this->filters['dateEnd'] ) ) {
			$dateEnd = esc_sql( $this->filters['dateEnd'] );
			$where[] = "reporting.`xstored` <= '$dateEnd'";
		}

		// Final WHERE Clause
		$where = ( ! empty( $where ) ) ? implode( ' AND ', $where ) : '1 = 1';

		$where .= ' AND (' . $this->get_group_leader_groups_query_string() . ')';

		$query = "SELECT
	reporting.*,
	xgrouping.post_title as group_name,
	course.post_title as course_name,
	lesson.post_title as lesson_name,
	user.display_name as user_name
			FROM {$wpdb->prefix}" . self::TABLE_QUIZ . " AS reporting
			LEFT OUTER JOIN $wpdb->posts xgrouping ON xgrouping.ID = reporting.group_id
			LEFT OUTER JOIN $wpdb->users user     ON user.ID     = reporting.user_id
			LEFT OUTER JOIN $wpdb->posts course   ON course.ID   = reporting.course_id
			LEFT OUTER JOIN $wpdb->posts lesson   ON lesson.ID   = reporting.lesson_id
			WHERE $where";

		return apply_filters( 'tc_xapi_report_query', $query, $this->filters, self::TABLE_QUIZ, $columns, $where );
	}

	/**
	 * Return List of Module for Course
	 *
	 * @access private
	 *
	 * @param int $course_id
	 *
	 * @return array
	 * @since  1.0.0
	 */
	private function get_modules_by_course( $course_id, $table_type = '' ) {
		if ( ! $this->set_user_id() ) {
			return array();
		}

		if ( ! current_user_can( apply_filters( 'uo_tincanny_reporting_capability', 'tincanny_reporting' ) ) ) {
			return array();
		}

		if ( ! $course_id ) {
			return array();
		}

		$cache = Cache::get( __FUNCTION__ . 'TINCAN_MODULES_BY_COURSE' );
		if ( ! empty( $cache ) ) {
			return $cache;
		}

		$table_name = self::TABLE_REPORTING;
		if ( 'quiz' === $table_type ) {
			$table_name = self::TABLE_QUIZ;
		}

		global $wpdb;

		$where = $this->get_group_leader_groups_query_string();
		$query = sprintf(
			'
			SELECT reporting.module, reporting.module_name
				FROM %s%s reporting
				WHERE reporting.course_id = %s AND (%s)
				GROUP BY reporting.module
				ORDER BY reporting.module_name ASC',
			$wpdb->prefix,
			$table_name,
			$course_id,
			$where
		);

		$results = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		Cache::create( __FUNCTION__ . 'TINCAN_MODULES_BY_COURSE', $results );

		return $results;
	}

}
