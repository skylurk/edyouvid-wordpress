<?php
/*
Plugin Name: Manage Enrollment for LearnDash
Plugin URI: https://www.nextsoftwaresolutions.com/manage-enrollment-for-learndash/
Description: Manage Enrollment for LearnDash, lets you enroll users to courses or groups in bulk. You can select users from a given UI, paste a list of users or use the CSV to bulk upload your selected courses, groups and users for processing.
Author: Next Software Solutions
Version: 1.3
Author URI: https://www.nextsoftwaresolutions.com
*/

/**
 * Manage Enrollment for LearnDash
 */
class manage_enrollment_learndash {
	public $version = "1.3";
	public $learndash_link = "https://www.nextsoftwaresolutions.com/r/learndash/manage_enrollment_learndash";
	public $import_user_from_csv_link = "https://wordpress.org/plugins/import-users-from-csv/";

	function __construct() {
		if(!is_admin())
			return;

//		$addon_plugins_file = dirname(__FILE__)."/addon_plugins/functions.php";
//		if(!class_exists('grassblade_addons') && file_exists($addon_plugins_file))
//		require_once($addon_plugins_file);

		global $manage_enrollment_learndash;
		$manage_enrollment_learndash = array("uploaded_data" => array(), "upload_error" => array(), "ajax_url" => admin_url("admin-ajax.php"));

		add_action( 'admin_menu', array($this,'menu'), 10);

		add_action( 'wp_ajax_manage_enrollment_learndash_course_selected', array($this, 'course_selected') );

		add_action( 'wp_ajax_manage_enrollment_learndash_enroll', array($this, 'enroll') );

		add_action( 'wp_ajax_manage_enrollment_learndash_unenroll', array($this, 'unenroll') );

		add_action( 'wp_ajax_manage_enrollment_learndash_check_enrollment', array($this, 'check_enrollment') );
		add_action( 'wp_ajax_manage_enrollment_learndash_get_enrolled_users', array($this, 'get_enrolled_users') );

		add_filter("learndash_submenu", array($this, "learndash_submenu"), 1, 1 );

		/* Import User from CSV Integration */
		add_action("is_iu_import_page_inside_table_bottom", array($this, "iu_import_page_inside_table_bottom"));
		add_filter("is_iu_import_usermeta", array($this, "iu_import_usermeta"), 10, 2);

		if( !empty($_GET['page']) && $_GET['page'] == "grassblade-manage-enrollment-learndash"  ) {

			if( !empty($_POST["manage_enrollment_learndash"]) && !empty($_FILES['enrollments_file']['name'])) {
				add_filter('upload_mimes', array($this, 'upload_mimes'));
				add_action( 'admin_init', array($this, "process_upload"));
			}
			add_action("admin_print_styles", array($this, "manage_enrollment_learndash_scripts"));
		}
	}
	function manage_enrollment_learndash_scripts() {
		global $manage_enrollment_learndash;

		wp_enqueue_script('manage_enrollment_learndash', plugins_url('/script.js', __FILE__), array('jquery'), $this->version );
		wp_enqueue_style("manage_enrollment_learndash", plugins_url("/style.css", __FILE__), array(), $this->version );
		wp_enqueue_script("select2js", plugins_url("/vendor/select2/js/select2.min.js", __FILE__), array(), $this->version );
		wp_enqueue_style("select2css", plugins_url("/vendor/select2/css/select2.min.css", __FILE__), array(), $this->version );
		wp_localize_script( 'manage_enrollment_learndash', 'manage_enrollment_learndash',  $manage_enrollment_learndash);

		wp_add_inline_style("manage_enrollment_learndash", '#manage_enrollment_learndash_table .has_xapi {background: url('.esc_url( plugins_url("img/icon-gb.png", __FILE__) ).'}');
	}
	function upload_mimes ( $existing_mimes=array() ) {
		// add your extension to the mimes array as below
		$existing_mimes['csv'] = 'text/csv';
		return $existing_mimes;
	}
	function process_upload() {
		global $manage_enrollment_learndash;
		if(empty($manage_enrollment_learndash) || !is_array($manage_enrollment_learndash))
		$manage_enrollment_learndash = array();

		if(strtolower( pathinfo($_FILES['enrollments_file']['name'], PATHINFO_EXTENSION) ) != "csv" || $_FILES["enrollments_file"]["type"] != "text/csv" && $_FILES["enrollments_file"]["type"] != "application/vnd.ms-excel")
		{
			$manage_enrollment_learndash["upload_error"] = __('Upload Error: Invalid file format. Please upload a valid csv file', 'manage-enrollment-learndash');
			return;
		}
		require_once(dirname(__FILE__)."/../grassblade/addons/parsecsv.lib.php");
		$csv = new parseCSV($_FILES['enrollments_file']['tmp_name']);
		if(empty($csv->data) || !is_array($csv->data) || empty($csv->data[0]))
		{
			$manage_enrollment_learndash["upload_error"] = __('Upload Error: Empty csv file', 'manage-enrollment-learndash');
			return;
		}
		$csv_data = array();
		foreach ($csv->data as $k => $data) {
			$csv_data[$k] = array();
			foreach ($data as $j => $val) {
				$j = str_replace(" ", "_", strtolower(trim($j)));
				$csv_data[$k][$j] = $val;
			}
		}
//		if(!isset($csv->data[0]["user_id"]) || !isset($csv->data[0]["course_id"])) {
//			$manage_enrollment_learndash["upload_error"] = __('Upload Error: Invalid file format. Expected columns: user_id, course_id, group_id', 'manage-enrollment-learndash');
//			return;
//		}

		$uploaded_data = $courses = $rejected_rows = $partially_rejected_rows = array();
		$allowed_columns = array("user_id", "course_id", "group_id" );

		if(!empty($_POST["create_users"])) {
			$csv_data = $this->create_users($csv_data);
		}
		foreach ($csv_data as $k => $data) {
			$row = array();
			$empty_row = true;

			foreach ($allowed_columns as $col) {
				if(!empty($data[$col]))
					$empty_row = false;

				if($col == "user_id")
				$row[$col] = (isset($data[$col]) && is_numeric($data[$col]))? intVal($data[$col]):"";
				else
				$row[$col] = !isset($data[$col])? "":((isset($data[$col]) && is_numeric($data[$col]))? intVal($data[$col]):explode(",", $data[$col]));

				if(isset($data[$col]) && is_array($data[$col]))
				foreach ($data[$col] as $key => $value) {
					$data[$col][$key] = is_numeric($value)? intVal($value):"";
				}
			}

			if($empty_row)
				continue;

			if(empty($row["user_id"])) {
				if(!empty($data["user_email"])) {
					$user = get_user_by("email", $data["user_email"]);
					if(!empty($user->ID))
						$row["user_id"] = $user->ID;
				}
			}
			if(empty($row["user_id"])) {
				if(!empty($data["user_login"])) {
					$user = get_user_by("login", $data["user_login"]);
					if(!empty($user->ID))
						$row["user_id"] = $user->ID;
				}
			}
			//echo "<pre>";print_r($data);print_r($row);echo "</pre>";
			if(empty($row["user_id"])) {
				$rejected_rows[] = $k + 2;
				continue;
			}

			if(empty($row["course_id"]) && empty($row["group_id"])) {
				$rejected_rows[] = $k + 2;
				continue;
			}

			$row_courses = empty($row["course_id"])? array(0):(is_array($row["course_id"])? $row["course_id"]:array($row["course_id"]));
			$row_groups = empty($row["group_id"])? array(0):(is_array($row["group_id"])? $row["group_id"]:array($row["group_id"]));

			$rejected = false;
			$accepted = false;

			foreach ($row_courses as $course_id) {
				foreach ($row_groups as $group_id) {

					if(!empty($course_id)) {
						if(!empty($courses[$course_id]))
							$course = $courses[$course_id];
						else {
							$course = get_post($course_id);
							if(!empty($course->ID) && $course->post_status == "publish" && $course->post_type == "sfwd-courses")
								$courses[$course_id] = $course;
							else
								$course = null;
						}

						if(empty($course->ID)) {
							$rejected = true;
						}
						else
						{
							$uploaded_data[] = array("user_id" => $row["user_id"], "course_id" => $course_id, "group_id" => "");
							$accepted = true;
						}
					}

					if(!empty($group_id)) {
						if(!empty($groups[$group_id]))
							$group = $groups[$group_id];
						else {
							$group = get_post($group_id);
							if(!empty($group->ID) && $group->post_status == "publish" && $group->post_type == "groups")
								$groups[$group_id] = $group;
							else
								$group = null;
						}

						if(empty($group->ID)) {
							$rejected = true;
						}
						else
						{
							$uploaded_data[] = array("user_id" => $row["user_id"], "course_id" => "", "group_id" => $group_id);
							$accepted = true;
						}
					}
				}
			}

			if( $rejected && !$accepted )
				$rejected_rows[] = $k + 2;
			else if( $rejected && $accepted )
				$partially_rejected_rows[] = $k + 2;
		}

		$manage_enrollment_learndash["uploaded_data"] 		= $uploaded_data;

		$upload_errors = array();

		if(!empty($rejected_rows))
		$upload_errors[] = "Rejected Rows: ".implode(", ", $rejected_rows);

		if(!empty($partially_rejected_rows))
		$upload_errors[] = "Partially Rejected Rows: ".implode(", ", $partially_rejected_rows);

		$manage_enrollment_learndash["upload_error"] = implode("<br>", $upload_errors);
	}
	function menu() {
		global $submenu, $admin_page_hooks;
		$icon = plugin_dir_url(__FILE__)."img/icon-gb.png";

		if(empty( $admin_page_hooks[ "grassblade-lrs-settings" ] )) {
			add_menu_page("GrassBlade", "GrassBlade", "manage_options", "grassblade-lrs-settings", array($this, 'menu_page'), $icon, null);
			if( !empty($_GET['page']) && $_GET['page'] == "grassblade-lrs-settings"  )
			add_action("admin_print_styles", array($this, "manage_enrollment_learndash_scripts"));
		}

		add_submenu_page("grassblade-lrs-settings", __('Manage Enrollment LearnDash', 'manage-enrollment-learndash'), __('Manage Enrollment LearnDash', 'manage-enrollment-learndash'),'manage_options','grassblade-manage-enrollment-learndash', array($this, 'menu_page'));
	}
	function create_users( $data ) {

	}
	function form() {
		global $wpdb, $manage_enrollment_learndash;

		$courses = get_posts("post_type=sfwd-courses&posts_per_page=-1&post_status=publish");
		$groups = get_posts("post_type=groups&posts_per_page=-1&post_status=publish");
		$users = $wpdb->get_results("SELECT ID, user_login, user_email, display_name FROM $wpdb->users ORDER BY display_name ASC");
		extract($manage_enrollment_learndash);

		if(class_exists("IS_IU_Import_Users"))
			$import_user_from_csv_link = admin_url("users.php?page=import-users-from-csv");
		else
			$import_user_from_csv_link = $this->import_user_from_csv_link;

		$this->manage_enrollment_learndash_scripts();
		include_once (dirname(__FILE__) . "/form.php");
	}
	function menu_page() {

		if (!current_user_can('manage_options'))
		{
		  wp_die( __('You do not have sufficient permissions to access this page.') );
		}

		$grassblade_plugin_file_path = WP_PLUGIN_DIR . '/grassblade/grassblade.php';
		if(!defined("GRASSBLADE_VERSION") && file_exists($grassblade_plugin_file_path)) {
			$grassblade_plugin_data = get_plugin_data($grassblade_plugin_file_path);
			define('GRASSBLADE_VERSION', @$grassblade_plugin_data['Version']);
		}

		$learndash_plugin_file_path = WP_PLUGIN_DIR . '/sfwd-lms/sfwd_lms.php';
		if(!defined("LEARNDASH_VERSION") && file_exists($learndash_plugin_file_path)) {
			$learndash_plugin_data = get_plugin_data($learndash_plugin_file_path);
			define('LEARNDASH_VERSION', @$learndash_plugin_data['Version']);
		}

		$dependency_active = true;

		if (!file_exists($grassblade_plugin_file_path) ) {
			$xapi_td = '<td><img src="'.plugin_dir_url(__FILE__).'img/no.png"/> '.(defined("GRASSBLADE_VERSION")? GRASSBLADE_VERSION:"").'</td>';
			$xapi_td .= '<td>
							<a class="buy-btn" href="https://www.nextsoftwaresolutions.com/grassblade-xapi-companion/">'.__("Buy Now", 'manage-enrollment-learndash').'</a>
						</td>';
			$dependency_active = false;
		}
		else {
			$xapi_td = '<td><img src="'.plugin_dir_url(__FILE__).'img/check.png"/> '.(defined("GRASSBLADE_VERSION")? GRASSBLADE_VERSION:"").'</td>';
			if ( !is_plugin_active('grassblade/grassblade.php') ) {
				$xapi_td .= '<td>'.$this->activate_plugin("grassblade/grassblade.php").'</td>';
				$dependency_active = false;
			}else {
				$xapi_td .= '<td><img src="'.plugin_dir_url(__FILE__).'img/check.png"/></td>';
			}
		}

		if (!file_exists( $learndash_plugin_file_path ) ) {
			$learndash_td = '<td><img src="'.plugin_dir_url(__FILE__).'img/no.png"/> '.(defined("LEARNDASH_VERSION")? LEARNDASH_VERSION:"").'</td>';
			$learndash_td .= '<td colspan="2">
							<a class="buy-btn" href="'.esc_url($this->learndash_link).'">'.__("Buy Now", 'manage-enrollment-learndash').'</a>
						</td>';
				$dependency_active = false;
		}
		else {
			$learndash_td = '<td><img src="'.plugin_dir_url(__FILE__).'img/check.png"/> '.(defined("LEARNDASH_VERSION")? LEARNDASH_VERSION:"").'</td>';
			if ( !is_plugin_active('sfwd-lms/sfwd_lms.php') ) {
				$learndash_td .= '<td>'.$this->activate_plugin("sfwd-lms/sfwd_lms.php").'</td>';
				$dependency_active = false;
			} else {
				$learndash_td .= '<td><img src="'.plugin_dir_url(__FILE__).'img/check.png"/></td>';
			}
		}

		if($dependency_active)
			$this->form();
		else {

		?>
		<div id="manage_enrollment_learndash" class="manage_enrollment_learndash_requirements">
			<h2>
				<img style="margin-right: 10px;" src="<?php echo esc_url(plugin_dir_url(__FILE__)."img/icon_30x30.png"); ?>"/>
				Manage Enrollment for LearnDash
			</h2>
			<hr>
			<div>
				<p class="text">To use Manage Enrollment for LearnDash, you need to meet the following requirements.</p>
				<h2>Requirements:</h2>
				<table class="requirements-tbl">
					<thead>
						<tr>
							<th><?php _e("SNo", "manage-enrollment-learndash"); ?></th>
							<th><?php _e("Requirements", 'manage-enrollment-learndash'); ?></th>
							<th><?php _e("Installed", 'manage-enrollment-learndash'); ?></th>
							<th><?php _e("Active", 'manage-enrollment-learndash'); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>1. </td>
							<td><a class="links" href="https://www.nextsoftwaresolutions.com/grassblade-xapi-companion/">GrassBlade xAPI Companion</a></td>
							<?php echo wp_kses($xapi_td, 'post'); ?>
						</tr>
						<tr>
							<td>2. </td>
							<td><a class="links" href="<?php echo $this->learndash_link; ?>">LearnDash LMS</a></td>
							<?php echo wp_kses($learndash_td, 'post'); ?>
						</tr>
					</tbody>
				</table>
				<br>
			</div>
		</div>
	<?php }
	}
	/**
	 * Generate an activation URL for a plugin like the ones found in WordPress plugin administration screen.
	 *
	 * @param  string $plugin A plugin-folder/plugin-main-file.php path (e.g. "my-plugin/my-plugin.php")
	 *
	 * @return string         The plugin activation url
	 */
	function activate_plugin($plugin)
	{
		$activation_link = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . urlencode( $plugin ), 'activate-plugin_' . $plugin );

		$link = '<a class="grassblade_learndash_activate_plugin" href="#" data-url="'.$activation_link.'">'.__("Activate").'</a>';
		return $link;
	}
	function learndash_submenu($add_submenu) {
		$add_submenu["manage_enrollment_learndash"] = array(
			"name"  =>      __('Manage Enrollment', 'manage-enrollment-learndash'),
			"cap"   =>      "manage_options",
			"link"  => 'admin.php?page=grassblade-manage-enrollment-learndash'
		);
		return $add_submenu;
	}
	function check_enrollment($enrollment) {

		if(!current_user_can("manage_options") || empty($_REQUEST["data"]) || (!is_array($_REQUEST["data"]) && !is_object($_REQUEST["data"])) )
			$this->json_out(array("status" => 0, "message" => "Invalid Data"));

		$enrollments = $_REQUEST["data"];
		foreach ($enrollments as $k => $enrollment) {
			$k 			= intVal($k);
			$course_id 	= empty($enrollment["course_id"])? "":intVal($enrollment["course_id"]);
			$group_id 	= empty($enrollment["group_id"])? "":intVal($enrollment["group_id"]);
			$user_id 	= empty($enrollment["user_id"])? "":intVal($enrollment["user_id"]);

			if(empty($course_id) && empty($group_id)) {
				$enrollments[$k]["message"] = __("Course/Group not selected.", 'manage-enrollment-learndash');
				$enrollments[$k]["status"] = 0;
			}
			else
			if(empty($user_id)) {
				$enrollments[$k]["message"] = __("User not selected.", 'manage-enrollment-learndash');
				$enrollments[$k]["status"] = 0;
			}
			else if( !empty($course_id) ) {
				//Course Selected

				if( !ld_course_check_user_access($course_id, $user_id) ) {
					$enrollments[$k]["message"] = __("User not enrolled to course.", 'manage-enrollment-learndash');
					$enrollments[$k]["status"] = 0;
				}
				else
				{
					$enrollments[$k]["message"] = __("Already Enrolled", 'manage-enrollment-learndash');
					$enrollments[$k]["status"] 	= 1;
				}
			}
			else if( !empty($group_id) ) {
				//Group Selected
				//Check Group Enrollment
				$learndash_users_group_ids = learndash_get_users_group_ids( $user_id );
				if(empty($learndash_users_group_ids) || !in_array($group_id, $learndash_users_group_ids)) {
					$enrollments[$k]["message"] = __("User not enrolled to group.", 'manage-enrollment-learndash');
					$enrollments[$k]["status"] = 0;
				}
				else
				{
					$enrollments[$k]["message"] = __("Already Enrolled", 'manage-enrollment-learndash');
					$enrollments[$k]["status"] 	= 1;
				}
			}
		}

		$this->json_out( array("status" => 1, "data" => $enrollments) );

	}
	function get_enrolled_users() {
		if(!current_user_can("manage_options") || empty($_REQUEST["course_id"]) &&  empty($_REQUEST["group_id"]))
			$this->json_out(array("status" => 0, "message" => __("Invalid Request", 'manage-enrollment-learndash')));

		if(!empty($_REQUEST["course_id"]) && is_numeric($_REQUEST["course_id"])) {
			$course_id = intVal($_REQUEST["course_id"]);

			$user_ids = learndash_get_course_users_access_from_meta($course_id);
			$user_ids = array_map("intVal", $user_ids);
			$this->json_out( array("status" => 1, "data" => $user_ids, "course_id" => $course_id) );
		}
		if(!empty($_REQUEST["group_id"]) && is_numeric($_REQUEST["group_id"])) {
			$group_id = intVal($_REQUEST["group_id"]);

			$user_ids = learndash_get_groups_user_ids($group_id);
			$user_ids = array_map("intVal", $user_ids);
			$this->json_out( array("status" => 1, "data" => $user_ids, "group_id" => $group_id) );
		}

		$this->json_out(array("status" => 0, "message" => __("Invalid Request", 'manage-enrollment-learndash')));
	}
	function enroll() {

		if(!current_user_can("manage_options") || empty($_REQUEST["data"]) || (!is_array($_REQUEST["data"]) && !is_object($_REQUEST["data"])) )
			$this->json_out(array("status" => 0, "message" => __("Invalid Data", 'manage-enrollment-learndash')));

		$enrollments = $_REQUEST["data"];
		foreach ($enrollments as $k => $enrollment) {
			$k 			= intVal($k);
			$course_id 	= empty($enrollment["course_id"])? "":intVal($enrollment["course_id"]);
			$group_id 	= empty($enrollment["group_id"])? "":intVal($enrollment["group_id"]);
			$user_id 	= empty($enrollment["user_id"])? "":intVal($enrollment["user_id"]);

			if(empty($course_id) && empty($group_id)) {
				$enrollments[$k]["message"] = __("Course not selected.", 'manage-enrollment-learndash');
				$enrollments[$k]["status"] = 0;
			}
			else
			if(empty($user_id)) {
				$enrollments[$k]["message"] = __("User not selected.", 'manage-enrollment-learndash');
				$enrollments[$k]["status"] = 0;
			}
			else if( !empty($course_id) ) {
				//Course Selected

				if( ld_course_check_user_access($course_id, $user_id) ) {
					$enrollments[$k]["message"] = __("Already Enrolled.", 'manage-enrollment-learndash');
					$enrollments[$k]["status"] = 1;
				}
				else
				{
					$success = ld_update_course_access($user_id, $course_id);
					if($success) {
						$enrollments[$k]["message"] = __("Enrolled", 'manage-enrollment-learndash');
						$enrollments[$k]["status"] = 1;
					}
					else
					{
						$enrollments[$k]["message"] = __("Failed to Enroll", 'manage-enrollment-learndash');
						$enrollments[$k]["status"] = 0;
					}
				}
			}
			else if( !empty($group_id) ) {
				//Group Selected

				$learndash_users_group_ids = learndash_get_users_group_ids( $user_id );
				if(!empty($learndash_users_group_ids) && in_array($group_id, $learndash_users_group_ids)) {
					$enrollments[$k]["message"] = __("Already Enrolled.", 'manage-enrollment-learndash');
					$enrollments[$k]["status"] = 1;
				}
				else
				{
					$success = ld_update_group_access($user_id, $group_id);
					if($success) {
						$enrollments[$k]["message"] = __("Enrolled", 'manage-enrollment-learndash');
						$enrollments[$k]["status"] = 1;
					}
					else
					{
						$enrollments[$k]["message"] = __("Failed to Enroll", 'manage-enrollment-learndash');
						$enrollments[$k]["status"] = 0;
					}
				}
			}
		}

		$this->json_out( array("status" => 1, "data" => $enrollments) );
	}
	function unenroll() {

		if(!current_user_can("manage_options") || empty($_REQUEST["data"]) || (!is_array($_REQUEST["data"]) && !is_object($_REQUEST["data"])) )
			$this->json_out(array("status" => 0, "message" => __("Invalid Data", 'manage-enrollment-learndash')));

		$enrollments = $_REQUEST["data"];
		foreach ($enrollments as $k => $enrollment) {
			$k 			= intVal($k);
			$course_id 	= empty($enrollment["course_id"])? "":intVal($enrollment["course_id"]);
			$group_id 	= empty($enrollment["group_id"])? "":intVal($enrollment["group_id"]);
			$user_id 	= empty($enrollment["user_id"])? "":intVal($enrollment["user_id"]);

			if(empty($course_id) && empty($group_id)) {
				$enrollments[$k]["message"] = __("Course not selected.", 'manage-enrollment-learndash');
				$enrollments[$k]["status"] = 0;
			}
			else
			if(empty($user_id)) {
				$enrollments[$k]["message"] = __("User not selected.", 'manage-enrollment-learndash');
				$enrollments[$k]["status"] = 0;
			}
			else if( !empty($course_id) ) {
				//Course Selected
				if( !ld_course_check_user_access($course_id, $user_id) ) {
					$enrollments[$k]["message"] = __("User not enrolled to course.", 'manage-enrollment-learndash');
					$enrollments[$k]["status"] = 1;
				}
				else
				{
					$success = ld_update_course_access($user_id, $course_id, true);
					if($success) {
						$enrollments[$k]["message"] = __("Removed Enrollement.", 'manage-enrollment-learndash');
						$enrollments[$k]["status"] = 1;
					}
					else
					{
						$enrollments[$k]["message"] = __("Failed to remove enrollment.", 'manage-enrollment-learndash');
						$enrollments[$k]["status"] = 0;
					}
				}
			}
			else if( !empty($group_id) ) {
				//Group Selected

				$learndash_users_group_ids = learndash_get_users_group_ids( $user_id );
				if(empty($learndash_users_group_ids) || !in_array($group_id, $learndash_users_group_ids)) {
					$enrollments[$k]["message"] = __("User not enrolled to group.", 'manage-enrollment-learndash');
					$enrollments[$k]["status"] = 1;
				}
				else
				{
					$success = ld_update_group_access($user_id, $group_id, true);
					if($success) {
						$enrollments[$k]["message"] = __("Removed Enrollement.", 'manage-enrollment-learndash');
						$enrollments[$k]["status"] = 1;
					}
					else
					{
						$enrollments[$k]["message"] = __("Failed to remove enrollment.", 'manage-enrollment-learndash');
						$enrollments[$k]["status"] = 0;
					}
				}

			}
		}

		$this->json_out( array("status" => 1, "data" => $enrollments) );
	}
	function json_out($data) {
		header('Content-Type: application/json');
		echo json_encode($data);
		exit();
	}


	function iu_import_page_inside_table_bottom() {
		if(!function_exists("ld_course_list"))
		return;

		wp_enqueue_script('manage_enrollment_learndash', plugins_url('/script.js', __FILE__), array('jquery'), $this->version );
		wp_enqueue_script("select2js", plugins_url("/vendor/select2/js/select2.min.js", __FILE__), array(), $this->version );
		wp_enqueue_style("select2css", plugins_url("/vendor/select2/css/select2.min.css", __FILE__), array(), $this->version );
		//wp_localize_script( 'manage_enrollment_learndash', 'manage_enrollment_learndash',  $manage_enrollment_learndash);

		$courses = ld_course_list(array("array" => true, "num" => -1));
		$groups = ld_group_list(array("array" => true, "num" => -1));

		?>
		<tr valign="top">
			<td scope="row"><strong><?php _e( 'LearnDash Course Enrollment' , 'manage-enrollment-learndash'); ?></strong></td>
			<td>
				<select id="enroll_learndash_courses" name="enroll_learndash_courses[]" multiple="multiple">
					<?php
						if(!empty($courses))
						foreach ($courses as $course) {
							?>
							<option value="<?php echo intVal($course->ID); ?>"><?php echo sanitize_text_field($course->post_title); ?></option>
							<?php
						}
					?>
				</select>
			</td>

		</tr>
		<tr valign="top">
			<td scope="row"><strong><?php _e( 'LearnDash Group Enrollment' , 'manage-enrollment-learndash'); ?></strong></td>
			<td>
				<select id="enroll_learndash_groups" name="enroll_learndash_groups[]" multiple="multiple">
					<?php
						if(!empty($groups))
						foreach ($groups as $group) {
							?>
							<option value="<?php echo intVal($group->ID); ?>"><?php echo sanitize_text_field($group->post_title); ?></option>
							<?php
						}
					?>
				</select>
			</td>
		</tr>
		<?php
	}

	function iu_import_usermeta($usermeta, $userdata) {
		if(!function_exists("ld_course_list"))
		return;

		if(!empty($_POST["enroll_learndash_courses"]) && is_array($_POST["enroll_learndash_courses"])) {
			foreach ($_POST["enroll_learndash_courses"] as $id) {
				$id = intVal($id);
				if(!empty($id) && !isset($usermeta["course_".$id."_access_from"])) {
					$usermeta["course_".$id."_access_from"] = time();
				}
			}
		}
		if(!empty($_POST["enroll_learndash_groups"]) && is_array($_POST["enroll_learndash_groups"])) {
			foreach ($_POST["enroll_learndash_groups"] as $id) {
				$id = intVal($id);
				if(!empty($id) && !isset($usermeta["learndash_group_users_".$id])) {
					$usermeta["learndash_group_users_".$id] = $id;
				}
			}
		}

		return $usermeta;
	}
}

new manage_enrollment_learndash();
