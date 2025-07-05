<?php
// Prevent direct access to the file
// Shortcode of this form is [edit_vendor_business_fields]
//
//Below form will edit the Custom acf business fileds which we have added through custom registration form.
// 
add_action('dokan_dashboard_content_inside_after', function() {
    global $wp;
    if ( isset($wp->query_vars['edit-account']) ) {
        echo do_shortcode('[edit_vendor_business_fields]');
    }
});
add_action('dokan_dashboard_content_inside_after', function()  {
    global $wp;
    if ( isset($wp->query_vars['edit-account']) ) {
        ?>
        <style>
    
          .dokan-btn, .btn-primary
            {
	         margin-top: 20px !important;
             }
        </style>
        <?php
    }
});
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register the shortcode and form processing
 */
function initialize_vendor_business_fields_editor() {
    add_shortcode('edit_vendor_business_fields', 'render_vendor_business_fields_form');
    add_action('init', 'process_vendor_business_fields_submission');
}
add_action('after_setup_theme', 'initialize_vendor_business_fields_editor');

/**
 * Render the form for editing vendor business fields
 */
function render_vendor_business_fields_form() {
    if (!is_user_logged_in()) {
        return '<p>' . esc_html__('You must be logged in to edit your business fields.', 'text-domain') . '</p>';
    }

    $user_id = get_current_user_id();

    // Retrieve current field values from user meta
    $business_registration_number = get_user_meta($user_id, 'business_registration_number', true) ?: '';
    $tax_number = get_user_meta($user_id, 'tax_number', true) ?: '';
    $registered_business_name = get_user_meta($user_id, 'registered_business_name', true) ?: '';
    $identification_docs_id = get_user_meta($user_id, 'identification_docs', true) ?: '';
    $business_type = get_user_meta($user_id, 'business_type', true) ?: '';

    // Handle success/errors via session
    if (!session_id()) {
        session_start();
    }
    $success = isset($_SESSION['edit_business_fields_success']) ? $_SESSION['edit_business_fields_success'] : false;
    $errors = isset($_SESSION['edit_business_fields_errors']) ? $_SESSION['edit_business_fields_errors'] : [];
    unset($_SESSION['edit_business_fields_success'], $_SESSION['edit_business_fields_errors']);

    // Output the form
    ob_start();
    ?>
    <div class="vendor-business-fields-editor">
        <?php if ($success) : ?>
            <div class="alert alert-success">
                <?php esc_html_e('Business fields updated successfully.', 'text-domain'); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($errors)) : ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error) : ?>
                        <li><?php echo esc_html($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="vendor-business-fields-form">
            <?php wp_nonce_field('edit_vendor_business_fields', 'edit_business_fields_nonce'); ?>

            <!-- Business Registration Number/License -->
            <div class="form-group">
                <label for="business_registration_number">
                    <?php esc_html_e('Business Registration Number/License', 'text-domain'); ?> <span class="required">*</span>
                </label>
                <input type="text" name="business_registration_number" id="business_registration_number" 
                       value="<?php echo esc_attr($business_registration_number); ?>" class="form-control" required>
            </div>

            <!-- GST or Tax Information -->
            <div class="form-group">
                <label for="tax_number">
                    <?php esc_html_e('GST or Tax Information', 'text-domain'); ?> <span class="required">*</span>
                </label>
                <input type="text" name="tax_number" id="tax_number" 
                       value="<?php echo esc_attr($tax_number); ?>" class="form-control" required>
            </div>

            <!-- Registered Business Name -->
            <div class="form-group">
                <label for="registered_business_name">
                    <?php esc_html_e('Registered Business Name', 'text-domain'); ?> <span class="required">*</span>
                </label>
                <input type="text" name="registered_business_name" id="registered_business_name" 
                       value="<?php echo esc_attr($registered_business_name); ?>" class="form-control" required>
            </div>
            
            
              <!-- Business Type -->
            <div class="form-group">
                <label for="business_type">
                    <?php esc_html_e('Business Type', 'text-domain'); ?> <span class="required">*</span>
                </label>
                <select name="business_type" id="business_type" class="form-control" required>
                    <option value=""><?php esc_html_e('Select Business Type', 'text-domain'); ?></option>
                    <option value="Tour operator" <?php selected($business_type, 'Tour operator'); ?>>
                        <?php esc_html_e('Tour operator', 'text-domain'); ?>
                    </option>
                    <option value="Travel agency" <?php selected($business_type, 'Travel agency'); ?>>
                        <?php esc_html_e('Travel agency', 'text-domain'); ?>
                    </option>
                    <option value="Other" <?php selected($business_type, 'Other'); ?>>
                        <?php esc_html_e('Other', 'text-domain'); ?>
                    </option>
                </select>
            </div>
            
<br />
            <!-- Identification Documents -->
            <div class="form-group">
                <label>
                    <?php esc_html_e('Identification Documents', 'text-domain'); ?> <span class="required">*</span>
                </label>
                <br />
                <?php if ($identification_docs_id) : ?>
                    <a href="<?php echo esc_url(wp_get_attachment_url($identification_docs_id)); ?>" target="_blank">
                        <?php esc_html_e('View Current Document', 'text-domain'); ?>
                    </a><br>
                <?php endif; ?>
                <input type="file" name="identification_docs" id="identification_docs" 
                       accept=".pdf,.jpg,.jpeg,.png">
                       <br />
                <small><?php esc_html_e('Allowed file types: PDF, JPG, JPEG, PNG. Max file size: 5MB', 'text-domain'); ?></small>
            </div>

          

            <!-- Submit Button -->
            <div class="form-group">
                <input type="submit" name="edit_business_fields_submit" class="btn btn-primary" 
                       value="<?php esc_attr_e('Update Business Fields', 'text-domain'); ?>">
            </div>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Process the form submission
 */
function process_vendor_business_fields_submission() {
    if (!isset($_POST['edit_business_fields_submit']) || 
        !wp_verify_nonce($_POST['edit_business_fields_nonce'], 'edit_vendor_business_fields')) {
        return;
    }

    if (!is_user_logged_in()) {
        return;
    }

    if (!session_id()) {
        session_start();
    }

    $user_id = get_current_user_id();
    $errors = [];

    // Define required fields
    $required_fields = [
        'business_registration_number' => __('Business Registration Number/License', 'text-domain'),
        'tax_number' => __('GST or Tax Information', 'text-domain'),
        'registered_business_name' => __('Registered Business Name', 'text-domain'),
        'business_type' => __('Business Type', 'text-domain'),
    ];

    // Validate required fields
    foreach ($required_fields as $field => $label) {
        if (empty($_POST[$field])) {
            $errors[] = sprintf(__('%s is required.', 'text-domain'), $label);
        }
    }

    // Validate file upload (if a new file is uploaded)
    if (!empty($_FILES['identification_docs']['name'])) {
        $file_type = $_FILES['identification_docs']['type'];
        $allowed_types = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = __('Invalid file type for identification documents. Allowed types: PDF, JPG, JPEG, PNG.', 'text-domain');
        }
        if ($_FILES['identification_docs']['size'] > 5 * 1024 * 1024) { // 5MB
            $errors[] = __('File size exceeds 5MB limit.', 'text-domain');
        }
    } elseif (empty(get_user_meta($user_id, 'identification_docs', true)) && empty($_FILES['identification_docs']['name'])) {
        $errors[] = __('Identification Documents are required.', 'text-domain');
    }

    // Process updates if no errors
    if (empty($errors)) {
        // Update user meta
        update_user_meta($user_id, 'business_registration_number', sanitize_text_field($_POST['business_registration_number']));
        update_user_meta($user_id, 'tax_number', sanitize_text_field($_POST['tax_number']));
        update_user_meta($user_id, 'registered_business_name', sanitize_text_field($_POST['registered_business_name']));
        update_user_meta($user_id, 'business_type', sanitize_text_field($_POST['business_type']));

        // Handle file upload
        if (!empty($_FILES['identification_docs']['name'])) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';

            $attachment_id = media_handle_upload('identification_docs', 0);
            if (!is_wp_error($attachment_id)) {
                update_user_meta($user_id, 'identification_docs', $attachment_id);
            } else {
                $errors[] = __('Error uploading identification documents.', 'text-domain');
            }
        }

        if (empty($errors)) {
            $_SESSION['edit_business_fields_success'] = true;
        }
    }

    if (!empty($errors)) {
        $_SESSION['edit_business_fields_errors'] = $errors;
    }

    // Redirect to avoid form resubmission
    wp_redirect(add_query_arg([], remove_query_arg([])));
    exit;
}