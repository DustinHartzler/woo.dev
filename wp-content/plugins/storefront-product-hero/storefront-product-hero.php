<?php
/**
 * Plugin Name:			Storefront Product Hero
 * Plugin URI:			http://woothemes.com/products/storefront-product-hero/
 * Description:			Display styling parallax product hero components on your web pages.
 * Version:				1.2.4
 * Author:				WooThemes
 * Author URI:			http://woothemes.com/
 * Requires at least:	4.0.0
 * Tested up to:		4.0.0
 *
 * Text Domain: storefront-product-hero
 * Domain Path: /languages/
 *
 * @package Storefront_Product_Hero
 * @category Core
 * @author James Koster
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( 'woo-includes/woo-functions.php' );
}

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '5547d5586682cf1f0926f9a5b2ec4e2c', '622338' );

/**
 * Returns the main instance of Storefront_Product_Hero to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Storefront_Product_Hero
 */
function Storefront_Product_Hero() {
	return Storefront_Product_Hero::instance();
} // End Storefront_Product_Hero()

Storefront_Product_Hero();

/**
 * Main Storefront_Product_Hero Class
 *
 * @class Storefront_Product_Hero
 * @version	1.0.0
 * @since 1.0.0
 * @package	Storefront_Product_Hero
 */
final class Storefront_Product_Hero {
	/**
	 * Storefront_Product_Hero The single instance of Storefront_Product_Hero.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $token;

	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $version;

	// Admin - Start
	/**
	 * The admin object.
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $admin;

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function __construct() {
		$this->token 			= 'storefront-product-hero';
		$this->plugin_url 		= plugin_dir_url( __FILE__ );
		$this->plugin_path 		= plugin_dir_path( __FILE__ );
		$this->version 			= '1.2.4';

		register_activation_hook( __FILE__, array( $this, 'install' ) );

		add_action( 'init', array( $this, 'sprh_load_plugin_textdomain' ) );

		add_action( 'init', array( $this, 'sprh_setup' ) );

		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'sprh_plugin_links' ) );
	}

	/**
	 * Main Storefront_Product_Hero Instance
	 *
	 * Ensures only one instance of Storefront_Product_Hero is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Storefront_Product_Hero()
	 * @return Main Storefront_Product_Hero instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	} // End instance()

	/**
	 * Load the localisation file.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function sprh_load_plugin_textdomain() {
		load_plugin_textdomain( 'storefront-product-hero', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0.0' );
	}

	/**
	 * Plugin page links
	 *
	 * @since  1.0.0
	 */
	public function sprh_plugin_links( $links ) {
		$plugin_links = array(
			'<a href="http://support.woothemes.com/">' . __( 'Support', 'storefront-product-hero' ) . '</a>',
			'<a href="http://docs.woothemes.com/document/storefront-product-hero/">' . __( 'Docs', 'storefront-product-hero' ) . '</a>',
		);

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Installation.
	 * Runs on activation. Logs the version number and assigns a notice message to a WordPress option.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function install() {
		$this->_log_version_number();

		// get theme customizer url
		$url = admin_url() . 'customize.php?';
		$url .= 'url=' . urlencode( site_url() . '?storefront-customizer=true' ) ;
		$url .= '&return=' . urlencode( admin_url() . 'plugins.php' );
		$url .= '&storefront-customizer=true';

		$notices 		= get_option( 'sprh_activation_notice', array() );
		$notices[]		= sprintf( __( '%sThanks for installing the Storefront Product Hero extension. To get started, visit the %sCustomizer%s.%s %sOpen the Customizer%s', 'storefront-woocommerce-customiser' ), '<p>', '<a href="' . $url . '">', '</a>', '</p>', '<p><a href="' . $url . '" class="button button-primary">', '</a></p>' );

		update_option( 'sprh_activation_notice', $notices );
	}

	/**
	 * Log the plugin version number.
	 * @access  private
	 * @since   1.0.0
	 * @return  void
	 */
	private function _log_version_number() {
		// Log the version number.
		update_option( $this->token . '-version', $this->version );
	}

	/**
	 * Setup all the things.
	 * Only executes if Storefront or a child theme using Storefront as a parent is active and the extension specific filter returns true.
	 * Child themes can disable this extension using the Storefront_Product_Hero_enabled filter
	 * @return void
	 */
	public function sprh_setup() {
		$theme = wp_get_theme();

		if ( 'Storefront' == $theme->name || 'storefront' == $theme->template && apply_filters( 'storefront_product_hero_enabled', true ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'sprh_styles' ), 999 );
			add_action( 'wp_enqueue_scripts', array( $this, 'sprh_scripts' ), 999 );
			add_action( 'customize_register', array( $this, 'sprh_customize_register' ) );
			add_action( 'customize_preview_init', array( $this, 'sprh_customize_preview_js' ) );
			add_action( 'homepage', array( $this, 'homepage_product_hero' ), 5 );
			add_filter( 'body_class', array( $this, 'sprh_body_class' ) );
			add_action( 'admin_notices', array( $this, 'sprh_customizer_notice' ) );

			// Hide the 'More' section in the customizer
			add_filter( 'storefront_customizer_more', '__return_false' );
		}
	}

	/**
	 * Admin notice
	 * Checks the notice setup in install(). If it exists display it then delete the option so it's not displayed again.
	 * @since   1.0.0
	 * @return  void
	 */
	public function sprh_customizer_notice() {
		$notices = get_option( 'sprh_activation_notice' );

		if ( $notices = get_option( 'sprh_activation_notice' ) ) {

			foreach ( $notices as $notice ) {
				echo '<div class="updated">' . $notice . '</div>';
			}

			delete_option( 'sprh_activation_notice' );
		}
	}

	/**
	 * Customizer Controls and settings
	 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
	 */
	public function sprh_customize_register( $wp_customize ) {

		/**
		 * Custom controls
		 * Load custom control classes
		 */
		require_once dirname( __FILE__ ) . '/includes/class-control-content-layout.php';
		require_once dirname( __FILE__ ) . '/includes/class-control-products.php';

		/**
		 * Add the panel
		 */
		$wp_customize->add_panel( 'sprh_panel', array(
		    'priority'       	=> 60,
		    'capability'     	=> 'edit_theme_options',
		    'theme_supports' 	=> '',
		    'title'				=> __( 'Product Hero', 'storefront-product-hero' ),
		    'description'    	=> __( 'Customise the appearance and content of the hero component that is displayed on your homepage.', 'storefront-product-hero' ),
		    'active_callback'	=> array( $this, 'storefront_homepage_template_callback' ),
		) );

		/**
	     * Add the sections
	     */
	    $wp_customize->add_section( 'sprh_section_content' , array(
		    'title'		=> __( 'Content', 'storefront-product-hero' ),
		    'priority'	=> 10,
		    'panel'		=> 'sprh_panel',
		) );

		$wp_customize->add_section( 'sprh_section_background' , array(
		    'title'		=> __( 'Background', 'storefront-product-hero' ),
		    'priority'	=> 20,
		    'panel'		=> 'sprh_panel',
		) );

		$wp_customize->add_section( 'sprh_section_layout' , array(
		    'title'		=> __( 'Layout', 'storefront-product-hero' ),
		    'priority'	=> 30,
		    'panel'		=> 'sprh_panel',
		) );

		/**
		 * Product selector
		 * See class-control-products.php
		 */
		$wp_customize->add_setting( 'sprh_featured_product', array(
			'default'    		=> 'default',
			'sanitize_callback'	=> 'absint',
		) );

		$wp_customize->add_control( new Products_Storefront_Control( $wp_customize, 'sprh_featured_product', array(
			'label'    		=> __( 'Featured product', 'storefront-product-hero' ),
			'description'   => __( 'Select a product to be featured in the product hero', 'storefront-product-hero' ),
			'section'		=> 'sprh_section_content',
			'settings'		=> 'sprh_featured_product',
			'priority'		=> 10,
		) ) );

		/**
		 * Content layout
		 * See class-control-content-layout.php
		 */
		$wp_customize->add_setting( 'sprh_alignment', array(
			'default'    		=> 'left',
			'sanitize_callback'	=> 'esc_attr'
		) );

		$wp_customize->add_control( new Product_Hero_Layout_Control( $wp_customize, 'sprh_alignment', array(
			'label'    => __( 'Content layout', 'storefront-product-hero' ),
			'section'  => 'sprh_section_layout',
			'settings' => 'sprh_alignment',
			'priority' => 20,
		) ) );

		/**
	     * Layout
	     */
	    $wp_customize->add_setting( 'sprh_layout', array(
            'default'			=> 'full',
            'sanitize_callback'	=> 'storefront_sanitize_choices',
        ) );
        $wp_customize->add_control( 'sprh_layout', array(
				'label'		=> __( 'Hero width', 'storefront-product-hero' ),
				'section'	=> 'sprh_section_layout',
				'settings'	=> 'sprh_layout',
				'type'		=> 'select',
				'priority'	=> 30,
				'choices'	=> array(
					'full'	=> 'Full width',
					'fixed'	=> 'Fixed width',
				),
			)
		);

		/**
	     * Full height
	     */
	    $wp_customize->add_setting( 'sprh_hero_full_height', array(
	        'default'			=> false,
	        'sanitize_callback'	=> 'absint',
	    ) );

	    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'sprh_hero_full_height', array(
            'label'			=> __( 'Full height', 'storefront-product-hero' ),
            'description'	=> __( 'Set the hero component to full height', 'storefront-product-hero' ),
            'section'		=> 'sprh_section_layout',
            'settings'		=> 'sprh_hero_full_height',
            'type'			=> 'checkbox',
            'priority'		=> 35,
        ) ) );

		/**
	     * Product image
	     */
	    $wp_customize->add_setting( 'sprh_product_image', array(
	        'default'			=> true,
	        'sanitize_callback'	=> 'absint',
	    ) );

	    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'sprh_product_image', array(
            'label'			=> __( 'Product image', 'storefront-product-hero' ),
            'description'	=> __( 'Display the product featured image', 'storefront-product-hero' ),
            'section'		=> 'sprh_section_content',
            'settings'		=> 'sprh_product_image',
            'type'			=> 'checkbox',
            'priority'		=> 40,
        ) ) );

        /**
	     * Product price
	     */
	    $wp_customize->add_setting( 'sprh_product_price', array(
	        'default'			=> true,
	        'sanitize_callback'	=> 'absint',
	    ) );

	    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'sprh_product_price', array(
            'label'			=> __( 'Product price', 'storefront-product-hero' ),
            'description'	=> __( 'Display the product price', 'storefront-product-hero' ),
            'section'		=> 'sprh_section_content',
            'settings'		=> 'sprh_product_price',
            'type'			=> 'checkbox',
            'priority'		=> 50,
        ) ) );

        /**
	     * Product rating
	     */
	    $wp_customize->add_setting( 'sprh_product_rating', array(
	        'default'			=> true,
	        'sanitize_callback'	=> 'absint',
	    ) );

	    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'sprh_product_rating', array(
            'label'			=> __( 'Product rating', 'storefront-product-hero' ),
            'description'	=> __( 'Display the product rating', 'storefront-product-hero' ),
            'section'		=> 'sprh_section_content',
            'settings'		=> 'sprh_product_rating',
            'type'			=> 'checkbox',
            'priority'		=> 60,
        ) ) );

		if ( class_exists( 'Arbitrary_Storefront_Control' ) ) {
			$wp_customize->add_control( new Arbitrary_Storefront_Control( $wp_customize, 'storefront_product_hero_divider_second', array(
				'section'  	=> 'sprh_section_content',
				'type' 		=> 'divider',
				'priority' 	=> 80,
			) ) );
		}

		/**
		 * Background Color
		 */
		$wp_customize->add_setting( 'sprh_background_color', array(
	        'default'			=> apply_filters( 'storefront_default_header_background_color', '#2c2d33' ),
	        'sanitize_callback'	=> 'sanitize_hex_color',
	        'transport'			=> 'postMessage',
	    ) );

	    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'sprh_background_color', array(
	        'label'		=> 'Background color',
	        'section'	=> 'sprh_section_background',
	        'settings'	=> 'sprh_background_color',
	        'priority'	=> 25,
	    ) ) );

	    /**
	     * Overlay color
	     */
		$wp_customize->add_setting( 'sprh_overlay_color', array(
	        'default'				=> apply_filters( 'storefront_product_hero_default_overlay_color', '#000000' ),
	        'description'			=> __( 'Specify the overlay background color', 'storefront-product-hero' ),
	        'sanitize_callback'		=> 'sanitize_hex_color',
	    ) );

	    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'sprh_overlay_color', array(
	        'label'		=> 'Overlay color',
	        'section'	=> 'sprh_section_background',
	        'settings'	=> 'sprh_overlay_color',
	        'priority'	=> 100,
	    ) ) );

	    /**
	     * Overlay opacity
	     */
        $wp_customize->add_setting( 'sprh_overlay_opacity', array(
            'default'			=> '0.5',
            'sanitize_callback'	=> 'storefront_sanitize_choices',
        ) );
        $wp_customize->add_control( 'sprh_overlay_opacity', array(
				'label'			=> __( 'Overlay opacity', 'storefront-product-hero' ),
				'section'		=> 'sprh_section_background',
				'settings'		=> 'sprh_overlay_opacity',
				'type'			=> 'select',
				'priority'		=> 110,
				'choices'		=> array(
					'0'			=> '0%',
					'0.1'		=> '10%',
					'0.2'		=> '20%',
					'0.3'		=> '30%',
					'0.4'		=> '40%',
					'0.5'		=> '50%',
					'0.6'		=> '60%',
					'0.7'		=> '70%',
					'0.8'		=> '80%',
					'0.9'		=> '90%',
				),
			)
		);

        /**
	     * Background
	     */
	    $wp_customize->add_setting( 'sprh_hero_background_img', array(
	        'default'			=> '',
	        'sanitize_callback'	=> 'sanitize_text_field',
	    ) );

	    $wp_customize->add_control( new WP_Customize_Upload_Control( $wp_customize, 'sprh_hero_background_img', array(
            'label'			=> __( 'Background image', 'storefront-product-hero' ),
	        'section'		=> 'sprh_section_background',
	        'settings'		=> 'sprh_hero_background_img',
	        'priority'		=> 10,
	    ) ) );

	    /**
	     * Background size
	     */
        $wp_customize->add_setting( 'sprh_background_size', array(
            'default'			=> 'auto',
            'sanitize_callback'	=> 'storefront_sanitize_choices',
        ) );
        $wp_customize->add_control( 'sprh_background_size', array(
				'label'			=> __( 'Background size', 'storefront-product-hero' ),
				'section'		=> 'sprh_section_background',
				'settings'		=> 'sprh_background_size',
				'type'			=> 'select',
				'priority'		=> 20,
				'choices'		=> array(
					'auto'			=> 'Default',
					'cover'			=> 'Cover',
				),
			)
		);

		if ( class_exists( 'Arbitrary_Storefront_Control' ) ) {
	        $wp_customize->add_control( new Arbitrary_Storefront_Control( $wp_customize, 'storefront_product_hero_divider', array(
				'section'  	=> 'sprh_section_background',
				'type'		=> 'divider',
				'priority' 	=> 25,
			) ) );
	    }

	    /**
	     * Parallax
	     */
	    $wp_customize->add_setting( 'sprh_hero_parallax', array(
	        'default'			=> true,
	        'sanitize_callback'	=> 'absint',
	    ) );

	    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'sprh_hero_parallax', array(
            'label'			=> __( 'Parallax', 'storefront-product-hero' ),
            'description'	=> __( 'Enable the parallax scrolling effect', 'storefront-product-hero' ),
            'section'		=> 'sprh_section_background',
            'settings'		=> 'sprh_hero_parallax',
            'type'			=> 'checkbox',
            'priority'		=> 30,
        ) ) );

        /**
	     * Parallax scroll speed
	     */
        $wp_customize->add_setting( 'sprh_parallax_scroll_ratio', array(
            'default'			=> '0.5',
            'sanitize_callback'	=> 'storefront_sanitize_choices',
        ) );
        $wp_customize->add_control( 'sprh_parallax_scroll_ratio', array(
				'label'			=> __( 'Parallax scroll speed', 'storefront-product-hero' ),
				'description'	=> __( 'The speed at which the parallax background scrolls relative to the window', 'storefront-product-hero' ),
				'section'		=> 'sprh_section_background',
				'settings'		=> 'sprh_parallax_scroll_ratio',
				'type'			=> 'select',
				'priority'		=> 40,
				'choices'		=> array(
					'0.25'			=> '25%',
					'0.5'			=> '50%',
					'0.75'			=> '75%',
				),
			)
		);

		/**
         * Parallax Offset
         */
        $wp_customize->add_setting( 'sprh_parallax_offset', array(
            'default'			=> 0,
            'sanitize_callback'	=> 'esc_attr',
        ) );
		$wp_customize->add_control( 'sprh_parallax_offset', array(
		    'type'        => 'range',
		    'priority'    => 42,
		    'section'     => 'sprh_section_background',
		    'label'			=> __( 'Parallax offset', 'storefront-product-hero' ),
			'description'	=> __( 'Offset the starting position of your background image', 'storefront-product-hero' ),
		    'input_attrs' => array(
		        'min'   => -500,
		        'max'   => 500,
		        'step'  => 1,
		    ),
		) );

		if ( class_exists( 'Arbitrary_Storefront_Control' ) ) {
	        $wp_customize->add_control( new Arbitrary_Storefront_Control( $wp_customize, 'storefront_product_hero_divider_two', array(
				'section'  	=> 'sprh_section_background',
				'type'		=> 'divider',
				'priority' 	=> 45,
			) ) );
	    }

        /**
		 * Heading Text
		 */
	    $wp_customize->add_setting( 'sprh_hero_heading_text', array(
	        'default'			=> '',
	        'sanitize_callback'	=> 'sanitize_text_field',
	    ) );

	    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'sprh_hero_heading_text', array(
            'label'			=> __( 'Heading text', 'storefront-product-hero' ),
            'description'	=> __( '(Leave blank to display product title)', 'storefront-product-hero' ),
            'section'		=> 'sprh_section_content',
            'settings'		=> 'sprh_hero_heading_text',
            'type'			=> 'text',
            'priority'		=> 170,
        ) ) );

        /**
	     * Heading text color
	     */
	    $wp_customize->add_setting( 'sprh_heading_color', array(
	        'default'			=> apply_filters( 'storefront_default_header_link_color', '#ffffff' ),
	        'sanitize_callback'	=> 'sanitize_hex_color',
	        'transport'			=> 'postMessage',
	    ) );

	    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'sprh_heading_color', array(
	        'label'		=> 'Heading text color',
	        'section'	=> 'sprh_section_content',
	        'settings'	=> 'sprh_heading_color',
	        'priority'	=> 180,
	    ) ) );

        /**
		 * Text
		 */
	    $wp_customize->add_setting( 'sprh_hero_text', array(
	        'default'			=> '',
	        'sanitize_callback'	=> 'wp_kses_post'
	    ) );

	    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'sprh_hero_text', array(
            'label'			=> __( 'Description text', 'storefront-product-hero' ),
            'description'	=> __( '(Leave blank to display product description)', 'storefront-product-hero' ),
            'section'		=> 'sprh_section_content',
            'settings'		=> 'sprh_hero_text',
            'type'			=> 'textarea',
            'priority'		=> 190,
        ) ) );

        /**
	     * Text color
	     */
	    $wp_customize->add_setting( 'sprh_hero_text_color', array(
	        'default'			=> apply_filters( 'storefront_default_header_text_color', '#5a6567' ),
	        'sanitize_callback'	=> 'sanitize_hex_color',
	        'transport'			=> 'postMessage',
	    ) );

	    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'sprh_hero_text_color', array(
	        'label'		=> 'Description text color',
	        'section'	=> 'sprh_section_content',
	        'settings'	=> 'sprh_hero_text_color',
	        'priority'	=> 200,
	    ) ) );

	    /**
	     * Link color
	     */
	    $wp_customize->add_setting( 'sprh_hero_link_color', array(
	        'default'			=> apply_filters( 'storefront_default_accent_color', '#96588a' ),
	        'sanitize_callback'	=> 'sanitize_hex_color',
	    ) );

	    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'sprh_hero_link_color', array(
	        'label'		=> 'Link color',
	        'section'	=> 'sprh_section_content',
	        'settings'	=> 'sprh_hero_link_color',
	        'priority'	=> 205,
	    ) ) );

	}

	/**
	 * Enqueue CSS and custom styles.
	 * @since   1.0.0
	 * @return  void
	 */
	public function sprh_styles() {
		wp_enqueue_style( 'sprh-styles', plugins_url( '/assets/css/style.css', __FILE__ ) );

		$link_color 	= get_theme_mod( 'sprh_hero_link_color', apply_filters( 'storefront_default_accent_color', '#96588a' ) );

		$sph_style = '
		.sprh-hero a:not(.button) {
			color: ' . $link_color . ';
		}';

		wp_add_inline_style( 'sprh-styles', $sph_style );
	}

	/**
	 * Enqueue scripts.
	 * @since   1.0.0
	 * @return  void
	 */
	public function sprh_scripts() {
		wp_register_script( 'sprh-script', plugins_url( '/assets/js/general.js', __FILE__ ), array( 'jquery' ) );
		wp_register_script( 'sprh-full-height', plugins_url( '/assets/js/full-height.js', __FILE__ ), array( 'jquery' ) );
		wp_register_script( 'sprh-stellar', plugins_url( '/assets/js/jquery.stellar.min.js', __FILE__ ), array( 'jquery' ), '0.6.2' );
	}

	/**
	 * Binds JS handlers to make Theme Customizer preview reload changes asynchronously.
	 *
	 * @since  1.0.0
	 */
	public function sprh_customize_preview_js() {
		wp_enqueue_script( 'sprh-customizer', plugins_url( '/assets/js/customizer.min.js', __FILE__ ), array( 'customize-preview' ), '1.0', true );
	}

	/**
	 * Storefront Product Hero Body Class
	 * Adds a class based on the extension name and any relevant settings.
	 */
	public function sprh_body_class( $classes ) {
		$classes[] = 'storefront-product-hero-active';

		return $classes;
	}

	/**
	 * Display the hero section
	 * @see get_theme_mod()
	 */
	public static function display_product_hero( $atts ) {

		$atts = extract( shortcode_atts( array(
			'heading_text' 				=> sanitize_text_field( get_theme_mod( 'sprh_hero_heading_text', '' ) ),
			'heading_text_color' 		=> get_theme_mod( 'sprh_heading_color', apply_filters( 'storefront_default_header_link_color', '#ffffff' ) ),
			'description_text' 			=> get_theme_mod( 'sprh_hero_text', '' ),
			'description_text_color' 	=> get_theme_mod( 'sprh_hero_text_color', apply_filters( 'storefront_default_header_text_color', '#5a6567' ) ),
			'background_img' 			=> sanitize_text_field( get_theme_mod( 'sprh_hero_background_img', '' ) ),
			'background_color'			=> sanitize_text_field( get_theme_mod( 'sprh_background_color', apply_filters( 'storefront_default_header_background_color', '#2c2d33' ) ) ),
			'background_size' 			=> get_theme_mod( 'sprh_background_size', 'auto' ),
			'layout' 					=> get_theme_mod( 'sprh_alignment', 'left' ),
			'width' 					=> 'fixed',
			'parallax' 					=> get_theme_mod( 'sprh_hero_parallax', true ),
			'parallax_scroll' 			=> get_theme_mod( 'sprh_parallax_scroll_ratio', '0.5' ),
			'parallax_offset' 			=> get_theme_mod( 'sprh_parallax_offset', 0 ),
			'overlay_color' 			=> get_theme_mod( 'sprh_overlay_color', apply_filters( 'storefront_product_hero_default_overlay_color', '#000000' ) ),
			'overlay_opacity' 			=> get_theme_mod( 'sprh_overlay_opacity', '0.5' ),
			'full_height' 				=> get_theme_mod( 'sprh_hero_full_height', false ),
			'style'						=> '',
			'overlay_style'				=> '',
			'product_id'				=> get_theme_mod( 'sprh_featured_product', 'default' ),
			'product_image'				=> get_theme_mod( 'sprh_product_image', true ),
			'product_price'				=> get_theme_mod( 'sprh_product_price', true ),
			'product_rating'			=> get_theme_mod( 'sprh_product_rating', true ),
		), $atts, 'product_hero' ) );

		// Get RGB color of overlay from HEX
		list( $r, $g, $b ) 			= sscanf( $overlay_color, "#%02x%02x%02x" );

		$stellar = '';

		if ( true == $parallax ) {
			wp_enqueue_script( 'sprh-script' );
			wp_enqueue_script( 'sprh-stellar' );

			$stellar = 'data-stellar-background-ratio="' . $parallax_scroll . '"';
		}

		$full_height_class 			= '';

		if ( true == $full_height ) {
			$full_height_class 		= 'sprh-full-height';
			wp_enqueue_script( 'sprh-full-height' );
		}

		$product_data = new WC_Product( $product_id );

		// Display the product hero only when a product has been set.
		if ( 'default' != $product_id ) {
		?>
		<section data-stellar-vertical-offset="<?php echo intval( $parallax_offset ); ?>" <?php echo $stellar; ?> class="sprh-hero <?php echo 'sprh-layout-' . $layout . ' ' . $width . ' ' . $full_height_class; ?>" style="<?php echo $style; ?>background-image: url(<?php echo $background_img; ?>); background-color: <?php echo $background_color; ?>; color: <?php echo $description_text_color; ?>; background-size: <?php echo $background_size; ?>;">
			<div class="overlay" style="background-color: rgba(<?php echo $r . ', ' . $g . ', ' . $b . ', ' . $overlay_opacity; ?>);<?php echo $overlay_style; ?>">
				<div class="col-full">

					<?php do_action( 'sprh_content_before' ); ?>

					<div class="sprh-featured-image">
						<?php
							if ( true == $product_image ) {
								echo '<a href="' . get_permalink( $product_id ) . '">' . get_the_post_thumbnail( $product_id, 'shop_single' ) . '</a>';
							}
						?>
					</div>

					<div class="sprh-hero-content-wrapper">

						<h1 style="color: <?php echo $heading_text_color; ?>;">
							<?php
								if ( '' != $heading_text ) {
									echo $heading_text;
								} else {
									echo $product_data->get_title();
								}
							?>
						</h1>

						<div class="sprh-hero-content">
							<?php
								if ( true == $product_price ) {
									echo '<p class="price">' . $product_data->get_price_html() . '</p>';
								}

								if ( true == $product_rating ) {
									echo $product_data->get_rating_html();
								}

								if ( '' != $description_text ) {
									echo wpautop( wp_kses_post( $description_text ) );
								} else {
									echo wpautop( get_post_field( 'post_content', $product_id ) );
								}
							?>

							<p class="more-details">
								<a href="<?php echo get_permalink( $product_id ); ?>" class="button"><?php _e( 'More details &rarr;', 'storefront-product-hero' ); ?></a>
							</p>

							<?php echo do_shortcode( '[add_to_cart id="' . $product_id . '"]' ); ?>
						</div>

					</div>

					<?php do_action( 'sprh_content_after' ); ?>
				</div>
			</div>
		</section>
		<?php
		}
	}

	/**
	 * Display the hero section via shortcode
	 * @see display_product_hero()
	 */
	public static function display_product_hero_shortcode( $atts ) {
		$hero = new Storefront_Product_Hero();

		ob_start();
		$hero->display_product_hero( $atts );
		return ob_get_clean();
	}

	/**
	 * Display the hero section via homepage action
	 * @see display_product_hero()
	 */
	public static function homepage_product_hero( $atts ) {

		// Default just for homepage customizer one needs to be full, so set that there
		$atts = array( 'width' => get_theme_mod( 'sprh_layout', 'full' ) );

		Storefront_Product_Hero::display_product_hero( $atts );
	}

	/**
	 * Homepage callback
	 * @return bool
	 */
	public function storefront_homepage_template_callback() {
		return is_page_template( 'template-homepage.php' ) ? true : false;
	}


} // End Class

// Create a shortcode to display the hero
add_shortcode( 'product_hero', array( 'Storefront_Product_Hero', 'display_product_hero_shortcode' ) );
