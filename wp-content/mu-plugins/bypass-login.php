<?php
/**
Plugin Name: Bypass Login
Plugin URL: https://serverpress.com/plugins/bypass-login
Description: Allows developer bypass of login credentials via quick selection of any of the first 100 usernames in a combobox.
Version: 1.1.0
Author: Stephen Carnam
Author URI: http://steveorevo.com
 */

class BypassLogin {
	function __construct() {

		// Do not run outside of DesktopServer
		//if ( ! defined('DESKTOPSERVER') ) return;
		add_action( 'wp_ajax_nopriv_bypass_login', array( $this, 'wp_ajax_nopriv_bypass_login' ) );
		add_action( 'wp_ajax_bypass_login', array( $this, 'wp_ajax_nopriv_bypass_login' ) );
		add_action( 'login_enqueue_scripts', array( $this, 'login_enqueue_scripts' ) );
		add_action( 'login_form', array( $this, 'login_form' ) );
	}

	public function login_enqueue_scripts() {
		wp_enqueue_script( 'jquery' );
	}

	public function echo_user_option_elements( $atts ) {
		$atts = shortcode_atts( array(
			'number' => 100,
			'orderby' => 'display_name',
			'role' => '',
			'exclude_roles' => array(),
		), $atts );
		$users = get_users( $atts );
		foreach ( $users as $user ) {
			$skip = false;
			$wp_roles = new WP_Roles();
			$roles = array_keys( $user->{$user->cap_key} );
			$cap = $user->{$user->cap_key};
			$roles = " (";
			$sep = '';
			foreach ( $wp_roles->role_names as $role => $name ) {
				if ( array_key_exists( $role, $cap ) ) {
					if ( in_array( $role, $atts['exclude_roles'] ) ) {
						$skip = true;
					}
					$roles .= $sep . $role;
					$sep = ', ';
				}
			}
			$roles .= ')';
			if ( $skip === true ) {
				continue;
			}
			echo '<option value="' . $user->ID . '">';
			echo $user->user_login;
			echo $roles . '</option>';
		}
	}

	public function login_form() {
		?>
		<p>
			<label for="bypass_login">Bypass Login<br>
				<select id="bypass_login" style="width:100%;margin:2px 0 15px;">
					<?php
					$this->echo_user_option_elements( array(
						'role' => 'administrator',
					) );
					$this->echo_user_option_elements( array(
						'role' => 'webmaster',
					) );
					$this->echo_user_option_elements( array(
						'role' => 'editor',
					) );
					$this->echo_user_option_elements( array(
						'exclude_roles' => array(
							'administrator',
							'webmaster',
							'editor',
						),
					) );

					// Remember redirect URL or default to admin
					$url = get_admin_url();
					if ( isset( $_REQUEST['redirect_to'] ) ) {
						$url = $_REQUEST['redirect_to'];
					}
					?>
				</select>
		</p>
		<script type="text/javascript">
			(function($){
				$(function(){
					$("select#bypass_login").prepend("<option value='-1' selected='selected'>Choose username...</option>").val('-1');

					// Send bypass request via ajax
					$("#bypass_login").change(function(){
						var user_id = $(this).val();
						if (user_id !== '-1' ) {
							var login = {
								action: 'bypass_login',
								user_id: user_id
							};
							$.post('<?php echo admin_url( 'admin-ajax.php' ); ?>', login, function(r){
								if (r < 1) {
									alert('Login error: ' + r);
								}else{
									window.location.href = '<?php echo $url; ?>';
									$('#wp-submit').attr('disabled', 'disabled').val('Logging in...');
								}
							});
						}
					});
				});
			})(jQuery);
		</script>
	<?php
	}

	public function wp_ajax_nopriv_bypass_login() {

		// Login as the user and return success
		$user_id = intval( $_POST['user_id'] );
		wp_set_auth_cookie( $user_id, true );
		echo 1;
		die();
	}
}
global $bypassLogin;
$bypassLogin = new BypassLogin();
