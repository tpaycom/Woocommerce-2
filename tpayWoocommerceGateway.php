<?php

/*
 *
 * tpay.com Woocommerce payment module
 *
 * @author tpay.com
 *
 * Plugin Name: tpay.com Woocommerce payment module
 * Plugin URI: http://www.tpay.com
 * Description: Brama płatności tpay.com do WooCommerce.
 * Author: tpay.com
 * Author URI: http://www.tpay.com
 * Version: 2.5.3
 */
use tpay\Lang;
use tpay\PaymentBasic;
use tpay\TException;
use tpay\TransactionAPI;
use tpay\Util;
use tpay\Validate;

add_action('plugins_loaded', 'initTpayGateway');

function initTpayGateway()
{

    if (!class_exists('WC_Payment_Gateway')) {
        add_action('admin_init', 'childPluginHasParentPlugin');
        function childPluginHasParentPlugin()
        {
            if (is_admin() && current_user_can('activate_plugins')
                && !is_plugin_active('woocommerce/woocommerce.php')
            ) {
                add_action('admin_notices', 'childPluginNotice');

                deactivate_plugins(plugin_basename(__FILE__));

                if (filter_input(INPUT_GET, 'activate')) {
                    unset($_GET['activate']);
                }
            }
        }

        function childPluginNotice()
        {
            echo '
            <div class="error"><p>Moduł płatności tpay.com wymaga zainstalowanej wtyczki Woocommerce, którą można pobrać
                    <a target="blank" href="https://wordpress.org/plugins/woocommerce/">tutaj</a></p></div>';
        }

        return;
    }


    class WC_Gateway_Transferuj extends WC_Payment_Gateway
    {
        const REGULATIONS = 'regulamin';
        const BLIKCODE = 'blikcode';
        const ORDER_ID = 'orderId';
        const KANAL = 'kanal';
        const RESULT = 'result';
        const REDIRECT = 'redirect';
        const SUCCESS = 'success';
        const CAUGHT_EXCEPTION = 'Caught exception: ';
        const TR_CRC = 'tr_crc';
        const TR_ERROR = 'tr_error';
        const KWOTA_DOPLATY = 'kwota_doplaty';
        const BANK_LIST = 'bank_list';
        const DOPLATA = 'doplata';
        const GATEWAY_NAME = 'WC_Gateway_Transferuj';
        const WOOCOMMERCE = 'woocommerce';
        //MUST BE OLD NAME!
        const GATEWAY_ID = 'transferuj';
        const JEZYK = 'jezyk';
        const BANK_VIEW = 'bank_view';
        const FAILURE = 'failure';
        const HTTP = 'http://';
        const HTTPS = 'https://';
        const WC_API = 'wc-api';
        private $pluginUrl;

        public $trId;

        /**
         * Constructor for the gateway.
         *
         * @access public
         *
         *
         * @global type $woocommerce
         */

        public function __construct()
        {
            $this->id = __(static::GATEWAY_ID, static::WOOCOMMERCE);

            $this->has_fields = true;

            $this->method_title = __('tpay.com', static::WOOCOMMERCE);
            if ((isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
                || (is_ssl())
            ) {
                $this->pluginUrl = str_replace(static::HTTP, static::HTTPS, plugins_url('', __FILE__));
                $this->notify_link = str_replace(static::HTTP, static::HTTPS,
                    add_query_arg(static::WC_API, static::GATEWAY_NAME, home_url('/')));
            } else {
                $this->pluginUrl = plugins_url('', __FILE__);
                $this->notify_link = str_replace(static::HTTPS, static::HTTP,
                    add_query_arg(static::WC_API, static::GATEWAY_NAME, home_url('/')));
            }

            $this->icon = apply_filters('woocommerce_transferuj_icon',
                $this->pluginUrl . '/includes/_img/tpayLogo.png');
            // Add tpay.com as payment gateway
            add_filter('woocommerce_payment_gateways', array($this, 'add_transferuj_gateway'));

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();
            if (!$this->is_valid_for_use()) {
                $this->enabled = 'no';
            }
            // Define user set variables
            $this->title = $this->get_option('title');
            $this->opis = $this->get_option('opis');
            $this->kwota_doplaty = $this->get_option(static::KWOTA_DOPLATY);
            $this->description = $this->get_option('description');
            $this->seller_id = $this->get_option('seller_id');
            $this->security_code = $this->get_option('security_code');
            $this->bank_list = $this->get_option(static::BANK_LIST);
            $this->doplata = $this->get_option(static::DOPLATA);
            $this->blik_on = $this->get_option('blik_on');
            $this->api_key = $this->get_option('api_key');
            $this->api_pass = $this->get_option('api_pass');
            $this->proxy_server = (bool)(int)$this->get_option('proxy_server');
            $this->autoFinish = (int)$this->get_option('auto_finish_order');
            if (!empty($this->api_key) && !empty($this->api_pass)){
                $this->supports = array(
                'refunds'
            );
            }
            // Actions
            add_action('woocommerce_update_options_payment_gateways_'
                . $this->id, array($this, 'process_admin_options'));

            //obliczanie koszyka na nowo jesli jest doplata za tpay.com
            if ($this->doplata != 0) {
                add_action('woocommerce_cart_calculate_fees', array($this, 'addFeeTpay'), 99);
                add_action('woocommerce_review_order_after_submit', array($this, 'basketReload'));
            }
            // Payment listener/API hook
            add_action('woocommerce_api_wc_gateway_transferuj', array($this, 'gateway_communication'));
            add_filter('payment_fields', array($this, 'payment_fields'));

            include_once 'includes/lib/src/_class_tpay/Validate.php';
            include_once 'includes/lib/src/_class_tpay/Util.php';
            include_once 'includes/lib/src/_class_tpay/Exception.php';
            include_once 'includes/lib/src/_class_tpay/PaymentBasic.php';
            include_once 'includes/lib/src/_class_tpay/Curl.php';
            include_once 'includes/lib/src/_class_tpay/TransactionApi.php';
            include_once 'includes/lib/src/_class_tpay/Lang.php';

        }

        /**
         * Initialise Gateway Settings Form Fields
         *
         * @access public
         * @return void
         */
        public function init_form_fields()
        {
            include_once 'includes/SettingsTpay.php';
            $charge = $this->get_option(static::DOPLATA);
            $list = $this->get_option('bank_list');
            $tiles = $this->get_option(static::BANK_VIEW);

            $settingsTpay = new SettingsTpay();
            $this->form_fields = $settingsTpay->getSettings($charge, $list, $tiles);

        }

        /**
         * Check if this gateway is enabled and available in the user's country.
         * @return bool
         */
        public function is_valid_for_use()
        {
            return get_woocommerce_currency() === "PLN";
        }

        public function addFeeTpay()
        {
            //dodawanie do zamowienia oplaty za tpay.com
            include 'includes/addFee.php';
        }

        public function basketReload()
        {
            //przeladowanie koszyka zamowienia po wybraniu platnosci tpay.com
            include_once 'includes/_tpl/basketReload.html';
        }

        /**
         * Generates box with gateway name and description, terms acceptance checkbox and channel list
         */
        public function payment_fields()
        {
            strcmp(get_locale(), "pl_PL") == 0 ? Lang::setLang('pl') : Lang::setLang('en');

            if ($this->get_option(static::DOPLATA) == 1) {
                Lang::l('fee_info');
                echo "<b>" . $this->get_option(static::KWOTA_DOPLATY) . " zł</b><br/><br/>";
            }

            if ($this->get_option(static::DOPLATA) == 2) {
                $kwota = WC()->cart->cart_contents_total + WC()->cart->shipping_total;
                $kwota = $kwota * ($this->get_option(static::KWOTA_DOPLATY)) / 100;
                $kwota = doubleval($kwota);
                $kwota = number_format($kwota, 2);
                Lang::l('fee_info');
                echo "<b>" . $kwota . " zł</b><br/><br/>";
            }

            $kwota = WC()->cart->cart_contents_total + WC()->cart->shipping_total;

            $data['merchant_id'] = $this->seller_id;
            $data['show_regulations_checkbox'] = true;
            $data['regulation_url'] = 'https://secure.tpay.com/regulamin.pdf';
            $data['form'] = '';
            echo '<style>';
            include_once 'includes/lib/src/common/_css/style.css';
            echo '</style>';
            echo $this->description;
            if ($this->blikOn() && $kwota >= 1) {
                $data['static_files_url'] = $this->pluginUrl . '/includes/';

                include_once 'includes/_tpl/blikForm.phtml';
                $paymentArray = array(1, 2);
                if (in_array($this->paymentType(), $paymentArray)) {
                    echo '<script type=text/javascript>';
                    include_once 'includes/lib/src/common/_js/showHide.js';
                    echo '</script>';
                }
            }
            echo '<input type="hidden" name="regulamin" id="tpay-regulations-input" value="0">';
            echo '<input type="hidden" id="tpay-payment-submit">';

            if ($this->paymentType() == 1) {

                echo '<input type="hidden" name="kanal" id="tpay-channel-input" value=" ">';

                include_once 'includes/lib/src/common/_tpl/bankSelection.phtml';

            } elseif ($this->paymentType() == 2) {

                echo '<input type="hidden" name="kanal" id="tpay-channel-input" value=" ">';

                include_once 'includes/lib/src/common/_tpl/bankSelectionList.phtml';

            } else {
                include_once 'includes/_tpl/displayNone.phtml';
            }
        }

        public function blikOn()
        {
            return ($this->blik_on == '1');
        }

        public function paymentType()
        {

            if ($this->get_option(static::BANK_LIST) == "0" && $this->get_option(static::BANK_VIEW) == "0") {
                $type = 1;
            } elseif ($this->get_option(static::BANK_LIST) == "0" && $this->get_option(static::BANK_VIEW) == "1") {
                $type = 2;
            } elseif ($this->get_option(static::BANK_LIST) == "1") {
                $type = 3;
            } else {
                $type = 0;
            }
            return $type;
        }

        /**
         * Adds tpay.com payment gateway to the list of installed gateways
         * @param $methods
         * @return array
         */
        public function add_transferuj_gateway($methods)
        {
            $methods[] = static::GATEWAY_NAME;
            return $methods;
        }

        /**
         * Generates admin options
         */
        public function admin_options()
        {
            include_once 'includes/_tpl/settingsAdmin.phtml';
        }

        /**
         * Sends and receives data to/from tpay.com server
         */
        //woocommerce required function

        public function gateway_communication()
        {
            if (filter_input(INPUT_GET, static::ORDER_ID)) {
                $data = $this->collectData(filter_input(INPUT_GET, static::ORDER_ID));
                $this->sendPaymentData($data);

            } else {
                $this->verifyPaymentResponse();
            }

            //exit must be present in this function!
            exit;
        }

        public function collectData($orderId)
        {
            // get order data
            $order = new WC_Order($orderId);
            $wcData = $order->get_address();
            // populate data array to be posted
            $data['kwota'] = $order->get_total();
            $data['opis'] = trim($this->opis . " Zamówienie nr " . $order->get_order_number());
            $data['crc'] = $orderId;
            $data['email'] = $wcData['email'];
            $data['nazwisko'] = $wcData['first_name'] . ' ' . $wcData['last_name'];
            $data['adres'] = $wcData['address_1'] . ' ' . $wcData['address_2'];
            $data['miasto'] = $wcData['city'];
            $data['kraj'] = $wcData['country'];
            $data['kod'] = $wcData['postcode'];
            $data['telefon'] = str_replace(' ', '', $wcData['phone']);
            $data['pow_url'] = $this->get_return_url($order) . '&utm_nooverride=1';
            $data['pow_url_blad'] = is_user_logged_in() ?
                get_permalink(get_option('woocommerce_myaccount_page_id')) . "&orders&utm_nooverride=1"
                : $order->get_cancel_order_url() . '&utm_nooverride=1';
            $data['wyn_url'] = $this->notify_link;
            if (filter_input(INPUT_GET, 'regulamin') === "1") {
                $data['akceptuje_regulamin'] = 1;
            }
            if (strcmp(get_locale(), "pl_PL") == 0) {
                $data[static::JEZYK] = "PL";
            } elseif (strcmp(get_locale(), "de_DE") == 0) {
                $data[static::JEZYK] = "DE";
            } else {
                $data[static::JEZYK] = "EN";
            }

            if (filter_input(INPUT_GET, static::KANAL)) {
                $data[static::KANAL] = (int)filter_input(INPUT_GET, static::KANAL);
            }
            $data['module'] = 'WooCommerce ' . $this->wpbo_get_woo_version_number();
            foreach ($data as $key => $value) {
                if (empty($value)) {
                    unset($data[$key]);
                }
            }

            return $data;
        }

        public function sendPaymentData($data)
        {
            if (filter_input(INPUT_GET, static::BLIKCODE)) {
                $kodblik = filter_input(INPUT_GET, static::BLIKCODE);
                $data2 = $data;
                $data2[static::KANAL] = 64;
                $data2['akceptuje_regulamin'] = 1;
                $transactionAPI = new TransactionAPI(
                    (string)$this->api_key,
                    (string)$this->api_pass,
                    (int)$this->seller_id,
                    (string)$this->security_code
                );
                try {

                    $resp = $transactionAPI->create($data2);
                } catch (TException $exception) {
                    return false;
                }
                $title = (string)$resp['title'];

                try {
                    $resp = $transactionAPI->blik($kodblik, $title);
                } catch (TException $exception) {
                    return false;
                }

                if ($resp['result'] === 1) {
                    $powUrl = $data['pow_url'];
                    wp_redirect(esc_url($powUrl));
                    return true;
                } else {
                    $powUrl = $data['pow_url_blad'];
                    wp_redirect(esc_url($powUrl));
                    return false;
                }
            } else {

                try {
                    $paymentBasic = new PaymentBasic(
                        (int)$this->seller_id,
                        (string)$this->security_code
                    );
                    $form = $paymentBasic->getTransactionForm($data);
                } catch (TException $exception) {
                    return false;
                }
                echo $form;

                return true;
            }
        }

        /**
         * Verifies that no errors have occured during transaction
         */
        public function verifyPaymentResponse()
        {
            try {
                $paymentBasic = new PaymentBasic(
                    (int)$this->seller_id,
                    (string)$this->security_code
                );
                $res = $paymentBasic->checkPayment(Validate::PAYMENT_TYPE_BASIC, $this->proxy_server);
            } catch (TException $exception) {
                return;
            }

            $this->trId = $res['tr_id'];

            if (($res['tr_status'] === 'TRUE')) {
                if ($res[static::TR_ERROR] == 'none') {
                    // transaction successful
                    $this->completePayment($res[static::TR_CRC], static::SUCCESS, false);
                } elseif ($res[static::TR_ERROR] == 'overpay') {
                    // payment was bigger than required
                    $this->completePayment($res[static::TR_CRC], static::SUCCESS, true);
                } else {
                    // transaction failed
                    $this->completePayment($res[static::TR_CRC], static::FAILURE, false);
                }
            } else {
                // transaction failed
                $this->completePayment($res[static::TR_CRC], static::FAILURE, false);
            }

        }

        /**
         * Sets proper transaction status for order based on $status
         * @param $orderId ; id of an order
         * @param $status ; status of a transaction, enum : {success, failure}
         * @param $overpay ; whether there was an overpay during payment
         * @throws ;
         */
        public function completePayment($orderId, $status, $overpay)
        {
            try {
                $order = new WC_Order($orderId);

                if ($status == static::SUCCESS) {
                    if ($overpay) {
                        $order->add_order_note('Zapłacono z nadpłatą.');
                    } else {
                        $order->add_order_note('Zapłacono');
                    }
                    $order->payment_complete($this->trId);
                    if ($this->autoFinish === 1) {
                        $order->update_status('completed');
                    }
                } elseif ($status == static::FAILURE) {
                    $reason = filter_input(INPUT_POST, 'reason') ? filter_input(INPUT_POST, 'reason') : "";
                    $reason .= filter_input(INPUT_POST, 'err_desc') ? filter_input(INPUT_POST, 'err_desc') : "";
                    $order->update_status('failed', __('Zapłata nie powiodła się. ' . $reason));
                } else {
                    throw new TException('Invalid payment type for tpay.com gateway');
                }
            } catch (Exception $exception) {
                Util::log('Exception in completing payment', $exception->getMessage());
                return;
            }

        }


        public function process_payment($orderId)
        {
            global $woocommerce;
            $order = new WC_Order($orderId);
            // Reduce stock levels
            function_exists('wc_reduce_stock_levels') ? wc_reduce_stock_levels($orderId) :
                $order->reduce_order_stock();
            // Clear cart
            $woocommerce->cart->empty_cart();
            // Post data and redirect to tpay.com
            if (!filter_input(INPUT_POST, static::BLIKCODE) || (filter_input(INPUT_POST, static::BLIKCODE) === null)) {
                return array(
                    static::RESULT   => static::SUCCESS,
                    static::REDIRECT => add_query_arg(array(
                        static::REGULATIONS => filter_input(INPUT_POST, static::REGULATIONS),
                        static::ORDER_ID    => $orderId,
                        static::BLIKCODE    => filter_input(INPUT_POST, static::BLIKCODE),
                        static::KANAL       => filter_input(INPUT_POST, static::KANAL),
                    ), $this->notify_link)
                );
            } else {
                return array(
                    static::RESULT   => static::SUCCESS,
                    static::REDIRECT => add_query_arg(array(
                        static::REGULATIONS => filter_input(INPUT_POST, static::REGULATIONS),
                        static::ORDER_ID    => $orderId,
                        static::BLIKCODE    => filter_input(INPUT_POST, static::BLIKCODE),
                    ), $this->notify_link)
                );
            }
        }

        public function process_refund($order_id, $amount = null, $reason = '')
        {
            $order = new WC_Order($order_id);

            $transactionAPI = new TransactionAPI(
                (string)$this->api_key,
                (string)$this->api_pass,
                (int)$this->seller_id,
                (string)$this->security_code
            );

            try {
                $transactionAPI->refundAny($order->get_transaction_id(), $amount);
                return true;
            } catch (Exception $exception) {
                Util::log('Exception in refunding payment', $exception->getMessage());
                return false;
            }

        }

        public function wpbo_get_woo_version_number() {
            // If get_plugins() isn't available, require it
            if ( ! function_exists( 'get_plugins' ) )
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

            // Create the plugins folder and file variables
            $plugin_folder = get_plugins( '/' . 'woocommerce' );
            $plugin_file = 'woocommerce.php';

            // If the plugin version number is set, return it
            if ( isset( $plugin_folder[$plugin_file]['Version'] ) ) {
                return $plugin_folder[$plugin_file]['Version'];

            } else {
                // Otherwise return null
                return NULL;
            }
        }

    }

    new WC_Gateway_Transferuj();
    include_once 'includes/TpayCards.php';
    new WC_Gateway_Tpay_Cards();
}
