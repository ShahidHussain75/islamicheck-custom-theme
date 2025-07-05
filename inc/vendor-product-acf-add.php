<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add ACF Check Fields to Dokan Product Edit Page
 */
function add_acf_check_fields_to_dokan() {
    // Only show on the Dokan seller dashboard
    if ( ! dokan_is_seller_dashboard() ) {
        return;
    }
    
    // Get the current product ID – try from the global post or from the URL
    $product_id = 0;
    if ( isset( $_GET['product_id'] ) ) {
        $product_id = absint( $_GET['product_id'] );
    } elseif ( isset( $GLOBALS['post'] ) && isset( $GLOBALS['post']->ID ) ) {
        $product_id = $GLOBALS['post']->ID;
    }
    
    if ( ! $product_id ) {
        return;
    }
    
    $product = get_post( $product_id );
    if ( ! $product || $product->post_type !== 'product' ) {
        return;
    }
    
    // Retrieve the current values from the ACF fields.
    // Fallback to get_post_meta if ACF functions aren’t available.
    if ( function_exists( 'get_field' ) ) {
        $check_in  = get_field( 'check_in', $product_id );
        $check_out = get_field( 'check_out', $product_id );
    } else {
        $check_in  = get_post_meta( $product_id, 'check_in', true );
        $check_out = get_post_meta( $product_id, 'check_out', true );
    }
    ?>
    <div class="dokan-edit-row dokan-clearfix hide_if_grouped hide_if_external">
        <div class="dokan-section-heading" data-togglehandler="dokan_product_inventory">
            <h2><i class="fas fa-wrench"></i> <?php _e( 'Check In and Check Out Date', 'your-text-domain' ); ?></h2>
            <a href="#" class="dokan-section-toggle">
                <i class="fas fa-sort-down fa-flip-vertical"></i>
            </a>
            <div class="dokan-clearfix"></div>
        </div>
    
        <div class="dokan-section-content">
            <!-- Check In Date -->
            <div class="dokan-form-group datepicker-container">
                <label for="check_in"><?php _e( 'Check In Date', 'your-text-domain' ); ?></label>
                <div class="datepicker-wrapper">
                    <input type="text" name="check_in" id="check_in" class="dokan-form-control datepicker" 
                           value="<?php echo esc_attr( $check_in ); ?>" 
                           placeholder="<?php esc_attr_e( 'Select Check-in Date', 'your-text-domain' ); ?>" autocomplete="off">
                    <i class="fas fa-calendar datepicker-icon"></i>
                </div>
            </div>
    
            <!-- Check Out Date -->
            <div class="dokan-form-group datepicker-container">
                <label for="check_out"><?php _e( 'Check Out Date', 'your-text-domain' ); ?></label>
                <div class="datepicker-wrapper">
                    <input type="text" name="check_out" id="check_out" class="dokan-form-control datepicker" 
                           value="<?php echo esc_attr( $check_out ); ?>" 
                           placeholder="<?php esc_attr_e( 'Select Check-out Date', 'your-text-domain' ); ?>" autocomplete="off">
                    <i class="fas fa-calendar datepicker-icon"></i>
                </div>
            </div>
        </div>
    </div>
    <?php
}
add_action( 'dokan_product_edit_after_main', 'add_acf_check_fields_to_dokan' );

/**
 * Save ACF Check Fields on Dokan Product Update
 */
function save_acf_check_fields_on_dokan_product_update( $post_id ) {
    if ( empty( $_POST ) ) {
        return;
    }
    
    // Save Check In Date
    if ( isset( $_POST['check_in'] ) && ! empty( $_POST['check_in'] ) ) {
        $check_in_input = sanitize_text_field( $_POST['check_in'] ); // e.g., "15/05/2025"
        $date_obj = DateTime::createFromFormat( 'd/m/Y', $check_in_input );
        if ( $date_obj ) {
            // Convert to Y-m-d format (ACF's expected save format)
            $formatted_check_in = $date_obj->format( 'Y-m-d' );
        } else {
            // Fallback if conversion fails
            $formatted_check_in = $check_in_input;
        }
        if ( function_exists( 'update_field' ) ) {
            update_field( 'check_in', $formatted_check_in, $post_id );
        } else {
            update_post_meta( $post_id, 'check_in', $formatted_check_in );
        }
    }
    
    // Save Check Out Date
    if ( isset( $_POST['check_out'] ) && ! empty( $_POST['check_out'] ) ) {
        $check_out_input = sanitize_text_field( $_POST['check_out'] ); // e.g., "16/05/2025"
        $date_obj = DateTime::createFromFormat( 'd/m/Y', $check_out_input );
        if ( $date_obj ) {
            $formatted_check_out = $date_obj->format( 'Y-m-d' );
        } else {
            $formatted_check_out = $check_out_input;
        }
        if ( function_exists( 'update_field' ) ) {
            update_field( 'check_out', $formatted_check_out, $post_id );
        } else {
            update_post_meta( $post_id, 'check_out', $formatted_check_out );
        }
    }
}
add_action( 'dokan_process_product_meta', 'save_acf_check_fields_on_dokan_product_update' );


/**
 * Enqueue jQuery UI Datepicker for Dokan Product Edit
 */
function load_jquery_ui_for_dokan() {
    // Load datepicker assets on the product edit page in the seller dashboard
    if ( dokan_is_seller_dashboard() && isset( $_GET['action'] ) && $_GET['action'] === 'edit' && isset( $_GET['product_id'] ) ) {
        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_style( 'jquery-ui-css', 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.14.1/themes/cupertino/jquery-ui.min.css' );
  //      wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css' );
    
        // Inline script for Datepicker initialization
        $script = "
            jQuery(document).ready(function($) {
                $('.datepicker').datepicker({
                    dateFormat: 'dd/mm/yy',
                    changeMonth: true,
                    changeYear: true,
                    showAnim: 'fadeIn'
                });
                $('.datepicker-icon').on('click', function() {
                    $(this).siblings('.datepicker').datepicker('show');
                });
            });
        ";
        wp_add_inline_script( 'jquery-ui-datepicker', $script );
    
        // Inline styles for proper layout
        $styles = "
            .datepicker-container { position: relative; }
            .datepicker-wrapper { position: relative; display: flex; align-items: center; }
            .datepicker-wrapper input { width: 100%; padding-right: 35px; }
            .datepicker-wrapper i { position: absolute; right: 10px; color: #666; font-size: 16px; cursor: pointer; pointer-events: auto; }
            .ui-datepicker-trigger { display: none; }
        ";
        wp_add_inline_style( 'jquery-ui-css', $styles );
    }
}
add_action( 'wp_enqueue_scripts', 'load_jquery_ui_for_dokan' );
