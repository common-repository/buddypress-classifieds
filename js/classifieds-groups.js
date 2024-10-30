jQuery(document).ready( function() {
	var j = jQuery;
	/**** Buttons ****************************************************/
	j(".classified-button.add-group a,.classified-button.remove-group a").live('click',
		function() {

			j(this).addClass('loading');
			
			var gid = j(this).parent().attr('id');
			gid = gid.split('-');
			gid = gid[1];
			
			var cid = j(this).parent().attr('rel');
			cid = cid.split('-');
			cid = cid[1];

			var nonce = j(this).attr('href');
			nonce = nonce.split('?_wpnonce=');
			nonce = nonce[1].split('&');
			nonce = nonce[0];

			var thelink = j(this);

			j.post( ajaxurl, {
				action: 'group_button',
				'cookie': encodeURIComponent(document.cookie),
				'gid': gid,
				'cid': cid,
				'_wpnonce': nonce
			},
			function(response)
			{
				response = response.substr(0, response.length-1);

				var action = thelink.attr('rel');
				var parentdiv = thelink.parent();

				if ( action == 'add-group' ) {
					j(parentdiv).fadeOut(200,
						function() {
							parentdiv.removeClass('add-group');
							j(this).removeClass('loading');
							parentdiv.addClass('remove-group');
							parentdiv.fadeIn(200).html(response);
						}
					);

				} else if ( action == 'remove-group' ) {
					j(parentdiv).fadeOut(200,
						function() {
							parentdiv.removeClass('remove-group');
							parentdiv.removeClass('loading');
							parentdiv.addClass('add-group');
							parentdiv.fadeIn(200).html(response);
						}
					);
				}
			});
			return false;
		}
	);
});