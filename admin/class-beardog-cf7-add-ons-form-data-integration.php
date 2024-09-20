<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


class Beardog_CF7_Add_Ons_Form_Data_Integration
{

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        add_action('wpcf7_before_send_mail', [$this, 'store_inquiry']);
    }

    public function store_inquiry($contact_form)
    {
        global $wpdb;

        $submission = WPCF7_Submission::get_instance();
        if (!$submission) {
            return;
        }

        $form_id = $contact_form->id();
        $form_name = $contact_form->title();
        $posted_data = $submission->get_posted_data();
        $ip_address = $submission->get_meta('remote_ip');
        $location = $this->get_location_by_ip($ip_address);
        $fields = json_encode($posted_data);

        $full_location = explode(", ", $location);
        $city = $full_location[0];
        $region = $full_location[1];
        $country = $full_location[2];

        $table_name = $wpdb->prefix . 'beardog_cf7_inquiries';

        $wpdb->insert(
            $table_name,
            [
                'form_id' => $form_id,
                'form_name' => $form_name,
                'fields' => $fields,
                'ip_address' => $ip_address,
                'city' => $city,
                'region' => $region,
                'country' => $country,
            ]
        );
    }

    public function get_location_by_ip($ip_address)
    {
        $response = wp_remote_get("http://ip-api.com/json/$ip_address");

        if (is_wp_error($response)) {
            return 'Unknown';
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($data['status'] === 'fail') {
            return 'Unknown';
        }

        $location = "{$data['city']}, {$data['regionName']}, {$data['country']}";
        return $location;
    }
}
