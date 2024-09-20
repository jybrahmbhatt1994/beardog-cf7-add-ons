<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// $run_test = new Beardog_Cf7_Add_Ons_Testing_Form_Integration();
// $run_test->get_all_cf7_forms();
?>

<div class="testing-sec">
    <h1>Form Testing Settings</h1>
    <form method="post" action="options.php" style="max-width: 500px;">
        <?php
        settings_fields('cf7-form-testing-settings-group');
        do_settings_sections('cf7-form-testing-settings-group');
        ?>
        <div style="margin-bottom:10px;margin-top:30px;">
            <input type="checkbox" name="cf7_form_testing_mode" <?php echo get_option('cf7_form_testing_mode') ? 'checked' : ''; ?> />
            <label for="cf7_form_testing_mode">Send email to below IDs</label>
        </div>
        <div style="margin-bottom: 20px;">
            <label for="cf7_form_testing_emails">Enter testing email IDs:</label><br>
            <input type="email" multiple name="cf7_form_testing_emails" value="<?php echo esc_attr(get_option('cf7_form_testing_emails')); ?>" style="width:100%;" required />
        </div>
        <div>
            <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes" style="width: 100%;">
        </div>
    </form>
</div>
<?php if (get_option('cf7_form_testing_mode') && get_option('cf7_form_testing_emails') != '') { ?>
    <h2>WP Form Tester</h2>
    <form action="" method="post" class="test-forms">
        <input type="hidden" name="test_forms_submit" value="1">
        <?php submit_button('Test Forms'); ?>
    </form>
<?php } ?>
