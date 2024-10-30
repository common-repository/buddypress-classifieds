<?php


function bp_classifieds_ajax_querystring($query_string, $object, $filter, $scope, $page, $search_terms, $extras ) {
	global $bp;


	if ($object=='classifieds') { //not !RETURN FALSE it breaks the thing
		$qs_arr = array();
		parse_str( $query_string, $qs_arr );
		
		$extras = $_COOKIE['bp-classifieds-extras'];

		if ($extras) {
			$extras = stripslashes($extras);
			$extras = json_decode($extras);

			if ($extras->action_tag)  {
				$new_qs_arr['action_tag']=$extras->action_tag;
				if ($scope) {
					$scope='all';
					$new_qs_arr['scope']=$scope;
					unset($qs_arr['user_id']);
				}
			}

			if ($extras->status)
				$new_qs_arr['status=']=$extras->status;

			if ($extras->cats) {
				if (is_array($extras->cats))
					$cats_str=implode(',',$extras->cats);
				$new_qs_arr['cats']=$cats_str;
				
			}
		}

		if ($new_qs_arr) {
			$new_qs_arr = wp_parse_args( $new_qs_arr, $qs_arr );
			$query_string=http_build_query( $new_qs_arr );
		}

		if ( defined( 'BP_CLASSIFIEDS_DEBUG' ) )$bp->classifieds->debug->log($new_qs_arr,'bp_classifieds_ajax_querystring $new_qs_arr');

		return apply_filters( 'bp_classifieds_ajax_querystring',$query_string,$object,$filter,$scope,$page,$search_terms,$extras );


	}
	
	return $query_string;


}
add_filter('bp_dtheme_ajax_querystring', 'bp_classifieds_ajax_querystring',10,7);


//DUPLICATED FROM CORE TO REPLACE locate_template BY bp_classifieds_locate_template
function bp_classifieds_object_template_loader() {
	$object = esc_attr( $_POST['object'] );
	bp_classifieds_locate_template( array( "$object/$object-loop.php" ), true );
}
add_action( 'wp_ajax_classifieds_filter', 'bp_classifieds_object_template_loader' );


function bp_classifieds_ajax_follow_button() {
	global $bp;
	
	//TO FIX : $_POST['cid'] = wrong value
	$classified_id=$_POST['cid'];


	
	$followed = classifieds_is_follower( $bp->loggedin_user->id, $classified_id );


	if ($followed) {

		check_ajax_referer('classifieds_unfollow_classified');
		

		if ( !classifieds_unfollow_classified( $classified_id, $bp->loggedin_user->id ) ) {
			echo __("Classified could not be unfollowed.", 'classifieds');
		} else {
			echo '<a href="' . wp_nonce_url( $bp->root_domain . '/' . $bp->classifieds->slug . '/' . $classified_id . '/' . __('follow','classifieds-slugs'), 'classifieds_follow_classified' ) . '" title="' . __('Follow Classified', 'classifieds') . '" id="classified-' . $classified->ID . '" rel="follow" class="follow">' . __( 'Follow Classified', 'classifieds' ) . '</a>';
		}
	} else {
		
		check_ajax_referer('classifieds_follow_classified');
		if ( !classifieds_follow_classified( $classified_id, $bp->loggedin_user->id ) ) {
			echo __("Classified could not be followed.", 'classifieds');
		} else {
			echo '<a href="' . wp_nonce_url( $bp->root_domain . '/' . $bp->classifieds->slug . '/' . $classified_id . '/' . __('unfollow','classifieds-slugs'), 'classifieds_unfollow_classified' ), '" title="' . __('Unfollow Classified', 'classifieds') . '" id="classified-' . $classified->ID . '" rel="unfollow" class="unfollow">' . __( 'Unfollow Classified', 'classifieds' ) . '</a>';
			
		}
	}

	return false;
}
add_action( 'wp_ajax_follow_button', 'bp_classifieds_ajax_follow_button' );


?>