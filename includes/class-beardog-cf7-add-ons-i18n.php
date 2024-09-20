<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://beardog.digital
 * @since      1.0.0
 *
 * @package    Beardog_Cf7_Add_Ons
 * @subpackage Beardog_Cf7_Add_Ons/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Beardog_Cf7_Add_Ons
 * @subpackage Beardog_Cf7_Add_Ons/includes
 * @author     Jainish Brahmbhatt <jainish@beardog.digital>
 */
class Beardog_Cf7_Add_Ons_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'beardog-cf7-add-ons',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
