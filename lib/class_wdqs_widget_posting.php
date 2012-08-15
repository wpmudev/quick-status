<?php
/**
 * Shows Status posting form
 */
class Wdqs_WidgetPosting extends WP_Widget {

	function Wdqs_WidgetPosting () {
		$widget_ops = array('classname' => __CLASS__, 'description' => __('Shows Status update form in a widget', 'wdqs'));
		parent::WP_Widget(__CLASS__, 'Status Update', $widget_ops);
	}

	function form($instance) {
		$title = esc_attr($instance['title']);

		// Set defaults
		// ...

		$html = '<p>';
		$html .= '<label for="' . $this->get_field_id('title') . '">' . __('Title:', 'wdqs') . '</label>';
		$html .= '<input type="text" name="' . $this->get_field_name('title') . '" id="' . $this->get_field_id('title') . '" class="widefat" value="' . $title . '"/>';
		$html .= '</p>';

		$data = new Wdqs_Options;
		if ('manual' != $data->get('placement')) {
			$html .= '<div class="error below-h2"><p>' . __('Please, switch your placement settings to <em>manual</em> in plugin settings', 'wdqs') . '</p></div>';
		}

		echo $html;
	}

	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);

		return $instance;
	}

	function widget($args, $instance) {
		extract($args);
		$title = apply_filters('widget_title', $instance['title']);

		$status = new Wdqs_PublicPages;
		$placement = $status->data->get('placement');
		if ('widget' == $placement) {
			echo $before_widget;
			if ($title) echo $before_title . $title . $after_title;

			$status->status_widget();

			echo $after_widget;
		}
	}

	function inject_script_dependencies () {
		echo '<link type="text/css" rel="stylesheet" href="' .WDQS_PLUGIN_URL  . '/css/status_widget.css" />';
		echo '<script type="text/javascript">var _wdqs_widget_ajaxurl="' . admin_url('admin-ajax.php') . '";</script>';
		echo '<script type="text/javascript" src="' . WDQS_PLUGIN_URL . '/js/status_widget.js"></script>';
	}

	function json_wdqs_list_posts () {
		$count = (int)$_POST['count'];
		header('Content-type: application/json');
		echo json_encode(array(
			'markup' => $this->_prepare_post_list_items($count),
		));
		exit();
	}

	private function _prepare_post_list_items ($count=1, $type=false) {
		$posts = $this->_get_status_posts($count, $type);
		$out = '';
		foreach ($posts as $post) {
			$item = '<li>' .
				$this->_get_avatar_markup($post) . 
				'<div class="wdqs_widget_status_title">' . $post->post_title . '</div>' .
				'<div class="wdqs_widget_status_body">' . $post->post_content . '</div>' .
			'</li>';
			$out .= $item;
		}
		return $out;
	}

	private function _get_avatar_markup ($post) {
		if (!$this->_avatars) return false;
		$key = $this->_avatars;
		$map = apply_filters('wdqs-widget-avatars_size_map', $this->_avatar_size_map);
		$size = in_array($key, array_keys($map)) ? $map[$key] : apply_filters('wdqs-widget-default_size_mapping', false);
		if (!$size) return false; // Unknown mapping

		$user = get_userdata($post->post_author);
		$name = apply_filters('wdqs-widget-avatar_user_name', $user->display_name, $user);

		$avatar = get_avatar($post->post_author, $size, null, $name);
		return '<div class="wdqs-author_avatar ' . esc_attr($key) . '" title="' . esc_attr($name) . '">' . $avatar . '</div>';
	}

	private function _get_status_posts ($count=1, $type=false) {
		$args = array(
			'posts_per_page' => (int)$count,
			'meta_key' => 'wdqs_type',
			'post_status' => 'publish',
			'orderby' => 'date',
		);
		if ($type) {
			$args['meta_value'] = $type;
		}
		$q = new Wp_Query($args);
		return $q->posts;
	}
}