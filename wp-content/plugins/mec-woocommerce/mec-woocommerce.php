<?php
/**
 *	Plugin Name: WooCommerce Integration for MEC
 *	Plugin URI: http://webnus.net/modern-events-calendar/
 *	Description: You can purchase ticket and WooCommerce products at the same time.
 *	Author: Webnus
 *	Version: 1.5.1
 *	Text Domain: mec-woocommerce
 *	Domain Path: /languages
 *	Author URI: http://webnus.net
 */


namespace MEC_Woocommerce;

// Don't load directly
if (!defined('ABSPATH')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit;
}
/**
 *   Base.
 *
 *   @author     Webnus <info@webnus.biz>
 *   @package    Modern Events Calendar
 *   @since     1.0.0
 */
class Base
{

	/**
	 *  Instance of this class.
	 *
	 *  @since   1.0.0
	 *  @access  public
	 *  @var     MEC_Woocommerce
	 */
	public static $instance;

	public static $is_mec_active;

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
		if (defined('MECWOOINTVERSION')) {
			return;
		}

		$this->settingUp();
		$this->preLoad();
		$this->setHooks($this);

		if( is_admin() && static::checkPlugins() ){

			Admin::getInstance();
		}

		do_action('MEC_Woocommerce_init');
	}

	/**
	 *  Global Variables.
	 *
	 *  @since   1.0.0
	 */
	public function settingUp()
	{
		define('MECWOOINTVERSION', '1.5.1');
		define('MECWOOINTDIR', plugin_dir_path(__FILE__));
		define('MECWOOINTURL', plugin_dir_url(__FILE__));
		define('MECWOOINTASSETS', MECWOOINTURL . 'assets/');
		define('MECWOOINTNAME' , 'Woocommerce Integration');
		define('MECWOOINTSLUG' , 'mec-woocommerce');
		define('MECWOOINTOPTIONS' , 'mec_woo_options');
		define('MECWOOINTTEXTDOMAIN' , 'mec-woocommerce');
		define('MECWOOINTMAINFILEPATH' ,__FILE__);
		define('MECWOOINTPABSPATH', dirname(__FILE__));
		define('MECWOOINT_API_URL', 'https://webnus.net/api/v3');

		register_deactivation_hook( __FILE__, [ $this, 'uninstall' ] );

		$this->add_option();

		if (!defined('DS')) {
			define('DS', DIRECTORY_SEPARATOR);
		}
	}

	/**
	 * Install (Activation Hook)
	 *
	 * @return void
	 */
	public function install() {
		$allProducts = get_posts(
			array(
				'post_type'   => 'mec-product',
				'numberposts' => -1,
				'post_status' => 'mec_tickets',
			)
		);

		foreach ( $allProducts as $product ) {
			if ( $product->post_status == 'mec_tickets' ) {
				wp_update_post(
					[
						'ID'        => $product->ID,
						'post_type' => 'product',
					]
				);
			}
		}
	}

	/**
	 * Uninstall (Deactivation Hook)
	 *
	 * @return void
	 */
	public static function uninstall() {
		$allProducts = get_posts(
			array(
				'post_type'   => 'product',
				'numberposts' => -1,
				'post_status' => 'mec_tickets',
			)
		);
		foreach ( $allProducts as $product ) {
			if ( $product->post_status == 'mec_tickets' ) {
				wp_update_post(
					[
						'ID'        => $product->ID,
						'post_type' => 'mec-product',
					]
				);
			}
		}
	}

	/**
	 *  Set Hooks
	 *
	 *  @since     1.0.0
	 */
	public function setHooks()
	{
		add_action( 'wp_loaded', [ $this, 'load_languages' ] );
		add_action( 'wp_loaded', [ $this, 'install' ] );

		add_filter('wpml_language_filter_extra_conditions_snippet', array( __CLASS__, 'filter_extra_conditions_snippet' ) );
	}

	/**
	 * Load Translation Languages
	 *
	 * @return void
	 */
	public function load_languages() {

		load_plugin_textdomain(
			'mec-woocommerce',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}

	/**
	 * Add "mec_woo_options" Option
	 *
	 * @return void
	 */
	public function add_option() {
		$addon_information = array(
			'product_name'  => '',
			'purchase_code' => '',
		);
		$has_option        = get_option( 'mec_woo_options', 'false' );
		if ( $has_option == 'false' ) {
			add_option( 'mec_woo_options', $addon_information );
		}
	}

	/**
	 * Plugin Requirements Check
	 *
	 * @since 1.0.0
	 */
	public static function checkPlugins()
	{
		$MEC_Woocommerce = self::instance();

		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if (is_plugin_active('modern-events-calendar-lite/modern-events-calendar-lite.php') && !class_exists('\MEC')) {
			self::$is_mec_active = false;
			add_action('admin_notices', [$MEC_Woocommerce, 'send_mec_lite_notice']);
			return false;
		} else if ( ! is_plugin_active( 'modern-events-calendar/mec.php' ) && !class_exists('\MEC') ) {
			self::$is_mec_active = false;
			add_action( 'admin_notices', [ $MEC_Woocommerce, 'send_mec_notice' ] );

			if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
				add_action( 'admin_notices', [ $MEC_Woocommerce, 'send_woo_notice' ] );
				self::$is_mec_active = false;
				return false;
			}

			return false;

		} else {
			if(!defined('MEC_VERSION')) {
				$plugin_data = get_plugin_data( realpath( WP_PLUGIN_DIR . '/modern-events-calendar/mec.php' ) );
				$version     = str_replace( '.', '', $plugin_data['Version'] );
			} else {
				$version = str_replace('.', '', MEC_VERSION);
			}

			if ( $version <= 422 ) {
				self::$is_mec_active = false;
				add_action( 'admin_notices', [ $MEC_Woocommerce, 'send_mec_version_notice' ], 'version' );

				if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
					add_action( 'admin_notices', [ $MEC_Woocommerce, 'send_woo_notice' ] );
					self::$is_mec_active = false;
					return false;
				}
				return false;
			}
		}

		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			add_action( 'admin_notices', [ $MEC_Woocommerce, 'send_woo_notice' ] );
			self::$is_mec_active = false;
			return false;
		}
		return true;
	}

	/**
	* Is MEC installed ?
	*
	* @since     1.0.0
	*/
	public function is_mec_installed() {
		if(class_exists('\MEC')) {
			return true;
		}
		$file_path         = 'modern-events-calendar/mec.php';
		$installed_plugins = get_plugins();
		return isset( $installed_plugins[ $file_path ] );
	}

	/**
	* Is WooCommerce installed ?
	*
	* @since     1.0.0
	*/
	public function is_woocommerce_installed() {
		$file_path         = 'woocommerce/woocommerce.php';
		$installed_plugins = get_plugins();
		return isset( $installed_plugins[ $file_path ] );
	}

	/**
	* Send Admin Notice (MEC)
	*
	* @since 1.0.0
	*/
	public function send_mec_notice( $type = false ) {
		$screen = get_current_screen();
		if ( isset( $screen->parent_file ) && 'plugins.php' === $screen->parent_file && 'update' === $screen->id ) {
			return;
		}
		if(class_exists('\MEC')) {
			return;
		}

		$plugin = 'modern-events-calendar/mec.php';
		if ( $this->is_mec_installed() ) {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			$activation_url = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . $plugin . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $plugin );
			$message        = '<p>' . __( 'WooCommerce Integration is not working because you need to activate the Modern Events Calendar plugin.', 'mec-woocommerce' ) . '</p>';
			$message       .= '<p>' . sprintf( '<a href="%s" class="button-primary">%s</a>', $activation_url, __( 'Activate Modern Events Calendar Now', 'mec-woocommerce' ) ) . '</p>';
		} else {
			if ( ! current_user_can( 'install_plugins' ) ) {
				return;
			}
			$install_url = 'https://webnus.net/modern-events-calendar/';
			$message     = '<p>' . __( 'WooCommerce Integration is not working because you need to install the Modern Events Calendar plugin', 'mec-woocommerce' ) . '</p>';
			$message    .= '<p>' . sprintf( '<a href="%s" class="button-primary">%s</a>', $install_url, __( 'Install Modern Events Calendar Now', 'mec-woocommerce' ) ) . '</p>';
		}
		?>
			<div class="notice notice-error is-dismissible">
				<p><?php echo $message; ?></p>
			</div>
		<?php
	}

	/**
	* Send Admin Notice (MEC Pro)
	*
	* @since 1.0.0
	*/
	public function send_mec_pro_notice( $type = false ) {
		$screen = get_current_screen();
		if ( isset( $screen->parent_file ) && 'plugins.php' === $screen->parent_file && 'update' === $screen->id ) {
			return;
		}

		$plugin = 'modern-events-calendar/mec.php';
		if ( $this->is_mec_installed() ) {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			$activation_url = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . $plugin . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $plugin );
			$message        = '<p>' . __( 'In order to use the plugin, please Active Modern Events Calendar Pro.', 'mec-woocommerce' ) . '</p>';
			$message       .= '<p>' . sprintf( '<a href="%s" class="button-primary">%s</a>', $activation_url, __( 'Activate Modern Events Calendar Now', 'mec-woocommerce' ) ) . '</p>';
		} else {
			if ( ! current_user_can( 'install_plugins' ) ) {
				return;
			}
			$install_url = 'https://webnus.net/pricing/#plugins';
			$message     = '<p>' . __( 'In order to use the plugin, please purchase Modern Events Calendar Pro.', 'mec-woocommerce' ) . '</p>';
			$message    .= '<p>' . sprintf( '<a href="%s" class="button-primary">%s</a>', $install_url, __( 'Purchase Modern Events Calendar Now', 'mec-woocommerce' ) ) . '</p>';
		}
		?>
			<div class="notice notice-error is-dismissible">
				<p><?php echo $message; ?></p>
			</div>
		<?php
	}

	/**
	* Send Admin Notice (MEC Version)
	*
	* @since 1.0.0
	*/
	public function send_mec_version_notice( $type = false ) {
		$screen = get_current_screen();
		if ( isset( $screen->parent_file ) && 'plugins.php' === $screen->parent_file && 'update' === $screen->id ) {
			return;
		}

		$plugin = 'modern-events-calendar/mec.php';

		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		$install_url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=modern-events-calendar' ), 'install-plugin_' . $plugin );
		$message     = '<p>' . __( 'WooCommerce Integration is not working because you need to install latest version of Modern Events Calendar plugin', 'mec-woocommerce' ) . '</p>';
		$message    .= esc_html__( 'Minimum version required' ) . ': <b> 4.2.3 </b>';
		$message    .= '<p>' . sprintf( '<a href="%s" class="button-primary">%s</a>', $install_url, __( 'Update Modern Events Calendar Now', 'mec-woocommerce' ) ) . '</p>';

		?>
			<div class="notice notice-error is-dismissible">
				<p><?php echo $message; ?></p>
			</div>
		<?php
	}

	/**
	* Send Admin Notice ( Woocommerce )
	*
	* @since 1.0.0
	*/
	public function send_woo_notice() {

		$screen = get_current_screen();
		if ( isset( $screen->parent_file ) && 'plugins.php' === $screen->parent_file && 'update' === $screen->id ) {
			return;
		}
		$plugin = 'woocommerce/woocommerce.php';
		if ( $this->is_woocommerce_installed() ) {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			$activation_url = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . $plugin . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $plugin );
			$message        = '<p>' . __( 'WooCommerce Integration is not working because you need to activate the WooCommerce plugin.', 'mec-woocommerce' ) . '</p>';
			$message       .= '<p>' . sprintf( '<a href="%s" class="button-primary">%s</a>', $activation_url, __( 'Activate WooCommerce Now', 'mec-woocommerce' ) ) . '</p>';
		} else {
			if ( ! current_user_can( 'install_plugins' ) ) {
				return;
			}
			$install_url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=WooCommerce' ), 'install-plugin_WooCommerce' );
			$message     = '<p>' . __( 'WooCommerce Integration is not working because you need to install the WooCommerce plugin', 'mec-woocommerce' ) . '</p>';
			$message    .= '<p>' . sprintf( '<a href="%s" class="button-primary">%s</a>', $install_url, __( 'Install WooCommerce Now', 'mec-woocommerce' ) ) . '</p>';
		}
		?>
			<div class="notice notice-error is-dismissible">
				<p><?php echo $message; ?></p>
			</div>
		<?php
	}

	/**
	* Send Admin Notice ( Woocommerce )
	*
	* @since 1.0.0
	*/
	public static function send_mec_lite_notice() {
		$screen = get_current_screen();
		if ( isset( $screen->parent_file ) && 'plugins.php' === $screen->parent_file && 'update' === $screen->id ) {
			return;
		}

		$plugin = 'modern-events-calendar/mec.php';

		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		$install_url = 'https://webnus.net/modern-events-calendar/';
		$message     = '<p>' . __( 'WooCommerce Integration is not working because you need to install latest version of Modern Events Calendar plugin (PRO)', 'mec-woocommerce' ) . '</p>';
		$message    .= '<p>' . sprintf( '<a href="%s" class="button-primary">%s</a>', $install_url, __( 'Upgrade to Modern Events Calendar Pro', 'mec-woocommerce' ) ) . '</p>';

		?>
			<div class="notice notice-error is-dismissible">
				<p><?php echo $message; ?></p>
			</div>
		<?php
	}

	/**
	 *  PreLoad
	 *
	 *  @since     1.0.0
	 */
	public function preLoad()
	{
		if(static::checkPlugins()) {
			include_once MECWOOINTDIR . DS . 'core' . DS . 'autoloader.php';
		}
	}

	public static function filter_extra_conditions_snippet($sql) {

		$sql = " AND post_status <> 'mec_tickets' ".$sql;

		return $sql;
	}


} //Base

add_action(
	'plugins_loaded',
	function() {
		\MEC_Woocommerce\Base::instance();
	}
);

add_filter(
    'MEC_register_gateways',
    function ($gateways) {
        $gateways['MEC_gateway_add_to_woocommerce_cart'] = \MEC_Woocommerce\Core\Gateway\Init::instance();
        return $gateways;
    }
);