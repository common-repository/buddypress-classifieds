// AJAX Functions
jQuery(document).ready( function() {
	var j = jQuery;
	
	/**** Page Load Actions **********************/

	/* Classified filter and scope set. */
	bp_init_objects( [ 'classifieds' ] );

	/* Clear cookies on logout */
	j('a.logout').click( function() {
		j.cookie('bp-classifieds-scope', null );
		j.cookie('bp-classifieds-filter', null );
		j.cookie('bp-classifieds-extras', null );
	});
		
	/**** Directory Search ****************************************************/


	var main_form = j("#classifieds-directory-form");
	/*CATEGORIES*/
	var cat_div = j('#browse-categories');
	var advanced_check_div = j('span#classifieds_advanced_search');
	var main_check = j("input[name=classifieds_advanced_search]");
	var tree = j(this).find('.tree');
	var checkboxes = tree.find("input[type='checkbox']");
	var submit_btn = j('#classifieds_search_submit');
	
	/*INIT*/
	classifieds_cats_init_main_check();
	classifieds_cats_color_check();
	/*disable|enable cats at loading*/
	function classifieds_cats_init_main_check(){
		if ((main_check).is(':checked')) {
			checkboxes.removeAttr('disabled');
			main_form.addClass('advanced_search');
		}else {
			checkboxes.attr('disabled', true);
			main_form.removeClass('advanced_search');
		}
	}
	
	/*ALL/NONE buttons*/
	j('#browse-category-check-all').click( function(event) {
		checkboxes.attr('checked',true);
		classifieds_cats_color_check();
	});
	j('#browse-category-uncheck-all').click( function(event) {
		checkboxes.removeAttr('checked');
		classifieds_cats_color_check();
	});
	checkboxes.click( function(event) {
		classifieds_cats_color_check();
	});

	/*check if categories are active*/
	function classifieds_cats_color_check() {
		if ((tree.find("input:checked").length) && ((main_check).is(':checked'))) {
			cat_div.addClass('active');
		}else {
			cat_div.removeClass('active');
		}
	}
	
	/*get current tab action*/
	function classifieds_get_action_id(tab) {
	
		var css_id = tab.attr('id').split( '-' );
		var scope = css_id[1];
		var action_check = scope.split('action');
		if (action_check[1]) {
			return action_check[1];
		}

	
	}
	
	/*returns checked categories array*/
	function classifieds_get_cats_ids() {
	
		var cat_div = j('#browse-categories');
		var main_check = j("input[name=classifieds_advanced_search]");
		var tree = cat_div.find('.tree');
		var checkboxes = tree.find("input[type='checkbox']");
		var checkboxes_checked = tree.find("input[type='checkbox']:checked");

		if (!(main_check).is(':checked')) return false;
		if (!checkboxes_checked.length) return false;
		
		if (checkboxes_checked.length==checkboxes.length) return false;

		var ids=new Array();
		var checked_boxes = tree.find("input[type='checkbox']:checked");
		jQuery.each(checked_boxes, function(i, val) {
		  var id=j(this).val();
		  ids.push(id);
		});

		return ids;
	}

	/*color categories on click*/
	advanced_check_div.click( function(event) {
		classifieds_cats_init_main_check();
		classifieds_cats_color_check();
	});	

	

	
	j('#classifieds_search_submit').not('.no-ajax').click( function(event) {
		var target = j(event.target);

		if ( target.attr('type') == 'submit' ) {
			var css_id = j('div.item-list-tabs li.selected').attr('id').split( '-' );
			var object = css_id[0];
			var scope = css_id[1];
			
			var extras={};
			
			//action check

			var action_check = scope.split('action');
			if (action_check[1]) {
				extras["action_tag"] = action_check[1];
				scope=null;
			}

			
			//cats check
			var cats_ids=classifieds_get_cats_ids();
			if (cats_ids.length)
				extras["cats"] = cats_ids;


			var extras_json = j.toJSON(extras);

			bp_filter_request( object, j.cookie('bp-' + object + '-filter'), j.cookie('bp-' + object + '-scope') , 'div.' + object, target.parent().children('label').children('input').val(), 1,extras_json);
		}

		return false;
	});
	/**** Buttons ****************************************************/
	j("div.follow-button a").live('click',
		function() {
			j(this).parent().addClass('loading');
			var cid = j(this).attr('id');
			cid = cid.split('-');
			cid = cid[1];


			var nonce = j(this).attr('href');
			nonce = nonce.split('?_wpnonce=');
			nonce = nonce[1].split('&');
			nonce = nonce[0];

			var thelink = j(this);

			j.post( ajaxurl, {
				action: 'follow_button',
				'cookie': encodeURIComponent(document.cookie),
				'cid': cid,
				'_wpnonce': nonce
			},
			function(response)
			{
				response = response.substr(0, response.length-1);

				var action = thelink.attr('rel');
				var parentdiv = thelink.parent();

				if ( action == 'follow' ) {
					j(parentdiv).fadeOut(200,
						function() {
							parentdiv.removeClass('follow');
							parentdiv.removeClass('loading');
							parentdiv.addClass('unfollow');
							parentdiv.fadeIn(200).html(response);
						}
					);

				} else if ( action == 'unfollow' ) {
					j(parentdiv).fadeOut(200,
						function() {
							parentdiv.removeClass('unfollow');
							parentdiv.removeClass('loading');
							parentdiv.addClass('follow');
							parentdiv.fadeIn(200).html(response);
						}
					);
				}
			});
			return false;
		}
	);
	/**** Tabs and Filters ****************************************************/
	/* When a navigation tab is clicked - e.g. | All Classifieds | My Classifieds | */
	
	//default extra_vars for ajax query
	function classifieds_filter_request_extra_args(tab) {
		var extras={};

		//action check
		var action_id=classifieds_get_action_id(tab);
		if (action_id) {
			if (action_id.length)
				extras["action_tag"] = action_id;
		}
		
		//cats check
		var cats_ids=classifieds_get_cats_ids();
		if (cats_ids) {
			if (cats_ids.length) {
				extras["cats"] = cats_ids;
			}
		}else {
			extras["cats"] = false;
		}
		
		var extras_json = j.toJSON(extras);

		j.cookie('bp-classifieds-extras',extras_json, {path:'/'});
		
	}
	
	j('#classifieds-directory-form div.item-list-tabs li').click( function(event) {
		classifieds_filter_request_extra_args(j(this));
	});
	
	
	j("input#classifieds_search").keyup(
		function(e) {
			if ( e.which == 13 ) {
				j('.ajax-loader').toggle();
				

				j.post( ajaxurl, {
					action: 'classified_filter',
					'cookie': encodeURIComponent(document.cookie),
					'_wpnonce': j("input#_wpnonce_classified_filter").val(),

					'classified-filter-box': j("#classified-filter-box").val()
				},
				function(response)
				{
					response = response.substr( 0, response.length - 1 );

					j("div#classified-loop").fadeOut(200,
						function() {
							j('.ajax-loader').toggle();
							j("div#classified-loop").html(response);
							j("div#classified-loop").fadeIn(200);
						}
					);
				});

				return false;
			}
		}
	);
	


	
	/* When the status select box is changed re-query */
	j("#classifieds-status-links:not('.no-ajax') a").not('.selected').click( function(event) {
	
		var object = 'classifieds';
		var scope = 'personal';
		var status = j(this).attr('rel');
		var search_terms = null;
	
		var extras={
			"status": status
		    }
			
		var extras_json = j.toJSON(extras);

		bp_filter_request( object, null, scope, 'div.' + object, search_terms, 1,extras_json);

		return false;
	});


	j("form#search-classifieds-form").submit( function() {
			j('.ajax-loader').toggle();

			j("div#classifieds-list-options a.selected").removeClass("selected");
			j("#letter-list li a.selected").removeClass("selected");

			j.post( ajaxurl, {
				action: 'directory_classifieds',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': j("input#_wpnonce-classified-filter").val(),
				's': j("input#classifieds_search").val(),
				'page': 1
			},
			function(response)
			{
				response = response.substr(0, response.length-1);
				j("#classified-dir-list").fadeOut(200,
					function() {
						j('.ajax-loader').toggle();
						j("#classified-dir-list").html(response);
						j("#classified-dir-list").fadeIn(200);
					}
				);
			});

			return false;
		}
	);
	


	j("form#classified-search-form").submit(
		function() {
			return false;
		}
	);

	j(".classifieds div#classified-dir-pag a").live('click',
		function() {
			j('.ajax-loader').toggle();

			var grpage = j(this).attr('href');
			grpage = grpage.split('=');

			j.post( ajaxurl, {
				action: 'classified_filter',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': j("input#_wpnonce_classified_filter").val(),
				'grpage': grpage[1],

				'classified-filter-box': j("#classified-filter-box").val()
			},
			function(response)
			{
				response = response.substr( 0, response.length - 1 );

				j("div#classified-loop").fadeOut(200,
					function() {
						j('.ajax-loader').toggle();
						j("div#classified-loop").html(response);
						j("div#classified-loop").fadeIn(200);
					}
				);
			});

			return false;
		}
	);


});

