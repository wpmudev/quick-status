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

	private $_word_limits = array();

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
		$array = array_map('strval', range(0, 55, 5));
		$this->_word_limits = array_combine($array, $array);
		$this->_word_limits[0] = __('No limit', 'wdqs');
	}

	function form ($instance) {
		$title = esc_attr($instance['title']);
		$count = esc_attr($instance['count']);
		$autorefresh = esc_attr($instance['autorefresh']);
		$avatars = esc_attr($instance['avatars']);
		$date_checked = isset($instance['date']) && (int)$instance['date'] ? 'checked="checked"' : false;
		$link_avatars_checked = isset($instance['link_avatars']) && (int)$instance['link_avatars'] ? 'checked="checked"' : false;
		$external_avatars_link_checked = isset($instance['external_avatars_link']) && (int)$instance['external_avatars_link'] ? 'checked="checked"' : false;
		$word_limit = esc_attr($instance['word_limit']);

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
			$html .= '<option value="' . $key . '" ' . (($key == $avatars) ? 'selected="selected"' : '') . '>' . $val . '&nbsp;</option>';
		}
		$html .= '</select>';
		
		$html .= '<br />';
		$html .= '<input type="checkbox" name="' . $this->get_field_name('link_avatars') . '" id="' . $this->get_field_id('link_avatars') . '" value="1" ' . $link_avatars_checked . '"/>&nbsp;';
		$html .= '<label for="' . $this->get_field_id('link_avatars') . '">' . __('Link author avatars to their author/profile pages', 'wdqs') . '</label>';
		
		$html .= '<br />';
		$html .= '<input type="checkbox" name="' . $this->get_field_name('external_avatars_link') . '" id="' . $this->get_field_id('external_avatars_link') . '" value="1" ' . $external_avatars_link_checked . '"/>&nbsp;';
		$html .= '<label for="' . $this->get_field_id('external_avatars_link') . '">' . __('Allow external author links', 'wdqs') . '</label>';
		$html .= '</p>';

		$html .= '<p>';
		$html .= '<input type="checkbox" name="' . $this->get_field_name('date') . '" id="' . $this->get_field_id('date') . '" value="1" ' . $date_checked . '"/>&nbsp;';
		$html .= '<label for="' . $this->get_field_id('date') . '">' . __('Show date', 'wdqs') . '</label>';
		$html .= '</p>';
		
		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id('word_limit') . '">' . __('Limit my excerpts to this many words:', 'wdqs') . '</label>';
		$html .= '<select name="' . $this->get_field_name('word_limit') . '" id="' . $this->get_field_id('word_limit') . '">';
		foreach ($this->_word_limits as $key => $val) {
			$html .= '<option value="' . $key . '" ' . (($key == $word_limit) ? 'selected="selected"' : '') . '>' . $val . '&nbsp;</option>';
		}
		$html .= '</select>';
		$html .= '<div><small>' . __('Only excerpts estimated to be longer then the selected value will be processed.', 'wdqs') . '</small></div>';
		$html .= '<div><small>' . __('Please, note that the processed excerpts wil lose any HTML formatting.', 'wdqs') . '</small></div>';
		$html .= '</p>';

		echo $html;
	}

	function update ($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['count'] = strip_tags($new_instance['count']);
		$instance['autorefresh'] = strip_tags($new_instance['autorefresh']);
		$instance['avatars'] = strip_tags($new_instance['avatars']);
		$instance['link_avatars'] = strip_tags($new_instance['link_avatars']);
		$instance['external_avatars_link'] = strip_tags($new_instance['external_avatars_link']);
		$instance['date'] = strip_tags($new_instance['date']);
		$instance['word_limit'] = strip_tags($new_instance['word_limit']);

		return $instance;
	}

	function widget ($args, $instance) {
		extract($args);
		$title = apply_filters('widget_title', $instance['title']);
		$count = (int)$instance['count'];
		$count = $count ? $count : 1;
		$autorefresh = $instance['autorefresh'];
		$this->_avatars = esc_attr($instance['avatars']);
		$this->_link_avatars = esc_attr($instance['link_avatars']);
		$this->_external_avatars_link = esc_attr($instance['external_avatars_link']);
		$this->_date = esc_attr($instance['date']);
		$this->_word_count_limit = (int)$instance['word_limit'];

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
				'<div class="wdqs_widget_status_body">' . $this->_prepare_post_list_item_content($post) . '</div>' .
				$this->_get_post_meta($post) .
			'</li>';
			$out .= $item;
		}
		return $out;
	}

	private function _prepare_post_list_item_content ($post) {
		$out = $post->post_content;
		$count = (int)$this->_word_count_limit;
		if ($this->_word_count_limit) {
			$approx_word_count = count(preg_split('/\s/um', wp_strip_all_tags($out), -1, PREG_SPLIT_NO_EMPTY));
			$out = $approx_word_count >= $this->_word_count_limit
				? wp_trim_words($out, $this->_word_count_limit)
				: $out
			;
		}
		return apply_filters('wdqs-widget-post_content', $out);
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
		if ($this->_link_avatars) {
			$url = apply_filters('the_author', $user->display_name);
			if ($this->_external_avatars_link && !empty($user->user_url)) {
				$url = $user->user_url;
			}
			$url = apply_filters('wdqs-widget-avatar_link_url', $url, $user, $post);
			$link_attributes = apply_filters('wdqs-widget-link_attributes', array("href" => $url), $user, $post);
			$link_attributes['href'] = !empty($link_attributes['href']) ? $link_attributes['href'] : $url;
			$attributes = array();
			foreach ($link_attributes as $attrib => $value) {
				$attributes[] = preg_replace('/[^-_a-z0-9.]/i', '', $attrib) . '="' . esc_attr($value) . '"';
			}
			$attributes = join(" ", $attributes);
			$avatar = "<a {$attributes}>{$avatar}</a>";
		}
		$avatar = apply_filters('wdqs-widget-avatar', $avatar, $user, $post);
		return '<div class="wdqs-author_avatar ' . esc_attr($key) . '" title="' . esc_attr($name) . '">' . $avatar . '</div>';
	}

	private function _get_post_meta ($post) {
		if ($this->_date) {
			$date_format = apply_filters('wdqs-widget-post_meta-date_format',
				(defined('WDQS_WIDGET_DATE_FORMAT') && WDQS_WIDGET_DATE_FORMAT
					? WDQS_WIDGET_DATE_FORMAT
					: get_option("date_format") . ' ' . get_option("time_format")
			));
			$date = mysql2date($date_format, $post->post_date);
		}

		return '<div class="wdqs-post_meta">' . 
			apply_filters('wdqs-post_meta', '<div class="wdqs-post_meta-date">' . $date . '</div>', $post) .
		'</div>';
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