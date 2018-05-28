<?php
/*
Plugin Name: WooCommerce SAPO International Parcel Service
Plugin URI: http://woocommerce.com/products/sapo-international-parcel-service/
Description: South African Post Office International Parcel Service shipping for WooCommerce.
Version: 1.2.0
Author: WooCommerce
Author URI: https://woocommerce.com/
Requires at least: 3.8
Tested up to: 4.6.1
	Copyright: © 2016 WooCommerce.
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( dirname( __FILE__ ) . '/woo-includes/woo-functions.php' );
}

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '53d0da05fa06e22837b2fccebfb4e692', '18694' );

/**
 * WC_Shipping_SAPO_IPS
 * South African Post international plugin main class.
 */
if ( ! class_exists( 'WC_Shipping_SAPO_IPS' ) ) {
	class WC_Shipping_SAPO_IPS {

		/**
		 * Plugin's version.
		 *
		 * @since 1.2.0
		 *
		 * @var string
		 */
		public $version = '1.2.0';

		public function __construct() {
			add_action( 'plugins_loaded', array( $this, 'init' ) );
		}

		/**
		 * Initialise the shipping module
		 *
		 */
		public function init() {

			if ( ! class_exists( 'WC_Shipping_Method' ) ) {
				return;
			}

			$this->load_plugin_text_domain();

			if ( version_compare( WC_VERSION, '2.6.0', '<' ) ) {
				include_once( dirname( __FILE__ ) . '/includes/class-sapo-ips-deprecated.php' );
			} else {
				include_once( dirname( __FILE__ ) . '/includes/class-sapo-ips.php' );
			}

			add_filter( 'woocommerce_shipping_methods', array( $this, 'add_shipping_method' ) );
			add_action( 'admin_init', array( $this, 'maybe_install' ), 5 );
			add_action( 'admin_notices', array( $this, 'upgrade_notice' ) );
			add_action( 'wp_ajax_sapo_ips_dismiss_upgrade_notice', array( $this, 'dismiss_upgrade_notice' ) );
		}

		/**
		 * Localisation
		 */
		public function load_plugin_text_domain() {
			$locale = apply_filters( 'plugin_locale', get_locale(), 'woocommerce-shipping-sapo-ips' );
			$dir    = trailingslashit( WP_LANG_DIR );

			load_textdomain( 'woocommerce-shipping-sapo-ips', $dir . 'woocommerce-shipping-sapo-ips/woocommerce-shipping-sapo-ips-' . $locale . '.mo' );
			load_plugin_textdomain( 'woocommerce-shipping-sapo-ips', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

		/**
		 * Add the shipping module to WooCommerce
		 *
		 * @param array $methods
		 * @return array $methods
		 */
		public function add_shipping_method( $methods ) {
			if ( version_compare( WC_VERSION, '2.6.0', '<' ) ) {
				$methods[] = 'WC_SAPO_IPS';
			} else {
				$methods['sapo_ips'] = 'WC_SAPO_IPS';
			}

			return $methods;
		}

		/**
		 * Checks the plugin version before installing.
		 *
		 * @access public
		 * @since 1.2.0
		 * @version 1.2.0
		 * @return bool
		 */
		public function maybe_install() {
			// only need to do this for versions less than 1.2.0 to migrate
			// settings to shipping zone instance
			$doing_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;
			if ( ! $doing_ajax
			     && ! defined( 'IFRAME_REQUEST' )
			     && version_compare( WC_VERSION, '2.6.0', '>=' )
			     && version_compare( get_option( 'wc_sapo_ips_version' ), '1.2.0', '<' ) ) {

				$this->install();

			}

			return true;
		}

		/**
		 * Update/migration script
		 *
		 * @since 1.2.0
		 */
		public function install() {
			// get all saved settings and cache it
			$sapo_ips_settings = get_option( 'woocommerce_sapo_ips_settings', false );

			// settings exists
			if ( $sapo_ips_settings ) {
				global $wpdb;

				// unset un-needed settings
				unset( $sapo_ips_settings['enabled'] );
				unset( $sapo_ips_settings['oer_app_id'] );

				// first add it to the "rest of the world" zone when no South African Post IPS
				// instance.
				if ( ! $this->zone_has_sapo_ips( 0 ) ) {
					$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->prefix}woocommerce_shipping_zone_methods ( zone_id, method_id, method_order, is_enabled ) VALUES ( %d, %s, %d, %d )", 0, 'sapo_ips', 1, 1 ) );
					// add settings to the newly created instance to options table
					$instance = $wpdb->insert_id;
					add_option( 'woocommerce_sapo_ips_' . $instance . '_settings', $sapo_ips_settings );
				}
				update_option( 'woocommerce_sapo_ips_show_upgrade_notice', 'yes' );
			}
			update_option( 'wc_sapo_ips_version', $this->version );
		}


		/**
		 * Show the user a notice for plugin updates
		 *
		 * @since 1.2.0
		 */
		public function upgrade_notice() {
			$show_notice = get_option( 'woocommerce_sapo_ips_show_upgrade_notice' );

			if ( 'yes' !== $show_notice ) {
				return;
			}

			$query_args = array( 'page' => 'wc-settings', 'tab' => 'shipping' );
			$zones_admin_url = add_query_arg( $query_args, get_admin_url() . 'admin.php' );
			?>
			<div class="notice notice-success is-dismissible wc-sapo-ips-notice">
				<p><?php echo sprintf( __( 'South African Post IPS now supports shipping zones. The zone settings were added to a new South African Post IPS method on the "Rest of the World" Zone. See the zones %shere%s ', 'woocommerce-shipping-sapo-ips' ),'<a href="' .$zones_admin_url. '">','</a>' ); ?></p>
			</div>

			<script type="application/javascript">
				jQuery( '.notice.wc-sapo-ips-notice' ).on( 'click', '.notice-dismiss', function () {
					wp.ajax.post('sapo_ips_dismiss_upgrade_notice');
				});
			</script>
			<?php
		}

		/**
		 * Turn of the dismisable upgrade notice.
		 * @since 1.2.0
		 */
		public function dismiss_upgrade_notice() {
			update_option( 'woocommerce_sapo_ips_show_upgrade_notice', 'no' );
		}

		/**
		 * Helper method to check whether given zone_id has sapo_ips method instance.
		 *
		 * @since 1.2.0
		 *
		 * @param int $zone_id Zone ID
		 *
		 * @return bool True if given zone_id has sapo_ips method instance
		 */
		public function zone_has_sapo_ips( $zone_id ) {
			global $wpdb;
			return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(instance_id) FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE method_id = 'sapo_ips' AND zone_id = %d", $zone_id ) ) > 0;
		}
	}
}

new WC_Shipping_SAPO_IPS();
