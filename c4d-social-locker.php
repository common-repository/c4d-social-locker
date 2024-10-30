<?php
/*
Plugin Name: C4D Social Locker
Plugin URI: http://coffee4dev.com/
Description: Lock your content until the visitor share it.
Author: Coffee4dev.com
Author URI: http://coffee4dev.com/category/products/wordpress/
Text Domain: c4d-social-locker
Version: 2.0.0
*/

define('C4DSL_PLUGIN_URI', plugins_url('', __FILE__));

add_action('wp_enqueue_scripts', 'c4d_social_locker_safely_add_stylesheet_to_frontsite');
add_shortcode('c4d-social-locker', 'c4d_social_locker');
add_action('wp_ajax_c4d_social_locker_t_auth', 'c4d_social_locker_t_auth');
add_action('wp_ajax_nopriv_c4d_social_locker_t_auth', 'c4d_social_locker_t_auth');
add_action('wp_ajax_c4d_social_locker_t_post', 'c4d_social_locker_t_post');
add_action('wp_ajax_nopriv_c4d_social_locker_t_post', 'c4d_social_locker_t_post');
add_action( 'c4d-plugin-manager-section', 'c4d_social_locker_section_options');
session_start();
// var_dump($_SESSION); die();
function c4d_social_locker_t_post() {
	$cb = c4d_social_locker_t_connect();
	// assign access token on each page load
	session_start();
	if (isset($_SESSION['oauth_token']) && isset($_GET['data']['status'])) {
		$cb->setToken($_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
		$params = array(
		  'status' => esc_html__($_GET['data']['status']) . ' ' . date("F j, Y, g:i a")
		);
		$reply = $cb->statuses_update($params);
		echo json_encode(array('r' => 1, 'post' => $reply, 'm' => 'success')); die();
	}
	echo json_encode(array('r' => 0, 'm' => 'session not verify')); die();
}
function c4d_social_locker_t_auth() {
	$cb = c4d_social_locker_t_connect();
	session_start();
	if (! isset($_SESSION['oauth_token'])) {
	  // get the request token
	  $reply = $cb->oauth_requestToken([
	    'oauth_callback' => 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
	  ]);

	  // store the token
	  $cb->setToken($reply->oauth_token, $reply->oauth_token_secret);
	  $_SESSION['oauth_token'] = $reply->oauth_token;
	  $_SESSION['oauth_token_secret'] = $reply->oauth_token_secret;
	  $_SESSION['oauth_verify'] = true;

	  // redirect to auth website
	  $auth_url = $cb->oauth_authorize();
	  echo json_encode(array('r' => 0, 'url' => $auth_url, 'm' => 'need auth')); die();

	} else if (isset($_GET['oauth_verifier']) && isset($_SESSION['oauth_verify'])) {
	  // verify the token
	  $cb->setToken($_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
	  unset($_SESSION['oauth_verify']);

	  // get the access token
	  $reply = $cb->oauth_accessToken([
	    'oauth_verifier' => $_GET['oauth_verifier']
	  ]);

	  // store the token (which is different from the request token!)
	  $_SESSION['oauth_token'] = $reply->oauth_token;
	  $_SESSION['oauth_token_secret'] = $reply->oauth_token_secret;

	  // send to same URL, without oauth GET parameters
	  echo json_encode(array('r' => 1, 'm' => 'verify'));
	  echo '<script>window.opener.c4d_social_locker.twitter_auth = 1; window.close();</script>'; die();
	}
	
	echo json_encode(array('r' => 1, 'm' => 'verify')); die();
}

function c4d_social_locker_t_connect() {
	global $c4d_plugin_manager;
	require_once (dirname(__FILE__).'/libs/codebird.php');
	$key = isset($c4d_plugin_manager['section-social-locker-twitter-key']) ? $c4d_plugin_manager['section-social-locker-twitter-key'] : '';
	$secret = isset($c4d_plugin_manager['section-social-locker-twitter-secret']) ? $c4d_plugin_manager['section-social-locker-twitter-secret'] : '';
	\Codebird\Codebird::setConsumerKey($key, $secret);
	return \Codebird\Codebird::getInstance();
}

function c4d_social_locker_safely_add_stylesheet_to_frontsite() {
	global $c4d_plugin_manager;
	if(!defined('C4DPLUGINMANAGER_OFF_JS_CSS')) {
		wp_enqueue_style( 'c4d-social-locker-frontsite-style', C4DSL_PLUGIN_URI.'/assets/default.css' );
		wp_enqueue_script( 'c4d-social-locker-frontsite-plugin-js', C4DSL_PLUGIN_URI.'/assets/default.js', array( 'jquery' ), false, true ); 
		wp_localize_script( 'jquery', 'c4d_social_locker',
            array( 
            	'ajax_url' => admin_url( 'admin-ajax.php' ), 
            	'appid' => isset($c4d_plugin_manager['section-social-locker-facebook-id']) ? $c4d_plugin_manager['section-social-locker-facebook-id'] : '' 
            ) );
	}
}

function c4d_social_locker($params, $content) {
	global $c4d_plugin_manager;
	$default = array(
		'title' => __('This content is locked', 'c4d-social-locker'),
		'desc' => __('Please support us, use one of the buttons below to unlock the content.', 'c4d-social-locker'),
		'url' => get_permalink(),
		'tweet_text' => get_the_title()
	);

	$params = is_array($params) ? $params : array();
	$params = array_merge($default, $params);

	$id = 'c4dsl'.uniqid(time());
	$html = '<div id="'.$id.'" class="c4d-social-locker">';
	$html .= '<div class="c4d-social-locker-message">
				<h3>'.$params['title'].'</h3>
				<div class="desc">'.$params['desc'].'</div>
			</div>';
	$html .= '<div class="c4d-social-locker-content">'.$content.'</div>';
	$html .= '<div class="c4d-social-locker-share">';

	$html .= '<div class="button twitter">';
	$html .= '<a class="c4d-social-locker-twitter-button"
			  href="https://twitter.com/share"
			  data-id="'.$id.'"
			  data-text="'.esc_html__($params['tweet_text']).'"
			  data-url="'.esc_url($params['url']).'"
			  >
			Tweet
			</a>';
	$html .= '</div>';

	$html .= '<div class="button facebook">';
	$html .= '<a class="c4d-social-locker-fb-button" 
				href="#"
				data-id="'.(isset($c4d_plugin_manager['section-social-locker-facebook-id']) ? $c4d_plugin_manager['section-social-locker-facebook-id'] : '' ).'"
			    data-text="'.esc_html__($params['tweet_text']).'"
			  	data-url="'.esc_url($params['url']).'"
			  >Share</a>';
	$html .= '</div>';

	$html .= '</div>';
	$html .= '</div>';
	
	return $html;
}
function c4d_social_locker_section_options(){
    $opt_name = 'c4d_plugin_manager';
    Redux::setSection( $opt_name, array(
        'title'            => esc_html__( 'Social Locker', 'c4d-social-locker' ),
        'id'               => 'section-social-locker',
        'desc'             => '',
        'customizer_width' => '400px',
    ));

    Redux::setSection( $opt_name, array(
        'title'            => esc_html__( 'Facebook', 'c4d-social-locker' ),
        'id'               => 'section-social-locker-facebook',
        'desc'             => '',
        'customizer_width' => '400px',
        'subsection' 	   => true,
        'fields'           => array(
            array(
                'id'       => 'section-social-locker-facebook-id',
                'type'     => 'text',
                'title'    => esc_html__( 'App Id', 'c4d-social-locker' )
            )
        )
    ));
    Redux::setSection( $opt_name, array(
        'title'            => esc_html__( 'Twitter', 'c4d-social-locker' ),
        'id'               => 'section-social-locker-twitter',
        'desc'             => '',
        'customizer_width' => '400px',
        'subsection' 	   => true,
        'fields'           => array(
            array(
                'id'       => 'section-social-locker-twitter-key',
                'type'     => 'text',
                'title'    => esc_html__( 'Key', 'c4d-social-locker' )
            ),
            array(
                'id'       => 'section-social-locker-twitter-secret',
                'type'     => 'text',
                'title'    => esc_html__( 'Secret', 'c4d-social-locker' )
            )
        )
    ));
}