<?php
//
// Custom shortcode to display cart items in a table of product page
//

add_action('woocommerce_cart_loaded_from_session', 'custom_wc_cart_force_session');
function custom_wc_cart_force_session() {
    WC()->cart->get_cart();
}


if ( ! function_exists( 'custom_cart_shortcode' ) ) {
    function custom_cart_shortcode() {
        ob_start();

        if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
            echo '<p>WooCommerce ist nicht aktiv oder der Warenkorb ist nicht verfügbar.</p>';
            return ob_get_clean();
        }
        
        if ( WC()->cart->is_empty() ) {
            echo '<p style="display:none">Dies ist die Produktvorlage</p>';
            return ob_get_clean();
        }

        echo '<table class="custom-cart-table" border="0">';
        echo '<tbody>';

        $item_number = 1; // Start item counter

        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            if ( empty( $cart_item['data'] ) ) {
                continue;
            }
            $product       = $cart_item['data'];
            $thumbnail     = $product->get_image();
            $product_name  = $product->get_name();
            $short_desc    = wp_trim_words( $product->get_short_description(), 15, '...' );
            $price         = wc_price( $cart_item['line_total'] ) . ''; // Add line break after price
            $remove_url    = wc_get_cart_remove_url( $cart_item_key );
            $item_meta     = wc_get_formatted_cart_item_data( $cart_item, true );

    // Get Seller Name
    $seller_id   = get_post_field('post_author', $product->get_id());
    $seller_name = get_the_author_meta('display_name', $seller_id);
	
            echo '<tr class="cart-item">';
                echo '<td class="cart-item-number">' . sprintf('%02d', $item_number) . '.</td>'; // Item Number
                echo '<td class="cart-item-thumbnail">' . $thumbnail . '</td>';
                echo '<td class="cart-item-details">';
                    echo '<h3 class="cart-item-name">' . esc_html( $product_name ) . '</h3>';
                    echo '<div class="cart-item-description">' . wp_kses_post( $short_desc ) . '</div>';
                echo '</td>';
        echo '<td class="cart-item-meta" style="vertical-align: top;"><h3 class="cart-item-name">Verkäufer:</h3>' . esc_html( $seller_name ) . '</td>';
		                echo '<td class="cart-item-price"><div class="woocommerce-Price-amount amount">' . $price . '</div><div>inkl. MwSt</div></td>'; // Price with line break
                echo '<td class="product-remove"><a href="' . esc_url( $remove_url ) . '" class="remove-item-button"><svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
  <mask id="mask0_119_4064" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="22" height="22">
    <rect x="0.615234" y="0.307617" width="21.3556" height="21.3556" fill="#D9D9D9"/>
  </mask>
  <g mask="url(#mask0_119_4064)">
    <path d="M6.84471 18.9932C6.35531 18.9932 5.93635 18.819 5.58784 18.4705C5.23933 18.122 5.06508 17.703 5.06508 17.2136V5.64601H4.17526V3.86638H8.62434V2.97656H13.9632V3.86638H18.4123V5.64601H17.5225V17.2136C17.5225 17.703 17.3482 18.122 16.9997 18.4705C16.6512 18.819 16.2323 18.9932 15.7429 18.9932H6.84471ZM15.7429 5.64601H6.84471V17.2136H15.7429V5.64601ZM8.62434 15.434H10.404V7.42564H8.62434V15.434ZM12.1836 15.434H13.9632V7.42564H12.1836V15.434Z" fill="#E40D0D"/>
  </g>
</svg></a></td>'; // Remove Button
            echo '</tr>';

            $item_number++; // Increment item counter
        }

        echo '</tbody>';
        echo '</table>';
        return ob_get_clean();
    }
    add_shortcode( 'custom_cart', 'custom_cart_shortcode' );
}
// Custom shortcode to display cart items in a table with Seller (Author) Details

// Handle custom cart quantity update from custom form
add_action( 'template_redirect', 'custom_handle_cart_quantity_update' );
function custom_handle_cart_quantity_update() {
    if ( isset( $_POST['custom_cart_update'] ) && ! empty( $_POST['cart'] ) && is_array( $_POST['cart'] ) ) {
        foreach ( $_POST['cart'] as $cart_item_key => $values ) {
            if ( isset( $values['qty'] ) ) {
                WC()->cart->set_quantity( $cart_item_key, wc_stock_amount( $values['qty'] ), true );
            }
        }
        WC()->cart->calculate_totals();
        WC()->cart->set_session();
        wp_safe_redirect( wc_get_cart_url() );
        exit;
    }
}




if ( ! function_exists( 'custom_cart_table_shortcode' ) ) {
    function custom_cart_table_shortcode() {
        ob_start();

        if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
            echo '<p>WooCommerce is not active or the cart is not available.</p>';
            return ob_get_clean();
        }

        if ( WC()->cart->is_empty() ) {
            echo '<p>Ihr Warenkorb ist leer.</p>';
            return ob_get_clean();
        }

        echo '<form action="' . esc_url( wc_get_cart_url() ) . '" method="post">';
        echo '<input type="hidden" name="custom_cart_update" value="1" />';

        // Desktop Cart Table
        echo '<div class="desktop-cart">';
        echo '<table class="custom-cart-table cartpage">';
        echo '<thead><tr><th>Nr.</th><th>Paketdetails</th><th class="lesspd">Paketname & Details</th><th class="lesspd">Verkäufername & Details</th><th class="lesspd">Zusätzliche Details</th><th>Anzahl der Personen</th><th>Preis</th><th class="csaign">Aktion</th></tr></thead>';

        echo '<tbody>';

        $item_number = 1;
        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            if ( empty( $cart_item['data'] ) ) {
                continue;
            }
            $product = $cart_item['data'];
            $thumbnail = $product->get_image();
            $product_name = $product->get_name();
            $short_desc = wp_trim_words( $product->get_short_description(), 15, '...' );
            $price = wc_price( $cart_item['line_total'] );
            $remove_url = wc_get_cart_remove_url( $cart_item_key );
            $item_meta = wc_get_formatted_cart_item_data( $cart_item, true );
            $author_id = get_post_field( 'post_author', $product->get_id() );
            $seller_name = get_the_author_meta( 'display_name', $author_id );

            $quantity_input = woocommerce_quantity_input(
                array(
                    'input_name'  => "cart[{$cart_item_key}][qty]",
                    'input_value' => $cart_item['quantity'],
                    'max_value'   => $product->get_max_purchase_quantity(),
                    'min_value'   => $product->get_min_purchase_quantity(),
                    'product_name'=> $product->get_name(),
                ),
                $product,
                false
            );

            echo '<tr class="cart-item">';
            echo '<td>' . sprintf('%02d', $item_number) . '.</td>';
            echo '<td>' . $thumbnail . '</td>';
            echo '<td><h3>' . esc_html( $product_name ) . '</h3><div>' . wp_kses_post( $short_desc ) . '</div></td>';
            echo '<td>' . esc_html( $seller_name ) . '</td>';
            echo '<td>' . $item_meta . '</td>';
            echo '<td>' . $quantity_input . '</td>';
            echo '<td>' . $price . '</td>';
            echo '<td><a href="' . esc_url( $remove_url ) . '" class="remove-item-button">❌</a></td>';
            echo '</tr>';

            $item_number++;
        }

        echo '</tbody></table>';
        echo '</div>'; // End Desktop Cart

        echo '<p><button type="submit" class="button" name="custom_cart_update" value="1">Warenkorb aktualisieren</button></p>';
        echo '</form>';

        // Mobile Cart
        echo '<div class="mobile-cart">';
        $item_number = 1;
        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            if ( empty( $cart_item['data'] ) ) {
                continue;
            }
            $product = $cart_item['data'];
            $thumbnail = $product->get_image();
            $product_name = $product->get_name();
            $short_desc = wp_trim_words( $product->get_short_description(), 10, '...' );
            $price = wc_price( $cart_item['line_total'] );
            $remove_url = wc_get_cart_remove_url( $cart_item_key );
            $author_id = get_post_field( 'post_author', $product->get_id() );
            $seller_name = get_the_author_meta( 'display_name', $author_id );

            $quantity_input = woocommerce_quantity_input(
                array(
                    'input_name'  => "cart[{$cart_item_key}][qty]",
                    'input_value' => $cart_item['quantity'],
                    'max_value'   => $product->get_max_purchase_quantity(),
                    'min_value'   => $product->get_min_purchase_quantity(),
                    'product_name'=> $product->get_name(),
                ),
                $product,
                false
            );

            echo '<div class="cart-item-mobile">';
            echo '<div class="cart-item-img">' . $thumbnail . '</div>';
            echo '<div class="cart-item-details">';
            echo '<h3>' . esc_html( $product_name ) . '</h3>';
            echo '<p>' . wp_kses_post( $short_desc ) . '</p>';
            echo '<span class="cart-item-seller">Verkäufer: ' . esc_html( $seller_name ) . '</span>';
            echo '<span class="cart-item-meta">' . wc_get_formatted_cart_item_data( $cart_item, true ) . '</span>';
            echo '<div class="cart-item-qty"><label>Number Of People:</label> ' . $quantity_input . '</div>';
            echo '<span class="cart-item-price">' . $price . '</span>';
            echo '</div>';
            echo '<a href="' . esc_url( $remove_url ) . '" class="remove-item-button-top">❌</a>';
            echo '</div>';

            $item_number++;
        }
        echo '</div>'; // End Mobile Cart

        return ob_get_clean();
    }
    add_shortcode( 'custom_cart_table', 'custom_cart_table_shortcode' );
}






//
// Custom shortcode to display cart meta
//
if ( ! function_exists( 'custom_cart_metadata' ) ) {
    function custom_cart_metadata() {
        ob_start();

        if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
            return ob_get_clean();
        }

        foreach ( WC()->cart->get_cart() as $cart_item ) {
            $item_meta = wc_get_formatted_cart_item_data( $cart_item, true );
            if ( ! empty( $item_meta ) ) {
                echo '<div class="cart-item-meta">' . $item_meta . '</div>';
            }
        }

        return ob_get_clean();
    }
    add_shortcode( 'custom_cart_metadata', 'custom_cart_metadata' );
}

// custom buttons
function custom_rearrange_buttons() {
    global $product;

    if ( ! $product->is_purchasable() ) {
        return;
    }

    // Use WooCommerce's default add to cart form structure
    ?>
    <form class="cart" method="post" enctype='multipart/form-data' style="width: 100%; max-width: 500px; margin: auto;">
    <?php
    // Output WooCommerce's default quantity field
    woocommerce_quantity_input(
        array(
            'min_value'   => $product->get_min_purchase_quantity(),
            'max_value'   => $product->get_max_purchase_quantity(),
            'input_value' => 1,
            'input_name'  => 'quantity',
            'input_class' => 'qty',
        ),
        $product
    );
    ?>

    <!-- Hidden Add to Cart Field -->
    <input type="hidden" name="add-to-cart" value="<?php echo esc_attr($product->get_id()); ?>" />

    <!-- Button Group -->
    <div class="mycustom-cart-buttons" style="display: flex; gap: 10px; margin-top: 15px;">
        <!-- Cancel Button -->
        <button type="button" class="button custombutton close-popup" style="flex: 1; background-color: white; color: #004d35; border: 2px solid #004d35;"><?php _e('Abbrechen', 'woocommerce'); ?></button>

        <!-- Buy Now Button -->
        <button type="submit" name="buy_now" value="1" class="button buy-now" style="flex: 1; background-color: #004d35; color: white; border: none;"><?php _e('Jetzt kaufen', 'woocommerce'); ?></button>

        <!-- Confirm Button -->
        <button type="submit" class="button confirm-button" style="flex: 1; background-color: #004d35; color: white; border: none;"><?php _e('Bestätigen', 'woocommerce'); ?></button>
    </div>
</form>

    <?php
}
remove_action('woocommerce_after_add_to_cart_button', 'custom_rearrange_buttons');
add_action('woocommerce_before_add_to_cart_button', 'custom_rearrange_buttons');




function custom_buy_now_redirect() {
    if (isset($_POST['buy_now']) && isset($_POST['add-to-cart'])) {
        wp_safe_redirect(wc_get_checkout_url());
        exit;
    }
}
// remove default location of quantity
remove_action('woocommerce_before_add_to_cart_button', 'woocommerce_template_single_add_to_cart', 30);

add_action('wp_loaded', 'custom_handle_buy_now_add_to_cart');
function custom_handle_buy_now_add_to_cart() {
    if (
        isset($_POST['buy_now']) &&
        isset($_POST['add-to-cart']) &&
        isset($_POST['quantity'])
    ) {
        $product_id = absint($_POST['add-to-cart']);
        $quantity   = wc_stock_amount($_POST['quantity']);

        // Add product to cart
        WC()->cart->add_to_cart($product_id, $quantity);

        // Redirect to checkout
        wp_safe_redirect(wc_get_checkout_url());
        exit;
    }
}

