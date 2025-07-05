<?php
// Prevent direct access
if (!defined('ABSPATH')) exit;

// Load theme's header
get_header();

// Load Elementor Main Vendor Page Template
echo do_shortcode('[elementor-template id="13297"]');

// Add button to open popup
// echo '<button id="open-popup-btn">View Reviews</button>';

$rating = dokan_get_readable_seller_rating($store_user->ID);

echo '<div id="reviews-popup" class="reviews-popup-overlay">
        <div class="reviews-popup-content">
            <span class="close-popup">&times;</span>
            <h2>' . esc_html($store_info['store_name']) . ' Reviews</h2>
            <div class="reviews-container">';

// Vendor ka ID le raha hoon
$seller_id = get_query_var('author');

// Vendor ke products fetch kar raha hoon
$args = array(
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'author'         => $seller_id, 
);
$products = new WP_Query($args);

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
            echo '<div class="review-item">
                <div class="review-header">
                    <span class="reviewer-name">' . esc_html($comment->comment_author) . '</span>
                    <div class="review-rating">';
                    for ($i = 1; $i <= 5; $i++) {
                        if ($i <= $rating) {
                            echo '<i class="fas fa-star"></i>';
                        } else {
                            echo '<i class="far fa-star"></i>';
                        }
                    }
            echo '</div>
                </div>
                <div class="review-content">' . esc_html($comment->comment_content) . '</div>
                <div class="review-date">' . date('F j, Y', strtotime($comment->comment_date)) . '</div>
            </div>';
        }
    } else {
        echo '<p>No reviews yet.</p>';
    }
} else {
    echo '<p>No products found for this vendor.</p>';
}

echo '</div></div></div>';

// Load theme's footer
get_footer();
?>

<style>
    /* Open button styling */
    #open-popup-btn {
        background: #004a31;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        margin: 20px 0;
        display: block;
    }

    .reviews-popup-overlay {
        display: none; /* Hide by default */
        justify-content: center;
        align-items: center;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        z-index: 999;
    }

    .reviews-popup-content {
        background: #fff;
        padding: 20px;
        border-radius: 10px;
        width: 50%;
        max-width: 600px;
        text-align: center;
        box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.2);
        animation: fadeIn 0.3s ease-in-out;
        position: relative;
    }

    /* Close button styling */
    .close-popup {
        position: absolute;
        top: 10px;
        right: 15px;
        font-size: 20px;
        cursor: pointer;
        color: #333;
    }

    .reviews-container {
        margin-top: 20px;
        max-height: 300px; /* Max height set for scrolling */
        overflow-y: auto; /* Enables scroll */
        padding-right: 10px; /* Space for scrollbar */
    }

    .review-item {
        background: #f8f8f8;
        padding: 15px;
        margin: 10px 0;
        border-radius: 5px;
        box-shadow: 0px 3px 10px rgba(0, 0, 0, 0.1);
    }

    .review-header {
        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
    }

    .reviewer-name {
        font-weight: bold;
        font-size: 16px;
        color: #333;
    }

    .review-rating i {
        color: #FFD700; /* Golden stars */
        margin: 2px;
    }

    .review-content {
        font-size: 14px;
        margin-top: 10px;
        color: #555;
    }

    .review-date {
        font-size: 12px;
        color: #888;
        margin-top: 5px;
    }

    /* Animation */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: scale(0.9);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }
</style>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        let openPopupBtn = document.getElementById("open-popup-btn");
        let closePopupBtn = document.querySelector(".close-popup");
        let popup = document.getElementById("reviews-popup");

        openPopupBtn.addEventListener("click", function() {
            popup.style.display = "flex";
        });

        closePopupBtn.addEventListener("click", function() {
            popup.style.display = "none";
        });

        // Close popup on clicking outside content
        popup.addEventListener("click", function(event) {
            if (event.target === popup) {
                popup.style.display = "none";
            }
        });
    });
</script>
