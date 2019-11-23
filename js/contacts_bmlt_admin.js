jQuery(document).ready(function($) {
	$("#contacts_bmlt_accordion").accordion({
		heightStyle: "content",
		active: false,
		collapsible: true
	});
	$(".contacts_bmlt_service_body_select").chosen({
		inherit_select_classes: true,
		width: "50%"
	});
});
