<?php
/**
 * Plugin load class.
 *
 * @author   kunlexzy
 * @package  LearnPress/VoguePay
 * @version  1.0.0
 */

defined( 'ABSPATH' ) or die();

if ( ! class_exists( 'LP_Addon_Voguepay' ) ) {
    /**
     * Class LP_Addon_Voguepay.
     */
    class LP_Addon_Voguepay extends LP_Addon {

        public function __construct() {
            parent::__construct();

            add_filter( 'learn-press/payment-methods', array( $this, 'add_payment' ) );
            add_filter( 'learn_press_payment_method', array( $this, 'add_payment' ) );
        }

        /**
         * Add voguepay to payment system.
         *
         * @param $methods
         *
         * @return mixed
         */
        public function add_payment( $methods ) {
            $methods['voguepay'] = 'LP_Gateway_Voguepay';

            return $methods;
        }

        public function plugin_links() {
            $links = array( 'settings' => '<a href="' . admin_url( 'admin.php?page=learn-press-settings&tab=payments&section=voguepay' ) . '">' . __( 'Settings', 'learnpress' ) . '</a>' );

            return $links;
        }

        /**
         * Include core file
         */
        protected function _includes() {
            require_once "class-lp-gateway-voguepay.php";
        }

        public function plugin_url( $file = '' ) {
            return plugins_url( '/' . $file, __FILE__ );
        }

    }
}