<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Beardog_Cf7_Add_Ons_Testing_Form_Integration
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    public function __construct()
    {
        // Hook into Contact Form 7 before sending email
        add_action('wpcf7_before_send_mail', [$this, 'modify_email_properties']);

        // Register REST API endpoint
        add_action('rest_api_init', [$this, 'register_rest_api']);
    }

    /**
     * Modify email properties if the test mode is active.
     *
     * @param WPCF7_ContactForm $contact_form
     */
    public function modify_email_properties($contact_form)
    {
        $submission = WPCF7_Submission::get_instance();
        if (!$submission) {
            return;
        }

        $mail = $contact_form->prop('mail'); // Get the main mail property

        // Check if test mode is active and the test email field is not empty
        if (get_option('cf7_form_testing_mode') && get_option('cf7_form_testing_emails') != '') {
            $test_emails = explode(',', get_option('cf7_form_testing_emails'));
            $valid_emails = array_filter(array_map('trim', array_map('sanitize_email', $test_emails)), 'is_email');

            if (!empty($valid_emails)) {
                $test_email_string = implode(',', $valid_emails);

                // Override the recipient, CC, and BCC if valid test emails are available
                $mail['recipient'] = $test_email_string;
                $mail['additional_headers'] = "Cc: " . $test_email_string . "\r\nBcc: " . $test_email_string;

                $contact_form->set_properties(array('mail' => $mail));
            }
        }
    }

    /**
     * Register REST API endpoint for client mode.
     */
    public function register_rest_api()
    {
        register_rest_route('clientids/v1', '/is_client/', array(
            'methods' => 'GET',
            'callback' => [$this, 'get_clients_mode'],
        ));
    }

    /**
     * Get CF7 clients mode for the REST API.
     *
     * @return WP_REST_Response
     */
    public function get_clients_mode()
    {
        $value = get_option('cf7_form_testing_mode'); // Retrieve the option value
        $booleanValue = boolval($value); // Cast the value to boolean
        return new WP_REST_Response($booleanValue, 200); // Return the boolean value
    }
}
