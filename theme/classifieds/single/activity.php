<div class="item-list-tabs no-ajax" id="subnav">
	<ul>
		<li class="feed"><a href="<?php bp_classified_activity_feed_link() ?>" title="RSS Feed"><?php _e( 'RSS', 'buddypress' ) ?></a></li>

		<?php do_action( 'bp_classified_activity_syndication_options' ) ?>

		<li id="activity-filter-select" class="last">
			<select>
				<option value="-1"><?php _e( 'No Filter', 'buddypress' ) ?></option>
				<option value="activity_update"><?php _e( 'Show Updates', 'buddypress' ) ?></option>
				<option value="published_classified"><?php _e( 'Show New Classifieds', 'buddypress' ) ?></option>
				<option value="republished_classified"><?php _e( 'Show Republished Classifieds', 'buddypress' ) ?></option>
				<option value="followed_classified"><?php _e( 'Show Followers', 'buddypress' ) ?></option>
				<option value="new_comment"><?php _e( 'Show Comments', 'buddypress' ) ?></option>

				<?php do_action( 'bp_classified_activity_filter_options' ) ?>
			</select>
		</li>
	</ul>
</div><!-- .item-list-tabs -->

<?php do_action( 'bp_before_classified_activity_post_form' ) ?>

<?php if ( is_user_logged_in() && classifieds_is_follower() ) : ?>
	<?php locate_template( array( 'activity/post-form.php'), true ) ?>
<?php endif; ?>

<?php do_action( 'bp_after_classified_activity_post_form' ) ?>
<?php do_action( 'bp_before_classified_activity_content' ) ?>

<div class="activity single-classified">
	<?php locate_template( array( 'activity/activity-loop.php' ), true ) ?>
</div><!-- .activity -->

<?php do_action( 'bp_after_classified_activity_content' ) ?>
