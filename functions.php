<?php

// Force https on production
if ( ! empty( $_SERVER['HTTP_HOST'] )
	&& 'raquelmsmith.com' === $_SERVER['HTTP_HOST']
	&& ! is_ssl() ) {
	wp_safe_redirect( 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], 301 );
	exit;
}

class Raquel_M_Smith {

	private static $instance;

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Raquel_M_Smith;
			self::$instance->require_files();
			self::$instance->setup_actions();
			self::$instance->setup_filters();
			self::$instance->configure();
		}
		return self::$instance;
	}

	/**
	 * Require any other class files that exist
	 */
	private function require_files() {
		spl_autoload_register( function( $class ) {
			$class = ltrim( $class, '\\' );
			if ( 0 !== stripos( $class, 'Raquel_M_Smith\\' ) ) {
				return;
			}

			$parts = explode( '\\', $class );
			array_shift( $parts );
			$last = array_pop( $parts ); // File should be 'class-[...].php'
			$last = 'class-' . $last . '.php';
			$parts[] = $last;
			$file = dirname( __FILE__ ) . '/inc/' . str_replace( '_', '-', strtolower( implode( $parts, '/' ) ) );
			if ( file_exists( $file ) ) {
				require $file;
			}

			// Might be a trait
			$file = str_replace( '/class-', '/trait-', $file );
			if ( file_exists( $file ) ) {
				require $file;
			}
		});
	}

	/**
	 * Registry of actions for the theme.
	 */
	private function setup_actions() {
		add_action( 'init', array( 'Raquel_M_Smith', 'action_init' ) );
		add_action('init', array( 'Raquel_M_Smith', 'redirect_to_backend' ) );
		add_action( 'wp_enqueue_scripts', array( 'Raquel_M_Smith', 'rms_enqueue_styles' ) );
		add_action('save_post', array( 'Raquel_M_Smith', 'action_save_post_trigger_netlify_deploy' ), 10, 3 );
		add_action( 'rest_api_init', array( 'Raquel_M_Smith', 'action_rest_api_init_cors' ), 15 );
	}

	/**
	 * Registery of filters for the theme.
	 */
	private function setup_filters() {
		add_filter('excerpt_more', array( 'Raquel_M_Smith', 'filter_excerpt_more' ) );
		add_filter( 'rest_allow_anonymous_comments', '__return_true' );
	}

	/**
	 * Configure aspects to the theme.
	 */
	private function configure() {
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'title-tag' );
	}

	/**
	 * Behaviors to perform on init
	 */
	public static function action_init() {
		remove_action( 'the_content_more_link', 'organic_origin_add_more_link_class', 10 );
		remove_filter( 'excerpt_more', 'organic_origin_excerpt_more' );
	}

	/**
	 * Enqueue necessary styles
	 */
	public static function rms_enqueue_styles() {
		$parent_style = 'organic-origin-style';
		wp_enqueue_style( $parent_style, get_template_directory_uri() . '/style.css' );
		$time = filemtime( get_stylesheet_directory() . '/assets/css/style.css' );
		wp_enqueue_style( 'rms-style',
			get_stylesheet_directory_uri() . '/assets/css/style.css',
			array( $parent_style ),
			$time
		);
	}

	public static function action_save_post_trigger_netlify_deploy( $post_id, $post, $update ) {
		if ( get_post_status ( $post_id ) == 'publish' ) {
			$response = wp_remote_post( 'https://api.netlify.com/build_hooks/5c9e8056b1c202018ab47de4' );
			if ( is_wp_error( $response ) ) {
				wp_mail( 'hello@raquelmsmith.com', 'Saving post failed to deploy', 'Saving post ' . $post_id . ' failed to trigger a Netlify deploy. Try again!' );
			}
		}
	}

	public static function filter_excerpt_more( $link ) {
		return ' ...';
	}

	public static function redirect_to_backend() {
		if ( !is_admin() && !self::is_wp_login() && !self::is_rest() && !is_user_logged_in() ) {
			wp_redirect( site_url( 'wp-admin' ) );
			exit();
		}
	}

    /**
     * Checks if the current request is a WP REST API request.
     * 
     * Case #1: After WP_REST_Request initialisation
     * Case #2: Support "plain" permalink settings
     * Case #3: URL Path begins with wp-json/ (your REST prefix)
     *          Also supports WP installations in subfolders
     * 
     * @returns boolean
     * @author matzeeable
     */
    public function is_rest() {
        $prefix = rest_get_url_prefix( );
        if (defined('REST_REQUEST') && REST_REQUEST // (#1)
            || isset($_GET['rest_route']) // (#2)
                && strpos( trim( $_GET['rest_route'], '\\/' ), $prefix , 0 ) === 0)
            return true;

        // (#3)
        $rest_url = wp_parse_url( site_url( $prefix ) );
        $current_url = wp_parse_url( add_query_arg( array( ) ) );
        return strpos( $current_url['path'], $rest_url['path'], 0 ) === 0;
	}
	
	public function is_wp_login(){
		$ABSPATH_MY = str_replace(array('\\','/'), DIRECTORY_SEPARATOR, ABSPATH);
		return ((in_array($ABSPATH_MY.'wp-login.php', get_included_files()) || in_array($ABSPATH_MY.'wp-register.php', get_included_files()) ) || (isset($_GLOBALS['pagenow']) && $GLOBALS['pagenow'] === 'wp-login.php') || $_SERVER['PHP_SELF']== '/wp-login.php');
	}

	public function action_rest_api_init_cors() {
		remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
		add_filter( 'rest_pre_serve_request', function( $value ) {
			header( 'Access-Control-Allow-Origin: https://raquelmsmith.com' );
			header( 'Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE' );
			header( 'Access-Control-Allow-Credentials: true' );
			return $value;
		});
	}
}

add_action( 'after_setup_theme', array( 'Raquel_M_Smith', 'get_instance' ) );

