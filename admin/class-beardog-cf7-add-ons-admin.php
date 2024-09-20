<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://beardog.digital
 * @since      1.0.0
 *
 * @package    Beardog_Cf7_Add_Ons
 * @subpackage Beardog_Cf7_Add_Ons/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Beardog_Cf7_Add_Ons
 * @subpackage Beardog_Cf7_Add_Ons/admin
 * @author     Jainish Brahmbhatt <jainish@beardog.digital>
 */

if (!class_exists('WP_List_Table')) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
/**
 * Class for handling the list table functionality
 */
class Beardog_Cf7_List_Table extends WP_List_Table
{
	private $form_id;
	private $year;
	private $month;
	private $country;

	public function __construct($args = array())
	{
		parent::__construct([
			'singular' => __('Inquiry', 'beardog-cf7-add-ons'),
			'plural'   => __('Inquiries', 'beardog-cf7-add-ons'),
			'ajax'     => false,
		]);
		$this->form_id = isset($args['form_id']) ? $args['form_id'] : 0;
		$this->year = isset($args['year']) ? $args['year'] : 0;
		$this->month = isset($args['month']) ? $args['month'] : 0;
		$this->country = isset($_GET['country']) ? sanitize_text_field($_GET['country']) : '';
	}

	public function get_columns()
	{
		$columns = [
			'cb' => '<input type="checkbox" />',
			'created_at' => __('Created At', 'beardog-cf7-add-ons'),
			'ip_address' => __('IP Address', 'beardog-cf7-add-ons'),
			// 'data' => __('Form Data', 'beardog-cf7-add-ons'),
		];

		if ($this->form_id) {
			$contact_form = WPCF7_ContactForm::get_instance($this->form_id);
			$form_properties = $contact_form->get_properties();
			// echo '<pre>';
			// var_dump($contact_form);
			$form_lines = explode("\n", $form_properties['form']);
			foreach ($form_lines as $line) {
				// if (preg_match('/\[(text|email|textarea|tel|url|radio|checkbox|select|number|range|quiz|file)\*?\s+([^\]]+)/', $line, $matches)) {
				if (preg_match_all('/\[(text|email|textarea|tel|url|number|date|select|checkbox|radio|quiz|file|hidden)\*? ([^\s\]]+)/', $line, $matches)) {
					// $firstSpacePos = strpos($matches[2], ' ');
					// if ($firstSpacePos && $firstSpacePos !== false) {
					// 	$firstWord = substr($matches[2], 0, $firstSpacePos);
					// }

					$field_names = array_map(function ($field) {
						// Extract only the name before any attributes like 'autocomplete'
						return preg_replace('/\s.*$/', '', $field);
					}, $matches[2]);
					// echo '<pre>';
					// 					var_dump($field_names); exit;
					$columns[$field_names[0]] = esc_html($field_names[0]);
					// echo '<pre>';
					// var_dump($matches[2]);
				}
			}
		}
		// echo '<pre>'; var_dump($columns);
		return $columns;
	}

	public function prepare_items()
	{
		global $wpdb;

		$table_name = $wpdb->prefix . 'beardog_cf7_inquiries';
		$per_page = 20;
		$current_page = $this->get_pagenum();
		$offset = ($current_page - 1) * $per_page;

		$query = "SELECT * FROM $table_name WHERE form_id = %d";
		$query_args = array($this->form_id);

		if ($this->year) {
			$query .= " AND YEAR(created_at) = %d";
			$query_args[] = $this->year;
		}
		if ($this->month) {
			$query .= " AND MONTH(created_at) = %d";
			$query_args[] = $this->month;
		}
		if ($this->country && $this->country !== 'all') {
			$query .= " AND country = %s";
			$query_args[] = $this->country;
		}

		$total_items = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM ($query) AS t", $query_args));

		$query .= " ORDER BY created_at DESC LIMIT %d OFFSET %d";
		$query_args[] = $per_page;
		$query_args[] = $offset;

		$this->items = $wpdb->get_results($wpdb->prepare($query, $query_args), ARRAY_A);

		foreach ($this->items as &$item) {
			foreach ($item as $key => &$value) {
				// Decode JSON if the value is stored as JSON
				// echo '<pre>';
				// var_dump($value);
				if ($this->isJson($value)) {
					$decoded_value = json_decode($value, true);
					// echo '<pre>'; var_dump($decoded_value);
					if (is_array($decoded_value)) {
						foreach ($decoded_value as $sub_key => $sub_value) {
							if (is_array($sub_value)) {
								// Convert array to a comma-separated string
								$decoded_value[$sub_key] = implode(', ', $sub_value);
							}
						}
						// Re-encode the JSON as a string with updated values
						$value = json_encode($decoded_value);
						// echo '<pre>'; var_dump($value);
					}
				}
			}
		}

		// echo '<pre>'; var_dump($this->items);

		$this->set_pagination_args([
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil($total_items / $per_page),
		]);

		$this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());
	}

	private function isJson($string)
	{
		json_decode($string);
		return (json_last_error() == JSON_ERROR_NONE);
	}


	public function column_default($item, $column_name)
	{
		switch ($column_name) {
			case 'ip_address':
				return $item[$column_name] . '<br/>' . $item['city'] . ', ' . $item['region'] . ', ' . $item['country'];
				break;
			case 'created_at':
				return date('Y-m-d H:i:s', strtotime($item[$column_name]));
				break;
		}

		$data = $item['fields'];
		$json_data = json_decode($data, true);
		if (is_array($json_data)) {
			foreach ($json_data as $key => $value) {
				switch ($column_name) {
					case $key:
						return wp_trim_words($value, 20, '...');
						break;
				}
			}
		}
	}

	private function format_form_data($data)
	{
		if (!is_array($data)) {
			return 'Invalid data format';
		}
		$output = '<ul>';
		foreach ($data as $key => $value) {
			$output .= sprintf('<li><strong>%s:</strong> %s</li>', esc_html($key), esc_html($value));
		}
		$output .= '</ul>';
		return $output;
	}

	public function column_cb($item)
	{
		return sprintf(
			'<input type="checkbox" name="inquiry[]" value="%s" />',
			$item['id']
		);
	}

	public function get_sortable_columns()
	{
		$sortable_columns = array(
			'id'  => array('id', false),
			'form_id' => array('form_id', false),
			'created_at'   => array('created_at', true),
		);
		return $sortable_columns;
	}

	public function get_bulk_actions()
	{
		$actions = [
			'delete' => __('Delete', 'beardog-cf7-add-ons'),
		];
		return $actions;
	}

	public function column_created_at($item)
	{
		$actions = array(
			'view' => sprintf('<a href="?page=%s&action=%s&inquiry=%s&form_id=%s">View</a>', $_REQUEST['page'], 'view', $item['id'], $this->form_id),
			// 'delete' => sprintf('<a href="?page=%s&action=%s&inquiry=%s&form_id=%s">Delete</a>', $_REQUEST['page'], 'delete', $item['id'], $this->form_id),
		);

		return sprintf(
			'%s %s',
			date('Y-m-d H:i:s', strtotime($item['created_at'])),
			$this->row_actions($actions)
		);
	}

	public function process_bulk_action()
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'beardog_cf7_inquiries';

		if ('delete' === $this->current_action()) {
			$ids = isset($_REQUEST['inquiry']) ? wp_parse_id_list($_REQUEST['inquiry']) : array();
			if (!empty($ids)) {
				foreach ($ids as $id) {
					$wpdb->delete($table_name, ['id' => $id], ['%d']);
				}
				wp_redirect(esc_url(add_query_arg()));
				exit;
			}
		}
	}
}

class Beardog_Cf7_Add_Ons_Admin
{
	private $plugin_name;
	private $version;
	private $list_table;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{
		$this->plugin_name = $plugin_name;
		$this->version = $version;

		if (is_admin()) {
			add_action('admin_menu', [$this, 'add_plugin_admin_menu']);
			add_action('admin_init', [$this, 'register_settings']);

			// Check if the "Test Forms" button was clicked
			// add_action('admin_post_test_forms_submit', [$this, 'handle_test_forms_submission']);
			add_action('updated_option', [$this, 'beardog_option_update_hook'], 10, 3);
			add_action('admin_menu', [$this, 'restrict_editor_access'], 999);


			// Hook into the admin_notices action to display notices
			add_action('admin_notices', [$this, 'beardog_admin_notices']);
		}
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{
		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/beardog-cf7-add-ons-admin.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{
		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/beardog-cf7-add-ons-admin.js', array('jquery'), $this->version, false);
	}

	public function add_plugin_admin_menu()
	{
		add_menu_page(
			'CF7 Add-ons', // Page title
			'CF7 Add-ons', // Menu title
			'manage_options', // Capability
			'cf7-add-ons', // Menu slug
			array($this, 'display_main_page'), // Callback function
			'dashicons-forms', // Icon URL
			//6 // Position
		);

		add_submenu_page(
			'cf7-add-ons',
			'Form Testing',
			'Form Testing',
			'manage_options',
			'cf7-form-testing',
			array($this, 'display_form_testing_page')
		);

		add_submenu_page(
			'cf7-add-ons',
			'Form Data',
			'Form Data',
			'manage_options',
			'cf7-form-data',
			array($this, 'display_form_data_page')
		);

		add_submenu_page(
			'cf7-add-ons',
			'Spam Protection',
			'Spam Protection',
			'manage_options',
			'cf7-spam-protection',
			array($this, 'display_spam_protection_page')
		);
	}

	public function display_main_page()
	{
		include_once 'partials/beardog-cf7-add-ons-admin-display.php';
	}

	public function display_form_testing_page()
	{
		// include_once 'partials/beardog-cf7-form-testing.php';
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
		<?php }
		if (isset($_POST['test_forms_submit'])) {
			$this->automate_cf7_form_submission();
		} ?>
	<?php
	}

	public function display_form_data_page()
	{
		$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
		$form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
		$year = isset($_GET['year']) ? intval($_GET['year']) : 0;
		$month = isset($_GET['month']) ? intval($_GET['month']) : 0;
		$country = isset($_GET['country']) ? sanitize_text_field($_GET['country']) : '';

		global $wpdb;
		$table_name = $wpdb->prefix . 'beardog_cf7_inquiries';

		$first_year = null;

		// Get the earliest entry date for the selected form
		$first_entry_date = $wpdb->get_var($wpdb->prepare(
			"SELECT MIN(created_at) FROM $table_name WHERE form_id = %d",
			$form_id
		));

		if ($form_id) {
			if ($first_entry_date) {
				$first_year = date('Y', strtotime($first_entry_date));
			}
		}

		if ($action === 'view' && isset($_GET['inquiry'])) {
			$this->display_single_entry($_GET['inquiry'], $form_id);
			return;
		}

		$forms = WPCF7_ContactForm::find();

		echo '<div class="wrap">';
		echo '<h1>Form Data</h1>';

		// Display filter form
		echo '<div class="wrap">';
		echo '<form method="get">';
		echo '<input type="hidden" name="page" value="cf7-form-data">';

		// Form ID dropdown
		echo '<select name="form_id" id="form_id">';
		echo '<option value="">Select Form</option>';
		foreach ($forms as $form) {
			$selected = selected($form_id, $form->id(), false);
			echo "<option value='" . esc_attr($form->id()) . "' $selected>" . esc_html($form->title()) . "</option>";
		}
		echo '</select>';

		// Country dropdown
		echo '<select name="country" id="country">';
		echo '<option value="">Select Country</option>';
		echo "<option value='United States'" . selected($country, 'United States', false) . ">United States</option>";
		echo '</select>';

		// Year dropdown
		echo '<select name="year" id="year">';
		echo '<option value="">Select Year</option>';
		if ($first_year) {
			for ($y = date('Y'); $y >= $first_year; $y--) {
				echo "<option value='$y'" . selected($year, $y, false) . ">$y</option>";
			}
		}
		echo '</select>';

		// Month dropdown
		echo '<select name="month" id="month">';
		echo '<option value="">Select Month</option>';
		for ($m = 1; $m <= 12; $m++) {
			echo "<option value='$m'" . selected($month, $m, false) . ">" . date('F', mktime(0, 0, 0, $m, 10)) . "</option>";
		}
		echo '</select>';

		echo '<input type="submit" value="Filter" class="button button-primary" id="filterData">';
		echo '</form>';

		// Only display the table if a form is selected
		if ($form_id) {
			$args = array(
				'form_id' => $form_id,
				'year' => $year,
				'month' => $month,
				'country' => isset($_GET['country']) ? sanitize_text_field($_GET['country']) : '',
			);
			$this->list_table = new Beardog_Cf7_List_Table($args);
			$this->list_table->process_bulk_action();
			$this->list_table->prepare_items();

			echo '<form method="post" class="beardog-cf7-data-list">';
			$this->list_table->display();
			echo '</form>';
		} else {
			echo '<p>Please select a form to view its data.</p>';
		}
		echo '</div>';
	?>
		<script>
			jQuery(document).ready(function() {
				var triggered = false;
				// alert(triggered);
				jQuery('#form_id').on('change', function() {
					// alert('1');
					if (jQuery(this).val()) {
						// alert('2');
						jQuery('#year, #month').show();
						if (!triggered) {
							jQuery('#filterData').trigger('click');
							triggered = true;
						}
					} else {
						jQuery('#year, #month').hide().val('');
						triggered = false;
					}
				});
			});
		</script>
<?php
	}

	public function display_single_entry($inquiry_id, $form_id)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'beardog_cf7_inquiries';
		$entry = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d AND form_id = %d", $inquiry_id, $form_id), ARRAY_A);

		if (!$entry) {
			wp_die('Entry not found');
		}

		echo '<div class="wrap">';
		echo '<h1>Form Entry Details</h1>';
		echo '<table class="widefat">';
		echo '<tr><th>Created At</th><td>' . esc_html(date('Y-m-d H:i:s', strtotime($entry['created_at']))) . '</td></tr>';
		echo '<tr><th>IP Address</th><td>' . esc_html($entry['ip_address']) . '</td></tr>';
		echo '<tr><th>Location</th><td>' . esc_html($entry['location']) . '</td></tr>';

		$fields = json_decode($entry['fields'], true);
		if (is_array($fields)) {
			foreach ($fields as $key => $value) {
				echo '<tr><th>' . esc_html($key) . '</th><td>' . esc_html($value) . '</td></tr>';
			}
		}

		echo '</table>';
		// echo '<p><a href="' . esc_url(add_query_arg(array('page' => 'cf7-form-data', 'form_id' => $form_id))) . '" class="button">Back to List</a></p>';
		$back_url = get_admin_url() . 'admin.php?page=cf7-form-data&form_id=' . $form_id . '';
		echo '<p><a href="' . esc_url($back_url) . '" class="button">Back to List</a></p>';
		echo '</div>';
	}

	public function display_spam_protection_page()
	{
		include_once 'partials/beardog-cf7-spam-protection.php';
	}

	public function register_settings()
	{
		register_setting('cf7-form-testing-settings-group', 'cf7_form_testing_mode', array($this, 'sanitize_checkbox'));
		register_setting('cf7-form-testing-settings-group', 'cf7_form_testing_emails', array($this, 'sanitize_emails'));
	}

	public function sanitize_checkbox($input)
	{
		return $input ? '1' : '';
	}

	public function sanitize_emails($input)
	{
		$emails = explode(',', $input);
		$sanitized_emails = array();
		foreach ($emails as $email) {
			if (is_email(trim($email))) {
				$sanitized_emails[] = sanitize_email(trim($email));
			}
		}
		return implode(', ', $sanitized_emails);
	}

	public function get_all_cf7_forms()
	{
		$args = array(
			'post_type' => 'wpcf7_contact_form',
			'posts_per_page' => -1, // Retrieve all forms
		);

		$cf7_posts = get_posts($args);

		$forms = array();
		foreach ($cf7_posts as $post) {
			$forms[] = array(
				'id' => $post->ID,
				'title' => $post->post_title,
				'content' => $post->post_content,
			);
		}
		return $forms;
	}

	function beardog_option_update_hook($option_name, $old_value, $new_value)
	{
		// Check if the updated option is the one you care about
		if ('cf7_form_testing_mode' === $option_name) {
			$this->get_cf7_form_content_by_id();
		}
	}

	public function get_cf7_form_content_by_id()
	{
		global $wpdb;
		$forms = $this->get_all_cf7_forms();

		$quiz_shortcode = '[quiz quiz-math class:quiz class:form-control id:quiz-math "1+1=?|2" "1+2=?|3" "1+3=?|4" "1+4=?|5" "1+5=?|6" "1+6=?|7" "1+7=?|8" "1+8=?|9" "1+9=?|10" "2+1=?|3" "2+2=?|4" "2+3=?|5" "2+4=?|6" "2+5=?|7" "2+6=?|8" "2+7=?|9" "2+8=?|10" "2+9=?|11" "3+1=?|4" "3+2=?|5" "3+3=?|6" "3+4=?|7" "3+5=?|8" "3+6=?|9" "3+7=?|10" "3+8=?|11" "3+9=?|12" "4+1=?|5" "4+2=?|6" "4+3=?|7" "4+4=?|8" "4+5=?|9" "4+6=?|10" "4+7=?|11" "4+8=?|12" "4+9=?|13" "5+1=?|6" "5+2=?|7" "5+3=?|8" "5+4=?|9" "5+5=?|10" "5+6=?|11" "5+7=?|12" "5+8=?|13" "5+9=?|14" "6+1=?|7" "6+2=?|8" "6+3=?|9" "6+4=?|10" "6+5=?|11" "6+6=?|12" "6+7=?|13" "6+8=?|14" "6+9=?|15" "7+1=?|8" "7+2=?|9" "7+3=?|10" "7+4=?|11" "7+5=?|12" "7+6=?|13" "7+7=?|14" "7+8=?|15" "7+9=?|16" "8+1=?|9" "8+2=?|10" "8+3=?|11" "8+4=?|12" "8+5=?|13" "8+6=?|14" "8+7=?|15" "8+8=?|16" "8+9=?|17" "9+1=?|10" "9+2=?|11" "9+3=?|12" "9+4=?|13" "9+5=?|14" "9+6=?|15" "9+7=?|16" "9+8=?|17" "9+9=?|18"]';

		$quiz_reverse = 'We are happy to help!';

		if (get_option('cf7_form_testing_mode')) {
			if (get_option('cf7_form_testing_emails') != '') {
				// echo 'yes';
				foreach ($forms as $form) {
					$query = $wpdb->prepare("SELECT post_content FROM {$wpdb->prefix}posts WHERE post_type = 'wpcf7_contact_form' AND ID = " . $form['id']);

					// Execute the query
					$form_content = $wpdb->get_var($query);

					// echo '<pre> form_content <br><br><br>';
					// var_dump($form_content);

					// $subject_meta = get_post_meta($form['id'], '_mail', $form_content);

					// if ($subject_meta) {
					// 	$subject_meta_array = maybe_unserialize($subject_meta);
					// 	if (isset($subject_meta_array['subject'])) {
					// 		// Get the subject value
					// 		$subject = $subject_meta_array['subject'];

					// 		// Output the subject
					// 		// echo 'Subject: ' . esc_html($subject);
					// 		// echo 'Subject <br><br><br>';
					// 		// var_dump($subject);
					// 	}
					// } else {
					// 	$subject = 'BEARDOG DIGITAL Lead:';
					// }

					$delimiterPattern = "/1\r\nBEARDOG DIGITAL Lead:|1\nBEARDOG DIGITAL Lead:|1\rBEARDOG DIGITAL Lead:/";

					// echo 'delimiterPattern <br><br><br>';
					// var_dump($delimiterPattern);

					// Split the content using preg_split and the regular expression
					$parts = preg_split($delimiterPattern, $form_content, 2);

					// echo 'parts <br><br><br>';
					// var_dump($parts);

					// The content before the delimiter will be in the first part of the array
					$desiredContent = $parts[0];

					// echo 'desiredContent <br><br><br>';
					// var_dump($desiredContent);

					$updated_content = str_replace($quiz_shortcode, $quiz_reverse, $desiredContent);

					// var_dump($updated_content);

					$wpdb->update(
						"{$wpdb->prefix}posts",
						['post_content' => $updated_content],
						['ID' => $form['id']]
					);

					update_post_meta($form['id'], '_form', $updated_content);
				}
			}
		} else {
			// echo 'no';
			foreach ($forms as $form) {
				$query = $wpdb->prepare("SELECT post_content FROM {$wpdb->prefix}posts WHERE post_type = 'wpcf7_contact_form' AND ID = " . $form['id']);

				// Execute the query
				$form_content = $wpdb->get_var($query);

				// $subject_meta = get_post_meta($form['id'], '_mail', $form_content);

				// if ($subject_meta) {
				// 	$subject_meta_array = maybe_unserialize($subject_meta);
				// 	if (isset($subject_meta_array['subject'])) {
				// 		// Get the subject value
				// 		$subject = $subject_meta_array['subject'];

				// 		// Output the subject
				// 		echo 'Subject: ' . esc_html($subject);
				// 	}
				// } else {
				// 	$subject = 'BEARDOG DIGITAL Lead:';
				// }

				$delimiterPattern = "/1\r\nBEARDOG DIGITAL Lead:|1\nBEARDOG DIGITAL Lead:|1\rBEARDOG DIGITAL Lead:/";

				// Split the content using preg_split and the regular expression
				$parts = preg_split($delimiterPattern, $form_content, 2);

				// The content before the delimiter will be in the first part of the array
				$desiredContent = $parts[0];
				$updated_content = str_replace($quiz_reverse, $quiz_shortcode, $desiredContent);

				$wpdb->update(
					"{$wpdb->prefix}posts",
					['post_content' => $updated_content], // Data array
					['ID' => $form['id']] // Where array
				);

				update_post_meta($form['id'], '_form', $updated_content);
			}
		}
	}

	public function automate_cf7_form_submission()
	{

		$forms = $this->get_all_cf7_forms();

		foreach ($forms as $form) {

			$form_id = $form['id'];
			$form1 = WPCF7_ContactForm::get_instance($form_id);

			$url = get_bloginfo('wpurl') . '/wp-json/contact-form-7/v1/contact-forms/' . $form_id . '/feedback';
			$boundary = wp_generate_uuid4();

			if ($form1) {
				$form_properties = $form1->get_properties();
				$form_content = $form_properties['form'];

				// echo '<pre>'; print_r($form_content);

				preg_match_all('/\[([a-zA-Z]+)(.*?)\]/', $form_content, $matches);
				$fields = $matches[0];
				$body = '';

				$characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
				$randomString = '';
				for ($i = 0; $i < 2; $i++) {
					$randomString .= $characters[rand(0, strlen($characters) - 1)];
				}

				foreach ($fields as $field) {
					// echo '<pre>'; var_dump($field);
					if (strpos($field, 'submit') !== false || strpos($field, 'response') !== false || strpos($field, 'quiz') !== false) {
						continue;
					}

					if (strpos($field, '[select') === 0) {
						preg_match_all('/"(.*?)"/', $field, $select_matches);
						$random_select = array_rand($select_matches[1]);
						$select_value = $select_matches[1][$random_select];
					}

					if (strpos($field, '[checkbox') === 0) {
						preg_match_all('/"(.*?)"/', $field, $checkbox_matches);
						$random_checkbox = array_rand($checkbox_matches[1]);
						$checkbox_value = $checkbox_matches[1][$random_checkbox];
					}

					if (strpos($field, '[radio') === 0) {
						preg_match_all('/"(.*?)"/', $field, $radio_matches);
						$random_radio = array_rand($radio_matches[1]);
						$radio_value = $radio_matches[1][$random_radio];
					}

					$parts = explode(' ', trim($field, "[]"));
					// echo '<pre>'; var_dump($parts);
					$tagName = rtrim($parts[0], "*");
					if (count($parts) >= 2) {
						$desiredString = $parts[1];
						// echo "Tag: " . $tagName . ", String: " . $desiredString . "<br>";

						if ($tagName == "text") {
							$value = 'User-' . $randomString;
						}
						if ($tagName == "email") {
							$value = 'email@example.com';
						}
						if ($tagName == "tel") {
							$value = '1234567890';
						}
						if ($tagName == "textarea") {
							$value = 'This is a test message from BearDog Digital.';
						}
						if ($tagName == "url") {
							$value = 'http://example.com';
						}
						if ($tagName == "number") {
							$value = '12345';
						}
						if ($tagName == "date") {
							$value = date('Y-m-d');
						}
						if ($tagName == "select") {
							$value = $select_value;
							// $value = 'sdsds';
						}
						if ($tagName == "checkbox") {
							$value = $checkbox_value;
						}
						if ($tagName == "radio") {
							$value = $radio_value;
						}

						$body .= '--' . $boundary . "\r\n";
						$body .= 'Content-Disposition: form-data; name="' . $desiredString . '"' . "\r\n\r\n";
						$body .= $value . "\r\n";
					} else {
						echo "Not enough parts in the field string: " . $field . "<br>";
					}
				}
				$body .= '--' . $boundary . "\r\n";
				$body .= 'Content-Disposition: form-data; name="_wpcf7_unit_tag"' . "\r\n\r\n";
				$body .= 'wpcf7-f' . $form_id . '-o1' . "\r\n";
				$body .= '--' . $boundary . '--';
			}

			$nonce = wp_create_nonce('wp_rest');
			$cookies = [];
			foreach ($_COOKIE as $name => $value) {
				$cookies[] = new WP_Http_Cookie(
					['name' => $name, 'value' => $value]
				);
			}

			// Prepare the arguments for wp_remote_post.
			$args = [
				'body' => $body,
				'timeout' => '45',
				'redirection' => '5',
				'httpversion' => '1.0',
				'blocking' => true,
				'headers' => [
					'X-WP-Nonce' => $nonce,
					'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
				],
				'cookies' => $cookies,
			];

			$response = wp_remote_post($url, $args);

			if (is_wp_error($response)) {
				$error_message = $response->get_error_message();
				echo "Something went wrong: $error_message";
			} else {
				// echo 'Response:<pre>';
				// print_r($response);
				// echo '</pre>';

				// Assuming $response['body'] contains your JSON string
				$json_string = $response['body'];

				// Decode the JSON string into an associative array
				$data = json_decode($json_string, true);

				// Now you can access each element using its key
				$contact_form_id = $data['contact_form_id'];
				$status = $data['status'];
				$message = $data['message'];
				$posted_data_hash = $data['posted_data_hash'];
				$into = $data['into'];
				$invalid_fields = $data['invalid_fields']; // This is an array

				// echo '<pre>'; print_r($invalid_fields);
				if ($status === 'mail_sent') {
					$class = 'notice-box-success';
				} else {
					$class = 'notice-box-error';
				}

				// Example of how to use the data
				echo "<div class='super-parent'>";
				echo "<div class='status-parent'>";
				echo "<div class='testing-status notice-box " . $class . "'>";
				echo "Contact Form ID: " . $contact_form_id . "<br>";
				echo "Status: " . $status . "<br>";
				echo "Message: " . $message . "<br>";
				// echo "Posted Data Hash: " . $posted_data_hash . "<br>";
				// echo "Into: " . $into . "<br>";
				echo "</div>";
				

				// If you need to work with 'invalid_fields' and it's an array
				if (!empty($invalid_fields)) {
					foreach ($invalid_fields as $field) {
						// Process each invalid field. Example:
						// echo '<br><br><br><br>'; print_r($field);
						echo "<div class='invalid-fields-data'>";
						echo "<strong>Invalid Field:</strong> <span>" . $field['field'] . "</span><br>";
						echo "<strong>Invalid Field Message:</strong> <span>" . $field['message'] . "</span><br>";
						// echo "<strong>Invalid Field Error ID:</strong> " . $field['error_id'] . "<br>";
						echo "</div>";
					}
				} else {
					echo "<strong>No invalid fields.</strong>";
				}
				echo "</div>";

				if ($status === 'mail_sent') {
					echo "<iframe src='/thank-you/'></iframe>";
				} else {
					echo "<iframe src='/404/'></iframe>";
				}
				echo "</div>";
			}
		}
	}

	public function beardog_admin_notices()
	{
		if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
			// Display a success notice
			echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
		} elseif (isset($_GET['settings-error']) && $_GET['settings-error']) {
			// Display an error notice
			echo '<div class="notice notice-error is-dismissible"><p>Error saving settings!</p></div>';
		}
	}

	public function restrict_editor_access() {
	    if (current_user_can('tester')) {
	        global $menu;
	        global $submenu;

	        // Remove menu items from admin dashboard
	        remove_menu_page('index.php');
	        remove_menu_page('edit.php?post_type=page');
	        remove_menu_page('upload.php');
	        remove_menu_page('edit-comments.php');
	        remove_menu_page('themes.php');
	        remove_menu_page('plugins.php');
	        remove_menu_page('users.php');
	        remove_menu_page('tools.php');
	        remove_menu_page('options-general.php');
	        remove_menu_page('edit.php?post_type=your_custom_post_type');
	        remove_menu_page('wpcf7');
	        remove_menu_page('theme-general-settings');
	        remove_menu_page('edit.php?post_type=agr_google_review');
	        remove_menu_page('profile.php');
	        remove_menu_page('wpseo_workouts');
	        remove_menu_page('wpseo_redirects');
	        remove_menu_page('edit.php?post_type=city-service');
	        remove_menu_page('edit.php?post_type=attorney');
	        remove_menu_page('edit.php?post_type=practicearea');
	        remove_menu_page('edit.php?post_type=practice-area');
	        remove_menu_page('edit.php?post_type=practice-areas');
	        remove_menu_page('awesome-google-review');
	        remove_menu_page('edit.php?post_type=acf-field-group');
	        remove_menu_page('beardog-seo-enhancer');
	        remove_menu_page('cfdb7-list.php');
	        remove_menu_page('export-personal-data.php');
	        remove_menu_page('tws-activate-contact-forms-anti-spam');
	        remove_menu_page('litespeed');
	        
	        
	        // Optionally remove submenus
	        // Uncomment any lines to remove submenu items
	        // remove_submenu_page('edit.php', 'edit-tags.php?taxonomy=category'); // Categories
	        // remove_submenu_page('edit.php', 'edit-tags.php?taxonomy=post_tag'); // Tags
	    }
	}
}
