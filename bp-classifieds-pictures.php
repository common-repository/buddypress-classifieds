<?php

//TEMPLATE FUNCTIONS

function classified_pictures_gallery(){
	echo classified_pictures_get_gallery();
}
function classified_pictures_get_gallery($classified_id=false){
	global $bp;
	
	if (!$classified_id)
		$classified_id = $bp->classifieds->current_classified->ID;
		
	if ((!$classified_id) || (!is_numeric($classified_id)))return false;
	
	switch_to_blog($bp->classifieds->options['blog_id']);
	$gallery = do_shortcode('[gallery id="'.$classified_id.'" link="file"]');
	restore_current_blog();

    return $gallery;
}

function classifieds_pictures_uploader_css() { //ok well hooked
	wp_enqueue_style( 'bp-classifieds-pictures-uploader', BP_CLASSIFIEDS_PLUGIN_URL . '/css/classifieds-pictures.css');
}
function classifieds_pictures_css() {
	wp_enqueue_style('thickbox');	
}
function classifieds_enqueue_livequery() {
	wp_enqueue_script('jquery.livequery',BP_CLASSIFIEDS_PLUGIN_URL . '/js/jquery.livequery.js', array('jquery'),'1.0.3');
}


function classifieds_pictures_js() {
	wp_enqueue_script('classifieds-pictures',BP_CLASSIFIEDS_PLUGIN_URL . '/js/classifieds-pictures.js', array('jquery'),BP_CLASSIFIEDS_VERSION);
}

//EXTENSION CLASS

class Classified_Pictures_Extension extends BP_Classified_Extension {

	function classified_pictures_extension() {
		global $bp;

		$this->name = __( 'Classified Pictures', 'classified-pictures' );
		$this->slug = __( 'pictures', 'classified-pictures-slugs' );
		$this->nav_item_name = __( 'Pictures', 'classified-pictures' );

		$this->create_step_position = 13;
		$this->nav_item_position = 23;

		if ( !$this->classified_id = $bp->classified->new_classified_id )
			$this->classified_id = $bp->classifieds->current_classified->ID;

	}

	function create_screen() {
		global $bp;

		if ( !bp_is_classified_creation_step( $this->slug ) )
			return false;
		?>
		
		<h3><?php _e('Upload Pictures','classified-pictures');?></h3>
		<p>
		<?php printf(__('You can upload up to %d pictures.  You can choose which one will be the classified\'s thumbnail by clicking %s while editing a picture.','classified-pictures'),$bp->classifieds->options['pics_max'],'<em>'.__('Use as thumbnail').'</em>');?>
		</p>
		<?php //if (bp_classifieds_pictures_classified_can_upload()) {?>
		<div>
			<input id="classified-pictures-upload" type="button" value="Upload Image" />
		</div>
		<?php //} ?>
		<h3><?php _e('Current Pictures','classified-pictures');?></h3>
		<div id="classified-gallery" rel="<?php echo $bp->classifieds->current_classified->ID;?>">
		<?php classified_pictures_gallery();?>
		</div>
		<?php
		//locate_template( array( 'pictures/pictures-loop.php' ), true );
		wp_nonce_field( 'classifieds_create_save_' . $this->slug );

	}
	//needed but empty as ajax does the job
	function create_screen_save() {
		global $bp;

		check_admin_referer( 'classifieds_create_save_' . $this->slug );
		
		$cid = $this->classified_id;

		bp_classifieds_pictures_set_classified_thumb($cid);
	
	}


	function edit_screen() {
		global $bp;
		
		if ( !bp_is_classified_admin_screen( $this->slug ) )
			return false; ?>

		<h3><?php _e('Upload Pictures','classified-pictures');?></h3>
		<p>
		<?php printf(__('You can upload up to %d pictures.  You can choose which one will be the classified\'s thumbnail by clicking %s while editing a picture.','classified-pictures'),$bp->classifieds->options['pics_max'],'<em>'.__('Use as thumbnail').'</em>');?>
		</p>
		<?php //if (bp_classifieds_pictures_classified_can_upload()) {?>
		<div>
			<input id="classified-pictures-upload" type="button" value="Upload Image" />
		</div>
		<?php //} ?>
		<h3><?php _e('Current Pictures','classified-pictures');?></h3>
		<div id="classified-gallery" rel="<?php echo $bp->classifieds->current_classified->ID;?>">
		<?php classified_pictures_gallery();?>
		</div>
		<input type="submit" name="save" value="<?php _e( 'Save Changes', 'buddypress' ) ?> &rarr;" />
		<?php
		wp_nonce_field( 'classifieds_edit_save_'  . $this->slug );
	}
	//needed but empty as ajax does the job
	function edit_screen_save() {

		if ( !isset( $_POST['save'] ) )	return false;
		
		//TO FIX CHECK DO NOT WORK
		//check_admin_referer( 'classifieds_edit_save_'  . $this->slug );
		
		$cid = $this->classified_id;

		bp_classifieds_pictures_set_classified_thumb($cid);
	
	}
}

//TO FIX : DIRTY FN ?
function bp_is_classified_gallery_page() {
	global $bp;
	
	
	$url_action = $bp->action_variables[0];
	if (!$url_action)
		$url_action=$bp->current_action;
	//&& $bp->is_single_item

	
	if (( $bp->current_component == BP_CLASSIFIEDS_SLUG ) && $url_action==__( 'pictures', 'classified-pictures-slugs' ) )
		return true;

	return false;
}




function classifieds_pictures_gallery_enabled() {
	global $bp;
	
	return $bp->classifieds->options['pics_max'];
}


//PICTURE | menu
function classifieds_pictures_setup_nav() {
	global $bp;
	
	$cid = $bp->classifieds->current_classified->ID;
	
	if (!classifieds_pictures_gallery_enabled()) return false;
	
	if (!bp_classifieds_pictures_get_gallery_items_count($cid)) return false;

	$classified_link = $bp->root_domain . '/' . $bp->classifieds->slug . '/' . $bp->classifieds->current_classified->slug . '/';
	
	//TO FIX : check access
	bp_core_new_subnav_item( array( 'name' => __( 'Gallery', 'classifieds' ), 'slug' => __('pictures','classified-pictures-slugs'), 'parent_url' => $classified_link, 'parent_slug' => $bp->classifieds->slug, 'screen_function' => 'classifieds_pictures_picture_screen_classifieds', 'position' => 25, 'item_css_id' => 'classified-pictures', 'user_has_access'=>bp_classified_is_visible()) );
}

function classifieds_pictures_picture_screen_classifieds() {
	global $bp;
	

	if ( $bp->is_single_item )
		bp_core_load_template( apply_filters( 'bp_classifieds_pictures_template', 'classifieds/single/plugins' ) );
}

//PICTURE |classifieds display
function classifieds_pictures_picture_screen_classifieds_content() {
	
	if ( bp_is_classified_gallery_page() && bp_classified_is_visible() ) :

		if (!classifieds_pictures_gallery_enabled()) return false;

		echo classified_pictures_get_gallery($bp->classifieds->current_classified->ID);
		
	endif;
}


/*********************************************************************************/

function classifieds_pictures_init() {

	if (!classifieds_pictures_gallery_enabled()) return false;

	bp_register_classified_extension( 'Classified_Pictures_Extension' );
	
	add_theme_support( 'post-thumbnails', array( 'post' ) );
	add_filter( 'bp_core_fetch_avatar', 'bp_classifieds_fetch_avatar',10,2 );
	
	global $bp;

	
	//CLASSIFIED
	if ( $bp->current_component == BP_CLASSIFIEDS_SLUG) {
	
		// TO FIX : SHOULD USE THIS ? BUT DO NOT WORK : add_action( 'bp_setup_nav','classifieds_pictures_setup_nav');
		add_action( 'plugins_loaded','classifieds_pictures_setup_nav',11);
		add_action( 'bp_template_content','classifieds_pictures_picture_screen_classifieds_content');
	
		if (!bp_classifieds_user_can_upload()) return false;
		
		//TO FIX statement do not work
		//if ((bp_is_classified_creation_step(__( 'pictures', 'classified-pictures-slugs' ))) || (bp_is_classified_admin_screen(__( 'pictures', 'classified-pictures-slugs' )))) {

			add_action('wp_print_scripts', 'classifieds_pictures_js');
			add_action('wp_print_scripts', 'classifieds_enqueue_livequery');
			
			//TO FIX not included using hook wp_head
			wp_enqueue_script('thickbox');
			wp_enqueue_script('media-upload');
			add_action('wp_print_styles', 'classifieds_pictures_css');
			add_action('wp_footer', 'bp_classifieds_pictures_thickbox');
			
		//}
		

	}
	
	//THICKBOX | backend
	add_action('admin_head-media-upload-popup','bp_classifieds_pictures_tb_popup_head');
	add_action('admin_print_scripts-media-upload-popup','classifieds_enqueue_livequery');
	add_action('admin_print_styles-media-upload-popup','classifieds_pictures_uploader_css');
	add_action('admin_print_scripts-media-upload-popup','classifieds_tb_popup_scripts');
}

function classifieds_tb_popup_scripts() {
		wp_enqueue_script('classifieds-thickbox-upload',BP_CLASSIFIEDS_PLUGIN_URL . '/js/classifieds-pictures-thickbox.js', array('jquery','jquery.livequery'),BP_CLASSIFIEDS_VERSION);	
}



function bp_classifieds_pictures_tb_popup_head() {
//we only load the css if we are in the uploader popup of the classifieds creation/edition



?>
	<script type="text/javascript">
		jQuery(document).ready(function() {
			if (!top.document.getElementById('classified-pictures-upload')) return false;
			
			var link = document.createElement("link");
			var head = document.getElementsByTagName("head")[0];
			link.setAttribute("rel", "stylesheet");
			link.setAttribute("type", "text/css");
			link.setAttribute("href", "<?php echo BP_CLASSIFIEDS_PLUGIN_URL;?>/css/classifieds-pictures.css");
			head.appendChild(link);

		});

	</script>
<?php
}


//removes unwanted tabs from the uploader tabs
function bp_classifieds_pictures_upload_tabs($tabs){
	unset($tabs['library']);
	unset($tabs['type_url']);
	//unset($tabs['gallery']);


	return $tabs;
}


add_action( 'classifieds_init', 'classifieds_pictures_init');
add_filter('media_upload_tabs', 'bp_classifieds_pictures_upload_tabs',11);
add_filter( 'upload_dir', 'bp_classifieds_pictures_dir' );

function bp_classifieds_pictures_thickbox() {
	global $bp;
	
	$classified_id = $bp->classifieds->current_classified->ID;
	
	?>
	<script type="text/javascript">
		tb_path_to_uploader = '<?php echo get_blog_option($bp->classifieds->options['blog_id'],'siteurl ');?>wp-admin/media-upload.php?post_id=<?php echo $classified_id;?>';
		tb_pathToImage = "<?php echo get_blog_option($bp->classifieds->options['blog_id'],'siteurl ');?>wp-includes/js/thickbox/loadingAnimation.gif";
		tb_closeImage = "<?php echo get_blog_option($bp->classifieds->options['blog_id'],'siteurl ');?>wp-includes/js/thickbox/tb-close.png";

	</script>
	<?php
}
/*
function bp_classifieds_post_gallery_html($output,$attr) {

	print_r(strtolower( preg_replace('[^a-zA-Z_:]', '', $tag_name) ););
	
	return $output;

}


add_filter('post_gallery','bp_classifieds_post_gallery_html',10,2);
*/
// Change the upload file location
//duplicated from wp_upload_dir / almost the same.
function bp_classifieds_pictures_dir() {

	$siteurl = get_blog_option(BP_ROOT_BLOG,'siteurl ');
	$upload_path = get_blog_option(BP_ROOT_BLOG,'upload_path' );
	$upload_path = trim($upload_path);
	
	if ( empty($upload_path) ) {
		$dir = WP_CONTENT_DIR . '/uploads';
	} else {
		$dir = $upload_path;
		if ( 'wp-content/uploads' == $upload_path ) {
			$dir = WP_CONTENT_DIR . '/uploads';
		} elseif ( 0 !== strpos($dir, ABSPATH) ) {
			// $dir is absolute, $upload_path is (maybe) relative to ABSPATH
			$dir = path_join( ABSPATH, $dir );
		}
	}

	if ( !$url = get_blog_option(BP_ROOT_BLOG,'upload_url_path' ) ) {
		if ( empty($upload_path) || ( 'wp-content/uploads' == $upload_path ) || ( $upload_path == $dir ) ) {
			$url = WP_CONTENT_URL . '/uploads';
		}else {
			$url = trailingslashit( $siteurl ) . $upload_path;
		}
	}

	if ( defined('UPLOADS') ) {
		$dir = ABSPATH . UPLOADS;
		$url = trailingslashit( $siteurl ) . UPLOADS;
	}

	if ( is_multisite() ) { 
		if ( defined( 'BLOGUPLOADDIR' ) )  {
			$dir = untrailingslashit(BLOGUPLOADDIR);
			//CLASSIFIEDS FIX : USE ROOT BLOG !
			global $blog_id;
			$dir = str_replace('/'.$blog_id.'/','/'.BP_ROOT_BLOG.'/', $dir);
		}
		$url = str_replace( UPLOADS, 'files', $url ); 
	}

	$bdir = $dir;
	$burl = $url;
	


	$subdir = '';
	if ( get_option( 'uploads_use_yearmonth_folders' ) ) {
		// Generate the yearly and monthly dirs
		$time = current_time( 'mysql' );
		$y = substr( $time, 0, 4 );
		$m = substr( $time, 5, 2 );
		$subdir = "/$y/$m";
	}

	$dir .= '/classifieds'.$subdir;
	$url .= '/classifieds'.$subdir;

	return apply_filters( 'bp_classifieds_pictures_dir', array( 'path' => $dir, 'url' => $url, 'subdir' => $subdir, 'basedir' => $bdir, 'baseurl' => $burl, 'error' => false ) );

}

function bp_classifieds_pictures_classified_can_upload($cid=false){

	global $bp;

	if (!bp_classifieds_user_can_upload()) return false;
	
	if ($cid) {
		$classified = new BP_Classifieds_Classified( $cid );
	}else {
		$classified = $bp->classifieds->current_classified;
	}

		
	if (!bp_classifieds_user_can_admin_classified($classified)) return false;
	
	$max_pics=$bp->classifieds->options['pics_max'];
	
	$current_pics=bp_classifieds_pictures_get_gallery_items_count($classified->ID);

	if ($current_pics<$max_pics) return true;
	
	return false;
	
}

function bp_classifieds_pictures_get_gallery_items($id) {
	global $bp;

	$args = array('post_parent' => $id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => 'ASC', 'orderby' => 'menu_order');

	//TO FIX : problem with ID. It does not retrieve anything when set.
	//unset ($args['post_parent']);
	
	switch_to_blog($bp->classifieds->options['blog_id']);

	$attachments = & get_children($args);

	restore_current_blog();

	return $attachments;
}

function bp_classifieds_pictures_get_classified_first_pictures($id) {
	
	$attachments = bp_classifieds_pictures_get_gallery_items($id);
	
	if ( ! is_array($attachments) ) return false;
	
	return array_shift($attachments);
}

function bp_classifieds_pictures_set_classified_thumb($cid){



	$thumb_id = get_post_thumbnail_id( $cid );
	
	//check that the picture really exists

	$thumb_url = bp_classifieds_pictures_get_classified_thumbnail_src($thumb_id);
	
	if ($thumb_url) return false; //a thumb  has already been defined
	
	global $bp;

	$first_picture = bp_classifieds_pictures_get_classified_first_pictures($cid);
	
	$thumbnail_id = $first_picture->ID;
	
	switch_to_blog($bp->classifieds->options['blog_id']);
	update_post_meta( $cid, '_thumbnail_id', $thumbnail_id );
	restore_current_blog();
}


function bp_classifieds_pictures_get_gallery_items_count($id) {
	global $wpdb;
	//$total_attachments = $wpdb->get_var("SELECT COUNT(ID) FROM {$wpdb->prefix}posts WHERE post_type = 'attachment'");
	//return $total_attachments;
	
	return count(bp_classifieds_pictures_get_gallery_items($id));

}

function bp_classifieds_pictures_get_classified_thumbnail_src($cid) {
	global $bp;
	
	switch_to_blog($bp->classifieds->options['blog_id']);
	$post_thumbnail_id = get_post_thumbnail_id( $cid );
	$thumb = wp_get_attachment_image_src($post_thumbnail_id);
	restore_current_blog();
	
	return $thumb['0'];
}

function bp_classifieds_fetch_avatar($img,$params) {

	extract( $params, EXTR_SKIP );
	
	if ($object!='classified') return $img;
	
	/* Add an identifying class to each item */
	$class .= ' ' . $object . '-' . $item_id . '-avatar';

	if ( !empty($css_id) )
		$css_id = " id='{$css_id}'";

	if ( $width )
		$html_width = " width='{$width}'";
	else
		$html_width = ( 'thumb' == $type ) ? ' width="' . BP_AVATAR_THUMB_WIDTH . '"' : ' width="' . BP_AVATAR_FULL_WIDTH . '"';

	if ( $height )
		$html_height = " height='{$height}'";
	else
		$html_height = ( 'thumb' == $type ) ? ' height="' . BP_AVATAR_THUMB_HEIGHT . '"' : ' height="' . BP_AVATAR_FULL_HEIGHT . '"';
		
	$avatar_url = bp_classifieds_pictures_get_classified_thumbnail_src($params['item_id']);
	
	if (!$avatar_url) {
		$themedir = apply_filters('bp_classifieds_enqueue_url',get_stylesheet_directory_uri() . '/classifieds');
		$avatar_url = $themedir.'/_inc/images/default_avatar.png';

	}
	
	$img = "<img src='{$avatar_url}' alt='{$alt}' class='{$class}'{$css_id}{$html_width}{$html_height} />";
	return apply_filters( 'bp_classifieds_fetch_avatar',$img, $params );

}
	
	


//AJAX
function bp_classifieds_pictures_ajax_gallery() {

	$cid = $_POST['cid'];
	
	if (!$cid) echo '';

	echo classified_pictures_get_gallery($cid);

}

add_action( 'wp_ajax_classified_gallery', 'bp_classifieds_pictures_ajax_gallery' );

function bp_classifieds_pictures_classified_can_upload_ajax() {

	$cid = $_POST['cid'];
	
	if ((!$cid) || (!is_numeric($cid))) echo'';

	echo bp_classifieds_pictures_classified_can_upload($cid);

}

add_action( 'wp_ajax_classified_pictures_can_upload', 'bp_classifieds_pictures_classified_can_upload_ajax' );

?>