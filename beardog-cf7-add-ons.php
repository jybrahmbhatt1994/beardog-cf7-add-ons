<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://beardog.digital
 * @since             1.0.0
 * @package           Beardog_Cf7_Add_Ons
 *
 * @wordpress-plugin
 * Plugin Name:       Beardog CF7 Add-ons
 * Plugin URI:        https://beardog.digital
 * Description:       All required functionalities of Contact Form 7, Exclusively for Beardog Digital.
 * Version:           1.0.0
 * Author:            Jainish Brahmbhatt
 * Author URI:        https://beardog.digital/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       beardog-cf7-add-ons
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'BEARDOG_CF7_ADD_ONS_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-beardog-cf7-add-ons-activator.php
 */
function activate_beardog_cf7_add_ons() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-beardog-cf7-add-ons-activator.php';
	Beardog_Cf7_Add_Ons_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-beardog-cf7-add-ons-deactivator.php
 */
function deactivate_beardog_cf7_add_ons() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-beardog-cf7-add-ons-deactivator.php';
	Beardog_Cf7_Add_Ons_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_beardog_cf7_add_ons' );
register_deactivation_hook( __FILE__, 'deactivate_beardog_cf7_add_ons' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-beardog-cf7-add-ons.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_beardog_cf7_add_ons() {

	$plugin = new Beardog_Cf7_Add_Ons();
	$plugin->run();

}
run_beardog_cf7_add_ons();
