<?php
/**
 * MNM Item Product Quantity
 * @version  1.0.6
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ){
	exit;
}

global $product;

if ( $mnm_product->is_in_stock() ) {

	$mnm_id   = $mnm_product->variation_id ? $mnm_product->variation_id : $mnm_product->id;

	$quantity = apply_filters( 'woocommerce_mnm_quantity_input', 0, $mnm_product );
	$quantity = isset( $_REQUEST[ 'mnm_quantity' ] ) && isset( $_REQUEST[ 'mnm_quantity' ][ $mnm_id ] ) && ! empty ( $_REQUEST[ 'mnm_quantity' ][ $mnm_id ] ) ? intval( $_REQUEST[ 'mnm_quantity' ][ $mnm_id ] ) : $quantity;

	ob_start();
	woocommerce_quantity_input( array(
		'input_name'  => 'mnm_quantity[' . $mnm_id . ']',
		'input_value' => $quantity,
		'min_value'   => $product->get_child_quantity( 'min', $mnm_id ),
		'max_value'   => $product->get_child_quantity( 'max', $mnm_id )
	) );
	echo str_replace( 'class="quantity"', 'class="quantity mnm-quantity"', ob_get_clean() );

} else {

	// Availability
	$availability      = $mnm_product->get_availability();
	$availability_html = empty( $availability[ 'availability' ] ) ? '' : '<p class="stock ' . esc_attr( $availability[ 'class' ] ) . '">' . esc_html( $availability[ 'availability' ] ) . '</p>';

	echo apply_filters( 'woocommerce_stock_html', $availability_html, $availability[ 'availability' ], $mnm_product );

}
