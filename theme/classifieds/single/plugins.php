<?php get_header() ?>

	<div id="content">
		<div class="padder">
			<?php if ( bp_has_classifieds() ) : while ( bp_classifieds() ) : bp_the_classified(); ?>

			<?php do_action( 'bp_before_classified_plugin_template' ) ?>

			<div id="item-header">
				<?php bp_classifieds_locate_template(array( 'classifieds/single/classified-header.php' ), true ) ?>
			</div>

			<div id="item-nav">
				<div class="item-list-tabs no-ajax" id="sub-nav">
					<ul>
						<?php bp_get_options_nav() ?>

						<?php do_action( 'bp_classified_plugin_options_nav' ) ?>
					</ul>
				</div>
			</div>

			<div id="item-body">

				<?php do_action( 'bp_template_content' ) ?>

			</div><!-- #item-body -->

			<?php endwhile; endif; ?>

			<?php do_action( 'bp_after_classified_plugin_template' ) ?>

		</div><!-- .padder -->
	</div><!-- #content -->

	<?php locate_template( array( 'sidebar.php' ), true ) ?>

<?php get_footer() ?>