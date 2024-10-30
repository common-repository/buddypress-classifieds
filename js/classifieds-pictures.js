jQuery(document).ready(function() {
	var j = jQuery;
	
	var gallery = j('#classified-gallery');
	var new_content = classified_pictures_add_edit_links(gallery);
	gallery.html(new_content);
	
	j('#classified-pictures-upload').click(function() {
		classifieds_pictures_thickbox();
	});
	
	j('.edit-classified-picture').livequery('click',function(){
		classifieds_pictures_thickbox_edit_pic();
	});

	j('iframe#TB_iframeContent').livequery(function(){ 
	/*Thickbox is loaded*/
		console.log("thickboxLoaded");
	}, function() { 
	/*Thickbox is closed*/
		console.log("thickboxUnLoaded");
		
		var tab = j('.item-list-tabs li.current');
		var gallery = j('#classified-gallery');
		var cid = gallery.attr('rel');
		
		tab.addClass('loading');

		j.post( ajaxurl, {
			action: 'classified_gallery',
			'cookie': encodeURIComponent(document.cookie),
			'cid': cid
		},
		function(response)
		{
			var gallery=j('#classified-gallery');
			var old_content=gallery.html();
			var new_gallery=gallery.clone();

			response = response.substr(0, response.length-1);
			
			new_gallery.html(response);

			/*replace content : inject links for ajax favorite picture */

			tab.removeClass('loading');

			
			new_gallery = classified_pictures_add_edit_links(new_gallery);
			
			var new_content=new_gallery.html();

			if (old_content == new_content) return false;
			
			gallery.fadeIn(200).html(new_content);

		});
		
	});
	

	
});

function classified_pictures_add_edit_links(gallery) {

	var new_gallery = gallery.clone();

	var items = new_gallery.find('.gallery-item');


	items.each( function() {
		
		link = j(this).find('.edit-classified-picture');
		var favorite_link =  j('<p class="hide-if-no-js"><a href="#" class="edit-classified-picture">Edit</a></p>');

		if (!link.length)
			favorite_link.appendTo(j(this).find('dt:first'));

	
	});

	
	
	return new_gallery;

}


function classifieds_pictures_thickbox() {

		var cid=jQuery('#classified-gallery').attr('rel');

		j.post( ajaxurl, {
			action: 'classified_pictures_can_upload',
			'cookie': encodeURIComponent(document.cookie),
			'cid': cid
		},
	
		function(can_upload){

			if (can_upload)
				tb_show('', tb_path_to_uploader+'&type=image&TB_iframe=true&width=640&height=618');
		});
		
	return false;
}

function classifieds_pictures_thickbox_edit_pic() {

	tb_show('', tb_path_to_uploader+'&type=image&tab=gallery&TB_iframe=true&width=640&height=618');
	return false;
}


