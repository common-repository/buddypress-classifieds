<?php
/*
function bp_classifieds_tinymce_scripts() {
     wp_enqueue_script('tiny_mce');
}
add_action("wp_print_scripts", "bp_classifieds_tinymce_scripts");
function bp_classifieds_tinymce_head() {
?>
<script type="text/javascript">
	
	tinyMCE.init({
		mode : "exact",
		elements: "classified-desc",
		theme : "simple"
	});

</script>
<?php
}

add_action("wp_head","bp_classifieds_tinymce_head");


function bp_classifieds_tinymce_init() {
	include_once(ABSPATH.'/wp-admin/includes/post.php');

		wp_tiny_mce( false , // true makes the editor "teeny"
			array(
				"editor_selector" => "classified-desc"
			)
		);

}

add_action("wp",'bp_classifieds_tinymce_init');
*/



function bp_classifieds_tinymce_head() {
	
	if ((!bp_is_classified_creation_step( __('classified-details','classifieds-slugs' ) )) && (!bp_is_classified_admin_screen( __('edit-details','classifieds-slugs' ) ) )) return false;
	


	global $bp;
	
	if ( defined( 'WPLANG' ) ) {
		$language = explode('_',WPLANG);
		$language = $language[0];
	}else {
		$language='en';
	}
		//Enqueued, it loads too late
		echo '<script language="javascript" type="text/javascript" src="'.$bp->root_domain.'/'.WPINC.'/js/tinymce/tiny_mce.js"></script>';
		
    	echo '<script language="javascript" type="text/javascript">';
    	echo 'tinyMCE.init({mode : "exact",elements : "classified-desc", language : "'.$language.'", theme : "advanced", theme_advanced_buttons1 : "bold,italic,bullist,numlist,blockquote,link,unlink", theme_advanced_buttons2 : "", theme_advanced_buttons3 : "", language : "en",theme_advanced_toolbar_location : "top", theme_advanced_toolbar_align : "left"});';
    	echo '</script>';
}


function bp_classifieds_tinymce_allowed_tags($c) {
	global $allowedtags;
		
		$allowedtags['em'] = array();
		$allowedtags['strong'] = array();
      	$allowedtags['ol'] = array();
      	$allowedtags['li'] = array();
      	$allowedtags['ul'] = array();
      	$allowedtags['blockquote'] = array();
      	$allowedtags['code'] = array();
      	$allowedtags['pre'] = array();
      	$allowedtags['a'] = array(
        	'href' => array(),
        	'title' => array(),
        	'target' => array(),
       		);
      	$allowedtags['img'] = array(
        	'src' => array(),
        	);
        $allowedtags['b'] = array();
        $allowedtags['span'] = array(
        	'style' => array(),
        	);
        $allowedtags['p'] = array();
        $allowedtags['br'] = array();

	$search = array(	'&lt;p&gt;',
						'&lt;/p&gt;',
						'&lt;br&gt;',
						'&lt;br /&gt;',	
						'&lt;em&gt;',
						'&lt;/em&gt;',
						'&lt;i&gt;',
						'&lt;/i&gt;',
						'&lt;strong&gt;',
						'&lt;/strong&gt;',
						'&lt;b&gt;',
						'&lt;/b&gt;',
						'&lt;/a&gt;',
						'&lt;ol&gt;',
						'&lt;/ol&gt;',
						'&lt;ul&gt;',
						'&lt;/ul&gt;',
						'&lt;li&gt;',
						'&lt;/li&gt;',
						'&lt;blockquote&gt;',
						'&lt;/blockquote&gt;',
						'<b>',
						'</b>',
						'<i>',
						'</i>',
					);
	$replace = array(	'<p>',
						'</p>',
						'<br>',
						'<br />',
						'<em>',
						'</em>',
						'<em>',
						'</em>',
						'<strong>',
						'</strong>',
						'<strong>',
						'</strong>',
						'</a>',
						'<ol>',
						'</ol>',
						'<ul>',
						'</ul>',
						'<li>',
						'</li>',
						'<blockquote>',
						'</blockquote>',
						'<strong>',
						'</strong>',
						'<em>',
						'</em>',
					);
	
	$c = preg_replace( "/&lt;a (title.*?)?href=&quot;http:([a-zA-Z_.\/-]+?)&quot;( target.*?)?&gt;/", '<a $1 href="http://$2" $3>', $c );
	$c = preg_replace( '/&lt;span style=&quot;text-decoration: underline;?&quot;&gt;(.*?)&lt;\/span&gt;/', '<span style="text-decoration: underline">$1</span>', $c );
	
	$c = str_replace( $search, $replace, $c );
	
	return wp_kses( $c, $allowedtags );
}

add_filter( 'bp_get_new_classified_description', 'bp_classifieds_tinymce_allowed_tags', 2 );
add_filter( 'bp_get_classified_description_editable', 'bp_classifieds_tinymce_allowed_tags', 2 );
add_action( 'wp_head', 'bp_classifieds_tinymce_head', 1 );




?>
