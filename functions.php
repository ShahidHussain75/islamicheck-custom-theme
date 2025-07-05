<?php
/**
 * Custom Dokan Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package custom-dokan
 * @since 1.0.0
 */

/**
 * Define Constants
 */
define( 'CHILD_THEME_custom_dokan_VERSION', '1.0.0' );

/**
 * Enqueue styles
 */
function child_enqueue_styles() {

	wp_enqueue_style( 'custom-dokan-theme-css', get_stylesheet_directory_uri() . '/style.css', array('astra-theme-css'), CHILD_THEME_custom_dokan_VERSION, 'all' );

}

add_action( 'wp_enqueue_scripts', 'child_enqueue_styles', 15 );




function fix_svg_metadata($data, $id) {
    $attachment = get_post($id);
    if ($attachment->post_mime_type == 'image/svg+xml') {
        return false; // Prevents WordPress from processing metadata
    }
    return $data;
}
add_filter('wp_get_attachment_metadata', 'fix_svg_metadata', 10, 2);



require_once get_stylesheet_directory() . '/inc/improved-vendor-registration.php';
require_once get_stylesheet_directory() . '/inc/improved-vendor-profile-editor.php';
require_once get_stylesheet_directory() . '/inc/shortcode-to-display-cart.php';
require_once get_stylesheet_directory() . '/inc/vendor-product-acf-add.php';


//
// Add custom registration fields to WooCommerce registration form
// 
// Add name fields above email and password using woocommerce_register_form_start
add_action('woocommerce_register_form_start', 'add_custom_registration_fields');
function add_custom_registration_fields() {
    // Retrieve previous POST data for repopulation
    $billing_first_name = isset($_POST['billing_first_name']) ? esc_attr($_POST['billing_first_name']) : '';
    $middle_name        = isset($_POST['middle_name']) ? esc_attr($_POST['middle_name']) : '';
    $billing_last_name  = isset($_POST['billing_last_name']) ? esc_attr($_POST['billing_last_name']) : '';
    ?>
    <!-- Name fields in one row -->
    <div class="custom-names-row" style="display: flex; gap: 10px;">
        <p class="form-row form-row-first" style="flex: 1;">
            <label for="reg_billing_first_name"><?php esc_html_e('Geben Sie Ihren', 'woocommerce'); ?> <span class="required">*</span></label>
            <input type="text" class="input-text" name="billing_first_name" id="reg_billing_first_name" value="<?php echo $billing_first_name; ?>" placeholder="Vorname *" />
        </p>

        <p class="form-row form-row-middle" style="flex: 1;">
            <label for="reg_middle_name" style="visibility:hidden"><?php esc_html_e('Zweiter Vorname', 'woocommerce'); ?></label>
            <input type="text" class="input-text" name="middle_name" id="reg_middle_name" value="<?php echo $middle_name; ?>" placeholder="Zweiter Vorname" />
        </p>

        <p class="form-row form-row-last" style="flex: 1;">
            <label for="reg_billing_last_name" style="visibility:hidden"><?php esc_html_e('Nachname', 'woocommerce'); ?></label>
            <input type="text" class="input-text" name="billing_last_name" id="reg_billing_last_name" value="<?php echo $billing_last_name; ?>" placeholder="Nachname *" />
        </p>
    </div>
    <?php
}

// Move checkboxes to appear below the password field using woocommerce_register_form
add_action('woocommerce_register_form', 'move_checkboxes_below_password', 20);
function move_checkboxes_below_password() {
    ?>
    <p class="form-row form-row-wide checkbox-row">
        <input type="checkbox" name="terms" id="terms" style="display: inline;" <?php checked(isset($_POST['terms'])); ?> required />
        <label for="terms" style="display: inline;" >
            <?php 
            $terms_url = get_permalink(get_option('woocommerce_terms_page_id'));
            printf(
    'Ich stimme den %1$sAllgemeinen Geschäftsbedingungen%2$s zu',
    '<a href="' . esc_url($terms_url) . '" target="_blank">',
    '</a>'
);
 
            ?>
            <span class="required">*</span>
        </label>
    </p>

    <p class="form-row form-row-wide checkbox-row">
        <input style="display: inline;" type="checkbox" name="promotional_emails" id="promotional_emails" <?php checked(isset($_POST['promotional_emails'])); ?> />
        <label style="display: inline;" for="promotional_emails"><?php esc_html_e('Ich möchte Werbe-E-Mails erhalten', 'woocommerce'); ?></label>
    </p>
    <?php
}

// Placeholder placing
function custom_woocommerce_placeholder_js() {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $("#username").attr("placeholder", "Benutzername oder E-Mail-Adresse *");
$("#password").attr("placeholder", "Passwort *");
$("#reg_username").attr("placeholder", "Benutzername wählen *");
$("#reg_email").attr("placeholder", "E-Mail-Adresse eingeben *");
$("#reg_password").attr("placeholder", "Passwort erstellen *");

        });
    </script>
    <?php
}
add_action('wp_footer', 'custom_woocommerce_placeholder_js');

// password visibility remove button
function remove_wc_show_password_button() {
    wp_add_inline_style('woocommerce-general', '.show-password-input { display: none !important; }');
}
add_action('wp_enqueue_scripts', 'remove_wc_show_password_button');

// Vendor registration form remoing on account page




// DEfault product type
// Set default virtual product and hide checkbox in Dokan vendor dashboard
add_action('wp_footer', 'customize_virtual_checkbox_for_vendors', 10);
function customize_virtual_checkbox_for_vendors() {
    if (dokan_is_seller_dashboard()) {
        ?>
        <style>
            #product_type, 
            select[name="product_type"], 
            label[for="product_type"], .virtual-checkbox, .downloadable-checkbox, 
            .content-half-part.dokan-form-group #_sku, 
            label[for="_manage_stock"], label[for="_backorders"], #_backorders {
                display: none !important;
            }
        </style>
        <script>
            jQuery(document).ready(function($) {
                $('#_virtual').prop('checked', true);
                $('#_manage_stock').prop('checked', true); // Merged into one ready function
            });
        </script>
        <?php
    }
}


// Replace People word with Stock
function replace_stock_with_people_output_buffer() {
    // Adjust the URL part as needed if your vendor dashboard URL differs
    if ( is_user_logged_in() && strpos( $_SERVER['REQUEST_URI'], '/dashboard/' ) !== false ) {
        ob_start( 'replace_stock_with_people_callback' );
    }
}
add_action( 'template_redirect', 'replace_stock_with_people_output_buffer' );

function replace_stock_with_people_callback( $buffer ) {
    // This will replace every instance of "Stock" with "People" in the output.
    return str_replace( 'Stock', 'People', $buffer );
}

function vendor_rating_summary() { 
    // Vendor ka ID le raha hoon
    $seller_id = get_query_var('author');

    if (!$seller_id) {
        return 'No vendor found.';
    }

    // Vendor ke products fetch kar raha hoon
    $args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'author'         => $seller_id, 
    );
    $products = new WP_Query($args);

    $total_rating = 0;
    $total_reviews = 0;

    if ($products->have_posts()) {
        $product_ids = wp_list_pluck($products->posts, 'ID'); // Vendor ke products ki IDs le raha hoon

        $review_args = array(
            'post__in'  => $product_ids,  // Sirf iss vendor ke products ke reviews lo
            'status'    => 'approve',
        );

        $comments = get_comments($review_args);

        if ($comments) {
            foreach ($comments as $comment) {
                $rating = get_comment_meta($comment->comment_ID, 'rating', true);
                if (!empty($rating)) {
                    $total_rating += floatval($rating); // Ratings ka sum bana raha hoon
                    $total_reviews++;
                }
            }
        }
    }

    // Average rating calculate kar raha hoon
    $average_rating = ($total_reviews > 0) ? round($total_rating / $total_reviews, 1) : 0;

    // ⭐ Filled and Empty Stars Generate kar raha hoon
    $full_stars = floor($average_rating); // Pure stars
    $half_star = ($average_rating - $full_stars) >= 0.5 ? 1 : 0; // Half star check
    $empty_stars = 5 - ($full_stars + $half_star); // Empty stars count

    $stars_html = '<span class="rating-stars">';
    
    for ($i = 0; $i < $full_stars; $i++) {
        $stars_html .= '<i class="fas fa-star"></i>'; // ⭐ Filled Star
    }
    if ($half_star) {
        $stars_html .= '<i class="fas fa-star-half-alt"></i>'; // 🌗 Half Star
    }
    for ($i = 0; $i < $empty_stars; $i++) {
        $stars_html .= '<i class="far fa-star"></i>'; // ☆ Empty Star
    }
    
    $stars_html .= '</span>';

    // Output summary
    return '<p id="open-popup-btn"><strong>Vendor Ratings:</strong> ' . $stars_html . ' (' . esc_html($average_rating) . ' from ' . esc_html($total_reviews) . ' reviews)</p>';
}

// Shortcode register kar raha hoon
add_shortcode('vendor_rating', 'vendor_rating_summary');


function show_filtered_products_by_date() {
    $from_date = isset($_GET['from_date']) ? sanitize_text_field($_GET['from_date']) : '';
    $to_date   = isset($_GET['to_date']) ? sanitize_text_field($_GET['to_date']) : '';

    $from_acf = $from_date ? date('Ymd', strtotime($from_date)) : '';
    $to_acf   = $to_date ? date('Ymd', strtotime($to_date)) : '';

    ob_start(); ?>

    <form method="GET" class="date-filter-inline" style="margin-bottom: 20px;">
        <label>vom:</label>
        <input type="date" name="from_date" value="<?php echo esc_attr($from_date); ?>" required>
        <label>nach:</label>
        <input type="date" name="to_date" value="<?php echo esc_attr($to_date); ?>" required>
        <button type="submit">Suche</button>
        <a href="<?php echo esc_url( get_site_url(null, 'shop') ); ?>" style="margin-left:10px; display:inline-block;">
    <span style="display:inline-block; background:#004a31; padding:2px 8px; border:1px solid #004a31; cursor:pointer; color:white; border-radius: 5px; font-size: 15px;">Zurücksetzen</span>
</a>

    </form>

    <?php
    if ($from_acf && $to_acf) {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'check_in',
                    'value' => $from_acf,
                    'compare' => '<=',
                    'type' => 'CHAR',
                ),
                array(
                    'key' => 'check_out',
                    'value' => $to_acf,
                    'compare' => '>=',
                    'type' => 'CHAR',
                ),
            ),
        );

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            echo '<ul class="products">';
            while ($query->have_posts()) {
                $query->the_post();
                wc_get_template_part('content', 'product');
            }
            echo '</ul>';
            wp_reset_postdata();
        } else {
            echo '<p>No packages found for selected dates.</p>';
        }

        // Hide Elementor loop grid only when filtering
        echo "<style>.elementor-loop-container.elementor-grid { display: none !important; }</style>";
    }

    return ob_get_clean();
}
add_shortcode('date_filter_products', 'show_filtered_products_by_date');



?>