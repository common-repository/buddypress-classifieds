<?php do_action( 'bp_before_classified_send_invites_content' ) ?>
<?php if ( bp_classified_is_published() ) : ?>
	<?php if ( bp_get_total_friend_count( bp_loggedin_user_id() ) ) : ?>

		<form action="<?php bp_classified_send_invite_form_action() ?>" method="post" id="send-invite-form" class="standard-form">

			<div class="left-menu">

				<div id="invite-list">
					<ul>
					
						<?php bp_new_classified_invite_friend_list() ?>
					</ul>

					<?php wp_nonce_field( 'classifieds_invite_uninvite_user', '_wpnonce_invite_uninvite_user' ) ?>
				</div>
			</div>

			<div class="main-column">

				<div id="message" class="info">
					<p><?php _e('Select people to invite from your friends list.', 'buddypress'); ?></p>
				</div>

				<?php do_action( 'bp_before_classified_send_invites_list' ) ?>

				<?php /* The ID 'friend-list' is important for AJAX support. */ ?>
				<ul id="friend-list" class="item-list"></ul>

				<?php do_action( 'bp_after_classified_send_invites_list' ) ?>

			</div>

			<div class="clear"></div>

			<div class="submit">
				<input type="submit" name="submit" id="submit" value="<?php _e( 'Send Invites', 'buddypress' ) ?>" />
			</div>

			<?php wp_nonce_field( 'classifieds_send_invites', '_wpnonce_send_invites') ?>

			<!-- Don't leave out this hidden field -->
			<input type="hidden" name="classified_id" id="classified_id" value="<?php bp_classified_id() ?>" />
		</form>

	<?php else : ?>

		<div id="message" class="info">
			<p><?php _e( 'Once you have built up friend connections you will be able to invite others to your classified. You can send invites any time in the future by selecting the "Send Invites" option when viewing your new classified.', 'buddypress' ); ?></p>
		</div>

	<?php endif; ?>
	<?php else : ?>

		<div id="message" class="info">
			<p><?php _e( 'This classified is not yet published.  Maybe you have to wait moderation before sending invitations to your friends !', 'classifieds' ); ?></p>
		</div>

	<?php endif; ?>
<?php do_action( 'bp_after_classified_send_invites_content' ) ?>