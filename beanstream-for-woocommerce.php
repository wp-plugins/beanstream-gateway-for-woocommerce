<?php
/*
 * Plugin Name: Beanstream for WooCommerce
 * Plugin URI: 
 * Description: Use Beanstream for collecting credit card payments on WooCommerce.
 * Version: 1.0
 * Author: Velmurugan Kuberan
 * Author URI: https://github.com/vkuberan
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Thanks to both Stephen Zuniga & Sean Voss
 * Stephen Zuniga // http://stephenzuniga.com
 * Sean Voss // https://github.com/seanvoss/striper
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit; 

define( 'BEANSTREAM_URL_PATH', plugins_url() . '/beanstream-for-woocommerce/' );
define( 'BEANSTREAM_DIR_PATH', plugin_dir_path( __FILE__ ) ); 

class BeanStream_For_WC {
	
    public function __construct() {
        global $wpdb;
		
		//files related to beanstream which handles cc processing, customer profiling etc.,
		include_once( 'includes/general-functions.php' );
        include_once( 'includes/classes/class-beanstream-api.php' );
				
        $this->settings = get_option( 'woocommerce_beanstream_settings', array() );
		
		// Add default values for fresh installs
        $this->settings['merchant_id']  = isset( $this->settings['merchant_id'] ) ? $this->settings['merchant_id'] : '';
        $this->settings['api_pass_key'] = isset( $this->settings['api_pass_key'] ) ? $this->settings['api_pass_key'] : '';
        $this->settings['api_version']  = isset( $this->settings['api_version'] ) ? $this->settings['api_version'] : 'v1';
        $this->settings['platform']     = isset( $this->settings['platform'] ) ? $this->settings['platform'] : 'www';
		$this->settings['testmode']     = isset( $this->settings['testmode'] ) ? $this->settings['testmode'] : 'no';	
		
		//reuse credit cards
		$this->settings['saved_cards']  = isset( $this->settings['saved_cards'] ) ? $this->settings['saved_cards'] : 'yes';	
		
		//db location; it only saves the last 4 digits of the cc
		$this->settings['beanstream_db_location']       = '_beanstream_customer_info';	
		
		// Hooks
        add_filter( 'woocommerce_payment_gateways', array( $this, 'add_beanstream_gateway' ) );

        // Localization
        load_plugin_textdomain( 'beanstream-for-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );		
		
	}
	
	/**
     * Add BeanStream Gateway to WooCommerces list of Gateways
     *
     * @access      public
     * @param       array $methods
     * @return      array
     */
    public function add_beanstream_gateway( $methods ) {
        if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
            return;
        }
		
        // Include payment gateway
        include_once( 'includes/classes/class-beanstream-gateway.php' );
        $methods[] = 'Beanstream_Gateway';
        return $methods;
    }
	
	/**
     * Localize Beanstrem error messages
     *
     * @access      protected
     * @param       Exception $e
     * @return      string
     */
    public function get_error_message( $e ) {

        switch ( $e->getMessage() ) {
			
            // Messages from Beanstream
            case 'beanstream_problem_connecting':
            case 'beanstream_empty_response':
            case 'beanstream_invalid_response':
                $message = __( 'There was a problem connecting to the payment gateway.', 'beanstream-for-woocommerce' );
                break;
			case 'DECLINE':
                $message = __( 'There was a problem processing your credit card. Please check your payment information and try again.', 'beanstream-for-woocommerce' );
                break;			
            default:
                $message = __( 'Failed to process the order, please try again later.', 'beanstream-for-woocommerce' );
        }

        return $message;
    }	
}

//init wc_beanstream
$GLOBALS['beanstream_for_wc'] = new BeanStream_For_WC();