<?php
/**
 * Renders form elements for admin settings pages.
 */
class Wdqs_AdminFormRenderer {
	function _get_option () {
		return WP_NETWORK_ADMIN ? get_site_option('wdqs') : get_option('wdqs');
	}

	function _create_checkbox ($name) {
		$opt = $this->_get_option();
		$value = @$opt[$name];
		return
			"<input type='radio' name='wdqs[{$name}]' id='{$name}-yes' value='1' " . ((int)$value ? 'checked="checked" ' : '') . " /> " .
				"<label for='{$name}-yes'>" . __('Yes', 'wdqs') . "</label>" .
			'&nbsp;' .
			"<input type='radio' name='wdqs[{$name}]' id='{$name}-no' value='0' " . (!(int)$value ? 'checked="checked" ' : '') . " /> " .
				"<label for='{$name}-no'>" . __('No', 'wdqs') . "</label>" .
		"";
	}

	function _create_radiobox ($name, $value) {
		$opt = $this->_get_option();
		$checked = (@$opt[$name] == $value) ? true : false;
		return "<input type='radio' name='wdqs[{$name}]' id='{$name}-{$value}' value='{$value}' " . ($checked ? 'checked="checked" ' : '') . " /> ";
	}


	function create_show_on_public_pages_box () {
		$opts = $this->_get_option();
		$use_hook = @$opts['use_hook'] ? @$opts['use_hook'] : 'loop_start';
		$placement = @$opts['placement'] ? @$opts['placement'] : 'front_page';

		echo '<div>' .
			$this->_create_checkbox('show_on_public_pages') .
		'</div><br />';

		$checked = ('front_page' == $placement) ? 'checked="checked"' : '';
		echo
			'<div class="wdqs_public_options">' .
			"<input type='radio' id='wdqs_placement-front_page' name='wdqs[placement]' value='front_page' {$checked} />" .
			'<label for="wdqs_placement-front_page">' . __('Show on front page only', 'wdqs') . '</label>' .
			'<div><small>' . __('If you select this option, Status will be added to your front page only.', 'wdqs') . '</small></div>' .
		'</div><br />';

		$checked = ('all_pages' == $placement) ? 'checked="checked"' : '';
		echo
			'<div class="wdqs_public_options">' .
			"<input type='radio' id='wdqs_placement-all_pages' name='wdqs[placement]' value='all_pages' {$checked} />" .
			'<label for="wdqs_placement-all_pages">' . __('Show on all public pages', 'wdqs') . '</label>' .
			'<div><small>' . __('If you select this option, Status will be added to all public pages.', 'wdqs') . '</small></div>' .
		'</div><br />';

		$checked = ('use_hook' == $placement) ? 'checked="checked"' : '';
		$hook_disabled = ('use_hook' == $placement) ? '' : 'disabled="disabled"';
		echo
			'<div class="wdqs_public_options">' .
			"<input type='radio' id='wdqs_placement-use_hook' name='wdqs[placement]' value='use_hook' {$checked} />" .
			'<label for="wdqs_placement-use_hook">' . __('Use this hook', 'wdqs') . ':</label> ' .
			"<input type='text' name='wdqs[use_hook]' id='wdqs_placement-use_hook-target' size='32' value='{$use_hook}' {$hook_disabled} {$checked} />" .
			'<div><small>' . __('Advanced: if you select this option, Status will be bound to this hook.', 'wdqs') . '</small></div>' .
		'</div><br />';

		$checked = ('manual' == $placement) ? 'checked="checked"' : '';
		echo
			'<div class="wdqs_public_options">' .
			"<input type='radio' id='wdqs_placement-manual' name='wdqs[placement]' value='manual' {$checked} />" .
			'<label for="wdqs_placement-manual">' . __('Do not place it automatically, I will place it myself', 'wdqs') . '</label> ' .
			'<div><small>' . __('Advanced: if you select this option, you will have to use <code>wdqs_quick_status()</code> function in your theme files to place Status wherever you want.', 'wdqs') . '</small></div>' .
			'<div><small>' . __('Example usage: <code>if (function_exists("wdqs_quick_status")) wdqs_quick_status();</code>', 'wdqs') . '</small></div>' .
		'</div><br />';
	}
	function create_show_on_dashboard_box () {
		echo $this->_create_checkbox('show_on_dashboard');
	}

	function create_title_box () {
		$opts = $this->_get_option();
		$title = @$opts['default_title'] ? $opts['default_title'] : __('My quick %s post', 'wdqs');
		echo "<input type='text' class='widefat' id='default_title' name='wdqs[default_title]' value='{$title}' />";
		echo "<div><small>" . __('This is what will be used as fallback title for your Status posts by default', 'wdqs') . '</small></div>';
		echo "<div><small>" . __('Use <code>%s</code> anywhere in your title to insert the post type (i.e. status, link, image, video)', 'wdqs') . '</small></div>';
	}

	function create_post_format_box () {
		$theme_formats = get_theme_support('post-formats');
		$theme_formats = is_array($theme_formats) ? $theme_formats[0] : array();
		array_unshift($theme_formats, '');
		if (!current_theme_supports('post-formats') || !$theme_formats) {
			_e('<p>Your theme does not support post formats</p>', 'wdqs');
			return;
		}
		$opts = $this->_get_option();

		echo '<label for="post_format-link">' . __('Link post format:', 'wdqs') . '</label> ';
		echo '<select name="wdqs[post_format-link]" id="post_format-link">';
		foreach ($theme_formats as $format) {
			$selected = ($format == $opts['post_format-link']) ? "selected='selected'" : '';
			$name = ((int)$format >= 0) ? esc_html(get_post_format_string($format)) : __('Use default format', 'wdqs');
			echo "<option value='{$format}' {$selected}>{$name}</option>";
		}
		echo '</select>';
		echo '<div><small>' . __('This format will be applied to all your &quot;Link&quot; posts.', 'wdqs')  . '</small></div>';

		echo '<label for="post_format-image">' . __('Image post format:', 'wdqs') . '</label> ';
		echo '<select name="wdqs[post_format-image]" id="post_format-image">';
		foreach ($theme_formats as $format) {
			$selected = ($format == $opts['post_format-image']) ? "selected='selected'" : '';
			$name = ((int)$format >= 0) ? esc_html(get_post_format_string($format)) : __('Use default format', 'wdqs');
			echo "<option value='{$format}' {$selected}>{$name}</option>";
		}
		echo '</select>';
		echo '<div><small>' . __('This format will be applied to all your &quot;Image&quot; posts.', 'wdqs')  . '</small></div>';

		echo '<label for="post_format-video">' . __('Video post format:', 'wdqs') . '</label> ';
		echo '<select name="wdqs[post_format-video]" id="post_format-video">';
		foreach ($theme_formats as $format) {
			$selected = ($format == $opts['post_format-video']) ? "selected='selected'" : '';
			$name = ((int)$format >= 0) ? esc_html(get_post_format_string($format)) : __('Use default format', 'wdqs');
			echo "<option value='{$format}' {$selected}>{$name}</option>";
		}
		echo '</select>';
		echo '<div><small>' . __('This format will be applied to all your &quot;Video&quot; posts.', 'wdqs')  . '</small></div>';

		echo '<label for="post_format-status">' . __('Generic Status post format:', 'wdqs') . '</label> ';
		echo '<select name="wdqs[post_format-status]" id="post_format-status">';
		foreach ($theme_formats as $format) {
			$selected = ($format == $opts['post_format-status']) ? "selected='selected'" : '';
			$name = ((int)$format >= 0) ? esc_html(get_post_format_string($format)) : __('Use default format', 'wdqs');
			echo "<option value='{$format}' {$selected}>{$name}</option>";
		}
		echo '</select>';
		echo '<div><small>' . __('This format will be applied to all your generic &quot;Status&quot; posts.', 'wdqs')  . '</small></div>';
	}

	function create_post_category_box () {
		$categories = get_categories(array('type'=>'post', 'taxonomy'=>'category', 'hierarchical'=>1, 'hide_empty'=>0));
		$opts = $this->_get_option();

		echo '<label for="post_category-link">' . __('Link category:', 'wdqs') . '</label> ';
		echo '<select name="wdqs[post_category-link]" id="post_category-link">';
		echo "<option value=''>" . __('No category', 'wdqs') . "</option>";
		foreach ($categories as $category) {
			$selected = ($category->term_id == $opts['post_category-link']) ? "selected='selected'" : '';
			$name = esc_html($category->name);
			if ($category->parent) $name = "&#8212;&nbsp;{$name}";
			echo "<option value='{$category->term_id}' {$selected}>{$name}</option>";
		}
		echo '</select>';
		echo '<div><small>' . __('This category will be automatically added to all your &quot;Link&quot; posts.', 'wdqs')  . '</small></div>';

		echo '<label for="post_category-image">' . __('Image category:', 'wdqs') . '</label> ';
		echo '<select name="wdqs[post_category-image]" id="post_category-image">';
		echo "<option value=''>" . __('No category', 'wdqs') . "</option>";
		foreach ($categories as $category) {
			$selected = ($category->term_id == $opts['post_category-image']) ? "selected='selected'" : '';
			$name = esc_html($category->name);
			if ($category->parent) $name = "&#8212;&nbsp;{$name}";
			echo "<option value='{$category->term_id}' {$selected}>{$name}</option>";
		}
		echo '</select>';
		echo '<div><small>' . __('This category will be automatically added to all your &quot;Image&quot; posts.', 'wdqs')  . '</small></div>';

		echo '<label for="post_category-video">' . __('Video category:', 'wdqs') . '</label> ';
		echo '<select name="wdqs[post_category-video]" id="post_category-video">';
		echo "<option value=''>" . __('No category', 'wdqs') . "</option>";
		foreach ($categories as $category) {
			$selected = ($category->term_id == $opts['post_category-video']) ? "selected='selected'" : '';
			$name = esc_html($category->name);
			if ($category->parent) $name = "&#8212;&nbsp;{$name}";
			echo "<option value='{$category->term_id}' {$selected}>{$name}</option>";
		}
		echo '</select>';
		echo '<div><small>' . __('This category will be automatically added to all your &quot;Video&quot; posts.', 'wdqs')  . '</small></div>';

		echo '<label for="post_category-status">' . __('Generic Status category:', 'wdqs') . '</label> ';
		echo '<select name="wdqs[post_category-status]" id="post_category-status">';
		echo "<option value=''>" . __('No category', 'wdqs') . "</option>";
		foreach ($categories as $category) {
			$selected = ($category->term_id == $opts['post_category-status']) ? "selected='selected'" : '';
			$name = esc_html($category->name);
			if ($category->parent) $name = "&#8212;&nbsp;{$name}";
			echo "<option value='{$category->term_id}' {$selected}>{$name}</option>";
		}
		echo '</select>';
		echo '<div><small>' . __('This category will be automatically added to all your generic &quot;Status&quot; posts.', 'wdqs')  . '</small></div>';
	}

}