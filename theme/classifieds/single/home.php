<?php get_header() ?>

	<div id="content">
		<div class="padder">
			<?php if ( bp_has_classifieds() ) : while ( bp_classifieds() ) : bp_the_classified(); ?>
			<?php do_action( 'bp_before_classified_home_content' ) ?>
			<div id="item-header">
				<?php bp_classifieds_locate_template( array( 'classifieds/single/classified-header.php' ), true ) ?>
			</div>

			<div id="item-nav">
				<div class="item-list-tabs no-ajax" id="sub-nav">
					<ul>
						<?php bp_get_options_nav() ?>

						<?php do_action( 'bp_classified_options_nav' ) ?>
					</ul>
				</div>
			</div>
			<div id="item-body">
				<?php do_action( 'bp_before_classified_body' ) ?>

				<?php if ( bp_is_classified_admin_page() && bp_classified_is_visible() ) : ?>
					<?php bp_classifieds_locate_template( array( 'classifieds/single/admin.php' ), true ) ?>

				<?php elseif ( bp_is_classified_followers() && bp_classified_is_visible() ) : ?>
					<?php bp_classifieds_locate_template( array( 'classifieds/single/followers.php' ), true ) ?>

				<?php elseif ( bp_is_classified_invites() && bp_classified_is_visible() ) : ?>
					<?php bp_classifieds_locate_template( array( 'classifieds/single/send-invites.php' ), true ) ?>


				<?php elseif ( bp_classified_is_visible() ) : ?>
					<?php bp_classifieds_locate_template( array( 'classifieds/single/activity.php' ), true ) ?>

				<?php else : ?>
					<?php /* The classified is not visible, show the status message */ ?>

					<?php do_action( 'bp_before_classified_status_message' ) ?>

					<div id="message" class="info">

						<p><?php bp_classified_status_message() ?></p>
					</div>

					<?php do_action( 'bp_after_classified_status_message' ) ?>
				<?php endif; ?>

				<?php do_action( 'bp_after_classified_body' ) ?>
			</div>

			<?php do_action( 'bp_after_classified_home_content' ) ?>

			<?php endwhile; 
			else:
				if (!bp_classified_is_visible()) {
					?>
					<div id="message" class="error">
						<p><?php _e( "Sorry, you are not allowed to view single classifieds.", 'buddypress' ) ?></p>
					</div>
					<?php
				}
			endif; ?>
		</div><!-- .padder -->
	</div><!-- #content -->

	<?php locate_template( array( 'sidebar.php' ), true ) ?>

<?php get_footer() ?>
