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
*  AdminOrders.
*
*  @author      Webnus <info@webnus.biz>
*  @package     Modern Events Calendar
*  @since       1.0.0
**/
class AdminOrders extends Helper
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
        add_action('manage_shop_order_posts_custom_column', [$this, 'woocommerce_column_type_in_shop_orders'], 99, 2);
        add_filter('manage_edit-shop_order_columns', [$this, 'shop_orders_column_type'], 99, 1);
        add_filter('manage_edit-shop_order_sortable_columns', [$this, 'shop_orders_column_type'], 99);
        add_action('woocommerce_order_details_after_order_table', [$this, 'order_details_after_order_table'], 10, 1);
        add_action('woocommerce_before_order_itemmeta', [$this, 'order_title_correct']);
    }

    /**
    * Correct Order Item Title
    *
    * @since     1.0.0
    */
    public function order_title_correct () {
        $rnd = md5(microtime() . random_int(0, 100));
        echo '<div id="randomID' . $rnd . '"></div>';
        echo '<script>
            jQuery("#randomID' . $rnd . '").parents("td").first().find(".wc-order-item-name").replaceWith(function () {
                return jQuery("<strong />").append(jQuery(this).contents());
            });
        </script>';
    }


    /**
     * Woocommerce Column Type In Shop Orders
     *
     * @param string $column_name
     * @param integer $post_id
     * @return void
     */
    public function woocommerce_column_type_in_shop_orders($column_name, $post_id)
    {
        if ($column_name == 'order_type') {
            if ($order_type = get_post_meta($post_id, 'mec_order_type', true)) {
                if ($order_type == 'mec_ticket') {
                    echo esc_html__('MEC Ticket', 'mec-woocommerce');
                    return;
                }
            }
            echo esc_html__('Shop Order', 'mec-woocommerce');
        }
        return;
    }

    /**
     * Column Order Type
     *
     * @param array $columns
     * @return array
     */
    public function shop_orders_column_type($columns)
    {
        $columns['order_type'] = esc_html__('Type', 'mec-woocommerce');
        return $columns;
    }

    /**
     * Display Order Details After Order Table
     *
     * @param object $order
     * @return void
     */
    public function order_details_after_order_table($order)
    {
        $order_id   = $order->get_id();
        $order_type = get_post_meta($order_id, 'mec_order_type', true);
        if (empty($order_type) || $order_type != 'mec_ticket') {
            return;
        }
        $transactions = [];
        foreach ($order->get_items() as $item_id => $order_item) {
            $product = $this->get_product($order_item['product_id'], true);
            if ($product) {
                $transaction_id                  = get_post_meta($product->ID, 'transaction_id', true);
                $transactions[$transaction_id] = $transaction_id;
            }
        }

        ?>
        <div>
            <h2><?php echo esc_html__('Attendees List', 'mec-woocommerce'); ?></h2>

            <table class="woocommerce-table shop_table order_details">
                <thead>
                    <th><?php echo esc_html__('Attendees', 'mec-woocommerce'); ?></th>
                    <th><?php echo esc_html__('Information', 'mec-woocommerce'); ?></th>
                </thead>

                <tbody>
                    <?php
                    foreach ($transactions as $transaction_id) {
                        $transaction = get_option($transaction_id, false);

                        foreach ($transaction['tickets'] as $ticket) {
                            if (!isset($ticket['email'])) {
                                continue;
                            }

                            if (isset($transaction['first_for_all']) && $transaction['first_for_all'] == '1') {
                    ?>
                                <tr>
                                    <td>
                                        <span class="mec-attendee-name"><?php echo esc_html__('Name: ', 'mec-woocommerce'); ?><?php echo $ticket['_name']; ?></span>
                                        <br>
                                        <span class="mec-attendee-email"><?php echo $ticket['email']; ?></span>
                                        <span class="mec-attendee-tickets-count"><?php echo '<strong> × ' . count($transaction['tickets']) . '</strong>'; ?></span>

                                    </td>
                                    <td>
                                        <span class="mec-attendee-name"><a href="<?php echo get_permalink($transaction['event_id']); ?>"><?php echo get_the_title($transaction['event_id']); ?></a></span>
                                        <br>
                                        <span class="mec-attendee-date"><?php echo $this->get_date_label($transaction['date'], $transaction['event_id']); ?></span>
                                    </td>
                                </tr>
                            <?php
                                break;
                            } else {
                            ?>
                                <tr>
                                    <td>
                                        <span class="mec-attendee-name"><?php echo esc_html__('Name: ', 'mec-woocommerce'); ?><?php echo $ticket['_name']; ?></span>
                                        <br>
                                        <span class="mec-attendee-email"><?php echo $ticket['email']; ?></span>
                                    </td>
                                    <td>
                                        <span class="mec-attendee-name"><a href="<?php echo get_permalink($transaction['event_id']); ?>"><?php echo get_the_title($transaction['event_id']); ?></a></span>
                                        <br>
                                        <span class="mec-attendee-date"><?php echo $this->get_date_label($transaction['date'], $transaction['event_id']); ?></span>
                                    </td>
                                </tr>
                    <?php
                            }
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    <?php
    }

} //AdminOrders

AdminOrders::instance();
