<?php get_header() ?>

	<div class="content-header">
	
	</div>

	<div id="content">	
		<?php do_action( 'template_notices' ) // (error/success feedback) ?>
	
		<?php if ( bp_has_classifieds() ) : while ( bp_classifieds() ) : bp_the_classified(); ?>

			<?php do_action( 'bp_before_classified_wire_content' ) ?>
	
			<div class="left-menu">
				<?php bp_classifieds_locate_template( array( 'classifieds/single/menu.php' ), true ) ?>
			</div>

			<div class="main-column">
				<div class="inner-tube">
			
					<div id="classified-name">
						<h1><a href="<?php bp_classified_permalink() ?>"><?php bp_classified_name() ?></a></h1>
						<p class="status"><?php bp_classified_type() ?></p>
					</div>

					<div class="bp-widget">
						<?php if ( function_exists('bp_wire_get_post_list') ) : ?>
							
							<?php bp_wire_get_post_list( bp_classified_id( false, false), __( 'Classified Wire', 'classifieds' ), sprintf( __( 'There are no wire posts for %s', 'buddypress' ), bp_classified_name(false) ), bp_classified_user_can('classifieds_wire_notify'), true ) ?>
						
						<?php endif; ?>
					</div>
			
				</div>
			</div>
	
		<?php endwhile; endif; ?>

	</div>

<?php get_footer() ?>