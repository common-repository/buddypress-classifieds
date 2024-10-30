<?php

if ( file_exists( BP_CLASSIFIEDS_PLUGIN_DIR . '/languages/' . get_locale() . '.mo' ) )
	load_textdomain( 'classifieds', BP_CLASSIFIEDS_PLUGIN_DIR . '/languages/' . get_locale() . '.mo' );

require ( BP_CLASSIFIEDS_PLUGIN_DIR . '/bp-classifieds-admin.php' );
require ( BP_CLASSIFIEDS_PLUGIN_DIR . '/bp-classifieds-ajax.php' );
require ( BP_CLASSIFIEDS_PLUGIN_DIR . '/bp-classifieds-classes.php' );
require ( BP_CLASSIFIEDS_PLUGIN_DIR . '/bp-classifieds-filters.php' );
require ( BP_CLASSIFIEDS_PLUGIN_DIR . '/bp-classifieds-templatetags.php' );
require ( BP_CLASSIFIEDS_PLUGIN_DIR . '/bp-classifieds-widgets.php' );
require ( BP_CLASSIFIEDS_PLUGIN_DIR . '/bp-classifieds-groups.php' );

require ( BP_CLASSIFIEDS_PLUGIN_DIR . '/bp-classifieds-maps.php' );

require ( BP_CLASSIFIEDS_PLUGIN_DIR . '/bp-classifieds-pictures.php' );


function classifieds_install() {
	global $bp, $wpdb;

	//new blogs cannot be called 'classifieds'
	if ( bp_core_is_multisite() )
		bp_core_add_illegal_names();
	
	//add members to the classifieds blog
	$users_to_add=bp_classifieds_get_non_authors();
	bp_classified_add_users_to_datablog($users_to_add);

	//install classifieds capabilities
	classifieds_setup_capabilities();
	
	update_site_option( 'bp-classifieds-db-version', BP_CLASSIFIEDS_DB_VERSION );

}

function classifieds_uninstall() {
	global $bp, $wpdb;
	
	//remove options
	delete_site_option( 'classifieds_options');
	//remove capabilities
	classifieds_setup_capabilities(false);


}

function classifieds_check_installed() {	
	global $wpdb, $bp;


	/* Need to check db tables exist, activate hook no-worky in mu-plugins folder. */
	if ( get_site_option('bp-classifieds-db-version') < BP_CLASSIFIEDS_DB_VERSION )
		classifieds_install();
}
add_action( 'admin_menu', 'classifieds_check_installed' );

function bp_classifieds_is_setup() {
	$classifieds_options = get_site_option( 'classifieds_options');
	
	if ($classifieds_options['blog_id']) {
		return true;
	}
}


function classifieds_setup_globals() {
	global $bp, $wpdb;

	/* For internal identification */
	$bp->classifieds->id = 'classifieds';
	
	/* Register this in the active components array */
	$bp->classifieds->format_notification_function = 'classifieds_format_notifications';
	$bp->classifieds->slug = BP_CLASSIFIEDS_SLUG;

	/* Register this in the active components array */
	$bp->active_components[$bp->classifieds->slug] = $bp->classifieds->id;
	
	$bp->classifieds->forbidden_names = apply_filters( 'classifieds_forbidden_names', array(
		__('my-classifieds','classifieds-slugs'),
		__('admin','classifieds-slugs'),
		__('create','classifieds-slugs'),
		__('publish','classifieds-slugs'),
		__('republish','classifieds-slugs'),
		__('follow','classifieds-slugs')
	));
	
	$options_defaults =	array(
		'capabilities'=>array('visitors'=>2),
		'days_active'=>30,
		'tags_suggestion'=>true,
		'default_category'=>1,
		'classifieds_groups'=>true,
		'pics_max'=>5,
		'tinymce'=>true
	);
	
	
	$custom_options = get_site_option( 'classifieds_options');
	
	$bp->classifieds->options = wp_parse_args($custom_options, $options_defaults );
	
	if ($bp->classifieds->options['tinymce'])
		require_once ( BP_CLASSIFIEDS_PLUGIN_DIR . '/bp-classifieds-tinymce.php' );
		
	
	$blog_id = $bp->classifieds->options['blog_id'];
	$table_prefix = $wpdb->base_prefix .$bp->classifieds->options['blog_id'].'_';

	$bp->classifieds->table_name_terms = $table_prefix.'terms';
	$bp->classifieds->table_name_term_taxonomy = $table_prefix.'term_taxonomy';
	$bp->classifieds->table_name_term_relationships = $table_prefix.'term_relationships';
	$bp->classifieds->table_name_classifieds = $table_prefix.'posts';
	$bp->classifieds->table_name_classifiedmeta = $table_prefix.'postmeta';

	$classified_creation_steps[__('classified-details','classifieds-slugs')] = array( 'name' => __( 'Classified Details', 'classifieds' ), 'position' => 0 );
	
	//enable only if actions or categories exists
	$show_creation_settings = (int) bp_classifieds_is_actions_enabled() + bp_classifieds_is_categories_enabled();
	$show_creation_settings = apply_filters('show_creation_settings',$show_creation_settings);
	
	if ( $show_creation_settings )
		$classified_creation_steps[__('classified-settings','classifieds-slugs')] = array( 'name' => __( 'Classified Settings', 'classifieds' ), 'position' => 10 );
	
	if ((bp_classified_user_can('Classifieds Publish Classifieds')) || (bp_classified_user_can('Classifieds Edit Others Classifieds')))
		$classified_creation_steps[__('classified-invites','classifieds-slugs')] = array( 'name' => __( 'Classified Invites', 'classifieds' ), 'position' => 40 );
	
	
	$bp->classifieds->classified_creation_steps = apply_filters( 'classifieds_create_classified_steps', $classified_creation_steps );
	
	/*
	if (bp_classifieds_moderation_exists()) {
		unset($classified_creation_steps[__('classified-invites','classifieds-slugs')]); //no invites at creation if moderation is set
		$valid_status[]='pending';
	}
	*/

	$capabilities = array(
		array(	
			'name'	=> __('Classifieds View Classifieds Lists','classifieds'),
			'default_cap'	=> 'read'
		),
		array(	
			'name'	=> __('Classifieds View Classified Details','classifieds'),
			'default_cap'	=> 'read'
		),
		array(	
			'name'	=> __('Classifieds View Classified Followers','classifieds'),
			'default_cap'	=> 'read'
		),
		array(	
			'name'	=> __('Classifieds Delete Classifieds','classifieds'),
			'default_cap'	=> 'delete_posts'
		),
		array(	
			'name'	=> __('Classifieds Edit Classifieds','classifieds'),
			'default_cap'	=> 'edit_posts'
		),
		array(	
			'name'	=> __('Classifieds Delete Published Classifieds','classifieds'),
			'default_cap'	=> 'delete_published_posts'
		),
		array(	
			'name'	=> __('Classifieds Upload Images','classifieds'),
			'default_cap'	=> 'upload_files'
		),
		array(	
			'name'	=> __('Classifieds Publish Classifieds','classifieds'),
			'default_cap'	=> 'publish_posts'
		),
		array(	
			'name'	=> __('Classifieds Republish Classifieds','classifieds'),
			'default_cap'	=> 'publish_posts'
		),
		array(	
			'name'	=> __('Classifieds Edit Others Classifieds','classifieds'),
			'default_cap'	=> 'edit_others_posts'
		),
		array(	
			'name'	=> __('Classifieds Edit Published Classifieds','classifieds'),
			'default_cap'	=> 'edit_published_posts'
		)
		
	);

	$bp->classifieds->capabilities = apply_filters( 'classifieds_capabilities',$capabilities);
	
	//activity
	apply_filters( 'bp_activity_mini_activity_types', array(
		'friendship_accepted',
		'friendship_created',
		'new_blog',
		'joined_group',
		'created_group',
		'new_member'
	) );

	
	do_action('classifieds_init');
	
}
add_action( 'plugins_loaded', 'classifieds_setup_globals', 5 );
add_action( 'admin_menu', 'classifieds_setup_globals', 2 );


function classifieds_enable_debug() {
	global $bp;
	
	$path_to_FB = $bp->classifieds->options['firephp_path'].'/FirePHP.class.php';

	if (($bp->classifieds->options['enable_debug']) && ( file_exists($path_to_FB))) {

		require_once ($path_to_FB);

		define ( 'BP_CLASSIFIEDS_DEBUG', true );
		
		$bp->classifieds->debug = FirePHP::getInstance(true);
		$bp->classifieds->debug->group('Versions');
		$bp->classifieds->debug->log('Classifieds version',BP_CLASSIFIEDS_VERSION);
		$bp->classifieds->debug->log('Classifieds db version',BP_CLASSIFIEDS_DB_VERSION);
		$bp->classifieds->debug->log('BuddyPress version',BP_VERSION);
		$bp->classifieds->debug->log('WP version',get_bloginfo( 'version' ));
		$bp->classifieds->debug->groupEnd();

	}
}
add_action( 'plugins_loaded', 'classifieds_enable_debug');



function bp_classifieds_header_nav_setup() {
	global $bp;
	
	if (!bp_classifieds_is_setup()) return false;
	
	$blog_id = $bp->classifieds->options['blog_id'];
	$url = get_blog_option( $blog_id, 'siteurl' );

	$selected = ( bp_is_page( BP_CLASSIFIEDS_SLUG ) ) ? ' class="selected"' : '';
	$title = __( 'Classifieds', 'classifieds' );

	echo sprintf('<li%s><a href="%s/%s" title="%s">%s</a></li>', $selected, get_option('home'), BP_CLASSIFIEDS_SLUG, $title, $title );
}
add_action( 'bp_nav_items', 'bp_classifieds_header_nav_setup', 99);


// Adds admin menu to WP Dashboard > BuddyPress	


function classifieds_add_admin_menu() {
	global $wpdb, $bp;
	

	if ( !is_site_admin() )
		return false;

	/* Add the administration tab under the "Site Admin" tab for site administrators */
	add_submenu_page( 'bp-general-settings', __('Classifieds','classifieds'),__('Classifieds','classifieds'), 'manage_options', 'bp-classifieds-setup', "classifieds_admin" );
}
add_action('admin_menu', 'classifieds_add_admin_menu');

function classifieds_edit_posts_page_remove_pending_notifications() {
	
	if ( !is_site_admin() )
		return false;
	
	global $pagenow;
	global $bp;
	global $blog_id;

	if (($pagenow != 'edit.php') || ($blog_id!=$bp->classifieds->options['blog_id'])) return false; //TO FIX TO CHECK : better statement ?

	if (bp_classifieds_moderation_exists()) {
		bp_core_delete_notifications_for_user_by_type( $bp->loggedin_user->id, $bp->classifieds->slug, 'classified_pending' );
	}

}
//add also when saving post

add_action('admin_menu','classifieds_edit_posts_page_remove_pending_notifications');




function classifieds_admin_init(){
	global $blog_id;
	
	if ($blog_id!=BP_ROOT_BLOG) return false;

	classifieds_admin_warnings();
	classifieds_upgrade_admin_warnings();
}
add_action('admin_init', 'classifieds_admin_init');


function classifieds_admin_warnings() {

	function classifieds_blog_id_warning() {
		if (bp_classifieds_is_setup()) return false;
		echo "
		<div id='classifieds-warning' class='updated fade'><p><strong>".__('BuddyPress Classifieds is almost ready.')."</strong> ".sprintf(__('You must now <a href="%1$s">setup</a> it.'), "admin.php?page=bp-classifieds-setup")."</p></div>
		";
	}
	add_action('admin_notices', 'classifieds_blog_id_warning');

	
}

function classifieds_upgrade_admin_warnings() {
	
	if (!bp_classifieds_is_setup()) return false;

	function classifieds_upgrade_sync() {
		global $bp;
		echo "
		<div id='classifieds-warning' class='updated fade error'><p>To be able to post classifieds, every member of your main blog must have the author role on the classifieds-data blog (blog #".$bp->classifieds->options['blog_id'].") : you need to <strong>sync them</strong>.  Please make a <strong>database backup</strong> first then <a href=\"admin.php?page=bp-classifieds-setup&upgrade=sync_users#system\">click here</a>.</p></div>
		";
	}

	//if (!empty($blog_datas_non_authors))
		//add_action('admin_notices', 'classifieds_upgrade_sync');
}


function classifieds_setup_root_component() {
	/* Register 'classifieds' as a root component */

	//TO FIX USELESS ?  if (!bp_classifieds_is_setup()) return false;

	bp_core_add_root_component( BP_CLASSIFIEDS_SLUG );

}
add_action( 'plugins_loaded', 'classifieds_setup_root_component', 2 );

function classifieds_setup_nav() {
	global $bp;
	
	if (!bp_classifieds_is_setup()) return false;
	
	//TO FIX : bad redirection if classified do not exists
	
	if ( $classified_id = BP_Classifieds_Classified::classified_exists($bp->current_action) ) {

		/* This is a single classified page. */

		$bp->is_single_item = true;
		$bp->classifieds->current_classified = &new BP_Classifieds_Classified( $classified_id );

		/* Using "item" not "classified" for generic support in other components. */
		/* If the user is not an admin, check if they are a moderator */
		if (!$bp->is_site_admin) {
			$bp->is_item_mod = bp_classified_user_can('Classifieds Edit Others Classifieds');
			if (!$bp->is_item_mod) {
				$bp->is_item_author = bp_classified_is_author($bp->classifieds->current_classified);
			}
		}
		
		/* Check if they can admin item */
		if ($bp->is_site_admin || $bp->is_item_mod || $bp->is_item_author) $bp->is_item_admin = 1;
		
		/* Is the logged in user a follower of the classified? */
		$bp->classifieds->current_classified->is_user_follower = ( is_user_logged_in() && classifieds_is_follower( $bp->loggedin_user->id, $bp->classifieds->current_classified->ID ) ) ? true : false;
	
		/* Should this classified be visible to the logged in user? */
		$bp->classifieds->current_classified->is_classified_visible_to_user = ( 'publish' == $bp->classifieds->current_classified->status || $bp->is_item_admin ) ? true : false;
	}

	/* Add 'Classifieds' to the main navigation */

	bp_core_new_nav_item( array( 'name' => sprintf( __( 'Classifieds <span>(%d)</span>', 'classifieds' ), bp_get_total_member_classified_count() ), 'slug' => $bp->classifieds->slug, 'position' => 80, 'screen_function' => 'classifieds_screen_my_classifieds', 'default_subnav_slug' => 'my-classifieds', 'item_css_id' => $bp->classifieds->id ) );

	$classifieds_link = $link = $bp->loggedin_user->domain . $bp->classifieds->slug . '/';

	/* Add the subnav items to the groups nav item */
	bp_core_new_subnav_item( array( 'name' => __( 'My Classifieds', 'classifieds' ), 'slug' => __('my-classifieds','classifieds-slugs'), 'parent_url' => $classifieds_link, 'parent_slug' => $bp->classifieds->slug, 'screen_function' => 'classifieds_screen_my_classifieds', 'position' => 10, 'item_css_id' => 'classifieds-my-classifieds' ) );
	bp_core_new_subnav_item( array( 'name' => __( 'Create a Classified', 'classifieds' ), 'slug' => __('create','classifieds-slugs'), 'parent_url' => $bp->root_domain . '/' . $bp->classifieds->slug. '/', 'parent_slug' => $bp->classifieds->slug, 'screen_function' => 'classifieds_screen_create_classified', 'position' => 10, 'item_css_id' => 'classifieds-my-classifieds' ) );

	if (bp_is_user_classifieds()) {

		if ( bp_is_home() && !$bp->is_single_item ) {
			
			$bp->bp_options_title = __( 'My Classifieds', 'classifieds' );
		
		} else if ( !bp_is_home() && !$bp->is_single_item ) {

			//$bp->bp_options_avatar = bp_classifieds_fetch_avatar( array( 'item_id' => $bp->displayed_user->id, 'type' => 'thumb' ) );
			$bp->bp_options_title = $bp->displayed_user->fullname;
			

			
		} else if ( $bp->is_single_item ) {

			// We are viewing a single classified, so set up the
			// classified navigation menu using the $bp->classifieds->current_classified global.
			
			/* When in a single classified, the first action is bumped down one because of the
			   classified name, so we need to adjust this and set the classified name to current_item. */
			   		   
			$bp->current_item = $bp->current_action;
			$bp->current_action = $bp->action_variables[0];
			array_shift($bp->action_variables);
									
			$bp->bp_options_title = $bp->classifieds->current_classified->name;
			
			//$bp->bp_options_avatar = bp_classifieds_fetch_avatar( array( 'item_id' => $bp->classifieds->current_classified->ID, 'object' => 'classified', 'type' => 'thumb', 'avatar_dir' => 'classified-avatars', 'alt' => __( 'Classified Avatar', 'classifieds' ) ) );
			
			$classified_link = $bp->root_domain . '/' . $bp->classifieds->slug . '/' . $bp->classifieds->current_classified->slug . '/';
			
			/* Reset the existing subnav items */
			bp_core_reset_subnav_items($bp->classifieds->slug);

			/* Add a new default subnav item for when the classifieds nav is selected. */
			bp_core_new_nav_default( array( 'parent_slug' => $bp->classifieds->slug, 'screen_function' => 'classifieds_screen_classified_home', 'subnav_slug' => 'home' ) );

			
			/* Add the "Home" subnav item, as this will always be present */
			bp_core_new_subnav_item( array( 'name' => __( 'Home', 'buddypress' ), 'slug' => __('home','classifieds-slugs'), 'parent_url' => $classified_link, 'parent_slug' => $bp->classifieds->slug, 'screen_function' => 'classifieds_screen_classified_home', 'position' => 10, 'item_css_id' => 'classified-home' ) );
			
			/* If the user is a classified mod or more, then show the classified admin nav item */
			
			
			bp_core_new_subnav_item( array( 'name' => __( 'Admin', 'buddypress' ), 'slug' => __('admin','classifieds-slugs'), 'parent_url' => $classified_link, 'parent_slug' => $bp->classifieds->slug, 'screen_function' => 'classifieds_screen_classified_admin', 'position' => 20, 'user_has_access' => bp_classifieds_user_can_admin_classified(), 'item_css_id' => 'classified-admin' ) );

			
			/*wire*/
			if ( ($bp->classifieds->current_classified->comment_status=='open') && function_exists('bp_wire_install') )
				bp_core_new_subnav_item( array( 'name' => __( 'Wire', 'buddypress' ), 'slug' => BP_WIRE_SLUG, 'parent_url' => $classified_link, 'parent_slug' => $bp->classifieds->slug, 'screen_function' => 'classifieds_screen_classified_wire', 'position' => 50, 'item_css_id' => 'classified-wire', 'user_has_access'=>bp_classified_is_visible()) );

			if (bp_classified_is_visible()&&bp_classified_user_can('Classifieds View Classified Followers')) $view_subnav_followers=true;
			bp_core_new_subnav_item( array( 'name' => __( 'Followers', 'classifieds' ), 'slug' => __('followers','classifieds-slugs'), 'parent_url' => $classified_link, 'parent_slug' => $bp->classifieds->slug, 'screen_function' => 'classifieds_screen_classified_followers', 'position' => 60, 'item_css_id' => 'classified-followers', 'user_has_access'=>$view_subnav_followers) );
			
			if ( bp_classified_is_published($bp->classifieds->current_classified) ) {
				if ( function_exists('friends_install') )
					bp_core_new_subnav_item( array( 'name' => __( 'Tell a friend', 'classifieds' ), 'slug' => __('send-invites','classifieds-slugs'), 'parent_url' => $classified_link, 'parent_slug' => $bp->classifieds->slug, 'screen_function' => 'classifieds_screen_classified_invite', 'item_css_id' => 'classified-invite', 'position' => 70, 'user_has_access' =>is_user_logged_in() ) );
			}
		}
	}
	
	do_action( 'classifieds_setup_nav',$bp->classifieds->current_classified->user_has_access);
	
}
add_action( 'plugins_loaded', 'classifieds_setup_nav' );
add_action( 'admin_menu', 'classifieds_setup_nav' );


function classifieds_directory_classifieds_setup() {
	global $classifieds_template;
	global $bp;

	if (bp_is_classifieds_directory()) {
		$bp->is_directory = true;

		do_action( 'classifieds_directory_classifieds_setup' );

		bp_classifieds_load_template('classifieds/index');
	}
}
add_action( 'wp', 'classifieds_directory_classifieds_setup', 2 );

function classifieds_setup_adminbar_menu() {
	global $bp;

	if ( !$bp->classifieds->current_classified )
		return false;

	/* Don't show this menu to non site admins or if you're viewing your own profile */
	if ( !is_site_admin() )
		return false;
	?>
	<li id="bp-adminbar-adminoptions-menu">
		<a href=""><?php _e( 'Admin Options', 'buddypress' ) ?></a>

		<ul>
			<li><a class="confirm" href="<?php echo wp_nonce_url( bp_get_classified_permalink( $bp->classifieds->current_classified ) . 'admin/delete-classified/', 'classifieds_delete_classified' ) ?>&amp;delete-classified-button=1&amp;delete-classified-understand=1"><?php _e( "Delete classified", 'buddypress' ) ?></a></li>

			<?php do_action( 'classifieds_adminbar_menu_items' ) ?>
		</ul>
	</li>
	<?php
}
add_action( 'bp_adminbar_menus', 'classifieds_setup_adminbar_menu', 20 );


/********************************************************************************
 * Screen Functions
 *
 * Screen functions are the controllers of BuddyPress. They will execute when their
 * specific URL is caught. They will first save or manipulate data using business
 * functions, then pass on the user to a template file.
 */
 
function classifieds_screen_create_classified() {
	global $bp;
	
	/* If we're not at domain.org/groups/create/ then return false */
	if ( $bp->current_component != $bp->classifieds->slug || 'create' != $bp->current_action )
		return false;

	if ( !is_user_logged_in() )
		return false;

	/* Make sure creation steps are in the right order */
	classifieds_action_sort_creation_steps();

	/* If no current step is set, reset everything so we can start a fresh group creation */
	if ( !$bp->classifieds->current_create_step = $bp->action_variables[1] ) {

		unset( $bp->classifieds->current_create_step );
		unset( $bp->classifieds->completed_create_steps );
		
		setcookie( 'bp_new_classified_id', false, time() - 1000, COOKIEPATH );
		setcookie( 'bp_completed_create_steps', false, time() - 1000, COOKIEPATH );
		
		$reset_steps = true;
		bp_core_redirect( $bp->root_domain . '/' . $bp->classifieds->slug. '/'.__('create','classifieds-slugs').'/'.__('step','classifieds-slugs').'/' . array_shift( array_keys( $bp->classifieds->classified_creation_steps )  ) );
	}
	
	/* If this is a creation step that is not recognized, just redirect them back to the first screen */
	if ( $bp->action_variables[1] && !$bp->classifieds->classified_creation_steps[$bp->action_variables[1]] ) {
		bp_core_add_message( __('There was an error saving classified details. Please try again.', 'classifieds'), 'error' );
		bp_core_redirect( $bp->root_domain . '/' . $bp->classifieds->slug. '/'.__('create','classifieds-slugs') );
	}

	/* Fetch the currently completed steps variable */
	if ( isset( $_COOKIE['bp_completed_create_steps'] ) && !$reset_steps )
		$bp->classifieds->completed_create_steps = unserialize( stripslashes( $_COOKIE['bp_completed_create_steps'] ) );

	/* Set the ID of the new classified, if it has already been created in a previous step */
	if ( isset( $_COOKIE['bp_new_classified_id'] ) ) {
		$bp->classifieds->new_classified_id = $_COOKIE['bp_new_classified_id'];
		$bp->classifieds->current_classified = new BP_Classifieds_Classified( $bp->classifieds->new_classified_id, false);
	}

	/* If the save, upload or skip button is hit, lets calculate what we need to save */
	if ( isset( $_POST['save'] ) ) {
				
		/* Check the nonce */
		check_admin_referer( 'classifieds_create_save_' . $bp->classifieds->current_create_step );
		
		if ( __('classified-details','classifieds-slugs') == $bp->classifieds->current_create_step ) {
		
			if ( empty( $_POST['classified-name'] ) || empty( $_POST['classified-desc'] ) ) {
				//TO FIX : errors do not show
				bp_core_add_message( __( 'Please fill in all of the required fields', 'buddypress' ), 'error' );
				bp_core_redirect( $bp->root_domain . '/' . $bp->classifieds->slug. '/'.__('create','classifieds-slugs').'/'.__('step','classifieds-slugs').'/' . $bp->classifieds->current_create_step );
			}
			
			if (count($bp->classifieds->classified_creation_steps) > 1) {
				$classified_status = 'draft';
			}else {
				if ((!bp_classified_user_can('Classifieds Publish Classifieds')) && (!bp_classified_user_can('Classifieds Edit Others Classifieds'))) {
					$classified_status = 'pending';
				}else {
					$classified_status = 'publish';
				}
			}

			if (isset($_POST['classified_enable_comments']) )
				$classified_enable_comments = 1;
			
			if ( !$bp->classifieds->new_classified_id = classifieds_create_classified( array( 'classified_id' => $bp->classifieds->new_classified_id, 'name' => $_POST['classified-name'], 'status'=>$classified_status, 'description' => $_POST['classified-desc'],'enable_comments' => $classified_enable_comments) ) ) {
				bp_core_add_message( __( 'There was an error saving classified details, please try again.', 'classifieds' ), 'error' );
				bp_core_redirect( $bp->root_domain . '/' . $bp->classifieds->slug. '/'.__('create','classifieds-slugs').'/'.__('step','classifieds-slugs').'/' . $bp->classifieds->current_create_step );				
			}
			classifieds_update_last_activity($bp->classifieds->new_classified_id);
		}
		
		if ( __('classified-settings','classifieds-slugs') == $bp->classifieds->current_create_step ) {
		
			//CATEGORIES & ACTIONS & TAGS

			
			if (((bp_classifieds_is_actions_enabled()) && ( !isset($_POST['classified-action']))) || (empty($_POST['classified-categories']))) {
				bp_core_add_message( __( 'Please fill in all of the required fields', 'buddypress' ), 'error' );
				bp_core_redirect( $bp->root_domain . '/' . $bp->classifieds->slug. '/'.__('create','classifieds-slugs').'/'.__('step','classifieds-slugs').'/' . $bp->classifieds->current_create_step );
			}

			if ( !$bp->classifieds->new_classified_id = classifieds_create_classified( array( 'classified_id' => $bp->classifieds->new_classified_id, 'action' => $_POST['classified-action'], 'categories' => $_POST['classified-categories'],'tags'=>$_POST['classified-tags']) ) ) {
				bp_core_add_message( __( 'There was an error saving classified details, please try again.', 'classifieds' ), 'error' );
				bp_core_redirect( $bp->root_domain . '/' . $bp->classifieds->slug. '/'.__('create','classifieds-slugs').'/'.__('step','classifieds-slugs').'/' . $bp->classifieds->current_create_step );				
			}
		}

		if ( __('classified-invites','classifieds-slugs') == $bp->classifieds->current_create_step ) {
			classifieds_send_invites($bp->loggedin_user->id,$bp->classifieds->new_classified_id,$_POST['friends']);
		}

		do_action( 'classifieds_create_classified_step_save_' . $bp->classifieds->current_create_step );
		do_action( 'classifieds_create_classified_step_complete' ); // Mostly for clearing cache on a generic action name
		
		/**
		 * Once we have successfully saved the details for this step of the creation process
		 * we need to add the current step to the array of completed steps, then update the cookies
		 * holding the information
		 */
		if ( !in_array( $bp->classifieds->current_create_step, (array)$bp->classifieds->completed_create_steps ) )
			$bp->classifieds->completed_create_steps[] = $bp->classifieds->current_create_step;
		
		/* Reset cookie info */
		setcookie( 'bp_new_classified_id', $bp->classifieds->new_classified_id, time()+60*60*24, COOKIEPATH );
		setcookie( 'bp_completed_create_steps', serialize( $bp->classifieds->completed_create_steps ), time()+60*60*24, COOKIEPATH );	

		/* If we have completed all steps and hit done on the final step we can redirect to the completed classified */
		if ( count( $bp->classifieds->completed_create_steps ) == count( $bp->classifieds->classified_creation_steps ) && $bp->classifieds->current_create_step == array_pop( array_keys( $bp->classifieds->classified_creation_steps ) ) ) {
		
			unset( $bp->classifieds->current_create_step );
			unset( $bp->classifieds->completed_create_steps );


			if ((!bp_classified_user_can('Classifieds Publish Classifieds')) && (!bp_classified_user_can('Classifieds Edit Others Classifieds'))) {
			
				if ( !$bp->classifieds->new_classified_id = classifieds_create_classified( array( 'classified_id' => $bp->classifieds->new_classified_id,'status' => 'pending') ) ) {
					if ( defined( 'BP_CLASSIFIEDS_DEBUG' ) )$bp->classifieds->debug->warn('error updating classified '.$bp->classifieds->new_classified_id.' status.');
				}else {
					bp_core_add_message(__('Your classified has been created but needs to be validated by a moderator.', 'classifieds' ));
					/* Post an email notification if settings allow */
					require_once ( BP_CLASSIFIEDS_PLUGIN_DIR . '/bp-classifieds-notifications.php' );
					//TO FIX : for all moderators (check categories) and admins
					classifieds_notification_classified_pending( $bp->classifieds->current_classified, 1 );
				}
			}else {
				classifieds_publish_classified($bp->classifieds->current_classified->ID);
				$invites_link = $invited_link . '/' . $bp->classifieds->current_classified->slug . '/send-invites';
				bp_core_add_message(__( 'Your classified has been published.  Don\'t forget to tell your friends !', 'classifieds' ));
			}
			
			do_action( 'classifieds_classified_create_complete', $bp->classifieds->new_classified_id );
			
			bp_core_redirect( bp_get_classified_permalink( $bp->classifieds->current_classified ) );
		} else {
			/**
			 * Since we don't know what the next step is going to be (any plugin can insert steps)
			 * we need to loop the step array and fetch the next step that way.
			 */
			foreach ( $bp->classifieds->classified_creation_steps as $key => $value ) {
				if ( $key == $bp->classifieds->current_create_step ) {
					$next = 1; 
					continue;
				}
				
				if ( $next ) {
					$next_step = $key; 
					break;
				}
			}
				bp_core_redirect( $bp->root_domain . '/' . $bp->classifieds->slug. '/'.__('create','classifieds-slugs').'/'.__('step','classifieds-slugs').'/' . $next_step );
		}
	}
	
	/* Classified avatar is handled seperately */
	if ( 'classified-avatar' == $bp->classifieds->current_create_step && isset( $_POST['upload'] ) ) {
	}

	bp_core_load_template( apply_filters( 'classifieds_template_classified_create', 'classifieds/create' ) );	
}

add_action( 'wp', 'classifieds_screen_create_classified', 3 );

function classifieds_action_sort_creation_steps() {
	global $bp;

	if ( $bp->current_component != BP_CLASSIFIEDS_SLUG && $bp->current_action != 'create' )
		return false;

	if ( !is_array( $bp->classifieds->classified_creation_steps ) )
		return false;

	foreach ( $bp->classifieds->classified_creation_steps as $slug => $step )
		$temp[$step['position']] = array( 'name' => $step['name'], 'slug' => $slug );

	/* Sort the steps by their position key */
	ksort($temp);
	unset($bp->classifieds->classified_creation_steps);

	foreach( $temp as $position => $step )
		$bp->classifieds->classified_creation_steps[$step['slug']] = array( 'name' => $step['name'], 'position' => $position );
}

function classifieds_screen_classified_home() {
	global $bp;

	if ( $bp->is_single_item ) {


		bp_core_delete_notifications_for_user_by_item_id( $bp->loggedin_user->id, $bp->classifieds->current_classified->ID, $bp->classifieds->slug, 'classified_published');
		bp_core_delete_notifications_for_user_by_item_id( $bp->loggedin_user->id, $bp->classifieds->current_classified->ID, $bp->classifieds->slug, 'classified_republished');
		bp_core_delete_notifications_for_user_by_item_id( $bp->loggedin_user->id, $bp->classifieds->current_classified->ID, $bp->classifieds->slug, 'classified_invite');
	
		do_action( 'classifieds_screen_classified_home' );	
		
		bp_core_load_template( apply_filters( 'classifieds_template_classified_home', 'classifieds/single/home' ) );	


	}
}

function classifieds_screen_my_classifieds() {
	global $bp;

	do_action( 'classifieds_screen_my_classifieds' );
	
	

	bp_core_load_template( apply_filters( 'classifieds_template_my_classifieds', 'members/single/home' ) );
	
}

function classifieds_screen_classified_admin() {
	global $bp;

	if ( $bp->current_component != BP_CLASSIFIEDS_SLUG || 'admin' != $bp->current_action )
		return false;

	if ( !empty( $bp->action_variables[0] ) )
		return false;

	bp_core_redirect( bp_get_classified_permalink( $bp->classifieds->current_classified ) . __('admin','classifieds-slugs').'/'.__('edit-details','classifieds-slugs') );
}

function classifieds_screen_classified_admin_edit_details() {
	global $bp;

	if ( $bp->current_component == $bp->classifieds->slug && __('edit-details','classifieds-slugs') == $bp->action_variables[0] ) {

		if (bp_classifieds_user_can_admin_classified($bp->classifieds->current_classified)) {

			// If the edit form has been submitted, save the edited details
			if ( isset( $_POST['save'] ) ) {
				/* Check the nonce first. */
				if ( !check_admin_referer( 'classifieds_edit_classified_details' ) )
					return false;
					
				if (isset($_POST['classified_enable_comments']) )
					$classified_enable_comments = 1;

				if ( !classifieds_edit_base_classified_details( $_POST['classified-id'], $_POST['classified-name'], $_POST['classified-desc'], $classified_enable_comments) ) {
					bp_core_add_message( __( 'There was an error updating classified details, please try again.', 'buddypress' ), 'error' );
				} else {
					bp_core_add_message( __( 'Classified details were successfully updated.', 'buddypress' ) );
				}

				do_action( 'classifieds_classified_details_edited', $bp->classifieds->current_classified->ID );

				bp_core_redirect( bp_get_classified_permalink( $bp->classifieds->current_classified ) . __('admin','classifieds-slugs').'/'.__('edit-details','classifieds-slugs') );
			}

			do_action( 'classifieds_screen_classified_admin_edit_details', $bp->classifieds->current_classified->ID );

			bp_core_load_template( apply_filters( 'classifieds_template_classified_admin', 'classifieds/single/home' ) );
		}
	}
}
add_action( 'wp', 'classifieds_screen_classified_admin_edit_details', 4 );

function classifieds_screen_classified_admin_settings() {
	global $bp;

	if ( $bp->current_component == $bp->classifieds->slug && __('classified-settings','classifieds-slugs') == $bp->action_variables[0] ) {

		if (!bp_classifieds_user_can_admin_classified($bp->classifieds->current_classified))
			return false;

		// If the edit form has been submitted, save the edited details
		if ( isset( $_POST['save'] ) ) {

			/* Check the nonce first. */
			if ( !check_admin_referer( 'classifieds_edit_classified_settings' ) )
				return false;

			if ( !classifieds_edit_classified_settings( $_POST['classified-id'], $_POST['classified-action'], $_POST['classified-categories'], $_POST['classified-tags']) ) {
				bp_core_add_message( __( 'There was an error updating classified settings, please try again.', 'buddypress' ), 'error' );
			} else {
				bp_core_add_message( __( 'Classified settings were successfully updated.', 'buddypress' ) );
			}

			do_action( 'classifieds_classified_settings_edited', $bp->classifieds->current_classified->ID );

			bp_core_redirect( bp_get_classified_permalink( $bp->classifieds->current_classified ) . __('admin','classifieds-slugs').'/'.__('classified-settings','classifieds-slugs') );
		}

		do_action( 'classifieds_screen_classified_admin_settings', $bp->classifieds->current_classified->ID );

		bp_core_load_template( apply_filters( 'classifieds_template_classified_admin_settings', 'classifieds/single/home' ) );
	}
}
add_action( 'wp', 'classifieds_screen_classified_admin_settings', 4 );


function classifieds_screen_classified_wire() {
	global $bp;
	
	$wire_action = $bp->action_variables[0];
	
	if ( $bp->is_single_item ) {
		if ( 'post' == $wire_action && bp_classified_user_can('classifieds_wire_post') ) {
			/* Check the nonce first. */
			if ( !check_admin_referer( 'bp_wire_post' ) ) 
				return false;
		
			if ( !classifieds_new_wire_post( $bp->classifieds->current_classified->ID, $_POST['wire-post-textarea'] ) )
				bp_core_add_message( __('Wire message could not be posted.', 'buddypress'), 'error' );
			else
				bp_core_add_message( __('Wire message successfully posted.', 'buddypress') );

			if ( !strpos( wp_get_referer(), $bp->wire->slug ) )
				bp_core_redirect( bp_get_classified_permalink( $bp->classifieds->current_classified ) );
			else
				bp_core_redirect( bp_get_classified_permalink( $bp->classifieds->current_classified ) . '/' . $bp->wire->slug );
	
		} else if ( 'delete' == $wire_action && classifieds_can_user_wire_post( $bp->loggedin_user->id, $bp->classifieds->current_classified->ID ) ) {
			$wire_message_id = $bp->action_variables[1];

			/* Check the nonce first. */
			if ( !check_admin_referer( 'bp_wire_delete_link' ) )
				return false;
		
			if ( !classifieds_delete_wire_post( $wire_message_id, $bp->classifieds->table_name_wire ) )
				bp_core_add_message( __('There was an error deleting the wire message.', 'buddypress'), 'error' );
			else
				bp_core_add_message( __('Wire message successfully deleted.', 'buddypress') );
			
			if ( !strpos( wp_get_referer(), $bp->wire->slug ) )
				bp_core_redirect( bp_get_classified_permalink( $bp->classifieds->current_classified ) );
			else
				bp_core_redirect( bp_get_classified_permalink( $bp->classifieds->current_classified ) . '/' . $bp->wire->slug );
		
		} else if ( ( !$wire_action || 'latest' == $bp->action_variables[1] ) ) {

			bp_core_load_template( apply_filters( 'classifieds_template_classified_wire', 'classifieds/single/wire' ) );

		} else {
		
			bp_core_load_template( apply_filters( 'classifieds_template_classified_home', 'classifieds/single/home' ) );

		}
	}
}

function classifieds_screen_classified_followers() {
	global $bp;

	if ( $bp->is_single_item ) {
		do_action( 'classifieds_screen_classified_followers', $bp->classifieds->current_classified->ID );
		bp_core_load_template( apply_filters( 'classifieds_template_classified_followers', 'classifieds/single/home' ) );
	}
}

function classifieds_screen_classified_invite() {
	global $bp;

	if ( $bp->is_single_item ) {
		
		if ( isset($bp->action_variables) && __('send','classifieds-slugs') == $bp->action_variables[0] ) {
		
			if ( !check_admin_referer( 'classifieds_send_invites', '_wpnonce_send_invites' ) )
				return false;
				
			if (bp_classified_is_published($bp->classifieds->current_classified)) {

				// Send the invites.
				classifieds_send_invites( $bp->loggedin_user->id, $bp->classifieds->current_classified->ID, $_POST['friends']);
				bp_core_add_message( __('Classified invites sent.', 'classifieds') );
				bp_core_redirect( bp_get_classified_permalink( $bp->classifieds->current_classified ) );
			}else {
				bp_core_add_message( __( 'This classified is not yet published.  Maybe you have to wait moderation before sending invitations to your friends !', 'classifieds' ) );
			}
			

		} else {
			// Show send invite page
			bp_core_load_template( apply_filters( 'classifieds_template_classified_invite', 'classifieds/single/home' ) );
		}
	}
}

/********************************************************************************
 * Action Functions
 *
 * Action functions are exactly the same as screen functions, however they do not
 * have a template screen associated with them. Usually they will send the user
 * back to the default screen after execution.
 */
 
function classifieds_action_follow() {
	global $bp;

	if ( $bp->current_component != $bp->classifieds->slug || $bp->action_variables[0] != __('follow','classifieds-slugs' ))
		return false;

	$potential_classified_id = $bp->current_action;
	
	if ( !is_numeric( $potential_classified_id ) || !isset( $potential_classified_id ) )
		return false;

	if (!bp_classified_can_follow($potential_classified_id)) return false;

	$followed = classifieds_is_follower( $bp->loggedin_user->id, $potential_classified_id );

	if (!$followed) {

		if ( !check_admin_referer( 'classifieds_follow_classified' ) )
			return false;

		if ( !classifieds_follow_classified($potential_classified_id,$bp->loggedin_user->id) ) {
			bp_core_add_message( __( 'Classified could not be followed.', 'classifieds' ), 'error' );
		} else {
			bp_core_add_message( __( 'Classified followed', 'classifieds' ) );
		}
	} else {
		bp_core_add_message( __( 'You already are following this classified', 'classifieds' ), 'error' );
	}

	bp_core_redirect( wp_get_referer() );

	return false;
}
add_action( 'init', 'classifieds_action_follow' );

function classifieds_action_unfollow() {
	global $bp;

	if ( $bp->current_component != $bp->classifieds->slug || $bp->action_variables[0] != __('unfollow','classifieds-slugs' ))
		return false;
		


	$potential_classified_id = $bp->current_action;
	
	if ( !is_numeric( $potential_classified_id ) || !isset( $potential_classified_id ) )
		return false;
		
	if (!bp_classified_can_follow($potential_classified_id)) return false;

	$followed = classifieds_is_follower( $bp->loggedin_user->id, $potential_classified_id );

	if ($followed) {

		if ( !check_admin_referer( 'classifieds_unfollow_classified' ) )
			return false;

		if ( !classifieds_unfollow_classified($potential_classified_id,$bp->loggedin_user->id) ) {
			bp_core_add_message( __( 'Classified could not be unfollowed.', 'classifieds' ), 'error' );
		} else {
			bp_core_add_message( __( 'Classified unfollowed', 'classifieds' ) );
		}
	} else {
		bp_core_add_message( __( 'This classified is already unfollowed', 'classifieds' ), 'error' );
	}

	bp_core_redirect( wp_get_referer() );

	return false;
}
add_action( 'init', 'classifieds_action_unfollow' );

function classifieds_action_publish() {
	global $bp;

	if ( $bp->current_component != $bp->classifieds->slug || $bp->action_variables[0] != __('publish','classifieds-slugs' ))
		return false;

	$potential_classified_id = $bp->current_action;
	
	if ( !is_numeric( $potential_classified_id ) || !isset( $potential_classified_id ) )
		return false;
		
	$classified = new BP_Classifieds_Classified( $potential_classified_id );
	$published = bp_classified_is_published($classified);

	if (!$published) {
	
		if (!bp_classifieds_user_can_publish_classified($classified)) return false;

		if ( !check_admin_referer( 'classifieds_publish_classified' ) )
			return false;

		if ( !classifieds_publish_classified($potential_classified_id,$bp->loggedin_user->id) ) {
			bp_core_add_message( __( 'Classified could not be published.', 'classifieds' ), 'error' );
		} else {
			bp_core_add_message( __( 'Classified published', 'classifieds' ) );
		}
	} else {
		bp_core_add_message( __( 'This classified is already published', 'classifieds' ), 'error' );
	}

	bp_core_redirect( wp_get_referer() );
	
	return false;
}
add_action( 'init', 'classifieds_action_publish' );

function classifieds_action_republish() {
	global $bp;

	if ( $bp->current_component != $bp->classifieds->slug || $bp->action_variables[0] != __('republish','classifieds-slugs' ))
		return false;

	$potential_classified_id = $bp->current_action;
	
	if ( !is_numeric( $potential_classified_id ) || !isset( $potential_classified_id ) )
		return false;
		
	if (!bp_classified_user_can('Classifieds Republish Classifieds')) return false;
	
	$classified = new BP_Classifieds_Classified( $potential_classified_id );


	$unactive = bp_classified_is_unactive($classified);

	if ($unactive) {

		if ( !check_admin_referer( 'classifieds_republish_classified' ) )
			return false;

		if ( !classifieds_republish_classified($potential_classified_id,$bp->loggedin_user->id) ) {
			bp_core_add_message( __( 'Classified could not be republished.', 'classifieds' ), 'error' );
		} else {
			bp_core_add_message( __( 'Classified republished', 'classifieds' ) );
		}
	} else {
		bp_core_add_message( __( 'This classified is already active', 'classifieds' ), 'error' );
	}

	bp_core_redirect( wp_get_referer() );
	
	return false;
}
add_action( 'init', 'classifieds_action_republish' );

function classifieds_action_delete() {
	global $bp;

	if ( $bp->current_component != $bp->classifieds->slug || $bp->action_variables[0] != __('delete','classifieds-slugs' ))
		return false;

	$potential_classified_id = $bp->current_action;
	
	if ( !is_numeric( $potential_classified_id ) || !isset( $potential_classified_id ) )
		return false;
		
	$classified = new BP_Classifieds_Classified( $potential_classified_id );

	if (!bp_classifieds_user_can_delete_classified($classified)) return false;


	if ( !check_admin_referer( 'classifieds_delete_classified' ) )
		return false;

	if ( !classifieds_delete_classified($potential_classified_id) ) {
		bp_core_add_message( __( 'Classified could not be deleted.', 'classifieds' ), 'error' );
	} else {
		bp_core_add_message( __( 'Classified deleted', 'classifieds' ) );
	}

	bp_core_redirect( wp_get_referer() );

	return false;
}
add_action( 'init', 'classifieds_action_delete' );


function classifieds_add_js() {
	if (bp_is_user_classifieds()) {
		global $bp;

			wp_enqueue_script( 'bp-classifieds-ajax', apply_filters('bp_classifieds_enqueue_url',get_stylesheet_directory_uri() . '/classifieds/_inc/js/ajax.js'), array('dtheme-ajax-js') );
			wp_enqueue_script( 'jquery.json', apply_filters('bp_classifieds_enqueue_url',get_stylesheet_directory_uri() . '/classifieds/_inc/js/jquery.json-2.2.min.js'),array('jquery'), '2.2' );

		
			if (($bp->classifieds->options['tags_suggestion']) && ((( bp_is_classified_creation_step( __('classified-settings','classifieds-slugs' ) ) )) || (bp_is_classified_admin_screen( __('edit-details','classifieds-slugs' ) ) ))) {
			wp_enqueue_script( 'jquery.autocomplete', apply_filters('bp_classifieds_enqueue_url',get_stylesheet_directory_uri() . '/classifieds/_inc/js/jquery-autocomplete/jquery.autocomplete.pack.js'),array('jquery'), '1.1' );
		}
	}
}

add_action( 'wp_print_scripts', 'classifieds_add_js', 1 );

function classifieds_add_js_head() {
	global $bp;
	if (($bp->classifieds->options['tags_suggestion']) && ((( bp_is_classified_creation_step( __('classified-settings','classifieds-slugs' ) ) )) || (bp_is_classified_admin_screen( __('edit-details','classifieds-slugs' ) ) ))) {
		?>
		<script type="text/javascript">
		//<![CDATA[
		jQuery(document).ready( function() {
			jQuery('#classified-tags').autocomplete("<?php echo get_blog_option($bp->classifieds->options['blog_id'],'siteurl'); ?>/wp-admin/admin-ajax.php?action=ajax-tag-search&tax=post_tag", {
				width: jQuery(this).width,
				multiple: true,
				multipleSeparator: ",",
				matchContains: true,
				minChars: 3,
			});
		});

		//]]>
		</script>
		<?php
	}
}

add_action( 'wp_head', 'classifieds_add_js_head');

function classifieds_add_css() {
	global $bp;
	
	if (bp_is_user_classifieds()) {
		wp_enqueue_style( 'bp-classifieds-screen', apply_filters('bp_classifieds_enqueue_url',get_stylesheet_directory_uri() . '/classifieds/style.css'));
		
		if (($bp->classifieds->options['tags_suggestion']) && ((( bp_is_classified_creation_step( __('classified-settings','classifieds-slugs' ) ) )) || (bp_is_classified_admin_screen( __('edit-details','classifieds-slugs' ) ) ))) {
			wp_enqueue_style( 'jquery.autocomplete', apply_filters('bp_classifieds_enqueue_url',get_stylesheet_directory_uri() . '/classifieds/_inc/js/jquery-autocomplete/jquery.autocomplete.css') );
		}
	}
}
add_action( 'wp_print_styles', 'classifieds_add_css' );


/*** Classified Fetching, Filtering & Searching  *************************************/


//checks the sent vars to build the new url
function classifieds_form_args() {
	global $bp;
	global $classifieds_query_args;
	
	if ( $bp->current_component != BP_CLASSIFIEDS_SLUG ) return false;

	$action = classifieds_get_current_action();
	if ($action)
		$classifieds_query_args['action_tag']=$action;
		
	$categories = classifieds_get_current_categories_ids();
	if ($categories)
	$classifieds_query_args['categories']=$categories;
	

	$tag = classifieds_get_current_tag();
	if ($tag) {
		$classifieds_query_args['tag']=$tag;
	}


	$filter=$_REQUEST['s'];
	if (($filter) && ($filter!=__( 'Search anything...', 'buddypress' ))) {
		$classifieds_query_args['s']=$args['s']=$filter;
	}

	$classifieds_query_args = apply_filters('classifieds_form_args',$classifieds_query_args);

	//build GET params for redirection url
	if ($_POST['classifieds_search_submit']) {
	
		//base link
		$link['base'] = $bp->root_domain . '/' . $bp->classifieds->slug;
		
		//action
		if ($classifieds_query_args['action_tag']) {
			$link['action_tag'].=$classifieds_query_args['action_tag']->slug;
		}
		
		//categories
		if ($classifieds_query_args['categories']) {
			if (count($classifieds_query_args['categories'])==1) {
				$category = new BP_Classifieds_Categories($classifieds_query_args['categories'][0]);
				if ($category->ID) 
					$args['categories']=$category->ID; //do not use cat permalink or the children checkboxes will be checked.
			}else {
				$args['categories']=classifieds_format_multiple_categories($classifieds_query_args['categories']);
			}
		}
		
		//action
		if ($classifieds_query_args['tag']) {
			$link['tag'].=$classifieds_query_args['tag']->slug;
		}

		if (!empty($args)) {

			$args_n=0;

			foreach($args as $arg=>$value) {
				if ($args_n==0) {
					$link['args'].='?';
				}else {
					$link['args'].='&';
				}
				$link['args'].=$arg.'='.$value;
				$args_n++;
			}
		}
		
		$link_url=implode('/',$link);

			bp_core_redirect( $link_url ); //redirect to a new built url (args will be as $_GET now)
		
	}
}


add_action('wp','classifieds_form_args');


function classifieds_format_multiple_categories($array) {
	$categories = implode(',',$array);
	return apply_filters('classifieds_format_multiple_categories',$categories);
}

function classifieds_extract_url_action($args=false) {
	$action = classifieds_get_current_action();	
	if ($action)
		return classifieds_get_current_action_id($action);

}
function classifieds_extract_url_cats($args=false) {
	$cats = classifieds_get_current_categories_ids();	

	if ($cats) 
		return $cats;
}
function classifieds_extract_url_tag($args=false) {
	//TO FIX : DO NOT RETURN IT, WHY ?
	$tag=classifieds_get_current_tag();


	if ($tag)
		return classifieds_get_current_tag_id($tag);
}

//get action from request or permalink
function classifieds_get_current_action() {
	global $bp;
	
	if (bp_classifieds_is_actions_enabled()) {
	
		//ajax request
		//if ($bp->ajax_querystring) {
		
		$ajax_args= array();
		parse_str( $bp->ajax_querystring, $ajax_args );

			if ($ajax_args['action_tag']) {
				$action_id=$ajax_args['action_tag'];
			//}

		}elseif ($_REQUEST['action_tag']) {
			//search
			if ($_POST['filter-actions'] && $_POST['action_tag']) {
				$action_id = $_POST['action_tag'];
				
			//param
			}elseif ($_GET['action_tag']) {
				$action_id = $_GET['action_tag'];
			}

		//permalink
		}elseif ($bp->current_action) {
			$action_id = BP_Classifieds_Tags::get_id_from_slug($bp->current_action);
		}
		
		$action = new BP_Classifieds_Tags($action_id);

		if ($action->term_id) {
			return apply_filters('classifieds_get_current_action',$action);
		}
		return false;
	}
}
	function classifieds_get_current_action_id() {
		$action = classifieds_get_current_action();
		return $action->term_id;
	}

function classifieds_get_current_categories() {

	$categories_ids = classifieds_get_current_categories_ids();
	
	if (!$categories_ids) return false;


	return apply_filters('classifieds_get_current_categories',$categories_ids);
	
}

function classifieds_get_current_tag() {

	global $bp;

		//ajax request
		//if ($bp->ajax_querystring) {
		$ajax_args= array();
		parse_str( $bp->ajax_querystring, $ajax_args );

			if ($ajax_args['tag']) {
				$tag_id=$ajax_args['tag'];
			//}

		}elseif ($_REQUEST['tag']) {
			//search
			if ($_POST['filter-tags'] && $_POST['tag']) {
				$tag_id = $_POST['tag'];
				
			//param
			}elseif ($_GET['tag']) {
				$tag_id = $_GET['tag'];
			}

		//permalink
		
		}elseif ($bp->current_action==__('tag','classifieds-slugs')) {

			$tag_id = BP_Classifieds_Tags::get_id_from_slug($bp->action_variables[0]);

		}

		$tag = new BP_Classifieds_Tags($tag_id);

		if ($tag->term_id) {
			return apply_filters('classifieds_get_current_tag',$tag);
		}
		return false;
	
}
	function classifieds_get_current_tag_id() {
		$tag = classifieds_get_current_tag();
		return $tag->term_id;
	}


	//get single or multiple categories from request or permalink
	function classifieds_get_current_categories_ids() {
		global $bp;
		
		if (!bp_is_classifieds_directory()) return false;

		//CATEGORIES

		//ajax request
		//if ($bp->ajax_querystring) {
		$ajax_args= array();
		parse_str( $bp->ajax_querystring, $ajax_args );

			if ($ajax_args['cats']) {
				if (!is_array($ajax_args['cats']))
					$categories_ids=explode(',',$ajax_args['cats']);
			//}

		}elseif ($_REQUEST['categories']) { //get|post request
			//search
			if ($_POST['classifieds_filter_cats'] && $_POST['categories']) {
				$categories_ids = $_POST['categories'];

			//param
			}elseif ($_GET['categories']) {

				$categories_ids = explode(',',$_GET['categories']);
			}
		}else { //permalink
		
			if (!$bp->action_variables) {
				$category_slug=$bp->current_action;
			}else {
				$category_slug= end($bp->action_variables);
			}

			if (!$category_slug) return false;
			

			
			$category = BP_Classifieds_Categories::get_id_from_slug($category_slug);
			
			if (!$category) return false;
			
			//A SINGLE CATEGORY IS CALLED; FETCH THE CHILDRENS TOO
				
			$categories_ids[] = $category->ID;

			if ($children = BP_Classifieds_Categories::get_children($category->ID)){
			
				foreach ($children as $child) {
					$children_ids[]=$child->ID;
				}
				$categories_ids=array_merge($categories_ids,$children_ids);

			}

		
		}

		return apply_filters('classifieds_get_current_categories_ids',(array)$categories_ids);
	}
	
function classifieds_show_advanced_search(){

	if (classifieds_get_current_categories_ids()) $show=true;
	
	return apply_filters('classifieds_show_advanced_search',$show);

}
	
//TO FIX : FIND SOMETHING BETTER ?
//used to sort the classifieds by category when doing a multiple category search
function classifieds_sort_by_category() {
	global $bp;
	global $wpdb;
	global $classifieds_template;

	

	$array_categories = BP_Classifieds_Categories::get_children();

	$treeset = new TreeSet();
	$ordered_cats = $treeset -> reindexTree($treeset -> buildTree($array_categories));

	foreach ($ordered_cats as $cat) {

		foreach ($classifieds_template->classifieds as $classified) {
			
			//TO FIX ? : if multiple categories ?
			if ($cat->ID==$classified->categories[0]) {
				$ordered_classifieds[]=$classified;
			}
		}
	}

	$classifieds_template->classifieds = $ordered_classifieds;

}

/*** Classified Invitations *********************************************************/

function classifieds_send_invites( $user_id, $classified_id, $invited_users=false) {
	global $bp;
	
	if (empty($invited_users)) return false;
	
	require_once ( BP_CLASSIFIEDS_PLUGIN_DIR . '/bp-classifieds-notifications.php' );
	
	if ( !$user_id )
		$user_id = $bp->loggedin_user->id;

	// Send friend invites.
	$classified = new BP_Classifieds_Classified( $classified_id, false);

	for ( $i = 0; $i < count( $invited_users ); $i++ ) {
		classifieds_notification_classified_invite( $classified, $invited_users[$i], $user_id ); //$invited_users[$i]= user_id
	}
	
	do_action( 'classifieds_send_invites', $bp->classifieds->current_classified->ID, $invited_users );
}

function classifieds_get_invites_for_classified( $user_id, $classified_id ) {
	return BP_Classifieds_Classified::get_invites( $user_id, $classified_id );
}

function classifieds_count_invitable_friends( $user_id, $classified_id ) {
	return classifieds_get_invitable_friend_count( $user_id, $classified_id );
}

	function classifieds_get_invitable_friend_count( $user_id, $classified_id ) {
		global $wpdb, $bp;

		$friend_ids = BP_Friends_Friendship::get_friend_user_ids( $user_id );
		
		$invitable_count = 0;
		for ( $i = 0; $i < count($friend_ids); $i++ ) {
			
			if ( classifieds_is_follower( (int)$friend_ids[$i], $classified_id ) )
				continue;
				
			$invitable_count++;
		}

		return $invitable_count;
	}
	
function bp_classified_send_invite_form_action( $deprecated = false ) {
	echo bp_get_classified_send_invite_form_action();
}
	function bp_get_classified_send_invite_form_action( $classified = false ) {
		global $classifieds_template, $bp;

		if ( !$classified )
			$classified =& $classifieds_template->classified;

		return apply_filters( 'bp_classified_send_invite_form_action', bp_get_classified_permalink( $classified ) . __('send-invites','classifieds-slugs').'/'.__('send','classifieds-slugs') );
	}
	
function classifieds_has_friends_to_invite( $classified = false ) {
	global $classifieds_template, $bp;
	
	if ( !function_exists('friends_install') )
		return false;

	if ( !$classified )
		$classified =& $classifieds_template->classified;
	
	if ( !friends_check_user_has_friends( $bp->loggedin_user->id ) || !classifieds_count_invitable_friends( $bp->loggedin_user->id, $classified->ID ) )
		return false;
	
	return true;
}

/*** Classified Meta ****************************************************/

function classifieds_delete_classifiedmeta( $classified_id, $meta_key = false, $meta_value = false ) {
	global $wpdb, $bp;

	if ( !is_numeric( $classified_id ) )
		return false;

	$meta_key = preg_replace('|[^a-z0-9_]|i', '', $meta_key);

	if ( is_array($meta_value) || is_object($meta_value) )
		$meta_value = serialize($meta_value);

	$meta_value = trim( $meta_value );

	if ( !$meta_key ) {
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . $bp->classifieds->table_name_classifiedmeta . " WHERE post_id = %d", $classified_id ) );
	} else if ( $meta_value ) {
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . $bp->classifieds->table_name_classifiedmeta . " WHERE post_id = %d AND meta_key = %s AND meta_value = %s", $classified_id, $meta_key, $meta_value ) );
	} else {
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . $bp->classifieds->table_name_classifiedmeta . " WHERE post_id = %d AND meta_key = %s", $classified_id, $meta_key ) );
	}

	/* Delete the cached object */
	wp_cache_delete( 'bp_classifieds_classifiedmeta_' . $classified_id . '_' . $meta_key, 'bp' );

	return true;
}

function classifieds_get_classifiedmeta( $classified_id, $meta_key = '') {
	global $wpdb, $bp;

	$classified_id = (int) $classified_id;

	if ( !$classified_id )
		return false;

	if ( !empty($meta_key) ) {
		$meta_key = preg_replace('|[^a-z0-9_]|i', '', $meta_key);

		if ( !$metas = wp_cache_get( 'bp_classifieds_classifiedmeta_' . $classified_id . '_' . $meta_key, 'bp' ) ) {
			$metas = $wpdb->get_col( $wpdb->prepare("SELECT meta_value FROM " . $bp->classifieds->table_name_classifiedmeta . " WHERE post_id = %d AND meta_key = %s", $classified_id, $meta_key) );
			wp_cache_set( 'bp_classifieds_classifiedmeta_' . $classified_id . '_' . $meta_key, $metas, 'bp' );
		}
	} else {
		$metas = $wpdb->get_col( $wpdb->prepare("SELECT meta_value FROM " . $bp->classifieds->table_name_classifiedmeta . " WHERE post_id = %d", $classified_id) );
	}

	if ( empty($metas) ) {
		if ( empty($meta_key) )
			return array();
		else
			return '';
	}

	$metas = array_map('maybe_unserialize', $metas);

	if ( 1 == count($metas) )
		return $metas[0];
	else
		return $metas;
}

//to insert/update several rows of metas
/*
function classifieds_update_classifiedmetas( $classified_id, $meta_key, $array_values ) {
	$count = count($array_values);
	$result=0;
	foreach($array_values as $meta_value) {
		if (classifieds_update_classifiedmeta( $classified_id, $meta_key, $meta_value )) $result++;

	}
	
	if ($result==$count) return true;
}
*/
function classifieds_update_classifiedmeta( $classified_id, $meta_key, $meta_value) {
	global $wpdb, $bp;

	if ( !is_numeric( $classified_id ) )
		return false;

	$meta_key = preg_replace( '|[^a-z0-9_]|i', '', $meta_key );

	if ( is_string($meta_value) )
		$meta_value = stripslashes($wpdb->escape($meta_value));

	$meta_value = maybe_serialize($meta_value);

	if (empty($meta_value)) {
		return classifieds_delete_classifiedmeta( $classified_id, $meta_key );
	}

	$cur = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $bp->classifieds->table_name_classifiedmeta . " WHERE post_id = %d AND meta_key = %s", $classified_id, $meta_key ) );

	if ( !$cur ) {
		$wpdb->query( $wpdb->prepare( "INSERT INTO " . $bp->classifieds->table_name_classifiedmeta . " ( post_id, meta_key, meta_value ) VALUES ( %d, %s, %s )", $classified_id, $meta_key, $meta_value ) );
	} else if ( $cur->meta_value != $meta_value ) {
		$wpdb->query( $wpdb->prepare( "UPDATE " . $bp->classifieds->table_name_classifiedmeta . " SET meta_value = %s WHERE post_id = %d AND meta_key = %s", $meta_value, $classified_id, $meta_key ) );
	} else {
		return false;
	}

	/* Update the cached object and recache */
	wp_cache_set( 'bp_classifieds_classifiedmeta_' . $classified_id . '_' . $meta_key, $meta_value, 'bp' );

	return true;
}

function classifieds_breadcrumb_clean($breadcrumb) {
	$html = strip_tags($breadcrumb, '<a>'); 
	return $html;
}

function classifieds_plugin_is_active($file,$sitewide=true) {
	if ($sitewide){
		$plugins = get_site_option( 'active_sitewide_plugins' );
		if ( array_key_exists( $file , $plugins ) ) return true;
	}else{
		$plugins = get_option('active_plugins');
		if ( in_array( $file , $plugins ) ) return true;
	}
}

//CAPABILITIES

//adds classifieds capabilities for each role.
//checks a core capability to know if the cap must be enabled or not for the role.
function classifieds_setup_capabilities($install=true) {
	global $bp;

	$capabilities = $bp->classifieds->capabilities;

	switch_to_blog($bp->classifieds->options['blog_id']);

	global $wp_roles;

	foreach ($wp_roles->roles as $rolename=>$array) {

		$role = get_role($rolename);

		foreach ($capabilities as $cap) {
		
			if (!$install) {
				$role->remove_cap($cap['name']);
			}else {
			
				$role_caps_names = $role->capabilities;
				
				if (array_key_exists($cap['name'],$role_caps_names)) continue; //install the cap only if it not exists yet

				$check_cap = $cap['default_cap'];
				
				if($role->has_cap($check_cap)) {
					$grant=true;
				}else {
					$grant=false;
				}

				$role->add_cap($cap['name'],$grant);

			
			}
		}
	}
	
	restore_current_blog();

}

function bp_classifieds_set_moderation($bool) {
	global $bp;
	
	if ($bool)
		$grant=false;
	else
		$grant=true;
	
	
	switch_to_blog($bp->classifieds->options['blog_id']);
	
	$role = get_role('author');
	$role->add_cap('Classifieds Publish Classifieds',$grant);
	
	//REMOVE THIS LINE WHEN WE'LL MERGE THE CLASSIFIEDS DATA BLOG
	$role->add_cap('publish_posts',$grant);
	
	restore_current_blog();
	
	return $mod;
}


//check every role to see if one or more need moderation for classifieds.
function bp_classifieds_moderation_exists() {
	global $bp;
	switch_to_blog($bp->classifieds->options['blog_id']);
	
	$role = get_role('author');
	if(!$role->has_cap('Classifieds Publish Classifieds')) $mod=true;
	
	restore_current_blog();
	
	return $mod;
}


function bp_classified_user_can($capname) {
	global $bp;
	
	switch_to_blog($bp->classifieds->options['blog_id']);
	
	$can = current_user_can($capname);

	restore_current_blog();
	
	return $can;
}

function bp_classifieds_user_can_admin_classified($classified=false) {
	global $classifieds_template;

	if (bp_classified_user_can('Classifieds Edit Others Classifieds')) return true;
	
	if (!$classified)
		$classified =&$classifieds_template->classified;
		
	if (!bp_classified_is_author($classified)) return false;
	
	if (bp_classified_is_published($classified)) {
		if (bp_classified_user_can('Classifieds Edit Published Classifieds')) return true;
	}else {
		if (bp_classified_user_can('Classifieds Edit Classifieds')) return true;
	}
	
	return false;

}

function bp_classifieds_user_can_delete_classified($classified=false) {
	global $classifieds_template;


	if (bp_classified_user_can('Classifieds Edit Others Classifieds')) return true;
	
	if (!$classified)
		$classified =&$classifieds_template->classified;
		
	if (!bp_classified_is_author($classified)) return false;
	
	if (bp_classified_is_published($classified)) {
		if (bp_classified_user_can('Classifieds Delete Published Classifieds')) return true;
	}else {
		if (bp_classified_user_can('Classifieds Delete Classifieds')) return true;
	}
	
	return false;

}

function bp_classifieds_user_can_publish_classified($classified=false) {
	global $classifieds_template;

	if (bp_classified_user_can('Classifieds Edit Others Classifieds')) return true;
	
	if (!$classified)
		$classified =&$classifieds_template->classified;
		
	if (!bp_classified_is_author($classified)) return false;
	
	if (bp_classified_user_can('Classifieds Publish Classifieds')) return true;
	
	return false;

}

function bp_classifieds_user_can_upload() {

	if ((bp_classified_user_can('Classifieds Upload Images')) && (bp_classified_user_can('upload_files'))) return true;
	
		return false;

}


function classifieds_can_user_create() {
	$can_create=bp_classified_user_can('Classifieds Edit Classifieds');
	if (!$can_create) {
		add_action( 'bp_classifieds_no_capability','bp_classifieds_no_capability' );
	}
	return $can_create;
}

function bp_classified_is_author($classified=false,$user_id=false) {
	global $bp;
	global $classifieds_template;
	
	if ( !$classified )
		$classified =& $classifieds_template->classified;
		
	if ( !$user_id ) $user_id = $bp->loggedin_user->id;
		
	if ($user_id==$classified->creator_id) return true;
}

//gets the list of users who are members of the main blog and not of the classifieds data blog
function bp_classifieds_get_non_authors() {

	if (!bp_classifieds_is_setup()) return false;

	global $bp;
	
	$blog2_id=$bp->classifieds->options['blog_id'];

	$users_of_blog1 = get_users_of_blog();
	$users_of_blog2 = get_users_of_blog($blog2_id);

	foreach ($users_of_blog1 as $key=>$user)
		$blog1_users_ids[]=$user->ID;
	
	foreach ($users_of_blog2 as $key=>$user){
		$roles=unserialize($users_of_blog2[$key]->meta_value);
		$blog2_roles[$user->ID]=$roles;
	}

	$add_users=array();
	
	//check if every member of blog#1 has suffisicent role in blog#2
	foreach ($blog1_users_ids as $user_id){
		
		$user_roles_blog2=$blog2_roles[$user_id]; //if not false, user exists in datablog
		
	


		if ($user_roles_blog2) { //is blog2 member

			if (($user_roles_blog2['administrator']) || ($user_roles_blog2['editor']) || ($user_roles_blog2['author'])) { //suffisicent rights, continue
				continue;
			}else { //user exists but is not author
				$add_users[]=array(
					'id'=>$user_id,
					'reset'=>true
				);
			}
		}else { //do not exist in blog#2, add him.
			$add_users[]['id']=$user_id;
		}
	}

	return $add_users;

}


function bp_classified_add_users_to_datablog($users) {

	if (empty($users)) return false;

	global $bp;
	
	$blog_id=$bp->classifieds->options['blog_id'];
	
	foreach ($users as $user) {
		
		if ($user['reset'])
			remove_user_from_blog($user['id'], $blog_id);
			
		bp_classified_add_user_to_datablog($user['id']);
	}
}


function bp_classified_add_user_to_datablog($user_id) {

	if (!bp_classifieds_is_setup()) return false;

	global $bp;
	
	$blog_id=$bp->classifieds->options['blog_id'];
	
	add_user_to_blog($blog_id, $user_id, 'author' ); //then give him the author role
}

function bp_classified_remove_user_from_datablog($user_id) {

	global $blog_id;
	
	if ($blog_id!=BP_ROOT_BLOG) return false;

	if (!bp_classifieds_is_setup()) return false;

	global $bp;
	
	$blog_id=$bp->classifieds->options['blog_id'];
	
	remove_user_from_blog($user_id,$blog_id); //then give him the author role
}

//auto add user(s) to classifieds data blog
add_action('wpmu_new_user','bp_classified_add_user_to_datablog',10,2);
add_action('delete_user','bp_classified_remove_user_from_datablog',10,2);


/*** Classified User Follower Checks ************************************************/

function classifieds_is_follower( $user_id=false, $classified_id=false) {
	global $wpdb, $bp;
	global $classifieds_template;
	
	if (!$user_id) 
		$user_id = $bp->loggedin_user->id;
		
	if (!$classified_id)
		$classified_id=& $classifieds_template->classified->ID;
	
	//TO FIX use classifieds_get_classifiedmeta
	$sql = $wpdb->prepare( "SELECT * FROM {$bp->classifieds->table_name_classifiedmeta} WHERE meta_value = %d AND post_id = %d AND meta_key = 'classified_follower'", $user_id, $classified_id );

	$result = $wpdb->get_var( $sql );	

	return $result;
}


/********************************************************************************
 * Activity & Notification Functions
 *
 * These functions handle the recording, deleting and formatting of activity and
 * notifications for the user and for this specific component.
 */

function classifieds_register_activity_actions() {
	global $bp;

	if ( !function_exists( 'bp_activity_set_action' ) )
		return false;

	bp_activity_set_action( $bp->classifieds->id, 'published_classified', __( 'Published a classified', 'classifieds' ) );
	bp_activity_set_action( $bp->classifieds->id, 'followed_classified', __( 'Followed a classified', 'classifieds' ) );
	//bp_activity_set_action( $bp->classifieds->id, 'new_comment', __( 'New classified comment', 'classifieds' ) );

	do_action( 'classifieds_register_activity_actions' );
}
add_action( 'plugins_loaded', 'classifieds_register_activity_actions' );


function classifieds_record_activity( $args = '' ) {
	global $bp;

	if ( !function_exists( 'bp_activity_add' ) )
		return false;

	/* If the classified is not publish, hide the activity sitewide. */
	if ( 'publish' == $bp->classifieds->current_classified->status )
		$hide_sitewide = false;
	else
		$hide_sitewide = true;
		
	$defaults = array(
		'user_id' => $bp->loggedin_user->id,
		'action' => '',
		'content' => '',
		'primary_link' => '',
		'component' => $bp->classifieds->id,
		'type' => false,
		'item_id' => false,
		'secondary_item_id' => false,
		'recorded_time' => gmdate( "Y-m-d H:i:s" ),
		'hide_sitewide' => $hide_sitewide
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	extract( $r, EXTR_SKIP );

	return bp_activity_add( array( 'user_id' => $user_id, 'action' => $action, 'content' => $content, 'primary_link' => $primary_link, 'component' => $component, 'type' => $type, 'item_id' => $item_id, 'secondary_item_id' => $secondary_item_id, 'hide_sitewide' => $hide_sitewide ) );
}

function classifieds_update_last_activity( $classified_id ) {
	classifieds_update_classifiedmeta( $classified_id, 'last_activity', gmdate( "Y-m-d H:i:s" ) );
}

//add_action( 'classifieds_deleted_comment', 'classifieds_update_last_activity' );
//add_action( 'classifieds_new_comment', 'classifieds_update_last_activity' );
add_action( 'classifieds_followed_classified', 'classifieds_update_last_activity' );
add_action( 'classifieds_unfollowed_classified', 'classifieds_update_last_activity' );
add_action( 'classifieds_published_classified', 'classifieds_update_last_activity' );
add_action( 'classifieds_republished_classified', 'classifieds_update_last_activity' );





function classifieds_format_notifications( $action, $item_id, $secondary_item_id, $total_items ) {
	global $bp;
	
	//TO FIX : if ( (int)$total_items > 1 ) { ?


	
	switch ( $action ) {
		
		case 'classified_invite':
			$classified_id = $item_id;

			$classified = new BP_Classifieds_Classified( $classified_id, false);

			$friend = new BP_Core_User($secondary_item_id);

			
			return apply_filters( 'bp_classifieds_single_classified_invite_notification', '<a href="' . bp_get_classified_permalink($classified) .'" title="' . __( 'Classified Invites', 'classifieds' ) . '">' . sprintf( __('%s suggests you to check the classified: "%s"', 'classifieds' ),$friend->fullname,$classified->name ) . '</a>', $classified->name );
		break;
		
		case 'classified_pending':
			if ( (int)$total_items > 1 ) {
				$blog_admin_url = get_blog_option($bp->classifieds->options['blog_id'],'siteurl ').'/wp-admin/edit.php';
				return apply_filters( 'bp_classifieds_multiple_classifieds_pending_notification', '<a href="' . $blog_admin_url . '" title="' . __( 'Classifieds Pending', 'classifieds' ) . '">' . 'New classified are pending!' . '</a>');
			}else{
				$classified_id = $item_id;
				$classified = new BP_Classifieds_Classified( $classified_id, false);
				$author = new BP_Core_User($classified->creator_id);
				
				//TO FIX : no slug when pending - see function save, the problem is not here
				return apply_filters( 'bp_classifieds_single_classified_pending_notification', '<a href="' . bp_get_classified_permalink($classified).'admin' . '" title="' . __( 'Classified Pending', 'classifieds' ) . '">' . sprintf( __('A new classified "%s" by %s is pending!', 'classifieds' ), $classified->name,$author->fullname ) . '</a>', $classified->name);
			}
		break;
		
		case 'classified_published':

			if ( (int)$total_items > 1 ) {
			
				$user_url = $bp->loggedin_user->domain . $bp->classifieds->slug . '/'.__('my-classifieds').'/'.__('publish','classifieds-slugs');
			
				return apply_filters( 'bp_classifieds_multiple_classifieds_published_notification', '<a href="' . $user_url . '" title="' . __( 'Classifieds Published', 'classifieds' ) . '">' . __('Several of your classifieds have been validated and published!', 'classifieds' ) . '</a>');
			
			}else {
		
			$classified_id = $item_id;

			$classified = new BP_Classifieds_Classified( $classified_id, false);
			
			
			return apply_filters( 'bp_classifieds_single_classified_published_notification', '<a href="' . bp_get_classified_permalink($classified) . '" title="' . __( 'Classified Published', 'classifieds' ) . '">' . sprintf( __('Your classified "%s" has been validated and published !', 'classifieds' ), $classified->name ) . '</a>', $classified->name );
			
			}
		break;
		
		case 'classified_republished':
		
			

			if ( (int)$total_items > 1 ) {
			
				$user_url = $bp->loggedin_user->domain . $bp->classifieds->slug . '/'.__('my-classifieds').'/'.__('publish','classifieds-slugs');
			
				return apply_filters( 'bp_classifieds_multiple_classifieds_republished_notification', '<a href="' . $user_url . '" title="' . __( 'Classifieds Republished', 'classifieds' ) . '">' . __('Several of your classifieds have been republished!', 'classifieds' ) . '</a>');
			
			}else {

			$classified_id = $item_id;
			$classified = new BP_Classifieds_Classified( $classified_id, false);

			return apply_filters( 'bp_classifieds_single_classified_republished_notification', '<a href="' . bp_get_classified_permalink($classified) . '" title="' . __( 'Classified Republished', 'classifieds' ) . '">' . sprintf( __('Your classified "%s" has been republished for %d days !', 'classifieds' ), $classified->name, $bp->classifieds->options['days_active'] ) . '</a>', $classified->name );
			
			}
		break;
	}

	do_action( 'classifieds_format_notifications', $action, $item_id, $secondary_item_id, $total_items );
	
	return false;
}

/********************************************************************************
 * Business Functions
 *
 * Business functions are where all the magic happens in BuddyPress. They will
 * handle the actual saving or manipulation of information. Usually they will
 * hand off to a database class for data access, then return
 * true or false on success or failure.
 */
 
function classifieds_follow_classified( $classified_id, $user_id = false ) {
	global $bp;
	global $wpdb;
		
	if ( !$user_id )
		$user_id = $bp->loggedin_user->id;
		
	if (!$user_id) return false;
		
		
	$classified = new BP_Classifieds_Classified($classified_id);
	
	if (!$classified) return false;
	//TO FIX use classifieds_update_classifiedmeta
	$result = $wpdb->query( $wpdb->prepare( "INSERT INTO {$bp->classifieds->table_name_classifiedmeta} (post_id,meta_key,meta_value) VALUES (%d,'classified_follower',%d)", $classified->ID,$user_id ) );	
	
	if (!$result) return false;

	/* Modify classified user count */
	$meta_followers_count = classifieds_update_classifiedmeta(  $classified->ID, 'total_follower_count', (int) classifieds_get_classifiedmeta( $classified->ID, 'total_follower_count') + 1 );
	
	/* Record this in activity streams */
	
	classifieds_record_activity( array(
		'action' => apply_filters( 'classifieds_activity_followed_classified', sprintf( __( '%s follows the classified %s', 'classifieds'), bp_core_get_userlink( $user_id ), '<a href="' . bp_get_classified_permalink( $classified ) . '">' . attribute_escape( $classified->name ) . '</a>' ), $user_id, &$classified ),
		'type' => 'followed_classified',
		'item_id' => $classified->ID
		//,'hide_sitewide' => true
	) );

	do_action( 'classifieds_followed_classified', $classified->ID, $user_id );
	
	return $result;
}

function classifieds_unfollow_classified( $classified_id, $user_id = false ) {
	global $bp;
	global $wpdb;
		
	if ( !$user_id )
		$user_id = $bp->loggedin_user->id;
		
	if (!$user_id) return false;
		
		
	$classified = new BP_Classifieds_Classified( $classified_id);
	
	if (!$classified) return false;
	//TO FIX use classifieds_delete_classifiedmeta
	$result = $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->classifieds->table_name_classifiedmeta} WHERE post_id=%d AND meta_key='classified_follower' AND meta_value=%d", $classified->ID,$user_id ) );	
	
	if (!$result) return false;
	
	/* Modify classified user count */
	$meta_followers_count = classifieds_update_classifiedmeta(  $classified->ID, 'total_follower_count', (int) classifieds_get_classifiedmeta( $classified->ID, 'total_follower_count') -1);
	
	/* Record this in activity streams */
	
	classifieds_record_activity( array(
		'action' => apply_filters( 'classifieds_activity_unfollowed_classified', sprintf( __( '%s unfollows the classified %s', 'classifieds'), bp_core_get_userlink( $user_id ), '<a href="' . bp_get_classified_permalink( $classified ) . '">' . attribute_escape( $classified->name ) . '</a>' ), $user_id, &$classified ),
		'type' => 'unfollowed_classified',
		'item_id' => $classified->ID
		//,'hide_sitewide' => true
	) );

	do_action( 'classifieds_unfollowed_classified', $classified->ID, $user_id );
	
	return $result;
}

function classifieds_publish_classified( $classified_id ) {
	global $bp;
	global $wpdb;
	
	$classified = new BP_Classifieds_Classified( $classified_id );
	
	if (!$classified) return false;
	
	if (!bp_classifieds_user_can_publish_classified($classified)) return false;
	
	$classified->status = 'publish';
	$classified->date_created = time();
	$author_id = $classified->creator_id;

	if ( !$classified->save() )
		return false;

	//remove single pending notification
	if (bp_classifieds_moderation_exists()) {
		bp_core_delete_all_notifications_by_type( $classified->ID, $bp->classifieds->slug, 'classified_pending' );
	}
	
	
	/* Once we compelete all steps, record the classified creation in the activity stream. */
	
	classifieds_record_activity( array(
		'action' => apply_filters( 'classifieds_activity_published_classified', sprintf( __( '%s published the classified %s', 'classifieds'), bp_core_get_userlink( $author_id ), '<a href="' . bp_get_classified_permalink( $classified ) . '">' . attribute_escape( $classified->name ) . '</a>' ), $author_id, &$classified ),
		'type' => 'published_classified',
		'item_id' => $classified->ID,
	) );

	/* Post an email notification if settings allow */
	
	
	if (bp_classifieds_moderation_exists()) {//do not show when moderation is not enabled
		require_once ( BP_CLASSIFIEDS_PLUGIN_DIR . '/bp-classifieds-notifications.php' );
		classifieds_notification_classified_published( $classified, $author_id );
	}

	do_action( 'classifieds_published_classified', $classified->ID, $author_id );

	return true;
}



function classifieds_republish_classified( $classified_id ) {
	global $bp;
	global $wpdb;
		
	if ((!bp_classified_user_can('Classifieds Republish Classifieds')) && (!bp_classified_user_can('Classifieds Edit Others Classifieds'))) return false;
		
	$classified = new BP_Classifieds_Classified( $classified_id );
	
	if (!$classified) return false;
	
	$classified->date_created = time();
	$author_id = $classified->creator_id;
	
	if ( !$classified->save() )
		return false;
	
	//record in activity stream

	classifieds_record_activity( array(
		'action' => apply_filters( 'classifieds_activity_republished_classified', sprintf( __( '%s republished the classified %s', 'classifieds'), bp_core_get_userlink( $author_id ), '<a href="' . bp_get_classified_permalink( $classified ) . '">' . attribute_escape( $classified->name ) . '</a>' ), $author_id, &$classified ),
		'type' => 'republished_classified',
		'item_id' => $classified->ID
	) );


	
	/* Post an email notification if settings allow */
	require_once ( BP_CLASSIFIEDS_PLUGIN_DIR . '/bp-classifieds-notifications.php' );
	
	classifieds_notification_classified_republished( $classified, $author_id );
	

	/* Modify classified meta */
	classifieds_update_classifiedmeta( $bp->classifieds->new_classified_id, 'republished', 1 );

	do_action( 'classifieds_republished_classified', $classified->ID, $user_id );

	return true;
}

function classifieds_delete_classified( $classified_id ) {
	global $bp;
	global $wpdb;

	// Get the classified object
	$classified = new BP_Classifieds_Classified( $classified_id );
	
	if (!bp_classifieds_user_can_delete_classified($classified)) return false;
	
	
	if ( !$classified->delete() )
		return false;


	/* Delete all classified activity from activity streams */
	if ( function_exists( 'bp_activity_delete_by_item_id' ) ) {
		bp_activity_delete_by_item_id( array( 'item_id' => $classified->ID, 'component' => $bp->classifieds->id) );
	}

	// Remove all notifications for any user belonging to this classified
	$delete_notifications = bp_core_delete_all_notifications_by_type( $classified->ID, $bp->classifieds->slug );

	do_action( 'classifieds_deleted_classified', $classified->ID );
	
	return true;
}

/*** Classified Creation, Editing & Deletion *****************************************/

function classifieds_create_classified( $args = '' ) {
	global $bp;
	
	if ( defined( 'BP_CLASSIFIEDS_DEBUG' ) )$bp->classifieds->debug->log($args,'classifieds_create_classified');



	/**
	 * Possible parameters (pass as assoc array):
	 *	'classified_id'
	 *	'creator_id'
	 *	'name'
	 *	'description'
	 *	'tags'
	 *	'slug'
	 *	'status'
	 *	'action'
	 *	'categories'
	 *	'enable_comments'
	 *	'date_created'
	 */
	 
	extract( $args, EXTR_SKIP );

	if ( $classified_id ) {
		$classified = new BP_Classifieds_Classified( $classified_id );
	}else {
		$classified = new BP_Classifieds_Classified();
	}

	
	if ( $creator_id ) {
		$classified->creator_id = $creator_id;
	} else {
		$classified->creator_id = $bp->loggedin_user->id;
	}
	
	if ( isset( $name ) )
		$classified->name = $name;
	
	if ( isset( $description ) )
		$classified->description = $description;

	if ( isset( $status ) )
		$classified->status = $status;
		
	if ( isset( $enable_comments ) )
		$classified->comment_status = 'open';
	else
		$classified->comment_status = 'closed';

	
	if ( isset( $action) ) {
		$classified->action = $action; 
	}
	
	
		
	if ( isset( $categories ) )
		$classified->categories = $categories;
		
	if ( isset( $tags ) ) {
		//$tags = explode(',',$tags); //string slugs
		$classified->tags = $tags;
	}

	if ( isset( $date_created ) )
		$classified->date_created = $date_created;
	
	if ( !$classified->save() )
		return false;

	return $classified->ID;
}

function classifieds_days_since_date($older_date,$newer_date=null) {
	global $bp;
	
	
	
	$chunks = array(
	array( 60 * 60 * 24 * 365 , __( 'year', 'buddypress' ), __( 'years', 'buddypress' ) ),
	array( 60 * 60 * 24 * 30 , __( 'month', 'buddypress' ), __( 'months', 'buddypress' ) ),
	array( 60 * 60 * 24 * 7, __( 'week', 'buddypress' ), __( 'weeks', 'buddypress' ) ),
	array( 60 * 60 * 24 , __( 'day', 'buddypress' ), __( 'days', 'buddypress' ) ),
	array( 60 * 60 , __( 'hour', 'buddypress' ), __( 'hours', 'buddypress' ) ),
	array( 60 , __( 'minute', 'buddypress' ), __( 'minutes', 'buddypress' ) ),
	array( 1, __( 'second', 'buddypress' ), __( 'seconds', 'buddypress' ) )
	);

	if ( !is_numeric( $older_date ) ) {
		$time_chunks = explode( ':', str_replace( ' ', ':', $older_date ) );
		$date_chunks = explode( '-', str_replace( ' ', '-', $older_date ) );

		$older_date = gmmktime( (int)$time_chunks[1], (int)$time_chunks[2], (int)$time_chunks[3], (int)$date_chunks[1], (int)$date_chunks[2], (int)$date_chunks[0] );
	}

	/* $newer_date will equal false if we want to know the time elapsed between a date and the current time */
	/* $newer_date will have a value if we want to work out time elapsed between two known dates */
	$newer_date = ( !$newer_date ) ? gmmktime( gmdate( 'H' ), gmdate( 'i' ), gmdate( 's' ), gmdate( 'n' ), gmdate( 'j' ), gmdate( 'Y' ) ) : $newer_date;

	/* Difference in seconds */
	$seconds = (int)$newer_date - $older_date;
	
	$days = round($seconds/86400);
	
	return $days;

}

function classifieds_edit_base_classified_details( $classified_id, $classified_name, $classified_desc, $enable_comments) {
	global $bp;

	if ( empty( $classified_name ) || empty( $classified_desc ) )
		return false;
	
	$classified = new BP_Classifieds_Classified( $classified_id, false);
	$classified->name = $classified_name;
	$classified->description = $classified_desc;

	if ( isset( $enable_comments ) )
		$classified->comment_status = 'open';
	else
		$classified->comment_status = 'closed';
	

	if ( !$classified->save() )
		return false;

	do_action( 'classifieds_details_updated', $classified->ID );
	
	return true;
}

function classifieds_edit_classified_settings( $classified_id, $action, $categories, $tags) {
	global $bp;
	
	$classified = new BP_Classifieds_Classified( $classified_id, false);
	$classified->action = $action;
	$classified->categories = $categories;
	
	$classified->tags = $tags;
	
	if ( !$classified->save() )
		return false;
	
	do_action( 'classifieds_settings_updated', $classified->ID );
	
	return true;
}

//MOVE ELSEWHERE ?
function classifieds_members_content() {
	if ( bp_is_user_classifieds() ) :
		bp_classifieds_locate_template( array( 'classifieds/single/classifieds.php' ), true );
	endif;
}

add_action('bp_after_member_body','classifieds_members_content');

//TO FIX MOVE INSIDE Classifieds Template Class ?




?>