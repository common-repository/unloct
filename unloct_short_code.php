<?php
/**Logged in shortcode**/
function unloct_shortcode( $atts, $content = null ) {
	$atts = shortcode_atts( array(
		'userlevel' => 'none',
	), $atts, 'unloct' );

	/*global $unloct_shortcode_message;
	$unloct_shortcode_message = get_option('unloct_shortcode_message');
	
	if ( $atts['userlevel'] == 'admin' && current_user_can( 'switch_themes' ) ) {
		return '<p>' . do_shortcode( $content ) . '</p>';
	}
	if ( $atts['userlevel'] == 'editor' && current_user_can( 'moderate_comments' ) ) {
		return '<p>' . do_shortcode( $content ) . '</p>';
	}
	if ( $atts['userlevel'] == 'author' && current_user_can( 'upload_files' ) ) {
		return '<p>' . do_shortcode( $content ) . '</p>';
	}
	if ( $atts['userlevel'] == 'contributor' && current_user_can( 'edit_posts' ) ) {
		return '<p>' . do_shortcode( $content ) . '</p>';
	}
	if ( $atts['userlevel'] == 'subscriber' && current_user_can( 'read' ) ) {
		return '<p>' . do_shortcode( $content ) . '</p>';
	}*/
	if ( $atts['userlevel'] == 'none' && is_user_logged_in() ) {
		return '<p>' . do_shortcode( $content ) . '</p>';
	} else {
		return '<p>Only Unloct.com subscribers can access this premium content. <a href="https://unloct.com">Click here to subscribe!</a></p>';
	}
}

add_shortcode( 'unloct', 'unloct_shortcode' );
?>