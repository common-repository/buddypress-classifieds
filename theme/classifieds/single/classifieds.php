<div class="item-list-tabs no-ajax" id="subnav">
	<ul>
		<?php if ( bp_is_my_profile() ) : ?>
			<?php bp_get_options_nav() ?>
		<?php endif; ?>

		<?php if ((bp_is_home()) || (bp_classified_user_can('Classifieds Edit Others Classifieds'))) {?>
		<li id="classifieds-status-links" class="last status no-ajax">
			<ul>
			<li><a rel="publish" href="<?php bp_my_classifieds_permalink(__( 'publish', 'classifieds-slugs' ));?>"<?php if (bp_is_my_classifieds_status(__('publish','classifieds-slugs')))echo' class="current"';?>><?php _e( 'publish', 'classifieds-slugs' );?></a></li>
			<?php if (bp_classifieds_moderation_exists()) {?>
				<li><a rel="pending" href="<?php bp_my_classifieds_permalink(__( 'pending', 'classifieds-slugs' ));?>"<?php if (bp_is_my_classifieds_status(__('pending','classifieds-slugs')))echo' class="current"';?>><?php _e( 'pending', 'classifieds-slugs' );?></a></li>
			<?php }?>
			<li><a rel="unactive" href="<?php bp_my_classifieds_permalink(__( 'unactive', 'classifieds-slugs' ));?>"<?php if (bp_is_my_classifieds_status(__('unactive','classifieds-slugs')))echo' class="current"';?>><?php _e( 'unactive', 'classifieds-slugs' );?></a></li>
			<li><a rel="followed" href="<?php bp_my_classifieds_permalink(__( 'followed', 'classifieds-slugs' ));?>"<?php if (bp_is_my_classifieds_status(__('followed','classifieds-slugs')))echo' class="current"';?>><?php _e( 'followed', 'classifieds-slugs' );?></a></li>
			<?php do_action( 'bp_member_classified_status_options' ) ?>
			</ul>
		</li>
		<?php }?>

	</ul>
</div>

<?php if ( 'invites' == bp_current_action() ) : ?>
	<?php bp_classifieds_locate_template( array( 'members/single/classifieds/invites.php' ), true ) ?>

<?php else : ?>

	<?php do_action( 'bp_before_member_classifieds_content' ) ?>

	<div class="classifieds myclassifieds">
		<?php bp_classifieds_locate_template( array( 'classifieds/classifieds-loop.php' ), true ) ?>
	</div>

	<?php do_action( 'bp_after_member_classifieds_content' ) ?>

<?php endif; ?>
