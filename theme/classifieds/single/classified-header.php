<?php do_action( 'bp_before_classified_header' ) ?>

<div id="item-actions">
	<?php if ( bp_classified_is_visible() ) : ?>

		<h3><?php _e( 'Classified Author', 'classifieds' ) ?></h3>
		<?php bp_classified_author() ?>

		<?php do_action( 'bp_after_classified_menu_admins' ) ?>


	<?php endif; ?>
</div>

<?php bp_classified_avatar() ?>

<h2><a href="<?php bp_classified_permalink() ?>" title="<?php bp_classified_name() ?>"><?php bp_classified_name() ?></a></h2>
<span class="highlight"><?php bp_classified_type() ?></span> <span class="activity date-created"><?php bp_classified_date_created() ?></span>

<?php do_action( 'bp_before_classified_header_meta' ) ?>

<div id="item-meta">

	<?php bp_classified_follow_button() ?>
	<?php if ((bp_classifieds_is_actions_enabled()) || (bp_classifieds_is_categories_enabled())){?>
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

	<?php do_action( 'bp_classified_header_meta' ) ?>
</div>

<div class="item-desc"><?php bp_classified_description() ?></div>

<?php do_action( 'bp_after_classified_header' ) ?>

<?php do_action( 'template_notices' ) ?>
