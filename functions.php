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
		add_action( 'wp_enqueue_scripts', array( 'Raquel_M_Smith', 'rms_enqueue_styles' ) );
		add_action('save_post', array( 'Raquel_M_Smith', 'action_save_post_trigger_netlify_deploy' ), 10, 3 );
	}

	/**
	 * Registery of filters for the theme.
	 */
	private function setup_filters() {
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
		$response = wp_remote_post( 'https://api.netlify.com/build_hooks/5c9e8056b1c202018ab47de4' );
		if ( is_wp_error( $response ) ) {
			wp_mail( 'hello@raquelmsmith.com', 'Saving post failed to deploy', 'Saving post ' . $post_id . ' failed to trigger a Netlify deploy. Try again!' );
		}
	}
}

add_action( 'after_setup_theme', array( 'Raquel_M_Smith', 'get_instance' ) );

