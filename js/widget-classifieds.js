	function classifieds_widget_filter_request_extra_args(link) {
		var extras={};

		//action check
		var action_id=classifieds_get_action_id(link);
		if (action_id) {
			if (action_id.length)
				extras["action"] = action_id;
		}
		
		console.log(extras);
		
		//cats check
		var cats_ids=classifieds_get_cats_ids();
		if (action_id) {
			if (cats_ids.length)
				extras["cats"] = cats_ids;
		}

		var extras_json = j.toJSON(extras);

		j.cookie('bp-classifieds-extras',extras_json);
	}


jQuery(document).ready( function() {
	var j = jQuery;

	j('div#classifieds-list-options a').live('click',function(event) {
		classifieds_widget_filter_request_extra_args(j(this));
	});

	j("div#classifieds-list-options a").live('click',
		function() { 
			console.log('test');
			j('#ajax-loader-classifieds').toggle();

			j("div#classifieds-list-options a").removeClass("selected");
			j(this).addClass('selected');
			
			j.post( ajaxurl, {
				action: 'widget_classifieds_list',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': j("input#_wpnonce-classifieds").val(),
				'max_classifieds': j("input#classifieds_widget_max").val(),
				'filter': j(this).attr('id')
			},
			function(response)
			{	
				j('#ajax-loader-classifieds').toggle();
				classifieds_wiget_response(response);
			});
		
			return false;
		}
	);
});

function classifieds_wiget_response(response) {
	response = response.substr(0, response.length-1);
	response = response.split('[[SPLIT]]');

	if ( response[0] != "-1" ) {
		jQuery("ul#classifieds-list").fadeOut(200, 
			function() {
				jQuery("ul#classifieds-list").html(response[1]);
				jQuery("ul#classifieds-list").fadeIn(200);
			}
		);

	} else {					
		jQuery("ul#classifieds-list").fadeOut(200, 
			function() {
				var message = '<p>' + response[1] + '</p>';
				jQuery("ul#classifieds-list").html(message);
				jQuery("ul#classifieds-list").fadeIn(200);
			}
		);
	}
}