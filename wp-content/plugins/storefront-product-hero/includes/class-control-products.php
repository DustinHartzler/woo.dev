<?php
/**
 * Class to create a custom product selector control
 */
class Products_Storefront_Control extends WP_Customize_Control {
    private $posts 	= false;
    public $type 	= 'select';

    public function __construct( $manager, $id, $args = array(), $options = array() ) {
        $postargs 	= wp_parse_args( $options, array(
        	'numberposts' 	=> '-1',
        	'post_type'		=> 'product',
        	'meta_key' 		=> '_featured',
        	'meta_value' 	=> 'yes'
        	)
        );

        $this->posts = get_posts( $postargs );

        parent::__construct( $manager, $id, $args );
    }

    /**
    * Render the content on the theme customizer page
    */
    public function render_content() {
        if ( ! empty( $this->posts ) ) {
            ?>
                <label>
                    <span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>

                    <select data-customize-setting-link="<?php echo esc_attr( $this->id ); ?>">
                    <?php
                    	echo '<option value="default">' . __( 'Please select a product', 'storefront_product_hero' ) . '</option>';
                        foreach ( $this->posts as $post ) {

                        	$_product = new WC_Product( $post->ID );

                        	echo '<option value="' . $post->ID . '"' . selected( $this->value(), $post->ID, false ) . '>' . $_product->get_formatted_name() . '</option>';
                        }
                    ?>
                    </select>
                </label>
            <?php
        }
    }
}