(function ($) {
$(function () {

	
function reload_widget ($item) {
	var count = $item.find(".wdqs_widget_count").val(),
		instance_id = $item.find(".wdqs_widget_instance_id").val()
	;
	$.post(_wdqs_widget_ajaxurl, {
		action: "wdqs_list_posts",
		count: count,
		instance_id: instance_id
	}, function (data) {
		$item.find('.wdqs_widget_status').html(data.markup);
	});
}
	
function init_widgets() {
	$(".wdqs_widget_status_root").each(function () {
		var $me = $(this),
			interval = parseInt($me.find(".wdqs_widget_autorefresh").val(), 10)
		;
		if (!interval) return true;
		setInterval(function () {
			reload_widget($me);
		}, interval);
	});
}

init_widgets();

});
})(jQuery);