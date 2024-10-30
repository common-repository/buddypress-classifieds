<?php do_action( 'bp_before_classified_menu_content' ) ?>

<?php bp_classified_avatar() ?>

<?php do_action( 'bp_after_classified_menu_avatar' ) ?>



<?php do_action( 'bp_before_classified_menu_author' ) ?>
<div class="bp-widget">
	<div class="author">
		<?php bp_classified_author(false,'thumb',30) ?>
		<span class="activity"><?php _e('Author','classifieds');?>
	</div>
</div>
<?php do_action( 'bp_after_classified_menu_author' ) ?>

<?php do_action( 'bp_before_classified_menu_buttons' ) ?>
<div class="button-block">
	<?php bp_classified_follow_button() ?>
	<?php //bp_classified_publish_button() ?>
	<?php //bp_classified_delete_button() ?>

	<?php do_action( 'bp_classified_menu_buttons' ) ?>
</div>

<?php do_action( 'bp_after_classified_menu_buttons' ) ?>




<?php do_action( 'bp_after_classified_menu_content' ); /* Deprecated -> */ do_action( 'classifieds_sidebar_after' ); ?>