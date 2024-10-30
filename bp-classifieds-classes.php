<?php


//SINGLE CLASSIFIED CLASS
//TO FIX : must return false if $id=0
class BP_Classifieds_Classified {

	var $ID;
	var $status;
	var $creator_id;
	var $date_created;
	var $name;
	var $description;
	var $tags;
	var $action;
	var $categories;
	var $enable_wire;
	var $slug;
	var $guid;
	
	function bp_classifieds_classified( $post_or_id = null) {
		global $wpdb, $bp;
		

		if(is_object($post_or_id)) {
			$post = $post_or_id;
			$id = $post->ID;
		}else{
			$id = (int)$post_or_id;
		}


		if ( $id ) {
			$this->ID = $id;
			
			if (!$post) {
				switch_to_blog($bp->classifieds->options['blog_id']);
				$post=get_post($this->ID);
				restore_current_blog();
			}
			
			$this->populate( $post );

			
		}
	}
	function populate($post) {
		global $bp;
		global $wpdb;

		if ($post) {
			$this->ID = $post->ID;
			$this->status = $post->post_status;
			$this->creator_id = $post->post_author;
			$this->date_created = $post->post_date;
			$this->name = $post->post_title;
			$this->description = $post->post_content;
			
			$this->comment_status = $post->comment_status;
			$this->slug = $post->post_name;
			$this->categories = self::get_categories();
			
			switch_to_blog($bp->classifieds->options['blog_id']);
			$this->tags = self::get_tags();
			$this->action = self::get_action();
			restore_current_blog();

		}
		

	}
	function classified_exists($post_slug) {
		global $bp;
		
		if (!$post_slug) return false;
		
		
		global $wpdb;
		$result = $wpdb->get_col("SELECT ID FROM {$bp->classifieds->table_name_classifieds} WHERE post_name='{$post_slug}' AND ({$bp->classifieds->table_name_classifieds}.post_status = 'pending' OR {$bp->classifieds->table_name_classifieds}.post_status = 'publish')");
		if($result){
			return $result[0];
			
		}

		
		/*OLD WAY - was not working when post was pending
		
		switch_to_blog($bp->classifieds->options['blog_id']);
		
		//TO FIX : do not work when post is pending
		$classified = query_posts('name='.$post_slug.'&post_status=publish,pending');
		switch_to_blog($bp->classifieds->options['blog_id']);

		//Hide TRASH 
		if($classified[0]->post_status=='trash') return false;
		
		return $classified[0]->ID;
		*/

	}

	
	function get_classifieds_total( $args=false, $limit = false, $page = false) {
		$args['just_total']=true;
		$classifieds=self::get_classifieds( $args, $limit, $page);
		return $classifieds['total'];
	}
	

	function get_classifieds( $args=false, $limit = false, $page = false) {

		global $bp;
		global $wpdb;

		$defaults = array(
			'user_id' => false,
			'action_tag' => false,
			'cats' => false,
			'tag' => false,
			'status' => 'publish',
			'days_back' =>true,
			'followed' =>false,
			'group_id'	=>false,
			'search_terms'=>false,
			'order' => 'DESC',
			'sort_by' => 'date_created',
			'just_total' => false
		);
		
		$args = wp_parse_args($args, $defaults );

		extract( $args, EXTR_SKIP );

		$query_post_args=array();
	
		//SUPPRESS FILTERS
		$query_post_args['suppress_filters']=false;
		//POST TYPE
		$query_post_args['post_type']='post';
		
		
		//TO FIX
		//showposts,paged & offset...
		//LIMIT
		if ($limit)
		$query_post_args['showposts']=$limit;
		//PAGE
		$page=$page-1;
		$query_post_args['offset']=$page*$limit;
		if ($page) $query_post_args['paged']=$page;
		
		
		
		
		//ACTION+TAG
		$tags=array();
		if ($action_tag) $tags[]=$action_tag;
		if ($tag) $tags[]=$tag;
		
		if ($tags) $query_post_args['tag__and']=$tags;

		
		/*
		$tags_slugs=array();
		foreach($tags as $tag_id) {
			$tag_obj = new BP_Classifieds_Tags($tag_id);
			$tags_slugs[]=$tag_obj->slug;		
		}
		if (!empty($tags_slugs)) $query_post_args['tag']=implode('+',$tags_slugs);
		*/
		//CATS
		if (!empty($cats)) {
			//explode cats if it's an ajax query
			if (!is_array($cats)) $cats=explode(',',$cats);
			$query_post_args['category__in']=$cats;
		}
		//STATUS
		if ('unactive' == $status ) {
			if (!function_exists('get_classifieds_filter_time_lastdays')) { //avoid redeclare
				function get_classifieds_filter_time_unactive($where = '') {
					global $bp;
					$where .= " AND post_date <= '" . date('Y-m-d', strtotime('-'.$bp->classifieds->options['days_active'].' days')) . "'";
					return $where;
				}
			}
			add_filter('posts_where', 'get_classifieds_filter_time_unactive' );
			
			unset($days_back);
		}elseif ('followed' == $status ) {
			$query_post_args['meta_key']='classified_follower';
			$query_post_args['meta_value']=$user_id;
			unset($user_id);
			unset($days_back);
		}else {
			$query_post_args['status']=$status;
		}
		//USER ID
		if ($user_id) $query_post_args['author']=$user_id;
		
		//GROUP ID
		if ($group_id) {
			$query_post_args['meta_key']='classified_group';
			$query_post_args['meta_value']=$group_id;
		}

		//FOLLOWED
		
		//ORDER
		$query_post_args['order']=$order;
		//ORDERBY
		$query_post_args['orderby']=$sort_by;



		
		switch_to_blog($bp->classifieds->options['blog_id']);
		
		
		//FILTERS FOR GET_POSTS
		//filter query fields to get only posts IDs
		if (!function_exists('get_classifieds_filter_fields')) { //avoid redeclare
			function get_classifieds_filter_fields($fields) {
				global $bp;
				return $fields;
				return $bp->classifieds->table_name_classifieds.".ID";
			}
		}
		if ($just_total)
			add_filter('posts_fields', 'get_classifieds_filter_fields' );
		
		//filter that sets a global containing the total rows (no limit)
		if (!function_exists('get_classifieds_count')) { //avoid redeclare
			function get_classifieds_count($count) {
				global $query_classifieds_count;
				$query_classifieds_count = $count;
				return $count;
			}
		}
		add_filter('found_posts','get_classifieds_count');
		
		//filter days back
		if ($days_back) {
				if (!function_exists('get_classifieds_filter_time_lastdays')) {
					function get_classifieds_filter_time_lastdays($where = '') { //avoid redeclare
						global $bp;

						$where .= " AND post_date >= '" . date('Y-m-d', strtotime('-'.$bp->classifieds->options['days_active'].' days')) . "'";
						return $where;
					}
				}
		
		add_filter('posts_where', 'get_classifieds_filter_time_lastdays' );

		}
		
		//filter search terms
		//TO FIX : retrieve $search_terms inside function get_classifieds_filter_keywords

		if (($search_terms) && ($search_terms!=__( 'Search anything...', 'buddypress' ))) {
		
			$function = '$where .= " AND (post_title LIKE \'%' . $search_terms . '%\' OR post_content LIKE \'%' . $search_terms . '%\')";return $where;';

			if (!function_exists('get_classifieds_filter_keywords')) { //avoid redeclare
				function get_classifieds_filter_keywords($where = '') {
					$where .= "(post_title LIKE '%" . $search_terms . "%' OR post_content LIKE '%" . $search_terms . "%')";
					return $where;
					
				}
			}
		
			add_filter('posts_where', create_function('$where', $function) );
		}
		
		
		$posts = get_posts($query_post_args);
		
		//remove filters
		remove_filter('posts_where', 'get_classifieds_filter_keywords' );
		remove_filter('posts_where', 'get_classifieds_filter_time_lastdays' );
		remove_filter('posts_fields', 'get_classifieds_filter_fields' );
		remove_filter('found_posts','get_classifieds_count');
		
		//GET POST COUNT | see function get_classifieds_count
		global $query_classifieds_count;
		$total = $query_classifieds_count;

		restore_current_blog();

		if (!empty($posts)) {
			$classifieds=array();

			foreach ($posts as $post) {
				$classifieds[] = new BP_Classifieds_Classified($post);
			}
		}
		

		return array( 'classifieds' => $classifieds, 'total' => $total );
			
	}
	

	
	//get categories for single classified
	function get_categories() {
		global $bp;
		
		switch_to_blog($bp->classifieds->options['blog_id']);
		$cats = wp_get_post_categories($this->ID);
		restore_current_blog();
		
		return $cats;
	}
	
	//get tags for single classified
	function get_tags() {
		global $bp;

		switch_to_blog($bp->classifieds->options['blog_id']);
		$tags = wp_get_post_tags($this->ID);
		restore_current_blog();

		foreach ($tags as $tag) {
			$tags_ids[]=$tag->term_id;
		}
		
		return $tags_ids;
	}
	
	//get action for single classified (from tags)
	function get_action() {
		global $bp;

		
		$action_tags = $bp->classifieds->options['actions_tags'];
		
		$tags = $this->tags;

		if (($action_tags) && ($tags) ) {
		
			foreach ($tags as $tag_id) {
				if (in_array($tag_id,$action_tags))
					return $tag_id;
			}

		}

	}
	
	function save() {
		global $wpdb, $bp;
		
		$this->creator_id = apply_filters( 'bp_classified_data_author_before_save', $this->creator_id, $this->ID );
		$this->name = apply_filters( 'bp_classified_data_name_before_save', $this->name, $this->ID );
		$this->description = apply_filters( 'bp_classified_data_description_before_save', $this->description, $this->ID );
		$this->tags = apply_filters( 'bp_classified_data_tags_before_save', $this->tags, $this->ID );
		$this->action = apply_filters( 'bp_classified_data_tags_before_save', $this->action, $this->ID );
		$this->categories = apply_filters( 'bp_classified_data_categories_before_save', $this->categories, $this->ID );
		$this->comment_status = apply_filters( 'bp_classified_data_comment_status_before_save', $this->comment_status, $this->ID );

		/* Call a before save action here */
		do_action( 'bp_classified_data_before_save', $this );

		//TO FIX
		

		/*NOTE
		$this->action : is arr.slugs @ creation THEN (int)id
		$this->tags : is str.slugs @ creation THEN array ids
		*/
		


		if ($this->tags) {
			if (is_array($this->tags)) { //array of IDS
				$tags=bp_get_classified_tags_editable($this); //str of slugs
				$tags = explode(',',$tags);
			}else {
				$tags = explode(',',$this->tags);
			}
		}else {
			$tags=array();
		}

		if ($this->action) {
			if (!is_array($this->action)) { //at edition - ID
				$action = new BP_Classifieds_Tags($this->action);
				$tags[]=$action->name;
			}else { 
				$tags = array_merge($tags,$this->action);
			}
		}

		if ($tags) {
			$tags = array_unique($tags);
			$tags = implode(',',$tags); //str slugs
		}

		$post = array(
			'ID'			=> $this->ID,
			'comment_status' => $this->comment_status,
			'post_status' => $this->status,
			'post_type' => 'post',
			'post_name' => $this->slug,
			'tags_input'	=> $tags,
			'post_author'	=> $this->creator_id,
			'post_title'	=> $this->name,
			'post_content'	=> $this->description,
			'post_date'		=> $this->post_date,
			'post_guid'		=> 'guid',
			
		);

			
		if ($this->categories)
			$post['post_category']=$this->categories;
			
			
		if (!$this->slug) {//when saving as draft; do no create slug and we need to
			
			$slug = sanitize_title_with_dashes($this->name);
			$slug = apply_filters( 'groups_group_slug_before_save', $slug, $this->id ); // TO CHECK
			$post['post_name']= $slug;
			$this->slug = $slug;
		}
		
		/*delete BP action hook (which sends a "new post" activity*/
		remove_action('save_post', 'bp_blogs_record_post', 10, 2 );

		switch_to_blog($bp->classifieds->options['blog_id']);
		if (!$this->ID) { //new post
			$this->ID = wp_insert_post($post);
		}else {
			//TO FIX : slug is lost when editing a post as non-admin
			$this->ID = wp_update_post($post);
		}

		restore_current_blog();
		
		if (!$this->ID) return false;

		/* Add an after save action here */
		do_action( 'bp_classified_data_after_save', $this ); 
		
		return $this->ID;
	}

	/**
	 * delete()
	 *
	 * This method will delete the corresponding row for an object from the database.
	 */	
	function delete() {
		global $bp;
		
		switch_to_blog($bp->classifieds->options['blog_id']);
		$result = wp_delete_post( $this->ID );
		restore_current_blog();
		
		return $result;
	}

}


/**
* Categories
 */

class BP_Classifieds_Categories {
	var $ID;
	var $name;
	var $slug;
	var $description;
	var $parent;
	var $count;
	
	/**
	 * bp_example_tablename()
	 *
	 * This is the constructor, it is auto run when the class is instantiated.
	 * It will either create a new empty object if no ID is set, or fill the object
	 * with a row from the table if an ID is provided.
	 */
	function bp_classifieds_categories($id=null) {
		global $wpdb, $bp;
		
		if ($id) {
			$this->populate($id);
		}
		
	}
	
	/**
	 * populate()
	 *
	 * This method will populate the object with a row from the database, based on the
	 * ID passed to the constructor.
	 */
	function populate($id) {
		global $wpdb, $bp;
		
		if ( $row = $wpdb->get_row( $wpdb->prepare("SELECT t.term_id as ID,t.name,t.slug,tt.description,tt.parent,tt.count FROM {$bp->classifieds->table_name_terms} t"
		." LEFT JOIN {$bp->classifieds->table_name_term_taxonomy} tt ON t.term_id = tt.term_id"
		." WHERE t.term_id=%d AND tt.taxonomy='category'",$id ) ) ) {
			$this->ID=$row->ID;
			$this->name = $row->name;
			$this->slug = $row->slug;
			$this->description = $row->description;
			$this->parent = $row->parent;
			$this->count = $row->count;
		}
	}



	/* Static Functions */

	/**
	 * Static functions can be used to bulk delete items in a table, or do something that
	 * doesn't necessarily warrant the instantiation of the class.
	 *
	 * Look at bp-core-classes.php for examples of mass delete.
	 */
	function get_children($parent=false,$exclude_default=false) {
		global $wpdb, $bp;
		
		if ($parent)
			$more_sql['parent'] = 'tt.parent='.$parent;
		
		//skip default category (forces the user to choose a category)
		//TO FIX : default cat is ignored in the search panel
		if ($exclude_default)
			$more_sql['skip_default'] = 't.term_id!=1';
		
		$more_sql['type'] = "tt.taxonomy='category'";
		
		$more_query=implode(' AND ',$more_sql);
		
		$children = $wpdb->prepare("SELECT t.term_id as ID,t.name,t.slug,tt.description,tt.parent,tt.count FROM {$bp->classifieds->table_name_terms} t"
		." LEFT JOIN {$bp->classifieds->table_name_term_taxonomy} tt ON t.term_id = tt.term_id"
		." WHERE {$more_query} ORDER BY t.name ASC");


		return $wpdb->get_results( $children );
		

		
	}
	
	function get_parents($id,$array,$ignore_first=true) {
		global $bp;

		$parents=array();

			foreach ($array as $item) {
			
				if ($item->ID==$id) {

					if (!$ignore_first) 
					$parents[]=$item;
					
					

					if ($item->parent) {
						$more_parents=self::get_parents($item->parent,$array,false);
						$parents = array_merge($parents,$more_parents);
					}
				}
			}
			
			return $parents;
	}

	
	function check_category($cat) {
		global $wpdb, $bp;
		
		if (!$cat) return false;
		
		if (is_numeric($cat)) {
			$key='id';
		}else {
			$key='slug';
		}

		
		if ( $row = $wpdb->get_row( $wpdb->prepare( "SELECT id,slug FROM {$bp->classifieds->table_name_categories} WHERE $key = %s",$cat ) )) {
			$result['id']=$row->id;
			$result['slug']=$row->slug;
		
			return $result;
		}
	}
	
	function get_id_from_slug($slug) {
		global $wpdb, $bp;
		
		$id = $wpdb->get_row( $wpdb->prepare("SELECT t.term_id as ID FROM {$bp->classifieds->table_name_terms} t"
		." LEFT JOIN {$bp->classifieds->table_name_term_taxonomy} tt ON t.term_id = tt.term_id"
		." WHERE t.slug=%s AND tt.taxonomy='category'",$slug ) );
		
		if ($id) return $id;
	}
}

/**
* Tags
 */

class BP_Classifieds_Tags {

	var $term_id;
	var $name;
	var $slug;
	var $term_group;
	var $term_taxonomy_id;
	var $description;
	var $parent;
	var $count;
	
	/**
	 * bp_example_tablename()
	 *
	 * This is the constructor, it is auto run when the class is instantiated.
	 * It will either create a new empty object if no ID is set, or fill the object
	 * with a row from the table if an ID is provided.
	 */
	function bp_classifieds_tags($id=null) {
		global $wpdb, $bp;
		
		if ($id) {
			$this->populate($id);
		}
		
	}
	
	/**
	 * populate()
	 *
	 * This method will populate the object with a row from the database, based on the
	 * ID passed to the constructor.
	 */
	function populate($id) {
		global $wpdb, $bp;

		$query = $wpdb->prepare("SELECT t.term_id,t.name,t.slug,t.term_group,tt.term_taxonomy_id,tt.taxonomy,tt.description,tt.parent,tt.count FROM {$bp->classifieds->table_name_terms} t"
		." LEFT JOIN {$bp->classifieds->table_name_term_taxonomy} tt ON t.term_id = tt.term_id"
		." WHERE t.term_id=%d AND tt.taxonomy='post_tag'",$id );
		
		if ( $row = $wpdb->get_row( $query ) ) {
			$this->term_id = $row->term_id;
			$this->name = $row->name;
			$this->slug = $row->slug;
			$this->term_group = $row->term_group;
			$this->term_taxonomy_id = $row->term_taxonomy_id;
			$this->description = $row->description;
			$this->parent = $row->parent;
			$this->count = $row->count;

			
		}
		
	}
	/* Static Functions */
	
	function get_all($args=false) {
		global $bp;
		
		if (!$args)	$args=array();
	
		switch_to_blog($bp->classifieds->options['blog_id']);
		$tags = get_terms( 'post_tag', array_merge( $args, array( 'orderby' => 'count', 'order' => 'DESC' ) ) ); // Always query top tags
		restore_current_blog();
	
		return $tags;

	}
	
	function get_id_from_slug($slug) {
		global $wpdb, $bp;
		

		
		$id = $wpdb->get_var( $wpdb->prepare("SELECT t.term_id as ID FROM {$bp->classifieds->table_name_terms} t"
		." LEFT JOIN {$bp->classifieds->table_name_term_taxonomy} tt ON t.term_id = tt.term_id"
		." WHERE t.slug=%s AND tt.taxonomy='post_tag'",$slug ) );

		if ($id) return $id;
	}

	
}

/**
* Actions
 */

class BP_Classifieds_Actions {
	var $term_id;
	var $name;
	var $slug;
	var $term_group;
	var $term_taxonomy_id;
	var $description;
	var $parent;
	var $count;
	
	/**
	 * bp_example_tablename()
	 *
	 * This is the constructor, it is auto run when the class is instantiated.
	 * It will either create a new empty object if no ID is set, or fill the object
	 * with a row from the table if an ID is provided.
	 */
	function bp_classifieds_action($id=null) {
		global $wpdb, $bp;
		
		if ($id) {
			$this->populate($id);
		}
		
	}
	
	/**
	 * populate()
	 *
	 * This method will populate the object with a row from the database, based on the
	 * ID passed to the constructor.
	 */
	function populate($id) {
	
		$actions = self::get_all();
		foreach ($actions as $action) {
			if ($action->term_id==$action_id)
				$row = $action;
		}
		
		$this->term_id = $row->term_id;
		$this->name = $row->name;
		$this->slug = $row->slug;
		$this->term_group = $row->term_group;
		$this->term_taxonomy_id = $row->term_taxonomy_id;
		$this->description = $row->description;
		$this->parent = $row->parent;
		$this->count = $row->count;

		

	}
	/* Static Functions */
	
	function get_all() {
		global $bp;
		$actions_ids = $bp->classifieds->options['actions_tags'];
		
		foreach ($actions_ids as $action_id) {
			$actions[]=new BP_Classifieds_Tags($action_id);
		}
		
		return $actions;
	}
	
	function get_id_from_slug($slug) {
	
		$actions = self::get_all();
		
		foreach ($actions as $action) {
			if ($action->slug==$action_id)
				return $action->term_id;
		}
	}
}

class TreeSet {



    public function reindexTree($tree) {

        foreach($tree[0] as $id){
            $ordered_tree[]= $this -> reindexBranch($id,$tree,true);
        }
		
		$objTmp = (object) array('aFlat' => array());
		array_walk_recursive($ordered_tree, create_function('&$v, $k, &$t', '$t->aFlat[] = $v;'), $objTmp);

		$new_array = $objTmp->aFlat;

      return $new_array;
    }

    public function reindexBranch($id,$tree,$isRoot = false) {

		$branch = $tree[$id];
		
		$items[] = $branch['item'];
		
        unset($branch['item']);

        if(count($branch) > 0){
            foreach($branch as $id){
                $children= $this -> reindexBranch($id,$tree);
				$items[]=$children;

				
            }
        }   

        return $items;
    }


    /**
     * Draw folder tree structure
     *
     * First step in a recursive drawing operation
     *
     * @param array $tree
     * @return string
     */
    public function drawTree($tree,$array_checked=false,$format_item_fn=false) {
		
		if ($array_checked) {
			global $checked_branches;
			$checked_branches = $array_checked;
		}

        foreach($tree[0] as $id){
            $html .= $this -> drawBranch($id,$tree,$format_item_fn,true);
        }

        return '<ul class="categories tree">'.$html.'</ul>';
		
		unset($checked_branches);
    }

       
    /**
     * Recursively draw tree branches
     *
     * @param int $id
     * @param array $tree
     * @param boolean $isRoot
     * @return string
     */
	 
    public function drawBranch($id,$tree,$format_item_fn=false,$isRoot = false) {

		$branch = $tree[$id];
		
		$item = $branch['item'];
		
		
		
		if (!$format_item_fn) {
			$html = '<li id=cat-'.$item->ID.' class="category"><span class="text">' . $item->name.'</span>';
		}else {
			if (is_array($format_item_fn)) {
				$html = $format_item_fn[0]->$format_item_fn[1]($item);
			}else{
				$html = $format_item_fn($item);
			}
		}
		
		
        unset($branch['item']);

        if(count($branch) > 0){
            $html .= '<ul>';
            foreach($branch as $id){
                $html .= $this -> drawBranch($id,$tree,$format_item_fn);
            }
            $html .= '</ul>';
        }   
        $html .= '</li>';
        return $html;
    }
   
    /**
     * Build tree structure from array
     *
     * Reorganizes flat array into a tree like structure
     *
     * @param array $categories
     * @return array
     */
	 
    public function buildTree($items) {
	
        $tree = array(0 => array());
        foreach($items as $item){
		
            $tree[$item->ID]['item'] = $item;
            if(!is_null($item->parent)){
                if(!isset($tree[$item->parent])){
                    $tree[$item->parent] = array();
                }
                $tree[$item->parent][$this -> findFreeIndex($tree[$item->parent],1)] = $item->ID;
            } else {
                $tree[0][$this -> findFreeIndex($tree[0],1)] = $item->ID;
            }
        }
		
        ksort($tree,SORT_ASC);


        return $tree;
    }
	/**
	 * Determine next un-used array index
	 *
	 * @param array $array
	 * @param int $startInd
	 * @return int
	 */
    protected function findFreeIndex($array,$startInd = 0) {
        return (isset($array[$startInd]) ? $this -> findFreeIndex($array,$startInd + 1) : $startInd);
    }
}


//	/!\	classifieds : replaced locate_template by bp_classifieds_locate_template
/**
 * API for creating classified extensions without having to hardcode the content into
 * the theme.
 *
 * This class must be extended for each classified extension and the following methods overridden:
 *
 * BP_Classified_Extension::widget_display(), BP_Classified_Extension::display(),
 * BP_Classified_Extension::edit_screen_save(), BP_Classified_Extension::edit_screen(),
 * BP_Classified_Extension::create_screen_save(), BP_Classified_Extension::create_screen()
 *
 * @package BuddyPress
 * @subpackage Classifieds
 * @since 1.1
 */
class BP_Classified_Extension {
	var $name = false;
	var $slug = false;

	/* Will this extension be visible to non-members of a classified? Options: public/private */
	var $visibility = 'public';

	var $create_step_position = 81;
	var $nav_item_position = 81;

	var $enable_create_step = true;
	var $enable_nav_item = true;
	var $enable_edit_item = true;

	var $nav_item_name = false;

	var $display_hook = 'classifieds_custom_classified_boxes';
	var $template_file = 'classifieds/single/plugins';

	// Methods you should override

	function display() {
		die( 'function BP_Classified_Extension::display() must be over-ridden in a sub-class.' );
	}

	function widget_display() {
		die( 'function BP_Classified_Extension::widget_display() must be over-ridden in a sub-class.' );
	}

	function edit_screen() {
		die( 'function BP_Classified_Extension::edit_screen() must be over-ridden in a sub-class.' );
	}

	function edit_screen_save() {
		die( 'function BP_Classified_Extension::edit_screen_save() must be over-ridden in a sub-class.' );
	}

	function create_screen() {
		die( 'function BP_Classified_Extension::create_screen() must be over-ridden in a sub-class.' );
	}

	function create_screen_save() {
		die( 'function BP_Classified_Extension::create_screen_save() must be over-ridden in a sub-class.' );
	}

	// Private Methods

	function _register() {
		global $bp;


		if ( $this->enable_create_step ) {
			/* Insert the classified creation step for the new classified extension */
			$bp->classifieds->classified_creation_steps[$this->slug] = array( 'name' => $this->name, 'slug' => $this->slug, 'position' => $this->create_step_position );

			/* Attach the classified creation step display content action */
			add_action( 'classifieds_custom_create_steps', array( &$this, 'create_screen' ) );

			/* Attach the classified creation step save content action */
			//not needed, ajax !
			//add_action( 'classifieds_create_classified_step_save_' . $this->slug, array( &$this, 'create_screen_save' ) );
		}

		/* Construct the admin edit tab for the new classified extension */
		if ( $this->enable_edit_item ) {
			add_action( 'classifieds_admin_tabs', create_function( '$current, $classified_slug', 'if ( "' . attribute_escape( $this->slug ) . '" == $current ) $selected = " class=\"current\""; echo "<li{$selected}><a href=\"' . $bp->root_domain . '/' . $bp->classifieds->slug . '/{$classified_slug}/admin/' . attribute_escape( $this->slug ) . '\">' . attribute_escape( $this->name ) . '</a></li>";' ), 10, 2 );

			/* Catch the edit screen and forward it to the plugin template */
			if ( $bp->current_component == $bp->classifieds->slug && 'admin' == $bp->current_action && $this->slug == $bp->action_variables[0] ) {
				add_action( 'wp', array( &$this, 'edit_screen_save' ) );
				add_action( 'classifieds_custom_edit_steps', array( &$this, 'edit_screen' ) );

				if ( '' != bp_classifieds_locate_template( array( 'classifieds/single/home.php' ), false ) ) {
					bp_core_load_template( apply_filters( 'classifieds_template_classified_home', 'classifieds/single/home' ) );
				} else {
					add_action( 'bp_template_content_header', create_function( '', 'echo "<ul class=\"content-header-nav\">"; bp_classified_admin_tabs(); echo "</ul>";' ) );
					add_action( 'bp_template_content', array( &$this, 'edit_screen' ) );
					bp_core_load_template( apply_filters( 'bp_core_template_plugin', '/classifieds/single/plugins' ) );
				}
			}
		}

		/* When we are viewing a single classified, add the classified extension nav item */
		if ( $this->visbility == 'public' || ( $this->visbility != 'public' && $bp->classifieds->current_classified->user_has_access ) ) {
			if ( $this->enable_nav_item ) {
				if ( $bp->current_component == $bp->classifieds->slug && $bp->is_single_item )
					bp_core_new_subnav_item( array( 'name' => ( !$this->nav_item_name ) ? $this->name : $this->nav_item_name, 'slug' => $this->slug, 'parent_slug' => BP_CLASSIFIEDS_SLUG, 'parent_url' => bp_get_classified_permalink( $bp->classifieds->current_classified ) . '/', 'position' => $this->nav_item_position, 'item_css_id' => 'nav-' . $this->slug, 'screen_function' => array( &$this, '_display_hook' ), 'user_has_access' => $this->enable_nav_item ) );

				/* When we are viewing the extension display page, set the title and options title */
				if ( $bp->current_component == $bp->classifieds->slug && $bp->is_single_item && $bp->current_action == $this->slug ) {
					add_action( 'bp_template_content_header', create_function( '', 'echo "' . attribute_escape( $this->name ) . '";' ) );
			 		add_action( 'bp_template_title', create_function( '', 'echo "' . attribute_escape( $this->name ) . '";' ) );
				}
			}

			/* Hook the classified home widget */
			if ( $bp->current_component == $bp->classifieds->slug && $bp->is_single_item && ( !$bp->current_action || 'home' == $bp->current_action ) )
				add_action( $this->display_hook, array( &$this, 'widget_display' ) );
		}
	}

	function _display_hook() {
		add_action( 'bp_template_content', array( &$this, 'display' ) );
		bp_core_load_template( apply_filters( 'bp_core_template_plugin', $this->template_file ) );
	}
}

function bp_register_classified_extension( $classified_extension_class ) {
	global $bp;

	if ( !class_exists( $classified_extension_class ) )
		return false;

	/* Register the classified extension on the plugins_loaded action so we have access to all plugins */
	add_action( 'plugins_loaded', create_function( '', '$extension = new ' . $classified_extension_class . '; add_action( "wp", array( &$extension, "_register" ), 2 );' ) );
}

?>
