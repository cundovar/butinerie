<?php

namespace MEC_Woocommerce\Core\Gateway;

use \MEC_Woocommerce\Core\Integrations\AddToCart;
// Don't load directly
if (!defined('ABSPATH')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit;
}

/**
*  Init.
*
*  @author      Webnus <info@webnus.biz>
*  @package     Modern Events Calendar
*  @since       1.0.0
**/
class Init extends \MEC_gateway
{

    /**
     * Gateway ID
     *
     * @var integer
     */
    public $id = 1995;

    /**
     * Options
     *
     * @var array
     */
    public $options;

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

        parent::__construct();

        // Gateway options
        $this->options = $this->options();
    }

    /**
     * Gateway Label
     *
     * @return void
     */
    public function label()
    {
        return __('Add to cart', 'mec-woocommerce');
    }

    /**
     * Gateway Color
     *
     * @return void
     */
    public function color()
    {
        return '#9b5c8f';
    }

    /**
     * Gateway Settings Form
     *
     * @return void
     */
    public function options_form()
    {
?>
        <div class="mec-form-row">
            <label>
                <input type="hidden" name="mec[gateways][<?php echo $this->id(); ?>][status]" value="0" />
                <input onchange="jQuery('#mec_gateways<?php echo $this->id(); ?>_container_toggle').toggle();" value="1" type="checkbox" name="mec[gateways][<?php echo $this->id(); ?>][status]" <?php if (isset($this->options['status']) and $this->options['status']) {
                    echo 'checked="checked"';
                } ?> />
                <?php _e('Add to WooCommerce Cart', 'mec-woocommerce'); ?>
            </label>
        </div>
        <div id="mec_gateways<?php echo $this->id(); ?>_container_toggle" class="mec-gateway-options-form
            <?php
            if ((isset($this->options['status']) and !$this->options['status']) or !isset($this->options['status'])) {
                echo 'mec-util-hidden';
            }
            ?>
            ">
            <div class="mec-form-row">
                <label class="mec-col-12" for="mec_gateways<?php echo $this->id(); ?>_sync_woo_order_status">
                    <input type="checkbox" id="mec_gateways<?php echo $this->id(); ?>_sync_woo_order_status" name="mec[gateways][<?php echo $this->id(); ?>][sync_order_status_for_booking]" <?php echo (isset($this->options['sync_order_status_for_booking']) and trim($this->options['sync_order_status_for_booking']) == 'on') ? 'checked="checked"' : ''; ?> />
                    <?php _e('Sync MEC  Booking confimation Status with WooCommerce Order', 'mec-woocommerce'); ?>
                </label>
            </div>
            <div class="mec-form-row">
                <label class="mec-col-12" for="mec_gateways<?php echo $this->id(); ?>_use_mec_taxes">
                    <input type="checkbox" id="mec_gateways<?php echo $this->id(); ?>_use_mec_taxes" name="mec[gateways][<?php echo $this->id(); ?>][use_mec_taxes]" <?php echo (isset($this->options['use_mec_taxes']) and trim($this->options['use_mec_taxes']) == 'on') ? 'checked="checked"' : ''; ?> />
                    <?php _e('Adding MEC Ticket Taxes/Fees to Cart', 'mec-woocommerce'); ?>
                    <span class="mec-tooltip">
                        <div class="box right">
                            <h5 class="title"><?php _e('MEC Taxes/Fees', 'mec'); ?></h5>
                            <div class="content">
                                <p><?php esc_attr_e('Adding MEC taxes/fees to your WooCommerce cart', 'mec'); ?><a href="https://webnus.net/dox/modern-events-calendar/mec-woocommerce-addon/" target="_blank"><?php _e('Read More', 'mec'); ?></a></p>
                            </div>
                        </div>
                        <i title="" class="dashicons-before dashicons-editor-help"></i>
                    </span>
                </label>
            </div>
            <div class="mec-form-row">
                <label class="mec-col-12" for="mec_gateways<?php echo $this->id(); ?>_remove_woo_taxes">
                    <input type="checkbox" id="mec_gateways<?php echo $this->id(); ?>_remove_woo_taxes" name="mec[gateways][<?php echo $this->id(); ?>][remove_woo_taxes]" <?php echo (isset($this->options['remove_woo_taxes']) and trim($this->options['remove_woo_taxes']) == 'on') ? 'checked="checked"' : ''; ?> onchange="jQuery('#mec_wrap_use_woo_tax').toggle();"/>
                    <?php _e('Remove WooCommerce Taxes from WooCoommerce Cart', 'mec-woocommerce'); ?>
                    <span class="mec-tooltip">
                        <div class="box left">
                            <h5 class="title"><?php _e('Remove Standard Taxes', 'mec'); ?></h5>
                            <div class="content">
                                <p><?php esc_attr_e('Removing WooCommerce standard taxes on the cart. (prevent adding Woocommerce taxes on the tickets).', 'mec'); ?><a href="https://webnus.net/dox/modern-events-calendar/mec-woocommerce-addon/" target="_blank"><?php _e('Read More', 'mec'); ?></a></p>
                            </div>
                        </div>
                        <i title="" class="dashicons-before dashicons-editor-help"></i>
                    </span>
                </label>
            </div>
            <div class="mec-form-row <?php if((isset($this->options['remove_woo_taxes']) and $this->options['remove_woo_taxes'])) echo 'mec-util-hidden'; ?>"  id="mec_wrap_use_woo_tax">
                <label class="mec-col-12" for="mec_gateways<?php echo $this->id(); ?>_use_woo_taxes">
                    <input type="checkbox" id="mec_gateways<?php echo $this->id(); ?>_use_woo_taxes" name="mec[gateways][<?php echo $this->id(); ?>][use_woo_taxes]" <?php echo (isset($this->options['use_woo_taxes']) and trim($this->options['use_woo_taxes']) == 'on') ? 'checked="checked"' : ''; ?> />
                    <?php _e('Adding WooCommerce Standard Taxes in MEC Booking', 'mec-woocommerce'); ?>
                    <span class="mec-tooltip">
                        <div class="box left">
                            <h5 class="title"><?php _e('WooCommerce Standard Taxes', 'mec'); ?></h5>
                            <div class="content">
                                <p><?php esc_attr_e('Adding WooCommerce standard taxes to MEC booking items, not other things anymore', 'mec'); ?><a href="https://webnus.net/dox/modern-events-calendar/mec-woocommerce-addon/" target="_blank"><?php _e('Read More', 'mec'); ?></a></p>
                            </div>
                        </div>
                        <i title="" class="dashicons-before dashicons-editor-help"></i>
                    </span>
                </label>
            </div>

            <div class="mec-form-row">
                <label class="mec-col-3" for="mec_gateways<?php echo $this->id(); ?>_title"><?php _e('Title', 'mec-woocommerce'); ?></label>
                <div class="mec-col-9">
                    <input style="width:100%" type="text" id="mec_gateways<?php echo $this->id(); ?>_title" name="mec[gateways][<?php echo $this->id(); ?>][title]" value="<?php echo (isset($this->options['title']) and trim($this->options['title'])) ? $this->options['title'] : ''; ?>" placeholder="<?php echo $this->label(); ?>" />
                </div>
            </div>
            <div class="mec-form-row">
                <label class="mec-col-3" for="mec_gateways<?php echo $this->id(); ?>_comment"><?php _e('Comment', 'mec-woocommerce'); ?></label>
                <div class="mec-col-9">
                    <textarea style="width:92%" id="mec_gateways<?php echo $this->id(); ?>_comment" name="mec[gateways][<?php echo $this->id(); ?>][comment]" placeholder="<?php echo __('Add to Cart Gateway Description','mec-woocommerce'); ?>"><?php echo (isset($this->options['comment']) and trim($this->options['comment'])) ? stripslashes($this->options['comment']) : ''; ?></textarea>
                    <span class="mec-tooltip">
                        <div class="box left">
                            <h5 class="title"><?php _e('Comment', 'mec'); ?></h5>
                            <div class="content">
                                <p><?php esc_attr_e('HTML allowed.', 'mec'); ?><a href="https://webnus.net/dox/modern-events-calendar/mec-woocommerce-addon/" target="_blank"><?php _e('Read More', 'mec'); ?></a></p>
                            </div>
                        </div>
                        <i title="" class="dashicons-before dashicons-editor-help"></i>
                    </span>
                </div>
            </div>
            <div class="mec-form-row">
                <label class="mec-col-3" for="mec_gateways<?php echo $this->id(); ?>_index"><?php _e('Position', 'mec'); ?></label>
                <div class="mec-col-9">
                    <input type="number" min="0" step="1" id="mec_gateways<?php echo $this->id(); ?>_index"
                        name="mec[gateways][<?php echo $this->id(); ?>][index]"
                        value="<?php echo (isset($this->options['index']) and trim($this->options['index'])) ? $this->options['index'] : 6; ?>"
                        placeholder="<?php echo esc_attr__('Position', 'mec'); ?>"/>
                </div>
            </div>
            <div class="mec-form-row">
                <?php $redirect_to = isset( $this->options['redirect_after_to_cart'] ) ? $this->options['redirect_after_to_cart'] : 'optional_cart'; ?>
                <label class="mec-col-3" for="mec_gateways<?php echo $this->id(); ?>_redirect_after_to_cart"><?php _e('After Add to Cart', 'mec'); ?></label>
                <div class="mec-col-9">
                    <select id="mec_gateways<?php echo $this->id(); ?>_redirect_after_to_cart" name="mec[gateways][<?php echo $this->id(); ?>][redirect_after_to_cart]">
                        <option value="cart" <?php selected( $redirect_to, 'cart' ); ?>><?php _e('Redirect to Cart', 'mec'); ?></option>
                        <option value="checkout" <?php selected( $redirect_to, 'checkout' ); ?>><?php _e('Redirect to Checkout', 'mec'); ?></option>
                        <option value="optional_cart" <?php selected( $redirect_to, 'optional_cart' ); ?>><?php _e('Optional View Cart Button', 'mec'); ?></option>
                        <option value="optional_checkout" <?php selected( $redirect_to, 'optional_checkout' ); ?>><?php _e('Optional Checkout Button', 'mec'); ?></option>
                    </select>
                </div>
            </div>
        </div>
    <?php
    }

    /**
     * CheckOut Form
     *
     * @param string $transaction_id
     * @param array $params
     * @return void
     */
    public function checkout_form($transaction_id, $params = array()){

        $redirect_to = $this->get_redirect_to_link();
        $redirect_to_type = $this->get_redirect_to_type();
        ?>
        <form id="mec_do_transaction_add_to_woocommerce_cart_form<?php echo $transaction_id; ?>" class="mec-click-pay">
            <input type="hidden" name="action" value="mec_do_transaction_add_to_woocommerce_cart" />
            <input type="hidden" name="transaction_id" value="<?php echo $transaction_id; ?>" />
            <input type="hidden" name="gateway_id" value="<?php echo $this->id(); ?>" />
            <input type="hidden" name="redirect_to" value="<?php echo $redirect_to; ?>" />
            <input type="hidden" name="redirect_to_type" value="<?php echo $redirect_to_type; ?>" />
            <?php wp_nonce_field('mec_transaction_form_' . $transaction_id); ?>
            <?php AddToCart::instance()->render_add_to_cart_button($transaction_id, $redirect_to, $redirect_to_type ); ?>
            <?php do_action('mec_booking_checkout_form_before_end', $transaction_id); ?>
        </form>
        <div class="mec-gateway-message mec-util-hidden" id="mec_do_transaction_add_to_woocommerce_cart_message<?php echo $transaction_id; ?>"></div>
        <?php
    }

    /**
     * Do Transaction
     *
     * @param string $transaction_id
     * @return void
     */
    public function do_transaction($transaction_id = null)
    {
        if(!$transaction_id) {
            return;
        }

        $transaction = $this->book->get_transaction($transaction_id);
        $attendees   = isset($transaction['tickets']) ? $transaction['tickets'] : array();
        $attention_date = isset($transaction['date']) ? $transaction['date'] : '';
        $attention_times = explode(':', $attention_date);
        $date = date('Y-m-d H:i:s', trim($attention_times[0]));

        // Is there any attendee?
        if (!count($attendees)) {
            $this->response(
                array(
                    'success' => 0,
                    'code'    => 'NO_TICKET',
                    'message' => __(
                        'There is no attendee for booking!',
                        'mec'
                    ),
                )
            );
        }

        $main_attendee = isset($attendees[0]) ? $attendees[0] : array();
        $name          = isset($main_attendee['_name']) ? $main_attendee['_name'] : '';
        $ticket_ids = '';
        $attendees_info = array();
        $new_attendees = array();

        foreach ($attendees as $k => $attendee) {
            $attendee['name'] = $attendee['_name'];
            $attendees[$k]['name'] = $attendee['_name'];
            $new_attendees[] = $attendee;
        }

        foreach ($new_attendees as $attendee) {
            $ticket_ids .= $attendee['id'] . ',';
            if (!array_key_exists($attendee['email'], $attendees_info)) $attendees_info[$attendee['email']] = array('count' => $attendee['count']);
            else $attendees_info[$attendee['email']]['count'] = ($attendees_info[$attendee['email']]['count'] + $attendee['count']);
        }

        $main_attendee['name'] = $main_attendee['_name'];
        $user_id = $this->register_user($main_attendee);

        $book_id      = $this->book->add(
            array(
                'post_author' => $user_id,
                'post_type' => 'mec-books',
                'post_title' =>  $name,
                'post_date' => $date,
                'attendees_info' => $attendees_info,
                'mec_attendees' => $attendees
            ),
            $transaction_id,
            ',' . $ticket_ids
        );
        if(!$book_id) {
            return;
        }

        update_post_meta($book_id, 'mec_attendees', $new_attendees);
        update_post_meta($book_id, 'mec_gateway', 'MEC_gateway_add_to_woocommerce_cart');
        update_post_meta($book_id, 'mec_gateway_label', $this->label());

        // Fires after completely creating a new booking
        do_action('mec_booking_completed', $book_id);

        return $book_id;
    }

    /**
     * Return redirect to link
     *
     * @return string
     */
    public function get_redirect_to_link(){

        $redirect_to = $this->get_redirect_to_type();

        $link = '';
        switch( $redirect_to ){
            case 'optional_checkout':
            case 'checkout':

                $link = wc_get_checkout_url();
                break;
            case 'optional_cart':
            case 'cart':
            default:

                $link = wc_get_cart_url();
                break;
        }

        return $link;
    }

    /**
     * Return redirect to type
     *
     * @return string
     */
    public function get_redirect_to_type(){

        $redirect_to = isset( $this->options['redirect_after_to_cart'] ) ? $this->options['redirect_after_to_cart'] : '';
        $wc_redirect = \WC_Admin_Settings::get_option('woocommerce_cart_redirect_after_add', false);
        $wc_redirect = 'yes' === $wc_redirect ? 'cart' : 'optional_cart';

        return !empty( $redirect_to ) ? $redirect_to : $wc_redirect;
    }

} //Init

add_filter(
    'MEC_register_gateways',
    function ($gateways) {
        $gateways['MEC_gateway_add_to_woocommerce_cart'] = \MEC_Woocommerce\Core\Gateway\Init::instance();
        return $gateways;
    }
);

add_action(
    'mec_feature_gateways_init',
    function () {
        \MEC_Woocommerce\Core\Gateway\Init::instance();
    }
);
