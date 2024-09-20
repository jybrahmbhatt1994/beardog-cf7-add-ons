<?php

/**
 * Fired during plugin activation
 *
 * @link       https://beardog.digital
 * @since      1.0.0
 *
 * @package    Beardog_Cf7_Add_Ons
 * @subpackage Beardog_Cf7_Add_Ons/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Beardog_Cf7_Add_Ons
 * @subpackage Beardog_Cf7_Add_Ons/includes
 * @author     Jainish Brahmbhatt <jainish@beardog.digital>
 */
class Beardog_Cf7_Add_Ons_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		
		//Create table for store CF7 inquiries
		global $wpdb;
		$table_name = $wpdb->prefix . 'beardog_cf7_inquiries';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            form_id mediumint(9) NOT NULL,
            form_name text NOT NULL,
            fields text NOT NULL,
            ip_address varchar(100) NOT NULL,
            city text NOT NULL,
            region text NOT NULL,
            country text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        //Add tester role
        remove_role('tester');

        add_role('tester', 'Tester', []);
        $role = get_role('tester');
    	$role->add_cap('manage_options');
	}

}
