<?php
/*   
* Plugin Name: Wishlink Pixel  
* Plugin URI:  
* Description: Wishlink(WL) Pixel tracks the purchases from the traffic sent by WL affiliated sources, made on the woocommerce enabled website.  
* Author: Wishlink   
* Version: 1.0   
* Author URI:   
* License: GPL3+   
* Text Domain:   
* Domain Path: /languages/   
*/

if ( in_array( 'woocommerce/woocommerce.php', get_option('active_plugins'))) {
    
    define('Wishlink_URL', 'https://api.wishlink.com/api/markSale?');


    add_action( 'admin_menu', 'wishlink_add_settings_page' );
    function wishlink_add_settings_page() {
        add_options_page( 'Wishlink Plugin Settings', 'Wishlink Plugin Menu', 'manage_options', 'wishlink-plugin', 'wishlink_render_plugin_settings_page');
    }

    function wishlink_render_plugin_settings_page() {
        ?>
        <h2>Wishlink Plugin Settings</h2>
        <form action="options.php" method="post">
            <?php 
            settings_fields( 'wishlink_plugin_options' );
            do_settings_sections( 'wishlink_plugin' ); ?>
            <input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e( 'Save' ); ?>" />
        </form>
        <?php
    }

    add_action( 'admin_init', 'wishlink_register_settings' );
    function wishlink_register_settings() {
        register_setting( 'wishlink_plugin_options', 'wishlink_plugin_options');
        add_settings_section( 'wishlink_settings', 'Wishlink Settings', 'wishlink_plugin_section_text', 'wishlink_plugin' );
        add_settings_field( 'wishlink_plugin_setting_platform', 'Platform Name', 'wishlink_plugin_setting_platform', 'wishlink_plugin', 'wishlink_settings');
        add_settings_field('wishlink_plugin_setting_currency', 'Currency Code', 'wishlink_plugin_setting_currency', 'wishlink_plugin', 'wishlink_settings');
    }

    function wishlink_plugin_section_text() {
        echo '<p>Here you can set all the options for using the Wishlink Plugin</p>';
    }

    function wishlink_plugin_setting_platform() {
        $options = get_option( 'wishlink_plugin_options');
        echo "<input id='wishlink_plugin_setting_platform' name='wishlink_plugin_options[platform]' placeholder='My Wordpress Site' type='text' value='" . esc_attr( $options['platform'] ) . "' />";
    }

    function wishlink_plugin_setting_currency() {
        $options = get_option( 'wishlink_plugin_options');
        echo "<input id='wishlink_plugin_setting_currency' name='wishlink_plugin_options[currency]' placeholder='INR' type='text' value='" . esc_attr( $options['currency'] ) . "' />";
    }


    add_action('wp_enqueue_scripts', 'wishlink_save_user_click_id_info');
    function wishlink_save_user_click_id_info(){
        wp_register_script( "wishlink_landing", plugin_dir_url( __FILE__ ).'js/wishlink-landing.js');
        wp_localize_script("wishlink_landing", "wishlinkLanding", array('platform' => esc_attr(get_option('wishlink_plugin_options')['platform'])));
        wp_enqueue_script("wishlink_landing");
    }
    

    function wishlink_calculate_previous_orders( $value = 0 ) {
        if ( ! is_user_logged_in() && $value === 0 ) {
            return 0;
        }
    
        global $wpdb;
        
        if ( is_numeric( $value) ) { 
            $meta_key   = '_customer_user';
            $meta_value = $value == 0 ? (int) get_current_user_id() : (int) $value;
        } 
        else { 
            $meta_key   = '_billing_email';
            $meta_value = sanitize_email( $value );
        }
        
        $paid_order_statuses = array_map( 'esc_sql', wc_get_is_paid_statuses() );
    
        $count = $wpdb->get_var( $wpdb->prepare("
            SELECT COUNT(p.ID) FROM {$wpdb->prefix}posts AS p
            INNER JOIN {$wpdb->prefix}postmeta AS pm ON p.ID = pm.post_id
            WHERE p.post_status IN ( 'wc-" . implode( "','wc-", $paid_order_statuses ) . "' )
            AND p.post_type LIKE 'shop_order'
            AND pm.meta_key = '%s'
            AND pm.meta_value = %s
            LIMIT 1
        ", $meta_key, $meta_value ) );
        return $count;
    }

    function wishlink_previous_orders_of_user($order){

        $user = $order->get_user();
        $order_count = 0;
        if($user === false){ // Check if the user is a guest or not
            $email = $order->get_billing_email();
            $order_count = wishlink_calculate_previous_orders($email);
        }
        else{
            $order_count = wishlink_calculate_previous_orders($order->get_user_id());
        }
        return $order_count;
    }


    add_action( 'woocommerce_thankyou', 'wishlink_script_enqueue_js');
    function wishlink_script_enqueue_js($order_id) {
        
        $order = wc_get_order($order_id);
        $num_orders = wishlink_previous_orders_of_user($order);

        $line_items = $order->get_items();
        $order_details = "~";
        foreach ( $line_items as $item ) {
            $product = $order->get_product_from_item( $item );
            $product_name = $product->get_name();
            $qty = $item['qty'];
            $order_details .= $product_name . "**" . $qty . "~";
        }
        $wishlink_options = get_option('wishlink_plugin_options');
        $platform = $wishlink_options['platform'];
        $currency = $wishlink_options['currency'];
        $coupon_codes_list = $order->get_coupon_codes();
        $coupon_code = 'null';
        if(count($coupon_codes_list) > 0){
            $coupon_code = $coupon_codes_list[0];
        }
        

        wp_register_script( "wishlink_send_tracking_data", plugin_dir_url( __FILE__ ).'js/wishlink-send-tracking-data.js');
        wp_localize_script( 'wishlink_send_tracking_data', 'wishlinkAjax',
         array( 'url' => Wishlink_URL,
         'saleAmount' => $order->get_total(),
         'platform' => $platform,
         'currency' => $currency,
         'orderId' => $order_id,
         'items' => $order_details,
         'couponCode' => $coupon_code,
         'numOrders' => $num_orders,
        ));
        
        wp_enqueue_script( 'wishlink_send_tracking_data' );
    }
} 
?>
