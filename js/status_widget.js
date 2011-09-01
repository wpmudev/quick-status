(function ($) {
$(function () {

	
function reload_widget ($item) {
	var count = $item.find(".wdqs_widget_count").val();
	$.post(_wdqs_widget_ajaxurl, {"action": "wdqs_list_posts", "count": count}, function (data) {
		$item.find('.wdqs_widget_status').html(data.markup);
	});
}
	
function init_widgets() {
	$(".wdqs_widget_status_root").each(function () {
		var $me = $(this);
		var interval = parseInt($me.find(".wdqs_widget_autorefresh").val());
		if (!interval) return true;
		setInterval(function () {
			reload_widget($me);
		}, interval);
	});
}

init_widgets();

});
})(jQuery);