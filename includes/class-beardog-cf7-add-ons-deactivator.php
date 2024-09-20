<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://beardog.digital
 * @since      1.0.0
 *
 * @package    Beardog_Cf7_Add_Ons
 * @subpackage Beardog_Cf7_Add_Ons/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Beardog_Cf7_Add_Ons
 * @subpackage Beardog_Cf7_Add_Ons/includes
 * @author     Jainish Brahmbhatt <jainish@beardog.digital>
 */
class Beardog_Cf7_Add_Ons_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		remove_role('tester');
	}

}
