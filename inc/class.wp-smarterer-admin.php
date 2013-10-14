<?php

class WP_Smarterer_Admin {

	/**
	 * Name of settings key
	 * @var string
	 */
	public $setting_name = 'wp_smarterer_options';


	function __construct() {
		// Options page
		add_action( 'admin_init', array( $this, 'options_init' ) );
		add_action( 'admin_menu', array( $this, 'add_options_page' ) );

		// User profile page
		add_action( 'show_user_profile', array( $this, 'user_profile_fields' ) );
		add_action( 'edit_user_profile', array( $this, 'user_profile_fields' ) );
		add_action( 'personal_options_update', array( $this, 'user_profile_fields_save' ) );
		add_action( 'edit_user_profile_update', array( $this, 'user_profile_fields_save' ) );

		// Oauth Callback
		add_action( 'wp_ajax_wp-smarterer-auth', array( $this, 'oauth_handler' ) );
	}

	/**
	 * Define option fields/sections
	 * @return array Array of option fields to be displayed/save properly
	 */
	function get_option_fields() {
		return array(
				'appkeys' => array(
					'type'  => 'section',
					'title' => __( 'App Keys', 'wp-smarterer' ),
					),
				'client_id' => array(
					'type' => 'field',
					'title' => __( 'Client ID', 'wp-smarterer' ),
					'description' => sprintf(
						__( 'Client ID is obtained by creating a new app on %s', 'wp-smarterer' ),
						'<a href="http://smarterer.com/developers/api/reg">smarterer.com</a>'
						),
					),
				'client_secret' => array(
					'type' => 'field',
					'title' => __( 'Client Secret', 'wp-smarterer' ),
					'description' => __( 'Client Secret is obtained by creating a new app on smarterer.com', 'wp-smarterer' ),
					),
			);
	}

	/**
	 * Setup and register setting/sections/fields of plugin
	 * @see  WP_Smarterer_Admin::get_option_fields
	 * @action admin_init
	 * @return void
	 */
	function options_init() {
		$fields = $this->get_option_fields();

		// Register setting
		register_setting(
			$this->setting_name,
			$this->setting_name,
			array( $this, 'validate' )
		);
		$sections = array();

		foreach ( $fields as $id => $args ) {
			if ( $args['type'] == 'section' ) {
				add_settings_section(
					$id,
					$args['title'],
					( isset( $args['callback'] ) ? $args['callback'] : '__return_false' ),
					$this->setting_name
				);
				$sections[] = $id;
			}
			elseif ( $args['type'] == 'field' ) {
				add_settings_field(
					$id,
					$args['title'],
					( isset( $args['callback'] ) ? $args['callback'] : array( $this, 'render_field' ) ),
					$this->setting_name,
					( isset( $args['section'] ) ? $args['section'] : end( $sections ) ),
					$id
				);
			}
		}
	}

	/**
	 * Get option values
	 * @return array Options array from DB
	 */
	function get_options() {
		$saved    = (array) get_option( $this->setting_name );
		$defaults = array();

		$options = wp_parse_args( $saved, $defaults );
		return $options;
	}

	/**
	 * Register options page, under 'Settings'
	 * @action admin_menu
	 */
	function add_options_page() {
		add_options_page(
			__( 'Smarterer', 'wp-smarterer' ),
			__( 'Smarterer', 'wp-smarterer' ),
			'edit_theme_options',
			$this->setting_name,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Render settings page
	 * @return void
	 */
	function render_page() {
		?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2><?php _e( 'WP Smarterer Options', 'wp_smarterer_options' ) ?></h2>

			<form method="post" action="options.php">
				<?php
				settings_fields( $this->setting_name );
				do_settings_sections( $this->setting_name );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render a settings field
	 * @param  string $id Field Unique ID
	 * @return void
	 */
	function render_field( $id ) {
		$options = $this->get_options();
		$fields = $this->get_option_fields();
		$field = $fields[ $id ];
		$value = isset( $options[ $id ] ) ? $options[ $id ] : null;
		?>
			<input type="text" name="<?php echo esc_attr( $this->setting_name ) ?>[<?php echo esc_attr( $id ) ?>]" id="<?php echo esc_attr( $id ) ?>" value="<?php echo esc_attr( $value ); ?>">
			<?php if ( ! empty( $field['description'] ) ): ?>
				<br/>
				<label class="description" for="<?php echo esc_attr( $id ) ?>"><?php echo esc_html( $field['description'] ) ?></label>
			<?php endif ?>
		<?php
	}

	/**
	 * Sanitize and validate form input. Accepts an array, return a sanitized array
	 *
	 * @param array $input Unknown values.
	 * @return array Sanitized options ready to be stored in the database.
	 */
	function validate( $input ) {
		$output = array();
		$fields = $this->get_option_fields();
		$default_san = 'sanitize_text_field';

		foreach ( $input as $key => $value ) {
			$field = $fields[ $key ];
			if ( empty( $value ) ) continue;
			$san = ( isset( $field['sanitizer'] ) ? $field['sanitizer'] : $default_san );
			$output[ $key ] = call_user_func( $san, $value );
		}

		return $output;
	}


	/**
	 * @action show_user_profile
	 * @action edit_user_profile
	 */
	function user_profile_fields( $user ) {
		$options = $this->get_options();
		$client_id = $options['client_id'];
		$url = add_query_arg( 'client_id', $client_id, add_query_arg( 'callback_url', admin_url( 'admin-ajax.php?action=wp-smarterer' ), 'https://smarterer.com/oauth/authorize?' ) );
		// Add thickbox args
		$url = $url . '&TB_iframe=1&width=100%&height=100%';
		$connected = get_user_meta( $user->ID, 'smarterer_token', true );
		$badges = ( $connected ) ? get_user_meta( $user->ID, 'smarterer_badges', true ): '';
		?>
		<h3>Smarterer</h3>

		<table class="form-table">
			<tr>
				<th><label for="smarterer_profile">Smarterer Profile</label></th>
				<td>
					<?php if ( $connected ): ?>
						<!-- TOKEN: <?php echo esc_attr( $connected ) ?> -->
						<!-- BADGES: <?php echo json_encode( $badges ) ?> -->
						<strong>Connected</strong>
						<br/>
						<label class="description" for="smarterer_disconnect">Disconnect? <input type="checkbox" name="smarterer_disconnect" id="smarterer_disconnect"></label> - 
						<label class="description" for="smarterer_refresh">Refresh? <input type="checkbox" name="smarterer_refresh" id="smarterer_refresh"></label>
					<?php else : ?>
						<strong>Not Connected</strong><br/><span class="description"><a href="<?php echo esc_url( $url ) ?>" target="_blank" class="thickbox">Connect using Smarterer?</a></span>
					<?php endif; ?>
				</td>
			</tr>
		</table>
		<?php

		add_thickbox();
	}

	/**
	 * @action personal_options_update
	 * @action edit_user_profile_update
	 */
	function user_profile_fields_save( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) )
			return false;

		if ( isset( $_POST['smarterer_disconnect'] ) ) {
			delete_user_meta( $user_id, 'smarterer_token' );
		} 
		elseif ( isset( $_POST['smarterer_refresh'] ) ) {
			$this->refresh_badges( $user_id ); 
		}
	}


	/**
	 * Handle oauth callback from Smarterer.com
	 * @action wp_ajax_wp-smarterer-auth
	 * @return void
	 */
	function oauth_handler() {
		$code = esc_attr( $_GET['?code'] ); // Bug in smarterer callback_url handling

		$options = $this->get_options();

		$url = sprintf(
			'https://smarterer.com/oauth/access_token?client_id=%s&client_secret=%s&grant_type=authorization_code&code=%s',
			$options['client_id'],
			$options['client_secret'],
			$code
			);
		$r = wp_remote_get( $url );

		if ( $r['response']['code'] != 200 )
			wp_die( 'Some error happened while trying to authenticate your login. Please contact website administrator.' );

		$body = json_decode( $r['body'] );
		$token = $body->access_token;

		$current_user = wp_get_current_user();
		update_user_meta( $current_user->ID, 'smarterer_token', $token );

		$this->refresh_badges( $current_user->ID );
		echo '<script>parent.location.href=parent.location.href+"?"; parent.tb_remove();</script>';
	}

	function refresh_badges( $user_id ) {
		$token = get_user_meta( $user_id, 'smarterer_token', true );
		$url = sprintf(
			'https://smarterer.com/api/badges?access_token=%s',
			$token
			);
		$r = wp_remote_get( $url );
		$badges = json_decode( $r[ 'body' ] );
		update_user_meta( $user_id, 'smarterer_badges', $badges );
	}


}

$admin = new WP_Smarterer_Admin;