<?php

function classifieds_notification_new_wire_post( $classified_id, $wire_post_id ) {
	global $bp;
	
	if ( !isset( $_POST['wire-post-email-notify'] ) )
		return false;
	
	$wire_post = new BP_Wire_Post( $bp->classifieds->table_name_wire, $wire_post_id );
	$classified = new BP_Classifieds_Classified( $classified_id, false);
	
	$poster_name = bp_core_get_user_displayname( $wire_post->user_id );
	$poster_profile_link = bp_core_get_user_domain( $wire_post->user_id ); 

	$subject = '[' . get_blog_option( BP_ROOT_BLOG, 'blogname' ) . '] ' . sprintf( __( 'New wire post on classified: %s', 'classifieds' ), stripslashes( attribute_escape( $classified->name ) ) );

	foreach ( $classified->user_dataset as $user ) {
		if ( 'no' == get_usermeta( $user->user_id, 'notification_classifieds_wire_post' ) ) continue;
		
		$ud = get_userdata( $user->user_id );
		
		// Set up and send the message
		$to = $ud->user_email;

		$wire_link = site_url( $bp->classifieds->slug . '/' . $classified->slug . '/wire/' );
		$classified_link = site_url( $bp->classifieds->slug . '/' . $classified->slug . '/' );
		$settings_link = bp_core_get_user_domain( $user->user_id ) . 'settings/notifications/'; 

		$message = sprintf( __( 
'%s posted on the wire of the classified "%s":

"%s"

To view the classified wire: %s

To view the classified home: %s

To view %s\'s profile page: %s

---------------------
', 'classifieds' ), $poster_name, stripslashes( attribute_escape( $classified->name ) ), stripslashes($wire_post->content), $wire_link, $classified_link, $poster_name, $poster_profile_link );

		$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );

		// Send it
		wp_mail( $to, $subject, $message );
		
		unset( $message, $to );
	}
}

//TO FIX | TO REMOVE ? 
/*
function classifieds_notification_classified_updated( $classified_id ) {
	global $bp;
	
	$classified = new BP_Classifieds_Classified( $classified_id, false);
	$subject = '[' . get_blog_option( BP_ROOT_BLOG, 'blogname' ) . '] ' . __( 'Classified Details Updated', 'classifieds' );

	foreach ( $classified->user_dataset as $user ) {
		if ( 'no' == get_usermeta( $user->user_id, 'notification_classifieds_classified_updated' ) ) continue;
		
		$ud = get_userdata( $user->user_id );
		
		// Set up and send the message
		$to = $ud->user_email;

		$classified_link = site_url( $bp->classifieds->slug . '/' . $classified->slug );
		$settings_link = bp_core_get_user_domain( $user->user_id ) . 'settings/notifications/';

		$message = sprintf( __( 
'Classified details for the classified "%s" were updated:

To view the classified: %s

---------------------
', 'classifieds' ), stripslashes( attribute_escape( $classified->name ) ), $classified_link );

		$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );

		// Send it
		wp_mail( $to, $subject, $message );

		unset( $message, $to );
	}
}
*/
function classifieds_notification_classified_invite( &$classified, $invited_user_id, $inviter_user_id ) {
	global $bp, $wpdb;
	
	if ( defined( 'BP_CLASSIFIEDS_DEBUG' ) )$bp->classifieds->debug->info('classifieds_notification_classified_invite START');
	
	$inviter_ud = get_userdata( $inviter_user_id );
	$inviter_name = bp_core_get_userlink( $inviter_user_id, true, false, true );
	$inviter_link = bp_core_get_user_domain( $inviter_user_id );
	
	$classified_link = bp_get_classified_permalink( $classified );

	// Post a screen notification first.
	$notification_sent = bp_core_add_notification( $classified->ID, $invited_user_id, 'classifieds', 'classified_invite',$inviter_user_id);
	
	if ( defined( 'BP_CLASSIFIEDS_DEBUG' ) )$bp->classifieds->debug->log($notification_sent,'classifieds_notification_classified_invite - notification_sent');

	if ( 'no' == get_usermeta( $invited_user_id, 'notification_classifieds_invite' ) )
		return false;

	$invited_ud = get_userdata($invited_user_id);
	
	$settings_link = bp_core_get_user_domain( $invited_user_id ) . 'settings/notifications/';

	// Set up and send the message
	$to = $invited_ud->user_email;

	$subject = '[' . get_blog_option( BP_ROOT_BLOG, 'blogname' ) . '] ' . sprintf( __( 'You have an invitation to check the classified: "%s"', 'classifieds' ), stripslashes( attribute_escape( $classified->name ) ) );

	$message = sprintf( __( 
'One of your friends %s has invited you to check the classified: "%s".

To view the classified visit: %s

To view %s\'s profile visit: %s

---------------------
', 'classifieds' ), $inviter_name, stripslashes( attribute_escape( $classified->name ) ), $classified_link, $inviter_name, $inviter_link );

	$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );

	// Send it
	$sent_mail = wp_mail( $to, $subject, $message );

	if ( defined( 'BP_CLASSIFIEDS_DEBUG' ) )$bp->classifieds->debug->log(array('result'=>$sent_mail,'mail'=>array('to'=>$to,'subject'=>$subject,'message'=>$message)),'classifieds_notification_classified_invite - mail');
}

function classifieds_notification_classified_pending( &$classified, $moderator_id) {
	global $bp, $wpdb;
	
	if ( defined( 'BP_CLASSIFIEDS_DEBUG' ) )$bp->classifieds->debug->info('classifieds_notification_classified_pending START');
	
	$moderator_ud = get_userdata( $moderator_id );
	$moderator_name = bp_core_get_userlink( $moderator_id, true, false, true );
	$moderator_link = bp_core_get_user_domain( $moderator_id );

	// Post a screen notification first.
	$notification_sent = bp_core_add_notification( $classified->ID, $moderator_id, 'classifieds', 'classified_pending' );
	
	if ( defined( 'BP_CLASSIFIEDS_DEBUG' ) )$bp->classifieds->debug->log($notification_sent,'classifieds_notification_classified_pending - notification_sent');

	if ( 'no' == get_usermeta( $moderator_id, 'notification_classifieds_pending' ) )
		return false;

	$classified_link = bp_get_classified_permalink( $classified );
	$classified_author = bp_core_get_userlink( $classified->creator_id, true, false, true );
	$classified_admin_link = $classified_link. 'admin';
	$classifieds_admin_link = get_blog_option( BP_ROOT_BLOG, 'siteurl' ).'/wp-admin/admin.php?page=bp-classifieds/bp-classifieds-admin.php';
	$settings_link = $moderator_link . 'settings/notifications/';

	// Set up and send the message
	$to = $moderator_ud->user_email;
	
	$author = new BP_Core_User($classified->creator_id);

	$subject = '[' . get_blog_option( BP_ROOT_BLOG, 'blogname' ) . '] ' . sprintf( __( 'A new classified "%s" by %s is pending !', 'classifieds' ), stripslashes( attribute_escape( $classified->name ) ),stripslashes( attribute_escape( $author->fullname ) ) );

	$message = sprintf( __( 
'A new classified : "%s" is pending moderation.

To view the classified visit: %s

To edit the classified details visit : %s

To manage classifieds visit : %s

---------------------
', 'classifieds' ), stripslashes( attribute_escape( $classified->name ) ), $classified_link, $classified_admin_link, $classifieds_admin_link );

	$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );

	// Send it
	$sent_mail = wp_mail( $to, $subject, $message );

	if ( defined( 'BP_CLASSIFIEDS_DEBUG' ) )$bp->classifieds->debug->log(array('result'=>$sent_mail,'mail'=>array('to'=>$to,'subject'=>$subject,'message'=>$message)),'classifieds_notification_classified_pending - mail');
}

function classifieds_notification_classified_published( &$classified, $author_id) {
	global $bp, $wpdb;
	
	if ( defined( 'BP_CLASSIFIEDS_DEBUG' ) )$bp->classifieds->debug->info('classifieds_notification_classified_published START');
	
	$author_ud = get_userdata( $author_id );
	$author_name = bp_core_get_userlink( $author_id, true, false, true );
	$author_link = bp_core_get_user_domain( $author_id );

	// Post a screen notification first.
	$notification_sent = bp_core_add_notification( $classified->ID, $author_id, 'classifieds', 'classified_published' );
	
	if ( defined( 'BP_CLASSIFIEDS_DEBUG' ) )$bp->classifieds->debug->log($notification_sent,'classifieds_notification_classified_published - notification_sent');

	if ( 'no' == get_usermeta( $author_id, 'notification_classifieds_published' ) )
		return false;

	$classified_link = bp_get_classified_permalink( $classified );
	$classified_admin_link = $classified_link. 'admin';
	$classified_invites_link = $classified_link. 'send-invites';
	$settings_link = $author_link . 'settings/notifications/';

	// Set up and send the message
	$to = $author_ud->user_email;

	$subject = '[' . get_blog_option( BP_ROOT_BLOG, 'blogname' ) . '] ' . sprintf( __( 'Your classified "%s" has been validated and published !', 'classifieds' ), stripslashes( attribute_escape( $classified->name ) ) );

	$message = sprintf( __( 
'Your classified : "%s" has been validated by an administrator and published.

To view the classified visit: %s

To edit the classified details visit : %s

Don\'t forget you can invite your friends to check it out here : %s

---------------------
', 'classifieds' ), stripslashes( attribute_escape( $classified->name ) ), $classified_link, $classified_admin_link, $classified_invites_link );

	$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );

	// Send it
	$sent_mail = wp_mail( $to, $subject, $message );

	if ( defined( 'BP_CLASSIFIEDS_DEBUG' ) )$bp->classifieds->debug->log(array('result'=>$sent_mail,'mail'=>array('to'=>$to,'subject'=>$subject,'message'=>$message)),'classifieds_notification_classified_published - mail');
}

function classifieds_notification_classified_republished( &$classified, $author_id) {
	global $bp, $wpdb;
	
	if ( defined( 'BP_CLASSIFIEDS_DEBUG' ) )$bp->classifieds->debug->info('classifieds_notification_classified_republished START');
	
	$author_ud = get_userdata( $author_id );
	$author_name = bp_core_get_userlink( $author_id, true, false, true );
	$author_link = bp_core_get_user_domain( $author_id );

	// Post a screen notification first.
	
	//only if creator!=current user
	if ($bp->loggedin_user->id==$author_id)	return false;
	
	
	$notification_sent = bp_core_add_notification( $classified->ID, $author_id, 'classifieds', 'classified_republished' );
	
	if ( defined( 'BP_CLASSIFIEDS_DEBUG' ) )$bp->classifieds->debug->log($notification_sent,'classifieds_notification_classified_republished - notification_sent');

	if ( 'no' == get_usermeta( $author_id, 'notification_classifieds_published' ) )
		return false;

	$classified_link = bp_get_classified_permalink( $classified );
	$classified_admin_link = $classified_link. '/admin';
	$classified_invites_link = $classified_link. '/send-invites';
	$settings_link = $author_link . 'settings/notifications/';

	// Set up and send the message
	$to = $author_ud->user_email;

	$subject = '[' . get_blog_option( BP_ROOT_BLOG, 'blogname' ) . '] ' . sprintf( __( 'Your classified "%s" has been republished !', 'classifieds' ), stripslashes( attribute_escape( $classified->name ) ) );

	$message = sprintf( __( 
'Your classified : "%s" has been republished.

To view the classified visit: %s

To edit the classified details visit : %s

Don\'t forget you can invite your friends to check it out here : %s

---------------------
', 'classifieds' ), stripslashes( attribute_escape( $classified->name ) ), $classified_link, $classified_admin_link, $classified_invites_link );

	$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );

	// Send it
	$sent_mail = wp_mail( $to, $subject, $message );

	if ( defined( 'BP_CLASSIFIEDS_DEBUG' ) )$bp->classifieds->debug->log(array('result'=>$sent_mail,'mail'=>array('to'=>$to,'subject'=>$subject,'message'=>$message)),'classifieds_notification_classified_republished - mail');
}

?>