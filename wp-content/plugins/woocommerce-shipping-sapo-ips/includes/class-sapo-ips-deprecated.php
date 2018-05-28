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
	var $sapo_rates = array(
		'A' => array(
			'Air' => array(
				'Base' => 122.20,
				'Additional' => 3.75
			),
			'Surface' => array(
				'Base' => 116.05,
				'Additional' => 1.50
			)
		),
		'B' => array(
			'Air' => array(
				'Base' => 180.20,
				'Additional' => 4.70
			),
			'Surface' => array(
				'Base' => 180.20,
				'Additional' => 2.90
			)
		),
		'C' => array(
			'Air' => array(
				'Base' => 180.20,
				'Additional' => 16.85
			),
			'Surface' => array(
				'Base' => 167.75,
				'Additional' => 4.70
			)
		),
		'D' => array(
			'Air' => array(
				'Base' => 186.45,
				'Additional' => 15.40
			),
			'Surface' => array(
				'Base' => 176.00,
				'Additional' => 3.30
			)
		),
		'E' => array(
			'Air' => array(
				'Base' => 138.70,
				'Additional' => 24.00
			),
			'Surface' => array(
				'Base' => 138.70,
				'Additional' => 5.25
			)
		),
		'F' => array(
			'Air' => array(
				'Base' => 132.45,
				'Additional' => 21.45
			),
			'Surface' => array(
				'Base' => 129.55,
				'Additional' => 3.30
			)
		),
		'SMALL_PACKET' => array(
			'Air' => array(
				'Base' => 0.00,
				'Additional' => 33.55
			),
			'Surface' => array(
				'Base' => 0.00,
				'Additional' => 16.80
			)
		),
	);

	var $zones = array(
		'A' => array(
			'BW','KM','KE','MW','MU','NA','SC','SZ','TZ'
		),
		'B' => array(
			'AO','LS','MG','MZ','RE','RW','UG','ZM','ZW'
		),
		'C' => array(
			'DZ','BH','BJ','BI','BF','CM','CV','CF','TD','CG','CD','DJ','AE','EG','GQ','ET','GA','GM','GW','GN','IL','CI','JO','KW','LB','LR','LY','ML','MR','MA','NE','NG','OM','QA','ST','SA','SN','SL','SD','SY','TG','TN','TR','AE','YE'
		),
		'D' => array(
			'AL','AD','AM','AT','AZ','BE','BA','BG','HR','CY','CZ','DK','EE','FI','FR','DE','GE','GB','GR','HU','IS','IE','IT','KZ','KG','LV','LI','LT','LU','MK','MT','MD','MC','ME','NL','NO','PL','PT','RO','RU','RS','SK','SI','ES','SE','CH','TJ','TM','UA','GB','UZ','VA'
		),
		'E' => array(
			'AG','AR','BS','BB','BZ','BM','BO','BR','CL','CO','CR','CU','DM','DO','EC','SV','GF','GD','GT','GY','HT','US','HN','JM','MX','NI','PA','PY','PE','SR','TT','US','UY','VE','VI'
		),
		'F' => array(
			'AF','AU','BD','BT','BN','KH','CA','CN','FJ','HK','ID','IN','IR','IQ','JP','KI','KP','KR','LA','MO','MY','MV','MN','MM','NR','NP','NZ','PK','PG','PH','WS','SG','SB','LK','TW','TH','TO','TV','VU','VN'
		)
	);

	var $tracking_rate = 25.50;
	var $small_packets_registration_rate = 26.60;
	var $small_packets;
	var $tracking;
	var $shipping_types;
	var $convert_currency;
	var $cc_key = 'e65018798d4a4585a8e2c41359cc7f3c';

	/**
	 * Contructor
	 **/
	public function __construct() {
		global $woocommerce;
		$this->id           = 'sapo_ips';
		$this->method_title = __( 'SAPO International Parcel Service', 'woocommerce-shipping-sapo-ips' );

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		$this->enabled        	= $this->settings['enabled'];
		$this->title          	= $this->settings['title'];
		$this->fee            	= $this->settings['fee'];
		$this->type           	= $this->settings['delivery_type'];
		$this->shipping_methods = isset( $this->settings['shipping_methods'] ) ? $this->settings['shipping_methods'] : array();
		$this->countries 		= isset( $this->settings['countries'] ) ? $this->settings['countries'] : array();
		$this->origin_country 	= $woocommerce->countries->get_base_country();
		$this->tracking			= $this->settings['tracking'];
		$this->small_packets	= $this->settings['small_packets'];
		$this->shipping_types   = $this->settings['shipping_types'];
		$this->convert_currency = $this->settings['convert_currency'];

		// check if user has their own APP ID
		if ( ! empty( $this->oer_app_id ) )
			$this->cc_key = $this->oer_app_id;

		// Only shipping from ZA and ZAR currency supported
		add_action( 'admin_notices', array( &$this, 'check_currency' ) );

		// Save settings
		add_action( 'woocommerce_update_options_shipping_sapo_ips', array( &$this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_shipping_methods', array( &$this, 'process_admin_options' ) );

		//delete_transient('wc_sapo_ips_exchange_rates');

	}

	/**
	 * Initialise Form Fields
	 **/
	function init_form_fields() {
		global $woocommerce;
		$this->form_fields = array(
			'enabled' => array(
				'title' => __( 'Enable/Disable', 'woocommerce-shipping-sapo-ips' ),
				'type' => 'checkbox',
				'label' => __( 'Enable SAPO International Parcel Service', 'woocommerce-shipping-sapo-ips' ),
				'default' => 'yes'
			),
			'title' => array(
				'title' => __( 'Method Title', 'woocommerce-shipping-sapo-ips' ),
				'type' => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-shipping-sapo-ips' ),
				'default' => __( 'SAPO International Parcel Service', 'woocommerce-shipping-sapo-ips' )
			),
			'delivery_type' => array(
				'title' => __( 'Calculation Type', 'woocommerce-shipping-sapo-ips' ),
				'type' => 'select',
				'description' => '',
				'default' => 'order',
				'options' => array(
					'order' => __('Per Order - charge shipping for the entire order as a whole', 'woocommerce-shipping-sapo-ips'),
					'item'  => __('Per Item - charge shipping for each item individually', 'woocommerce-shipping-sapo-ips')
				)
			),
			'fee' => array(
				'title' => __( 'Handling Fee', 'woocommerce-shipping-sapo-ips' ),
				'type' => 'text',
				'description' => __( 'Fee including tax. Enter an amount, e.g. 2.50, or a percentage, e.g. 5%. Leave blank to disable.', 'woocommerce-shipping-sapo-ips' ),
				'default' => ''
			),
			'tracking' => array(
				'title' => __( 'Enable/Disable Tracking', 'woocommerce-shipping-sapo-ips' ),
				'type' => 'checkbox',
				'label' => __( 'Add registration for tracking to be added to shipping costs. (R25.50)', 'woocommerce-shipping-sapo-ips' ),
				'default' => 'no'
			),
			'small_packets' => array(
				'title' => __( 'Enable/Disable Small Packets', 'woocommerce-shipping-sapo-ips' ),
				'type' => 'checkbox',
				'label' => __( 'Enable small packets rates for items under 2kg', 'woocommerce-shipping-sapo-ips' ),
				'default' => 'no'
			),
			'shipping_types' => array(
				'title' => __('Shipping Types', 'woocommerce-shipping-sapo-ips'),
				'type' => 'multiselect',
				'class' => 'chosen_select',
				'css' => 'width: 250px;',
				'default' => array('AIR','SURFACE'),
				'options' => array( 'AIR' => 'Air', 'SURFACE' => 'Surface' )
			),
			'oer_app_id' => array(
				'title' => __( 'Open Exchange Rates APP ID', 'woocommerce-shipping-sapo-ips' ),
				'type' => 'text',
				'label' => __( 'Your Open Exchange Rates APP ID for retrieving latest exchange rates. See http://openexchangerates.org', 'woocommerce-shipping-sapo-ips' ),
				'default' => ''
			),
			'convert_currency' => array(
				'title' => __( 'Enable/Disable Currency Conversion', 'woocommerce-shipping-sapo-ips' ),
				'type' => 'checkbox',
				'label' => __( 'Enable currency conversion when shop not using ZAR.', 'woocommerce-shipping-sapo-ips' ),
				'default' => 'no'
			)
		);
	}

	/**
	 * Check if ZAR is shop currency and base country is ZA as only ZAR and shipping from ZA is supported
	 **/
	function check_currency() {
		if ( 'ZAR' != get_option( 'woocommerce_currency' ) && 'yes' == $this->enabled && 'no' == $this->convert_currency ) :
			echo '<div class="error"><p>' . sprintf(__('SAPO International Parcel Service is enabled, but the <a href="%s">currency</a> is not ZAR; Please enable currency conversion to convert to shop currency.', 'woocommerce-shipping-sapo-ips'), admin_url( 'admin.php?page=wc-settings&tab=general' ) ) . '</p></div>';
		endif;

		if ( 'ZA' != $this->origin_country && 'yes' == $this->enabled ) :
			echo '<div class="error"><p>' . sprintf(__('SAPO International Parcel Service is enabled, but the <a href="%s">base country/region</a> is not South Africa.', 'woocommerce-shipping-sapo-ips'), admin_url( 'admin.php?page=wc-settings&tab=general' ) ) . '</p></div>';
		endif;

		if ( ! in_array( get_option( 'woocommerce_weight_unit' ), array( 'kg', 'g' ) ) ) :
			echo '<div class="error"><p>' . sprintf(__('SAPO International Parcel Service is enabled, but the <a href="%s">weight unit</a> is not set to g / kg', 'woocommerce-shipping-sapo-ips'), admin_url( 'admin.php?page=wc-settings&tab=catalog' ) ) . '</p></div>';
		endif;
	}

	function process_admin_options() {
		parent::process_admin_options();
	}

	/**
	 * Do some checks to see if shipping method is available to customer
	 **/
	function is_available( $package ) {
		global $woocommerce;

		// Obviously you cant use this if its not enabled
		if ( $this->enabled == "no" )
			return false;

		// Can only ship from South Africa
		if ( $this->origin_country != 'ZA' )
			return false;

		// Only certain countries allowed
		$country_found = false;
		foreach( $this->zones as $zone => $codes ) {
			if ( in_array( $woocommerce->customer->get_shipping_country(), $codes ) )
				$country_found = true;
		}
		if ( ! $country_found ) {
			return false;
		}

		return true;
	}

	/**
	 * Calculate the shipping costs
	 **/
	function calculate_shipping( $package = array() ) {
		global $woocommerce;

		$this->rates = array();
		$air_shipping_total = 0;
		$surface_shipping_total = 0;
		$weight = 0;

		// Find country zone
		$customer_zone = '';
		foreach( $this->zones as $zone => $codes ) {
			if ( in_array( $woocommerce->customer->get_shipping_country(), $codes ) )
				$customer_zone = $zone;
		}

		if ( empty ( $customer_zone ) )
			exit;

		$air_rate = $this->sapo_rates[$customer_zone]['Air']['Base'];
		$surface_rate = $this->sapo_rates[$customer_zone]['Surface']['Base'];
		$additional_air = $this->sapo_rates[$customer_zone]['Air']['Additional'];
		$additional_surface = $this->sapo_rates[$customer_zone]['Surface']['Additional'];
		$small_packets = false;

		switch ( $this->type ) :
			case 'order' :
				if ( sizeof( $woocommerce->cart->get_cart() ) > 0 ) :
					foreach ( $woocommerce->cart->get_cart() as $item_id => $values ) :
						$_product = $values['data'];
						if ( $_product->exists() && $values['quantity'] > 0 ) :
							if ( ! $_product->is_virtual() ) :
								$weight += $_product->get_weight() * $values['quantity'];
							endif;
						endif;
					endforeach;

					$weight = $this->convert_weight( $weight );
					if ( $this->small_packets == 'yes' && $weight <= 2000 ) {
						$air_shipping_total = ceil( $weight/100 ) * $this->sapo_rates['SMALL_PACKET']['Air']['Additional'];
						$surface_shipping_total = ceil( $weight/100 ) * $this->sapo_rates['SMALL_PACKET']['Surface']['Additional'];
						$small_packets = true;
					} else {
						$air_shipping_total += $air_rate;
						$surface_shipping_total += $surface_rate;
						$air_shipping_total += ceil( $weight/100 ) * $additional_air;
						$surface_shipping_total += ceil( $weight/100 ) * $additional_surface;
					}
				endif;
			break;

			case 'item' :
				if ( sizeof( $woocommerce->cart->get_cart() ) > 0 ) :
					foreach ( $woocommerce->cart->get_cart() as $item_id => $values ) :
						$_product = $values['data'];
						if ( $_product->exists() && $values['quantity'] > 0 ) :
							if ( ! $_product->is_virtual() ) :
								for ( $i = 0; $i < $values['quantity']; $i++ ) :
									$weight = $this->convert_weight( $_product->get_weight() );
									if ( $this->small_packets == 'yes' && $weight <= 2000 ) {
										$air_shipping_total = ceil( $weight/100 ) * $this->sapo_rates['SMALL_PACKET']['Air']['Additional'];
										$surface_shipping_total = ceil( $weight/100 ) * $this->sapo_rates['SMALL_PACKET']['Surface']['Additional'];
										$small_packets = true;
									} else {
										$air_shipping_total += $air_rate;
										$surface_shipping_total += $surface_rate;
										$air_shipping_total += ceil( $weight/100 ) * $additional_air;
										$surface_shipping_total += ceil( $weight/100 ) * $additional_surface;
									}
								endfor;
							endif;
						endif;
					endforeach;
				endif;
			break;
		endswitch;

		// Check if tracking should be registered and add fees
		if ( $this->tracking == 'yes' && ! $small_packets ) {
			$air_shipping_total += $this->tracking_rate;
			$surface_shipping_total += $this->tracking_rate;
		} elseif ( $this->tracking == 'yes' && $small_packets ) {
			$air_shipping_total += $this->small_packets_registration_rate;
			$surface_shipping_total += $this->small_packets_registration_rate;
		}

		// Check if currency must be converted and convert
		if ( $this->convert_currency == 'yes' && 'ZAR' != get_option( 'woocommerce_currency' ) ) {
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
				'taxes' => false
			);
			// Register the rate
			$this->add_rate( $rate );
		}

		if ( in_array( 'SURFACE', $this->shipping_types ) ) {
			$rate = array(
				'id' => $this->id . '_surface',
				'label' => $this->title . ': Surface',
				'cost' => $surface_shipping_total,
				'taxes' => false
			);

			// Register the rate
			$this->add_rate( $rate );
		}
	}

	/**
	 * Convert weight unit to grams
	 **/
	function convert_weight( $weight ) {
		if ( 'kg' == get_option( 'woocommerce_weight_unit' ) )
			$weight = $weight * 1000;
		elseif ( 'lbs' ==  get_option( 'woocommerce_weight_unit' ) )
			$weight = $weight * 453.59237;
		return $weight;
	}

	/**
	 * Retrieves exchange rates from openexchangerates.org
	 **/
	function get_exchange_rates() {
		$response = wp_remote_get( 'http://openexchangerates.org/api/latest.json?app_id='.$this->cc_key, array( 'sslverify'=>false ) );
		if ( $response['response']['code'] == 200 || ! is_wp_error( $response ) ) {
			set_transient( 'wc_sapo_ips_exchange_rates', json_decode( $response['body'] ), 60 * 60 );
		} else {
			$woocommerce_errors = array();
			$woocommerce_errors[] = __( 'Failed to update exchange rates from openexchangerates.org', 'woocommerce-shipping-sapo-ips' );
			update_option('woocommerce_errors', $woocommerce_errors);
		}
	}
}
?>
