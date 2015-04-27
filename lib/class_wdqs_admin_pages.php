<?php
/**
 * Handles all Admin access functionality.
 */
class Wdqs_AdminPages {

	//var $data;
	var $_link_type = 'status';
	var $_link_title = '';

	function Wdqs_AdminPages () { $this->__construct(); }

	function __construct () {
		$this->data = new Wdqs_Options;
	}

	/**
	 * Main entry point.
	 *
	 * @static
	 */
	function serve () {
		$me = new Wdqs_AdminPages;
		if ('widget' == $me->data->get('placement')) {
			require_once (WDQS_PLUGIN_BASE_DIR . '/lib/class_wdqs_widget_posting.php');
			add_action('widgets_init', create_function('', "register_widget('Wdqs_WidgetPosting');"));
		}
		$me->add_hooks();
	}

	/**
	 * Remote page retrieving routine.
	 *
	 * @param string Remote URL
	 * @return mixed Remote page as string, or (bool)false on failure
	 * @access private
	 */
	function get_page_contents ($url) {
		$response = wp_remote_get($url);
		if (is_wp_error($response)) return false;
		return $response['body'];
	}

	private function _check_permissions () {
		/*
		if ($this->data->get("contributors")) {
			return current_user_can("edit_posts");
		}
		*/
		return current_user_can(WDQS_PUBLISH_CAPABILITY);
	}

	function js_load_scripts () {
		if (!$this->_check_permissions()) return false;
		if (!$this->data->get('show_on_dashboard')) return false;
		if (defined('WP_NETWORK_ADMIN') && WP_NETWORK_ADMIN) return false;
		wp_enqueue_script('jquery');
		wp_enqueue_script('thickbox');
		wp_enqueue_script('media-upload');
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

		$options = apply_filters('wdqs-core-javascript_options', array(
			"ajax_url" => admin_url('admin-ajax.php'),
		));
		printf('<script type="text/javascript">var _wdqs=%s;</script>', json_encode($options));
	}

	function css_load_styles () {
		if (defined('WP_NETWORK_ADMIN') && WP_NETWORK_ADMIN) return false;
		wp_enqueue_style('wdqs', WDQS_PLUGIN_URL . '/css/wdqs.css');
		wp_enqueue_style('thickbox');
		wp_enqueue_style('wdqs_widget', WDQS_PLUGIN_URL . '/css/widget.css');
	}

	function register_settings () {
		$form = new Wdqs_AdminFormRenderer;

		register_setting('wdqs', 'wdqs');
		add_settings_section('wdqs_settings', __('Status settings', 'wdqs'), create_function('', ''), 'wdqs_options_page');
		add_settings_field('wdqs_public', __('Show on public pages', 'wdqs'), array($form, 'create_show_on_public_pages_box'), 'wdqs_options_page', 'wdqs_settings');
		add_settings_field('wdqs_back', __('Show on Dashboard', 'wdqs'), array($form, 'create_show_on_dashboard_box'), 'wdqs_options_page', 'wdqs_settings');
		add_settings_field('wdqs_contributors', __('Allow submitting for review', 'wdqs'), array($form, 'create_contributors_box'), 'wdqs_options_page', 'wdqs_settings');
		//add_settings_field('wdqs_allow_cap_override', __('Allow capability overrides', 'wdqs'), array($form, 'create_cap_override_box'), 'wdqs_options_page', 'wdqs_settings');

		add_settings_section('wdqs_post', __('Status post settings', 'wdqs'), create_function('', ''), 'wdqs_options_page');
		add_settings_field('wdqs_title', __('Default title', 'wdqs'), array($form, 'create_title_box'), 'wdqs_options_page', 'wdqs_post');
		add_settings_field('wdqs_format', __('Post format', 'wdqs'), array($form, 'create_post_format_box'), 'wdqs_options_page', 'wdqs_post');
		add_settings_field('wdqs_category', __('Post categories', 'wdqs'), array($form, 'create_post_category_box'), 'wdqs_options_page', 'wdqs_post');

		add_settings_section('wdqs_extra', __('Extras', 'wdqs'), create_function('', ''), 'wdqs_options_page');
		add_settings_field('wdqs_external', __('External links', 'wdqs'), array($form, 'create_externals_box'), 'wdqs_options_page', 'wdqs_extra');
		add_settings_field('wdqs_html5_video', __('Simple HTML5 video support', 'wdqs'), array($form, 'create_html5_video_box'), 'wdqs_options_page', 'wdqs_extra');
		add_settings_field('wdqs_download', __('Download external images', 'wdqs'), array($form, 'create_download_box'), 'wdqs_options_page', 'wdqs_extra');
	}

	function create_admin_menu_entry () {
		$perms = (defined('WP_NETWORK_ADMIN') && WP_NETWORK_ADMIN) ? 'manage_network_options' : 'manage_options';
		$page = (defined('WP_NETWORK_ADMIN') && WP_NETWORK_ADMIN) ? 'settings.php' : 'options-general.php';
		if (!empty($_POST) && isset($_POST['option_page']) && current_user_can($perms)) {
			$changed = false;
			$update = (defined('WP_NETWORK_ADMIN') && WP_NETWORK_ADMIN) ? 'update_site_option' : 'update_option';
			if(!empty($_POST['option_page']) && 'wdqs' == $_POST['option_page']) {
				$update('wdqs', stripslashes_deep($_POST['wdqs']));
				$changed = true;
			}

			if ($changed) {
				$goback = esc_url_raw(add_query_arg('settings-updated', 'true',  wp_get_referer()));
				wp_redirect($goback);
				die;
			}
		}
		add_submenu_page($page, __('Status', 'wdqs'), __('Status', 'wdqs'), $perms, 'wdqs', array($this, 'create_admin_page'));
	}

	function create_admin_page () {
		include(WDQS_PLUGIN_BASE_DIR . '/lib/forms/plugin_settings.php');
	}

	function status_dashboard_widget () {
		$status = false;
		include(WDQS_PLUGIN_BASE_DIR . '/lib/forms/dashboard_widget.php');
	}

	function add_status_dashboard_widget () {
		// We can alternatively use "edit_posts"
		//if (!current_user_can('publish_posts')) return false;
		if (!$this->_check_permissions()) return false;
		if ($this->data->get('show_on_dashboard')) {
			wp_add_dashboard_widget('wdqs_dashboard_status_widget', __("Status", 'wdqs'), array($this, 'status_dashboard_widget'));
		}
	}

	/**
	 * This is where the extracted link is processed, and
	 * reponse/storage HTML is generated.
	 */
	function generate_link_preview ($link, $data=false, $is_post=false) {
		$preview = false;
		$image = $no_image = $height = $width = $link_title = $link_text = false;
		if (is_array($data)) {
			extract($data);
		} /*else {
			$image = $no_image = $height = $width = false;
		}*/

		// Is it a video/oEmbed?
		if (!class_exists('WP_oEmbed')) require_once(ABSPATH . '/wp-includes/class-oembed.php');
		$wp_oembed = new WP_oEmbed();
		foreach(array_keys($wp_oembed->providers) as $rx) {
			if (!@preg_match($rx, $link)) continue;
			$args = array();
			if ($height) $args['height'] = $height;
			if ($width) $args['width'] = $width;
			$preview = wp_oembed_get($link, $args);
		}
		if ($preview) {
			$this->_link_type = 'video';
			return "<div class='wdqs wdqs_embed'>{$preview}</div>";
		}

		// Is it an image?
		if (
			// a) Direct link
			preg_match('/\.(png|gif|jpg|jpeg)$/i', $link)
			||
			// b) Dynamic image
			@getimagesize($link)
		) {
			$this->_link_type = 'image';
			$height = $height ? "height='{$height}'" : '';
			$width = $width ? "width='{$width}'" : '';
			$link = apply_filters('wdqs-image-img_src', $link);
			return "<div class='wdqs wdqs_image'><img src='{$link}' {$height} {$width} /></div>";
		}

		// We're still here, and it's
		// most likely we're dealing with a link to a page.
		// Parse it, then.
		$page = $this->get_page_contents($link);
		if (!class_exists('simple_html_dom_node')) require_once(WDQS_PLUGIN_BASE_DIR . '/lib/external/simple_html_dom.php');
		$html = str_get_html($page);
		$str = $html->find('text');

		if (!$str) return $link;

		$this->_link_type = 'link';

		if (!$image) {
			$images = array();
			$image_els = $html->find('img');
			foreach ($image_els as $el) {
				if ($el->width > 100 && $el->height > 1) // Disregard spacers
					$images[] = $el->src;
			}
			$og_image = $html->find('meta[property=og:image]', 0);
			if ($og_image) array_unshift($images, $og_image->content); //$images[] = $og_image->content;

			if ($is_post) {
				$image = current($images);
				$images = false;
			}
		}
		if ($image && $is_post) {
			$image = apply_filters('wdqs-link-img_src', $image);
		}
		if ($no_image) {
			$images = $image = false;
		}

		if (!$link_title) {
			$og_title = $html->find('meta[property=og:title]', 0);
			$title = $html->find('title', 0);
			$title = $og_title ? $og_title->content : $title->plaintext;
		} else $title = $link_title;
		$this->_link_title = $title ? $title : $this->_get_default_title();

		if (!$link_text) {
			$p = $html->find('p', 0);
			$og_desc = $html->find('meta[property=og:description]', 0);
			$meta_desc = $html->find('meta[name=description]', 0);
			$meta = $og_desc ? $og_desc : $meta_desc;
			$text = $meta ? $meta->content : $p->plaintext;
		} else $text = $link_text;

		$rel = $this->data->get('external_nofollow') ? 'rel="nofollow"' : '';
		$extra_link_attributes = apply_filters('wdqs-link-extra_attributes', "{$rel}");

		$template_file = locate_template('wdqs-link_preview.php');
		$template_file = is_file($template_file) ? $template_file : WDQS_PLUGIN_BASE_DIR . '/lib/forms/link_preview.php';
		ob_start();
		include($template_file);
		$preview = ob_get_contents();
		ob_end_clean();
		return $preview;
	}

	/**
	 * Here we extract the link and pass it on for further processing,
	 * then replace it with the results.
	 */
	function generate_preview_html ($txt, $data=false, $is_post=false) {
		$link = false;
		if (preg_match('/\s+/', $txt)) {
			$parts = preg_split('/\s+/', trim($txt));
			foreach ($parts as $part) {
				if (preg_match('/^https?:/', $part)) {
					$link = $part; break;
				}
			}
		} else {
			$link = preg_match('/^https?:/', trim($txt)) ? trim($txt) : false;
		}

		//if (!$link && !$is_post) return $is_post ? $txt : false;
		if (!$link && !$is_post) {
			$this->_link_type = 'generic';
			return $txt;
		}
		if (!preg_match('!^https?:!', $link)) return $is_post ? $txt : false;

		$txt = preg_replace('!' . preg_quote($link) . '!', '###WDQS_LINK###', $txt);
		if (!current_user_can('unfiltered_html')) {
			$txt = wp_filter_post_kses($txt);
		}
		$txt = nl2br($txt);
		$preview = preg_replace('!###WDQS_LINK###!', $this->generate_link_preview($link, $data, $is_post), $txt);

		return $preview;
	}

	private function _get_default_title () {
		$title = $this->data->get('default_title');
		$title = $title ? $title : __('My quick %s post', 'wdqs');
		return sprintf($title, $this->_link_type);
	}

	function create_post ($data) {
		if (!$this->_check_permissions()) return false;
		global $user_ID;
		$send = array(
			'image' => $data['thumbnail'],
			'no_image' => (int)$data['no_thumbnail'],
			'height' => (int)$data['height'],
			'width' => (int)$data['width'],
			'link_title' => @$data['link_title'],
			'link_text' => @$data['link_text'],
		);
		$text = $this->generate_preview_html($data['data'], $send, true);
		$title = @$data['title'] ? $data['title'] : $this->_get_default_title();
		$category = $this->data->get('post_category-' . $this->_link_type);
		$post = array (
			'post_title' => $title,
			'post_content' => $text,
			'post_date' => current_time('mysql'),
			'post_status' => @$_POST['is_draft'] ? 'draft' : (current_user_can("publish_posts") ? 'publish' : 'pending'),
			'post_author' => $user_ID,
			'post_category' => array($category),
		);

		$format = $this->data->get('post_format-' . $this->_link_type);


		$post_id = wp_insert_post($post);

		if ($post_id) {
			set_post_format($post_id, $format);
			update_post_meta($post_id, 'wdqs_type', $this->_link_type);
			update_post_meta($post_id, 'wdqs_posted', time());
			$post = get_post($post_id);

			// Alright, now let's check if we're equipped to set the image as featured
			if ($this->data->get('download_images-to_media_library') && $this->data->get('download_images-featured_image') && class_exists('Wdqs_ImageDownloader')) {
				$image = apply_filters('wdqs-link-img_src', $data['thumbnail']);
				if (!empty($image) && Wdqs_ImageDownloader::is_local_image($image)) {
					$query = new WP_Query(array(
						'post_type' => 'attachment',
						'post_status' => 'any',
						'meta_query' => array(array(
							'key' => '_wp_attached_file',
							'value' => basename($image),
							'compare' => 'LIKE'
						)),
					));
					$attachment = !empty($query->posts[0]) ? $query->posts[0] : false;
					$featured_image_id = false;
					if ($attachment && !empty($attachment->ID)) {
						$url = get_post_meta($attachment->ID, '_wp_attached_file', true);
						if (preg_match('/' . preg_quote(basename($image), '/') . '$/i', $url)) $featured_image_id = $attachment->ID;
					}
					// Okay, so we're good to go
					if ($featured_image_id) set_post_thumbnail($post_id, $featured_image_id);
				}
			}

			// Prepare old post for UFb triggering
			// Walkaround for 1.3 UFb fix
			$old_post = clone $post;
			$old_post->post_status = 'draft';
			// Make sure we trigger this hook, as that's what UFb uses
			do_action('post_updated', $post_id, $post, $old_post);
		}

		return $post_id;
	}

	function json_generate_preview () {
		$_POST = stripslashes_deep($_POST);
		$data = array(
			'height' => @$_POST['height'],
			'width' => @$_POST['width'],
		);
		$preview = $this->generate_preview_html(@$_POST['text'], $data);
		$title = (isset($_POST['title']) && $_POST['title']) ? $_POST['title'] : $this->_link_title;
		$title = $title ? $title : $this->_get_default_title();

		$status = strlen($preview);
		header('Content-type: application/json');
		echo json_encode(array(
			'status' => $status,
			'preview' => array(
				'markup' => $preview,
				'type' => $this->_link_type,
				'title' => $title,
				'height' => @$_POST['height'],
				'width' => @$_POST['width'],
			),
		));
		exit();
	}

	function json_post () {
		$status = $this->create_post($_POST);
		ob_start();
		include(WDQS_PLUGIN_BASE_DIR . '/lib/forms/dashboard_widget.php');
		$form = ob_get_contents();
		ob_end_clean();
		header('Content-type: application/json');
		echo json_encode(array(
			'status' => $status,
			'form' => $form,
		));
		exit();
	}

	function inject_settings_link ($links) {
		$settings = '<a href="' .
			(is_network_admin() ? network_admin_url('settings.php?page=wdqs') : admin_url('admin.php?page=wdqs')) .
			'">' . __('Settings', 'wdqs') . 
		'</a>';
		array_unshift($links, $settings);
		return $links;
	}

	function html5_video_support_javascript ($options) {
		$html5_video = $this->data->get('html5_video');

		$html5_video_types = explode(
			',', 
			(@$html5_video['video_types'] ? $html5_video['video_types'] : 'webm, mp4, ogg, ogv')
		);
		$html5_video_types = is_array($html5_video_types) 
			? array_map('trim', $html5_video_types)
			: array()
		;

		$options['html5_video'] = array(
			"allowed" => (int)@$html5_video['use_html5_video'],
			"video_unavailable" => @$html5_video['unavailable'] ? $html5_video['unavailable'] : __('Not supported', 'wdqs'),
			"video_types" => $html5_video_types,
		);
		return $options;
	}

	function oembed_providers_list ($options) {
		if (!class_exists('WP_oEmbed')) require_once(ABSPATH . '/wp-includes/class-oembed.php');
		$wp_oembed = new WP_oEmbed();
		$provider_rx = array();
		foreach(array_keys($wp_oembed->providers) as $rx) $provider_rx[] = preg_replace('/#i?/', '', $rx);
		$options['oembed']['providers'] = $provider_rx;
		return $options;
	}

	function add_hooks () {
		// Step0: Register options and menu
		add_action('admin_init', array($this, 'register_settings'));
		add_action('admin_menu', array($this, 'create_admin_menu_entry'));
		add_action('network_admin_menu', array($this, 'create_admin_menu_entry'));
		
		add_filter('plugin_action_links_' . WDQS_PLUGIN_CORE_BASENAME, array($this, 'inject_settings_link'));
		add_filter('network_admin_plugin_action_links_' . WDQS_PLUGIN_CORE_BASENAME, array($this, 'inject_settings_link'));

		add_action('admin_print_scripts-index.php', array($this, 'js_load_scripts'));
		add_action('admin_print_styles-index.php', array($this, 'css_load_styles'));

		add_action('wp_dashboard_setup', array($this, 'add_status_dashboard_widget'));

		add_action('wp_ajax_wdqs_generate_preview', array($this, 'json_generate_preview'));
		add_action('wp_ajax_wdqs_post', array($this, 'json_post'));

		// Internal
		add_filter('wdqs-core-javascript_options', array($this, 'html5_video_support_javascript'), 9);
		add_filter('wdqs-core-javascript_options', array($this, 'oembed_providers_list'), 9);
	}
}