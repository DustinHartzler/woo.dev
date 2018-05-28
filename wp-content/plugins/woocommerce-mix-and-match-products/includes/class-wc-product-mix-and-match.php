<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mix & Match Product Class.
 *
 * @class 	WC_Product_Mix_and_Match
 */

class WC_Product_Mix_and_Match extends WC_Product {

	/** @private array Type-specific data, including product/variation ids. */
	private $mnm_data;

	/** @private array Price-specific data, used to caculate min/max product prices for display and min/max prices incl/excl tax. */
	private $pricing_data = array();

	/** @private array of child products/variations. */
	private $children;

	/** @private string The total number of items that make up a full product. */
	private $container_size = false;

	/** @private boolean Whether the product is priced according to its contents. */
	private $per_product_pricing;

	/** @private boolean Whether the product's contents are shipped individually. */
	private $per_product_shipping;

	/** @private boolean In per-product pricing mode, the sale status of the product is defined by the children. */
	private $on_sale;

	/** @private array of child keys that are available. */
	private $available_items;

	/** @private boolean True if children stock can fill all slots. */
	private $has_enough_children_in_stock;

	/** @private boolean True if children must be backordered to fill all slots. */
	private $backorders_required;

	/** @private boolean True if product data is in sync with children. */
	private $is_synced;

	/** product prices
	 * @var double
	 */
	public $min_mnm_price;
	public $max_mnm_price;
	public $min_price;
	public $max_price;
	public $min_mnm_regular_price;
	public $max_mnm_regular_price;
	public $base_price;
	public $base_regular_price;
	public $base_sale_price;

	private $hide_price_html;

	public $min_mnm_price_excl_tax = false;
	public $min_mnm_price_incl_tax = false;


	/**
	 * __construct function.
	 *
	 * @param  mixed $product
	 */
	public function __construct( $product ) {

		$this->product_type = 'mix-and-match';

		parent::__construct( $product );

		$this->per_product_pricing  = get_post_meta( $this->id, '_mnm_per_product_pricing', true ) == 'yes' ? true : false;
		$this->per_product_shipping = get_post_meta( $this->id, '_mnm_per_product_shipping', true ) == 'yes' ? true : false;

		$this->min_price            = get_post_meta( $this->id, '_min_mnm_price', true );
		$this->max_price            = get_post_meta( $this->id, '_max_mnm_price', true );

		$this->mnm_data             = get_post_meta( $this->id, '_mnm_data', true );

		$this->is_synced            = false;

		$this->min_price            = (double) get_post_meta( $this->id, '_price', true );

		$this->base_price           = ( $base_price         = get_post_meta( $this->id, '_base_price', true ) ) ? (double) $base_price : 0.0;
		$this->base_regular_price   = ( $base_regular_price = get_post_meta( $this->id, '_base_regular_price', true ) ) ? (double) $base_regular_price : 0.0;
		$this->base_sale_price      = ( $base_sale_price    = get_post_meta( $this->id, '_base_sale_price', true ) ) ? (double) $base_sale_price : 0.0;

		if ( $this->is_priced_per_product() ) {
			$this->price = $this->get_base_price();
		}
	}


	/**
	 * Return the product's size limit.
	 *
	 * @return array
	 */
	public function get_container_size() {

		if ( false === $this->container_size ) {
			$this->container_size = intval( get_post_meta( $this->id, '_mnm_container_size', true ) );
		}

		return apply_filters( 'woocommerce_mnm_container_size', $this->container_size, $this );
	}


	/**
	 * Get the configuration data.
	 * includes the IDs of items allowed to be added to container
	 *
	 * @return array or false
	 */
	public function get_mnm_data() {

		if ( is_array( $this->mnm_data ) ) {

			return $this->mnm_data;
		}

		return false;
	}


	/**
	 * Stock of container is synced to allowed bundled items.
	 *
	 * @return boolean
	 */
	public function is_synced() {

		return $this->is_synced;
	}


	/**
	 * Mimics the return of the product's children posts.
	 * these are the items that are allowed to be in the container (but aren't actually child posts)
	 *
	 * @return array
	 */
	public function get_children() {

		if ( ! is_array( $this->children ) ) {

			$this->children = array();

			if ( $this->get_mnm_data() ) {

				foreach ( $this->get_mnm_data() as $mnm_item_id => $mnm_item_data ) {

					$product = wc_get_product( $mnm_item_id );

					if ( $product ) {
						$this->children[ $mnm_item_id ] = $product;
					}
				}
			}

		}

		return apply_filters( 'woocommerce_mnm_get_children', $this->children, $this );
	}


	/**
	 * get the prodcut object of one of the child items
	 *
	 * @param  int 	$child_id
	 * @return object 	WC_Product or WC_Product_variation
	 */
	public function get_child( $child_id ) {

		if ( isset( $this->children[ $child_id ] ) ) {
			$child = $this->children[ $child_id ];
		} else {
			$child = wc_get_product( $child_id );
		}

		return apply_filters( 'woocommerce_mnm_get_child', $child, $this );
	}


	/**
	 * Returns whether or not the product has any child product.
	 *
	 * @return bool
	 */
	public function has_children() {

		return sizeof( $this->get_children() ) ? true : false;
	}


	/**
	 * Get an array of available children for the current product.
	 *
	 * @access public
	 * @return array
	 */
	public function get_available_children() {

		if ( ! $this->is_synced() ) {
			$this->sync();
		}

		$available_children = array();

		foreach ( $this->get_children() as $child_id => $child ) {

			if ( $this->is_child_available( $child_id ) ) {
				$available_children[ $child_id ] = $child;
			}
		}

		return $available_children;
    }


    /**
     * Is child item available for inclusion in container.
     *
     * @access public
     * @param  int 	$child_id
     * @return boolean
     */
	public function is_child_available( $child_id ) {

		if ( ! $this->is_synced() ) {
			$this->sync();
		}

		if ( ! empty( $this->available_items ) && in_array( $child_id, $this->available_items ) ) {
			return true;
		}

		return false;
	}


    /**
     * Sync child data such as price, availability, etc.
     *
     * @return void
     */
	public function sync() {

		/*-----------------------------------------------------------------------------------*/
		/*	Sync Availability Data
		/*-----------------------------------------------------------------------------------*/

		$this->available_items              = array();

		$this->min_mnm_price                = '';
		$this->max_mnm_price                = '';
		$this->min_mnm_regular_price        = '';
		$this->max_mnm_regular_price        = '';

		$this->min_mnm_price_excl_tax       = false;
		$this->min_mnm_price_incl_tax       = false;

		$this->has_enough_children_in_stock = false;
		$this->backorders_required          = false;

		$items_in_stock                     = 0;
		$backorders_allowed                 = false;
		$unlimited_stock_available          = false;

		$children                           = $this->get_children();
		$container_size                     = $this->get_container_size();
		$min_container_size                 = apply_filters( 'woocommerce_mnm_min_container_size', $container_size > 0 ? $container_size : 1, $this );
		$max_container_size                 = apply_filters( 'woocommerce_mnm_max_container_size', $container_size > 0 ? $container_size : '', $this );

		if ( empty( $children ) ) {
			return;
		}

		foreach ( $children as $child_id => $child ) {

			// skip any item that isn't in stock/purchasable
			if ( ( empty( $child->id ) && empty( $child->variation_id ) ) || ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && ! $child->is_in_stock() ) || ( $this->is_priced_per_product() && ! $child->is_purchasable() ) ) {
				continue;
			}

			// store available child id
			$this->available_items[] = $child_id;

			$unlimited_child_stock_available = false;
			$child_stock_available           = 0;
			$child_backorders_allowed        = false;
			$child_sold_individually         = $child->is_sold_individually();

			// calculate how many slots this child can fill with backordered / non-backordered items
			if ( $child->managing_stock() ) {

				$child_stock = $child->get_total_stock();

				if ( $child_stock > 0 ) {

					$child_stock_available = $child_stock;

					if ( $child->backorders_allowed() ) {
						$backorders_allowed = $child_backorders_allowed = true;
					}

				} elseif ( $child->backorders_allowed() ) {
					$backorders_allowed = $child_backorders_allowed = true;
				}

			} elseif ( $child->is_in_stock() ) {
				$unlimited_stock_available = $unlimited_child_stock_available = true;
			}

			// set max number of slots according to stock status and max container size
			if ( $child_sold_individually ) {
				$this->sold_individually = true;
				$this->pricing_data[ $child_id ][ 'slots' ] = 1;
			} else if ( $max_container_size > 0 ) {
				if ( $unlimited_child_stock_available || $child_backorders_allowed ) {
					$this->pricing_data[ $child_id ][ 'slots' ] = $max_container_size;
				} else {
					$this->pricing_data[ $child_id ][ 'slots' ] = $child_stock_available > $max_container_size ? $max_container_size : $child_stock_available;
				}
			// if max_container_size = 0, then unlimited so only limit by stock
			} else if ( $unlimited_child_stock_available || $child_backorders_allowed ){
				$this->pricing_data[ $child_id ][ 'slots' ] = '';
			} else {
				$this->pricing_data[ $child_id ][ 'slots' ] = $child_stock_available;
			}

			// store price and slots for the min/max price calculation
			if ( $this->is_priced_per_product() ) {

				$this->pricing_data[ $child_id ][ 'price' ]            = $child->get_price();
				$this->pricing_data[ $child_id ][ 'regular_price' ]    = $child->get_regular_price();

				// amount used up in "cheapest" config
				$this->pricing_data[ $child_id ][ 'slots_filled_min' ] = 0;
				// amount used up in "most expensive" config
				$this->pricing_data[ $child_id ][ 'slots_filled_max' ] = 0;

				// save sale status for parent
				if ( $child->is_on_sale() ) {
					$this->on_sale = true;
				}
			}

			$items_in_stock += $child_stock_available;
		}

		// Update data for container availability
		if ( $unlimited_stock_available || $backorders_allowed || $items_in_stock >= $min_container_size ) {
			$this->has_enough_children_in_stock = true;
		}

		if ( ! $unlimited_stock_available && $backorders_allowed && $items_in_stock < $min_container_size ) {
			$this->backorders_required = true;
		}


		/*-----------------------------------------------------------------------------------*/
		/*	Per Product Pricing Min/Max Prices
		/*-----------------------------------------------------------------------------------*/

		if ( $this->is_priced_per_product() ) {

			// container base price
			$base_price              = $this->get_base_price();
			$base_reg_price          = $this->get_base_regular_price();

			/*-----------------------------------------------------------------------------------*/
			/*	Min Price
			/*-----------------------------------------------------------------------------------*/

			// slots filled so far
			$filled_slots = 0;

			// sort by cheapest
			uasort( $this->pricing_data, array( $this, 'sort_by_price' ) );

			$this->min_mnm_price         = 0;
			$this->min_mnm_regular_price = 0;

			if ( $this->has_enough_children_in_stock ) {

				// fill slots and calculate min price
				foreach ( $this->pricing_data as $child_id => $data ) {

					$slots_to_fill = $min_container_size - $filled_slots;

					$items_to_use = $this->pricing_data[ $child_id ][ 'slots_filled_min' ] = $this->pricing_data[ $child_id ][ 'slots' ] !== '' ? min( $this->pricing_data[ $child_id ][ 'slots' ], $slots_to_fill ) : $slots_to_fill;

					$filled_slots += $items_to_use;

					$this->min_mnm_price         += $items_to_use * $this->pricing_data[ $child_id ][ 'price' ];
					$this->min_mnm_regular_price += $items_to_use * $this->pricing_data[ $child_id ][ 'regular_price' ];

					if ( $filled_slots >= $min_container_size ) {
						break;
					}
				}

			} else {

				// in the unlikely even that stock is insufficient, just calculate the min price from the cheapest child
				foreach ( $this->pricing_data as $child_id => $data ) {
					$this->pricing_data[ $child_id ][ 'slots_filled_min' ] = 0;
				}

				$cheapest_child_id   = current( array_keys( $this->pricing_data ) );
				$cheapest_child_data = current( array_values( $this->pricing_data ) );

				$this->pricing_data[ $cheapest_child_id ][ 'slots_filled_min' ] = $min_container_size;

				$this->min_mnm_price         = $cheapest_child_data[ 'price' ] * $min_container_size;
				$this->min_mnm_regular_price = $cheapest_child_data[ 'regular_price' ] * $min_container_size;
			}

			// add the base price
			$this->min_mnm_price         = $base_price + $this->min_mnm_price;
			$this->min_mnm_regular_price = $base_reg_price + $this->min_mnm_regular_price;


			/*-----------------------------------------------------------------------------------*/
			/*	Max Price
			/*-----------------------------------------------------------------------------------*/

			// slots filled so far
			$filled_slots = 0;

			// sort by most expensive
			arsort( $this->pricing_data );

			$this->max_mnm_price         = 0;
			$this->max_mnm_regular_price = 0;

			if ( $this->has_enough_children_in_stock && $max_container_size !== '' ) {

				// fill slots and calculate max price
				foreach ( $this->pricing_data as $child_id => $data ) {

					$slots_to_fill = $max_container_size - $filled_slots;

					$items_to_use = $this->pricing_data[ $child_id ][ 'slots_filled_max' ] = $this->pricing_data[ $child_id ][ 'slots' ] !== '' ? min( $this->pricing_data[ $child_id ][ 'slots' ], $slots_to_fill ) : $slots_to_fill;

					$filled_slots += $items_to_use;

					$this->max_mnm_price         += $items_to_use * $this->pricing_data[ $child_id ][ 'price' ];
					$this->max_mnm_regular_price += $items_to_use * $this->pricing_data[ $child_id ][ 'regular_price' ];

					if ( $filled_slots >= $max_container_size ) {
						break;
					}
				}

			} else {

				// in the unlikely even that stock is insufficient, just calculate the max price from the most expensive child
				foreach ( $this->pricing_data as $child_id => $data ) {
					$this->pricing_data[ $child_id ][ 'slots_filled_max' ] = 0;
				}

				if ( $max_container_size !== '' ) {

					$priciest_child_id   = current( array_keys( $this->pricing_data ) );
					$priciest_child_data = current( array_values( $this->pricing_data ) );

					$this->pricing_data[ $priciest_child_id ][ 'slots_filled_max' ] = $container_size;

					$this->max_mnm_price         = $priciest_child_data[ 'price' ] * $container_size;
					$this->max_mnm_regular_price = $priciest_child_data[ 'regular_price' ] * $container_size;

				} else {
					$this->max_mnm_price = $this->max_mnm_regular_price = '';
				}

			}

			if ( $max_container_size !== '' ) {
				// add the base price
				$this->max_mnm_price         = $base_price + $this->max_mnm_price;
				$this->max_mnm_regular_price = $base_reg_price + $this->max_mnm_regular_price;
			}

		} else {

			$this->min_mnm_price = $this->max_mnm_price = $this->price;
		}

		/*-----------------------------------------------------------------------------------*/
		/*	Price Meta compatibility
		/*-----------------------------------------------------------------------------------*/

		if ( apply_filters( 'woocommerce_mnm_update_price_meta', true, $this ) ) {

			$product_price = get_post_meta( $this->id, '_price', true );

			if ( $this->min_price != $this->min_mnm_price ) {
				update_post_meta( $this->id, '_min_mnm_price', $this->min_mnm_price );
			}

			if ( $this->max_price != $this->max_mnm_price ) {
				update_post_meta( $this->id, '_max_mnm_price', $this->max_mnm_price );
			}

			if ( ! is_admin() && $this->is_priced_per_product() && $product_price != $this->min_mnm_price ) {
				update_post_meta( $this->id, '_price', $this->min_mnm_price );
			}
		}

		do_action( 'woocommerce_mnm_synced', $this );

		$this->is_synced = true;
    }

	/**
	 * Sort array data by price.
	 *
	 * @param  array $a
	 * @param  array $b
	 * @return -1|0|1
	 */
    private function sort_by_price( $a, $b ) {

	    if ( $a[ 'price' ] == $b[ 'price' ] ) {
	        return 0;
	    }

	    return ( $a[ 'price' ] < $b[ 'price' ] ) ? -1 : 1;
	}

	/**
	 * In per-product pricing mode, get_price() returns the base price of the container.
	 *
	 * @return 	double
	 */
	public function get_price() {

		if ( $this->is_priced_per_product() ) {
			return apply_filters( 'woocommerce_mnm_get_price', (double) $this->get_base_price(), $this );
		} else {
			return parent::get_price();
		}
	}

	/**
	 * Overrides get_regular_price to return base price in static price mode.
	 *
	 * @return double
	 */
	public function get_regular_price() {

		if ( $this->is_priced_per_product() ) {
			return apply_filters( 'woocommerce_mnm_get_regular_price', $this->get_base_regular_price(), $this );
		} else {
			return parent::get_regular_price();
		}
	}

	/**
	 * Get mnm base price.
	 *
	 * @return double
	 */
	public function get_base_price() {

		if ( $this->is_priced_per_product() ) {
			return apply_filters( 'woocommerce_mnm_get_base_price', $this->base_price, $this );
		} else {
			return false;
		}
	}

	/**
	 * Get mnm base regular price.
	 *
	 * @return double
	 */
	public function get_base_regular_price() {

		if ( $this->is_priced_per_product() ) {
			return apply_filters( 'woocommerce_mnm_get_base_regular_price', $this->base_regular_price, $this );
		} else {
			return false;
		}
	}

	/**
	 * Get mnm base sale price.
	 *
	 * @return double
	 */
	public function get_base_sale_price() {

		if ( $this->is_priced_per_product() ) {
			return apply_filters( 'woocommerce_mnm_get_base_sale_price', $this->base_sale_price, $this );
		} else {
			return false;
		}
	}

	/**
	 * Get min/max mnm price.
	 *
	 * @param  string $min_or_max
	 * @return double
	 */
	public function get_mnm_price( $min_or_max = 'min', $display = false ) {

		if ( $this->is_priced_per_product() ) {

			if ( ! $this->is_synced() ) {
				$this->sync();
			}

			$price = $min_or_max === 'min' ? $this->min_mnm_price : $this->max_mnm_price;

			if ( $price && $display ) {

				$display_price = WC_Mix_and_Match_Helpers::get_product_display_price( $this, $this->get_base_price() );

				foreach ( $this->pricing_data as $child_id => $data ) {

					$qty = $data[ 'slots_filled_' . $min_or_max ];

					if ( $qty > 0 ) {
						$child = $this->get_child( $child_id );
						$display_price += $qty * WC_Mix_and_Match_Helpers::get_product_display_price( $child, $data[ 'price' ] );
					}
				}

				$price = $display_price;
			}

		} else {

			$price = parent::get_price();

			if ( $display ) {
				$price = WC_Mix_and_Match_Helpers::is_wc_version_gte_2_4() ? parent::get_display_price( $price ) : WC_Mix_and_Match_Helpers::get_product_display_price( $this, $price );
			}
		}

		return $price;
	}

	/**
	 * Get min/max MnM regular price.
	 *
	 * @param  string $min_or_max
	 * @return double
	 */
	public function get_mnm_regular_price( $min_or_max = 'min', $display = false ) {

		if ( $this->is_priced_per_product() ) {

			if ( ! $this->is_synced() ) {
				$this->sync();
			}

			$price = $min_or_max === 'min' ? $this->min_mnm_regular_price : $this->max_mnm_regular_price;

			if ( $price && $display ) {

				$display_price = WC_Mix_and_Match_Helpers::get_product_display_price( $this, $this->get_base_regular_price() );

				foreach ( $this->pricing_data as $child_id => $data ) {

					$qty = $data[ 'slots_filled_' . $min_or_max ];

					if ( $qty > 0 ) {
						$child = $this->get_child( $child_id );
						$display_price += $qty * WC_Mix_and_Match_Helpers::get_product_display_price( $child, $data[ 'regular_price' ] );
					}
				}

				$price = $display_price;
			}

		} else {

			$price = parent::get_regular_price();

			if ( $display ) {
				$price = WC_Mix_and_Match_Helpers::is_wc_version_gte_2_4() ? parent::get_display_price( $price ) : WC_Mix_and_Match_Helpers::get_product_display_price( $this, $price );
			}
		}

		return $price;
	}

	/**
	 * MnM price including tax.
	 *
	 * @return double
	 */
	public function get_mnm_price_including_tax( $min_or_max = 'min' ) {

		if ( $this->is_priced_per_product() ) {

			if ( ! $this->is_synced() ) {
				$this->sync();
			}

			$property = $min_or_max . '_mnm_price_incl_tax';

			if ( $this->$property !== false )  {
				return $this->$property;
			}

			$price = $min_or_max === 'min' ? $this->min_mnm_price : $this->max_mnm_price;

			if ( $price ) {

				$this->$property = $this->get_price_including_tax( 1, $this->get_base_price() );

				foreach ( $this->pricing_data as $child_id => $data ) {

					$qty = $data[ 'slots_filled_' . $min_or_max ];

					if ( $qty > 0 ) {
						$child = $this->get_child( $child_id );
						$this->$property += $child->get_price_including_tax( $qty, $data[ 'regular_price' ] );
					}
				}

				$price = $this->$property;
			}

		} else {

			$price = parent::get_price_including_tax( 1, parent::get_price() );
		}

		return $price;
	}

	/**
	 * Min/max MnM price excl tax.
	 *
	 * @return double
	 */
	public function get_mnm_price_excluding_tax( $min_or_max = 'min' ) {

		if ( $this->is_priced_per_product() ) {

			if ( ! $this->is_synced() ) {
				$this->sync();
			}

			$property = $min_or_max . '_mnm_price_excl_tax';

			if ( $this->$property !== false )  {
				return $this->$property;
			}
			$price = $min_or_max === 'min' ? $this->min_mnm_price : $this->max_mnm_price;

			if ( $price ) {

				$this->$property = $this->get_price_excluding_tax( 1, $this->get_base_price() );

				foreach ( $this->pricing_data as $child_id => $data ) {

					$qty = $data[ 'slots_filled_' . $min_or_max ];

					if ( $qty > 0 ) {
						$child = $this->get_child( $child_id );
						$this->$property += $child->get_price_excluding_tax( $qty, $data[ 'regular_price' ] );
					}
				}

				$price = $this->$property;
			}

		} else {

			$price = parent::get_price_excluding_tax( 1, parent::get_price() );
		}

		return $price;
	}

	/**
	 * Returns range style html price string without min and max.
	 *
	 * @param  mixed    $price    default price
	 * @return string             overridden html price string (old style)
	 */
	public function get_price_html( $price = '' ) {

		if ( $this->is_priced_per_product() ) {

			if ( ! $this->is_synced() ) {
				$this->sync();
			}

			// Get the price
			if ( $this->min_mnm_price === '' ) {

				$price = apply_filters( 'woocommerce_mnm_empty_price_html', '', $this );

			} else {

				$price = wc_price( $this->get_mnm_price( 'min', true ) );

				if ( $this->is_on_sale() && $this->min_mnm_regular_price !== $this->min_mnm_price ) {
					$regular_price = wc_price( $this->get_mnm_regular_price( 'min', true ) );

					if ( $this->min_mnm_price !== $this->max_mnm_price ) {
						$price = sprintf( _x( '%1$s%2$s', 'Price range: from', 'woocommerce-mix-and-match-products' ), $this->get_price_html_from_text(), $this->get_price_html_from_to( $regular_price, $price ) . $this->get_price_suffix() );
					} else {
						$price = $this->get_price_html_from_to( $regular_price, $price ) . $this->get_price_suffix();
					}

					$price = apply_filters( 'woocommerce_mnm_sale_price_html', $price, $this );

				} elseif ( $this->min_mnm_price == 0 && $this->max_mnm_price == 0 ) {

					$free_string = apply_filters( 'woocommerce_mnm_show_free_string', false, $this ) ? __( 'Free!', 'woocommerce-mix-and-match-products' ) : $price;
					$price       = apply_filters( 'woocommerce_mnm_free_price_html', $free_string, $this );

				} else {

					if ( $this->min_mnm_price !== $this->max_mnm_price ) {
						$price = sprintf( _x( '%1$s%2$s', 'Price range: from', 'woocommerce-mix-and-match-products' ), $this->get_price_html_from_text(), $price . $this->get_price_suffix() );
					} else {
						$price = $price . $this->get_price_suffix();
					}

					$price = apply_filters( 'woocommerce_mnm_price_html', $price, $this );
				}
			}

			$price = apply_filters( 'woocommerce_get_mnm_price_html', $price, $this );

			return apply_filters( 'woocommerce_get_price_html', $price, $this );

		} else {

			return parent::get_price_html();
		}
	}

	/**
	 * Prices incl. or excl. tax are calculated based on the bundled products prices, so get_price_suffix() must be overridden to return the correct field in per-product pricing mode.
	 *
	 * @param  mixed    $price  price string
	 * @param  mixed    $qty  item quantity
	 * @return string    modified price html suffix
	 */
	public function get_price_suffix( $price = '', $qty = 1 ) {

		if ( $this->is_priced_per_product() ) {

			$price_display_suffix  = get_option( 'woocommerce_price_display_suffix' );

			if ( $price_display_suffix ) {
				$price_display_suffix = ' <small class="woocommerce-price-suffix">' . $price_display_suffix . '</small>';

				if ( false !== strpos( $price_display_suffix, '{price_including_tax}' ) ) {
					$price_display_suffix = str_replace( '{price_including_tax}', wc_price( $this->get_mnm_price_including_tax() * $qty ), $price_display_suffix );
				}

				if ( false !== strpos( $price_display_suffix, '{price_excluding_tax}' ) ) {
					$price_display_suffix = str_replace( '{price_excluding_tax}', wc_price( $this->get_mnm_price_excluding_tax() * $qty ), $price_display_suffix );
				}
			}

			return apply_filters( 'woocommerce_get_price_suffix', $price_display_suffix, $this );

		} else {

			return parent::get_price_suffix();
		}
	}


	/**
	 * A MnM product must contain children and have a price in static mode only.
	 *
	 * @return boolean
	 */
	public function is_purchasable() {

		$purchasable = true;

		// Products must exist of course
		if ( ! $this->exists() ) {
			$purchasable = false;

		// When priced statically a price needs to be set
		} elseif ( $this->is_priced_per_product() == false && $this->get_price() === '' ) {

			$purchasable = false;

		// Check the product is published
		} elseif ( $this->post->post_status !== 'publish' && ! current_user_can( 'edit_post', $this->id ) ) {

			$purchasable = false;

		} elseif ( false === $this->has_children() ) {

			$purchasable = false;
		}

		return apply_filters( 'woocommerce_is_purchasable', $purchasable, $this );
	}


    /**
	 * Returns whether or not the product container has any available child items.
	 *
	 * @return bool
	 */
	public function has_available_children() {

		return sizeof( $this->get_available_children() ) ? true : false;
	}


    /**
	 * Returns whether or not the product container's price is based on the included items.
	 *
	 * @return bool
	 */
	public function is_priced_per_product() {

		return apply_filters( 'woocommerce_mnm_priced_per_product', $this->per_product_pricing, $this );
	}


    /**
	 * Returns whether or not the product container's shipping cost is based on the included items.
	 *
	 * @return bool
	 */
	public function is_shipped_per_product() {

		return apply_filters( 'woocommerce_mnm_shipped_per_product', $this->per_product_shipping, $this );
	}


    /**
	 * Get availability of container
	 *
	 * @return array
	 */
	public function get_availability() {

		$backend_availability_data = parent::get_availability();

		if ( ! parent::is_in_stock() || $this->is_on_backorder() ) {
			return $backend_availability_data;
		}

		if ( ! is_admin() ) {

			if ( ! $this->is_synced() ) {
				$this->sync();
			}

			$availability = $class = '';

			if ( ! $this->has_enough_children_in_stock ) {
				$availability = __( 'Insufficient stock', 'woocommerce-mix-and-match-products' );
				$class        = 'out-of-stock';
			}

			if ( $this->backorders_required ) {
				$availability = __( 'Available on backorder', 'woocommerce-mix-and-match-products' );
				$class        = 'available-on-backorder';
			}

			if ( $class == 'out-of-stock' || $class == 'available-on-backorder' ) {
				return array( 'availability' => $availability, 'class' => $class );
			}
		}

		return $backend_availability_data;
	}


    /**
	 * Returns whether container is in stock
	 *
	 * @return bool
	 */
	public function is_in_stock() {

		$backend_stock_status = parent::is_in_stock();

		if ( ! is_admin() ) {

			if ( ! $this->is_synced() ) {
				$this->sync();
			}

			if ( $backend_stock_status === true && ! $this->has_enough_children_in_stock ) {

				return false;
			}
		}

		return $backend_stock_status;
	}


	/**
	 * Override on_sale status of mnm product. In per-product-pricing mode, true if a one of the child products is on sale, or if there is a base sale price defined.
	 *
	 * @return boolean
	 */
	public function is_on_sale() {

		$is_on_sale = false;

		if ( $this->is_priced_per_product() ) {

			if ( ! $this->is_synced() ) {
				$this->sync();
			}

			$is_on_sale = ( ( $this->base_sale_price != $this->base_regular_price && $this->base_sale_price == $this->base_price ) || ( $this->on_sale && $this->min_mnm_regular_price > 0 ) );

		} else {
			$is_on_sale = parent::is_on_sale();
		}

		return apply_filters( 'woocommerce_mnm_is_on_sale', $is_on_sale, $this );
	}


	/**
	 * Get the add to cart button text
	 *
	 * @return string
	 */
	public function add_to_cart_text() {

		$text = __( 'Read More', 'woocommerce-mix-and-match-products' );

		if ( $this->is_purchasable() && $this->is_in_stock() ) {
			$text =  __( 'Select options', 'woocommerce-mix-and-match-products' );
		}

		return apply_filters( 'mnm_add_to_cart_text', $text, $this );
	}

	/**
	 * Get the data attributes
	 *
	 * @return string
	 */
	public function get_data_attributes() {
		$attributes = array(
						'per_product_pricing' => $this->is_priced_per_product() ? 'true' : 'false',
						'container_id'        => $this->id,
						'container_size'      => $this->get_container_size(),
						'base_price'          => WC_Mix_and_Match_Helpers::get_product_display_price( $this, $this->get_base_price() ),
						'base_regular_price'  => WC_Mix_and_Match_Helpers::get_product_display_price( $this, $this->get_base_regular_price )
					);

		$attributes = (array) apply_filters( 'woocommerce_mix_and_match_data_attributes', $attributes, $this );

		$data = '';
		foreach ( $attributes as $a => $att ){
			$data .= sprintf( 'data-%s="%s"', esc_attr( $a ), esc_attr( $att ) );
		}

		return $data;
	}

	/**
	 * Get the min/max quantity of a child.
	 *
	 * @param  string $min_or_max
	 * @param  string $child_id
	 * @return int
	 */
	public function get_child_quantity( $min_or_max, $child_id ) {

		if ( ! $this->is_synced() ) {
			$this->sync();
		}

		$qty = '';

		if ( $mnm_product = $this->get_child( $child_id ) ) {

			if ( $min_or_max === 'min' ) {
				$qty = 0;
			} else {
				if ( isset( $this->pricing_data[ $child_id ][ 'slots' ] ) ) {
					$qty = $this->pricing_data[ $child_id ][ 'slots' ];
				}
			}

			return apply_filters( 'woocommerce_mnm_quantity_input_' . $min_or_max, $qty, $mnm_product );
		}

		return $qty;
	}
}


