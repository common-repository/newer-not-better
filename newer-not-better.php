<?php
/**
 * Newer Not Better
 *
 * Plugin Name:       Newer Not Better
 * Plugin URI:        https://adamainsworth.co.uk/plugins/
 * Description:       Prevents selected plugins bugging you about updates.
 * Version:           1.0.0
 * Author:            Adam Ainsworth
 * Author URI:        https://adamainsworth.co.uk/
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       newer-not-better
 * Domain Path:       /languages
 * Requires at least: 4.0.4
 * Tested up to:      5.8.1
 */

 // redirect if some comes directly
if ( ! defined( 'WPINC' ) && ! defined( 'ABSPATH' ) ) {
	header('Location: /'); die;
}

// check that we're not defined somewhere else
if ( ! class_exists( 'Newer_Not_Better' ) ) {
	class Newer_Not_Better {
		private function __construct() {}

		public static function activate() {
	        if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			// any activation code here
		}

		public static function deactivate() {
	        if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			// any deactivation code here
		}

		public static function uninstall() {
	        if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			if ( __FILE__ !== WP_UNINSTALL_PLUGIN ) {
				return;
			}
			 
			$option_name = 'nnb_options';
			delete_option($option_name);
			delete_site_option($option_name);
		}

		public static function init() {
			add_filter( 'plugin_action_links', [ __CLASS__, 'add_links' ], 10, 2 );
			add_filter( 'site_transient_update_plugins', [__CLASS__, 'disable_plugin_updates'], 10, 1 );
			add_action( 'admin_menu', [__CLASS__, 'add_admin_menu'] );
			add_action( 'admin_init', [__CLASS__, 'options_init'] );
		}

		// add links to section on plugins page
		public static function add_links( $links, $file ) {
			// TODO - add enable / disable to plugins page

			if ( $file === 'newer-not-better/newer-not-better.php' && current_user_can( 'manage_options' ) ) {	
				
				$links = (array) $links;
				$links[] = sprintf( '<a href="%s">%s</a>', admin_url( 'options-general.php?page=newer_not_better' ), esc_html__( 'Settings', 'newer-not-better' ) );
			}

			return $links;
		}

		// get a list of plugins in an array
		public static function get_plugins() {
			// TODO - get all plugins, not just active ones
			// TODO - get friendly names, not just paths
			$options = get_option( 'nnb_options' );
			$plugin_paths_raw = $options['plugins'];
			$plugin_paths = preg_split('/\r\n|\r|\n/', $plugin_paths_raw);

			return $plugin_paths;
		}
		
		// run through all plugins in options and remove them from update list
		public static function disable_plugin_updates( $value ) {
			if ( isset($value) && is_object($value) ) {
				$plugin_paths = self::get_plugins();
				
				foreach( $plugin_paths as $plugin_path ) {
					if ( isset( $value->response[$plugin_path] ) ) {

						unset( $value->response[$plugin_path] );
					}
				}
			}

			return $value;
		}		
		
		// add the item to the admin menu
		public static function add_admin_menu() { 
			add_options_page(
				'Newer Not Better',
				'Newer Not Better',
				'manage_options',
				'newer_not_better',
				[__CLASS__, 'options_page_render']
			);
		}
		
		// set up options and settings fields
		public static function options_init() { 
			register_setting( 'nnb_options', 'nnb_options' );
		
			add_settings_section(
				'nnb_options_section', 
				__( '', 'newer-not-better' ), 
				[__CLASS__, 'settings_render'], 
				'nnb_options'
			);
		
			add_settings_field( 
				'plugins', 
				__( 'Plugin paths', 'newer-not-better' ), 
				[__CLASS__, 'plugins_render'], 
				'nnb_options', 
				'nnb_options_section' 
			);
		}
		
		// render options page
		public static function options_page_render() { 
			?>
				<form action='options.php' method='post'>		
					<h2>Newer Not Better</h2>
		
					<?php
						settings_fields( 'nnb_options' );
						do_settings_sections( 'nnb_options' );
						submit_button();
					?>
				</form>
			<?php
		}
		
		// render settings section
		public static function settings_render() { 
			?><p><strong><?php esc_html_e( 'Enter the required plugin paths from below, one on each line.', 'newer-not-better' ); ?></strong></p><?php

			$active_plugins = get_option('active_plugins');
			?>
				<ul>
					<?php foreach( $active_plugins as $active_plugin_path ) : ?>
						<li><?php echo( esc_html( $active_plugin_path ) ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php
		}
		
		// render settings fields
		public static function plugins_render() {
			$options = get_option( 'nnb_options' );
			// TO DO add JS controlled checkboxes to control the contents of the text area, will will be hidden
			
			printf(
				'<textarea cols="80" rows="5" name="nnb_options[plugins]">%s</textarea>',
				esc_textarea( $options['plugins'] )
			);
		}		
	}

	register_activation_hook( __FILE__, [ 'Newer_Not_Better', 'activate' ] );
	register_deactivation_hook( __FILE__, [ 'Newer_Not_Better', 'deactivate' ] );
	register_uninstall_hook( __FILE__, [ 'Newer_Not_Better', 'uninstall' ] );
	add_action( 'plugins_loaded', [ 'Newer_Not_Better', 'init' ] );
}
