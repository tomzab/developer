<?php /*

**************************************************************************

Plugin Name:  Developer
Plugin URI:   http://wordpress.org/extend/plugins/developer/
Description:  The first stop for every WordPress developer
Version:      0.1
Author:       Automattic
Author URI:   http://automattic.com/wordpress-plugins/
License:      GPLv2 or later

Text Domain:  a8c-developer
Domain Path:  /languages/

**************************************************************************/

class Automattic_Developer {

	public $settings              = array();
	public $default_settings      = array();

	public $recommended_plugins   = array();
	public $recommended_constants = array();

	// Using "private" for read-only functionality. See __get().
	private $option_name          = 'a8c_developer';
	private $settings_page_slug   = 'a8c_developer';

	function __construct() {
		add_action( 'init',           array( &$this, 'init' ) );
		add_action( 'admin_init',     array( &$this, 'admin_init' ) );

		add_action( 'admin_menu',     array( &$this, 'register_settings_page' ) );

		add_action( 'admin_bar_menu', array( &$this, 'add_node_to_admin_bar' ) );
	}

	// Allows private variables to be read. Basically implements read-only variables.
	function __get( $var ) {
		return ( isset( $this->$var ) ) ? $this->$var : null;
	}

	public function init() {
		$this->default_settings = array(
			'project_type' => false,
		);

		$this->settings = wp_parse_args( (array) get_option( $this->option_name ), $this->default_settings );

		$this->recommended_plugins = array(
			'debug-bar' => array(
				'name'   => __( 'Debug Bar', 'a8c-developer' ),
				'active' => class_exists( 'Debug_Bar' ),
			),
			'log-deprecated-notices' => array(
				'name'   => __( 'Log Deprecated Notices', 'a8c-developer' ),
				'active' => class_exists( 'Deprecated_Log' ),
			),
			'foobar' => array(
				'name'   => 'Dummy Test Plugin',
				'active' => false,
			),
			// TODO: Add more?
		);

		$this->recommended_constants = array(
			'WP_DEBUG'    => sprintf( __( 'Enables <a href="%s" target="_blank">debug mode</a> which helps identify and resolve issues', 'a8c-developer' ), 'http://codex.wordpress.org/Debugging_in_WordPress' ),
			'SAVEQUERIES' => __( 'Logs database queries to an array so you can review them. The Debug Bar plugin will list out database queries if you set this constant.', 'a8c-developer' ),
			'FOOBAR'      => 'A dummy constant for showing a missing constant',
		);
	}

	public function admin_init() {
		register_setting( $this->option_name, $this->option_name, array( &$this, 'settings_validate' ) );

		if ( ! get_option( $this->option_name ) ) {
			add_action( 'admin_notices', array( &$this, 'admin_notices_setup_nag' ) );
		}
	}

	public function register_settings_page() {
		add_options_page( __( 'Automattic Developer Helper', 'a8c-developer' ), __( 'Developer', 'a8c-developer' ), 'manage_options', $this->settings_page_slug, array( &$this, 'settings_page' ) );
	}

	public function add_node_to_admin_bar( $wp_admin_bar ) {
		$wp_admin_bar->add_node( array(
			'id'     => $this->settings_page_slug,
			'title'  => __( 'Developer', 'a8c-developer' ),
			'parent' => 'top-secondary', // Off on the right side
			'href'   => admin_url( 'options-general.php?page=' . $this->settings_page_slug ),
			'meta'   => array(
				'title' => __( 'View the Automattic Developer Helper settings and status page', 'a8c-developer' ),
			),
		) );
	}

	public function admin_notices_setup_nag() {
		global $parent_file, $hook_suffix;

		// Don't do anything on this plugin's settings page
		if ( 'settings_page_' . $this->settings_page_slug == $hook_suffix )
			return;

		add_settings_error( $this->option_name, $this->option_name. '_not_set_up', sprintf( __( 'Please <a href="%s">configure the development plugin</a>. TODO: Copy.', 'a8c-developer' ), admin_url( 'options-general.php?page=' . $this->settings_page_slug ) ) );

		// Avoid a double message
		if ( 'options-general.php' != $parent_file )
			settings_errors( $this->option_name );
	}

	public function settings_page() {
		add_settings_section( 'a8c_developer_main', esc_html__( 'Main Configuration', 'a8c-developer' ), '__return_false', $this->settings_page_slug . '_settings' );
		add_settings_field( 'a8c_developer_project_type', esc_html__( 'Project Type', 'a8c-developer' ), array( &$this, 'settings_field_select' ), $this->settings_page_slug . '_settings', 'a8c_developer_main', array(
			'name'        => 'project_type',
			'description' => esc_html__( 'Are you developing for a site to be hosted on WordPress.com or somewhere else?', 'a8c-developer' ),
			'options'     => array(
				'wpcom' => esc_html__( 'WordPress.com', 'a8c-developer' ),
				'wporg' => esc_html__( 'Somewhere else (WordPress.org)', 'a8c-developer' ),
			),
		) );

		add_settings_section( 'a8c_developer_plugins', esc_html__( 'Plugins', 'a8c-developer' ), array( &$this, 'settings_section_plugins' ), $this->settings_page_slug . '_status' );
		foreach ( $this->recommended_plugins as $plugin_slug => $plugin_details ) {
			$plugin_details = array_merge( array( 'slug' => $plugin_slug ), $plugin_details );
			add_settings_field( 'a8c_developer_plugin_' . $plugin_slug, $plugin_details['name'], array( &$this, 'settings_field_plugin' ), $this->settings_page_slug . '_status', 'a8c_developer_plugins', $plugin_details );
		}

		add_settings_section( 'a8c_developer_constants', esc_html__( 'Environment Configuration', 'a8c-developer' ), array( &$this, 'settings_section_constants' ), $this->settings_page_slug . '_status' );
		foreach ( $this->recommended_constants as $constant => $description ) {
			add_settings_field( 'a8c_developer_constant_' . $constant, $constant, array( &$this, 'settings_field_constant' ), $this->settings_page_slug . '_status', 'a8c_developer_constants', array(
				'constant'    => $constant,
				'description' => $description,
			) );
		}


		# Add more sections and fields here as needed
?>

		<div class="wrap">

		<?php screen_icon(); // TODO: Better icon? ?>

		<h2><?php esc_html_e( 'Automattic Developer Helper', 'vehicle-info' ); ?></h2>

		<form action="options.php" method="post">

			<?php settings_fields( $this->option_name ); // matches value from register_setting() ?>

			<?php do_settings_sections( $this->settings_page_slug . '_settings' ); // matches values from add_settings_section/field() ?>

			<?php submit_button(); ?>

			<?php do_settings_sections( $this->settings_page_slug . '_status' ); ?>
		</form>

		<h3 style="margin-top:150px">Current Settings Value:</h3>
		<?php var_dump( get_option( $this->option_name ) ); ?>

		</div>
<?php
	}

	public function settings_field_select( $args ) {
		if ( empty( $args['name'] ) || ! is_array( $args['options'] ) )
			return false;

		$selected = ( isset( $this->settings[ $args['name'] ] ) ) ? $this->settings[ $args['name'] ] : '';

		echo '<select name="a8c_developer[' . esc_attr( $args['name'] ) . ']">';

		foreach ( (array) $args['options'] as $value => $label )
			echo '<option value="' . esc_attr( $value ) . '"' . selected( $value, $selected, false ) . '>' . $label . '</option>';

		echo '</select>';

		if ( ! empty( $args['description'] ) )
			echo ' <span class="description">' . $args['description'] . '</span>';
	}

	public function settings_section_plugins() {
		echo '<p>' . __( 'We recommend you have the following plugins installed:', 'a8c-developer' ) . '</p>';
	}

	// TODO: Make this not shitty
	public function settings_field_plugin( $args ) {
		if ( $args['active'] ) {
			echo '<span style="font-weight:bold;color:green;">' . __( 'ACTIVE', 'a8c-developer' ) . '</span>';
		} else {
			// TODO: Enable if already installed but just disabled
			echo '<a style="font-weight:bold;color:darkred;" href="' . wp_nonce_url( admin_url( 'update.php?action=install-plugin&plugin=' . $args['slug'] ), 'install-plugin_' . $args['slug'] ) . '" title="' . __( 'Click here to install', 'a8c-developer' ) . '">' . __( 'INACTIVE', 'a8c-developer' ) . '</a>';
		}
	}

	public function settings_section_constants() {
		echo '<p>' . sprintf( __( 'We also recommend you set the following constants to <code>true</code> in your <code>wp-config.php</code> file. <a href="%s" target="_blank">Need help?</a>', 'a8c-developer' ), 'http://codex.wordpress.org/Editing_wp-config.php' ) . '</p>';
	}

	// TODO: Make this not shitty
	public function settings_field_constant( $args ) {
		if ( defined( $args['constant'] ) && constant( $args['constant'] ) ) {
			echo '<span style="font-weight:bold;color:green;">' . __( 'SET', 'a8c-developer' ) . '</span>';
		} else {
			echo '<span style="font-weight:bold;color:darkred;">' . __( 'NOT SET', 'a8c-developer' ) . '</span>';
		}

		if ( ! empty( $args['description'] ) )
			echo '<br /><span class="description">' . $args['description'] . '</span>';
	}

	public function settings_validate( $settings ) {
		// Don't save, keeps the nag
		return false;

		// TODO: VALIDATE
		return $settings;
	}
}

$Automattic_Developer = new Automattic_Developer();

?>