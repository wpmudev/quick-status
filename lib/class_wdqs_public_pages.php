<?php
/**
 * Handles all Admin access functionality.
 */
class Wdqs_PublicPages {

	var $data;
	var $_link_type = 'link';

	function Wdqs_PublicPages () { $this->__construct(); }

	function __construct () {
		$this->data = new Wdqs_Options;
	}

	/**
	 * Main entry point.
	 *
	 * @static
	 */
	function serve () {
		$me = new Wdqs_PublicPages;
		$me->add_hooks();
	}


	function js_load_scripts () {
		if (!$this->_check_permissions()) return false;
		wp_enqueue_script('jquery');
		wp_enqueue_script( 'media-upload' );
		add_thickbox();

		wp_enqueue_script('wdqs_widget', WDQS_PLUGIN_URL . '/js/widget.js');
		wp_localize_script('wdqs_widget', 'l10nWdqs', array(
			'no_thumbnail' => __('No thumbnail', 'wdqs'),
			'of' => __('of', 'wdqs'),
			'images_found' => __('images found', 'wdqs'),
			'use_default_title' => __('Use default title', 'wdqs'),
			'use_this_title' => __('Use this title', 'wdqs'),
			'post_title' => __('Post title', 'wdqs'),
			'height' => __('Height', 'wdqs'),
			'width' => __('Width', 'wdqs'),
			'leave_empty_for_defaults' => __('Leave these boxes empty for defaults', 'wdqs'),
		));
		echo '<script type="text/javascript">var _wdqs_ajaxurl="' . admin_url('admin-ajax.php') . '";</script>';
		echo '<script type="text/javascript">var _wdqs_adminurl="' . admin_url() . '";</script>';
	}

	function css_load_styles () {
		if (!current_theme_supports('wdqs')) {
			wp_enqueue_style('wdqs', WDQS_PLUGIN_URL . '/css/wdqs.css');
		}
		if (!$this->_check_permissions()) return false;
		wp_enqueue_style('thickbox');
		wp_enqueue_style('wdqs_widget', WDQS_PLUGIN_URL . '/css/widget.css');
		wp_enqueue_style('wdqs_widget-front', WDQS_PLUGIN_URL . '/css/widget-front.css');
	}

	function status_widget () {
		if (!$this->_check_permissions()) return false;
		if (defined('WDQS_BOX_CREATED')) return false; // Already added
		echo "<div>";
		include(WDQS_PLUGIN_BASE_DIR . '/lib/forms/dashboard_widget.php');
		echo "</div>";
		define ('WDQS_BOX_CREATED', true);
	}

	private function _check_permissions () {
		global $current_user;
		if (!current_user_can('publish_posts')) return false;
		if (!$current_user->ID) return false;

		$placement = $this->data->get('placement');
		$placement = $placement ? $placement : 'front_page';

		if ('front_page' == $placement && !is_front_page()) return false;

		return true;
	}


	function add_hooks () {
		// Step0: Register options and menu
		if (!$this->data->get('show_on_public_pages')) return false;
		add_action('wp_print_scripts', array($this, 'js_load_scripts'));
		add_action('wp_print_styles', array($this, 'css_load_styles'));

		$placement = $this->data->get('placement');
		$placement = $placement ? $placement : 'front_page';

		if ('manual' != $placement) {
			$hook = $this->data->get('use_hook');
			$hook = $hook ? $hook : 'loop_start';
			add_action($hook, array($this, 'status_widget'), 100);
		}
	}
}

/**
 * Manual placement function.
 * This can be used in theme files, e.g. like this:
 *
 * <code>
 *	if (function_exists('wdqs_quick_status')) wdqs_quick_status();
 * </code>
 */
function wdqs_quick_status () {
	$status = new Wdqs_PublicPages;
	$placement = $status->data->get('placement');
	if ('manual' == $placement) $status->status_widget();
}