 jQuery(function() {
	jQuery( "#tabs" ).tabs().addClass( "ui-tabs-vertical ui-helper-clearfix" );
	jQuery( "#tabs li" ).removeClass( "ui-corner-top" ).addClass( "ui-corner-left" );
});
 jQuery(function() {
	jQuery( "#accordion" ).accordion({
		collapsible: true,
		active: false
	});
});