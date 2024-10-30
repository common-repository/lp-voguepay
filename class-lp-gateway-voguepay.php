<?php
/**
 * Plugin load class.
 *
 * @author   kunlexzy
 * @package  LearnPress/VoguePay
 * @version  1.0.0
 */

defined( 'ABSPATH' ) or exit;

if ( ! function_exists( 'LP_Gateway_Voguepay' ) ) {
    /**
     * Class LP_Gateway_Voguepay.
     */
    class LP_Gateway_Voguepay extends LP_Gateway_Abstract {

        /**
         * @var LP_Settings
         */
        public $settings;


        /**
         * @var string
         */
        public $id = 'voguepay';

        /**
         * Constructor for the gateway.
         */
        public function __construct() {

            parent::__construct();

            $this->url='https://voguepay.com/';
            $this->icon               = $this->settings->get( 'icon', LP_Addon_Voguepay::instance()->plugin_url( 'assets/voguepay_icon.png' ) );
            $this->method_title       = __( 'VoguePay', 'learnpress' );
            $this->method_description = __( 'Make payment via voguepay', 'learnpress' );

            // Get settings
            $this->title        = $this->settings->get( 'title', $this->method_title );
            $this->description  = $this->settings->get( 'description', $this->method_description );


            add_filter( 'learn-press/payment-gateway/' . $this->id . '/available', array(
                $this,
                'is_available'
            ) );

            $this->init();
        }

        public function init() {

            if(!$this->is_available()) return;

            if ( did_action( 'init' ) ) {
                $this->register_web_hook();
             } else {
                add_action( 'init', array( $this, 'register_web_hook' ) );
             }

            add_action( 'learn_press_web_hook_learn_press_voguepay', array( $this, 'web_hook_process_voguepay' ) );

        }


        public function register_web_hook() {
            learn_press_register_web_hook( 'voguepay', 'learn_press_voguepay' );
        }

        public function web_hook_process_voguepay( $request ) {

            if(isset($request['transaction_id']))
            {
                  //check transaction status from voguepay
                  $transaction_id=sanitize_text_field($request['transaction_id']);
                  if(empty($transaction_id)) return;

                    $args = array( 'timeout' => 60 );

                    if( $this->get_identifier()=='demo' ) {

                        $json = wp_remote_get( $this->url .'?v_transaction_id='.$transaction_id.'&type=json&demo=true', $args );

                    } else {

                        $json = wp_remote_get( $this->url .'?v_transaction_id='.$transaction_id.'&type=json', $args );

                    }

                    $transaction 	= json_decode( $json['body'], true );

                    foreach($transaction as $key =>$val) $transaction[$key]=sanitize_text_field($val);

                    $transaction_id = $transaction['transaction_id'];
                    $ref_split 		= explode('-', $transaction['merchant_ref'] );

                    $order_id 		= (int) $ref_split[0];
                    $order 			= new LP_Order( $order_id );
                    $order_total	= $order->get_total();
                    $amount_paid_currency 	= $ref_split[1];
                    $amount_paid 	= $ref_split[2];

                    if( $transaction['status'] == 'Approved' ) {

                    if( $transaction['merchant_id'] != $this->get_identifier() ) {

                        //Update the order status
                        $order->update_status('cancelled',__('Payment made to another account, this requires investigation','learnpress'));

                        update_post_meta( $order->get_id(), 'Result',__('Payment made to another account, this requires investigation','learnpress').' - '.$transaction_id );

                        echo 'Merchant ID mis-match';

                    } else {

                        // check if the amount paid is equal to the order amount.
                        if( $amount_paid < $order_total ) {

                            //Update the order status
                            $order->update_status('cancelled',__('Amount paid do not match order amount, this requires investigation','learnpress'));

                            update_post_meta( $order->get_id(),'Result',__('Amount paid do not match order amount, this requires investigation','learnpress').' - '.$transaction_id );


                            echo 'Total amount mis-match';

                        } else {

                            //Update the order status
                            $order->update_status('completed',__($transaction['response_message'],'learnpress'));

                            update_post_meta( $order->get_id(),'Result',__($transaction['response_message'].' - '.$transaction_id,'learnpress') );

                            $order->payment_complete($transaction_id);

                            echo 'OK';
                        }
                    }



                } else {

                        //Update the order status
                        $order->update_status('failed',__($transaction['response_message'],'learnpress'));

                        update_post_meta( $order->get_id(),'Result',__($transaction['response_message'].' - '.$transaction_id,'learnpress') );


                        echo 'OK';
                    }


            }
       }

        /**
         * Check if gateway is enabled.
         *
         * @return bool
         */
        public function is_available() {
            if ( LP()->settings->get( "{$this->id}.enable" ) != 'yes' ) {
                return false;
            }

            if( empty(trim($this->get_identifier())) ) return false;

            return true;
        }

        /**
         * Output for the order received page.
         *
         * @param $order
         */

        protected function _get( $name ) {
            return LP()->settings->get( $this->id . '.' . $name );
        }

        /**
         * Admin payment settings.
         *
         * @return array
         */
        public function get_settings() {

            return apply_filters( 'learn-press/gateway-payment/voguepay/settings',
                array(
                    array(
                        'title'   => __( 'Enable', 'learnpress' ),
                        'id'      => '[enable]',
                        'default' => 'no',
                        'type'    => 'yes-no'
                    ),
                    array(
                        'title'   => __( 'Test Mode', 'learnpress' ),
                        'id'      => '[demo]',
                        'default' => 'no',
                        'type'    => 'yes-no'
                    ),
                    array(
                        'title'      => __( 'Merchant ID', 'learnpress' ),
                        'id'         => '[merchant_id]',
                        'type'       => 'text',
                        'visibility' => array(
                            'state'       => 'show',
                            'conditional' => array(
                                array(
                                    'field'   => '[enable]',
                                    'compare' => '=',
                                    'value'   => 'yes'
                                ),
                                array(
                                    'field'   => '[demo]',
                                    'compare' => '!=',
                                    'value'   => 'yes'
                                )
                            )
                        )
                    ),
                    array(
                        'title'      => __( 'Store ID (Optional)', 'learnpress' ),
                        'id'         => '[store]',
                        'type'       => 'text',
                        'visibility' => array(
                            'state'       => 'show',
                            'conditional' => array(
                                array(
                                    'field'   => '[enable]',
                                    'compare' => '=',
                                    'value'   => 'yes'
                                )
                            )
                        )
                    ),
                    array(
                        'title'      => __( 'Description', 'learnpress' ),
                        'id'         => '[description]',
                        'default'    => $this->description,
                        'type'       => 'textarea',
                        'editor'     => array( 'textarea_rows' => 5 ),
                        'visibility' => array(
                            'state'       => 'show',
                            'conditional' => array(
                                array(
                                    'field'   => '[enable]',
                                    'compare' => '=',
                                    'value'   => 'yes'
                                )
                            )
                        )
                    )




                )
            );
        }

        /**
         * Payment form.
         */
        public function get_payment_form() {
            return LP()->settings->get( $this->id . '.description' );
        }

        /**
         * Process the payment and return the result
         *
         * @param $order_id
         *
         * @return array
         * @throws Exception
         */
        public function process_payment( $order_id ) {

            $redirect = $this->get_request_url( $order_id );

            $json = array(
                'result'   => $redirect ? 'success' : 'fail',
                'redirect' => $redirect
            );

            return $json;
        }

        public function get_request_url( $order_id ) {

            $order = new LP_Order( $order_id );
            $checkout = LP()->checkout();

            $order_id = $order->get_id();
            $voguepay_args = array(
                'v_merchant_id' 		=> $this->get_identifier(),
                'cur' 					=> learn_press_get_currency(),
                'memo'					=> "Payment for Order - $order_id on ". get_bloginfo('name'),
                'email'                 => $checkout->get_checkout_email(),
                'total' 				=> $order->get_total(),
                'merchant_ref'			=> $order_id.'-'.learn_press_get_currency().'-'.$order->get_total(),
                'notify_url'			=> get_home_url(). '/?' . learn_press_get_web_hook( 'voguepay' ) . '=1',
                'success_url'			=> esc_url( $this->get_return_url( $order ) ),
                'fail_url'				=> esc_url( learn_press_is_enable_cart() ? learn_press_get_page_link( 'cart' ) : get_home_url())
            );

            if(!empty($this->settings->get( 'store' ))) $voguepay_args['store_id']=$this->settings->get( 'store' );
            $voguepay_args['developer_code']='5b75c24e5c518';
            $payment_url = $this->url.'?p=linkToken&' . http_build_query( $voguepay_args );

            $args = array(
                'timeout'   => 60
            );

            $request = wp_remote_get( $payment_url, $args );

            $valid_url = strpos( $request['body'], $this->url.'pay' );

            if ( ! is_wp_error( $request ) && 200 == wp_remote_retrieve_response_code( $request ) && $valid_url !== false ) return $request['body'];

            return null;
        }


        function get_identifier()
        {
            return ($this->settings->get( 'demo' )=='yes')? 'demo' : $this->settings->get( 'merchant_id' );
        }


    }
}
