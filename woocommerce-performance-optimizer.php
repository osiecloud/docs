<?php
/**
 * Plugin Name: WooCommerce Performance Optimizer
 * Plugin URI: https://your-website.com
 * Description: Tối ưu WooCommerce bằng cách tắt các tính năng không cần thiết cho website chỉ cần giỏ hàng, đặt hàng và sản phẩm cơ bản
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: wc-performance-optimizer
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WC_Performance_Optimizer {
    
    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    public function init() {
        // Chỉ chạy nếu WooCommerce đã được kích hoạt
        if (!class_exists('WooCommerce')) {
            return;
        }
        
        $this->disable_unnecessary_scripts_styles();
        $this->disable_woocommerce_features();
        $this->optimize_queries();
        $this->optimize_frontend();
        $this->optimize_admin();
    }
    
    /**
     * Tắt các scripts và styles không cần thiết
     */
    private function disable_unnecessary_scripts_styles() {
        // Tắt WooCommerce styles và scripts không cần thiết
        add_action('wp_enqueue_scripts', array($this, 'disable_woocommerce_loading_css_js'), 99);
        
        // Tắt WooCommerce cart fragments trên trang không cần thiết
        add_action('wp_enqueue_scripts', array($this, 'disable_woocommerce_cart_fragments'), 99);
        
        // Tắt password strength meter
        add_action('wp_print_scripts', array($this, 'disable_password_strength_meter'), 100);
    }
    
    /**
     * Tắt các tính năng WooCommerce không cần thiết
     */
    private function disable_woocommerce_features() {
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
        
        // Tắt WooCommerce breadcrumbs
        remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);
        
        // Tắt WooCommerce sidebar
        remove_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);
        
        // Tắt các admin features
        add_filter('woocommerce_admin_features', array($this, 'disable_admin_features'));
        
        // Tắt marketplace suggestions
        add_filter('woocommerce_allow_marketplace_suggestions', '__return_false');
        add_filter('woocommerce_show_marketplace_suggestions', '__return_false');
        
        // Tắt usage tracking
        add_filter('woocommerce_tracker_send_override', '__return_false');
        
        // Tắt setup wizard
        add_filter('woocommerce_enable_setup_wizard', '__return_false');
        
        // Tắt widgets không cần thiết
        add_action('widgets_init', array($this, 'unregister_wc_widgets'), 11);
        
        // Tắt image features không cần thiết
        add_action('after_setup_theme', array($this, 'remove_woocommerce_image_features'));
    }
    
    /**
     * Tối ưu database queries
     */
    private function optimize_queries() {
        // Giảm số lượng queries cho product variations
        add_filter('woocommerce_ajax_variation_threshold', array($this, 'increase_variation_threshold'), 10, 2);
        
        // Disable WooCommerce session trên trang không cần thiết
        add_action('init', array($this, 'disable_woocommerce_sessions'));
        
        // Tối ưu transients
        add_action('init', array($this, 'optimize_woocommerce_transients'));
        
        // Tắt geolocation
        add_filter('woocommerce_geolocate_ip', '__return_false');
        add_filter('pre_option_woocommerce_default_customer_address', function() {
            return 'base';
        });
    }
    
    /**
     * Tối ưu frontend
     */
    private function optimize_frontend() {
        // Lazy load images
        add_filter('wp_get_attachment_image_attributes', array($this, 'add_lazy_loading_to_wc_images'), 10, 3);
        
        // Tối ưu gallery
        add_filter('woocommerce_single_product_image_thumbnail_html', array($this, 'optimize_product_gallery'), 10, 2);
    }
    
    /**
     * Tối ưu admin
     */
    private function optimize_admin() {
        // Tắt dashboard widgets
        add_action('wp_dashboard_setup', array($this, 'remove_woocommerce_dashboard_widgets'));
        
        // Giảm posts per page
        add_filter('edit_posts_per_page', array($this, 'reduce_admin_posts_per_page'), 10, 2);
        
        // Tắt admin notices
        add_action('wp_loaded', array($this, 'remove_admin_notices'));
    }
    
    /**
     * Callback functions
     */
    public function disable_woocommerce_loading_css_js() {
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
    
    public function disable_woocommerce_cart_fragments() {
        if (is_admin()) return;
        
        if (!is_woocommerce() && !is_cart() && !is_checkout()) {
            wp_dequeue_script('wc-cart-fragments');
        }
    }
    
    public function disable_password_strength_meter() {
        if (!is_admin()) {
            wp_dequeue_script('wc-password-strength-meter');
            wp_dequeue_script('password-strength-meter');
        }
    }
    
    public function disable_admin_features($features) {
        return array_diff($features, array(
            'analytics',
            'remote-inbox-notifications',
            'remote-free-extensions',
            'payment-gateway-suggestions',
            'shipping-label-banner',
            'subscriptions',
            'onboarding',
            'wc-admin-onboarding-tasks',
            'marketing'
        ));
    }
    
    public function unregister_wc_widgets() {
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
    
    public function remove_woocommerce_image_features() {
        remove_theme_support('wc-product-gallery-zoom');
        remove_theme_support('wc-product-gallery-lightbox');
        remove_theme_support('wc-product-gallery-slider');
    }
    
    public function increase_variation_threshold($threshold, $product) {
        return 100;
    }
    
    public function disable_woocommerce_sessions() {
        if (!is_admin() && !is_woocommerce() && !is_cart() && !is_checkout() && !is_account_page()) {
            if (class_exists('WC_Session_Handler')) {
                remove_action('wp_loaded', array('WC_Session_Handler', 'init'));
            }
        }
    }
    
    public function optimize_woocommerce_transients() {
        delete_transient('wc_count_comments');
        delete_transient('woocommerce_cache_excluded_uris');
    }
    
    public function add_lazy_loading_to_wc_images($attr, $attachment, $size) {
        if (!is_admin()) {
            $attr['loading'] = 'lazy';
        }
        return $attr;
    }
    
    public function optimize_product_gallery($html, $post_thumbnail_id) {
        if (!is_product()) {
            return '';
        }
        return $html;
    }
    
    public function remove_woocommerce_dashboard_widgets() {
        remove_meta_box('woocommerce_dashboard_status', 'dashboard', 'normal');
        remove_meta_box('woocommerce_dashboard_recent_reviews', 'dashboard', 'normal');
    }
    
    public function reduce_admin_posts_per_page($posts_per_page, $post_type) {
        if ($post_type === 'product') {
            return 20;
        }
        return $posts_per_page;
    }
    
    public function remove_admin_notices() {
        if (class_exists('WC_Admin_Notices')) {
            remove_action('admin_notices', array('WC_Admin_Notices', 'output_notices'), 10);
        }
    }
}

// Khởi tạo plugin
new WC_Performance_Optimizer();