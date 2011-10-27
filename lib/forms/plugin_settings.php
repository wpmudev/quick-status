<div class="wrap">
	<h2><?php _e('Status settings', 'wdqs');?></h2>

<?php if (WP_NETWORK_ADMIN) { ?>
	<form action="settings.php" method="post">
<?php } else { ?>
	<form action="options.php" method="post">
<?php } ?>

	<?php settings_fields('wdqs'); ?>
	<?php do_settings_sections('wdqs_options_page'); ?>
	<p class="submit">
		<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" />
	</p>
	</form>

</div>

<script type="text/javascript">
(function ($) {
$(function () {

/*
function toggle_front_page () {
	if (!$("#show_on_public_pages-yes").is(":checked")) {
		$("#show_on_front_page-yes").attr("checked", false).attr("disabled", true);
		$("#show_on_front_page-no").attr("checked", true).attr("disabled", true);
	} else {
		$("#show_on_front_page-yes").attr("disabled", false);
		$("#show_on_front_page-no").attr("disabled", false);
	}
}

toggle_front_page();
$("#show_on_public_pages-yes").change(toggle_front_page);
$("#show_on_public_pages-no").change(toggle_front_page);
*/

function toggle_public_pages () {
	if ($("#show_on_public_pages-yes").is(":checked")) {
		$(".wdqs_public_options input:radio").each(function () {
			$(this).attr("disabled", false);
		});
	} else {
		$(".wdqs_public_options input:radio").each(function () {
			$(this).attr("disabled", true);
		});
	}
}
toggle_public_pages();
$("#show_on_public_pages-yes").change(toggle_public_pages);
$("#show_on_public_pages-no").change(toggle_public_pages);

function toggle_use_hook () {
	if ($("#wdqs_placement-use_hook").is(":checked")) {
		$("#wdqs_placement-use_hook-target").attr("disabled", false);
	} else {
		$("#wdqs_placement-use_hook-target").attr("disabled", true);
	}
}
toggle_use_hook();
$(".wdqs_public_options input:radio").change(toggle_use_hook);

});
})(jQuery);
</script>