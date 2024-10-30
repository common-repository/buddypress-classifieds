<?php /* Querystring is set via AJAX in _inc/ajax.php - bp_dtheme_object_filter() */ ?>
<?php do_action( 'template_notices' ) ?>

<?php do_action( 'bp_before_classifieds_loop' ) ?>

<?php 

if ( bp_has_classifieds( bp_ajax_querystring( 'classifieds' ) )) : ?>

	<div class="pagination">

		<div class="pag-count" id="classified-dir-count">
			<?php bp_classifieds_pagination_count() ?>
		</div>

		<div class="pagination-links" id="classified-dir-pag">
			<?php bp_classifieds_pagination_links() ?>
		</div>

	</div>

	<?php 

	do_action( 'bp_before_directory_classifieds_list' ) ?>

	<ul id="classifieds-list" class="item-list">
	<?php while ( bp_classifieds() ) : bp_the_classified(); ?>
	<?php 

	do_action( 'bp_before_classifieds_list_item' ) ?>

		<li>
			<div class="item-avatar">
				<a href="<?php bp_classified_permalink() ?>"><?php bp_classified_avatar( 'type=thumb&width=50&height=50' ) ?></a>
			</div>

			<div class="item">
				<div class="item-title">
					<a href="<?php bp_classified_permalink() ?>"><?php bp_classified_name() ?></a>
					<?php /*if ( bp_get_classified_latest_update() ) : ?>
						<span class="update"> - <?php bp_classified_latest_update( 'length=10' ) ?></span>
					<?php endif; */?>
					<span class="activity date-created"><?php bp_classified_date_created() ?></span>
				</div>
				<div class="item-excerpt"><?php bp_classified_description_excerpt();?></div>
				
				<div class="item-meta">
					<?php bp_classified_breadcrumb_author() ?>
					<?php if ((bp_classifieds_is_actions_enabled()) || (bp_classifieds_is_categories_enabled())){?>
					<?php _e('in','classifieds');?>
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
				</div>
				
				<?php do_action( 'bp_directory_classifieds_item' ) ?>

			</div>

			<div class="action">
				<?php bp_classified_follow_button() ?>

				<?php do_action( 'bp_directory_classifieds_actions' ) ?>
			</div>

			<div class="clear"></div>
		</li>
	<?php do_action( 'bp_after_classifieds_list_item' ) ?>
	<?php endwhile; ?>
	</ul>

	<?php do_action( 'bp_after_directory_classifieds_list' ) ?>

<?php else: 
	?>
	<div id="message" class="info">
		<p><?php _e( "Sorry, no classifieds were found.", 'buddypress' ) ?></p>
	</div>
<?php endif; ?>

<?php do_action( 'bp_after_classifieds_loop' ) ?>
