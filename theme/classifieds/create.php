<?php get_header() ?>

	<div id="content">
		<div class="padder">

		<form action="<?php bp_classified_creation_form_action() ?>" method="post" id="create-classified-form" class="standard-form" enctype="multipart/form-data">
			<h2><?php _e( 'Create a Classified', 'buddypress' ) ?> &nbsp;<a class="button" href="<?php echo bp_get_root_domain() . '/' . BP_CLASSIFIEDS_SLUG . '/' ?>"><?php _e( 'Classifieds Directory', 'buddypress' ) ?></a></h2>

			<?php do_action( 'bp_before_create_classified' ) ?>

			<div class="item-list-tabs no-ajax" id="classified-create-tabs">
				<ul>
					<?php bp_classified_creation_tabs(); ?>
				</ul>
			</div>

			<?php do_action( 'template_notices' ) ?>
			
			<?php if (classifieds_can_user_create()) :?>

				<div class="item-body" id="classified-create-body">

					<?php /* Classified creation step 1: Basic classified details */ ?>
					<?php if ( bp_is_classified_creation_step( __('classified-details','classifieds-slugs' ) )) : ?>

						<?php do_action( 'bp_before_classified_details_creation_step' ); ?>

						<label for="classified-name"><?php _e('Classified Name', 'classifieds') ?> <? _e( '(required)', 'classifieds' )?></label>
						<input type="text" name="classified-name" id="classified-name" value="<?php bp_new_classified_name() ?>" />
			
						<label for="classified-desc"><?php _e('Classified Description', 'classifieds') ?> <? _e( '(required)', 'classifieds' )?></label>

						<textarea name="classified-desc" id="classified-desc"><?php bp_new_classified_description() ?></textarea>

						<?php do_action( 'bp_after_classified_details_creation_step' ); /* Deprecated -> */ do_action( 'classifieds_custom_classified_fields_editable' ); ?>

						<?php wp_nonce_field( 'classifieds_create_save_classified-details' ) ?>

						

					<?php endif; ?>

				<!-- Classified creation step 2: Classified settings -->		
				<?php if ( bp_is_classified_creation_step( __('classified-settings','classifieds-slugs' ) ) ) : ?>

					<?php do_action( 'bp_before_classified_settings_creation_step' ); ?>

					<?php if ( function_exists('bp_wire_install') ) : ?>
					<div class="checkbox">
						<label><input type="checkbox" name="classified-show-wire" id="classified-show-wire" value="1"<?php if ( bp_get_new_classified_enable_wire() ) { ?> checked="checked"<?php } ?> /> <?php _e('Enable comment wire', 'buddypress') ?></label>
					</div>
					<?php endif; ?>
		
					<?php if (bp_classifieds_is_actions_enabled()) :?>
						<h3><?php _e( 'Choose Action', 'classifieds' ); ?></h3>
						<?php bp_new_classified_action();?>
					<?php endif; ?>
					
					<?php if (bp_classifieds_is_categories_enabled()) :?>
						<h3><?php _e( 'Choose Category', 'classifieds' ); ?></h3>
						<?php bp_new_classified_categories();?>
					<?php endif; ?>
					
					<h3><?php _e( 'Choose Tags', 'classifieds' ); ?></h3>
					<input name="classified-tags" id="classified-tags" value="<?php bp_new_classified_tags() ?>"/>

					<?php do_action( 'bp_after_classified_settings_creation_step' ); ?>

					<?php wp_nonce_field( 'classifieds_create_save_classified-settings' ) ?>
					



		
				<?php endif; ?>

					<?php /* Classified creation step 3: Avatar Uploads ?>
					<?php if ( bp_is_classified_creation_step( __('classified-avatar','classifieds-slugs' ) ) ) : ?>

						<?php do_action( 'bp_before_classified_avatar_creation_step' ); ?>

						<?php if ( !bp_get_avatar_admin_step() ) : ?>

							<div class="left-menu">
								<?php bp_new_classified_avatar() ?>
							</div><!-- .left-menu -->

							<div class="main-column">
								<p><?php _e("Upload an image to use as an avatar for this classified. The image will be shown on the main classified page, and in search results.", 'buddypress') ?></p>

								<p>
									<input type="file" name="file" id="file" />
									<input type="submit" name="upload" id="upload" value="<?php _e( 'Upload Image', 'buddypress' ) ?>" />
									<input type="hidden" name="action" id="action" value="bp_avatar_upload" />
								</p>

								<p><?php _e( 'To skip the avatar upload process, hit the "Next Step" button.', 'buddypress' ) ?></p>
							</div><!-- .main-column -->

						<?php endif; ?>

						<?php 
						
						if ( 'crop-image' == bp_get_avatar_admin_step() ) : ?>

							<h3><?php _e( 'Crop Classified Avatar', 'buddypress' ) ?></h3>

							<img src="<?php bp_avatar_to_crop() ?>" id="avatar-to-crop" class="avatar" alt="<?php _e( 'Avatar to crop', 'buddypress' ) ?>" />

							<div id="avatar-crop-pane">
								<img src="<?php bp_avatar_to_crop() ?>" id="avatar-crop-preview" class="avatar" alt="<?php _e( 'Avatar preview', 'buddypress' ) ?>" />
							</div>

							<input type="submit" name="avatar-crop-submit" id="avatar-crop-submit" value="<?php _e( 'Crop Image', 'buddypress' ) ?>" />

							<input type="hidden" name="image_src" id="image_src" value="<?php bp_avatar_to_crop_src() ?>" />
							<input type="hidden" name="upload" id="upload" />
							<input type="hidden" id="x" name="x" />
							<input type="hidden" id="y" name="y" />
							<input type="hidden" id="w" name="w" />
							<input type="hidden" id="h" name="h" />

						<?php endif; ?>

						<?php do_action( 'bp_after_classified_avatar_creation_step' ); ?>

						<?php wp_nonce_field( 'classifieds_create_save_classified-avatar' ) ?>

					<?php endif; */?>
					
					

				<!-- Classified creation step 4: Invite friends to classified -->	
				<?php if ( bp_is_classified_creation_step( __('classified-invites','classifieds-slugs' ) ) ) : ?>

					<?php do_action( 'bp_before_classified_invites_creation_step' ); ?>
					
					<?php if (classifieds_has_friends_to_invite()) {?>
						<div class="left-menu">
						
							<div id="classifieds-invite-list">
								<ul>
									<?php bp_new_classified_invite_friend_list();?>
								</ul>
							
								<?php wp_nonce_field( 'classifieds_send_invites', '_wpnonce_send_invites' ) ?>
							</div>
						
						</div>

						<div class="main-column">
							
							<div id="message" class="info">
								<p><?php _e('Select people to warn from your friends list.', 'classifieds'); ?></p>
							</div>


						</div><!-- .main-column -->
						
						<?php }else {?>
							<div id="message" class="info">
								<p><?php _e( 'You either need to build up your friends list, or your friends have already been invited to check this classified.', 'classifieds' );?></p>
							</div>
						<?php
						}
						?>
		
						<?php wp_nonce_field( 'classifieds_create_save_classified-invites' ) ?>
	
					<?php do_action( 'bp_after_classified_invites_creation_step' ); ?>
				
				<?php endif; ?>

					<?php do_action( 'classifieds_custom_create_steps' ) // Allow plugins to add custom classified creation steps ?>

					<?php do_action( 'bp_before_classified_creation_step_buttons' ); ?>

					<?php

					if ( 'crop-image' != bp_get_avatar_admin_step() ) : ?>
						<div class="submit" id="previous-next">
							<?php // Previous Button ?>
							<?php if ( !bp_is_first_classified_creation_step() ) : ?>
								<input type="button" value="&larr; <?php _e('Previous Step', 'buddypress') ?>" id="classified-creation-previous" name="previous" onclick="location.href='<?php bp_classified_creation_previous_link() ?>'" />
							<?php endif; ?>

							<?php // Next Button ?>
							<?php if ( !bp_is_last_classified_creation_step() && !bp_is_first_classified_creation_step() ) : ?>
								<input type="submit" value="<?php _e('Next Step', 'buddypress') ?> &rarr;" id="classified-creation-next" name="save" />
							<?php endif;?>

							<?php // Create Button ?>
							<?php if ( bp_is_first_classified_creation_step() ) : ?>
								<input type="submit" value="<?php _e('Create Classified and Continue', 'buddypress') ?> &rarr;" id="classified-creation-create" name="save" />
							<?php endif; ?>

							<?php //Finish Button ?>
							<?php if ( bp_is_last_classified_creation_step() ) : ?>
								<input type="submit" value="<?php _e('Finish', 'buddypress') ?> &rarr;" id="classified-creation-finish" name="save" />
							<?php endif; ?>
						</div>
					<?php endif;

					?>

					<?php do_action( 'bp_after_classified_creation_step_buttons' ); ?>

					<?php /* Don't leave out this hidden field */ ?>
					<input type="hidden" name="classified_id" id="classified_id" value="<?php bp_new_classified_id() ?>" />

					<?php do_action( 'bp_directory_classifieds_content' ) ?>

				</div><!-- .item-body -->

				<?php do_action( 'bp_after_create_classified' ) ?>
			
			<?php else:?>

			<div id="message" class="error">
				<p><?php _e( "Sorry, you are not allowed to create classifieds.", 'buddypress' ) ?></p>
			</div>

			<?php endif;?>

		</form>

		</div><!-- .padder -->
	</div><!-- #content -->

	<?php locate_template( array( 'sidebar.php' ), true ) ?>

<?php get_footer() ?>







<?php 
/*
get_header() ?>

	<div class="content-header">
		<ul class="content-header-nav">
			<?php bp_classified_creation_tabs(); ?>
		</ul>
	</div>

	<div id="content">	
		<h2><?php _e( 'Create a Classified', 'classifieds' ) ?> <?php bp_classified_creation_stage_title() ?></h2>
		<?php do_action( 'template_notices' ) // (error/success feedback) ?>

		<?php do_action( 'bp_before_classified_creation_content' ) ?>
		
		<?php if (classifieds_can_user_create()) :?>

			<form action="<?php bp_classified_creation_form_action() ?>" method="post" id="create-classified-form" class="standard-form" enctype="multipart/form-data">


			
				<!-- Classified creation step 3: Avatar Uploads -->	
				<?php if ( bp_is_classified_creation_step( 'classified-avatar' ) ) : ?>

					<?php do_action( 'bp_before_classified_avatar_creation_step' ); ?>

					<div class="left-menu">
						<?php bp_new_classified_avatar() ?>
					</div>
			
					<div class="main-column">
						<p><?php _e("Upload an image to use as an avatar for this classified. The image will be shown on the main classified page, and in search results.", 'buddypress') ?></p>
				
					<?php if ( !bp_get_avatar_admin_step() ) : ?>
				
						<p>
							<input type="file" name="file" id="file" /> 
							<input type="submit" name="upload" id="upload" value="<?php _e( 'Upload Image', 'buddypress' ) ?>" />
							<input type="hidden" name="action" id="action" value="bp_avatar_upload" />
						</p>

					<?php endif; ?>
				
					<?php if ( 'crop-image' == bp_get_avatar_admin_step() ) : ?>
				
						<h3><?php _e( 'Crop Classified Avatar', 'classifieds' ) ?></h3>
					
						<img src="<?php bp_avatar_to_crop() ?>" id="avatar-to-crop" class="avatar" alt="<?php _e( 'Avatar to crop', 'buddypress' ) ?>" />
					
						<div id="avatar-crop-pane" style="width:100px;height:100px;overflow:hidden;">
							<img src="<?php bp_avatar_to_crop() ?>" id="avatar-crop-preview" class="avatar" alt="<?php _e( 'Avatar preview', 'buddypress' ) ?>" />
						</div>

						<input type="submit" name="avatar-crop-submit" id="avatar-crop-submit" value="<?php _e( 'Crop Image', 'classifieds' ) ?>" />
					
						<input type="hidden" name="image_src" id="image_src" value="<?php bp_avatar_to_crop_src() ?>" />
						<input type="hidden" name="upload" id="upload" />
						<input type="hidden" id="x" name="x" />
						<input type="hidden" id="y" name="y" />
						<input type="hidden" id="w" name="w" />
						<input type="hidden" id="h" name="h" />

					<?php endif; ?>
					
						<p><?php _e( 'To skip the avatar upload process, hit the "Next Step" button.', 'buddypress' ) ?></p>
					</div>

					<?php do_action( 'bp_after_classified_avatar_creation_step' ); ?>

					<?php wp_nonce_field( 'classifieds_create_save_classified-avatar' ) ?>
			
				<?php endif; ?>
			
				<!-- Classified creation step 4: Invite friends to classified -->	
				<?php if ( bp_is_classified_creation_step( 'classified-invites' ) ) : ?>

					<?php do_action( 'bp_before_classified_invites_creation_step' ); ?>

					<div class="left-menu">
					
						<div id="classifieds-invite-list">
							<ul>
								<?php bp_new_classified_invite_friend_list() ?>
							</ul>
						
							<?php wp_nonce_field( 'classifieds_invite_uninvite_user', '_wpnonce_invite_uninvite_user' ) ?>
						</div>
					
					</div>

					<div class="main-column">
						<?php if (classifieds_has_friends_to_invite()) {?>
							<div id="message" class="info">
								<p><?php _e('Select people to warn from your friends list.', 'classifieds'); ?></p>
							</div>

							<?php /* The ID 'friend-list' is important for AJAX support.  ?>
							<ul id="classifieds-friend-list" class="item-list"></ul>
						<?php }else {?>
							<div id="message" class="error">
								<p><?php _e( 'You either need to build up your friends list, or your friends have already been invited to check this classified.', 'classifieds' );?></p>
							</div>
						<?php
						}
						?>
		
						<?php wp_nonce_field( 'classifieds_create_save_classified-invites' ) ?>
					
					</div>

					<?php do_action( 'bp_after_classified_invites_creation_step' ); ?>
				
				<?php endif; ?>
			
				<?php do_action( 'classifieds_custom_create_steps' ) // Allow plugins to add custom classified creation steps ?>
			
				<?php do_action( 'bp_before_classified_creation_step_buttons' ); ?>

				<div class="submit" id="previous-next">
					<!-- Previous Button -->
					<?php if ( !bp_is_first_classified_creation_step() ) : ?>
						<input type="button" value="&larr; <?php _e('Previous Step', 'buddypress') ?>" id="classified-creation-previous" name="previous" onclick="location.href='<?php bp_classified_creation_previous_link() ?>'" />
					<?php endif; ?>

					<!-- Next Button -->
					<?php if ( !bp_is_last_classified_creation_step() && !bp_is_first_classified_creation_step() ) : ?>
						<input type="submit" value="<?php _e('Next Step', 'buddypress') ?> &rarr;" id="classified-creation-next" name="save" />
					<?php endif;?>
			
					<!-- Create Button -->
					<?php if ( bp_is_first_classified_creation_step() ) : ?>
						<input type="submit" value="<?php _e('Create Classified and Continue', 'classifieds') ?> &rarr;" id="classified-creation-create" name="save" />
					<?php endif; ?>
			
					<!-- Finish Button -->
					<?php if ( bp_is_last_classified_creation_step() ) : ?>
						<input type="submit" value="<?php _e('Finish', 'buddypress') ?> &rarr;" id="classified-creation-finish" name="save" />
					<?php endif; ?>
				</div>
				
				<?php do_action( 'bp_after_classified_creation_step_buttons' ); ?>

				<!-- Don't leave out this hidden field -->
				<input type="hidden" name="classified_id" id="classified_id" value="<?php bp_new_classified_id() ?>" />
			</form>
		<?php endif; ?>

		<?php do_action( 'bp_after_classified_creation_content' ) ?>
	
	</div>

<?php get_footer() 
*/
?>