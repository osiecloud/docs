<?php
/**
 * WooCommerce Performance Optimization Functions
 * Thêm vào functions.php của theme hoặc tạo plugin riêng
 * 
 * Dành cho website chỉ cần: giỏ hàng, đặt hàng, sản phẩm cơ bản
 */

// ================================
// 1. TẮT SCRIPTS VÀ STYLES KHÔNG CẦN THIẾT
// ================================

/**
 * Tắt WooCommerce CSS/JS trên các trang không cần thiết
 */
add_action('wp_enqueue_scripts', 'optimize_wc_scripts_styles', 99);
function optimize_wc_scripts_styles() {
    // Chỉ load WC assets trên các trang WooCommerce
    if (function_exists('is_woocommerce')) {
        if (!is_woocommerce() && !is_cart() && !is_checkout() && !is_account_page() && !is_shop()) {
            // Tắt CSS
            wp_dequeue_style('woocommerce-layout');
            wp_dequeue_style('woocommerce-smallscreen'); 
            wp_dequeue_style('woocommerce-general');
            wp_dequeue_style('wc-blocks-style');
            
            // Tắt JS
            wp_dequeue_script('wc-cart-fragments');
            wp_dequeue_script('woocommerce');
            wp_dequeue_script('wc-add-to-cart');
            wp_dequeue_script('wc-single-product');
            wp_dequeue_script('wc-checkout');
        }
    }
}

/**
 * Tắt cart fragments AJAX trên trang chủ và các trang không cần
 */
add_action('wp_enqueue_scripts', 'disable_cart_fragments_on_non_wc_pages', 99);
function disable_cart_fragments_on_non_wc_pages() {
    if (is_admin() || is_cart() || is_checkout() || is_account_page()) return;
    
    // Tắt hoàn toàn cart fragments trên trang chủ
    if (is_front_page() || is_home()) {
        wp_dequeue_script('wc-cart-fragments');
        return;
    }
    
    // Trên các trang khác, chỉ tắt nếu không phải trang WC
    if (!is_woocommerce()) {
        wp_dequeue_script('wc-cart-fragments');
    }
}

// ================================
// 2. TẮT CÁC TÍNH NĂNG KHÔNG CẦN THIẾT
// ================================

/**
 * Tắt các tab và sections không cần thiết trong single product
 */
add_action('init', 'remove_wc_single_product_features');
function remove_wc_single_product_features() {
    // Tắt tab reviews
    add_filter('woocommerce_product_tabs', 'remove_product_tabs', 98);
    
    // Tắt related products
    remove_action('woocommerce_output_related_products_args', 'woocommerce_output_related_products_args', 20);
    
    // Tắt upsells
    remove_action('woocommerce_single_product_summary', 'woocommerce_output_upsells', 15);
}

function remove_product_tabs($tabs) {
    unset($tabs['reviews']); // Tắt tab đánh giá
    unset($tabs['additional_information']); // Tắt tab thông tin bổ sung
    return $tabs;
}

/**
 * Tắt cross-sells trong giỏ hàng
 */
remove_action('woocommerce_cart_collaterals', 'woocommerce_cross_sell_display');

/**
 * Tắt breadcrumbs WooCommerce
 */
remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);

/**
 * Tắt sidebar WooCommerce
 */
remove_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);

// ================================
// 3. TỐI ƯU ADMIN VÀ DASHBOARD
// ================================

/**
 * Tắt các tính năng admin không cần thiết
 */
add_filter('woocommerce_admin_features', 'disable_wc_admin_features');
function disable_wc_admin_features($features) {
    return array_diff($features, array(
        'analytics',
        'remote-inbox-notifications', 
        'remote-free-extensions',
        'payment-gateway-suggestions',
        'shipping-label-banner',
        'subscriptions',
        'onboarding',
        'wc-admin-onboarding-tasks',
        'marketing',
        'mobile-app-banner',
        'navigation',
        'overview',
        'activity-panels',
        'analytics-dashboard/customizable',
        'store-alerts'
    ));
}

/**
 * Tắt marketplace suggestions
 */
add_filter('woocommerce_allow_marketplace_suggestions', '__return_false');
add_filter('woocommerce_show_marketplace_suggestions', '__return_false');

/**
 * Tắt usage tracking
 */
add_filter('woocommerce_tracker_send_override', '__return_false');

/**
 * Tắt setup wizard
 */
add_filter('woocommerce_enable_setup_wizard', '__return_false');

/**
 * Tắt WooCommerce dashboard widgets
 */
add_action('wp_dashboard_setup', 'remove_wc_dashboard_widgets');
function remove_wc_dashboard_widgets() {
    remove_meta_box('woocommerce_dashboard_status', 'dashboard', 'normal');
    remove_meta_box('woocommerce_dashboard_recent_reviews', 'dashboard', 'normal');
}

/**
 * Tắt admin notices WooCommerce
 */
add_action('wp_loaded', 'remove_wc_admin_notices');
function remove_wc_admin_notices() {
    if (class_exists('WC_Admin_Notices')) {
        remove_action('admin_notices', array('WC_Admin_Notices', 'output_notices'), 10);
    }
}

// ================================
// 4. TỐI ƯU DATABASE VÀ QUERIES
// ================================

/**
 * Tăng threshold cho AJAX variation để giảm queries
 */
add_filter('woocommerce_ajax_variation_threshold', 'increase_wc_variation_threshold', 10, 2);
function increase_wc_variation_threshold($threshold, $product) {
    return 100; // Tăng từ 30 lên 100
}

/**
 * Disable WooCommerce sessions trên trang không cần thiết
 */
add_action('init', 'optimize_wc_sessions', 1);
function optimize_wc_sessions() {
    if (is_admin()) return;
    
    // Tắt session trên trang chủ và trang không liên quan WC
    if (is_front_page() || is_home() || (!is_woocommerce() && !is_cart() && !is_checkout() && !is_account_page())) {
        add_filter('woocommerce_session_use_secure_cookie', '__return_false');
        
        // Prevent session start
        if (class_exists('WC_Session_Handler')) {
            remove_action('wp_loaded', array('WC_Session_Handler', 'init'));
        }
    }
}

/**
 * Tối ưu transients
 */
add_action('init', 'optimize_wc_transients');
function optimize_wc_transients() {
    // Xóa các transients cũ không cần thiết
    delete_transient('wc_count_comments');
    delete_transient('woocommerce_cache_excluded_uris');
    delete_transient('wc_report_sales_by_date');
}

/**
 * Tắt geolocation để giảm queries
 */
add_filter('woocommerce_geolocate_ip', '__return_false');
add_filter('pre_option_woocommerce_default_customer_address', function() {
    return 'base';
});

// ================================
// 5. TỐI ƯU FRONTEND PERFORMANCE
// ================================

/**
 * Tắt password strength meter
 */
add_action('wp_print_scripts', 'disable_wc_password_strength_meter', 100);
function disable_wc_password_strength_meter() {
    if (!is_admin() && !is_checkout() && !is_account_page()) {
        wp_dequeue_script('wc-password-strength-meter');
        wp_dequeue_script('password-strength-meter');
    }
}

/**
 * Lazy load cho WooCommerce images
 */
add_filter('wp_get_attachment_image_attributes', 'add_lazy_loading_to_wc_images', 10, 3);
function add_lazy_loading_to_wc_images($attr, $attachment, $size) {
    if (!is_admin()) {
        $attr['loading'] = 'lazy';
        $attr['decoding'] = 'async';
    }
    return $attr;
}

/**
 * Tắt product gallery features không cần thiết
 */
add_action('after_setup_theme', 'remove_wc_gallery_features');
function remove_wc_gallery_features() {
    remove_theme_support('wc-product-gallery-zoom');
    remove_theme_support('wc-product-gallery-lightbox'); 
    remove_theme_support('wc-product-gallery-slider');
}

/**
 * Tối ưu product gallery - chỉ load khi cần
 */
add_filter('woocommerce_single_product_image_thumbnail_html', 'optimize_wc_product_gallery', 10, 2);
function optimize_wc_product_gallery($html, $post_thumbnail_id) {
    if (!is_product()) {
        return '';
    }
    return $html;
}

// ================================
// 6. TẮT WIDGETS KHÔNG CẦN THIẾT
// ================================

/**
 * Unregister WooCommerce widgets không sử dụng
 */
add_action('widgets_init', 'unregister_wc_widgets', 11);
function unregister_wc_widgets() {
    unregister_widget('WC_Widget_Products');
    unregister_widget('WC_Widget_Product_Categories'); 
    unregister_widget('WC_Widget_Product_Tag_Cloud');
    unregister_widget('WC_Widget_Cart');
    unregister_widget('WC_Widget_Layered_Nav');
    unregister_widget('WC_Widget_Layered_Nav_Filters');
    unregister_widget('WC_Widget_Price_Filter');
    unregister_widget('WC_Widget_Product_Search');
    unregister_widget('WC_Widget_Recently_Viewed');
    unregister_widget('WC_Widget_Top_Rated_Products');
}

// ================================
// 7. TỐI ƯU CHO MOBILE
// ================================

/**
 * Tối ưu cho mobile - tắt các tính năng nặng
 */
add_action('wp_enqueue_scripts', 'optimize_wc_for_mobile');
function optimize_wc_for_mobile() {
    if (wp_is_mobile()) {
        // Tắt zoom trên mobile
        wp_dequeue_script('zoom');
        wp_dequeue_script('wc-single-product');
        
        // Giảm số lượng related products trên mobile
        add_filter('woocommerce_output_related_products_args', 'reduce_related_products_mobile');
    }
}

function reduce_related_products_mobile($args) {
    if (wp_is_mobile()) {
        $args['posts_per_page'] = 2; // Giảm từ 4 xuống 2
        $args['columns'] = 2;
    }
    return $args;
}

// ================================
// 8. CACHE VÀ OPTIMIZATION HEADERS
// ================================

/**
 * Thêm cache headers cho WooCommerce assets
 */
add_action('wp_enqueue_scripts', 'add_wc_cache_headers', 999);
function add_wc_cache_headers() {
    if (!is_admin()) {
        // Cache static assets lâu hơn
        if (is_shop() || is_product_category() || is_product_tag()) {
            header('Cache-Control: public, max-age=3600'); // 1 hour
        }
    }
}

/**
 * Preload critical WooCommerce resources
 */
add_action('wp_head', 'preload_wc_critical_resources');
function preload_wc_critical_resources() {
    if (is_shop() || is_product_category() || is_product()) {
        echo '<link rel="preload" href="' . WC()->plugin_url() . '/assets/css/woocommerce.css" as="style">';
        echo '<link rel="preload" href="' . WC()->plugin_url() . '/assets/js/frontend/woocommerce.min.js" as="script">';
    }
}

// ================================
// 9. GIẢM POSTS PER PAGE TRONG ADMIN
// ================================

/**
 * Giảm số lượng products hiển thị trong admin để tăng tốc
 */
add_filter('edit_posts_per_page', 'reduce_wc_admin_posts_per_page', 10, 2);
function reduce_wc_admin_posts_per_page($posts_per_page, $post_type) {
    if ($post_type === 'product') {
        return 20; // Giảm từ 50 xuống 20
    }
    if ($post_type === 'shop_order') {
        return 25; // Giảm orders hiển thị
    }
    return $posts_per_page;
}

// ================================
// 10. DISABLE AUTOMATIC UPDATES
// ================================

/**
 * Tắt automatic updates checking để giảm HTTP requests
 */
add_filter('automatic_updater_disabled', '__return_true');
remove_action('init', 'wp_schedule_update_checks');

/**
 * Tắt heartbeat API trên admin pages không cần thiết
 */
add_action('init', 'optimize_heartbeat');
function optimize_heartbeat() {
    if (is_admin()) {
        $screen = get_current_screen();
        if ($screen && in_array($screen->id, ['edit-product', 'edit-shop_order'])) {
            wp_deregister_script('heartbeat');
        }
    }
}