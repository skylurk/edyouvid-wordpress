<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

class LearnDash_Forum_Group_Widget extends WP_Widget {
	/**
	 * Init the class
	 */
	public function __construct() {
		$widget_args  = array(
			'classname'   => 'ld-forum-group-widget',
			/* Translators: 1. LearnDash grops label  */
			'description' => sprintf( esc_attr__( 'Display links to forums that are assigned to the user\'s Group(s).', 'uncanny-pro-toolkit' ), 'groups' ),
		);
		$control_args = array();

		parent::__construct( 'ld_forum_group',
			/* Translators: 1. LearnDash groups label  */
			sprintf( esc_attr__( '%1$s Forums', 'uncanny-pro-toolkit' ), 'Group' ),
			$widget_args,
			$control_args
		);
	}

	/**
	 * Output widget form on admin page
	 *
	 * @param array $instance Widget instance values
	 *
	 * @return void
	 */
	public function form( $instance ) {
		?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_attr_e( 'Title', 'uncanny-pro-toolkit' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
				   name="<?php echo $this->get_field_name( 'title' ); ?>" type="text"
				   value="<?php echo esc_attr( $instance['title'] ); ?>"/>
		</p>

		<?php
	}

	/**
	 * Update group instance values
	 *
	 * @param array $new_instance Widget instance values
	 * @param array $instance     Existing/old instance values
	 *
	 * @return array                New sanitized values
	 */
	public function update( $new_instance, $instance ) {
		$instance['title'] = sanitize_text_field( $new_instance['title'] );

		return $instance;
	}

	/**
	 * Output widget HTML
	 *
	 * @param array $args     Widget args
	 * @param array $instance Widget inputs
	 *
	 * @return void
	 */
	public function widget( $args, $instance ) {

		$user_forums = get_user_forum_group_access();

		$widget_content = '<div class="ld-group-forum-links-wrapper">';
		$widget_content .= '<h2 class="widget-title">' . $instance['title'] . '</h2>';
		$widget_content .= '<ul>';

		foreach ( $user_forums as $forum_id => $group_id ) {
			$forum = get_post( $forum_id );

			if ( $forum ) {
				$permalink      = get_the_permalink( $forum_id );
				$widget_content .= apply_filters( 'group_forum_widget_li', '<li><a href="' . $permalink . '">' . $forum->post_title . '</a></li>', $forum, $group_id, get_current_user_id() );
			}
		}

		$widget_content .= '</ul>';
		$widget_content .= "</div>";

		echo $args['before_widget'];
		if ( ! empty( $title ) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		echo $widget_content;
		echo $args['after_widget'];
	}
}

add_action( 'widgets_init', function () {
	register_widget( 'LearnDash_Forum_Group_Widget' );
} );