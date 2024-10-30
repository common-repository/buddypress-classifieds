<div class="item-list-tabs no-ajax" id="subnav">
	<ul>
		<?php bp_classified_admin_tabs(); ?>
	</ul>
</div><!-- .item-list-tabs -->

<form action="<?php bp_classified_admin_form_action() ?>" name="classified-settings-form" id="classified-settings-form" class="standard-form" method="post" enctype="multipart/form-data">

<?php do_action( 'bp_before_classified_admin_content' ) ?>

<?php /* Edit Classified Details */ ?>
<?php if ( bp_is_classified_admin_screen( __('edit-details','classifieds-slugs' ) ) ) : ?>

	<?php do_action( 'bp_before_classified_details_admin' ); ?>

	<label for="classified-name">* <?php _e( 'Classified Name', 'classifieds' ) ?></label>
	<input type="text" name="classified-name" id="classified-name" value="<?php bp_classified_name() ?>" />

	<label for="classified-desc">* <?php _e( 'Classified Description', 'classifieds' ) ?></label>
	<textarea name="classified-desc" id="classified-desc"><?php bp_classified_description_editable() ?></textarea>

	<?php do_action( 'classifieds_custom_classified_fields_editable' ) ?>

	<?php do_action( 'bp_after_classified_details_admin' ); ?>

	<p><input type="submit" value="<?php _e( 'Save Changes', 'buddypress' ) ?> &raquo;" id="save" name="save" /></p>
	<?php wp_nonce_field( 'classifieds_edit_classified_details' ) ?>


<?php endif; ?>

<?php /* Manage Classified Settings */ ?>
<?php if ( bp_is_classified_admin_screen( __('classified-settings','classifieds-slugs' ) ) ) : ?>

	<?php do_action( 'bp_before_classified_settings_admin' ); ?>

	<?php if ( function_exists('bp_wire_install') ) : ?>

		<div class="checkbox">
			<label><input type="checkbox" name="classified-show-wire" id="classified-show-wire" value="1"<?php bp_classified_show_wire_setting() ?>/> <?php _e( 'Enable comment wire', 'buddypress' ) ?></label>
		</div>

	<?php endif; ?>

	<?php if (bp_classifieds_is_actions_enabled()) :?>
		<h3><?php _e( 'Choose Action', 'classifieds' ); ?></h3>
		<?php bp_new_classified_action();?>
	<?php endif; ?>
	
	<h3><?php _e( 'Choose Category', 'classifieds' ); ?></h3>
	<?php bp_new_classified_categories();?>
	
	<h3><?php _e( 'Choose Tags', 'classifieds' ); ?></h3>
	<label for="classified-tags"><?php _e( 'Tags', 'buddypress' ) ?></label>
	<input name="classified-tags" id="classified-tags" value="<?php bp_classified_tags_editable();?>"/>

	<?php do_action( 'bp_after_classified_settings_admin' ); ?>

	<p><input type="submit" value="<?php _e( 'Save Changes', 'buddypress' ) ?> &raquo;" id="save" name="save" /></p>
	<?php wp_nonce_field( 'classifieds_edit_classified_settings' ) ?>

<?php endif; ?>

<?php /* Classified Avatar Settings */ ?>
<?php if ( bp_is_classified_admin_screen( __('classified-avatar','classifieds-slugs' ) ) ) : ?>

	<?php if ( 'upload-image' == bp_get_avatar_admin_step() ) : ?>

			<p><?php _e("Upload an image to use as an avatar for this classified. The image will be shown on the main classified page, and in search results.", 'buddypress') ?></p>

			<p>
				<input type="file" name="file" id="file" />
				<input type="submit" name="upload" id="upload" value="<?php _e( 'Upload Image', 'buddypress' ) ?>" />
				<input type="hidden" name="action" id="action" value="bp_avatar_upload" />
			</p>

			<?php if ( bp_get_classified_has_avatar() ) : ?>
				<p><?php _e( "If you'd like to remove the existing avatar but not upload a new one, please use the delete avatar button.", 'buddypress' ) ?></p>

				<div class="generic-button" id="delete-classified-avatar-button">
					<a class="edit" href="<?php bp_classified_avatar_delete_link() ?>" title="<?php _e( 'Delete Avatar', 'buddypress' ) ?>"><?php _e( 'Delete Avatar', 'buddypress' ) ?></a>
				</div>
			<?php endif; ?>

			<?php wp_nonce_field( 'bp_avatar_upload' ) ?>

	<?php endif; ?>

	<?php if ( 'crop-image' == bp_get_avatar_admin_step() ) : ?>

		<h3><?php _e( 'Crop Avatar', 'buddypress' ) ?></h3>

		<img src="<?php bp_avatar_to_crop() ?>" id="avatar-to-crop" class="avatar" alt="<?php _e( 'Avatar to crop', 'buddypress' ) ?>" />

		<div id="avatar-crop-pane">
			<img src="<?php bp_avatar_to_crop() ?>" id="avatar-crop-preview" class="avatar" alt="<?php _e( 'Avatar preview', 'buddypress' ) ?>" />
		</div>

		<input type="submit" name="avatar-crop-submit" id="avatar-crop-submit" value="<?php _e( 'Crop Image', 'buddypress' ) ?>" />

		<input type="hidden" name="image_src" id="image_src" value="<?php bp_avatar_to_crop_src() ?>" />
		<input type="hidden" id="x" name="x" />
		<input type="hidden" id="y" name="y" />
		<input type="hidden" id="w" name="w" />
		<input type="hidden" id="h" name="h" />

		<?php wp_nonce_field( 'bp_avatar_cropstore' ) ?>

	<?php endif; ?>

<?php endif; ?>

<?php do_action( 'classifieds_custom_edit_steps' ) // Allow plugins to add custom classified edit screens ?>

<?php /* Delete Classified Option */ ?>
<?php if ( bp_is_classified_admin_screen( __('delete-classified','classifieds-slugs' ) ) ) : ?>

	<?php do_action( 'bp_before_classified_delete_admin' ); ?>

	<div id="message" class="info">
		<p><?php _e( 'WARNING: Deleting this classified will completely remove ALL content associated with it. There is no way back, please be careful with this option.', 'buddypress' ); ?></p>
	</div>

	<input type="checkbox" name="delete-classified-understand" id="delete-classified-understand" value="1" onclick="if(this.checked) { document.getElementById('delete-classified-button').disabled = ''; } else { document.getElementById('delete-classified-button').disabled = 'disabled'; }" /> <?php _e( 'I understand the consequences of deleting this classified.', 'buddypress' ); ?>

	<?php do_action( 'bp_after_classified_delete_admin' ); ?>

	<div class="submit">
		<input type="submit" disabled="disabled" value="<?php _e( 'Delete Classified', 'buddypress' ) ?> &rarr;" id="delete-classified-button" name="delete-classified-button" />
	</div>

	<input type="hidden" name="classified-id" id="classified-id" value="<?php bp_classified_id() ?>" />

	<?php wp_nonce_field( 'classifieds_delete_classified' ) ?>

<?php endif; ?>

<?php /* This is important, don't forget it */ ?>
<input type="hidden" name="classified-id" id="classified-id" value="<?php bp_classified_id() ?>" />

<?php do_action( 'bp_after_classified_admin_content' ) ?>

</form>

