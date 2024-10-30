<?php

/* Register widgets for classifieds component */
function classifieds_register_widgets() {
	add_action('widgets_init', create_function('', 'return register_widget("BP_Classifieds_Widget");') );
	add_action('widgets_init', create_function('', 'return register_widget("BP_Classifieds_Widget_Tag_Cloud");') );
}
add_action( 'plugins_loaded', 'classifieds_register_widgets' );


/*** CLASSIFIEDS WIDGET *****************/

class BP_Classifieds_Widget extends WP_Widget {
	function bp_classifieds_widget() {
		parent::WP_Widget( false, $name = __( 'Classifieds', 'classifieds' ) );

		//if ( is_active_widget( false, false, $this->id_base ) )
			//wp_enqueue_script( 'classifieds_widget_classifieds_list-js', BP_CLASSIFIEDS_PLUGIN_URL . '/js/widget-classifieds.js', array('jquery') );
	}

	function widget($args, $instance) {
		global $bp;

	    extract( $args );
		
		if ($instance['title']) {
			$title = $instance['title'];
			unset($instance['title']);
		}else {
			$title =$widget_name;
		}

		echo $before_widget;
		if ( $title )
			echo $before_title . $title . $after_title;

		?>

		<?php if ( bp_has_classifieds($instance) ) : ?>
			<div class="item-options" id="classifieds-list-options">
				<span class="ajax-loader" id="ajax-loader-classifieds"></span>
				

					<a id="classifieds-all" <?php if (!$instance['action_tag'])echo 'class="selected"';?> href="<?php echo bp_root_domain().'/'. BP_CLASSIFIEDS_SLUG ?>"><?php printf( __( 'All Classifieds (%s)', 'buddypress' ), bp_get_total_classified_count() ) ?></a>
					<?php
					//actions tabs
					if (bp_classifieds_is_actions_enabled()) {
						echo" |";
						$actions = BP_Classifieds_Actions::get_all();

						foreach ($actions as $key=>$action) {
							
							unset($class);
							if ($instance['action_tag']==$action->term_id) $class=' class="selected"';
							?>
							<a <?php echo $class;?>id="classifieds-action<?php echo $action->term_id;?>" href="<?php bp_classified_action_permalink($action,true,true); ?>"><?php printf( ucfirst($action->name).' (%s)', bp_get_total_action_classified_count( $action->term_id ) ) ?></a>
							<?php
							if ($key<count($actions)-1)
								echo" |";
						}
					}
					?>
				<!--
				<a href="<?php echo site_url() . '/' . $bp->classifieds->slug ?>" id="newest-classifieds"><?php _e("Newest", 'buddypress') ?></a> |
				<a href="<?php echo site_url() . '/' . $bp->classifieds->slug ?>" id="recently-active-classifieds"><?php _e("Active", 'buddypress') ?></a> |
				<a href="<?php echo site_url() . '/' . $bp->classifieds->slug ?>" id="popular-classifieds" class="selected"><?php _e("Popular", 'buddypress') ?></a>
				-->
			</div>

			<ul id="classifieds-list" class="item-list">
				<?php while ( bp_classifieds() ) : bp_the_classified(); ?>
					<li>


						<div class="item">
							<div class="item-title">
								<a href="<?php bp_classified_permalink() ?>" title="<?php bp_classified_name() ?>"><?php bp_classified_name() ?></a>
								<span class="activity date-created"><?php bp_classified_date_created() ?></span>
							</div>
							<div class="item-excerpt"><?php bp_classified_description_excerpt();?></div>
							<div class="item-meta">
								<?php bp_classified_breadcrumb_author(false,false) ?>
								<?php if ((bp_classifieds_is_actions_enabled()) || (bp_classifieds_is_categories_enabled())){?>
								<?php _e('in','classifieds');?>
									<span class="breadcrumb">
										<?php if (bp_classifieds_is_actions_enabled()){?>
											<?php bp_classified_breadcrumb_action(); ?>
										<?php };?>
										
										<?php if (bp_classifieds_is_categories_enabled()){?>
										
											<?php bp_classified_breadcrumb_categories(); ?>
										<?php };?>

									</span>
								<?php };?>
								<?php if (bp_classified_has_tags()) {?>
								
									<span class="tags"><?php bp_classified_tags()?></span>
								<?php };?>
							</div>
						</div>
					</li>

				<?php endwhile; ?>
			</ul>
			<?php wp_nonce_field( 'classifieds_widget_classifieds_list', '_wpnonce-classifieds' ); ?>
			<input type="hidden" name="classifieds_widget_max" id="classifieds_widget_max" value="<?php echo attribute_escape( $instance['max'] ); ?>" />

		<?php else: ?>

			<div class="widget-error">
				<?php _e('There are no classifieds to display.', 'buddypress') ?>
			</div>

		<?php endif; ?>

		<?php echo $after_widget; ?>
	<?php
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		
		$instance['title'] = strip_tags(stripslashes($new_instance['title']));
		$max = strip_tags( $new_instance['max'] );
		if ($max)
			$instance['max'] = $max;
		$instance['action_tag'] = $new_instance['action_tag'];
		$instance['cats'] = $new_instance['cats'];

		return $instance;
	}

	function form( $instance ) {

		$instance = wp_parse_args( (array) $instance, array( 'max' => 5 ) );
		$max = strip_tags( $instance['max'] );

		$w_action = $instance['action_tag'];
		$cats = $instance['cats'];
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:') ?></label>
		<input type="text" class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php if (isset ( $instance['title'])) {echo esc_attr( $instance['title'] );} ?>" /></p>
		<p><label for="bp-classifieds-widget-classifieds-max"><?php _e('Max classifieds to show:', 'buddypress'); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'max' ); ?>" name="<?php echo $this->get_field_name( 'max' ); ?>" type="text" value="<?php echo attribute_escape( $max ); ?>" style="width: 30%" /></label></p>
		
		<?php

		//ACTIONS
		if (bp_classifieds_is_actions_enabled()) {
			$output.='<p><label for="'.$this->get_field_id('action_tag').'">'._e('Actions:').'</label>';
			$actions = BP_Classifieds_Actions::get_all();

			if (!$w_action)
				$checked_all=' CHECKED';

			?>
			<input type="radio" name="<?php echo $this->get_field_name( 'action_tag' );?>" value=""<?php echo $checked_all;?>><?php _e('All','classifieds');?>
			<?php
			
			foreach ($actions as $key=>$action) {

				unset($checked);
				
				if ($w_action==$action->term_id) $checked=' CHECKED';

				

					?>
					<input type="radio" name="<?php echo $this->get_field_name( 'action_tag' );?>" value="<?php echo $action->term_id;?>"<?php echo $checked;?>><?php echo $action->name;?>
					<?php
					
			}
			echo"</p>";
		}
		
		//CATEGORIES
	
		if (bp_classifieds_is_categories_enabled()) {
		
			$array_categories = BP_Classifieds_Categories::get_children();
			
			$treeset = new TreeSet();
			
			$current_categories  = $cats;
			
			
			
			
			$categories_tree = $treeset -> drawTree($treeset -> buildTree($array_categories),$current_categories,array(&$this,'format_cat_for_widget'));
			
			

			$output.='<p><label for="'.$this->get_field_id('title').'">'._e('Categories:').'</label><div id="browse-categories">';
			$output.=$categories_tree;
			$output.='</div></p>';
			
			echo $output;
			
		}
		?>
		
		
	<?php
	}
	
	function format_cat_for_widget($cat) {
		global $checked_branches;


		if (
			(!$checked_branches) || 
			( (is_array($checked_branches)) &&in_array($cat->ID,$checked_branches) )
			
			) $checked=' CHECKED';
			

		$html='<li class="category" id="cat-'.$cat->ID.'"><div class="folder icon"></div><input type="checkbox" name="'.$this->get_field_name( 'cats' ).'[]" value="'.$cat->ID.'"'.$checked.'>'.$cat->name.'</span>';
		
		return $html;
	}
	
}



function classifieds_ajax_widget_classifieds_list() {
	global $bp;

	check_ajax_referer('classifieds_widget_classifieds_list');

	switch ( $_POST['filter'] ) {
		case 'newest-classifieds':
			$type = 'newest';
		break;
		case 'recently-active-classifieds':
			$type = 'active';
		break;
		case 'popular-classifieds':
			$type = 'popular';
		break;
	}
	
	
	

	if ( bp_has_classifieds( 'type=' . $type . '&per_page=' . $_POST['max'] . '&max=' . $_POST['max'] ) ) : ?>
		<?php echo "0[[SPLIT]]"; ?>

		<ul id="classifieds-list" class="item-list">
			<?php while ( bp_classifieds() ) : bp_the_classified(); ?>
				<li>
					<div class="item-avatar">
						<a href="<?php bp_classified_permalink() ?>"><?php bp_classified_avatar_thumb() ?></a>
					</div>

					<div class="item">
						<div class="item-title"><a href="<?php bp_classified_permalink() ?>" title="<?php bp_classified_name() ?>"><?php bp_classified_name() ?></a></div>
						<div class="item-meta">
							<span class="activity">
								<?php
								if ( 'newest-classifieds' == $_POST['filter'] ) {
									printf( __( 'created %s ago', 'buddypress' ), bp_get_classified_date_created() );
								} else if ( 'recently-active-classifieds' == $_POST['filter'] ) {
									printf( __( 'active %s ago', 'buddypress' ), bp_get_classified_last_active() );
								} else if ( 'popular-classifieds' == $_POST['filter'] ) {
									bp_classified_member_count();
								}
								?>
							</span>
						</div>
					</div>
				</li>

			<?php endwhile; ?>
		</ul>
		<?php wp_nonce_field( 'classifieds_widget_classifieds_list', '_wpnonce-classifieds' ); ?>
		<input type="hidden" name="classifieds_widget_max" id="classifieds_widget_max" value="<?php echo attribute_escape( $_POST['max'] ); ?>" />

	<?php else: ?>

		<?php echo "-1[[SPLIT]]<li>" . __("No classifieds matched the current filter.", 'buddypress'); ?>

	<?php endif;

}
add_action( 'wp_ajax_widget_classifieds_list', 'classifieds_ajax_widget_classifieds_list' );

/*** CLASSIFIEDS TAGS CLOUD WIDGET *****************/

function bp_classifieds_tag_cloud( $args = '' ) {
	global $bp;
	
	$defaults = array(
		'smallest' => 8, 'largest' => 22, 'unit' => 'pt', 'number' => 45,
		'format' => 'flat', 'separator' => "\n", 'orderby' => 'name', 'order' => 'ASC',
		'exclude' => '', 'include' => '', 'link' => 'view', 'taxonomy' => 'post_tag', 'echo' => true
	);
	$args = wp_parse_args( $args, $defaults );

	$tags = BP_Classifieds_Tags::get_all($args);

	if ( empty( $tags ) )
		return;

	switch_to_blog($bp->classifieds->options['blog_id']);

	foreach ( $tags as $key => $tag ) {
		$link = bp_get_classified_tag_permalink($tag);
		if ( is_wp_error( $link ) )
			return false;

		$tags[ $key ]->link = $link;
		$tags[ $key ]->id = $tag->term_id;
	}
	restore_current_blog();


	$return = wp_generate_tag_cloud( $tags, $args ); // Here's where those top tags get sorted according to $args
	


	$return = apply_filters( 'bp_classifieds_tag_cloud', $return, $args );

	if ( 'array' == $args['format'] || empty($args['echo']) )
		return $return;

	echo $return;
}

class BP_Classifieds_Widget_Tag_Cloud extends WP_Widget {
	
	function BP_Classifieds_Widget_Tag_Cloud() {
		parent::WP_Widget( false, $name = __( 'Classifieds Tag Cloud', 'classifieds' ) );
	}
	

	function widget( $args, $instance ) {
		extract($args);
		$title = apply_filters('widget_title', empty($instance['title']) ? __( 'Classifieds Tag Cloud', 'classifieds' ) : $instance['title']);

		echo $before_widget;
		if ( $title )
			echo $before_title . $title . $after_title;
		echo '<div>';
		bp_classifieds_tag_cloud(apply_filters('bp_classifieds_widget_tag_cloud_args', array()));
		echo "</div>\n";
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance['title'] = strip_tags(stripslashes($new_instance['title']));
		return $instance;
	}

	function form( $instance ) {
?>
	<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:') ?></label>
	<input type="text" class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php if (isset ( $instance['title'])) {echo esc_attr( $instance['title'] );} ?>" /></p>
<?php
	}
}



?>
