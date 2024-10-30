<?php

/*****************************************************************************
 * Classifieds Template Class
 **/
 
//TO FIX TO MOVE
//this is a ...strange function hooked on the filter 'found_posts', that fetches the total rows found for a query (no limits).
//see core function &get_posts()

 
class BP_Classifieds_Template {
	var $current_classified = -1;
	var $classified_count;
	var $classifieds;
	var $classified;
	
	var $in_the_loop;
	
	var $pag_page;
	var $pag_num;
	var $pag_links;
	var $total_classified_count;
	
	var $single_classified = false;
	
	var $sort_by;
	var $order;
	
	function bp_classifieds_template( $args, $per_page, $max) {
		global $bp;



		if ( defined( 'BP_CLASSIFIEDS_DEBUG' ) )$bp->classifieds->debug->log($args,'bp_classifieds_template_args');
		
		

		if ($args['type']=='single-classified') {
			$classified = new stdClass;
			$classified->ID = BP_Classifieds_Classified::classified_exists($args['slug']);			
			$this->classifieds = array( $classified );
		}else {

		
			/*PAGING*/
			$this->pag_page = isset( $_REQUEST['clpage'] ) ? intval( $_REQUEST['clpage'] ) : 1;
			$this->pag_num = isset( $_REQUEST['num'] ) ? intval( $_REQUEST['num'] ) : $per_page;
			
			//FILTER
			if ( isset( $_REQUEST['s'] )) $args['filter'] = $_REQUEST['s'];
			
			
			
			if (is_array($args['categories'])) {
				$pag_link_categories=implode(',',$args['categories']);
			}

			$classifieds = BP_Classifieds_Classified::get_classifieds($args, $this->pag_num, $this->pag_page);
		
			$this->classifieds = apply_filters('bp_classifieds_template_get_classifieds',$classifieds);
			
		}
		
		if ( 'single-classified' == $args['type'] ) {
			$this->single_classified = true;
			$this->total_classified_count = 1;
			$this->classified_count = 1;
		} else {
			if ( !$max || $max >= (int)$this->classifieds['total'] )
				$this->total_classified_count = (int)$this->classifieds['total'];
			else
				$this->total_classified_count = (int)$max;
				
			$this->classifieds = $this->classifieds['classifieds'];
		
			if ( $max ) {
				if ( $max >= count($this->classifieds) )
					$this->classified_count = count($this->classifieds);
				else
					$this->classified_count = (int)$max;
			} else {
				$this->classified_count = count($this->classifieds);
			}

			$this->pag_links = paginate_links( array(
				'base' => add_query_arg( array( 'action_tag'=>$args['action_tag'],'categories'=>$pag_link_categories,'clpage' => '%#%', 'num' => $this->pag_num, 's' => $args['filter'], 'sortby' => $this->sort_by, 'order' => $this->order ) ),
				'format' => '',
				'total' => ceil($this->total_classified_count / $this->pag_num),
				'current' => $this->pag_page,
				'prev_text' => '&laquo;',
				'next_text' => '&raquo;',
				'mid_size' => 1
			));
			
		}
	}

	function has_classifieds() {
		if ( $this->classified_count )
			return true;
		
		return false;
	}
	
	function next_classified() {
		$this->current_classified++;
		$this->classified = $this->classifieds[$this->current_classified];
			
		return $this->classified;
	}
	
	function rewind_classifieds() {
		$this->current_classified = -1;
		if ( $this->classified_count > 0 ) {
			$this->classified = $this->classifieds[0];
		}
	}
	
	function template_classifieds() { 
		if ( $this->current_classified + 1 < $this->classified_count ) {
			return true;
		} elseif ( $this->current_classified + 1 == $this->classified_count ) {
			do_action('loop_end');
			// Do some cleaning up after the loop
			$this->rewind_classifieds();
		}

		$this->in_the_loop = false;
		return false;
	}
	
	function the_classified() {
		global $classified;

		$this->in_the_loop = true;
		
		$this->classified = $this->next_classified();

		
		// If this is a single classified then instantiate classified meta when creating the object.
		if ( $this->single_classified ) {
			if ( !$classified = wp_cache_get( 'classifieds_classified_' . $this->classified->ID, 'bp' ) ) {
				$classified = new BP_Classifieds_Classified( $this->classified->ID, true );
				wp_cache_set( 'classifieds_classified_' . $this->classified->ID, $classified, 'bp' );
			}
		} else {
			if ( !$classified = wp_cache_get( 'classifieds_classified_nouserdata_' . $this->classified->ID, 'bp' ) ) {
				$classified = new BP_Classifieds_Classified( $this->classified->ID, false);
				wp_cache_set( 'classifieds_classified_nouserdata_' . $this->classified->ID, $classified, 'bp' );
			}
		}
		
		$this->classified = $classified;
		
		if ( 0 == $this->current_classified ) // loop has just started
			do_action('loop_start');
	}
}

function bp_has_classifieds( $args = '' ) {
	global $classifieds_template, $bp;
	
	$qs_arr = array();
	parse_str( $args, $qs_arr );

	if ( defined( 'BP_CLASSIFIEDS_DEBUG' ) )$bp->classifieds->debug->log($args,'bp_has_classifieds args');

	/***
	 * Set the defaults based on the current page. Any of these will be overridden
	 * if arguments are directly passed into the loop. Custom plugins should always
	 * pass their parameters directly to the loop.
	 */
	$type = 'active';
	$user_id = false;
	$search_terms = false;
	$slug = false;
	

	

	/* User filtering */
	if ( !empty( $bp->displayed_user->id ) )
		$user_id = $bp->displayed_user->id;

	/* Type */
	
	if ( 'my-classifieds' == $bp->current_action ) {
	
		$order = $bp->action_variables[0];
		if ( __( 'publish', 'classifieds-slugs' ) == $order ) {
			bp_core_delete_notifications_for_user_by_type( $bp->loggedin_user->id, $bp->classifieds->slug, 'classified_published' );
			bp_core_delete_notifications_for_user_by_type( $bp->loggedin_user->id, $bp->classifieds->slug, 'classified_republished' );
		}else if ( __( 'pending', 'classifieds-slugs' ) == $order )
			$args ['status'] = 'pending';
		else if ( __( 'unactive', 'classifieds-slugs' ) == $order )
			$args ['status'] = 'unactive';
		else if ( __( 'followed', 'classifieds-slugs' ) == $order )
			$args ['status'] = 'followed';
	} else if ( $bp->classifieds->current_classified->slug ) {
		
		$type = 'single-classified';
		$slug = $bp->classifieds->current_classified->slug;
	}

	//CAPABILITIES

	if ($type != 'single-classified') {
		//user cannot view classifieds lists
		if (!bp_classifieds_are_visible()) {
			return false;
		}
	}else {
		//user cannot view single classifieds
		if (!bp_classified_is_visible()) return false;
	}
	
	if ( isset( $_REQUEST['classified--filter-box'] ) || isset( $_REQUEST['s'] ) )
		$search_terms = ( isset( $_REQUEST['classified--filter-box'] ) ) ? $_REQUEST['classified--filter-box'] : $_REQUEST['s'];
	
	$defaults = array(
		'type' => $type,
		'page' => 1,
		'per_page' => 20,
		'max' => false,

		'user_id' => $user_id, // Pass a user ID to limit to groups this user has joined
		'slug' => $slug, // Pass a group slug to only return that group
		'search_terms' => $search_terms // Pass search terms to return only matching groups
	);
	
	//EXTRACT URL ARGS
	$defaults['action_tag']=classifieds_extract_url_action();
	$defaults['cats']=classifieds_extract_url_cats();
	$defaults['tag']=classifieds_extract_url_tag();
	
	$template_args = wp_parse_args( $args, $defaults );
	$per_page=$template_args['per_page'];
	$max=$template_args['max'];
	unset($template_args['per_page']);
	unset($template_args['max']);
	
	$classifieds_template = new BP_Classifieds_Template($template_args, $per_page, $max );	

	
	if ($template_args['cats']) { //if we are category-browsing

		add_action('bp_before_classifieds_list_item','classifieds_sort_by_categories_cat_title');
		add_action('bp_before_directory_classifieds_list','classifieds_sort_by_category');
	}

	return apply_filters( 'bp_has_classifieds', $classifieds_template->has_classifieds(), &$classifieds_template );
}


function bp_classifieds() {
	global $classifieds_template;
	return $classifieds_template->template_classifieds();
}

function bp_the_classified() {
	global $classifieds_template;
	return $classifieds_template->the_classified();
}

/***************************************************************************
 * Classified Users Template Tags
 **/

class BP_Classifieds_Classified_Followers_Template {
	var $current_follower = -1;
	var $follower_count;
	var $followers;
	var $follower;
	
	var $in_the_loop;
	
	var $pag_page;
	var $pag_num;
	var $pag_links;
	var $total_classified_count;
	
	function bp_classifieds_classified_followers_template( $classified_id, $per_page, $max, $exclude_admins_mods, $exclude_banned ) {
		global $bp;
		
		$this->pag_page = isset( $_REQUEST['mlpage'] ) ? intval( $_REQUEST['mlpage'] ) : 1;
		$this->pag_num = isset( $_REQUEST['num'] ) ? intval( $_REQUEST['num'] ) : $per_page;
		
		$this->followers = classified_get_followers( $classified_id, $this->pag_num, $this->pag_page);

		if ( !$max || $max >= (int)$this->followers['count'] )
			$this->total_follower_count = (int)$this->followers['count'];
		else
			$this->total_follower_count = (int)$max;

		$this->followers = $this->followers['followers'];
		
		if ( $max ) {
			if ( $max >= count($this->followers) )
				$this->follower_count = count($this->followers);
			else
				$this->follower_count = (int)$max;
		} else {
			$this->follower_count = count($this->followers);
		}

		$this->pag_links = paginate_links( array(
			'base' => add_query_arg( 'mlpage', '%#%' ),
			'format' => '',
			'total' => ceil( $this->total_follower_count / $this->pag_num ),
			'current' => $this->pag_page,
			'prev_text' => '&laquo;',
			'next_text' => '&raquo;',
			'mid_size' => 1
		));
	}
	
	function has_followers() {
		if ( $this->follower_count )
			return true;

		return false;
	}
	
	function next_follower() {
		$this->current_follower++;
		$this->follower = $this->followers[$this->current_follower];
		
		return $this->follower;
	}
	
	function rewind_followers() {
		$this->current_follower = -1;
		if ( $this->follower_count > 0 ) {
			$this->follower = $this->followers[0];
		}
	}
	
	function followers() { 
		if ( $this->current_follower + 1 < $this->follower_count ) {
			return true;
		} elseif ( $this->current_follower + 1 == $this->follower_count ) {
			do_action('loop_end');
			// Do some cleaning up after the loop
			$this->rewind_followers();
		}

		$this->in_the_loop = false;
		return false;
	}
	
	function the_follower() {
		global $follower;

		$this->in_the_loop = true;
		$this->follower = $this->next_follower();

		if ( 0 == $this->current_follower ) // loop has just started
			do_action('loop_start');
	}
}

function bp_classified_has_followers( $args = '' ) {
	global $bp, $followers_template;
	
	$defaults = array(
		'classified_id' => $bp->classifieds->current_classified->ID,
		'per_page' => 10,
		'max' => false,
		'exclude_admins_mods' => 1,
		'exclude_banned' => 1
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	$followers_template = new BP_Classifieds_Classified_Followers_Template( $classified_id, $per_page, $max, (int)$exclude_admins_mods, (int)$exclude_banned );
	return apply_filters( 'bp_classified_has_followers', $followers_template->has_followers(), &$followers_template );
}

function bp_classified_followers() {
	global $followers_template;
	
	return $followers_template->followers();
}

function bp_classified_the_user() {
	global $followers_template;
	
	return $followers_template->the_follower();
}

function bp_classified_follower_avatar() {
	echo bp_get_classified_user_avatar();
}
	function bp_get_classified_user_avatar() {
		global $followers_template;

		return apply_filters( 'bp_get_classified_user_avatar', bp_core_fetch_avatar( array( 'item_id' => $followers_template->follower->user_id, 'type' => 'full' ) ) );
	}

function bp_classified_follower_avatar_thumb() {
	echo bp_get_classified_user_avatar_thumb();
}
	function bp_get_classified_user_avatar_thumb() {
		global $followers_template;

		return apply_filters( 'bp_get_classified_user_avatar_thumb', bp_core_fetch_avatar( array( 'item_id' => $followers_template->follower->user_id, 'type' => 'thumb' ) ) );
	}

function bp_classified_follower_avatar_mini( $width = 30, $height = 30 ) {
	echo bp_get_classified_user_avatar_mini( $width, $height );
}
	function bp_get_classified_user_avatar_mini( $width = 30, $height = 30 ) {
		global $followers_template;

		return apply_filters( 'bp_get_classified_user_avatar_mini', bp_core_fetch_avatar( array( 'item_id' => $followers_template->follower->user_id, 'type' => 'thumb', 'width' => $width, 'height' => $height ) ) );
	}

function bp_classified_follower_name() {
	echo bp_get_classified_user_name();
}
	function bp_get_classified_user_name() {
		global $followers_template;

		return apply_filters( 'bp_get_classified_user_name', bp_core_get_user_displayname( $followers_template->follower->user_id ) );
	}

function bp_classified_follower_url() {
	echo bp_get_classified_user_url();
}
	function bp_get_classified_user_url() {
		global $followers_template;

		return apply_filters( 'bp_get_classified_user_url', bp_core_get_userlink( $followers_template->follower->user_id, false, true ) );
	}

function bp_classified_follower_link() {
	echo bp_get_classified_user_link();
}
	function bp_get_classified_user_link() {
		global $followers_template;

		return apply_filters( 'bp_get_classified_user_link', bp_core_get_userlink( $followers_template->follower->user_id ) );
	}

function bp_classified_follower_last_activity() {
	echo bp_get_classified_follower_last_activity();
}
	function bp_get_classified_follower_last_activity() {
		global $followers_template;

		return apply_filters( 'bp_get_classified_follower_last_activity', bp_last_activity($followers_template->follower->user_id,false) );
	}
	
function bp_classified_follower_id() {
	echo bp_get_classified_user_id();
}
	function bp_get_classified_follower_id() {
		global $followers_template;

		return apply_filters( 'bp_get_classified_follower_id', $followers_template->follower->user_id );
	}

function bp_classified_follower_needs_pagination() {
	global $followers_template;

	if ( $followers_template->total_follower_count > $followers_template->pag_num )
		return true;
	
	return false;
}

function bp_classified_follower_pagination() {
	echo bp_get_classified_user_pagination();
	wp_nonce_field( 'bp_classifieds_user_list', '_user_pag_nonce' );
}
	function bp_get_classified_user_pagination() {
		global $followers_template;
		return apply_filters( 'bp_get_classified_user_pagination', $followers_template->pag_links );
	}

function bp_classified_follower_pagination_count() {
	echo bp_get_classified_user_pagination_count();
}
	function bp_get_classified_user_pagination_count() {
		global $followers_template;

		$from_num = intval( ( $followers_template->pag_page - 1 ) * $followers_template->pag_num ) + 1;
		$to_num = ( $from_num + ( $followers_template->pag_num - 1 ) > $followers_template->total_follower_count ) ? $followers_template->total_follower_count : $from_num + ( $followers_template->pag_num - 1 ); 

		return apply_filters( 'bp_get_classified_user_pagination_count', sprintf( __( 'Viewing users %d to %d (of %d users)', 'buddypress' ), $from_num, $to_num, $followers_template->total_follower_count ) );  
	}

function bp_classified_follower_admin_pagination() {
	echo bp_get_classified_user_admin_pagination();
	wp_nonce_field( 'bp_classifieds_user_admin_list', '_user_admin_pag_nonce' );
}
	function bp_get_classified_user_admin_pagination() {
		global $followers_template;
		
		return $followers_template->pag_links;
	}
function classified_get_followers($classified_id,$limit = false, $page = false) {
	global $bp, $wpdb;
	
	if ( $limit && $page )
		$pag_sql = $wpdb->prepare( "LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
	
	//TO FIX use classifieds_get_classifiedmeta
	$followers = $wpdb->get_results( $wpdb->prepare( "SELECT meta_value FROM {$bp->classifieds->table_name_classifiedmeta} WHERE meta_key='classified_follower' AND post_id = %d {$pag_sql}", $classified_id ) );

	if ( !$followers )
		return false;
	
	if ( !isset($pag_sql) ) 
		$total_follower_count = count($followers);
	else
		//TO FIX use classifieds_get_classifiedmeta
		$total_follower_count = $wpdb->get_var( $wpdb->prepare( "SELECT count(user_id) FROM {$bp->classifieds->table_name_classifiedmeta} WHERE meta_key='classified_follower' AND post_id = %d", $classified_id ) );

	return array( 'followers' => $followers, 'count' => $total_follower_count );
}

function bp_classifieds_search_form() {
	echo bp_get_classifieds_search_form();
}

	function bp_get_classifieds_search_form() {
		global $bp;

		$form_url = $bp->root_domain . '/' . $bp->classifieds->slug;
		$output.="<form action=\"".$form_url."\" method=\"post\" id=\"search-classifieds-form\">\n";
		
		//SEARCH
		
		$output.="<label><input type=\"text\" name=\"s\" id=\"classifieds_search\" value=\"";

		if ( isset( $_REQUEST['s'] ) ) {
			$output.=attribute_escape( $_REQUEST['s'] );
		} else { 
			$output.=__( 'Search anything...', 'buddypress' );
		}
		//TO FIX : replace by classifieds_searchform_search_field() in widget-classifieds.js function ? but need to parse php
		
		$output.="\"  onfocus=\"if (this.value == '".__( 'Search anything...', 'buddypress' )."') {this.value = '';}\" onblur=\"if (this.value == '') {this.value = '".__( 'Search anything...', 'buddypress' )."';}\" /></label>\n";

		
		
		//CATEGORIES
		
		if (bp_classifieds_is_categories_enabled()) {
		
			$array_categories = BP_Classifieds_Categories::get_children();
			
			$treeset = new TreeSet();
			
			$current_categories  = classifieds_get_current_categories_ids();

			
			$categories_tree = $treeset -> drawTree($treeset -> buildTree($array_categories),$current_categories,'classifieds_format_cat_for_search_form');
			
			

			if ($current_categories) {
				$all_categories_checked=' CHECKED';
				$cats_active_class=' class="active"';
			}

			$output.='<span id="classifieds_advanced_search"><input name="classifieds_advanced_search" type="checkbox" value="1"'.$all_categories_checked.'>'.__('Advanced Search','classifieds').'</span>';
			$output.='<input class="no-ajax" type="submit" id="classifieds_search_submit" name="classifieds_search_submit" value="'.__( 'Search', 'buddypress' ).'" /></form>';
			$output.='<div id="browse-categories"'.$cats_active_class.'><h4>'.__("Filter Categories","classifieds").'</h4>';
			$output.='<small class="all-none"><a id="browse-category-check-all" href="#">'.__('All','buddypress').'</a>/<a id="browse-category-uncheck-all" href="#">'.__('None','buddypress').'</a></small>';
			$output.=$categories_tree;
			$output.='</div>';
			
		}

		
		
		
		return $output;
	}

function classifieds_format_cat_for_search_form($cat) {
	global $checked_branches;


	if (
		(!$checked_branches) || 
		( (is_array($checked_branches)) &&in_array($cat->ID,$checked_branches) )
		
		) $checked=' CHECKED';
		
	$count=bp_get_total_category_classified_count($cat->ID);
		

	$html='<li class="category" id="cat-'.$cat->ID.'"><div class="folder icon"></div><input type="checkbox" name="categories[]" value="'.$cat->ID.'"'.$checked.'><span class="text"><a href="'.bp_get_classified_category_permalink($cat).'">'.$cat->name.'</a>&nbsp;<small>('.$count.')</small></span>';
	
	return $html;
}

function classifieds_sort_by_categories_cat_title() {
	global $bp;
	global $classifieds_template;
	global $classifieds_loop_current_category;

	//TO FIX : works, but should be better with $bp->classifieds->current_classified ? is empty at that time.
	$current_classified_id = $classifieds_template->current_classified;
	$classified = $classifieds_template->classifieds[$current_classified_id];

	if ($classifieds_loop_current_category!=$classified->categories[0]) {
		$classifieds_loop_current_category=$classified->categories[0];
		echo '<div class="category-header cat-'.$classified->categories[0].'">';
		bp_classified_category_header_categories();
		echo'</div>';
	}
}



function classifieds_format_cat_for_creation($cat) {
	global $bp;
	global $checked_branches;

	if ( (is_array($checked_branches)) &&in_array($cat->ID,$checked_branches) )
		$checked=' CHECKED';

		if ( defined( 'BP_CLASSIFIEDS_DEBUG' ) )$bp->classifieds->debug->log(array('cat'=>$cat,'checked'=>$checked), 'classifieds_format_cat_for_creation');

		$html='<li><div class="folder icon"></div><input type="radio" name="classified-categories[]" value="'.$cat->ID.'"'.$checked.'><span class="text">'.$cat->name.'</span>';
	
	return $html;
}

function classifieds_format_cat_for_breadcrumb($cat) {
	global $bp;
	global $post;
	global $wpdb;
	
	if (bp_classifieds_is_actions_enabled()) {
		$classified = $post;
		
		switch_to_blog($bp->classifieds->options['blog_id']);
		$classified_action =  get_tag(7);//get_tag($classified->action);
		restore_current_blog();
		
		$cat_link = bp_get_classified_action_permalink($classified_action);

		$cat_link .='/'.bp_get_classified_category_permalink($cat,false);
	}else {
		$cat_link =bp_get_classified_category_permalink($cat);
	}

	$html='<a class="category cat-'.$cat->ID.'" href="'.$cat_link.'">'.$cat->name.'</a>';
	
	return $html;
}


function bp_classified_action_permalink($action,$full=true,$keepcats=false) {
		echo bp_get_classified_action_permalink($action,$full,$keepcats);
}
	function bp_get_classified_action_permalink($action, $full=true,$keepcats=false) {
			global $bp;

			if ($full) {
				$permalink = $bp->root_domain . '/' . $bp->classifieds->slug . '/' . $action->slug;
			}else {
				$permalink = $action->slug;
			}
			
			if ($keepcats) {
				$categories_ids = classifieds_get_current_categories_ids();
				if ($categories_ids) {
					$cats = implode(',',$categories_ids);
					$permalink.='?categories='.$cats;
				}
			}
			
			return $permalink;
	}		
		


function bp_classified_category_permalink($category) {
		echo bp_get_classified_category_permalink($category);
}
	function bp_get_classified_category_permalink($category,$full=true) {
			global $bp;

			$array_categories = BP_Classifieds_Categories::get_children();

			$parents = BP_Classifieds_Categories::get_parents($category->ID,$array_categories);
			foreach($parents as $parent) {
				$array_slugs[]=$parent->slug;

			}


			if (count($array_slugs)>1) {
				$array_slugs = array_reverse($array_slugs);
				
			}
			
			$array_slugs[]=$category->slug;
			$path=implode('/',$array_slugs);
			
			if ($full) {
				$permalink = $bp->root_domain . '/' . $bp->classifieds->slug . '/' . $path;
			}else {
				$permalink = $path;
			}
			
			
			return apply_filters( 'bp_get_classified_category_permalink',$permalink);
	}

function bp_classified_category_header_categories() {
	echo strip_tags(bp_get_classified_breadcrumb_categories(), '<a>');
}	
function bp_classified_breadcrumb_categories() {
	if (!classifieds_get_current_categories_ids()) {
		echo strip_tags(bp_get_classified_breadcrumb_categories(), '<a>');
	}
}
	
	function bp_get_classified_breadcrumb_categories($classified=false) {
		global $classifieds_template, $bp;
		global $post;

		if ( !$classified )
			$classified =&$classifieds_template->classified;
			
			

			//CATEGORY
			$category = $classified->categories[0];
			$array_categories = BP_Classifieds_Categories::get_children();
			
			

			$parents_categories = BP_Classifieds_Categories::get_parents($category,$array_categories,false); 
			
			if ($parents_categories) {
				
				$treeset = new TreeSet();

				$html = $treeset -> drawTree($treeset -> buildTree($parents_categories),false,'classifieds_format_cat_for_breadcrumb');
			}

			//$html.='</li>';

		return apply_filters( 'bp_get_classified_breadcrumb_categories', $html );
	}
	
function bp_directory_classifieds_content_desc_cats_names() {
	echo bp_get_directory_classifieds_content_desc_cats_names();
}
	
	function bp_get_directory_classifieds_content_desc_cats_names() {
		global $classifieds_query_args;
		

		
		if (!$classifieds_query_args['categories']) return false;
		
		foreach( $classifieds_query_args['categories'] as $cat_id) {
		$category = new BP_Classifieds_Categories($cat_id);
		
		$cats_links[] = classifieds_format_cat_for_breadcrumb($category);
		}
		
		$str = implode(', ',$cats_links);
		$str = strip_tags($str, '<a>'); 


		return apply_filters('bp_get_directory_classifieds_content_desc_cats_names',$str);
	}
	
/***************************************************************************
 * Classified Creation Process Template Tags
 **/

function bp_classified_creation_tabs() {
	global $bp;
	
	if ( !is_array( $bp->classifieds->classified_creation_steps ) )
		return false;
	
	if ( !$bp->classifieds->current_create_step )
		$bp->classifieds->current_create_step = array_shift( array_keys( $bp->classifieds->classified_creation_steps ) );

	$counter = 1;
	foreach ( $bp->classifieds->classified_creation_steps as $slug => $step ) {
		$is_enabled = bp_are_previous_classified_creation_steps_complete( $slug ); ?>
		
		<li<?php if ( $bp->classifieds->current_create_step == $slug ) : ?> class="current"<?php endif; ?>><?php if ( $is_enabled ) : ?><a href="<?php echo $bp->root_domain . '/' . $bp->classifieds->slug; ?>/create/step/<?php echo $slug ?>"><?php endif; ?><?php echo $counter ?>. <?php echo $step['name'] ?><?php if ( $is_enabled ) : ?></a><?php endif; ?></li><?php
		$counter++;
	}
	
	unset( $is_enabled );
	
	do_action( 'classifieds_creation_tabs' );
}

function bp_classified_creation_stage_title() {
	global $bp;
	
	echo apply_filters( 'bp_classified_creation_stage_title', '<span>&mdash; ' . $bp->classifieds->classified_creation_steps[$bp->classifieds->current_create_step]['name'] . '</span>' );
}

function bp_classified_creation_form_action() {
	echo bp_get_classified_creation_form_action();
}
	function bp_get_classified_creation_form_action() {
		global $bp;
		
		if ( empty( $bp->action_variables[1] ) )
			$bp->action_variables[1] = array_shift( array_keys( $bp->classifieds->classified_creation_steps ) );
		
		return apply_filters( 'bp_get_classified_creation_form_action', $bp->root_domain . '/' . $bp->classifieds->slug . '/create/step/' . $bp->action_variables[1] );
	}

function bp_is_classified_creation_step( $step_slug ) {
	global $bp;

	/* Make sure we are in the classifieds component */
	if ( $bp->current_component != BP_CLASSIFIEDS_SLUG || 'create' != $bp->current_action )
		return false;

	/* If this the first step, we can just accept and return true */
	if ( !$bp->action_variables[1] && array_shift( array_keys( $bp->classifieds->classified_creation_steps ) ) == $step_slug )
		return true;

	/* Before allowing a user to see a classified creation step we must make sure previous steps are completed */
	if ( !bp_is_first_classified_creation_step() ) {
		if ( !bp_are_previous_classified_creation_steps_complete( $step_slug ) )
			return false;
	}

	/* Check the current step against the step parameter */
	if ( $bp->action_variables[1] == $step_slug )
		return true;

	return false;
}

function bp_is_classified_creation_step_complete( $step_slugs ) {
	global $bp;
	
	if ( !$bp->classifieds->completed_create_steps )
		return false;

	if ( is_array( $step_slugs ) ) {
		$found = true;
		
		foreach ( $step_slugs as $step_slug ) {
			if ( !in_array( $step_slug, $bp->classifieds->completed_create_steps ) )
				$found = false;
		}
		
		return $found;
	} else {
		return in_array( $step_slugs, $bp->classifieds->completed_create_steps );	
	}

	return true;
}

function bp_are_previous_classified_creation_steps_complete( $step_slug ) {
	global $bp;
	
	/* If this is the first classified creation step, return true */
	if ( array_shift( array_keys( $bp->classifieds->classified_creation_steps ) ) == $step_slug )
		return true;
	
	reset( $bp->classifieds->classified_creation_steps );
	unset( $previous_steps );
		
	/* Get previous steps */
	foreach ( $bp->classifieds->classified_creation_steps as $slug => $name ) {
		if ( $slug == $step_slug )
			break;
	
		$previous_steps[] = $slug;
	}
	
	return bp_is_classified_creation_step_complete( $previous_steps );
}

function bp_classified_creation_previous_link() {
	echo bp_get_classified_creation_previous_link();
}
	function bp_get_classified_creation_previous_link() {
		global $bp;
		
		foreach ( $bp->classifieds->classified_creation_steps as $slug => $name ) {
			if ( $slug == $bp->action_variables[1] )
				break;
	
			$previous_steps[] = $slug;
		}

		return apply_filters( 'bp_get_classified_creation_previous_link', $bp->root_domain . '/' . $bp->classifieds->slug . '/create/step/' . array_pop( $previous_steps ) );
	}

function bp_is_last_classified_creation_step() {
	global $bp;
	
	$last_step = array_pop( array_keys( $bp->classifieds->classified_creation_steps ) );

	if ( $last_step == $bp->classifieds->current_create_step )
		return true;
	
	return false;
}

function bp_is_first_classified_creation_step() {
	global $bp;
	
	$first_step = array_shift( array_keys( $bp->classifieds->classified_creation_steps ) );

	if ( $first_step == $bp->classifieds->current_create_step )
		return true;
	
	return false;
}

function classifieds_get_friends_invite_list( $user_id = false, $classified_id ) {
	global $bp;
	
	if ( !$user_id )
		$user_id = $bp->loggedin_user->id;
	
	$friend_ids = friends_get_alphabetically( $user_id );

	if ( (int) $friend_ids['total'] < 1 )
		return false;
		

		
	for ( $i = 0; $i < $friend_ids['total']; $i++ ) {
	

	
		if ( classifieds_is_follower( $friend_ids['users'][$i]->id, $classified_id ) ) //skip if user is currently following classified
			continue;
			

			
		$display_name = bp_core_get_user_displayname( $friend_ids['users'][$i]->id );

		
		
		if ( $display_name != ' ' ) {
			$friends[] = array(
				'id' => $friend_ids['users'][$i]->id,
				'full_name' => $display_name
			);
		}
	}

	if ( !$friends )
		return false;

	return $friends;
}

function bp_new_classified_invite_friend_list() {
	echo bp_get_new_classified_invite_friend_list();
}
	function bp_get_new_classified_invite_friend_list( $args = '' ) {
		global $bp;
		
		if ( !function_exists('friends_install') )
			return false;
		
		$defaults = array(
			'classified_id' => false,
			'separator' => 'li'
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );
	
		if ( !$classified_id )
			$classified_id = ( $bp->classifieds->new_classified_id ) ? $bp->classifieds->new_classified_id : $bp->classifieds->current_classified->ID;
			
		$friends = classifieds_get_friends_invite_list($bp->loggedin_user->id,$classified_id );

		if ( $friends ) {
		

			
			/*$invites = classifieds_get_invites_for_classified( $bp->loggedin_user->id, $classified_id );*/

			for ( $i = 0; $i < count( $friends ); $i++ ) {
				/*
				if ( $invites ) {
					if ( in_array( $friends[$i]['id'], $invites ) ) {
						$checked = ' checked="checked"';
					} else {
						$checked = '';
					} 
				}
				*/
				
				$items[] = '<' . $separator . '><input' . $checked . ' type="checkbox" name="friends[]" id="f-' . $friends[$i]['id'] . '" value="' . attribute_escape( $friends[$i]['id'] ) . '" /> ' . $friends[$i]['full_name'] . '</' . $separator . '>';
			}
		}
		
		return implode( "\n", (array)$items );
	}


function bp_new_classified_id() {
	echo bp_get_new_classified_id();
}
	function bp_get_new_classified_id() {
		global $bp;
		return apply_filters( 'bp_get_new_classified_id', $bp->classifieds->new_classified_id );
	}
	
function bp_new_classified_name() {
	echo bp_get_new_classified_name();
}
	function bp_get_new_classified_name() {
		global $bp;
		return apply_filters( 'bp_get_new_classified_name', $bp->classifieds->current_classified->name );
	}

function bp_new_classified_description() {
	echo bp_get_new_classified_description();
}
	function bp_get_new_classified_description() {
		global $bp;
	
		return apply_filters( 'bp_get_new_classified_description', $bp->classifieds->current_classified->description );
	}
	
function bp_classified_description_editable() {
	echo bp_get_classified_description_editable();
}
	function bp_get_classified_description_editable( $classified = false ) {
		global $classifieds_template;

		if ( !$classified )
			$classified =& $classifieds_template->classified;

		return apply_filters( 'bp_get_classified_description_editable', $classified->description );
	}

function bp_get_classified_enable_wire() {
	global $bp;

	if ((!$bp->classifieds->current_classified) || ($bp->classifieds->current_classified->comment_status=='open')) $enable_wire=true;
	
	return (int) apply_filters( 'bp_get_classified_enable_wire', $enable_wire );
}
	

	

function bp_new_classified_action() {
	echo bp_get_new_classified_action();
}

	function bp_get_new_classified_action() {
		global $bp;

		$action_id = $bp->classifieds->current_classified->action;


		$actions = BP_Classifieds_Actions::get_all();
		
		if (empty($actions)) return false;
		
		$html.='<ul id="choose-action">';

		foreach ($actions as $action) {
			unset($checked);

			if ($action_id==$action->term_id) $checked=' CHECKED';
			$html .= '<li id="action-'.$action->term_id.'" class="action"><input type="radio" value="'.$action->name.'" name="classified-action[]"'.$checked.'/><span class="text">' . $action->name.'</span>';
		}
		
		$html.='</ul>';
		
		return apply_filters( 'bp_get_new_classified_action',$html );

	}

function bp_new_classified_categories() {
	echo bp_get_new_classified_categories();
}
	function bp_get_new_classified_categories() {
		global $bp;

		$array_categories = BP_Classifieds_Categories::get_children(0,true);

		$treeset = new TreeSet();
		
		$checked_cats = $bp->classifieds->current_classified->categories;


		return apply_filters( 'bp_get_new_classified_categories', $treeset -> drawTree($treeset -> buildTree($array_categories),$checked_cats,'classifieds_format_cat_for_creation'));
	}
	
function bp_new_classified_tags() {
	global $bp;
	echo bp_get_classified_tags_editable($bp->classifieds->current_classified);
}

function bp_classified_tags_editable() {
	echo bp_get_classified_tags_editable();
}
	function bp_get_classified_tags_editable( $classified = false ) {
		global $classifieds_template;

		if ( !$classified )
			$classified =& $classifieds_template->classified;
			
		if (empty($classified->tags)) return false;

		foreach($classified->tags as $tag_id) {

			if ($tag_id==$classified->action) continue;
			
			$tag=new BP_Classifieds_Tags($tag_id);
			if ($tag)
				$tags_names[]=$tag->name;
			unset($tag);

		}
		if ($tags_names)
			$tags_list=implode(',',$tags_names);

		return apply_filters( 'bp_get_classified_tags_editable', $tags_list );
	}
	
	

function bp_new_classified_status() {
	echo bp_get_new_classified_status();
}
	function bp_get_new_classified_status() {
		global $bp;
		return apply_filters( 'bp_get_new_classified_status', $bp->classifieds->current_classified->status );
	}

function bp_new_classified_avatar( $args = '' ) {
	echo bp_get_new_classified_avatar( $args );
}
	function bp_get_new_classified_avatar( $args = '' ) {
		global $bp;
			
		$defaults = array(
			'type' => 'full',
			'width' => false,
			'height' => false,
			'class' => 'avatar',
			'id' => 'avatar-crop-preview',
			'alt' => __( 'Classified Avatar', 'classifieds' ) 
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );
	
		return apply_filters( 'bp_get_new_classified_avatar', bp_core_fetch_avatar( array( 'item_id' => $bp->classifieds->current_classified->ID, 'object' => 'classified', 'type' => $type, 'avatar_dir' => 'classified-pictures', 'alt' => $alt, 'width' => $width, 'height' => $height, 'class' => $class, 'no_grav'=>true ) ) );
	}
	
function bp_classified_permalink() {
		echo bp_get_classified_permalink();
}
	function bp_get_classified_permalink( $classified = false ) {
		global $classifieds_template, $bp;

		if ( !$classified )
			$classified =& $classifieds_template->classified;

		return apply_filters( 'bp_get_classified_permalink', $bp->root_domain . '/' . $bp->classifieds->slug . '/' . $classified->slug . '/');
	}
	
	function bp_get_classified_permalink_id( $classified = false ) {
		global $classifieds_template, $bp;

		if ( !$classified )
			$classified =& $classifieds_template->classified;

		return apply_filters( 'bp_get_classified_permalink', $bp->root_domain . '/' . $bp->classifieds->slug . '/' . $classified->ID . '/');
	}
function bp_my_classifieds_permalink($status=false){
	echo bp_get_my_classifieds_permalink($status);
}	
	function bp_get_my_classifieds_permalink($status=false){
		global $bp;
		$link = $bp->loggedin_user->domain . $bp->classifieds->slug . '/'.__('my-classifieds','classifieds-slugs').'/';
		if ($status) {
			$link .=$status;
		}
		return apply_filters('bp_get_my_classifieds_permalink',$link);
	}

	
//CAPABILITIES
function bp_classifieds_no_capability() {
?>
	<div id="message" class="error">
		<p><?php echo apply_filters('bp_classifieds_no_capability_msg',__( 'You do not have access to this content.', 'classifieds' )); ?></p>
	</div>
<?php
}



function bp_classifieds_is_actions_enabled() {
	global $bp;

	return $bp->classifieds->options['actions_tags'];
}

function bp_classifieds_is_categories_enabled() {
	global $bp;
	
	$categories = BP_Classifieds_Categories::get_children(0,true);
	
	if (!empty($categories)) return true;
}

function bp_classified_is_wire_enabled( $classified = false ) {
	global $classifieds_template;

	if ( !$classified )
		$classified =& $classifieds_template->classified;
	
	if ( $classified->comment_status=='open')
		return true;
	
	return false;
}


function bp_classifieds_are_visible() {
	
	if ( !is_user_logged_in() ) {
		global $bp;
		if ($bp->classifieds->options['capabilities']['visitors']!=0) $are_visible=true;

	}else {
		$are_visible = bp_classified_user_can('Classifieds View Classifieds Lists');
	}
	
	if (!$are_visible) {
		add_action( 'bp_before_my_classifieds_loop','bp_classifieds_no_capability' );
		add_filter('bp_classifieds_get_query','classifieds_filter_return_false');
		add_filter('bp_classified_show_no_classifieds_message','classifieds_filter_return_false');
	}
	
	return $are_visible;
}
add_action('plugins_loaded','bp_classifieds_are_visible' );

function bp_classified_is_visible() {

	if ( !is_user_logged_in() ) {
		global $bp;
		if ($bp->classifieds->options['capabilities']['visitors']==2) $is_visible=true;
	}else {	
		$is_visible=bp_classified_user_can('Classifieds View Classified Details');
	}
	
	if (!$is_visible) {
		add_action( 'bp_classifieds_no_capability','bp_classifieds_no_capability' );
	}
	return $is_visible;
}

function bp_classified_is_unactive($classified = false) {
	global $bp;
	global $classifieds_template;
	
	if ( !$classified )
		$classified =& $classifieds_template->classified;

	if (classifieds_days_since_date($classified->date_created) > $bp->classifieds->options['days_active']) { //is unactive

		return true;
	}
}

function bp_classified_is_published($classified = false) {
	global $classifieds_template;
	
	if ( !$classified )
		$classified =& $classifieds_template->classified;

	if ($classified->status=='publish') return true;
}

function bp_my_classifieds_action() {
	global $bp;
	
	if (bp_is_home)
		return $bp->action_variables[0];

}

function bp_classified_id() {
	echo bp_get_classified_id();
}

	function bp_get_classified_id($classified = false) {
		global $classifieds_template;
		
		if ( !$classified )
			$classified =& $classifieds_template->classified;
			
		return apply_filters('bp_get_classified_id',$classified->ID);
	}
	
function bp_classified_name() {
	echo bp_get_classified_name();
}

	function bp_get_classified_name($classified = false) {
		global $classifieds_template;

		if ( !$classified ){
			$classified =&$classifieds_template->classified;
		}

		return apply_filters('bp_get_classified_name',$classified->name);
	}
function bp_classified_date_created() {
	echo bp_get_classified_date_created();
}
	function bp_get_classified_date_created( $classified = false ) {
		global $classifieds_template;

		if ( !$classified )
			$classified =& $classifieds_template->classified;
	
		return apply_filters( 'bp_get_classified_date_created', mysql2date(get_option( 'date_format' ), $classified->date_created) );
	}
	
function bp_classified_description_excerpt() {
	echo bp_get_classified_description_excerpt();
}
	function bp_get_classified_description_excerpt( $classified = false ) {
		global $classifieds_template;

		if ( !$classified )
			$classified =& $classifieds_template->classified;

		return apply_filters( 'bp_get_classified_description_excerpt', bp_create_excerpt( $classified->description, 20 ) );	
	}
	
function bp_classified_has_tags( $classified = false ) {
	global $classifieds_template;
	global $bp;

	if ( !$classified )
		$classified =& $classifieds_template->classified;
		
	if ($classified->tags) 
		return true;
		
	return false;
}
	
function bp_classified_tags() {
	echo bp_get_classified_tags();
}
	function bp_get_classified_tags( $classified = false ) {
		global $classifieds_template;
		global $bp;

		if ( !$classified )
			$classified =& $classifieds_template->classified;
			
		if ($classified->tags) {
		
			$action_tags = $bp->classifieds->options['actions_tags'];

			foreach ($classified->tags as $tag_id) {

				if ((!$action_tags) || (!in_array($tag_id,$action_tags))) {
					$tag = new BP_Classifieds_Tags($tag_id);
					
					$tags_block[]='<a class="tag" href="'.bp_get_classified_tag_permalink($tag).'">'.$tag->name.'</a>';
				}
			}
			if ($tags_block)
				$tags_block = implode('',$tags_block);
		}
		
		

		return apply_filters( 'bp_get_classified_tags', $tags_block );	
	}

function bp_classified_breadcrumb_author($classified = false,$avatar=true) {
	global $classifieds_template;

	if ( !$classified )
		$classified =& $classifieds_template->classified;
		
	$name = bp_core_get_userlink( $classified->creator_id );
	$link = bp_core_get_user_domain( $classified->creator_id );
	$thumb = bp_core_fetch_avatar( array( 'item_id' => $classified->creator_id, 'type' => 'thumb', 'width' => 20, 'height' => 20 ));
	?>
	<a class="classified-author" href="<?php echo $link; ?>"><?php echo $thumb.$name ?></a>

<?php
}
function bp_get_classified_author_id($classified = false ) {
	global $classifieds_template;

	if ( !$classified )
		$classified =& $classifieds_template->classified;

	return $classified->creator_id;
}

function bp_classified_author($classified = false ) {
	global $classifieds_template;

	if ( !$classified )
		$classified =& $classifieds_template->classified;
		
	$author_id = bp_get_classified_author_id($classified);

	$author = new BP_Core_User($author_id);
	
?>
		<ul id="classified-author">
			<li>
				<a href="<?php echo $author->user_url ?>" title="<?php echo $author->fullname ?>"><?php echo $author->avatar_mini ?></a>
				<h5><?php echo $author->user_link ?></h5>
				<span class="activity"><?php echo $author_title ?></span>
				<hr />
			</li>
		</ul>
<?php
}


function bp_classified_breadcrumb_action() {
	echo bp_get_classified_breadcrumb_action();
}

	function bp_get_classified_breadcrumb_action($classified=false) {
		global $classifieds_template, $bp;

		if ( !$classified )
			$classified =& $classifieds_template->classified;

			//ACTION
			if (bp_classifieds_is_actions_enabled()) {

				$action_id = $classified->action;
				
				if ($action_id) {
					$action = new BP_Classifieds_Tags($action_id);
					return apply_filters( 'bp_get_classified_breadcrumb_action','<a class="action" href="'.bp_get_classified_action_permalink($action).'">' . $action->name.'</a>');
				}
			}
	}
	
function bp_classified_tag_permalink($tag) {
		echo bp_get_classified_tag_permalink($tag);
}
	
	function bp_get_classified_tag_permalink($tag) {
			global $bp;

			$permalink = $bp->root_domain . '/' . $bp->classifieds->slug . '/tag/' . $tag->slug;
			
			return apply_filters( 'bp_get_classified_tag_permalink',$permalink);
	}
function bp_classified_avatar( $args = '' ) {
	echo bp_get_classified_avatar( $args );
}
	function bp_get_classified_avatar( $args = '' ) {
		global $bp, $classifieds_template;
		
		

		$defaults = array(
			'type' => 'full',
			'width' => false,
			'height' => false,
			'class' => 'avatar',
			'id' => false,
			'alt' => __( 'Classified avatar', 'buddypress' )
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );


		/* Fetch the avatar from the folder, if not provide backwards compat. */
		if ( !$avatar = bp_core_fetch_avatar( array( 'item_id' => $classifieds_template->classified->ID, 'object' => 'classified', 'type' => $type, 'avatar_dir' => 'classified-avatars', 'alt' => $alt, 'css_id' => $id, 'class' => $class, 'width' => $width, 'height' => $height ) ) )
			$avatar = '<img src="' . attribute_escape( $classifieds_template->classified->avatar_thumb ) . '" class="avatar" alt="' . attribute_escape( $classifieds_template->classified->name ) . '" />';

		return apply_filters( 'bp_get_classified_avatar', $avatar );
	}

function bp_classified_avatar_thumb() {
	echo bp_get_classified_avatar_thumb();
}
	function bp_get_classified_avatar_thumb( $classified = false ) {
		return bp_get_classified_avatar( 'type=thumb' );
	}

function bp_classified_avatar_mini() {
	echo bp_get_classified_avatar_mini();
}
	function bp_get_classified_avatar_mini( $classified = false ) {
		return bp_get_classified_avatar( 'type=thumb&width=30&height=30' );
	}
	
function bp_classified_can_follow($classified_id=false) {
	global $bp,$classifieds_template;

	if ( !$classified_id ) {
		$classified = & $classifieds_template->classified;
	}else {
		$classified = new BP_Classifieds_Classified( $classified_id );
	}

	if (!is_user_logged_in()) return false;	
	
	if (bp_classified_is_author($classified))	return false;

	if (!bp_classified_is_published($classified))	return false;

	return true;
	
}

function bp_classified_follow_button($classified = false) {
	echo bp_get_classified_follow_button($classified);
}
	
function bp_get_classified_follow_button( $classified = false) {
	
	// Check if button must be visible
	if (!bp_classified_can_follow()) return false;	

	global $bp, $classifieds_template;

	if ( !$classified )
		$classified =& $classifieds_template->classified;
		
	$is_follower = classifieds_is_follower( $bp->loggedin_user->id, $classified->ID );
	
	if ($is_follower) {
		$class="unfollow";
	}else {
		$class="follow";
	}

	$button.='<div class="generic-button follow-button ' . $class . '" id="follow-button-' . $classified->ID . '">';

	// is followed yet
	if ( $is_follower ) {
		$button.='<a href="' . wp_nonce_url( bp_get_classified_permalink_id( $classified ) . __('unfollow','classifieds-slugs'), 'classifieds_unfollow_classified' ) . '" title="' . __('Unfollow Classified', 'classifieds') . '" id="classified-' . $classified->ID . '" rel="unfollow" class="unfollow">' . __( 'Unfollow Classified', 'classifieds' ) . '</a>';
	}else{
		$button.='<a href="' . wp_nonce_url( bp_get_classified_permalink_id( $classified ) . __('follow','classifieds-slugs'), 'classifieds_follow_classified' ) . '" title="' . __('Follow Classified', 'classifieds') . '" id="classified-' . $classified->ID . '" rel="follow" class="follow">' . __( 'Follow Classified', 'classifieds' ) . '</a>';
	}
	
	$button.= '</div>';
	
	return apply_filters('bp_get_classified_follow_button',$button);


}

function bp_get_classified_publish_link( $classified = false) {
	global $bp, $classifieds_template;
	
	if ( !$classified )
		$classified =& $classifieds_template->classified;

	//PUBLISH BUTTON (awaiting classified)
	if ($classified->status=='pending') {
		// If they're not an admin or mod, no publish button.
		
		if (!bp_classifieds_user_can_publish_classified($classified)) return false;

		$link = wp_nonce_url( bp_get_classified_permalink_id( $classified ) .__('publish','classifieds-slugs'), 'classifieds_publish_classified' );
		
	//REPUBLISH BUTTON (awaiting classified)
	}else if (($classified->status=='publish') && bp_classified_is_unactive()) {
		if ((!bp_classified_user_can('Classifieds Republish Classifieds')) && (!bp_classified_user_can('Classifieds Edit Others Classifieds')))
			return false;
		$link = wp_nonce_url( bp_get_classified_permalink_id( $classified ) .__('republish','classifieds-slugs'), 'classifieds_republish_classified' );
			
	}
	return apply_filters('bp_get_classified_publish_link',$link);
}

function bp_get_classified_delete_link( $classified = false) {
	global $bp, $classifieds_template;
	
	if ( !$classified )
		$classified =& $classifieds_template->classified;
		
	if (!bp_classifieds_user_can_delete_classified($classified)) return false;
		
	$link = wp_nonce_url( bp_get_classified_permalink_id( $classified ) .__('delete','classifieds-slugs'), 'classifieds_delete_classified' );
	
	return apply_filters('bp_get_classified_delete_link',$link);
}

function bp_classified_classes() {
	echo implode(' ',bp_get_classified_classes());
}
	function bp_get_classified_classes( $classified = false ) {
		global $classifieds_template;
		global $bp;

		if ( !$classified )
			$classified =& $classifieds_template->classified;
			
		$classes[] = $classified->status;
		
		if (bp_classified_is_unactive($classified))
			$classes[] =' unactive';
			
		if (bp_classified_is_author()) $classes[] ='my-classified';
		
		if (!$bp->is_single) $classes[] ='classified-item';
		$classes[]='cat-'.$classified->category;

		return apply_filters( 'bp_get_classified_classes', $classes );	
	}

function bp_classified_type() {
		echo bp_get_classified_type();
}
	function bp_get_classified_type( $classified = false ) {
		global $classifieds_template;

		if ( !$classified )
			$classified =& $classifieds_template->classified;

		if ( 'publish' == $classified->status ) {
			$type = __( "Published", "classifieds" );
		} else if ( 'pending' == $classified->status ) {	
			$type = __( 'Pending');
		} else {
			$type = ucwords( $classified->status ) . ' ' . __( 'Classified', 'classifieds' );
		}
		
		if (bp_classified_is_unactive($classified))
			$type .=' ('.__( "Unactive", "classifieds" ).')';

		return apply_filters( 'bp_get_classified_type', $type );	
	}
function bp_classified_description() {
		echo bp_get_classified_description();
}
	function bp_get_classified_description( $classified = false ) {
		global $classifieds_template;

		if ( !$classified )
			$classified =& $classifieds_template->classified;

		return apply_filters( 'bp_get_classified_description', stripslashes($classified->description) );
	}
	
function bp_classified_total_followers() {
		echo bp_get_classified_total_followers();
}
	function bp_get_classified_total_followers($classified = false ) {
		global $classifieds_template;

		if ( !$classified )
			$classified =& $classifieds_template->classified;
			
		return apply_filters( 'bp_get_classified_total_followers', $classified->total_follower_count);
	}
	
function bp_classified_show_no_classifieds_message() {
	global $bp;

	if ( !bp_get_total_classified_count() ) {
		$show_msg=true;
	}else {
		$show_msg=false;
	}
	return apply_filters('bp_classified_show_no_classifieds_message',$show_msg);
}

function bp_classified_pagination() {
	echo bp_get_classified_pagination();
}
	function bp_get_classified_pagination() {
		global $classifieds_template;
		
		return apply_filters( 'bp_get_classified_pagination', $classifieds_template->pag_links );
	}

function bp_classifieds_pagination_count() {
	global $bp, $classifieds_template;

	$from_num = intval( ( $classifieds_template->pag_page - 1 ) * $classifieds_template->pag_num ) + 1;
	$to_num = ( $from_num + ( $classifieds_template->pag_num - 1 ) > $classifieds_template->total_classified_count ) ? $classifieds_template->total_classified_count : $from_num + ( $classifieds_template->pag_num - 1) ;

	echo sprintf( __( 'Viewing classified %d to %d (of %d classifieds)', 'classifieds' ), $from_num, $to_num, $classifieds_template->total_classified_count ); ?> &nbsp;
	<span class="ajax-loader"></span><?php 
}

function bp_classifieds_pagination_links() {
	echo bp_get_classified_pagination_links();
}
	function bp_get_classified_pagination_links() {
		global $classifieds_template;
		
		return apply_filters( 'bp_get_classifieds_pagination_links', $classifieds_template->pag_links );
	}


function bp_total_classified_count() {
	echo bp_get_total_classified_count();
}
	function bp_get_total_classified_count() {
		global $classifieds_template;

		return apply_filters( 'bp_get_total_classified_count', BP_Classifieds_Classified::get_classifieds_total() );
	}

function bp_total_member_classified_count($user_id=false) {
	echo bp_get_total_member_classified_count($user_id);
}
	function bp_get_total_member_classified_count($user_id=false) {
		global $bp;
		if (!$user_id)
			$user_id = $bp->loggedin_user->id;
			
		return apply_filters( 'bp_get_total_member_classified_count', BP_Classifieds_Classified::get_classifieds_total(array('user_id'=>$user_id)),$user_id );
	}
	
function bp_total_action_classified_count($action_id) {
	echo bp_get_total_action_classified_count($action_id);
}
	function bp_get_total_action_classified_count($action_id) {
		return apply_filters( 'bp_get_total_action_classified_count', BP_Classifieds_Classified::get_classifieds_total(array('action_tag'=>$action_id)),$action_id );
	}
	
function bp_total_category_classified_count($cat_id) {
	echo bp_get_total_category_classified_count($cat_id);
}
	function bp_get_total_category_classified_count($cat_id) {
		return apply_filters( 'bp_get_total_category_classified_count', BP_Classifieds_Classified::get_classifieds_total(array('cats'=>$cat_id)),$cat_id );
	}
	
	
	
function bp_classifieds_member_classifieds_type() {
	global $bp;
	
	if (!$bp->displayed_user->id) return false;
	
	return $bp->action_variables[0];
}

function bp_is_classified_admin_screen( $slug ) {
	global $bp;
	
	if ( $bp->current_component != BP_CLASSIFIEDS_SLUG || __('admin','classifieds-slugs') != $bp->current_action )
		return false;
		


	if ( $bp->action_variables[0] == $slug )
		return true;
	
	return false;
}

function bp_classified_admin_tabs( $classified = false ) {
	global $bp, $classifieds_template;

	if ( !$classified )
		$classified = ( $classifieds_template->classified ) ? $classifieds_template->classified : $bp->classifieds->current_classified;
	
	$current_tab = $bp->action_variables[0];
?>

	<?php
		if (!bp_classifieds_user_can_admin_classified($classified)) return false;
	?>
	<li<?php if ( __('edit-details','classifieds-slugs') == $current_tab || empty( $current_tab ) ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->root_domain . '/' . $bp->classifieds->slug.'/'.$classified->slug .'/'.__('admin','classifieds-slugs').'/'.__('edit-details','classifieds-slugs');?>"><?php _e('Edit Details', 'buddypress') ?></a></li>
	<li<?php if ( __('classified-settings','classifieds-slugs') == $current_tab ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->root_domain . '/' . $bp->classifieds->slug.'/'.$classified->slug .'/'.__('admin','classifieds-slugs').'/'.__('classified-settings','classifieds-slugs');?>"><?php _e('Classified Settings', 'classifieds') ?></a></li>

	<?php //TO FIX TO CHECK INVITES ? ?>

	
	<?php do_action( 'classifieds_admin_tabs', $current_tab, $classified->slug ) ?>
	
	<?php
	$publish_link = bp_get_classified_publish_link();
	if ($publish_link) {
		?>
		<li><a href="<?php echo $publish_link;?>">
		<?php
		if ((!bp_classified_is_unactive()) || (!bp_classified_is_published())) {
			_e('Publish Classified', 'classifieds');
		}else {
			_e('Republish Classified', 'classifieds');
		}
		?></a></li>
		<?php
	}
	
	$delete_link = bp_get_classified_delete_link();
	if ($delete_link) {
		?>
		<li><a href="<?php echo $delete_link;?>"><?php _e('Delete Classified', 'classifieds') ?></a></li>
		<?php
	}

}
function bp_classified_admin_form_action( $page = false) {
	echo bp_get_classified_admin_form_action( $page );
}
	function bp_get_classified_admin_form_action( $page = false, $classified = false ) {
		global $bp, $classifieds_template;

		if ( !$classified )
			$classified =& $classifieds_template->classified;

		if ( !$page )
			$page = $bp->action_variables[0];

		return apply_filters( 'bp_classified_admin_form_action', bp_get_classified_permalink( $classified ) . '/'.__('admin','classifieds-slugs').'/' . $page );
	}
	
function bp_classified_form_action( $page, $deprecated = false ) {
	echo bp_get_classified_form_action( $page );
}
	function bp_get_classified_form_action( $page, $classified = false ) {
		global $bp, $classifieds_template;

		if ( !$classified )
			$classified =& $classifieds_template->classified;

		return apply_filters( 'bp_classified_form_action', bp_get_classified_permalink( $classified ) . '/' . $page );
	}


function bp_classifieds_is_directory_all(){
	global $bp;
	
	if ((bp_is_classifieds_directory()) && (!$bp->current_action))
		return true;
	
	return false;
	
}
function bp_classifieds_is_directory_action($action){
	global $bp;
	
	if ((bp_is_classifieds_directory()) && ($bp->current_action==$action->slug))
		return true;
	
	return false;
}

function bp_is_classifieds_tag() {
	global $bp;
	
	if ((bp_is_classifieds_directory()) && ($bp->current_action==__('tag','classifieds-slugs'))) {
		return true;
	}
		
	return false;
}

	
function bp_is_user_classifieds() {
	global $bp;

	if ( BP_CLASSIFIEDS_SLUG == $bp->current_component )
		return true;

	return false;
}

function bp_is_classifieds_directory() {
	global $bp;
	
	$escape_slugs=array(
		__('create','classifieds-slugs')
	);

	if ( $bp->current_component == BP_CLASSIFIEDS_SLUG && !$bp->is_single_item && ( !in_array( $bp->current_action, $escape_slugs ) ) && !$bp->displayed_user->id) {
		return true;
		
	}
		
	return false;
}



function bp_is_classified() {
	global $bp;

	if ( BP_CLASSIFIEDS_SLUG == $bp->current_component && $bp->classifieds->current_classified )
		return true;

	return false;
}

function bp_is_classified_home() {
	global $bp;

	if ( BP_CLASSIFIEDS_SLUG == $bp->current_component && $bp->is_single_item && ( !$bp->current_action || __('home','classifieds-slugs') == $bp->current_action ) )
		return true;

	return false;
}

function bp_is_classified_create() {
	global $bp;

	if ( BP_CLASSIFIEDS_SLUG == $bp->current_component && __('create','classifieds-slugs') == $bp->current_action )
		return true;

	return false;
}


function bp_is_classified_admin_page() {
	global $bp;

	if ( BP_CLASSIFIEDS_SLUG == $bp->current_component && $bp->is_single_item && __('admin','classifieds-slugs') == $bp->current_action )
		return true;

	return false;
}

function bp_is_classified_wire() {
	global $bp;

	if ( BP_CLASSIFIEDS_SLUG == $bp->current_component && $bp->is_single_item && __('wire','classifieds-slugs') == $bp->current_action )
		return true;

	return false;
}



function bp_is_classified_followers() {
	global $bp;

	if ( BP_CLASSIFIEDS_SLUG == $bp->current_component && $bp->is_single_item && __('followers','classifieds-slugs') == $bp->current_action )
		return true;

	return false;
}

function bp_is_classified_invites() {
	global $bp;

	if ( BP_CLASSIFIEDS_SLUG == $bp->current_component && __('send-invites','classifieds-slugs') == $bp->current_action )
		return true;

	return false;
}


function bp_is_classified_single() {
	global $bp;

	if ( BP_CLASSIFIEDS_SLUG == $bp->current_component && $bp->is_single_item )
		return true;

	return false;
}

function bp_is_my_classifieds() {
	global $bp;
	if (!$bp->displayed_user->id) return false;
	if ($bp->current_action==__('my-classifieds','classifieds-slugs'))
		return true;
	return false;
}

function bp_is_my_classifieds_status($status) {
	if (!bp_is_my_classifieds()) return false;
	
	global $bp;
	
	if ($bp->action_variables[0]==$status)
		return true;
	return false;
	
}

/////////////////ACTIVITY/////////////

function bp_classifieds_activity_filter_options() {
	if ( bp_is_active( 'classifieds' ) ) : ?>
		<option value="published_classified,republished_classified"><?php _e( 'Show New Classifieds', 'classifieds' ) ?></option>
	<?php
	endif;
}

function bp_classifieds_member_activity_filter_options() {
	if ( bp_is_active( 'classifieds' ) ) : ?>
		<option value="published_classified,republished_classified"><?php _e( 'Show New Classifieds', 'classifieds' ) ?></option>
		<option value="followed_classified,unfollowed_classified"><?php _e( 'Show Followed Classifieds', 'classifieds' ) ?></option>
	<?php
	endif;
}

add_action('bp_activity_filter_options','bp_classifieds_activity_filter_options');

add_action( 'bp_member_activity_filter_options','bp_classifieds_member_activity_filter_options');

/***
 * Classifieds RSS Feed Template Tags
 */

function bp_classified_activity_feed_link() {
	echo bp_get_classified_activity_feed_link();
}
	function bp_get_classified_activity_feed_link() {
		global $bp;

		return apply_filters( 'bp_get_classified_activity_feed_link', bp_get_classified_permalink( $bp->classifieds->current_classified ) . 'feed/' );
	}

function bp_current_classified_name() {
	echo bp_get_current_classified_name();
}
	function bp_get_current_classified_name() {
		global $bp;

		$name = apply_filters( 'bp_get_classified_name', $bp->classifieds->current_classified->name );
		return apply_filters( 'bp_get_current_classified_name', $name );
	}


//////////////TEMPLATES////////////////////


function bp_classifieds_enqueue_url($file){
	// split template name at the slashes
	
	$stylesheet_path = get_stylesheet_directory_uri();
	$suffix = explode($stylesheet_path,$file);	
	
	$suffix_str=$suffix[1];
	
	$file_path_to_check = BP_CLASSIFIEDS_PLUGIN_DIR . '/theme'.$suffix_str;
	$file_url_to_return = BP_CLASSIFIEDS_PLUGIN_URL . '/theme'.$suffix_str;

	if ( file_exists($file)) {
		return $file;
	}elseif ( file_exists($file_path_to_check)) {
		return $file_url_to_return;
	}
}
add_filter( 'bp_classifieds_enqueue_url', 'bp_classifieds_enqueue_url' );

/**
 * Check if template exists in style path, then check custom plugin location (code snippet from MrMaz)
 *
 * @param array $template_names
 * @param boolean $load Auto load template if set to true
 * @return string
 */
function bp_classifieds_locate_template( $template_names, $load = false ) {

	if ( !is_array( $template_names ) )
		return '';

	$located = '';
	foreach($template_names as $template_name) {

		// split template name at the slashes
		$paths = explode( '/', $template_name );
		
		// only filter templates names that match our unique starting path
		if ( !empty( $paths[0] ) && 'classifieds' == $paths[0] ) {


			$style_path = STYLESHEETPATH . '/' . $template_name;
			$plugin_path = BP_CLASSIFIEDS_PLUGIN_DIR . "/theme/{$template_name}";

			if ( file_exists( $style_path )) {
				$located = $style_path;
				break;
			} else if ( file_exists( $plugin_path ) ) {
				$located = $plugin_path;
				break;
			}
		}
	}

	if ($load && '' != $located)
		load_template($located);

	return $located;
}

/**
 * Filter located BP template (code snippet from MrMaz)
 *
 * @see bp_core_load_template()
 * @param string $located_template
 * @param array $template_names
 * @return string
 */
function bp_classifieds_filter_template( $located_template, $template_names ) {

	// template already located, skip
	if ( !empty( $located_template ) )
		return $located_template;

	// only filter for our component
	if ( $bp->current_component == $bp->classifieds->slug ) {
		return bp_classifieds_locate_template( $template_names );
	}

	return '';
}
add_filter( 'bp_located_template', 'bp_classifieds_filter_template', 10, 2 );

/**
 * Use this only inside of screen functions, etc (code snippet from MrMaz)
 *
 * @param string $template
 */
function bp_classifieds_load_template( $template ) {
	bp_core_load_template( $template );
}


	
?>