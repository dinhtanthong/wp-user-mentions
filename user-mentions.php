<?php 
/*
   Plugin Name: User Mentions
   Version: 0.0.1
   Author: Morgan Kelsie McGuire
   Author URI: http://morgankelsiemcguire.com
   Description: Enables user mentions in comments using @username
   Text Domain: user-mentions
   License: GPLv3
*/

function comments() {
	return is_singular() && comments_open(get_queried_object_id());
}

function um_scripts() {
	if(comments()):
   		wp_enqueue_script('user-mentions', plugin_dir_url(__FILE__) . 'user-mentions.js', array('jquery'), '0.0.1', true);
   		wp_localize_script('user-mentions', 'um', array('ajaxurl' => admin_url('admin-ajax.php'), 'action' => 'um_ajax_print_user_list'));
	endif;	
}

add_action('wp_enqueue_scripts', 'um_scripts');


function um_inline_styles() {
	if(comments()):
		echo "
<style type='text/css' id='user-mentions-inline-css'>
	.um-comment-form { position:relative; }
	.um-comment-autocomplete { background:white; list-style-type:none; padding:0; position:absolute; width:100%; }
	.um-comment-autocomplete li { padding:0.5em 1em; }
	.um-comment-autocomplete:hover .um-comment-autocomplete-selected { background:transparent; }   
	.um-comment-autocomplete-selected, .um-comment-autocomplete:hover .um-comment-autocomplete-selected:hover, .um-comment-autocomplete li:hover { background:#E5E5E5; }
</style>
";
	endif;
}

add_action('wp_print_styles', 'um_inline_styles');


function um_add_comment_form_class($defaults) {
    $defaults['class_form'] = 'um-comment-form ' . (isset($defaults['class_form']) ? $defaults['class_form'] : '');
    return $defaults;
}

add_filter('comment_form_defaults', 'um_add_comment_form_class');


function um_add_tags_to_comment($commentdata) {	
    $commentdata['comment_content'] = preg_replace_callback('/(?:\B@)([\w]+)/', function($matches) {
    	if($user = get_user_by('login', $matches[1])) return '<a href="'. get_author_posts_url($user->ID) .'">'. $matches[0] .'</a>';
    	return $matches[0];
    }, $commentdata['comment_content']);

    return $commentdata;
}

add_filter('preprocess_comment', 'um_add_tags_to_comment');


function um_notify_tagged_user_unmoderated($comment_ID, $comment_approved) {
	if($comment_approved)
		um_user_notification(get_comment($comment_ID));
}

add_action('comment_post', 'um_notify_tagged_user_unmoderated', 10, 2);

function um_notify_tagged_user_moderated($new_status, $old_status, $comment) {
	if($old_status !== $new_status && $new_status == 'approved')
		um_user_notification($comment);
}

add_action('transition_comment_status', 'um_notify_tagged_user_moderated', 10, 3);

function um_user_notification($comment) {
	preg_match_all('/(?:\B@)([\w]+)/', $comment->comment_content, $matches);

	if($matches[1]) {
		$sent = [];
		$blogname = get_option('blogname');
		$comment_link = get_comment_link($comment->comment_ID);

		foreach($matches[1] as $tag)
			if($user = get_user_by('login', $tag))
				if(!in_array($user, $sent)) {
					ob_start();
			        require um_user_notification_template();
			        $body = ob_get_clean();
					$subject = sprintf(__('[%s] %s mentioned you in a comment', 'user-mentions'), $blogname, $comment->comment_author);

					if(wp_mail($user->user_email, $subject, $body))
						$sent[] = $user;
				}
	}
}

function um_user_notification_template() {
    $template_path = locate_template('templates/user-mentions/notification.php');

    if($template_path) return $template_path; 

    return __DIR__ . '/templates/user-mentions/notification.php';
}


function um_get_user_list() {
	$search  = $_POST['search'];
	$fields  = array('user_login', 'display_name');
	$orderby = 'login';
	$users = get_users(array('search' => ($search ? "*$search*" : NULL), 'fields' => $fields, 'orderby' => $orderby, 'exclude' => [ get_current_user_id() ]));

	return $users;
}

function um_ajax_print_user_list() {
	echo json_encode(um_get_user_list());
	wp_die();
}

add_action('wp_ajax_um_ajax_print_user_list', 'um_ajax_print_user_list');
add_action('wp_ajax_nopriv_um_ajax_print_user_list', 'um_ajax_print_user_list');