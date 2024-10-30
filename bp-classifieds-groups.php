<?php

class Classified_Groups_Extension extends BP_Classified_Extension {

	function classified_groups_extension() {
		global $bp;

		$this->name = __( 'Classified Groups', 'classified-groups' );
		$this->slug = BP_GROUPS_SLUG;
		$this->nav_item_name = __( 'Groups', 'buddypress' );

		$this->create_step_position = 25;
		$this->nav_item_position = 35;

	}

	function create_screen() {
		global $bp;

		if ( !bp_is_classified_creation_step( $this->slug ) )
			return false;
		locate_template( array( 'groups/groups-loop.php' ), true );
		wp_nonce_field( 'classifieds_create_save_' . $this->slug );

	}
	//needed but empty as ajax does the job
	function create_screen_save() {
	}


	function edit_screen() {
		global $bp;
		
		if ( !bp_is_classified_admin_screen( $this->slug ) )
			return false; ?>

		<h2><?php echo attribute_escape( $this->name ) ?></h2>
		<?php
		locate_template( array( 'groups/groups-loop.php' ), true );
	}
	//needed but empty as ajax does the job
	function edit_screen_save() {
	}
}

//TO FIX : DIRTY FN ?
function bp_is_group_classifieds_page() {
	global $bp;
	
	
	$url_action = $bp->action_variables[0];
	if (!$url_action)
		$url_action=$bp->current_action;
	//&& $bp->is_single_item
	if ( BP_GROUPS_SLUG == $bp->current_component && BP_CLASSIFIEDS_SLUG == $url_action )
		return true;

	return false;
}

function classifieds_groups_group_delete_classifieds( $group_id ) {
	global $wpdb;
	global $bp;
	

	$sql=$wpdb->prepare( "DELETE FROM " . $bp->classifieds->table_name_classifiedmeta . " WHERE meta_key = 'classified_group' AND meta_value = %d", $group_id);

	$result = $wpdb->query($sql);
	return $result;
}

function classifieds_groups_css() {
	wp_enqueue_style( 'bp-classifieds-groups', BP_CLASSIFIEDS_PLUGIN_URL . '/css/classifieds-groups.css' );
}
function classifieds_groups_js() {
	wp_enqueue_script( 'bp-classifieds-groups', BP_CLASSIFIEDS_PLUGIN_URL . '/js/classifieds-groups.js',array('jquery'), BP_CLASSIFIEDS_VERSION );
}

//GROUP : check group allows classifieds
function classifieds_groups_group_classifieds_enabled( $group_id = false ) {
	global $groups_template;

	if ( !$group_id )
		$group_id =& $groups_template->group->id;

		
	if (groups_get_groupmeta($group_id, 'classifieds_disabled')) {
		$enabled = false;
	}else {
		$enabled = true;
	}

	return apply_filters( 'classifieds_groups_group_enable_classifieds', $enabled );
}

//GROUP | creation : checkbox to disable classifieds
function classifieds_groups_group_settings_creation() {
	global $bp;

	?>
	
	<div class="checkbox">
		<label><input type="checkbox" name="group-disable-classifieds" id="group-disable-classifieds" value="1" /> <?php _e('Disable classifieds on this group', 'classifieds') ?></label>
	</div>
	<?php 
}

//GROUP | admin : checkbox to disable classifieds
function classifieds_groups_group_settings_admin() {
	global $bp;
	?>
	
	<div class="checkbox">
		<label><input type="checkbox" name="group-disable-classifieds" id="group-disable-classifieds" value="1"<?php if (!classifieds_groups_group_classifieds_enabled($bp->groups->current_group->id))echo" CHECKED";?>/> <?php _e('Disable classifieds on this group', 'classifieds') ?></label>
	</div>
	<?php 
}

//GROUP | save
function classifieds_groups_group_settings_save() {
	global $bp;
	
	if ($_POST['group-disable-classifieds'])
		groups_update_groupmeta( $bp->groups->current_group->id, 'classifieds_disabled', true );
	else
		groups_delete_groupmeta( $bp->groups->current_group->id, 'classifieds_disabled');
}

//GROUP | menu
function classifieds_groups_setup_nav() {
	global $bp;
	
	$group_id = $bp->groups->current_group->id;

	
	if (!classifieds_groups_group_classifieds_enabled($group_id)) return false;

	$group_link = $bp->root_domain . '/' . $bp->groups->slug . '/' . $bp->groups->current_group->slug . '/';
	
	//TO FIX : check access
	bp_core_new_subnav_item( array( 'name' => __( 'Classifieds', 'classifieds' ), 'slug' => BP_CLASSIFIEDS_SLUG, 'parent_url' => $group_link, 'parent_slug' => $bp->groups->slug, 'screen_function' => 'classifieds_groups_group_screen_classifieds', 'item_css_id' => 'classifieds', 'position' => 80, 'user_has_access' => $bp->groups->current_group->user_has_access ) );
}

//GROUP | classifieds screen
function classifieds_groups_group_screen_classifieds() {
	global $bp;
	
	if ( $bp->is_single_item )
		bp_core_load_template( apply_filters( 'bp_classifieds_groups_template', 'groups/single/plugins' ) );
	}

//GROUP |classifieds display
function classifieds_groups_group_display_classifieds() {
	
	if ( bp_is_group_classifieds_page() && bp_group_is_visible() ) :

		if (!classifieds_groups_group_classifieds_enabled()) return false;

		bp_classifieds_locate_template( array( 'classifieds/classifieds-loop.php' ), true );
	endif;
}


//CLASSIFIED
function classifieds_groups_classified_is_in_group($classified_id,$group_id) {
	global $bp;
	global $groups_template;
	global $wpdb;
		
	//TO FIX use classifieds_get_classifiedmeta
	$sql = $wpdb->prepare( "SELECT * FROM {$bp->classifieds->table_name_classifiedmeta} WHERE meta_value = %d AND post_id = %d AND meta_key = 'classified_group'", $group_id, $classified_id );

	$result = $wpdb->get_var( $sql );	

	return $result;

}

function bp_classifieds_group_button($group_id,$classified_id) {
	global $bp;
	
	$checked = classifieds_groups_classified_is_in_group($classified_id,$group_id);

	
	if ($checked)
		$checked_class='remove-group';
	else
		$checked_class='add-group';

	echo '<div rel="classified-'.$classified_id.'" class="generic-button classified-button ' . $group->status . ' '.$checked_class.'" id="groupbutton-' . $group_id . '">';
	
	if (!$checked) {
		echo '<a rel="add-group" href="' . wp_nonce_url( $bp->root_domain . '/' . $bp->classifieds->slug. '/'.__('create','classifieds-slugs').'/'.__('step','classifieds-slugs').'/' . $bp->classifieds->current_create_step . '/' . __('add-group','classifieds-groups-slugs'), 'classifieds_add_classified_group' ) . '">' . __( 'Add to Group', 'classifieds-groups' ) . '</a>';
	}else {
		echo '<a rel="remove-group" href="' . wp_nonce_url( $bp->root_domain . '/' . $bp->classifieds->slug. '/'.__('create','classifieds-slugs').'/'.__('step','classifieds-slugs').'/' . $bp->classifieds->current_create_step . '/' . __('remove-group','classifieds-groups-slugs'), 'classifieds_remove_classified_group' ) . '">' . __( 'Remove from Group', 'classifieds-groups' ) . '</a>';
	}


	echo '</div>';
}

//displays add|remove from group button
function classifieds_groups_group_action() {
	global $bp;

	if (bp_is_classified_creation_step(BP_GROUPS_SLUG)) { //classified creation
		$group_id = bp_get_group_id();
		$classified_id = $bp->classifieds->current_classified->ID;
	}elseif (bp_is_classified_admin_screen(BP_GROUPS_SLUG)) { //classified admin
		$group_id = bp_get_group_id();
		$classified_id = bp_get_classified_id();
	}elseif (bp_is_group_classifieds_page()) { //group classifieds | for group mods|admin or classified author
		$group_id = $bp->groups->current_group->id;
		if (($bp->is_item_admin) || ($bp->is_item_mod) || bp_classified_is_author())
			$classified_id = bp_get_classified_id();
	}
	
	if ((!$classified_id) || (!$group_id)) return false;
	
	bp_classifieds_group_button($group_id,$classified_id);
}


function bp_classifieds_ajax_group_button() {
	global $bp;
	
	//TO FIX : $_POST['cid'] = wrong value
	$group_id=$_POST['gid'];
	$classified_id=$_POST['cid'];

	if ((!$group_id) || (!$classified_id)) return false;
	
	$checked = classifieds_groups_classified_is_in_group($classified_id,$group_id);

	if ($checked) {

		check_ajax_referer('classifieds_remove_classified_group');
		

		if ( !classifieds_groups_remove_classified_from_group( $classified_id, $group_id ) ) {
			echo __("Classified could not be removed from the group.", 'classifieds-groups');
		} else {
			echo '<a rel="add-group" href="' . wp_nonce_url( $bp->root_domain . '/' . $bp->classifieds->slug. '/'.__('create','classifieds-slugs').'/'.__('step','classifieds-slugs').'/' . $bp->classifieds->current_create_step . '/' . __('add-group','classifieds-groups-slugs'), 'classifieds_add_classified_group' ) . '">' . __( 'Add to Group', 'classifieds-groups' ) . '</a>';
		}
	} else {
		
		check_ajax_referer('classifieds_add_classified_group');
		if ( !classifieds_groups_add_classified_to_group( $classified_id, $group_id ) ) {
			echo __("Classified could not be added to the group.", 'classifieds-groups');
		} else {
			echo '<a rel="remove-group" href="' . wp_nonce_url( $bp->root_domain . '/' . $bp->classifieds->slug. '/'.__('create','classifieds-slugs').'/'.__('step','classifieds-slugs').'/' . $bp->classifieds->current_create_step . '/' . __('remove-group','classifieds-groups-slugs'), 'classifieds_remove_classified_group' ) . '">' . __( 'Remove from Group', 'classifieds-groups' ) . '</a>';
			
		}
	}

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
 
function classifieds_groups_add_classified_to_group( $classified_id, $group_id) {
	global $bp;
	global $wpdb;

	$classified = new BP_Classifieds_Classified($classified_id);
	
	if (!$classified) return false;
	//TO FIX use classifieds_update_classifiedmeta
	$result = $wpdb->query( $wpdb->prepare( "INSERT INTO {$bp->classifieds->table_name_classifiedmeta} (post_id,meta_key,meta_value) VALUES (%d,'classified_group',%d)", $classified->ID,$group_id ) );	
	
	if (!$result) return false;


	do_action( 'classifieds_groups_added_to_group', $classified->ID, $group_id );
	
	return true;
}

function classifieds_groups_remove_classified_from_group( $classified_id, $group_id ) {
	global $bp;
	global $wpdb;


	$classified = new BP_Classifieds_Classified($classified_id);
	
	if (!$classified) return false;
	//TO FIX use classifieds_delete_classifiedmeta
	$result = $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->classifieds->table_name_classifiedmeta} WHERE post_id=%d AND meta_key='classified_group' AND meta_value=%d", $classified->ID,$group_id ) );	
	
	if (!$result) return false;

	do_action( 'classifieds_groups_removed_from_group', $classified->ID, $group_id );
	
	return true;
}

//filter group loops to show only classified creator groups
function classifieds_groups_groupsloop_ajax_querystring($query_string, $object, $filter, $scope, $page, $search_terms, $extras ) {
	if ((!bp_is_classified_creation_step(BP_GROUPS_SLUG)) && (!bp_is_classified_admin_screen(BP_GROUPS_SLUG))) return false;
	
	global $bp;
	
	if ($object!='groups') return false;
	
	if (bp_is_classified_creation_step(BP_GROUPS_SLUG)) {
		$classified = $bp->classifieds->current_classified;
	}elseif (bp_is_classified_admin_screen(BP_GROUPS_SLUG)) {
		global $classifieds_template;
		$classified = & $classifieds_template->classified;
	}
	
	if (!$classified) return false;

	
	$user_id = $classified->creator_id;
	$qs[] = 'user_id=' . $user_id;

	/* Now pass the querystring to override default values. */
	$new_qs = empty( $qs ) ? '' : join( '&', (array)$qs );
	
	return apply_filters( 'classifieds_groups_groupsloop_ajax_querystring',$new_qs,$object,$filter,$scope,$page,$search_terms,$extras );

}

function classifieds_groups_extract_url_args($args) {
	global $bp;
	
	if (!bp_is_group_classifieds_page()) return false;
	

	
	$group_id = $bp->groups->current_group->id;
	
	if (!classifieds_groups_group_classifieds_enabled()) return false;

	$args['group_id']=$group_id;
	
	return $args;
	
}

function classifieds_groups_init() {
	global $bp;

	bp_register_classified_extension( 'Classified_Groups_Extension' );

	//GROUP
	if ( $bp->current_component == BP_GROUPS_SLUG) {
		add_action( 'bp_setup_nav','classifieds_groups_setup_nav');
		add_action( 'bp_before_group_settings_creation_step','classifieds_groups_group_settings_creation');
		add_action( 'bp_before_group_settings_admin','classifieds_groups_group_settings_admin');
		add_action( 'groups_create_group_step_save_group-settings','classifieds_groups_group_settings_save');
		add_action( 'groups_group_settings_edited','classifieds_groups_group_settings_save');
		add_action( 'groups_delete_group', 'classifieds_groups_group_delete_classifieds'); //delete classifieds metas for this group
	}
	if (bp_is_group_classifieds_page()) {
		add_action( 'bp_template_content','classifieds_groups_group_display_classifieds');
		add_filter('classifieds_extract_url_args','classifieds_groups_extract_url_args');//filter classifieds loop for group
		add_action('bp_directory_classifieds_actions','classifieds_groups_group_action');//add button for group mods to remove classifieds
	}
	
	//CLASSIFIED
	if ( $bp->current_component == BP_CLASSIFIEDS_SLUG) {
		//TO FIX, DO NOT WORK !
		//if ((bp_is_group_classifieds_page()) || (bp_is_classified_creation_step(BP_GROUPS_SLUG)) || (bp_is_classified_admin_screen(BP_GROUPS_SLUG))) {

			add_action('wp_print_styles', 'classifieds_groups_css');
			add_action('wp_print_scripts', 'classifieds_groups_js');
			add_action( 'bp_directory_groups_actions','classifieds_groups_group_action');
			add_action( 'wp_ajax_group_button', 'bp_classifieds_ajax_group_button' );
		//}
		//TO FIX, DO NOT WORK !
		//if ((bp_is_classified_creation_step(BP_GROUPS_SLUG)) || (bp_is_classified_admin_screen(BP_GROUPS_SLUG))) {

			add_filter('bp_dtheme_ajax_querystring', 'classifieds_groups_groupsloop_ajax_querystring',10,7);//filter groups loop to display only author groups
		//}
	}

}


add_action( 'classifieds_init', 'classifieds_groups_init');
	
	
	
	
	












?>