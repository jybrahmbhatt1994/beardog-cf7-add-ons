<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<section class="main-sec">
    <h1>Hello Folks!</h1>
    <h2>This plugin enhances Contact Form 7 functionalities with exclusive features tailored for Beardog Digital.</h2>
    <div class="btn-group">
        <a href="<?php echo admin_url('admin.php?page=cf7-form-testing'); ?>" class="custom-btn btn-16">Test Forms</a>
        <a href="<?php echo admin_url('admin.php?page=cf7-form-data'); ?>" class="custom-btn btn-16">Form Data</a>
        <a href="<?php echo admin_url('admin.php?page=cf7-spam-protection'); ?>" class="custom-btn btn-16">Spam Protection</a>
    </div>
</section>