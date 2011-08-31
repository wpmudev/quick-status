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
		echo $this->_create_checkbox('show_on_public_pages');
	}
	function create_show_on_front_page_box () {
		echo $this->_create_checkbox('show_on_front_page');
	}
	function create_show_on_dashboard_box () {
		echo $this->_create_checkbox('show_on_dashboard');
	}

}