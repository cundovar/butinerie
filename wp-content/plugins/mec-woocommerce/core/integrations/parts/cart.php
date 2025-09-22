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
*  Cart.
*
*  @author      Webnus <info@webnus.biz>
*  @package     Modern Events Calendar
*  @since       1.0.0
**/
class Cart extends Helper
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
        add_filter('woocommerce_is_purchasable', [$this, 'valid_mec_ticket_products_purchasable'], 20, 2);
        add_action('woocommerce_check_cart_items', [$this, 'update_transaction_data'], 10);
        add_filter('woocommerce_quantity_input_args', [$this, 'quantityArgs'], 10, 2);
    }

   /**
    * Check MEC Products Purchasable Status
    *
    * @param boolean $purchasable
    * @param object $product
    * @return boolean
    */
    public static function valid_mec_ticket_products_purchasable($purchasable, $product)
    {
        if ($product->exists() && ('mec_tickets' === $product->get_status())) {
            $purchasable = true;
        }

        return $purchasable;
    }

    /**
     * Update Transaction Data
     *
     * @since     1.0.0
     */
    public function update_transaction_data()
    {
        $factory = \MEC::getInstance('app.libraries.factory');
        foreach (wc()->cart->get_cart() as $key => $item) {
            $product_id     = $item['product_id'];
            $transaction_id = get_post_meta($product_id, 'transaction_id', true);
            $event_id       = get_post_meta($product_id, 'event_id', true);
            $tickets        = get_post_meta($event_id, 'mec_tickets', true);
            $ticket_data    = [];
            $_removed       = $removed = $_added = $added = 0;
            $__added        = $__removed = [];

            if ($transaction_id) {
                $transaction = get_option($transaction_id, false);
                $tikID = 0;
                if (isset($transaction['first_for_all']) && $transaction['first_for_all'] == '1' || $item['quantity'] == '0') {
                    foreach ($transaction['tickets'] as $ticket) {
                        if(!isset($tickets[$ticket['id']]['id'])) {
                            $tikID++;
                            $tickets[$ticket['id']]['id'] = $tikID;
                        }
                        if (isset($ticket['id'])) {
                            if (isset($ticket_data[$tickets[$ticket['id']]['id']])) {
                                $ticket_data[$tickets[$ticket['id']]['id']]++;
                            } else {
                                $ticket_data[$tickets[$ticket['id']]['id']] = 1;
                            }
                        }
                    }

                    if ($t_id = get_post_meta($product_id, 'ticket_id', true)) {
                        $t_count = isset($ticket_data[$t_id]) ? $ticket_data[$t_id] : 1;
                        if ($t_count != $item['quantity']) {
                            if ($t_count > $item['quantity']) {
                                $_removed = $removed = $t_count - $item['quantity'];
                            } else {
                                $_added = $added = $item['quantity'] - $t_count;
                            }
                            $ticket_variations = $factory->main->ticket_variations($transaction['event_id']);
                            foreach ($transaction['tickets'] as $t_key => $ticket) {
                                if ($tickets[$ticket['id']]['id'] == $t_id) {

                                    if ($removed) {
                                        unset($transaction['tickets'][$t_key]);
                                        $removed     = $removed - 1;
                                        $__removed[] = $tickets[$ticket['id']]['price'];
                                        foreach ($ticket['variations'] as $tk => $v) {
                                            $__removed[] = $ticket_variations[$tk]['price'] * $v;
                                        }
                                    }

                                    if ($added) {
                                        $transaction['tickets'][] = $ticket;
                                        $added                    = $added - 1;
                                        $__added[]                = $tickets[$ticket['id']]['price'];
                                        foreach ($ticket['variations'] as $tk => $v) {
                                            $__added[] = $ticket_variations[$tk]['price'] * $v;
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if ($_added || $_removed) {

                        foreach ($__removed as $price) {
                            $transaction['total'] = $transaction['total'] - $price;
                            $transaction['price'] = $transaction['price'] - $price;
                            $transaction['price_details']['total'] = $transaction['price_details']['total'] - $price;
                        }
                        update_option($transaction_id, $transaction);
                    }
                }
            }
        }
    }

    /**
     * Customize Product Quantity Arguments
     *
     * @param array $args
     * @param object $product
     * @return array
     */
    public static function quantityArgs($args, $product)
    {
        $transaction_id = get_post_meta($product->get_id(), 'transaction_id', true);
        $cantChangeQuantity = get_post_meta($product->get_id(), 'cantChangeQuantity', true);
        if ($transaction_id) {
            $transaction = get_option($transaction_id, false);
            if (isset($transaction['first_for_all']) && $transaction['first_for_all'] == '0' || $cantChangeQuantity) {
                $input_value       = $args['input_value'];
                $args['min_value'] = $args['max_value'] = 1;
            } else if (isset($transaction['first_for_all']) && $transaction['first_for_all'] == '1') {

                $input_value       = $args['input_value'];
                $args['min_value'] = $input_value;
                $args['max_value'] = strval($input_value);
                $args['step'] = 0;
            }
            $tickets = get_post_meta($transaction['event_id'], 'mec_tickets', true);
            $ticket_name = get_post_meta($product->get_id(), 'ticket_name', true);
            $ticket_id = get_post_meta($product->get_id(), 'ticket_id', true);
            $book   = \MEC::getInstance('app.libraries.book');
            $render   = \MEC::getInstance('app.libraries.render');
            $dates = $render->dates($transaction['event_id'], NULL, 10);
            $occurrence_time = isset($dates[0]['start']['timestamp']) ? $dates[0]['start']['timestamp'] : strtotime($dates[0]['start']['date']);
            $availability = $book->get_tickets_availability($transaction['event_id'], $occurrence_time);
            $tikID = 0;
            foreach ($transaction['tickets'] as $ticket) {
                if(!isset($tickets[$ticket['id']]['id'])) {
                    $tikID++;
                    $tickets[$ticket['id']]['id'] = $tikID;
                }
                if ($ticket_id == $tickets[$ticket['id']]['id']) {
                    $ticket_limit = isset($availability[$ticket['id']]) ? $availability[$ticket['id']] : -1;
                    if ($ticket_limit !== -1) {
                        $args['max_value'] = $ticket_limit;
                    }
                    break;
                }
            }
        }

        return $args;
    }


} //Cart

Cart::instance();
