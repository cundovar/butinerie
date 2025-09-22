<?php

namespace MEC_Woocommerce\Core\Integrations;
use \MEC_Woocommerce\Core\Helpers\Products as Helper;
use MEC\Books\EventBook;
// Don't load directly
if (!defined('ABSPATH')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit;
}

/**
*  AddToCart.
*
*  @author      Webnus <info@webnus.biz>
*  @package     Modern Events Calendar
*  @since       1.0.0
**/
class AddToCart extends Helper
{

    /**
    *  Instance of this class.
    *
    *  @since   1.0.0
    *  @access  public
    *  @var     MEC_Woocommerce
    */
    public static $instance;

   /**
    *  The directory of this file
    *
    *  @access  public
    *  @var     string
    */
    public static $dir;

   /**
    *  Provides access to a single instance of a module using the Singleton pattern.
    *
    *  @since   1.0.0
    *  @return  object
    */
    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }


    public function __construct()
    {
        if (self::$instance === null) {
            self::$instance = $this;
        }

        $this->setHooks();
    }

   /**
    *  Hooks
    *
    *  @since     1.0.0
    */
    public function setHooks()
    {
        add_action('wp_loaded', [$this, 'add_multiple_products_to_cart'], 15, 1);
        add_action('wp_loaded', [$this, 'process_add_to_cart']);
        add_action('wp_enqueue_scripts', [$this, 'render_the_script'], 10000);

        add_filter( 'woocommerce_update_cart_validation', [$this,'mec_wc_qty_update_cart_validation'], 1, 4 );
        add_filter( 'woocommerce_is_sold_individually', [ __CLASS__, 'filter_sold_individually_for_tickets' ], 10, 2 );
		add_filter( 'woocommerce_cart_item_quantity', [ __CLASS__, 'remove_edit_ticket_quantity' ], 10, 3 );
    }

    /**
     * WooCommerce Maybe Add Multiple Products To Cart
     *
     * @param boolean $url
     * @return void
     */
    public function add_multiple_products_to_cart($url = false)
    {
        $get_term = get_term_by('slug', 'mec-woo-cat', 'product_cat');
        static::$term_id = (isset($get_term) and !empty($get_term)) ? $get_term->term_id : '';
        if (!class_exists('WC_Form_Handler') || empty($_REQUEST['add-to-cart'])) {
            return;
        }

        $product_ids = explode(',', $_REQUEST['add-to-cart']);
        foreach ($product_ids as $pid) {
            if ($product = wc_get_product($pid)) {
                if (strtolower($product->get_status()) != 'mec_tickets') {
                    return;
                }
            }
        }

        remove_action('wp_loaded', array('WC_Form_Handler', 'add_to_cart_action'), 20);
        $count  = count($product_ids);
        $cart_keys = array();
        $added = $not_added = 0;
        foreach ($product_ids as $id_and_quantity) {
            $id_and_quantity         = explode(':', $id_and_quantity);
            $product_id              = $id_and_quantity[0];
            $_REQUEST['quantity']    = !empty($id_and_quantity[1]) ? absint($id_and_quantity[1]) : 1;
            $_REQUEST['add-to-cart'] = $product_id;

            if( !$product_id ){
                continue;
            }

            $values = [
                'product_id' => $product_id,
                'quantity' => $_REQUEST['quantity']
            ];
            $can_add = $this->mec_wc_qty_update_cart_validation(true,null,$values,$_REQUEST['quantity']);
            if($can_add){

                $added_to_cart = @\WC()->cart->add_to_cart($product_id, $_REQUEST['quantity']);
                $product_id        = apply_filters('woocommerce_add_to_cart_product_id', absint($product_id));
                $adding_to_cart    = wc_get_product($product_id);
                $added++;
                $cart_keys[] = $added_to_cart;
            }else{
                $not_added++;
            }
        }

        if( $not_added ){

            foreach( $cart_keys as $cart_key ){

                @\WC()->cart->remove_cart_item( $cart_key );
            }
        }

        $redirect_to = isset( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : '';
        $redirect_to_type = isset( $_REQUEST['redirect_to_type'] ) ? $_REQUEST['redirect_to_type'] : '';
        switch( $redirect_to_type ){
            case 'cart':
            case 'checkout':

                $r = array(
                    'redirect_to' => $redirect_to
                );

                wp_send_json( $r );

                break;
            case 'optional_cart':
            case 'optional_checkout':

                if( 'optional_cart' === $redirect_to_type ){

                    $link_text = __('Cart Page', 'mec-woocommerce');
                }elseif( 'optional_checkout' === $redirect_to_type ){

                    $link_text = __('Checkout Page', 'mec-woocommerce');
                }

                if ( $added && $count > 1) {

                    wc_add_notice(__('The Tickets are added to your cart.', 'mec-woocommerce') . ' <a href="' . $redirect_to . '" target="_blank">' . $link_text . '</a>', apply_filters('woocommerce_add_to_cart_notice_type', 'success'));
                } elseif($added) {

                    wc_add_notice(__('The Ticket is added to your cart.', 'mec-woocommerce') . ' <a href="' . $redirect_to . '" target="_blank">' . $link_text . '</a>', apply_filters('woocommerce_add_to_cart_notice_type', 'success'));
                }

                break;
        }

        die();
    }

    /**
     * Render Add To Cart Button
     *
     * @param string $transaction_id
     * @param string $redirect_to
     * @param string  $redirect_to_type
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function render_add_to_cart_button( $transaction_id, $redirect_to, $redirect_to_type ){

        $redirect = 'no';
        $nonce = wp_create_nonce('mec-woocommerce-process-add-to-cart');
        $add_to_cart_url = get_site_url() . '?transaction-id=' . $transaction_id . '&action=mec-woocommerce-process-add-to-cart&nonce=' . $nonce;

        $add_to_cart_url = esc_url_raw(
            add_query_arg(
                array(
                    'redirect_to' => urlencode($redirect_to),
                    'redirect_to_type' => $redirect_to_type,
                ),
                $add_to_cart_url
            )
        );

        switch( $redirect_to_type ){
            case 'cart':
            case 'checkout':

                $redirect = 'yes';

                break;
            case 'optional_cart':
            case 'optional_checkout':

                $redirect_to = '#';

                break;
        }
        $RedirectURL = apply_filters( 'mec_woocommerce_after_add_to_cart_url', $redirect_to );

        echo '<a href="' . esc_attr($add_to_cart_url) . '" id="mec_woo_add_to_cart_btn_r" data-cart-url="' . esc_attr($RedirectURL) . '" class="button mec-add-to-cart-btn-r" aria-label="Please Wait" rel="nofollow">' . esc_html__('Add to cart', 'mec-woocommerce') . '</a>';
    }

    /**
     * Render Inline Script
     *
     * @since     1.0.0
     */
    public function render_the_script(){

        $script = <<<Script
            // MEC Woocommerce Add to Cart BTN
            jQuery(document).on('ready', function() {
                jQuery(document).on('DOMNodeInserted', function (e) {
                    if (jQuery(e.target).find('#mec_woo_add_to_cart_btn_r').length) {
                        jQuery(e.target).find('#mec_woo_add_to_cart_btn_r:not(.loading)').on('click', function () {
                            return false;
                        });
                        jQuery(e.target).find('#mec_woo_add_to_cart_btn_r:not(.loading)').on('click', function () {
                            var href = jQuery(this).attr('href');
                            var cart_url = jQuery(this).data('cart-url');
                            var _this = jQuery(this);
                            _this.addClass('loading');
                            jQuery.ajax({
                                type: "get",
                                url: href,
                                success: function (response) {
                                    if(typeof response.message != 'undefined') {
                                        jQuery('.mec-add-to-cart-message').remove();
                                        jQuery('.mec-book-form-gateways').before('<div class="mec-add-to-cart-message mec-util-hidden mec-error" style="display: block;">'+ response.message +'</div>');
                                        _this.removeClass('loading');
                                        return;
                                    }
                                    var SUrl = response.url;
                                    jQuery.ajax({
                                        type: "get",
                                        url: SUrl,
                                        success: function (response) {
                                            jQuery(this).removeClass('loading');
                                            setTimeout(function() {
                                                window.location.href = cart_url === '#' ? window.location.href : cart_url;
                                            }, 500);
                                        }
                                    });
                                }
                            });
                            return false;
                        });
                    }
                })
            });
        Script;

        wp_add_inline_script('jquery', $script);
    }

    /**
     * Process Add to Cart
     *
     * @since     1.0.0
     */
    public function process_add_to_cart()
    {
        if (isset($_REQUEST['action']) && $_REQUEST['action'] != 'mec-woocommerce-process-add-to-cart') {
            return false;
        } else if (!isset($_REQUEST['action'])) {
            return false;
        }
        if (!wp_verify_nonce($_REQUEST['nonce'], 'mec-woocommerce-process-add-to-cart')) {
            return false;
        }

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $transaction_id 	= isset($_REQUEST['transaction-id']) ? $_REQUEST['transaction-id'] : '';
            if (!$transaction_id) {
                return false;
            }
        } else {
            return false;
        }
        $transaction    	= get_option($transaction_id, false);
        $all_dates          = $this->get_all_dates( $transaction_id );
        $tickets        	= get_post_meta($transaction['event_id'], 'mec_tickets', true);
        $product_ids    	= [];
        $factory            = \MEC::getInstance('app.libraries.factory');
        $main            	= \MEC::getInstance('app.libraries.main');
        $book            	= \MEC::getInstance('app.libraries.book');
        $render            	= \MEC::getInstance('app.libraries.render');
        $settings 			= static::$mec_settings;
        $gateways_options = $main->get_gateways_options();
        $gateway_options = $gateways_options[1995];
        $event = $render->data($transaction['event_id']);
        $tickets = isset($event->tickets) ? $event->tickets : array();
        $cart_tickets = isset($event->tickets) ? $event->tickets : array();
        $dates = $render->dates($transaction['event_id'], $event);
        $dates = explode(':', $transaction['date']);


        $occurrence_time = $dates[0];
        $availability = $book->get_tickets_availability($transaction['event_id'], $occurrence_time);
        foreach ($transaction['tickets'] as $ticketKey => $ticket) {
            if ( $ticketKey !== 'attachments')
            {
                $ticket_id = $ticket['id'];
                $si = isset($availability[$ticket_id]) ? $availability[$ticket_id] : false;
                $str_replace = isset($tickets[$ticket['id']]['name']) ? '<strong>'.$tickets[$ticket['id']]['name'].'</strong>' : '';
                $ticket_message_sold_out =  sprintf(__('The %s ticket is sold out. You can try another ticket or another date.', 'mec'), $str_replace);
                if(!$si >= $availability['total']) {
                    $main->response(array('success'=>0, 'message'=>sprintf($main->m('booking_restriction_message3',  $ticket_message_sold_out), $limit), 'code'=>'LIMIT_REACHED'));
                    return;
                }
            }
        }

        foreach ($transaction['tickets'] as $ticketKey => $ticket) {
            if ( $ticketKey !== 'attachments')
            {
                $ticket_id = $ticket['id'];
                $transaction['ticket_limit'] = $tickets[$ticket['id']]['limit'];
            }
        }
        update_option( $transaction_id, $transaction );
        $transaction    	= get_option($transaction_id, false);

        list($limit, $unlimited) = $book->get_user_booking_limit($transaction['event_id']);
        $used_times = 0;
        foreach (wc()->cart->get_cart() as $key => $item) {
            $product_id     = $item['product_id'];
            $event_id       = get_post_meta($product_id, 'event_id', true);
            if($event_id == $transaction['event_id'] ){
                $used_times++;
            }
        }
        if($used_times >= $limit) {
            //$main->response(array('success'=>0, 'message'=>sprintf($main->m('booking_restriction_message3', __("Maximum allowed number of tickets that you can book is %s.", 'mec')), $limit), 'code'=>'LIMIT_REACHED'));
            //return;
        }

        if (isset($gateway_options['use_mec_taxes']) && $gateway_options['use_mec_taxes']) {
            if (get_post_meta($transaction['event_id'], 'mec_fees_global_inheritance', true)) {
                $fees = isset($settings['fees']) && isset($settings['taxes_fees_status']) && $settings['taxes_fees_status'] ? $settings['fees'] : array();
            } else {
                $fees = get_post_meta($transaction['event_id'], 'mec_fees', true);
            }
        } else {
            $fees = [];
        }

        $ticket_variations  = $factory->main->ticket_variations($transaction['event_id']);
        $event_data         = [
            'event_id'   => $transaction['event_id'],
            'event_name' => get_the_title($transaction['event_id']),
        ];
        if (isset($transaction['coupon']) && $transaction['coupon']) {
            $term = get_term_by('name', $transaction['coupon'], 'mec_coupon');
            $coupon_id = isset($term->term_id) ? $term->term_id : 0;
            // Coupon is not exists
            if ($coupon_id) {
                $discount_type = get_term_meta($coupon_id, 'discount_type', true);
                $discount = get_term_meta($coupon_id, 'discount', true);
            }
        }

        $last_ticket_name   = '';
        $last_product_id    = '';
        $count              = 0;
        $tickets_count      = 0;
        $current_ticket_id  = 0;
        $variation_added    = [];
        $product_ids_object = [];
        $ticket_ids = [];
        foreach ($transaction['tickets'] as $ticketKey => $_ticket) {
            if ( $ticketKey !== 'attachments')
            {
                if (isset($_ticket['id'])) {
                    $tickets_count++;
                }
            }

        }
        $new_tickets = $details = [];
        foreach ($transaction['price_details']['details'] as $detail) {
            if($detail['type'] == 'tickets') {
                $details[] = $detail;
            }
        }

        $last_product_ids = array();
        foreach ($transaction['tickets'] as $ticketKey => $_ticket) {

            if ( $ticketKey === 'attachments'){

                continue;
            }

            $current_ticket = isset($details[$current_ticket_id]) ? $details[$current_ticket_id] : '';
            $current_ticket_id++;
            if (!isset($_ticket['id'])) {

                continue;
            }

            $t = $tickets[$_ticket['id']];
            if (isset($t['dates']) && !empty($t['dates'])) {
                $today = strtotime(date('Y-m-d', time()));
                foreach ($t['dates'] as $date) {
                    if ($today >= strtotime($date['start']) && $today <= strtotime($date['end'])) {
                        $t['price'] = $date['price'];
                    }
                }
            }

            $booking_options = get_post_meta($transaction['event_id'], 'mec_booking', true);
            if(!is_array($booking_options)) $booking_options = array();

            if(is_user_logged_in()){
                // User
                $user = wp_get_current_user();

                $roles = (array) $user->roles;

                $loggedin_discount = (isset($booking_options['loggedin_discount']) ? $booking_options['loggedin_discount'] : 0);
                $role_discount = $loggedin_discount;

                foreach($roles as $key => $role){

                    // If role discount is higher than the preset role OR a previous roles discount, set it to the new higher discount
                    if(
                        isset($booking_options['roles_discount_'.$role])
                        && is_numeric($booking_options['roles_discount_'.$role])
                        && $booking_options['roles_discount_'.$role] > $role_discount
                        ){

                        $role_discount = $booking_options['roles_discount_'.$role];
                    }
                }

                if(trim($role_discount) and is_numeric($role_discount)){

                    if($type === 'price_label' and !is_numeric($t['price']))
                    {
                        $numeric = preg_replace("/[^0-9.]/", '', $t['price']);
                        if(is_numeric($numeric)) $t['price'] = $main->render_price(($numeric - (($numeric * $role_discount) / 100)));
                    } else {
                        $t['price'] = $t['price'] - (($t['price'] * $role_discount) / 100);
                    }
                }
            }

            $_ticket['_name'] = $_ticket['name'];

            if ($t) {
                $_ticket['name']  = $t['name'];
                $_ticket['price'] = $t['price'];
            }

            if (!$_ticket['price']) {
                $_ticket['price'] = 0;
            }

            foreach( $all_dates as $_ticket_date ){

                $event_data['date'] = $_ticket_date;
                $_ticket['date'] = $_ticket_date;
                // $title = 'Modern Event Calendar Ticket (' . $_ticket['name'] . ') - ' . $transaction_id . '.' . time();
                if (isset($transaction['first_for_all']) && $transaction['first_for_all'] == '1') {
                    $last_product_id = isset( $last_product_ids[$_ticket['id']][$_ticket_date] ) && $last_product_ids[$_ticket['id']][$_ticket_date] ? $last_product_ids[$_ticket['id']][$_ticket_date] : false;
                    if ( !$last_product_id ) {
                        $product_id       = $last_product_id = $this->create_product($_ticket, $transaction_id, $event_data);
                        $last_product_ids[$_ticket['id']][$_ticket_date] = $last_product_id;
                        $_ticket['product_id'] = $product_id;
                        array_push($_ticket,$_ticket['product_id']);
                        $new_tickets[] = $_ticket;
                        $ticket_ids[] = $product_id;
                        $last_ticket_name = $_ticket['id'];
                    }

                    if ($last_ticket_name == $_ticket['id']) {
                        $count++;
                        if (!isset($product_ids_object[$last_product_id])) {
                            $product_ids_object[$last_product_id] = 0;
                        }
                        $product_ids_object[$last_product_id]++;

                        update_post_meta($product_id, '_mec_ticket_id', $_ticket['id']);
                        update_post_meta($product_id, '_mec_ticket_limit', $transaction['ticket_limit']);
                    } else {
                        $product_id       = $last_product_id = $this->create_product($_ticket, $transaction_id, $event_data);
                        $_ticket['product_id'] = $product_id;
                        array_push($_ticket,$_ticket['product_id']);
                        $new_tickets[] = $_ticket;
                        $ticket_ids[] = $product_id;
                        $last_ticket_name = $_ticket['id'];

                        if (!isset($product_ids_object[$last_product_id])) {
                            $product_ids_object[$last_product_id] = 0;
                        }
                        $product_ids_object[$last_product_id]++;

                        update_post_meta($product_id, '_mec_ticket_id', $_ticket['id']);
                        update_post_meta($product_id, '_mec_ticket_limit', $transaction['ticket_limit']);
                    }

                    foreach ($_ticket['variations'] as $id => $v) {
                        if (!$v) {
                            continue;
                        }
                        $a = $ticket_variations[$id];

                        if (!isset($variation_added[$product_id][$a['title']][$a['price']])) {
                            $variation_added[$product_id][$a['title']][$a['price']] = 1;
                            $variation_data = [
                                'MEC_WOO_V_max'   => @$a['max'],
                                'MEC_WOO_V_title' => $a['title'],
                                'MEC_WOO_V_price' => isset($a['sale_price']) ? $a['sale_price'] : $a['price'],
                                'MEC_WOO_V_count' => $v,
                            ];
                            static::create_product_variation([$product_id], $variation_data);
                        }
                        $count            = 1;
                        $last_ticket_name = $_ticket['id'];
                        $last_product_id  = $product_id;
                        update_post_meta($product_id, '_mec_ticket_id', $_ticket['id']);
                        update_post_meta($product_id, '_mec_ticket_limit', $transaction['ticket_limit']);
                    }
                } else {
                    $product_id    = $this->create_product($_ticket, $transaction_id, $event_data);
                    $_ticket['product_id'] = $product_id;
                    $_ticket['date'] = $_ticket_date;
                    array_push($_ticket,$_ticket['product_id']);
                    $new_tickets[] = $_ticket;

                    $ticket_ids[] = $product_id;
                    $product_ids[] = $product_id;
                    if (isset($_ticket['variations'])) {
                        foreach ($_ticket['variations'] as $id => $v) {
                            if (!$v) {
                                continue;
                            }
                            $a              = $ticket_variations[$id];

                            $variation_data = [
                                'MEC_WOO_V_max'   => @$a['max'],
                                'MEC_WOO_V_title' => $a['title'],
                                'MEC_WOO_V_price' => isset($a['sale_price']) ? $a['sale_price'] : $a['price'],
                                'MEC_WOO_V_count' => $v,
                            ];
                            static::create_product_variation([$product_id], $variation_data);
                        }
                    }
                    update_post_meta($product_id, '_mec_ticket_id', $_ticket['id']);
                    update_post_meta($product_id, '_mec_ticket_limit', $transaction['ticket_limit']);
                }
            }
        }

        foreach($new_tickets as $nk => $new_ticket){

            $product_id = $new_ticket['product_id'];
            $new_ticket['count'] = isset($product_ids_object[$product_id]) ? $product_ids_object[$product_id] : $new_ticket['count'];
            $new_tickets[$nk] = $new_ticket;
        }

        $transaction['tickets'] = $new_tickets;

        update_option( $transaction_id, $transaction);

        if (isset($transaction['coupon']) && $transaction['coupon']) {
            // Coupon is not exists
            if ($coupon_id) {
                $totalPrices = 0;
                foreach ($ticket_ids as $pid) {
                    $ecd_product	=	new \WC_Product($pid);
                    if ($ecd_product->exists()) {
                        $totalPrices += $ecd_product->price;
                    }
                }

                foreach ($ticket_ids as $pid) {
                    $ecd_product	=	new \WC_Product($pid);
                    if ($ecd_product->exists()) {
                        if ($discount_type == 'percent') {
                            $discount_amount = ($ecd_product->price * $discount) / 100;
                        } else if (isset($transaction['first_for_all']) && $transaction['first_for_all'] == '1') {
                            $percent = ($ecd_product->price * 100) / $totalPrices;
                            $discount_amount = ($discount * $percent) / 100;
                            $discount_amount = $discount_amount / $product_ids_object[$pid];
                        } else {
                            $percent = ($ecd_product->price * 100) / $totalPrices;
                            $discount_amount = ($discount * $percent) / 100;
                        }

                        $final_price = $ecd_product->price - $discount_amount;
                        $product_id = $ecd_product->get_id();
                        update_post_meta($product_id, '_sale_price', $final_price);
                        update_post_meta($product_id, '_price', $final_price);
                    }
                }
            }
        }

        $products_prices = array();

        if ($fees) {
            foreach ($ticket_ids as  $pid) {
                $ecd_product	=	new \WC_Product($pid);
                if ($ecd_product->exists()) {
                    $final_price = $ecd_product->get_price();

                    foreach ($fees as $fee) {
                        if ($fee['amount']) {
                            switch ($fee['type']) {
                                case 'percent':
                                    $final_price = $final_price + (($final_price * $fee['amount']) / 100);
                                    break;
                            }
                        }
                    }

                    foreach ($fees as $fee) {
                        if ($fee['amount']) {
                            switch ($fee['type']) {
                                case 'amount':
                                    $final_price = $final_price + $fee['amount'];
                                    break;
                            }
                        }
                    }

                    $product_id = $ecd_product->get_id();
                    $quantity = get_post_meta( $product_id, 'ticket_used_count', true );
                    $products_prices[ $product_id ] = $final_price * $quantity;

                    update_post_meta($product_id, '_sale_price', $final_price);
                    update_post_meta($product_id, '_price', $final_price);
                }
            }
        }

        $related_products = $product_ids;
        foreach ($fees as $fee) {
            if ($fee['amount']) {
                $product_id = 0;
                $amount = $fee['amount'];

                switch ($fee['type']) {
                    case 'amount_per_booking':
                        $product_id = $this->create_product(
                            [
                                "name" =>  $fee['title'],
                                "count" =>  "1",
                                "variations" => [],
                                "price" => $fee['amount'],
                                'cantChangeQuantity' => true,
                                'm_product_type' => 'amount_per_booking',
                                'related_products' => $related_products,
                            ],
                            $transaction_id,
                            $event_data
                        );


                        if ($product_ids_object) {
                            $product_ids_object[$product_id] = 1;
                        } else {
                            $product_ids[] = $product_id;
                        }

                        break;
                    case 'amount_per_date':
                        $amount = $fee['amount'] * count($all_dates);
                        $product_id = $this->create_product(
                            [
                                "name" =>  $fee['title'],
                                "count" =>  "1",
                                "variations" => [],
                                "price" => $amount,
                                'cantChangeQuantity' => true,
                                'm_product_type' => 'amount_per_date',
                                'related_products' => $related_products,
                            ],
                            $transaction_id,
                            $event_data
                        );

                        if ($product_ids_object) {
                            $product_ids_object[$product_id] = 1;
                        } else {
                            $product_ids[] = $product_id;
                        }
                        break;
                }

                if( $product_id ){

                    // $products_prices[ $product_id ] = $amount;
                }
            }
        }

        if (!$product_ids && $product_ids_object) {
            foreach ($product_ids_object as $pid => $count) {
                if ($count > 1) {
                    $product_ids[] = $pid . ':' . $count;
                } else if ($count) {
                    $product_ids[] = $pid;
                }
            }
        }


        do_action('mec-woocommerce-product-created', $product_ids, $transaction_id);




        foreach ($product_ids as $pr_key => $pr_id) {
            update_post_meta($pr_id, '_mec_event_id', $transaction['event_id']);
        }
        $countt = 0;
        foreach ($product_ids as $pr_key_1 => $pr_id_1) {

            $ex = explode(':',$pr_id_1);
            $p_id = isset($ex[0]) ? $ex[0] : 0;
            $p_quantity = isset($ex[1]) ? $ex[1] : 1;
            if(!$p_id){

                continue;
            }
            $ticket_id_in_cart = get_post_meta($p_id, '_mec_ticket_id', true);
            $ticket_limit_in_cart = get_post_meta($p_id, '_mec_ticket_limit', true);
            $event_id_in_cart = get_post_meta($p_id, '_mec_event_id', true);

            if ( $countt > 0  && (isset($ticket_limit_in_cart) && !empty($ticket_limit_in_cart) && $countt >= $ticket_limit_in_cart )){
                $main->response(array('success'=>0, 'message'=>sprintf($main->m('booking_restriction_message3', __("Maximum allowed number of tickets that you can book is %s.", 'mec-woocommerce')), $ticket_limit_in_cart), 'code'=>'LIMIT_REACHED'));
                return;
            }

        }

        $redirect_to = isset( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : '';
        $redirect_to_type = isset( $_REQUEST['redirect_to_type'] ) ? $_REQUEST['redirect_to_type'] : '';
        $product_ids     = implode(',', $product_ids);
        $add_to_cart_url = esc_url_raw(
            add_query_arg(
                array(
                    'add-to-cart' => $product_ids,
                    'redirect_to' => urlencode($redirect_to),
                    'redirect_to_type' => $redirect_to_type,
                ),
                wc_get_cart_url()
            )
        );
        ob_start();
        ob_get_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'url' => $add_to_cart_url
        ]);
        die();
    }

    public function get_variation_products(){

        return array(
            'amount_per_booking',
            'amount_per_date',
            'percent',
        );
    }

    public function mec_wc_qty_get_cart_qty( $product_id , $cart_item_key = '' ) {
        global $woocommerce;
        $running_qty = 0;

        foreach($woocommerce->cart->get_cart() as $other_cart_item_keys => $values ) {
            if ( $product_id == $values['product_id'] ) {

                if ( $cart_item_key == $other_cart_item_keys ) {
                    continue;
                }

                $product_type = get_post_meta( $values['product_id'], 'm_product_type', true );
                $v_types = $this->get_variation_products();
                if(  in_array( $product_type, $v_types ) ){
                    continue;
                }

                $running_qty += (int) $values['quantity'];
            }
        }

        return $running_qty;
    }

    public function get_event_total_tickets( $product_id , $cart_item_key = '', $event_id = 0 ) {
        global $woocommerce;
        $running_qty = 0;

        foreach($woocommerce->cart->get_cart() as $other_cart_item_keys => $values ) {

            if ( $cart_item_key == $other_cart_item_keys ) {
                continue;
            }

            $product_type = get_post_meta( $values['product_id'], 'm_product_type', true );
            $v_types = $this->get_variation_products();
            if(  in_array( $product_type, $v_types ) ){
                continue;
            }

            $p_event_id = get_post_meta( $values['product_id'], 'event_id', true );
            if( $event_id && $p_event_id != $event_id ){
                continue;
            }

            $running_qty += (int) $values['quantity'];
        }

        return $running_qty;
    }

    public function count_same_event_in_cart($product_ids, $specific_id)
    {
        $count = 0;

        foreach ($product_ids as $key => $pr_id)
        {
            $event_id = get_post_meta($pr_id, '_mec_event_id', true);
            if ($event_id == $specific_id)
            {
                $count++;
            }
        }

        return $count;
    }

    public function get_all_dates( $transaction_id ){

        $transaction = get_option( $transaction_id );

        return !empty( $transaction['all_dates'] ) ? $transaction['all_dates'] : [$transaction['date']];
    }


    public function get_dates_count( $product_id ){

        $transaction_id = get_post_meta( $product_id,'transaction_id', true );
        $all_dates = $this->get_all_dates( $transaction_id );

        return count( $all_dates );
    }

    public function are_related_products_added( $p_id, $cart_ticket_ids ){

        $related_ids = get_post_meta( $p_id, 'related_products', true );

        foreach( $related_ids as $k => $related_product_id ){

            if( in_array( $related_product_id, $cart_ticket_ids ) ){

                unset( $related_ids[ $k ] );
            }
        }

        return empty( $related_products ) ? true : false;
    }

    public function mec_wc_qty_update_cart_validation( $passed, $cart_item_key, $values, $quantity ) {

        // Check if product update is MEC ticket
        $product_id = $values['product_id'];
        $product = wc_get_product( $product_id );
        $event_id = get_post_meta( $product_id, 'event_id',true);
        $pr_status = $product->get_status();
        if ($pr_status != 'mec_tickets' ){
            return $passed;
        }

        // $dates_count = $this->get_dates_count( $product_id );
        // $quantity = $quantity * $dates_count;

        $ticket_used = get_post_meta($product_id, 'ticket_used_count', true);
        if( $quantity && (int)$ticket_used !== (int)$quantity ){

            wc_add_notice( apply_filters( 'wc_qty_error_message',__('The number of tickets is not allowed','mec-woocommerce')),'error' );
            return false;
        }

        $settings = static::$mec_settings;
        if(!isset($settings['booking_limit']) or (isset($settings['booking_limit']) and !trim($settings['booking_limit']))) $all_booking_limit = '';
        else $all_booking_limit = trim($settings['booking_limit']);

        $product_ids = [];
        $cart = @\WC()->cart->get_cart();
        foreach( $cart as $cart_item ){
            // compatibility with WC +3
            if( version_compare( WC_VERSION, '3.0', '<' ) ){
                $product_status = $cart_item['data']->status();
                $product_id = $cart_item['data']->id();
                if ( $product_status == 'mec_tickets' ) {
                    $product_ids[] = $product_id;
                }
            } else {
                $product_status = $cart_item['data']->get_status();
                $product_id = $cart_item['data']->get_id();
                if ( $product_status == 'mec_tickets' ) {
                    $product_ids[] = $product_id;
                }

            }
        }

        // Check limit for All Event in cart
        $event_tickets_in_cart = $this->get_event_total_tickets( $values['product_id'], $cart_item_key, $event_id );
        $product_type = get_post_meta( $values['product_id'], 'm_product_type', true );
        $v_types = $this->get_variation_products();
        $is_variation = in_array( $product_type, $v_types );
        if( $is_variation ){

            $added = $this->are_related_products_added( $values['product_id'], $product_ids );
            if( !$added ){

                wc_add_notice( apply_filters( 'wc_qty_error_message', sprintf( __( 'Tickets not added', 'mec-woocommerce' ) ) ),'error' );

                return false;
            }else{
                return $passed;
            }
        }

        if ( isset($all_booking_limit) && !empty($all_booking_limit) && ($event_tickets_in_cart + $quantity) > $all_booking_limit ) {

            wc_add_notice( apply_filters( 'wc_qty_error_message', sprintf( __( 'You can add just %1$s ticket(s) to your cart', 'mec-woocommerce' ),$all_booking_limit), $all_booking_limit ),
            'error' );
            $passed = false;
        }

        // Check limit for each event updated
        $product_id = $values['product_id'];
        $event_id = get_post_meta($product_id, 'event_id', true);
        $event_title = get_the_title($event_id);
        $event_timestamp = get_post_meta($product_id, 'mec_date', true);
        $event_available_tickets = EventBook::getInstance()->get_tickets_availability( $event_id, $event_timestamp );
        $event_ticket_id = get_post_meta($product_id, 'ticket_id', true);
        $booking_options = get_post_meta($event_id, 'mec_booking', true);

        // Total user booking limited
        if(isset($booking_options['bookings_user_limit_unlimited']) and !trim($booking_options['bookings_user_limit_unlimited']))
        {
            $event_booking_limit = (isset($booking_options['bookings_user_limit']) and trim($booking_options['bookings_user_limit'])) ? trim($booking_options['bookings_user_limit']) : '';
        }
        $event_in_cart = $this->mec_wc_qty_get_cart_qty( $values['product_id'], $cart_item_key );
        $same_event_count = $this->count_same_event_in_cart($product_ids,$event_id);
        $check_count = 0;
        $check_ticket = 0;
        if ( $same_event_count > 1 )
        {
            foreach ($product_ids as $key => $pr_id_value) {
                $check_event_id = get_post_meta($pr_id_value, '_mec_event_id', true);
                $p_event_timestamp = get_post_meta($pr_id_value, 'mec_date', true);
                $check_ticket_id = get_post_meta($pr_id_value, 'ticket_id', true);

                if ( $check_event_id == $event_id )
                {
                    $product_count = $this->mec_wc_qty_get_cart_qty( $pr_id_value );
                    $check_count =  $product_count + $check_count;

                    $event_timestamp = get_post_meta($pr_id_value, 'mec_date', true);
                    if($p_event_timestamp === $event_timestamp && $event_ticket_id === $check_ticket_id){

                        $check_ticket += $product_count;
                    }
                }
            }
        }

        $event_in_cart = $event_in_cart + $check_count;

        //tickets check
        $total_event_available_ticket = $event_available_tickets['total'];
        $sum_event_ticket_available = $event_in_cart + $quantity;
        if( -1 != $total_event_available_ticket && $sum_event_ticket_available > $total_event_available_ticket ){

            wc_add_notice( apply_filters( 'wc_qty_error_message', sprintf( __( 'You can add a maximum of %1$s "%2$s\'s" to %3$s.', 'mec-woocommerce' ),
                        $total_event_available_ticket,
                        $event_title,
                        '<a href="' . esc_url( wc_get_cart_url() ) . '">' . __( 'your cart', 'mec-woocommerce' ) . '</a>'),
                    $total_event_available_ticket ),
            'error' );
        }

        $total_ticket_available_allowed = isset($event_available_tickets[$event_ticket_id]) ? $event_available_tickets[$event_ticket_id] : -1;
        $sum_ticket_available = $check_ticket + $quantity;
        if( -1 != $total_ticket_available_allowed && $sum_ticket_available > $total_ticket_available_allowed ){

            wc_add_notice( apply_filters( 'wc_qty_error_message', sprintf( __( 'You can add a maximum of %1$s "%2$s\'s" to %3$s.', 'mec-woocommerce' ),
                        $total_ticket_available_allowed,
                        $event_title,
                        '<a href="' . esc_url( wc_get_cart_url() ) . '">' . __( 'your cart', 'mec-woocommerce' ) . '</a>'),
                    $total_ticket_available_allowed ),
            'error' );
        }

        if ( isset( $event_booking_limit) && ( $event_in_cart + $quantity ) > $event_booking_limit ) {
            wc_add_notice( apply_filters( 'wc_qty_error_message', sprintf( __( 'You can add a maximum of %1$s %2$s\'s to %3$s.', 'mec-woocommerce' ),
                        $event_booking_limit,
                        get_the_title(get_post_meta($values['product_id'], 'event_id', true)),
                        '<a href="' . esc_url( wc_get_cart_url() ) . '">' . __( 'your cart', 'mec-woocommerce' ) . '</a>'),
                    $event_booking_limit ),
            'error' );
            $passed = false;
        }

        $mec_ticket_limit = get_post_meta($values['product_id'], '_mec_ticket_limit', true);
        if ( isset( $mec_ticket_limit) && $mec_ticket_limit && ( $event_in_cart + $quantity ) > $mec_ticket_limit ) {
            wc_add_notice( apply_filters( 'wc_qty_error_message', sprintf( __( 'You can add a maximum of %1$s %2$s\'s to %3$s.', 'mec-woocommerce' ),
                        $mec_ticket_limit,
                        get_the_title(get_post_meta($values['product_id'], 'event_id', true)),
                        '<a href="' . esc_url( wc_get_cart_url() ) . '">' . __( 'your cart', 'mec-woocommerce' ) . '</a>'),
                    $mec_ticket_limit ),
            'error' );
            $passed = false;
        }

        return $passed;
    }


    public static function filter_sold_individually_for_tickets( $return , $product ){

		if($return){

			return $return;
		}

		$is_ticket = 'mec_tickets' === $product->get_status();
		if(!$is_ticket){

			return $return;
		}

		$first_for_all = $product->get_meta('first_for_all');

		if( 'yes' !== $first_for_all ){

			return true;
		}

		return false;
	}

	public static function remove_edit_ticket_quantity( $product_quantity, $cart_item_key, $cart_item ){

		if( is_cart() ){

			$product = wc_get_product( $cart_item['product_id'] );
			if($product && 'mec_tickets' === $product->get_status()){

				$product_quantity = sprintf( '%2$s <input type="hidden" name="cart[%1$s][qty]" value="%2$s" />', $cart_item_key, $cart_item['quantity'] );
			}
		}

		return $product_quantity;
	}


} //AddToCart

AddToCart::instance();
