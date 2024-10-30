<?php
//TO FIX : delete classified markers @ classified delete

class Classified_Map_Extension extends BP_Classified_Extension {

	function classified_map_extension() {
		global $bp;
		$this->name = __( 'Classified Map', 'classifieds' );
		$this->slug = __( 'map', 'maps-slugs' );
		$this->nav_item_name = __( 'Map', 'map' );

		$this->create_step_position = 26;
		$this->nav_item_position = 36;
	}

	function create_screen() {
		global $bp;

		if ( !bp_is_classified_creation_step( $this->slug ) )
			return false;

		classifieds_maps_show_map($bp->classifieds->current_classified->ID,true);
		
		wp_nonce_field( 'classifieds_create_save_' . $this->slug );
	}


	function edit_screen() {
		global $bp;
		
		if ( !bp_is_classified_admin_screen( $this->slug ) )
			return false; ?>

		<h2><?php echo attribute_escape( $this->name ) ?></h2>
		<?php

		classifieds_maps_show_map($bp->classifieds->current_classified->ID,true);
		
	}
	//needed but empty as ajax does the job
	function edit_screen_save() {
	}
}

//TO FIX : DIRTY FN ?
function bp_is_classifieds_map_page() {
	global $bp;

	$url_action = $bp->action_variables[0];
	if (!$url_action)
		$url_action=$bp->current_action;
	//&& $bp->is_single_item
	if ( BP_CLASSIFIEDS_SLUG == $bp->current_component && __( 'map', 'maps-slugs' )== $url_action )
		return true;

	return false;
}

function classifieds_maps_show_map($classified_id,$editable=false) {
	global $bp;
	//THIS IS FOR FETCHING THE MARKERS
	$marker_args = array(
		'type' => 'classified',
		'secondary_id'	=> $classified_id
	);
	
	//THIS IS THE MAP PARAMS
	$map_args = array(
		'editable'	=>$editable,
		'enable_desc'	=>false,
		'showmarkers'	=> false
	);

	$bp->maps->current_map = new Bp_Map($map_args,$marker_args);
	bp_maps_locate_template( array( 'maps/map.php' ), true );

}


function classifieds_maps_classified_has_map($classified_id=false) {
	global $bp;
	
	if (!$classified_id)
		$classified_id=$bp->classifieds->current_classified->ID;

	if ($bp->maps->current_map)
		if (!empty($bp->maps->current_map->markers_template->markers)) return true;
		
	if (classifieds_maps_get_classified_markers( $classified_id )) return true;
		
	return false;
}

function classifieds_maps_get_classified_markers( $classified_id ) {
	global $wpdb,$bp;

	$query = $wpdb->prepare( "SELECT id FROM `{$bp->maps->table_name_markers}` mk WHERE secondary_id={$classified_id} AND type='classified'");

	$markers_ids = $wpdb->get_col($query );

	return $markers_ids;
}

function classifieds_maps_delete_classified_markers( $classified_id ) {
	global $wpdb;
	global $bp;
	
	if (!$classified_id) return false;
	
	$markers = classifieds_maps_get_classified_markers( $classified_id );
	
	if (!$markers) return false;
	
	$markers_str = implode(',',$markers);
	

	$sql=$wpdb->prepare( "DELETE FROM {$bp->maps->table_name_markers} WHERE id IN (%s)", $markers_str);

	$result = $wpdb->query($sql);
	return $result;
}

function classifieds_maps_setup_nav() {
	global $bp;

	//TO FIX : current_classified doesn't fire !!!
	
	$classified_id = $bp->classifieds->current_classified->ID;

	if (!classifieds_maps_classified_has_map($classified_id)) return false;
	$classified_link = $bp->root_domain . '/' . $bp->classifieds->slug . '/' . $bp->classifieds->current_classified->slug . '/';
	
	//TO FIX : check access
	bp_core_new_subnav_item( array( 'name' => __( 'Map', 'maps' ), 'slug' => __( 'map', 'maps-slugs' ), 'parent_url' => $classified_link, 'parent_slug' => $bp->classifieds->slug, 'screen_function' => 'classifieds_maps_screen_map', 'item_css_id' => 'map', 'position' => 80, 'user_has_access' => bp_classified_is_visible() ) );
}


function classifieds_maps_screen_map() {
	global $bp;
	
	if ( $bp->is_single_item )
		bp_core_load_template( apply_filters( 'bp_classifieds_map_template', 'classifieds/single/plugins' ) );
	}

//GROUP |classifieds display
function classifieds_maps_display_map() {
	
	if ( bp_is_classifieds_map_page() && bp_classified_is_visible() ) :

		if (!classifieds_maps_classified_has_map()) return false;
		
		global $bp;


		classifieds_maps_show_map($bp->classifieds->current_classified->ID);
		
	endif;
}

function classifieds_maps_is_admin_screen() {
	global $bp;

	if ($bp->current_component != BP_CLASSIFIEDS_SLUG) return false;
	
	//TO FIX : current_action (instead of action_variables[0]) should be admin or create
	
	//edition
	if (($bp->action_variables[0] == 'admin') && ($bp->action_variables[1]==__( 'map', 'maps-slugs' ))) return true;
	
	//creation
	if (($bp->current_action == 'create') && ($bp->action_variables[0]=='step') && ($bp->action_variables[1]==__( 'map', 'maps-slugs' ))) return true;

	return false;

	
}

function classifieds_maps_init() {
	global $bp;
	
	if (!class_exists('Bp_Map')) return false;

	//if (!$bp->classifieds->options['classifieds_maps']) return false;

	bp_register_classified_extension( 'Classified_Map_Extension' );

	
	if ((bp_is_classifieds_map_page()) || (classifieds_maps_is_admin_screen())) 
		bp_maps_head_init();
	
	if (bp_is_classifieds_map_page()) {
		
		add_action( 'bp_template_content','classifieds_maps_display_map');
	}

}


add_action( 'classifieds_init', 'classifieds_maps_init');
add_action( 'classifieds_setup_nav','classifieds_maps_setup_nav');
	
	
	
	












?>