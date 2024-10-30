<?php
/**
 * Plugin Name
 *
 * @package     LearnPressVoguepay
 * @author      kunlexzy
 *
 * @wordpress-plugin
 * Plugin Name: VoguePay Plugin for LearnPress
 * Plugin URI:  https://wordpress.org/plugins/lp-voguepay/
 * Description: Accept credit card payment on LearnPress using voguepay
 * Version:     1.0.0
 * Author:      kunlexzy
 * Author URI:  https://voguepay.com/3445-0056682
 * Text Domain: lp-voguepay
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

defined( 'ABSPATH' ) or die();

define( 'LP_VOGUEPAY_VERSIOM', '1.0.0' );

LP_Addon::load( 'LP_Addon_Voguepay', 'load.php', __FILE__ );
 
