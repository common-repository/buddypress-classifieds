<?php

function classifieds_admin_js($hook_suffix) {

	if ($hook_suffix!='buddypress_page_bp-classifieds-setup') return false;
	wp_enqueue_script('jquery-ui-tabs');
	wp_enqueue_script( 'bp-classifieds-admin', BP_CLASSIFIEDS_PLUGIN_URL . '/js/admin-classifieds.js' );	
		wp_enqueue_style( 'bp-classifieds-admin-tabs', BP_CLASSIFIEDS_PLUGIN_URL . '/css/jquery.ui.tabs.css' );
		wp_enqueue_style( 'bp-classifieds-admin', BP_CLASSIFIEDS_PLUGIN_URL . '/css/admin.css' );

}
add_action('admin_enqueue_scripts', 'classifieds_admin_js',1);

function classifieds_admin() {
	global $bp;
	//global $_options;
	global $current_site;
	global $errors;

	$options=$bp->classifieds->options;
	
	if ( isset( $_POST['submit'] ) ) {
		$_options=array();
		$errors = new WP_Error();

		switch ( $_POST['action'] ) {
			
			/*
			case 'system-install':
			break;
			*/
		
			case 'options':
			
				check_admin_referer('classifieds-options');
				
				//GENERAL
				
				//blog id
				$blog_id=(int)$_POST['blog_id'];
				if ($blog_id==0){
					$errors->add('blog_id',__( 'Please fill in all of the required fields', 'buddypress' ));
				}else {
					$_options['blog_id'] = $blog_id;
					
					//remove "hello world" post
					$default_post = new BP_Classifieds_Classified(1);
					if ($default_post) 
						$default_post->delete();
				}
				//days active
				if (($_POST['days_active']) && (is_numeric($_POST['days_active']))) {
					$_options['days_active'] = $_POST['days_active'];
				}else {
					$errors->add('days_active',__( 'Days active must be a number', 'classifieds' ));
				}
				//action tags
				if ($_POST['actions_tags']) {
					$actions = explode (',',$_POST['actions_tags']);
					foreach ($actions as $action) {
						$tag = new BP_Classifieds_Tags($action);
						if (!$tag->term_id) {
							$tag_error = true;
							$errors->add('invalid_tag',sprintf(__( 'The tag id#%d do not exists.  Be sure you have created it !', 'classifieds' ),$action));
						}
					}
					
					if (!$tag_error) {
						$_options['actions_tags'] = $actions;
					}
				}else {
					$_options['actions_tags'] = false;
				}
				//capabilities
				$_options['capabilities']['visitors'] = $_POST['capability_visitors'];
				
				$moderation_needed = (bool)$_POST['moderation_needed'];
				bp_classifieds_set_moderation($moderation_needed);

				
				
				//POSTING
				
				$_options['tags_suggestion'] = (bool) $_POST['tags_suggestion'];
				$_options['classifieds_groups'] = (bool) $_POST['classifieds_groups'];
				$_options['tinymce'] = (bool) $_POST['tinymce'];
				$_options['pics_max'] = (int) $_POST['pics_max'];
				$_options['classifieds_maps'] = (bool) $_POST['classifieds_maps'];

				
			break;

			case 'system-options':

				check_admin_referer('bp-classifieds-system-options');

				//TO FIX : do not save options
				
				//DEBUG
				$_options['enable_debug'] = (bool) $_POST['enable_debug'];
				
				$firephp_path = trim(stripslashes($_POST['firephp_path']));
				if ($firephp_path!=ABSPATH) {
					if ( file_exists($firephp_path))
						$_options['firephp_path']=$firephp_path;
				}
				
				//RESET OPTIONS
				if ($_POST['reset-options']) {
					if (!delete_site_option( 'classifieds_options')) {
						$errors->add('reset_options',__( 'Error while trying to reset options', 'classifieds' ));
					}else {
						unset($_options);
						unset($options);
					}
				}

				//CLEAR DATA
				if ($_POST['clear-data']) {
					//TO FIX
				}
				//UNINSTALL PLUGIN
				if ($_POST['uninstall-plugin']) {
					classifieds_uninstall();
				}
			break;
		}
		
		//PLUGINS
		do_action( 'bp_classifieds_admin_options_plugins_save');
		

		$options = wp_parse_args( $_options, $options ); //changes full array options with updated values
		if (update_site_option( 'classifieds_options', $options ))
			$message = __('Settings saved','classifieds');
		
 		do_action( 'bp_classifieds_admin_screen',$options );
		
		//TO FIX : CHECK showing
		if ($errors->get_error_message($code)) {
		?>
			<div id="message" class="error">
				<p><?php echo $errors->get_error_message($code);?></p>
			</div>
		<?php }elseif ($message) {
		?>
			<div id="message" class="info">
				<p><?php echo $message;?></p>
			</div>
		<?php
		}

		
	
	}
	?>


	<div id="slider" class="wrap">
		<ul id="tabs">
			<?php
			
			$donations_tab='<li><a href="#donations">'.__("Support & Donations", "classifieds").'</a></li>';
			
			if (!bp_classifieds_is_setup()) {
					echo $donations_tab;
			} ?>
			
			<li><a href="#options"><?php _e( 'Options', 'classifieds' ) ?></a></li>
			<?php do_action( 'classifieds_settings_tabs' );?>
			<li><a href="#system"><?php _e('System', 'support') ;?></a></li>
			<?php
			if (bp_classifieds_is_setup()) {
					echo $donations_tab;
			} ?>
			
			
		</ul>

		<br />
		<div id="system">
			<h2><?php _e('System', 'classifieds') ;?></h2>
			<form action="#system" name="bp-classifieds-options" id="bp-classifieds-options" method="post">	
				<h3><?php _e('Reset Options', 'classifieds') ;?></h3>
				<p>
					<input name="reset-options" type="checkbox" id="reset-options" value="1"/>
					<?php _e( 'Reset plugin options', 'classifieds' ); ?>
					 - <small><?php _e( 'This will not affect the content (classifieds and categories)', 'classifieds' ); ?></small>
					</p>
				</p>
				<p>
					<input name="clear-data" type="checkbox" id="clear-data" value="1"/>
					<?php _e( 'Clear all classifieds datas', 'classifieds' ); ?>
					</p>
				</p>
				<p>
					<input name="uninstall-plugin" type="checkbox" id="uninstall-plugin" value="1"/>
					<?php _e( 'Totally uninstall the plugin, classifieds included !', 'classifieds' ); ?>
				</p>

				<h3><?php _e('Maintenance', 'classifieds') ;?></h3>
				<table class="form-table">
				<?php
				$firephp_path=$options['firephp_path'];
				$enable_debug=$options['enable_debug'];

				if (!$firephp_path) {
					$debug_disabled=" DISABLED";
					$firephp_path=ABSPATH;
					unset($enable_debug);
				}

				?>
						<tr valign="top">
							<th scope="row"><label for="enable_debug"><?php _e( 'Enable debugging', 'classifieds' ) ?></label></th>
							<td>
								<input name="enable_debug" type="checkbox" id="enable_debug" value="1"<?php echo( $enable_debug ? ' checked="checked"' : '' );echo $debug_disabled;?>/>
								<small><?php printf(__('To make this work, you have to upload the FirePHPCore directory from %s, then set its absolute path here', 'classifieds'),'<a target="_blank" href="http://www.firephp.org">FirePHP</a>') ?></small><em> ~ <?php _e('without trailing slash','classifieds');?></em>
								<input class="required" name="firephp_path" type="text" id="firephp_path" size="100" value="<?php echo $firephp_path;?>"/>
							</td>
						</tr>
				<!--
					<tr valign="top">
						<th scope="row"><label for="autoprune_drafts"><?php _e( 'Auto prune classifieds drafts', 'classifieds' ); ?></label></th>
						<td>
							<input name="autoprune_drafts" type="text" id="autoprune_drafts" size="3" value="<?php echo $options['autoprune_drafts'];?>"/> <?php _e('days','classifieds');?>
							 - <small><?php _e( 'Leave empty if you want to disable this', 'classifieds' ); ?></small>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="autoprune_drafts"><?php _e( 'Auto prune unactive classifieds', 'classifieds' ); ?></label></th>
						<td>
							<input name="autoprune_unactive" type="text" id="autoprune_unactive" size="3" value="<?php echo $options['autoprune_unactive'];?>"/> <?php _e('days','classifieds');?>
							 - <small><?php _e( 'Leave empty if you want to disable this', 'classifieds' ); ?></small>
						</td>
					</tr>
				-->
				</table>
				
				<p id="submit-types" class="submit">
					<input type="hidden" name="action" value="system-options" />
					<input class="button-primary" type="submit" name="submit" value="<?php _e('Save system Options', 'classifieds') ?>"/>
				</p>
				<?php wp_nonce_field('bp-classifieds-system-options') ?>
			</form>
		</div>
		<div id="options">
			<h2><?php _e( 'Options', 'classifieds' ) ?></h2>
			
			<form action="#options" name="bp-classifieds-options" id="bp-classifieds-options" method="post">				
			<h3><?php _e( 'General', 'classifieds' ) ?></h3>
			
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><label for="blog_id"><?php _e('Blog ID', 'buddypress') ?></label></th>
						<td>
							<input class="required" name="blog_id" type="text" id="blog_id" size="3" value="<?php echo $options['blog_id'];?>"/>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="days_active"><?php _e( 'Days active', 'classifieds' ) ?></label></th>
						<td>
							<input name="days_active" type="text" size="2" id="days_active" value="<?php echo $options['days_active'];?>"/>
							<?php _e( 'How many days before a classified becomes inactive ?', 'classifieds' ); ?>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="actions_tags"><?php _e( 'Actions tags', 'classifieds' ) ?></label></th>
						<td>
						<?php
						if ($options['actions_tags'])
							$action_tags = implode(',',$options['actions_tags']);
						?>
							<input name="actions_tags" type="text" size="2" id="actions_tags" value="<?php echo $action_tags;?>"/>
							<?php printf(__( 'If you want special tags %s to sort your classifieds, put their ids here - comma-separated', 'classifieds' ),'(<em>'.__( 'e.g. : propositions, offers', 'classifieds' ).'</em>)'); ?><br>

						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="actions_tags"><?php _e( 'Capabilities', 'classifieds' ) ?></label></th>
						<td>
							<p>
								<input name="capability_visitors" type="radio" id="capability_visitors-0" value="0"<?php if ( $options['capabilities']['visitors']==0) echo' checked="checked"';?>/>
								<?php _e( 'Visitors can\'t see the classifieds' ) ?><br>
								<input name="capability_visitors" type="radio" id="capability_visitors-1" value="1"<?php if ( $options['capabilities']['visitors']==1) echo' checked="checked"';?>/>
								<?php _e( 'Visitors can only see the classifieds listings' ) ?><br>
								<input name="capability_visitors" type="radio" id="capability_visitors-2" value="2"<?php if ( $options['capabilities']['visitors']==2) echo' checked="checked"';?>/>
								<?php _e( 'Visitors can see the classifieds listings and the classifieds details' ) ?>
							</p>
							<p>
							<?php
								$mod_exists = bp_classifieds_moderation_exists();

							?>
								<input name="moderation_needed" type="checkbox" id="moderation_needed" value="1"<?php echo( $mod_exists ? ' checked="checked"' : '' ); ?>/>
								<?php _e( 'Enable moderation for authors', 'classifieds' ) ?><br>
							</p>
							<p>
								<small><?php printf(__('If you want precise control on who can do what with the classifieds, install a plugin that can manage capabilities, like eg. %s, then edit the capabilities for the author role in your classifieds-data blog.'),'<a href="http://wordpress.org/extend/plugins/capsman/" target="_blank">Capability Manager</a>');?>
							</p>
						</td>
					</tr>

					<?php do_action( 'bp_classifieds_admin_options_general' );?>
				</table>
				
				<h3><?php _e( 'Posting', 'classifieds' ) ?></h3>

					
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><label for="classifieds_groups">TinyMCE</label></th>
							<td>
								<input name="tinymce" type="checkbox" id="tinymce" value="1"<?php echo( $options['tinymce'] ? ' checked="checked"' : '' ); ?>/>
								<?php _e( 'Enable WYSIWYG editor', 'classifieds' ) ?>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="pics_max"><?php _e( 'Max. Pictures', 'classifieds' ) ?></label></th>
							<td>
								<input name="pics_max" type="text" size="2" id="pics_max" value="<?php echo $options['pics_max'];?>"/>
								<?php _e( 'Maximum number of pictures a user can upload when posting a classified', 'classifieds' );?>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="classifieds_groups"><?php _e( 'Group Classifieds', 'classifieds' ) ?></label></th>
							<td>
								<input name="classifieds_groups" type="checkbox" id="classifieds_groups" value="1"<?php echo( $options['classifieds_groups'] ? ' checked="checked"' : '' ); ?>/>
								<?php _e( 'Enable group classifieds', 'classifieds' );?>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="classifieds_maps"><?php _e( 'Classifieds Maps', 'classifieds' ) ?></label></th>
							<td>
								<?php 
								if ($options['classifieds_maps']) $maps_checked=" CHECKED";
								if (!classifieds_plugin_is_active('buddypress-maps/buddypress-maps.php')) {
									$maps_disabled=" DISABLED";
									unset($maps_checked);
								}
								?>
								<input name="classifieds_maps" type="checkbox" id="classifieds_maps" value="1"<?php echo $maps_checked.$maps_disabled; ?>/>
								<?php _e( 'Enable classifieds maps', 'classifieds' );?><small> - <?php printf(__( 'You\'ll need the plugin %s to enable maps for classifieds', 'classifieds' ),'<a target="_blank" href="http://wordpress.org/extend/plugins/buddypress-maps">BuddPress Maps</a>') ?></small>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="tags_suggestion"><?php _e( 'Auto-suggest tags', 'classifieds' ) ?></label></th>
							<td>
								<input name="tags_suggestion" type="checkbox" id="tags_suggestion" value="1"<?php echo( $options['tags_suggestion'] ? 'checked="checked"' : '' ); ?> />
								<?php _e( 'Use auto-suggestion when creating a classifieds', 'classifieds' );?> (javascript)
							</td>
						</tr>
					</table>
					
				<?php do_action( 'bp_classifieds_admin_options' );?>
				
				<br />
				<p class="submit">
					<input type="hidden" name="action" value="options" />
					<input class="button-primary" type="submit" name="submit" value="<?php _e('Save Settings', 'buddypress') ?>"/>
				</p>
				<?php wp_nonce_field('classifieds-options') ?>
			</form>
		</div>
		<?php do_action( 'bp_classifieds_settings_div' );?>
		<div id="donations">
			<h2><?php _e('Support & Donations', 'classifieds') ;?></h2>
			<h3><?php _e('Donations', 'classifieds') ;?></h3>
			<p><?php printf(__('Coding this plugin was an long, long way.  If you like it, if you use it, please %s !','classifieds'),'<a href="http://dev.benoitgreant.be/2010/02/01/buddypress-classifieds#donate">'.__('Make a donation','classifieds').'</a>');?>
			<h3><?php _e('Support', 'classifieds') ;?></h3>
			<p><?php printf(__('You can find installation instructions %s','classifieds'),'<a href="http://dev.benoitgreant.be/2010/02/01/buddypress-classifieds">'.__('here','classifieds').'</a>');?>.</p>
			<p><?php printf(__('Bugs, ideas, ... can be reported into the %s','classifieds'),'<a href="http://dev.benoitgreant.be/bbpress/forum/buddypress-classifieds">'.__('support forum','classifieds').'</a>');?>.</p>
		</div>
	</div>
<?php 
}


?>