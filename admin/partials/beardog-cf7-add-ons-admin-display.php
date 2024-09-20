<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://beardog.digital
 * @since      1.0.0
 *
 * @package    Beardog_Cf7_Add_Ons
 * @subpackage Beardog_Cf7_Add_Ons/admin/partials
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<section class="main-sec">
    <h1>Hello Folks!</h1>
    <h2>This plugin enhances Contact Form 7 functionalities with exclusive features tailored for Beardog Digital.</h2>
    <div class="btn-group">
        <a href="<?php echo admin_url('admin.php?page=cf7-form-testing'); ?>" class="custom-btn btn-16">Test Forms</a>
        <a href="<?php echo admin_url('admin.php?page=cf7-form-data'); ?>" class="custom-btn btn-16">Form Data</a>
        <a href="<?php echo admin_url('admin.php?page=cf7-spam-protection'); ?>" class="custom-btn btn-16">Spam Protection</a>
    </div>
</section>