<?php get_header() ?>
	<div id="content">
		<div class="padder">

		<form action="" method="post" id="classifieds-directory-form" class="dir-form<?php if (classifieds_show_advanced_search())echo' advanced_search';?>">
			<h2><?php _e( 'Classifieds Directory', 'buddypress' ) ?><?php if ( is_user_logged_in() && (bp_classified_user_can('Classifieds Edit Classifieds'))  ) : ?> &nbsp;<a class="button" href="<?php echo bp_get_root_domain() . '/' . BP_CLASSIFIEDS_SLUG . '/create/' ?>"><?php _e( 'Create a Classified', 'buddypress' ) ?></a><?php endif; ?></h2>

			<?php do_action( 'bp_before_directory_classifieds_content' ) ?>
			<?php
			if (!bp_classifieds_are_visible()) {
			?>
			<div id="message" class="error">
				<p><?php _e( "Sorry, you are not allowed to view classifieds.", 'buddypress' ) ?></p>
			</div>
			<?php
			}else {
			?>
			<div id="classified-dir-search" class="dir-search"><!-- not .dir-search because mess with ajax return false-->
				<?php bp_classifieds_search_form();?>
			</div><!-- #classified-dir-search -->

			<div class="item-list-tabs no-ajax">
				<ul>
					<li id="classifieds-all"<?php if (bp_classifieds_is_directory_all()) echo ' class="selected"';?>><a href="<?php echo bp_root_domain().'/'. BP_CLASSIFIEDS_SLUG ?>"><?php printf( __( 'All Classifieds (%s)', 'buddypress' ), bp_get_total_classified_count() ) ?></a></li>
					<?php
					//actions tabs
					if (bp_classifieds_is_actions_enabled()) {
						$actions = BP_Classifieds_Actions::get_all();
						foreach ($actions as $action) {
							?>
							<li id="classifieds-action<?php echo $action->term_id;?>"<?php if (bp_classifieds_is_directory_action($action)) echo ' class="selected"';?>><a href="<?php bp_classified_action_permalink($action,true,true); ?>"><?php printf( ucfirst($action->name).' (%s)', bp_get_total_action_classified_count( $action->term_id ) ) ?></a></li>
							<?php
						}
					}
					?>
					<?php if ( is_user_logged_in() && bp_get_total_member_classified_count( bp_loggedin_user_id() ) ) : ?>
						<li id="classifieds-personal"><a href="<?php echo bp_loggedin_user_domain() . BP_CLASSIFIEDS_SLUG . '/my-classifieds/' ?>"><?php printf( __( 'My Classifieds (%s)', 'buddypress' ), bp_get_total_member_classified_count( bp_loggedin_user_id() ) ) ?></a></li>
					<?php endif; ?>

					<?php do_action( 'bp_classifieds_directory_classified_types' ) ?>

				</ul>
			</div><!-- .item-list-tabs -->

				<div id="classifieds-dir-list" class="classifieds dir-list">
					<?php bp_classifieds_locate_template( array( 'classifieds/classifieds-loop.php' ), true ) ?>
				</div><!-- #classifieds-dir-list -->

			<?php
			}
			?>
			<?php do_action( 'bp_directory_classifieds_content' ) ?>

			<?php wp_nonce_field( 'directory_classifieds', '_wpnonce-classifieds-filter' ) ?>

		</form><!-- #classifieds-directory-form -->

		<?php do_action( 'bp_after_directory_classifieds_content' ) ?>

		</div><!-- .padder -->
	</div><!-- #content -->

	<?php locate_template( array( 'sidebar.php' ), true ) ?>

<?php get_footer() ?>
