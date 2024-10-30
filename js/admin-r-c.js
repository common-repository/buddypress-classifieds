jQuery(document).ready( function($) {
	//auto checks roles that have not been filled
	jQuery("form#bp-classifieds-r-c tr.mp_sep.default input").click(function () {
		
		var thisclass = jQuery(this).attr("class");

		if (jQuery(this).is(':checked')) {
			jQuery("form#bp-classifieds-r-c .no_data input."+thisclass).attr("checked",'true');
		}else {
			jQuery("form#bp-classifieds-r-c .no_data input."+thisclass).removeAttr("checked");
		}
	});
	
	/*
	jQuery("#bp-classifieds-r-c .mp_sep").not(':first').hide();
	jQuery("#classifieds-r-c-more-roles").click(function () {
		jQuery("#bp-classifieds-r-c .mp_sep").show();
		jQuery(this).hide();
		return false;
		
	});
	*/
	
	
});




