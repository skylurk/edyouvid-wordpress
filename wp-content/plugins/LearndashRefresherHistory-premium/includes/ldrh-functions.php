<?php
if( !function_exists('ldrh_send_email') ){
    function ldrh_send_email($type, $args = array()){
        $message = '';
        $subject = '';
        $to = '';
        $headers = '';
        $attachments = array();

        switch($type){
            case 'notify_admin':
                $message = get_option('ldrh_admin_email_template', 'User courses that needs to be refreshed<br/><br/>{table}');
                $message = str_replace('{table}', $args['table'], $message);
                $subject = __('User Courses Refresher Notification', 'ldrh');
                $admin_email = get_option('admin_email');
                $sitename = get_option('blogname', 'Learndash Refresher History');
                $headers = array();
                $headers[] = 'Content-Type: text/html; charset=UTF-8';
                $headers[] = 'From: '.$sitename.' <' . $admin_email . '>';
                $to = $args['to'] ? $args['to'] : $admin_email;
                break;
            case 'notify_leaders':
                $message = get_option('ldrh_leaders_email_template', 'User courses that needs to be refreshed<br/><br/>{table}');
                $message = str_replace('{table}', $args['table'], $message);
                $subject = __('User Courses Refresher Notification', 'ldrh');
                $admin_email = get_option('admin_email');
                $sitename = get_option('blogname', 'Learndash Refresher History');
                $headers = array();
                $headers[] = 'Content-Type: text/html; charset=UTF-8';
                $headers[] = 'From: '.$sitename.' <' . $admin_email . '>';
                $to = $args['to'];
                break;
            case 'notify_student':
                $message = get_option('ldrh_student_email_template', '{course} needs to be refreshed');
                $message = str_replace('{course}', $args['course'], $message);
                $subject = '"'.$args['course'] .'" '.__('needs to be refreshed', 'ldrh');
                $admin_email = get_option('admin_email');
                $sitename = get_option('blogname', 'Learndash Refresher History');
                $headers = array();
                $headers[] = 'Content-Type: text/html; charset=UTF-8';
                $headers[] = 'From: '.$sitename.' <' . $admin_email . '>';
                $to = $args['to'];
                break;
        }

        return wp_mail($to, $subject, $message, $headers, $attachments);
    }
}

if( !function_exists('ldrh_remove_course_data') ){
    function ldrh_remove_course_data($user_id, $course_id){
        // remove course progress
        $progress = get_user_meta($user_id, '_sfwd-course_progress', true);
        if(isset($progress[$course_id])){
            unset($progress[$course_id]);
        }
        update_user_meta($user_id, '_sfwd-course_progress', $progress);

        // remove course quizzes
        $user_quizzes = get_user_meta($user_id, '_sfwd-quizzes', true);
        foreach($user_quizzes as $index => $record){
            if(isset($record['course'])){
                if($record['course'] == $course_id){
                    unset($user_quizzes[$index]);
                }
            }else{
                $quiz_course = get_post_meta($record['quiz'], 'course_id', true);
                if($quiz_course == $course_id){
                    unset($user_quizzes[$index]);
                }
            }
        }
        update_user_meta($user_id, '_sfwd-quizzes', $user_quizzes);

        delete_user_meta($user_id, 'course_completed_' . $course_id);
    }
}

if( !function_exists('ldrh_get_refresher_table') ){
    function ldrh_get_refresher_table($rows){
        $table = '<table style="background: #fff;border: 1px solid #ccd0d4;box-shadow: 0 1px 1px rgba(0,0,0,.04);border-spacing: 0;width: 100%;clear: both;margin: 0;">';
        
        $table .= '<thead>';
        $table .= '<tr>';
        $table .= '<th style="padding: 8px 10px;color: #32373c;text-align: left;line-height: 1.3em;font-size: 14px;border-bottom: 1px solid #ccd0d4;font-weight: bold;">'.__('User', 'ldrh').'</th>';
        $table .= '<th style="padding: 8px 10px;color: #32373c;text-align: left;line-height: 1.3em;font-size: 14px;border-bottom: 1px solid #ccd0d4;font-weight: bold;">'.__('Course', 'ldrh').'</th>';
        $table .= '</tr>';
        $table .= '</thead>';
        
        $table .= '<tbody>';
        foreach($rows as $user_info => $courseArr){
            $info = explode('##', $user_info);
            $user = '';
            $user .= isset($info[1]) && $info[1] ? $info[1] : '';
            $user .= isset($info[0]) && $info[0] ? '(ID: '.$info[0].')' : '';
            foreach($courseArr as $course_name){
                $table .= '<tr>';
                $table .= '<td style="padding: 8px 10px;color: #555;font-size: 13px;line-height: 1.5em;">'.$user.'</td>';
                $table .= '<td style="padding: 8px 10px;color: #555;font-size: 13px;line-height: 1.5em;">'.$course_name.'</td>';
                $table .= '</tr>';
            }
        }
        $table .= '</tbody>';
        
        $table .= '</table>';
        
        return $table;
    }
}

if( !function_exists('ldrh_get_leader_table') ){
    function ldrh_get_leader_table($rows){
        $table = '<table style="background: #fff;border: 1px solid #ccd0d4;box-shadow: 0 1px 1px rgba(0,0,0,.04);border-spacing: 0;width: 100%;clear: both;margin: 0;">';
        
        $table .= '<thead>';
        $table .= '<tr>';
        $table .= '<th style="padding: 8px 10px;color: #32373c;text-align: left;line-height: 1.3em;font-size: 14px;border-bottom: 1px solid #ccd0d4;font-weight: bold;">'.__('User', 'ldrh').'</th>';
        $table .= '<th style="padding: 8px 10px;color: #32373c;text-align: left;line-height: 1.3em;font-size: 14px;border-bottom: 1px solid #ccd0d4;font-weight: bold;">'.__('Course', 'ldrh').'</th>';
        $table .= '</tr>';
        $table .= '</thead>';
        
        $table .= '<tbody>';
        foreach($rows as $row){
            $user = '';
            $user .= isset($row['user']) && $row['user'] ? $row['user'] : '';
            $user .= isset($row['id']) && $row['id'] ? '(ID: '.$row['id'].')' : '';
            $course_name = isset($row['course']) && $row['course'] ? $row['course'] : '';
            
            $table .= '<tr>';
            $table .= '<td style="padding: 8px 10px;color: #555;font-size: 13px;line-height: 1.5em;">'.$user.'</td>';
            $table .= '<td style="padding: 8px 10px;color: #555;font-size: 13px;line-height: 1.5em;">'.$course_name.'</td>';
            $table .= '</tr>';
        }
        $table .= '</tbody>';
        
        $table .= '</table>';
        
        return $table;
    }
}

if( !function_exists('ldrh_user_course_belong_same_group') ){
    function ldrh_user_course_belong_same_group($user_id, $course_id) {
        $group_ids = learndash_get_users_group_ids($user_id);
        if (!empty($group_ids)) {
            foreach ($group_ids as $group_id) {
                if (learndash_group_has_course($group_id, $course_id)) {
                    return $group_id;
                }
            }
        }
        return false;
    }
}

if( !function_exists('ldrh_user_course_belong_same_group') ){
    function ldrh_get_leader_groups_users($leader_id = 0) {
	$user_ids = array();
	
	if(!empty($leader_id)){
            $groups = learndash_get_administrators_group_ids($leader_id);

            if ( !empty($groups)){
                foreach($groups as $group_id){
                    $group_users_ids = learndash_get_groups_user_ids($group_id);
                    if (!empty($group_users_ids)){
                        $user_ids = array_merge($user_ids, $group_users_ids);
                    }
                }
            }
	}

	if (!empty($user_ids)){
            $user_ids = array_unique( $user_ids );
	}
	
	return $user_ids;	
    }
}

if( !function_exists('ldrh_get_user_name') ){
    function ldrh_get_user_name($user){
        $name = '';

        if($user->first_name || $user->last_name){
            if($user->first_name || $user->last_name){
                $name = $user->first_name . " " . $user->last_name;
            }elseif($user->first_name){
                $name = $user->first_name;
            }elseif($user->last_name){
                $name = $user->first_name;
            }
        }elseif($user->display_name){
            $name = $user->display_name;
        }elseif($user->user_login){
            $name = $user->user_login;
        }

        return $name;
    }
}

if(!function_exists('ldrh_get_course_certificate_link')){
    function ldrh_get_course_certificate_link($course_id, $cert_user_id = null) {
        $cert_user_id = !empty($cert_user_id) ? intval($cert_user_id) : get_current_user_id();

        if (( empty($course_id) ) || ( empty($cert_user_id) )) {
            return '';
        }

        $certificate_id = learndash_get_setting($course_id, 'certificate');
        if (empty($certificate_id)) {
            return '';
        }

        if (( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() )){
            $view_user_id = get_current_user_id();
        }else{
            $view_user_id = $cert_user_id;
        }

        $cert_query_args = array(
            "course_id" => $course_id,
        );

        // We add the user query string key/value if the viewing user is an admin. This 
        // allows the admin to view other user's certificated
        if (( $cert_user_id != $view_user_id ) && ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) )) {
            $cert_query_args['user'] = $cert_user_id;
        }
        $cert_query_args['cert-nonce'] = wp_create_nonce($course_id . $cert_user_id . $view_user_id);

        $url = add_query_arg($cert_query_args, get_permalink($certificate_id));
        
        return $url;
    }
}