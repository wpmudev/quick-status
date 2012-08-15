<?php
/**
 * Shows one or more last Status posts
 */
class Wdqs_WidgetStatus extends WP_Widget {

	private $_avatar_sizes = array();
	private $_avatar_size_map = array(
		'small' => 32,
		'medium' => 48,
		'large' => 64,
		'huge' => 96,
	);

	function Wdqs_WidgetStatus () {
		$widget_ops = array('classname' => __CLASS__, 'description' => __('Shows one or more last Status posts', 'wdqs'));
		parent::WP_Widget(__CLASS__, 'Status', $widget_ops);

		add_action('wp_ajax_wdqs_list_posts', array($this, 'json_wdqs_list_posts'));
		add_action('wp_ajax_nopriv_wdqs_list_posts', array($this, 'json_wdqs_list_posts'));
		wp_enqueue_script('jquery');

		$this->_avatar_sizes = array(
			0 => __('No avatars', 'wdqs'),
			'small' => __('Small', 'wdqs'),
			'medium' => __('Medium', 'wdqs'),
			'large' => __('Large', 'wdqs'),
			'huge' => __('Huge', 'wdqs'),
		);
	}

	function form($instance) {
		$title = esc_attr($instance['title']);
		$count = esc_attr($instance['count']);
		$autorefresh = esc_attr($instance['autorefresh']);
		$avatars = esc_attr($instance['avatars']);

		// Set defaults
		// ...

		$html = '<p>';
		$html .= '<label for="' . $this->get_field_id('title') . '">' . __('Title:', 'wdqs') . '</label>';
		$html .= '<input type="text" name="' . $this->get_field_name('title') . '" id="' . $this->get_field_id('title') . '" class="widefat" value="' . $title . '"/>';
		$html .= '</p>';

		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id('count') . '">' . __('Show this many posts:', 'wdqs') . '</label>';
		$html .= '<select name="' . $this->get_field_name('count') . '" id="' . $this->get_field_id('count') . '">';
		for ($i=1; $i<11; $i++) {
			$html .= '<option value="' . $i . '" ' . (($i == $count) ? 'selected="selected"' : '') . '>' . $i . '</option>';
		}
		$html .= '</select>';
		$html .= '</p>';

		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id('autorefresh') . '">' . __('Auto-update rate <small>(seconds)</small>:', 'wdqs') . '</label>';
		$html .= '<select name="' . $this->get_field_name('autorefresh') . '" id="' . $this->get_field_id('autorefresh') . '">';
		$html .= '<option value="">' . __('Never auto-update', 'wdqs') . '</option>';
		for ($i=5; $i<=120; $i+=5) {
			$html .= '<option value="' . $i . '" ' . (($i == $autorefresh) ? 'selected="selected"' : '') . '>' . $i . '</option>';
		}
		$html .= '</select> ';
		$html .= '</p>';

		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id('avatars') . '">' . __('Show avatars:', 'wdqs') . '</label>';
		$html .= '<select name="' . $this->get_field_name('avatars') . '" id="' . $this->get_field_id('avatars') . '">';
		foreach ($this->_avatar_sizes as $key => $val) {
			$html .= '<option value="' . $key . '" ' . (($key == $avatars) ? 'selected="selected"' : '') . '>' . $val . '</option>';
		}
		$html .= '</select>';
		$html .= '</p>';

		echo $html;
	}

	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['count'] = strip_tags($new_instance['count']);
		$instance['autorefresh'] = strip_tags($new_instance['autorefresh']);
		$instance['avatars'] = strip_tags($new_instance['avatars']);

		return $instance;
	}

	function widget($args, $instance) {
		extract($args);
		$title = apply_filters('widget_title', $instance['title']);
		$count = (int)$instance['count'];
		$count = $count ? $count : 1;
		$autorefresh = $instance['autorefresh'];
		$this->_avatars = esc_attr($instance['avatars']);

		echo $before_widget;
		if ($title) echo $before_title . $title . $after_title;

		echo '<div class="wdqs_widget_status_root">';
		echo '<ul class="wdqs_widget_status">';
		echo $this->_prepare_post_list_items($count);
		echo '</ul>';
		if ($autorefresh) {
			$rate = (int)($autorefresh * 1000);
			echo "<input type='hidden' class='wdqs_widget_count' value='{$count}' />";
			echo "<input type='hidden' class='wdqs_widget_autorefresh' value='{$rate}' />";
		}
		if (!defined('WDQS_WIDGET_STATUS_JAVASCRIPT_INCLUDED')) {
			$this->inject_script_dependencies();
			define('WDQS_WIDGET_STATUS_JAVASCRIPT_INCLUDED', true);
		}
		echo '</div>';

		echo $after_widget;
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