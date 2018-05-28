<?php
/**
 * South African Postal Service International Parcel Service
 *
 * Provides SAPO International Parcel Service shipping to WooCommerce.
 *
 * @class 		WC_SAPO_IPS
 * @package		WooCommerce
 * @category	Shipping Module
 * @author		Gerhard Potgieter
 *
 **/

class WC_SAPO_IPS extends WC_Shipping_Method  {

	protected $international_rates;
	protected $zones;
	protected $tracking_rate = 25.50;
	protected $small_packets_registration_rate = 26.60;
	protected $small_packets;
	protected $tracking;
	protected $shipping_types;
	protected $convert_currency;
	protected $cc_key = 'e65018798d4a4585a8e2c41359cc7f3c';

	/**
	 * @param int $instance_id
	 */
	public function __construct( $instance_id = 0 ) {

		$this->id           = 'sapo_ips';
		$this->instance_id  = absint( $instance_id );

		$this->method_title = __( 'SAPO International Parcel Service', 'woocommerce-shipping-sapo-ips' );

		$this->supports     = array(
			'shipping-zones',
			'instance-settings',
			'settings',
		);

		$this->init();
	}

	/**
	 * Init function.
	 *
	 * @access private
	 * @return void
	 */
	private function init() {
		$this->_init_rates();
		$this->_init_zones();

		// Load the settings.
		$this->init_form_fields();
		$this->set_settings();

		// Only shipping from ZA and ZAR currency supported
		add_action( 'admin_notices', array( $this, 'check_currency' ) );

		// Save settings
		add_action( 'woocommerce_update_options_shipping_sapo_ips', array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_shipping_methods', array( $this, 'process_admin_options' ) );
	}

	/**
	 * Setup instance settings
	 * @since 1.2.0
	 */
	public function set_settings() {

		// check if user has their own APP ID
		if ( ! empty( $this->get_option( 'oer_app_id' ) ) ) {
			$this->cc_key = $this->get_option( 'oer_app_id' );
		}

		$this->title          	= $this->get_option( 'title', $this->method_title );
		$this->fee            	= $this->get_option( 'fee', '' );
		$this->type           	= $this->get_option( 'delivery_type', 'order' );
		$this->shipping_methods = $this->get_option( 'shipping_methods', array() );
		$this->tracking			= $this->get_option( 'tracking', 'no' );
		$this->small_packets	= $this->get_option( 'small_packets', 'no' );
		$this->shipping_types   = $this->get_option( 'shipping_types', array( 'AIR','SURFACE' ) );
		$this->convert_currency = $this->get_option( 'convert_currency', 'no' );
	}

	/**
	 * Initialise Form Fields
	 **/
	public function init_form_fields() {

		$this->instance_form_fields = array(
			'title' => array(
				'title'       => __( 'Method Title', 'woocommerce-shipping-sapo-ips' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-shipping-sapo-ips' ),
				'default'     => __( 'SAPO International Parcel Service', 'woocommerce-shipping-sapo-ips' ),
			),
			'delivery_type' => array(
				'title'       => __( 'Calculation Type', 'woocommerce-shipping-sapo-ips' ),
				'type'        => 'select',
				'description' => '',
				'default'     => 'order',
				'options'     => array(
					'order' => __( 'Per Order - charge shipping for the entire order as a whole', 'woocommerce-shipping-sapo-ips' ),
					'item'  => __( 'Per Item - charge shipping for each item individually', 'woocommerce-shipping-sapo-ips' ),
				),
			),
			'fee' => array(
				'title'       => __( 'Handling Fee', 'woocommerce-shipping-sapo-ips' ),
				'type'        => 'text',
				'description' => __( 'Fee including tax. Enter an amount, e.g. 2.50, or a percentage, e.g. 5%. Leave blank to disable.', 'woocommerce-shipping-sapo-ips' ),
				'default'     => '',
			),
			'tracking'    => array(
				'title'   => __( 'Enable/Disable Tracking', 'woocommerce-shipping-sapo-ips' ),
				'type'    => 'checkbox',
				'label'   => __( 'Add registration for tracking to be added to shipping costs. (R25.50)', 'woocommerce-shipping-sapo-ips' ),
				'default' => 'no',
			),
			'small_packets' => array(
				'title'   => __( 'Enable/Disable Small Packets', 'woocommerce-shipping-sapo-ips' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable small packets rates for items under 2kg', 'woocommerce-shipping-sapo-ips' ),
				'default' => 'no',
			),
			'shipping_types' => array(
				'title'   => __( 'Shipping Types', 'woocommerce-shipping-sapo-ips' ),
				'type'    => 'multiselect',
				'class'   => 'chosen_select',
				'css'     => 'width: 250px;',
				'default' => array( 'AIR','SURFACE' ),
				'options' => array( 'AIR' => 'Air', 'SURFACE' => 'Surface' ),
			),
			'convert_currency' => array(
				'title'   => __( 'Enable/Disable Currency Conversion', 'woocommerce-shipping-sapo-ips' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable currency conversion when shop not using ZAR.', 'woocommerce-shipping-sapo-ips' ),
				'default' => 'no',
			),
		);

		$this->form_fields = array(
			'oer_app_id' => array(
				'title'   => __( 'Open Exchange Rates APP ID', 'woocommerce-shipping-sapo-ips' ),
				'type'    => 'text',
				'label'   => __( 'Your Open Exchange Rates APP ID for retrieving latest exchange rates. See http://openexchangerates.org', 'woocommerce-shipping-sapo-ips' ),
				'default' => '',
			),
		);
	}

	/**
	 * Set $this international rates property
	 */
	protected function _init_rates() {
		require_once( dirname( __FILE__ ) . '/international-rates.php' );
		$this->international_rates = wc_sapo_get_ips_rates();
	}

	/**
	 * Set $this zone property
	 */
	protected function _init_zones() {
		require_once( dirname( __FILE__ ) . '/zones.php' );
		$this->zones = wc_sapo_get_ips_zones();
	}

	/**
	 * Check if ZAR is shop currency and base country is ZA as only ZAR and shipping from ZA is supported
	 **/
	public function check_currency() {
		if ( 'ZAR' != get_option( 'woocommerce_currency' ) && 'yes' == $this->enabled && 'no' == $this->convert_currency ) :
			echo '<div class="error"><p>' . sprintf( __( 'SAPO International Parcel Service is enabled, but the <a href="%s">currency</a> is not ZAR; Please enable currency conversion to convert to shop currency.', 'woocommerce-shipping-sapo-ips' ), admin_url( 'admin.php?page=wc-settings&tab=general' ) ) . '</p></div>';
		endif;

		if ( 'ZA' != WC()->countries->get_base_country() && 'yes' == $this->enabled ) :
			echo '<div class="error"><p>' . sprintf( __( 'SAPO International Parcel Service is enabled, but the <a href="%s">base country/region</a> is not South Africa.', 'woocommerce-shipping-sapo-ips' ), admin_url( 'admin.php?page=wc-settings&tab=general' ) ) . '</p></div>';
		endif;

		if ( ! in_array( get_option( 'woocommerce_weight_unit' ), array( 'kg', 'g' ) ) ) :
			echo '<div class="error"><p>' . sprintf( __( 'SAPO International Parcel Service is enabled, but the <a href="%s">weight unit</a> is not set to g / kg', 'woocommerce-shipping-sapo-ips' ), admin_url( 'admin.php?page=wc-settings&tab=catalog' ) ) . '</p></div>';
		endif;
	}

	/**
	 * Save the settings.
	 */
	public function process_admin_options() {
		parent::process_admin_options();
		$this->set_settings();
	}

	/**
	 * Do some checks to see if shipping method is available to customer
	 *
	 * @param array $package
	 * @return bool
	 */
	function is_available( $package ) {

		$is_available = true;

		// Only certain countries allowed
		$country_found = false;
		foreach ( $this->zones as $zone => $codes ) {
			if ( in_array( WC()->customer->get_shipping_country(), $codes ) ) {
				$country_found = true;
			}
		}

		if (   'no' === $this->enabled
			|| 'ZA' != WC()->countries->get_base_country()
			|| ! $country_found ) {

				$is_available = false;

		}

		/**
		 * SAPO IPS is available filter.
		 *
		 * @since 1.2.0
		 * @param
		 */
		return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', $is_available, $package );
	}

	/**
	 * Calculate the shipping costs
	 */
	function calculate_shipping( $package = array() ) {

		$this->rates = array();
		$air_shipping_total = 0;
		$surface_shipping_total = 0;
		$weight = 0;

		// Find country zone
		$customer_zone = '';
		foreach ( $this->zones as $zone => $codes ) {
			if ( in_array( WC()->customer->get_shipping_country(), $codes ) ) {
				$customer_zone = $zone;
			}
		}

		if ( empty( $customer_zone ) ) {
			exit;
		}

		$air_rate           = $this->international_rates[ $customer_zone ]['Air']['Base'];
		$surface_rate       = $this->international_rates[ $customer_zone ]['Surface']['Base'];
		$additional_air     = $this->international_rates[ $customer_zone ]['Air']['Additional'];
		$additional_surface = $this->international_rates[ $customer_zone ]['Surface']['Additional'];
		$small_packets      = false;

		switch ( $this->type ) :
			case 'order' :
				if ( sizeof( WC()->cart->get_cart() ) > 0 ) :
					foreach ( WC()->cart->get_cart() as $item_id => $values ) :
						$_product = $values['data'];
						if ( $_product->exists() && $values['quantity'] > 0 ) :
							if ( ! $_product->is_virtual() ) :
								$weight += $_product->get_weight() * $values['quantity'];
							endif;
						endif;
					endforeach;

					$weight = $this->convert_weight( $weight );
					if ( 'yes' === $this->small_packets  && $weight <= 2000 ) {
						$air_shipping_total = ceil( $weight / 100 ) * $this->international_rates['SMALL_PACKET']['Air']['Additional'];
						$surface_shipping_total = ceil( $weight / 100 ) * $this->international_rates['SMALL_PACKET']['Surface']['Additional'];
						$small_packets = true;
					} else {
						$air_shipping_total += $air_rate;
						$surface_shipping_total += $surface_rate;
						$air_shipping_total += ceil( $weight / 100 ) * $additional_air;
						$surface_shipping_total += ceil( $weight / 100 ) * $additional_surface;
					}
				endif;
			break;

			case 'item' :
				if ( sizeof( WC()->cart->get_cart() ) > 0 ) :
					foreach ( WC()->cart->get_cart() as $item_id => $values ) :
						$_product = $values['data'];
						if ( $_product->exists() && $values['quantity'] > 0 ) :
							if ( ! $_product->is_virtual() ) :
								for ( $i = 0; $i < $values['quantity']; $i++ ) :
									$weight = $this->convert_weight( $_product->get_weight() );
									if ( 'yes' === $this->small_packets && $weight <= 2000 ) {
										$air_shipping_total = ceil( $weight / 100 ) * $this->international_rates['SMALL_PACKET']['Air']['Additional'];
										$surface_shipping_total = ceil( $weight / 100 ) * $this->international_rates['SMALL_PACKET']['Surface']['Additional'];
										$small_packets = true;
									} else {
										$air_shipping_total += $air_rate;
										$surface_shipping_total += $surface_rate;
										$air_shipping_total += ceil( $weight / 100 ) * $additional_air;
										$surface_shipping_total += ceil( $weight / 100 ) * $additional_surface;
									}
								endfor;
							endif;
						endif;
					endforeach;
				endif;
			break;
		endswitch;

		// Check if tracking should be registered and add fees
		if ( 'yes' === $this->tracking && ! $small_packets ) {
			$air_shipping_total += $this->tracking_rate;
			$surface_shipping_total += $this->tracking_rate;
		} elseif ( 'yes' === $this->tracking && $small_packets ) {
			$air_shipping_total += $this->small_packets_registration_rate;
			$surface_shipping_total += $this->small_packets_registration_rate;
		}

		// Check if currency must be converted and convert
		if ( 'yes' === $this->convert_currency  && 'ZAR' != get_option( 'woocommerce_currency' ) ) {
			// Transient to hold exchange rates
			if ( false === ( $rates = get_transient( 'wc_sapo_ips_exchange_rates' ) ) ) {
				$this->get_exchange_rates();
				$rates = get_transient( 'wc_sapo_ips_exchange_rates' );
			}
			// Do currency conversion
			if ( 'USD' != get_option( 'woocommerce_currency' ) ) {
				$currency = $rates->rates->{get_option( 'woocommerce_currency' )} * ( 1 / $rates->rates->ZAR );
				$air_shipping_total *= $currency;
				$surface_shipping_total *= $currency;
			} else {
				$air_shipping_total /= $rates->rates->ZAR;
				$surface_shipping_total /= $rates->rates->ZAR;
			}
		}

		// Check for handling fee and calculate
		if ( ! empty( $this->fee ) ) :
			$air_shipping_total += $this->get_fee( $this->fee, $air_shipping_total );
			$surface_shipping_total += $this->get_fee( $this->fee, $surface_shipping_total );
		endif;

		if ( in_array( 'AIR', $this->shipping_types ) ) {
			$rate = array(
				'id' => $this->id . '_air',
				'label' => $this->title . ': Air',
				'cost' => $air_shipping_total,
				'taxes' => false,
			);
			// Register the rate
			$this->add_rate( $rate );
		}

		if ( in_array( 'SURFACE', $this->shipping_types ) ) {
			$rate = array(
				'id' => $this->id . '_surface',
				'label' => $this->title . ': Surface',
				'cost' => $surface_shipping_total,
				'taxes' => false,
			);

			// Register the rate
			$this->add_rate( $rate );
		}
	}

	/**
	 * Convert weight unit to grams
	 **/
	function convert_weight( $weight ) {
		if ( 'kg' === get_option( 'woocommerce_weight_unit' ) ) {
			$weight = $weight * 1000;
		} elseif ( 'lbs' === get_option( 'woocommerce_weight_unit' ) ) {
			$weight = $weight * 453.59237;
		}

		return $weight;
	}

	/**
	 * Retrieves exchange rates from openexchangerates.org
	 **/
	function get_exchange_rates() {
		$response = wp_remote_get( 'http://openexchangerates.org/api/latest.json?app_id='.$this->cc_key, array( 'sslverify' => false ) );
		if ( 200 == $response['response']['code'] || ! is_wp_error( $response ) ) {
			set_transient( 'wc_sapo_ips_exchange_rates', json_decode( $response['body'] ), 60 * 60 );
		} else {
			$woocommerce_errors = array();
			$woocommerce_errors[] = __( 'Failed to update exchange rates from openexchangerates.org', 'woocommerce-shipping-sapo-ips' );
			update_option( 'woocommerce_errors', $woocommerce_errors );
		}
	}
}
