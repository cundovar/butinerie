<?php

namespace MEC_Woocommerce\Core\Helpers;

// Don't load directly
if (!defined('ABSPATH')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit;
}

use MEC\Settings\Settings;
/**
*  Products.
*
*  @author      Webnus <info@webnus.biz>
*  @package     Modern Events Calendar
*  @since       1.0.0
**/
class Products {

    /**
     * Global Variables
     *
     * @since     1.0.0
     * @access     private
     */
    public static $id = 0;
    public static $access_to_run = 0;
    public static $checkout = [];
    public static $mec_settings;
    public static $gateway_options;
    public static $do_action = true;
    public static $term_id;

    /**
    *  Instance of this class.
    *
    *  @since   1.0.0
    *  @access  public
    *  @var     MEC_Woocommerce
    */
    public static $_instance;


   /**
    *  Provides access to a single instance of a module using the Singleton pattern.
    *
    *  @since   1.0.0
    *  @return  object
    */
    public static function getInstance()
    {
        if(!static::$gateway_options) {
            $main			  = \MEC::getInstance('app.libraries.main');
            $gateways_options = $main->get_gateways_options();
            static::$gateway_options = isset($gateways_options[1995]) ? $gateways_options[1995] : '';
            static::$mec_settings = $main->get_settings();
            if (!isset(static::$mec_settings['datepicker_format'])) {
                static::$mec_settings['datepicker_format'] = 'yy-mm-dd&Y-m-d';
            }
            if (!defined('WP_POST_REVISIONS')) {
                define('WP_POST_REVISIONS', false);
            }
        }

        if (self::$_instance === null) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }


    /**
     * Get Product By name|ID
     *
     * @param string $product_title
     * @param boolean $isID
     * @return void
     */
    public function get_product($product_title, $isID = false)
    {
        global $wpdb;
        if ($isID) {
            $post = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE ID = %s AND post_type='product' AND post_status = %s", $product_title, 'MEC_Tickets'));
        } else {
            $post = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type='product' AND post_status = %s", $product_title, 'MEC_Tickets'));
        }
        if ($post) {
            return $post;
        }

        return null;
    }

    /**
     * Create Product Variations
     *
     * @param array $product_ids
     * @param array $variation_data
     * @return void
     */
    public static function create_product_variation($product_ids, $variation_data)
    {
        foreach ($product_ids as $pid) {
            $_regular_price = get_post_meta($pid, '_regular_price', true);
            $_price         = get_post_meta($pid, '_price', true);
            update_post_meta($pid, '_regular_price', ($_regular_price + ($variation_data['MEC_WOO_V_price'] * $variation_data['MEC_WOO_V_count'])));
            update_post_meta($pid, '_price', ($_price + ($variation_data['MEC_WOO_V_price'] * $variation_data['MEC_WOO_V_count'])));
            add_post_meta($pid, 'MEC_Variation_Data', json_encode($variation_data , JSON_UNESCAPED_UNICODE ));
        }
    }

    /**
     * Get Event Date Label
     *
     * @param string $date
     * @param integer $event_id
     * @return void
     */
    public function get_date_label($date, $event_id)
    {
        $date_format = get_option('date_format');
        $time_format = get_option('time_format');
        $event_date = isset($date) ? explode(':', $date) : array();
        $event_start_time = $event_end_time = $new_event_start_time = $new_event_end_time = '';
        if (is_numeric($event_date[0]) and is_numeric($event_date[1])) {
            $start_datetime = date_i18n('Y/m/d' . ' ' . $time_format, $event_date[0]);
            $end_datetime = date_i18n('Y/m/d' . ' ' . $time_format, $event_date[1]);
        } else {
            $start_datetime = $event_date[0];
            $end_datetime = $event_date[1];
        }
        if (isset($start_datetime) and !empty($start_datetime)) {
            $event_start_time = date_i18n($time_format,strtotime($start_datetime));
        }
        if (isset($end_datetime) and !empty($end_datetime)) {
            $event_end_time = date_i18n($time_format,strtotime($end_datetime));
        }

        if (isset($start_datetime) and !empty($start_datetime)) {
            $event_start_date = date_i18n($date_format,strtotime($start_datetime));
        }
        if (isset($end_datetime) and !empty($end_datetime)) {
            $event_end_date = date_i18n($date_format,strtotime($end_datetime));
        }


        $event = get_post($event_id);
        $render = \MEC::getInstance('app.libraries.render');
        $event->data = $render->data($event_id);
        $allday = isset($event->data->meta['mec_allday']) ? $event->data->meta['mec_allday'] : 0;
        if ($allday == '0' and isset($event->data->time) and trim($event->data->time['start'])) :
            $new_event_date = ($event_end_date == $event_start_date) ? $event_start_date . ' ' . $event_start_time . ' - ' . $event_end_time : $event_start_date . ' ' . $event_start_time . ' - ' . $event_end_date . ' ' . $event_end_time;
        else :
            $new_event_date = ($event_end_date == $event_start_date) ? $event_start_date : $event_start_date . ' - ' . $event_end_date;
        endif;

        return $new_event_date;
    }

    /**
     * Set Product Attributes
     *
     * @param int $post_id
     * @param array $attributes
     * @return void
     */
    public function set_product_attributes($post_id, $attributes)
    {
        $i = 0;
        // Loop through the attributes array k
        foreach ($attributes as $name => $value) {
            $product_attributes[$i] = array(
                'name'         => htmlspecialchars(stripslashes($name)), // set attribute name
                'value'        => $value, // set attribute value
                'position'     => 1,
                'is_visible'   => 1,
                'is_variation' => 1,
                'is_taxonomy'  => 0,
            );

            $i++;
        }
        update_post_meta($post_id, '_product_attributes', $product_attributes);
    }

    /**
     * Create Woocommerce Product
     *
     * @param array $ticket
     * @param string $transaction_id
     * @param array $event_data
     * @return void
     */
    public function create_product($ticket, $transaction_id, $event_data)
    {
        $post = array(
            'post_content' => '',
            'post_status'  => 'MEC_Tickets',
            'post_title'   => 'Ticket (' . $ticket['name'] . ') - ' . $transaction_id,
            'post_parent'  => '',
            'post_type'    => 'product',
        );
        $transaction = get_option( $transaction_id );
        // Create post
        $post_id = wp_insert_post($post);
        update_post_meta($post_id, 'transaction_id', $transaction_id);

        $first_for_all = isset($transaction['first_for_all']) && 1 == $transaction['first_for_all'] ? 'yes' : 'no';
        update_post_meta($post_id, 'first_for_all', $first_for_all);

        if('yes' === $first_for_all){
            $ticket_used = 0;
            foreach($transaction['tickets'] as $t_ticket){
                if(isset($t_ticket['id']) && $t_ticket['id'] == $ticket['id']){
                    $ticket_used++;
                }
            }
        }else{

            $ticket_used = isset($ticket['count']) ? $ticket['count'] : 0;
        }

        $ticket_used = $ticket_used ? $ticket_used : 1;
        update_post_meta($post_id, 'ticket_used_count', $ticket_used);

        if (has_post_thumbnail($event_data['event_id'])) {
            $image                = wp_get_attachment_image_src(get_post_thumbnail_id($event_data['event_id']), 'full');
            $event_featured_image = str_replace(get_site_url(), $_SERVER['DOCUMENT_ROOT'], $image[0]);

            if ($event_featured_image) {
                set_post_thumbnail($post_id, attachment_url_to_postid($image[0]));
            }
        }
        if (!$ticket['price']) {
            $ticket['price'] = 0;
        }

        $ticket['product_id']= $post_id;

        $product_type = Settings::getInstance()->get_settings( 'ticket_product_type' );
        $is_virtual = ( 'virtual' === $product_type ) ? 'yes' : 'no';


        $ticket_sales_with_wooCommerce_product = false;

        if( !isset( $ticket['id'] ) ){

            $ticket['id'] = 0;
        }
        $event_tickets = (array)get_post_meta( $event_data['event_id'], 'mec_tickets',true);
        $event_ticket = isset( $event_tickets[$ticket['id']] ) && is_array( $event_tickets[$ticket['id']] ) ? $event_tickets[$ticket['id']] : [];
        $ticket_custom_categories = isset( $event_ticket['category_ids'] ) && !empty( $event_ticket['category_ids'] ) ? (array)$event_ticket['category_ids'] : [];
        if( false == $ticket_sales_with_wooCommerce_product && !empty( $ticket_custom_categories ) ){

            foreach($ticket_custom_categories as $k => $category_id){

                $ticket_custom_categories[$k] = intval($category_id);
            }

            wp_set_object_terms($post_id, $ticket_custom_categories, 'product_cat', true);

        }

        if (isset($ticket['m_product_type'])) {
            update_post_meta($post_id, 'm_product_type', $ticket['m_product_type']);
            update_post_meta($post_id, 'related_products', $ticket['related_products']);
        }
        wp_set_object_terms($post_id, 'MEC-Woo-Cat', 'product_cat', true);
        wp_set_object_terms($post_id, 'simple', 'product_type');
        update_post_meta($post_id, '_visibility', false);
        update_post_meta($post_id, '_stock_status', 'instock');
        update_post_meta($post_id, 'total_sales', '0');
        update_post_meta($post_id, '_downloadable', 'no');
        update_post_meta($post_id, '_virtual', $is_virtual);
        update_post_meta($post_id, '_regular_price', $ticket['price']);
        update_post_meta($post_id, '_sale_price', isset($ticket['sale_price']) ? $ticket['sale_price'] : $ticket['price']);
        update_post_meta($post_id, '_purchase_note', '');
        update_post_meta($post_id, '_featured', 'no');
        update_post_meta($post_id, '_weight', '');
        update_post_meta($post_id, '_length', '');
        update_post_meta($post_id, '_width', '');
        update_post_meta($post_id, '_height', '');
        update_post_meta($post_id, '_sku', '');
        update_post_meta($post_id, '_product_attributes', array());
        update_post_meta($post_id, '_sale_price_dates_from', '');
        update_post_meta($post_id, '_sale_price_dates_to', '');
        update_post_meta($post_id, '_price', isset($ticket['sale_price']) ? $ticket['sale_price'] : $ticket['price']);
        update_post_meta($post_id, 'mec_ticket', $ticket);
        update_post_meta($post_id, '_sold_individually', '');
        update_post_meta($post_id, '_manage_stock', 'no');
        update_post_meta($post_id, '_backorders', 'no');
        update_post_meta($post_id, '_stock', '');

        // event Data
        update_post_meta($post_id, 'event_id', $event_data['event_id']);
        update_post_meta($post_id, 'event_name', $event_data['event_name']);
        if (isset($event_data['date'])) {
            update_post_meta($post_id, 'mec_date', $event_data['date']);
        }
        if (isset($ticket['cantChangeQuantity'])) {
            update_post_meta($post_id, 'cantChangeQuantity', true);
        }
        update_post_meta($post_id, 'ticket_id', $ticket['id'] ?? '');
        update_post_meta($post_id, 'ticket_name', $ticket['name']);

        $terms = array('exclude-from-search', 'exclude-from-catalog');
        wp_set_post_terms($post_id, $terms, 'product_visibility', false);
        update_post_meta($post_id, '_product_image_gallery', '');

        return $post_id;
    }

    /**
     * Access Protected
     *
     * @param object $obj
     * @param object $prop
     * @param string $value
     * @return void
     */
    public function accessProtected($obj, $prop, $value = null) {
        $reflection = new \ReflectionClass($obj);
        $property = $reflection->getProperty($prop);
        $property->setAccessible(true);
        return $property->getValue($obj)[$value];
    }

} //Products Helper

Products::getInstance();