<?php

/* Apply WordPress defined filters */
add_filter( 'bp_get_classified_description', 'wptexturize' );
add_filter( 'bp_get_classified_description_excerpt', 'wptexturize' );
add_filter( 'bp_get_the_site_classified_description', 'wptexturize' );
add_filter( 'bp_get_the_site_classified_description_excerpt', 'wptexturize' );
add_filter( 'bp_get_classified_name', 'wptexturize' );
add_filter( 'bp_get_the_site_classified_name', 'wptexturize' );

add_filter( 'bp_get_classified_description', 'convert_smilies' );
add_filter( 'bp_get_classified_description_excerpt', 'convert_smilies' );
add_filter( 'bp_get_the_site_classified_description', 'convert_smilies' );
add_filter( 'bp_get_the_site_classified_description_excerpt', 'convert_smilies' );

add_filter( 'bp_get_classified_description', 'convert_chars' );
add_filter( 'bp_get_classified_description_excerpt', 'convert_chars' );
add_filter( 'bp_get_classified_name', 'convert_chars' );
add_filter( 'bp_get_the_site_classified_name', 'convert_chars' );
add_filter( 'bp_get_the_site_classified_description', 'convert_chars' );
add_filter( 'bp_get_the_site_classified_description_excerpt', 'convert_chars' );

add_filter( 'bp_get_classified_description', 'wpautop' );
add_filter( 'bp_get_classified_description_excerpt', 'wpautop' );
add_filter( 'bp_get_the_site_classified_description', 'wpautop' );
add_filter( 'bp_get_the_site_classified_description_excerpt', 'wpautop' );

add_filter( 'bp_get_classified_description', 'make_clickable' );
add_filter( 'bp_get_classified_description_excerpt', 'make_clickable' );

add_filter( 'bp_get_classified_name', 'wp_filter_kses', 1 );
add_filter( 'bp_get_classified_permalink', 'wp_filter_kses', 1 );
add_filter( 'bp_get_classified_description', 'wp_filter_kses', 1 );
add_filter( 'bp_get_classified_description_excerpt', 'wp_filter_kses', 1 );
add_filter( 'bp_get_the_site_classified_name', 'wp_filter_kses', 1 );
add_filter( 'bp_get_the_site_classified_description', 'wp_filter_kses', 1 );
add_filter( 'bp_get_the_site_classified_description_excerpt', 'wp_filter_kses', 1 );
add_filter( 'classifieds_classified_name_before_save', 'wp_filter_kses', 1 );
add_filter( 'classifieds_classified_description_before_save', 'wp_filter_kses', 1 );
add_filter( 'classifieds_classified_news_before_save', 'wp_filter_kses', 1 );

add_filter( 'bp_get_classified_description', 'stripslashes' );
add_filter( 'bp_get_classified_description_excerpt', 'stripslashes' );
add_filter( 'bp_get_classified_name', 'stripslashes' );

add_filter( 'classifieds_new_classified_forum_desc', 'bp_create_excerpt' );

add_filter( 'classifieds_classified_name_before_save', 'force_balance_tags' );
add_filter( 'classifieds_classified_description_before_save', 'force_balance_tags' );

add_filter( 'bp_get_total_classified_count', 'number_format' );
add_filter( 'bp_get_classified_total_for_member', 'number_format' );
add_filter( 'bp_get_classified_total_members', 'number_format' );


?>