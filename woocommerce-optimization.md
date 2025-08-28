# Tối ưu WooCommerce - Tắt Filter và Query không cần thiết

Hướng dẫn này sẽ giúp bạn tối ưu WooCommerce bằng cách tắt các filter, hook và query không cần thiết cho một website chỉ cần các chức năng cơ bản: giỏ hàng, đặt hàng, và sản phẩm.

## 1. Tắt các Hook và Filter không cần thiết

### Tạo file functions.php hoặc plugin tối ưu

```php
<?php
/**
 * WooCommerce Performance Optimization
 * Tắt các hook và filter không cần thiết
 */

// Tắt WooCommerce styles và scripts không cần thiết
add_action('wp_enqueue_scripts', 'disable_woocommerce_loading_css_js', 99);
function disable_woocommerce_loading_css_js() {
    // Chỉ load WC styles/scripts trên các trang cần thiết
    if (function_exists('is_woocommerce')) {
        if (!is_woocommerce() && !is_cart() && !is_checkout() && !is_account_page()) {
            // Tắt WooCommerce CSS
            wp_dequeue_style('woocommerce-layout');
            wp_dequeue_style('woocommerce-smallscreen');
            wp_dequeue_style('woocommerce-general');
            
            // Tắt WooCommerce JS
            wp_dequeue_script('wc-cart-fragments');
            wp_dequeue_script('woocommerce');
            wp_dequeue_script('wc-add-to-cart');
        }
    }
}

// Tắt WooCommerce cart fragments trên trang không cần thiết
add_action('wp_enqueue_scripts', 'disable_woocommerce_cart_fragments', 99);
function disable_woocommerce_cart_fragments() {
    if (is_admin()) return;
    
    // Chỉ load cart fragments trên các trang cần thiết
    if (!is_woocommerce() && !is_cart() && !is_checkout()) {
        wp_dequeue_script('wc-cart-fragments');
    }
}

// Tắt WooCommerce reviews
remove_action('woocommerce_single_product_summary', 'woocommerce_output_product_data_tabs', 25);
remove_action('woocommerce_product_tabs', 'woocommerce_product_reviews_tab', 30);

// Tắt related products
remove_action('woocommerce_output_related_products_args', 'woocommerce_output_related_products_args', 20);
remove_action('woocommerce_single_product_summary', 'woocommerce_output_related_products_args', 25);

// Tắt cross-sells trong cart
remove_action('woocommerce_cart_collaterals', 'woocommerce_cross_sell_display');

// Tắt up-sells
remove_action('woocommerce_single_product_summary', 'woocommerce_output_upsells', 15);

// Tắt WooCommerce breadcrumbs nếu không cần
remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);

// Tắt WooCommerce sidebar
remove_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);
```

## 2. Tắt các tính năng WooCommerce không cần thiết

```php
<?php
/**
 * Tắt các tính năng WooCommerce không sử dụng
 */

// Tắt WooCommerce reports và analytics
add_filter('woocommerce_admin_features', 'disable_woocommerce_features');
function disable_woocommerce_features($features) {
    return array_diff($features, array(
        'analytics',
        'remote-inbox-notifications',
        'remote-free-extensions',
        'payment-gateway-suggestions',
        'shipping-label-banner',
        'subscriptions',
        'onboarding',
        'wc-admin-onboarding-tasks',
    ));
}

// Tắt WooCommerce marketing features
add_filter('woocommerce_allow_marketplace_suggestions', '__return_false');
add_filter('woocommerce_show_marketplace_suggestions', '__return_false');

// Tắt WooCommerce usage tracking
add_filter('woocommerce_tracker_send_override', '__return_false');

// Tắt WooCommerce setup wizard
add_filter('woocommerce_enable_setup_wizard', '__return_false');

// Tắt WooCommerce admin notices
add_action('wp_loaded', function() {
    remove_action('admin_notices', array('WC_Admin_Notices', 'output_notices'), 10);
});

// Tắt WooCommerce widgets không cần thiết
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
}
```

## 3. Tối ưu Database Queries

```php
<?php
/**
 * Tối ưu WooCommerce Database Queries
 */

// Giảm số lượng queries cho product variations
add_filter('woocommerce_ajax_variation_threshold', 'increase_variation_threshold', 10, 2);
function increase_variation_threshold($threshold, $product) {
    return 100; // Tăng threshold để giảm AJAX calls
}

// Tắt automatic updates checking
remove_action('init', 'wp_schedule_update_checks');
add_filter('automatic_updater_disabled', '__return_true');

// Disable WooCommerce session nếu không cần thiết trên trang chủ
add_action('init', 'disable_woocommerce_sessions');
function disable_woocommerce_sessions() {
    if (!is_admin() && !is_woocommerce() && !is_cart() && !is_checkout() && !is_account_page()) {
        remove_action('wp_loaded', array('WC_Session_Handler', 'init'));
    }
}

// Tối ưu WooCommerce transients
add_action('init', 'optimize_woocommerce_transients');
function optimize_woocommerce_transients() {
    // Xóa transients cũ
    delete_transient('wc_count_comments');
    delete_transient('woocommerce_cache_excluded_uris');
}

// Tắt WooCommerce geolocation nếu không cần
add_filter('woocommerce_geolocate_ip', '__return_false');
add_filter('pre_option_woocommerce_default_customer_address', function() {
    return 'base';
});
```

## 4. Tối ưu Frontend Performance

```php
<?php
/**
 * Tối ưu Frontend Performance
 */

// Tắt WooCommerce password strength meter
add_action('wp_print_scripts', 'disable_password_strength_meter', 100);
function disable_password_strength_meter() {
    if (!is_admin()) {
        wp_dequeue_script('wc-password-strength-meter');
        wp_dequeue_script('password-strength-meter');
    }
}

// Tối ưu WooCommerce gallery
add_filter('woocommerce_single_product_image_thumbnail_html', 'optimize_product_gallery', 10, 2);
function optimize_product_gallery($html, $post_thumbnail_id) {
    // Chỉ load gallery khi cần thiết
    if (!is_product()) {
        return '';
    }
    return $html;
}

// Lazy load WooCommerce images
add_filter('wp_get_attachment_image_attributes', 'add_lazy_loading_to_wc_images', 10, 3);
function add_lazy_loading_to_wc_images($attr, $attachment, $size) {
    if (!is_admin()) {
        $attr['loading'] = 'lazy';
    }
    return $attr;
}

// Tắt WooCommerce zoom và lightbox nếu không cần
add_action('after_setup_theme', 'remove_woocommerce_image_features');
function remove_woocommerce_image_features() {
    remove_theme_support('wc-product-gallery-zoom');
    remove_theme_support('wc-product-gallery-lightbox');
    remove_theme_support('wc-product-gallery-slider');
}
```

## 5. Tối ưu Admin Performance

```php
<?php
/**
 * Tối ưu WooCommerce Admin
 */

// Tắt WooCommerce admin dashboard widgets
add_action('wp_dashboard_setup', 'remove_woocommerce_dashboard_widgets');
function remove_woocommerce_dashboard_widgets() {
    remove_meta_box('woocommerce_dashboard_status', 'dashboard', 'normal');
    remove_meta_box('woocommerce_dashboard_recent_reviews', 'dashboard', 'normal');
}

// Giảm số lượng posts per page trong admin
add_filter('edit_posts_per_page', 'reduce_admin_posts_per_page', 10, 2);
function reduce_admin_posts_per_page($posts_per_page, $post_type) {
    if ($post_type === 'product') {
        return 20; // Giảm từ 50 xuống 20
    }
    return $posts_per_page;
}

// Tắt WooCommerce marketing hub
add_filter('woocommerce_admin_features', function($features) {
    return array_diff($features, ['marketing']);
});
```

## 6. Cách sử dụng

### Cách 1: Thêm vào functions.php của theme
```php
// Thêm tất cả code trên vào file functions.php của theme active
```

### Cách 2: Tạo plugin tối ưu riêng
Tạo file `woocommerce-performance-optimizer.php`:

```php
<?php
/**
 * Plugin Name: WooCommerce Performance Optimizer
 * Description: Tối ưu WooCommerce bằng cách tắt các tính năng không cần thiết
 * Version: 1.0
 * Author: Your Name
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Thêm tất cả code tối ưu ở trên vào đây
```

## 7. Monitoring và Testing

### Kiểm tra hiệu suất:
1. Sử dụng tools như GTmetrix, PageSpeed Insights
2. Kiểm tra số lượng database queries với Query Monitor plugin
3. Test các chức năng cơ bản: thêm vào giỏ hàng, checkout, thanh toán

### Lưu ý quan trọng:
- Backup website trước khi áp dụng
- Test từng phần một để đảm bảo không bị lỗi
- Một số tối ưu có thể ảnh hưởng đến theme/plugin khác
- Điều chỉnh theo nhu cầu cụ thể của website

## 8. Tối ưu Database (Advanced)

```sql
-- Xóa các transients cũ
DELETE FROM wp_options WHERE option_name LIKE '_transient_%';
DELETE FROM wp_options WHERE option_name LIKE '_site_transient_%';

-- Tối ưu bảng wp_options
DELETE FROM wp_options WHERE option_name LIKE 'woocommerce_queue_batch_%';
DELETE FROM wp_options WHERE option_name LIKE '_wc_session_%';
```

Với những tối ưu này, website WooCommerce của bạn sẽ chạy nhanh hơn đáng kể, đặc biệt khi chỉ cần các chức năng cơ bản như giỏ hàng, đặt hàng và hiển thị sản phẩm.