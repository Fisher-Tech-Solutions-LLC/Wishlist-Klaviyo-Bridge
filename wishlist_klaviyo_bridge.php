<?php 
/*
* Plugin Name: Wishlist Klaviyo Bridge
* Description: This is a plugin to bridge the gap between wishlist and klaviyo
* Version: 1.0
* Author: Fisher Tech Solutions LLC
* Author URI: https://www.fishertechsolutions.com
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$yith = 'yith-woocommerce-wishlist/init.php';
$klaviyo = 'klaviyo/klaviyo.php';

if (!function_exists('is_plugin_active')) {
    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}

function klaviyo_wishlist_bridge_settings() {
    register_setting('klaviyo_private_api_key', 'klaviyo_private_api_key');

    add_settings_section('klaviyo_private_api_key', 'Klaviyo Private API Key', 'klaviyo_private_api_key_section_callback', 'klaviyo_private_api_key');
    add_settings_field('klaviyo_private_api_key', 'Klaviyo Private API Key', 'klaviyo_private_api_key_callback', 'klaviyo_private_api_key', 'klaviyo_private_api_key');
}

function klaviyo_private_api_key_section_callback() {
    echo '<p>Enter your Klaviyo private API key here.</p>';
}

function klaviyo_private_api_key_callback() {
    $klaviyo_private_api_key = get_option('klaviyo_private_api_key');
    $klaviyo_private_api_key = apply_filters('klaviyo_private_api_key', $klaviyo_private_api_key);

    echo '<input type="text" id="klaviyo_private_api_key" name="klaviyo_private_api_key" value="' . $klaviyo_private_api_key . '" />';
}

function klaviyo_wishlist_bridge_options_page() {
    add_options_page('Klaviyo Wishlist Bridge', 'Klaviyo Wishlist Bridge', 'manage_options', 'klaviyo-wishlist-bridge', 'klaviyo_wishlist_bridge_options_page_html');
}

function klaviyo_wishlist_bridge_options_page_html() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_GET['settings-updated'])) {
        add_settings_error('klaviyo_wishlist_bridge_messages', 'klaviyo_wishlist_bridge_message', 'Settings Saved', 'updated');
    }

    settings_errors('klaviyo_wishlist_bridge_messages');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('klaviyo_private_api_key');
            do_settings_sections('klaviyo_private_api_key');
            submit_button('Save Settings');
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', 'klaviyo_wishlist_bridge_settings');

add_action('admin_menu', 'klaviyo_wishlist_bridge_options_page');

if (is_plugin_active($yith) && is_plugin_active($klaviyo) ) {
    add_action( 'yith_wcwl_adding_to_wishlist', 'klaviyo_add_to_wishlist', 10, 2 );

    function klaviyo_add_to_wishlist($product_id, $wishlist_id) {
        // TODO - get the private api key from a settings page
        $private_api_key = get_option('klaviyo_private_api_key');

        // get the current users email
        $current_user = wp_get_current_user();
        $customer_email = $current_user->user_email;


        $product_value = get_post_meta($product_id, '_price', true);

        $time = date('Y-m-d\TH:i:sP');

        $categories = array();
        $categories_array = get_the_terms($product_id, 'product_cat');
        if ($categories_array && !is_wp_error($categories_array)) {
            $categories = wp_list_pluck($categories_array, 'name');
        }

        $time = date('Y-m-d\TH:i:sP');

        $request_args = array(
            'body' => json_encode(array(
                'data' => array(
                    'type' => 'event',
                    'attributes' => array(
                        'properties' => array(
                            'ProductId' => $product_id,
                            'ProductName' => get_the_title($product_id),
                            'ProductURL' => get_the_permalink($product_id),
                            'ProductImage' => get_the_post_thumbnail_url($product_id),
                            'ProductValue' => $product_value,
                            'ProductCategories' => implode(', ', $categories),
                        ),
                        'time' => $time,
                        'value' => $product_value,
                        'value_currency' => 'USD',
                        'metric' => array(
                            'data' => array(
                                'type' => 'metric',
                                'attributes' => array(
                                    'name' => 'Added To Wishlist',
                                ),
                            ),
                        ),
                        'profile' => array(
                            'data' => array(
                                'type' => 'profile',
                                'attributes' => array(
                                    'email' => $customer_email,
                                ),
                            ),
                        ),
                    ),
                ),
            )),
            'headers' => array(
                'Authorization' => 'Klaviyo-API-Key ' . $private_api_key,
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'revision' => '2024-02-15',
            ),
        );

        $response = wp_remote_post('https://a.klaviyo.com/api/events/', $request_args);

        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body);
            error_log(print_r($data, true));
        }
    }

} else {
    add_action( 'admin_notices', 'klaviyo_wishlist_bridge_notice' );
}

function klaviyo_wishlist_bridge_notice() {
    ?>

    <div class="notice notice-error is-dismissible">
        <p><?php _e( 'The Wishlist Klaviyo Bridge plugin requires the YITH WooCommerce Wishlist and Klaviyo for WooCommerce plugins to be installed and activated.', 'wishlist-klaviyo-bridge' ); ?></p>
    </div>

    <?php
}

?>