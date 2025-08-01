<?php
/**
 * Improved Dokan Vendor Registration with Custom Fields and Custom Form Layout
 *
 * This implementation re-arranges the registration form fields as follows:
 *  1. First Name *
 *  2. Last Name *
 *  3. Shop Name (User Name) *
 *  4. Shop URL (Business URL) *
 *  5. Email *
 *  6. Password *
 *  7. Business Information Phone Number *
 *  8. Business Address * (Address line, City, Country)
 *  9. Business Registration Number/License *
 * 10. GST or Tax Information *
 * 11. Registered Business Name *
 * 12. Support Contact *
 * 13. Website URL
 * 14. Business Type * (Select: Tour operator, Travel agency, Other)
 * 15. Profile Picture *
 * 16. Banner Picture *
 * 17. Identification Documents Upload *
 * 18. Business registration name *
 *
 * Features:
 * - Adds custom fields to the Dokan dashboard under "Business Details".
 * - Implements country as a select field using WooCommerce countries.
 * - Provides a separate dashboard page for uploading identification documents.
 * - Uses ACF to manage custom fields where applicable.
 */
 // shortcode of below form is [custom_vendor_form]

if (!defined('ABSPATH')) {
    exit;
}

class Improved_Vendor_Registration {

    public function __construct() {
        // Register shortcode for the registration form
        add_shortcode('custom_vendor_form', array($this, 'render_registration_form'));

        // Handle form submission
        add_action('init', array($this, 'process_registration'));

        // Add custom fields to Dokan dashboard
        add_filter('dokan_settings_fields', array($this, 'add_custom_dashboard_fields'), 10, 2);

        // Save custom fields from the Dokan dashboard
        add_action('dokan_store_profile_saved', array($this, 'save_dashboard_fields'));

        // Validate custom fields during registration
        add_filter('woocommerce_process_registration_errors', array($this, 'validate_custom_fields'), 10, 4);

        // Save custom fields after registration
        add_action('dokan_new_seller_created', array($this, 'save_registration_custom_fields'), 10, 2);

        // Add custom menu for document upload
        add_filter('dokan_get_dashboard_nav', array($this, 'add_upload_documents_menu'));
        add_action('dokan_load_custom_template', array($this, 'load_upload_documents_template'));

        // Register ACF fields programmatically
        $this->register_acf_fields();
    }

    /**
     * Register ACF fields programmatically for vendor registration.
     */
    public function register_acf_fields() {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group(array(
            'key' => 'group_vendor_registration_fields',
            'title' => 'Vendor Registration Fields',
            'fields' => array(
                array(
                    'key' => 'field_business_registration_number',
                    'label' => 'Business Registration Number/License',
                    'name' => 'business_registration_number',
                    'type' => 'text',
                    'required' => 1,
                ),
                array(
                    'key' => 'field_tax_number',
                    'label' => 'GST or Tax Information',
                    'name' => 'tax_number',
                    'type' => 'text',
                    'required' => 1,
                ),
                array(
                    'key' => 'field_registered_business_name',
                    'label' => 'Registered Business Name',
                    'name' => 'registered_business_name',
                    'type' => 'text',
                    'required' => 1,
                ),
                array(
                    'key' => 'field_identification_docs',
                    'label' => 'Identification Documents',
                    'name' => 'identification_docs',
                    'type' => 'file',
                    'required' => 1,
                    'return_format' => 'id',
                    'mime_types' => 'pdf,jpg,jpeg,png',
                    'max_size' => 5,
                ),
                // Add Business Type field here
                array(
                    'key' => 'field_business_type',
                    'label' => 'Business Type',
                    'name' => 'business_type',
                    'type' => 'select',
                    'required' => 1,
                    'choices' => array(
                        'Tour operator' => 'Tour operator',
                        'Travel agency' => 'Travel agency',
                        'Other' => 'Other',
                    ),
                    'default_value' => '',
                    'allow_null' => 0,
                    'multiple' => 0,
                    'ui' => 0,
                    'return_format' => 'value',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'user_role',
                        'operator' => '==',
                        'value' => 'seller',
                    ),
                ),
            ),
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => '',
            'active' => true,
            'description' => 'Custom fields for Dokan vendor registration.',
        ));
    }

    /**
     * Render the complete registration form with custom field order.
     */
    public function render_registration_form() {
        if (is_user_logged_in()) {
            return '<p style="text-align:center">' . esc_html__('Sie sind bereits registriert und angemeldet.', 'dokan') . '</p>';
        }

        if (!session_id()) {
            session_start();
        }

        $errors = isset($_SESSION['registration_errors']) ? $_SESSION['registration_errors'] : array();
        unset($_SESSION['registration_errors']);

        $enable_registration = dokan_get_option('enable_registration', 'dokan_selling', 'on');
        if ($enable_registration === 'off') {
            return '<p>' . esc_html__('Vendor registration is currently disabled.', 'dokan') . '</p>';
        }

        ob_start();
        ?>
        <div class="dokan-custom-registration">
            <?php if (!empty($errors)) : ?>
                <div class="dokan-alert dokan-alert-danger">
                    <ul>
                        <?php foreach ($errors as $error) : ?>
                            <li><?php echo esc_html($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="dokan-vendor-register">
                <?php wp_nonce_field('dokan_custom_vendor_register', 'dokan_custom_nonce'); ?>

                <!-- First Name -->
                <div class="dokan-form-group">
                    <label for="first_name"><?php esc_html_e('Vorname der autorisierten Person', 'dokan'); ?> <span class="required">*</span></label>
                    <input type="text" name="first_name" id="first_name" class="dokan-form-control" required placeholder="Vornamen eingeben">
                </div>

                <!-- Last Name -->
                <div class="dokan-form-group">
                    <label for="last_name"><?php esc_html_e('Nachname der autorisierten Person', 'dokan'); ?> <span class="required">*</span></label>
                    <input type="text" name="last_name" id="last_name" class="dokan-form-control" required placeholder="Nachnamen eingeben">
                </div>

                <!-- Shop Name -->
                <div class="dokan-form-group">
                    <label for="dokan_store_name"><?php esc_html_e('Name des Geschäfts', 'dokan'); ?> <span class="required">*</span></label>
                    <input type="text" name="dokan_store_name" id="dokan_store_name" class="dokan-form-control" required placeholder="Geschäftsname eingeben">
                </div>

                <!-- Shop URL (auto-generated, read-only) -->
                <div class="dokan-form-group" style="display: none;">
                    <label for="dokan_store_url"><?php esc_html_e('Shop URL', 'dokan'); ?> <span class="required">*</span></label>
                    <input type="text" name="dokan_store_url" id="dokan_store_url" class="dokan-form-control" required readonly placeholder="<?php echo esc_url( home_url( '/store/' ) ); ?>">
                    <small class="description"><?php esc_html_e('Die Shop-URL wird automatisch aus dem Geschäftsnamen generiert und kann nicht geändert werden.', 'dokan'); ?></small>
                </div>

                <!-- Email -->
                <div class="dokan-form-group">
                    <label for="user_email"><?php esc_html_e('E-mail', 'dokan'); ?> <span class="required">*</span></label>
                    <input type="email" name="user_email" id="user_email" class="dokan-form-control" required placeholder="E-Mail-Adresse eingeben">
                </div>

                <!-- Password -->
                <div class="dokan-form-group">
                    <label for="user_pass"><?php esc_html_e('Passwort', 'dokan'); ?> <span class="required">*</span></label>
                    <input type="password" name="user_pass" id="user_pass" class="dokan-form-control" required placeholder="Starkes Passwort eingeben">
                </div>
                <!-- Confirm Password -->
                <div class="dokan-form-group">
                    <label for="confirm_pass">Passwort bestätigen <span class="required">*</span></label>
                    <input type="password" name="confirm_pass" id="confirm_pass" class="dokan-form-control" required placeholder="Passwort erneut eingeben">
                </div>
                <!-- Business Phone -->
                <div class="dokan-form-group">
                    <label for="business_phone"><?php esc_html_e('Geschäftstelefonnummer', 'dokan'); ?> <span class="required">*</span></label>
                    <input type="text" name="business_phone" id="business_phone" class="dokan-form-control" required placeholder="Geschäftstelefonnummer eingeben">
                </div>

                <!-- Business Address -->
                <div class="dokan-form-group">
                    <label for="business_address"><?php esc_html_e('Geschäftsadresse', 'dokan'); ?> <span class="required">*</span></label>
                    <input type="text" name="business_address" id="business_address" class="dokan-form-control" placeholder="<?php esc_attr_e('Straßenadresse', 'dokan'); ?>" required>
                </div>

                <!-- City -->
                <div class="dokan-form-group">
                    <label for="business_city"><?php esc_html_e('Stadt', 'dokan'); ?> <span class="required">*</span></label>
                    <input type="text" name="business_city" id="business_city" class="dokan-form-control" required placeholder="Stadtnamen eingeben">
                </div>

                <!-- Post/ZIP Code -->
                <div class="dokan-form-group">
                    <label for="business_zip"><?php esc_html_e('Postleitzahl', 'dokan'); ?> <span class="required">*</span></label>
                    <input type="text" name="business_zip" id="business_zip" class="dokan-form-control" required placeholder="Postleitzahl eingeben">
                </div>

                <!-- Country -->
                <div class="dokan-form-group">
                    <label for="business_country"><?php esc_html_e('Land', 'dokan'); ?> <span class="required">*</span></label>
                    <select name="business_country" id="business_country" class="dokan-form-control" required>
                        <option value=""><?php esc_html_e('Land auswählen', 'dokan'); ?></option>
                        <?php foreach (WC()->countries->get_countries() as $key => $value) : ?>
                            <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($value); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Business Registration Number -->
                <div class="dokan-form-group">
                    <label for="business_registration_number"><?php esc_html_e('Handelsregisternummer/Lizenz', 'dokan'); ?></label>
                    <input type="text" name="business_registration_number" id="business_registration_number" class="dokan-form-control" placeholder="Registrierungsnummer eingeben">
                </div>

                <!-- GST or Tax Information -->
                <div class="dokan-form-group">
                    <label for="tax_number"><?php esc_html_e('Umsatzsteuer- oder Steuernummer', 'dokan'); ?> <span class="required">*</span></label>
                    <input type="text" name="tax_number" id="tax_number" class="dokan-form-control" required placeholder="Steuerinformationen eingeben">
                </div>

                <!-- Registered Business Name -->
                <div class="dokan-form-group">
                    <label for="registered_business_name"><?php esc_html_e('Eingetragener Firmenname', 'dokan'); ?> <span class="required">*</span></label>
                    <input type="text" name="registered_business_name" id="registered_business_name" class="dokan-form-control" required placeholder="Eingetragenen Firmennamen eingeben">
                </div>

                <!-- Support Contact -->
                <div class="dokan-form-group">
                    <label for="support_contact"><?php esc_html_e('Support-Kontakt', 'dokan'); ?> <span class="required">*</span></label>
                    <input type="text" name="support_contact" id="support_contact" class="dokan-form-control" required placeholder="Support-Telefonnummer eingeben">
                </div>

                <!-- Website URL -->
                <div class="dokan-form-group">
                    <label for="website_url"><?php esc_html_e('Website-URL', 'dokan'); ?></label>
                    <input type="url" name="website_url" id="website_url" class="dokan-form-control" placeholder="https://example.com">
                </div>

               

                <!-- Business Type -->
                <div class="dokan-form-group">
                    <label for="business_type"><?php esc_html_e('Art des Unternehmens', 'dokan'); ?> <span class="required">*</span></label>
                    <select name="business_type" id="business_type" class="dokan-form-control" required>
                        <option value=""><?php esc_html_e('Unternehmensart auswählen', 'dokan'); ?></option>
                        <option value="Tour operator"><?php esc_html_e('Reiseveranstalter', 'dokan'); ?></option>
                        <option value="Travel agency"><?php esc_html_e('Reisebüro', 'dokan'); ?></option>
                        <option value="Other"><?php esc_html_e('Andere', 'dokan'); ?></option>
                    </select>
                </div>

                <!-- Profile Picture -->
                <div class="dokan-form-group">
                    <label for="profile_picture"><?php esc_html_e('Profilbild', 'dokan'); ?></label><br />
                    <input type="file" name="profile_picture" id="profile_picture" accept="image/*">
                </div>

                <!-- Banner Picture -->
                <div class="dokan-form-group">
                    <label for="banner_picture"><?php esc_html_e('Bannerbild', 'dokan'); ?></label><br />
                    <input type="file" name="banner_picture" id="banner_picture" accept="image/*">
                </div>

                <!-- Identification Documents -->
                <div class="dokan-form-group">
                    <label for="identification_docs"><?php esc_html_e('Identitätsdokumente hochladen', 'dokan'); ?> <span class="required">*</span></label>
                    <input type="file" name="identification_docs" id="identification_docs" accept=".pdf,.jpg,.jpeg,.png" required><br />
                    <small class="description"><?php esc_html_e('Zulässige Dateiformate: PDF, JPG, JPEG, PNG. Maximale Dateigröße: 5 MB', 'dokan'); ?></small>
                </div>

                <!-- Terms and Conditions -->
                <div class="dokan-form-group">
                    <label for="terms_conditions">
                        <input type="checkbox" name="terms_conditions" id="terms_conditions" required>
                       <?php 
    printf(
        esc_html__('Ich akzeptiere die %s', 'dokan'),
        '<a href="' . esc_url( site_url('/vendor-terms-and-conditions') ) . '" target="_blank">' . esc_html__('Allgemeinen Geschäftsbedingungen', 'dokan') . '</a>'
    ); 
?> <span class="required">*</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <div class="dokan-form-group">
                    <input type="submit" name="vendor_register" class="dokan-btn dokan-btn-theme" value="<?php esc_attr_e('Als Verkäufer registrieren', 'dokan'); ?>">
                </div>
            </form>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var storeNameInput = document.getElementById('dokan_store_name');
            var storeUrlInput = document.getElementById('dokan_store_url');
            var baseUrl = '<?php echo esc_url( home_url( '/store/' ) ); ?>';

            if (storeNameInput && storeUrlInput) {
                storeNameInput.addEventListener('input', function() {
                    var slug = storeNameInput.value
                        .toLowerCase()
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/^-+|-+$/g, '')
                        .replace(/-+/g, '-');
                    storeUrlInput.value = baseUrl + slug;
                });
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Validate custom registration fields.
     */
    public function validate_custom_fields($errors, $username, $email, $validation_error) {
        if (!isset($_POST['role']) || $_POST['role'] !== 'seller') {
            return $errors;
        }

        $required_fields = array(
            'first_name'                   => __('First Name', 'dokan'),
            'last_name'                    => __('Last Name', 'dokan'),
            'dokan_store_name'             => __('Shop Name', 'dokan'),
            'dokan_store_url'              => __('Shop URL (Business URL)', 'dokan'),
            'user_email'                   => __('Email', 'dokan'),
            'user_pass'                    => __('Password', 'dokan'),
            'confirm_pass'                 => __('Confirm Password', 'dokan'),
            'business_phone'               => __('Business Information Phone Number', 'dokan'),
            'business_address'             => __('Business Address', 'dokan'),
            'business_city'                => __('City', 'dokan'),
            'business_zip'                 => __('Post/ZIP Code', 'dokan'),
            'business_country'             => __('Country', 'dokan'),
            'business_registration_number' => __('Business Registration Number/License', 'dokan'),
            'tax_number'                   => __('GST or Tax Information', 'dokan'),
            'support_contact'              => __('Support Contact', 'dokan'),
            'business_type'                => __('Business Type', 'dokan'), // Add Business Type validation
        );

        foreach ($required_fields as $field => $label) {
            if (empty($_POST[$field])) {
                $errors->add('required-field', sprintf(__('%s is required.', 'dokan'), $label));
            }
        }
        // Check if passwords match
        if (!empty($_POST['user_pass']) && !empty($_POST['confirm_pass'])) {
            if ($_POST['user_pass'] !== $_POST['confirm_pass']) {
                $errors->add('password-mismatch', __('Passwords do not match.', 'dokan'));
            }
        }

        if (empty($_POST['terms_conditions'])) {
            $errors->add('terms_conditions', __('You must accept the Terms and Conditions.', 'dokan'));
        }

        $file_fields = array(
            'profile_picture'     => __('Profile Picture', 'dokan'),
            'banner_picture'      => __('Banner Picture', 'dokan'),
            'identification_docs' => __('Identification Documents Upload', 'dokan'),
        );

        foreach ($file_fields as $field => $label) {
            if (empty($_FILES[$field]['name'])) {
                $errors->add('required-file', sprintf(__('%s is required.', 'dokan'), $label));
            } elseif ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
                $errors->add('file-upload-error', sprintf(__('%s upload failed. Please try again.', 'dokan'), $label));
            } elseif ($field === 'identification_docs') {
                $allowed_types = array('application/pdf', 'image/jpeg', 'image/jpg', 'image/png');
                $file_type = wp_check_filetype($_FILES[$field]['name']);
                if (!in_array($_FILES[$field]['type'], $allowed_types) && !in_array($file_type['type'], $allowed_types)) {
                    $errors->add('file-type-error', __('Only PDF, JPG, and PNG files are allowed for Identification Documents.', 'dokan'));
                }
                $max_size = 5 * 1024 * 1024; // 5MB
                if ($_FILES[$field]['size'] > $max_size) {
                    $errors->add('file-size-error', __('Identification Documents file size exceeds the maximum limit of 5MB.', 'dokan'));
                }
            }
        }

        return $errors;
    }

   /**
     * Process the vendor registration form submission.
     */
    public function process_registration() {
        if (isset($_POST['vendor_register'])) {
            if (!session_id()) {
                session_start();
            }

            if (!isset($_POST['dokan_custom_nonce']) || !wp_verify_nonce($_POST['dokan_custom_nonce'], 'dokan_custom_vendor_register')) {
                $this->add_error(__('Security check failed.', 'dokan'));
                return;
            }

            $username = sanitize_user($_POST['dokan_store_name'] ?? '');
            $email = sanitize_email($_POST['user_email'] ?? '');
            $password = $_POST['user_pass'] ?? '';
            $confirm_password = $_POST['confirm_pass'] ?? ''; // Add confirm password
            $store_url = sanitize_text_field($_POST['dokan_store_url'] ?? '');

            // Check if required fields are empty
            if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($store_url)) {
                $this->add_error(__('Please fill in all required fields.', 'dokan'));
                return;
            }

            // Check if passwords match
            if ($password !== $confirm_password) {
                $this->add_error(__('Passwords do not match.', 'dokan'));
                return;
            }

            $userdata = array(
                'user_login' => $username,
                'user_email' => $email,
                'user_pass'  => $password,
                'role'       => 'seller',
            );

            $user_id = wp_insert_user($userdata);
            if (is_wp_error($user_id)) {
                $this->add_error($user_id->get_error_message());
                return;
            }

            // Trigger Dokan's new vendor verification email
            do_action('dokan_new_seller_created', $user_id, $_POST);

            // Send welcome email to the new vendor with PDF attachment
            $vendor_email = $email;
            $username = sanitize_text_field($_POST['dokan_store_name'] ?? '');
            $first_name = sanitize_text_field($_POST['first_name'] ?? '');
            $last_name = sanitize_text_field($_POST['last_name'] ?? '');
            $full_name = $first_name . ' ' . $last_name;
            
            $subject = sprintf(__('Willkommen bei, %s!', 'dokan'), $full_name);

            $headers = array(
                'Content-Type: text/html; charset=UTF-8',
                'From:  <' . get_option('admin_email') . '>'
            );

            // Path to the PDF file to attach
            $pdf_attachment = WP_CONTENT_DIR . '/uploads/vendor-docs/vendor-guide.pdf';
            
            // Check if the file exists
            $attachments = array();
            if (file_exists($pdf_attachment)) {
                $attachments[] = $pdf_attachment;
            } else {
                // Log error if file doesn't exist
                error_log('Vendor guide PDF not found at: ' . $pdf_attachment);
            }

            $message = sprintf(
                '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 5px;">
    <h2 style="color: #333;">Willkommen bei! 🎉</h2>
    <p>Sehr geehrte Damen und Herren,</p>
    <p>vielen Dank für Ihre Registrierung bei.</p>
    <p>Unsere Aufgabe ist es nun, die von Ihnen eingetragenen Informationen sorgfältig zu prüfen. Sobald alle Angaben vollständig und korrekt sind, werden wir Ihr Konto freischalten und Sie darüber informieren.</p>
    <p>Sollten wir Rückfragen zu einzelnen Angaben haben, melden wir uns zeitnah bei Ihnen.</p>
    <p>Wir freuen uns auf die Zusammenarbeit und stehen Ihnen bei Fragen jederzeit gerne zur Verfügung.</p>
    <p style="margin-top: 30px;">Mit freundlichen Grüßen,<br><strong>Mazen Uklah</strong><br>Geschäftsführer</p>
    <p style="margin: 0;"><strong>*****</strong><br><a href="mailto:####@####.de" style="color: #0073aa; text-decoration: none;">####@####.de</a><br>Tel.- WhatsApp: 026639790105</p>
</div>
',
                esc_html($full_name),
                esc_html($username),
                esc_html($vendor_email),
                esc_url(site_url('/my-account/')),
                esc_url(site_url('/wp-content/uploads/2025/05/AGB-3.pdf'))
            );
            
            // Use WP Mail SMTP to send the email with attachment
            $mail_sent = wp_mail($vendor_email, $subject, $message, $headers, $attachments);
            
            // Also send a notification to admin
            $admin_email = get_option('admin_email');
            $admin_subject = sprintf(__('Neuer Verkäufer bei *****: %s', 'dokan'), $full_name);
            
            $admin_message = sprintf(
                '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 5px;">
                    <h2 style="color: #333;">Neuer Verkäufer registriert</h2>
                    <p>Ein neuer Verkäufer hat sich bei ***** registriert.</p>
                    <p>Details des Verkäufers:</p>
                    <ul>
                        <li><strong>Name:</strong> %s</li>
                        <li><strong>Geschäftsname:</strong> %s</li>
                        <li><strong>E-Mail:</strong> %s</li>
                        <li><strong>Telefon:</strong> %s</li>
                    </ul>
                    <p>Sie können die vollständigen Details im <a href="%s" style="color: #0073aa; text-decoration: none;">Admin-Bereich</a> einsehen.</p>
                </div>',
                esc_html($full_name),
                esc_html($username),
                esc_html($vendor_email),
                esc_html($_POST['business_phone'] ?? 'Nicht angegeben'),
                esc_url(admin_url('user-edit.php?user_id=' . $user_id))
            );
            
            wp_mail($admin_email, $admin_subject, $admin_message, $headers);

            update_user_meta($user_id, 'dokan_store_url', $store_url);

            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);
            wp_redirect(dokan_get_navigation_url());
            exit;
        }
    }

    /**
     * Save additional custom fields after registration.
     */
    public function save_registration_custom_fields($user_id, $data) {
        $dokan_settings = get_user_meta($user_id, 'dokan_profile_settings', true) ?: array();

        // Personal details
        if (isset($_POST['first_name'])) {
            update_user_meta($user_id, 'first_name', sanitize_text_field($_POST['first_name']));
        }
        if (isset($_POST['last_name'])) {
            update_user_meta($user_id, 'last_name', sanitize_text_field($_POST['last_name']));
        }

        // Dokan fields
        $dokan_settings['store_name'] = sanitize_text_field($_POST['dokan_store_name'] ?? '');
        $dokan_settings['phone'] = sanitize_text_field($_POST['business_phone'] ?? '');
        $dokan_settings['support_contact'] = sanitize_text_field($_POST['support_contact'] ?? '');
        $dokan_settings['website_url'] = sanitize_text_field($_POST['website_url'] ?? '');
        $dokan_settings['business_type'] = sanitize_text_field($_POST['business_type'] ?? '');
        $dokan_settings['business_registration_number'] = sanitize_text_field($_POST['business_registration_number'] ?? '');
        $dokan_settings['tax_number'] = sanitize_text_field($_POST['tax_number'] ?? '');
        $dokan_settings['registered_business_name'] = sanitize_text_field($_POST['registered_business_name'] ?? '');

        // Address fields
        $address = array();
        if (isset($_POST['business_address'])) {
            $address['street_1'] = sanitize_text_field($_POST['business_address']);
        }
        if (isset($_POST['business_city'])) {
            $address['city'] = sanitize_text_field($_POST['business_city']);
        }
        if (isset($_POST['business_zip'])) {
            $address['zip'] = sanitize_text_field($_POST['business_zip']);
        }
        if (isset($_POST['business_country'])) {
            $address['country'] = sanitize_text_field($_POST['business_country']);
        }
        if (!empty($address)) {
            $dokan_settings['address'] = $address;
        }

        // ACF fields
        if (isset($_POST['business_registration_number'])) {
            update_field('business_registration_number', $dokan_settings['business_registration_number'], 'user_' . $user_id);
        }
        if (isset($_POST['tax_number'])) {
            update_field('tax_number', $dokan_settings['tax_number'], 'user_' . $user_id);
        }
        if (isset($_POST['registered_business_name'])) {
            update_field('registered_business_name', $dokan_settings['registered_business_name'], 'user_' . $user_id);
        }
        if (isset($_POST['business_type'])) {
            update_field('business_type', $dokan_settings['business_type'], 'user_' . $user_id);
        }

        // File uploads
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        if (!empty($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $attachment_id = media_handle_upload('profile_picture', 0);
            if (!is_wp_error($attachment_id)) {
                $dokan_settings['gravatar'] = $attachment_id;
            }
        }

        if (!empty($_FILES['banner_picture']) && $_FILES['banner_picture']['error'] === UPLOAD_ERR_OK) {
            $attachment_id = media_handle_upload('banner_picture', 0);
            if (!is_wp_error($attachment_id)) {
                $dokan_settings['banner'] = $attachment_id;
            }
        }

        if (!empty($_FILES['identification_docs']) && $_FILES['identification_docs']['error'] === UPLOAD_ERR_OK) {
            $attachment_id = media_handle_upload('identification_docs', 0);
            if (!is_wp_error($attachment_id)) {
                update_field('identification_docs', $attachment_id, 'user_' . $user_id);
                $dokan_settings['identification_docs'] = $attachment_id;
            }
        }

        update_user_meta($user_id, 'dokan_profile_settings', $dokan_settings);
    }

    /**
     * Add custom fields to the Dokan dashboard for sellers.
     */
    public function add_custom_dashboard_fields($fields, $user_id) {
        $user = get_userdata($user_id);
        if (!in_array('seller', (array) $user->roles)) {
            return $fields;
        }

        $dokan_settings = get_user_meta($user_id, 'dokan_profile_settings', true) ?: array();

        $fields['custom_info'] = array(
            'name'     => __('Business Details', 'dokan'),
            'priority' => 30,
            'fields'   => array(
                'business_phone' => array(
                    'name'  => 'business_phone',
                    'label' => __('Business Information Phone Number', 'dokan'),
                    'type'  => 'text',
                    'value' => $dokan_settings['phone'] ?? '',
                ),
                'support_contact' => array(
                    'name'  => 'support_contact',
                    'label' => __('Support Contact', 'dokan'),
                    'type'  => 'text',
                    'value' => $dokan_settings['support_contact'] ?? '',
                ),
                'website_url' => array(
                    'name'  => 'website_url',
                    'label' => __('Website URL', 'dokan'),
                    'type'  => 'url',
                    'value' => $dokan_settings['website_url'] ?? '',
                ),
                'business_type' => array(
                    'name'  => 'business_type',
                    'label' => __('Business Type', 'dokan'),
                    'type'  => 'text',
                    'value' => $dokan_settings['business_type'] ?? '',
                ),
                'business_country' => array(
                    'name'    => 'business_country',
                    'label'   => __('Country', 'dokan'),
                    'type'    => 'select',
                    'options' => WC()->countries->get_countries(),
                    'value'   => $dokan_settings['address']['country'] ?? '',
                ),
                'business_zip' => array(
                    'name'  => 'business_zip',
                    'label' => __('Post/ZIP Code', 'dokan'),
                    'type'  => 'text',
                    'value' => $dokan_settings['address']['zip'] ?? '',
                ),
            ),
        );

        return $fields;
    }

    /**
     * Save custom dashboard fields.
     */
    public function save_dashboard_fields($vendor_id) {
        $user = get_userdata($vendor_id);
        if (!in_array('seller', (array) $user->roles)) {
            return;
        }

        $dokan_settings = get_user_meta($vendor_id, 'dokan_profile_settings', true) ?: array();

        $fields = array(
            'business_phone' => 'phone',
            'support_contact' => 'support_contact',
            'website_url' => 'website_url',
            'business_type' => 'business_type',
            'business_country' => 'address.country',
            'business_zip' => 'address.zip',
        );

        foreach ($fields as $post_key => $settings_key) {
            if (isset($_POST[$post_key])) {
                if (strpos($settings_key, '.') !== false) {
                    list($parent, $child) = explode('.', $settings_key);
                    $dokan_settings[$parent][$child] = sanitize_text_field($_POST[$post_key]);
                } else {
                    $dokan_settings[$settings_key] = sanitize_text_field($_POST[$post_key]);
                }
            }
        }

        update_user_meta($vendor_id, 'dokan_profile_settings', $dokan_settings);
    }

    /**
     * Add Upload Documents menu item to Dokan dashboard navigation.
     */
    public function add_upload_documents_menu($urls) {
        $urls['upload-documents'] = array(
            'title' => __('Upload Documents', 'dokan'),
            'icon'  => '<i class="fa fa-upload"></i>',
            'url'   => dokan_get_navigation_url('edit-account'),
            'pos'   => 80,
        );
        return $urls;
    }

    /**
     * Load the custom template for the Upload Documents page.
     */
    public function load_upload_documents_template($query_vars) {
        if (isset($query_vars['upload-documents'])) {
            $this->render_upload_documents_page();
            exit;
        }
    }

    /**
     * Render the Upload Documents page in the vendor dashboard.
     */
    public function render_upload_documents_page() {
        $user_id = get_current_user_id();
        if (!$user_id || !dokan_is_user_seller($user_id)) {
            wp_redirect(dokan_get_navigation_url());
            exit;
        }

        $current_doc_id = get_field('identification_docs', 'user_' . $user_id);
        $current_doc_url = $current_doc_id ? wp_get_attachment_url($current_doc_id) : '';

        if (isset($_POST['upload_documents'])) {
            if (!isset($_POST['dokan_upload_nonce']) || !wp_verify_nonce($_POST['dokan_upload_nonce'], 'dokan_upload_documents')) {
                wp_die(__('Security check failed.', 'dokan'));
            }

            if (!empty($_FILES['identification_docs']) && $_FILES['identification_docs']['error'] === UPLOAD_ERR_OK) {
                $attachment_id = media_handle_upload('identification_docs', 0);
                if (!is_wp_error($attachment_id)) {
                    update_field('identification_docs', $attachment_id, 'user_' . $user_id);
                    $dokan_settings = get_user_meta($user_id, 'dokan_profile_settings', true) ?: array();
                    $dokan_settings['identification_docs'] = $attachment_id;
                    update_user_meta($user_id, 'dokan_profile_settings', $dokan_settings);
                    wp_redirect(dokan_get_navigation_url('upload-documents'));
                    exit;
                } else {
                    $error = $attachment_id->get_error_message();
                }
            } else {
                $error = __('Please select a file to upload.', 'dokan');
            }
        }

        ?>
        <div class="dokan-dashboard-wrap">
            <?php dokan_get_template_part('global/dokan-header'); ?>
            <div class="dokan-dashboard-content">
                <article class="dokan-settings-area">
                    <header class="dokan-dashboard-header">
                        <h1 class="entry-title"><?php _e('Upload Identification Documents', 'dokan'); ?></h1>
                    </header>
                    <div class="dokan-panel dokan-panel-default">
                        <div class="dokan-panel-body">
                            <?php if (isset($error)) : ?>
                                <div class="dokan-alert dokan-alert-danger"><?php echo esc_html($error); ?></div>
                            <?php endif; ?>
                            <form method="POST" enctype="multipart/form-data">
                                <?php wp_nonce_field('dokan_upload_documents', 'dokan_upload_nonce'); ?>
                                <p><?php _e('Current Document:', 'dokan'); ?>
                                    <?php if ($current_doc_url) : ?>
                                        <a href="<?php echo esc_url($current_doc_url); ?>" target="_blank" rel="noreferrer"><?php _e('View Document', 'dokan'); ?></a>
                                    <?php else : ?>
                                        <?php _e('No document uploaded.', 'dokan'); ?>
                                    <?php endif; ?>
                                </p>
                                <p>
                                    <label for="identification_docs"><?php _e('Upload New Document', 'dokan'); ?></label><br />
                                    <input type="file" name="identification_docs" id="identification_docs" accept=".pdf,.jpg,.jpeg,.png" />
                                </p>
                                <p>
                                    <input type="submit" name="upload_documents" class="dokan-btn dokan-btn-theme" value="<?php _e('Upload', 'dokan'); ?>" />
                                </p>
                            </form>
                        </div>
                    </div>
                </article>
            </div>
        </div>
        <?php
    }

    /**
     * Add an error message to the session.
     */
    private function add_error($message) {
        if (!session_id()) {
            session_start();
        }
        $_SESSION['registration_errors'] = $_SESSION['registration_errors'] ?? array();
        $_SESSION['registration_errors'][] = $message;
    }
}

new Improved_Vendor_Registration();
