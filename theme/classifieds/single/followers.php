<?php if ( bp_classified_has_followers() ) : ?>

	<?php do_action( 'bp_before_classified_followers_content' ) ?>

	<div class="pagination">

		<div id="follower-count" class="pag-count">
			<?php bp_classified_follower_pagination_count() ?>
		</div>

		<div id="follower-pagination" class="pagination-links">
			<?php bp_classified_follower_pagination() ?>
		</div>

	</div>

	<?php do_action( 'bp_before_classified_followers_list' ) ?>

	<ul id="follower-list" class="item-list">
		<?php while ( bp_classified_followers() ) : bp_classified_the_user(); ?>

			<li>
				<?php bp_classified_follower_avatar_thumb() ?>
				<h5><?php bp_classified_follower_link() ?></h5>
				<span class="activity"><?php bp_classified_follower_last_activity() ?></span>

				<?php do_action( 'bp_classified_followers_list_item' ) ?>

				<?php if ( function_exists( 'friends_install' ) ) : ?>

					<div class="action">
						<?php bp_add_friend_button( bp_get_classified_follower_id() ) ?>

						<?php do_action( 'bp_classified_followers_list_item_action' ) ?>
					</div>

				<?php endif; ?>
			</li>

		<?php endwhile; ?>

	</ul>

	<?php do_action( 'bp_after_classified_followers_content' ) ?>

<?php else: ?>

	<div id="message" class="info">
		<p><?php _e( 'This classified has no followers.', 'classifieds' ); ?></p>
	</div>

<?php endif; ?>
