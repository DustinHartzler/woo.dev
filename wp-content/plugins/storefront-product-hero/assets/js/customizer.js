/**
 * Theme Customizer enhancements for a better user experience.
 *
 * Contains handlers to make Theme Customizer preview reload changes asynchronously.
 */

( function( $ ) {
	wp.customize( 'sprh_heading_color', function( value ) {
		value.bind( function( to ) {
			$( '.sprh-hero h1' ).css( 'color', to );
		} );
	} );

	wp.customize( 'sprh_hero_text_color', function( value ) {
		value.bind( function( to ) {
			$( '.sprh-hero-content' ).css( 'color', to );
		} );
	} );

	wp.customize( 'sprh_background_color', function( value ) {
		value.bind( function( to ) {
			$( '.sprh-hero' ).css( 'background-color', to );
		} );
	} );
} )( jQuery );