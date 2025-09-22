<?php

namespace MEC_Woocommerce\Core\Integrations;
use \MEC_Woocommerce\Core\Helpers\Products as Helper;
// Don't load directly
if (!defined('ABSPATH')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit;
}

/**
*  Payment.
*
*  @author      Webnus <info@webnus.biz>
*  @package     Modern Events Calendar
*  @since       1.0.0
**/
class Payment extends Helper
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
        add_action('woocommerce_checkout_order_processed', [$this, 'capture_payment'], 10, 1);
        add_action('woocommerce_after_checkout_validation', [$this,'checkout_validation'], 10, 1);
    }

    public function checkout_validation($data){

        $can_add = true;
        $cart_items = @WC()->cart->get_cart();
        foreach ($cart_items as $item_id => $order_item) {

            $product_id = $order_item['product_id'];
            $product = $this->get_product($product_id, true); # Get Product
            // Check The Product is Processed
            if ($product && !get_post_meta($product->ID, 'mec_payment_complete', true)) {
                $transaction_id = get_post_meta($product->ID, 'transaction_id', true);
                // Don't Process Shop Products
                if(!$transaction_id) {
                    continue;
                }

                // Don't Process Processed Transaction
                if (get_option($transaction_id . '_MEC_payment_complete', false)) {
                    continue;
                }

                //check items
                $event_id = get_post_meta($product_id, 'event_id', true);
                $event_timestamp = get_post_meta($product_id, 'mec_date', true);
                $event_ticket_id = get_post_meta($product_id, 'ticket_id', true);
                if( !isset($checked[$event_id][$event_timestamp][$event_ticket_id]) ){

                    $addToCart = new AddToCart();
                    $can_add = $can_add && $addToCart->mec_wc_qty_update_cart_validation(
                        true,
                        null,
                        ['product_id' => $product_id],
                        0
                    );

                    $checked[$event_id][$event_timestamp][$event_ticket_id] = true;
                }
            }
        }
    }

    /**
     * Capture WOO Payment
     *
     * @param integer $order_id
     * @return void
     */
    public function capture_payment($order_id)
    {
        // Don't Capture Processed Order
        if (get_post_meta($order_id, 'mw_capture_completed', true)) {
            return;
        }

        // Set Variables
        $order  = new \WC_Order($order_id);
        $discount = $order->get_total_discount(); # Order Discount
        $applied_coupons = $order->get_coupon_codes();
        $tax = $order->get_tax_totals(); # Order Tax
        $main = \MEC::getInstance('app.libraries.main'); # Instance of MEC Main Class
        $gateways_options = $main->get_gateways_options(); # Get Gateways Options
        $gateway_options = $gateways_options[1995]; # Get Add to Cart Payment Options

        // Process Order Items
        foreach ($order->get_items() as $item_id => $order_item) {

            $product = $this->get_product($order_item['product_id'], true); # Get Product
            // Check The Product is Processed
            if ($product && !get_post_meta($product->ID, 'mec_payment_complete', true)) {
                $transaction_id = get_post_meta($product->ID, 'transaction_id', true);
                // Don't Process Shop Products
                if(!$transaction_id) {
                    continue;
                }

                // Don't Process Processed Transaction
                if (get_option($transaction_id . '_MEC_payment_complete', false)) {
                    continue;
                }

                // If Tax Used in Order
                if ($tax) {
                    // Get Transaction from Options by Using transaction_id
                    $transaction = get_option($transaction_id);
                    // If The WooCommerce Tax was not Applied in Transaction and the "use woocommerce taxes" is Enable in Gateway Options
                    if (!isset($transaction['WCTax']) && isset($gateway_options['use_woo_taxes']) && $gateway_options['use_woo_taxes'] && !isset($gateway_options['remove_woo_taxes']) && !$gateway_options['remove_woo_taxes']) {
                        $gateway_options['use_mec_taxes'] = isset($gateway_options['use_mec_taxes']) ? $gateway_options['use_mec_taxes'] : false;
                        $transaction['WCTax'] = true;
                        $removed_taxes = 0;
                        if( !$gateway_options['use_mec_taxes'] ) {
                            // Remove Standard Fees from Transaction
                            foreach ($transaction['price_details']['details'] as $key => $dt) {
                                if ($dt['type'] == 'fee') {
                                    $removed_taxes += $transaction['price_details']['details'][$key]['amount'];
                                    unset($transaction['price_details']['details'][$key]);
                                }
                            }
                        }

                        // Process WooCommerce Taxes
                        $transaction['price_details']['total'] = $transaction['price_details']['total'] - $removed_taxes;
                        $booking_price = $transaction['price_details']['total'];
                        foreach ($order->get_tax_totals() as $key => $tax) {
                            $tax_value = \WC_Tax::get_rate_percent_value($tax->rate_id);
                            $amount = ($booking_price * $tax_value) / 100;
                            $transaction['price_details']['total'] += $amount;
                            $transaction['price_details']['details'][] = [
                                'amount' => $amount,
                                'description' => 'WooCommerce ' . $tax->label,
                                'type' => 'fee'
                            ];
                        }

                        // Update Transaction Prices
                        $transaction['total'] = $transaction['price'] = $transaction['price_details']['total'];
                        update_option($transaction_id, $transaction);
                    }
                } else {
                    if (!isset($gateway_options['use_mec_taxes'])) {
                        $transaction = get_option($transaction_id);
                        $removed_taxes = 0;
                        // Remove Standard Fees from Transaction
                        foreach ($transaction['price_details']['details'] as $key => $dt) {
                            if ($dt['type'] == 'fee' && isset($dt['fee_type']) && $dt['fee_type'] != 'amount_per_booking' ) {
                                $removed_taxes += $transaction['price_details']['details'][$key]['amount'];
                                unset($transaction['price_details']['details'][$key]);
                            }
                        }
                        // Update Transaction Prices
                        $transaction['price_details']['total'] = $transaction['price_details']['total'] - $removed_taxes;
                        $transaction['total'] = $transaction['price'] = $transaction['price_details']['total'];
                        update_option($transaction_id, $transaction);
                    }
                }

                $gateway = \MEC_Woocommerce\Core\Gateway\Init::instance();
                if (!get_post_meta($product->ID, 'mec_payment_complete', true) && !get_post_meta($product->ID, 'transaction_created', true)) {
                    $book_id = $gateway->do_transaction($transaction_id);
                    update_post_meta($product->ID, 'transaction_created', '1');
                }

                $transaction = get_option($transaction_id);
                if ($discount) {

                    // Update Transaction Prices
                    $transaction['price_details']['total'] = $transaction['price_details']['total'] - $discount;
                    $transaction['total'] = $transaction['price'] = $transaction['price_details']['total'];

                    $transaction['price_details']['details'][] = [
                        'amount' => 0 - $discount,
                        'description' => 'WooCommerce Coupon (' . implode(', ', $applied_coupons) . ')',
                        'type' => 'coupon'
                    ];

                    update_option($transaction_id, $transaction);
                }

                $user_id = 0;
                if($book_id){

                    $user_id = get_post_field('post_author', $book_id);
                }
                $attendees = isset($transaction['tickets']) ? $transaction['tickets'] : array();
                $main_attendee = current($attendees);
                $name = isset($main_attendee['_name']) ? $main_attendee['_name'] : get_userdata($user_id)->display_name;
                $user_data = $user_id ? get_userdata($user_id) : 0;
                $email = !empty($user_data) && isset($user_data->user_email) ?  $user_data->user_email : $main_attendee['email'];
                $book_subject = $name . ' - ' . $email;
                wp_update_post([
                    'ID' => $book_id,
                    'post_title' => $book_subject,
                ]);

                update_post_meta($book_id, 'mec_email', $email);
                update_post_meta($book_id, 'mec_price', $transaction['total']);
                update_post_meta($book_id, 'mec_order_id', $order_id);
                update_post_meta($product->ID, 'mec_payment_complete', $book_id);
                update_post_meta($order_id, 'mec_order_type', 'mec_ticket');
                update_option($transaction_id . '_MEC_payment_complete', $book_id);
            }
        }
        // Capture Order as Completed
        update_post_meta($order_id, 'mw_capture_completed', true);
    }

} //Payment

Payment::instance();
