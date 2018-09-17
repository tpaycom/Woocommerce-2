<?php

use tpay\Lang;
use tpay\PaymentCard;
use tpay\TException;
use tpay\Util;
use tpay\Validate;
use tpay\CardAPI;

class WC_Gateway_Tpay_Cards extends WC_Payment_Gateway
{
    const REGULATIONS = 'regulations';
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
    const GATEWAY_NAME = 'WC_Gateway_Tpay_Cards';
    const WOOCOMMERCE = 'woocommerce';
    //MUST BE OLD NAME!
    const GATEWAY_ID = 'tpaycards';
    const JEZYK = 'jezyk';
    const FAILURE = 'failure';
    const HTTP = 'http://';
    const HTTPS = 'https://';
    const WC_API = 'wc-api';
    const CARDDATA = 'carddata';
    const ORDERID = 'orderid';
    const CLIENTNAME = 'client_name';
    const CLIENTEMAIL = 'client_email';
    const HTTP_X_FORWARDED_PROTO = 'HTTP_X_FORWARDED_PROTO';
    const CURRENCY = 'currency';
    const TPAY_ID = 'tpayID';
    const ORDER_ID1 = 'order_id';
    public $midId = 11;
    public $siteDomain;
    private $pluginUrl;
    private $tableName;
    private $trId;
    private $basicClass;

    public function __construct()
    {
        global $wpdb;
        $this->id = __(static::GATEWAY_ID, static::WOOCOMMERCE);
        $this->has_fields = true;
        $this->method_title = __('tpay.com credit cards', static::WOOCOMMERCE);
        $this->basicClass = new WC_Gateway_Transferuj();
        if ((isset($_SERVER[static::HTTP_X_FORWARDED_PROTO]) && $_SERVER[static::HTTP_X_FORWARDED_PROTO] === 'https')
            || (is_ssl())
        ) {
            $this->pluginUrl = str_replace(static::HTTP, static::HTTPS, plugins_url('', __FILE__));
            $this->siteDomain = preg_replace('/\?.*/', '', str_replace(static::HTTP, static::HTTPS, home_url('/')));
        } else {
            $this->pluginUrl = plugins_url('', __FILE__);
            $this->siteDomain = preg_replace('/\?.*/', '', str_replace(static::HTTPS, static::HTTP, home_url('/')));
        }
        $this->notify_link = add_query_arg('wc-api', static::GATEWAY_NAME, $this->siteDomain);
        $this->icon = apply_filters('woocommerce_transferuj_icon',
            $this->pluginUrl . '/_img/tpayLogo.png');
        add_action('woocommerce_update_options_payment_gateways_'
            . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_api_wc_gateway_tpay_cards', array($this, 'gateway_communication'));

        add_filter('payment_fields', array($this, 'payment_fields'));
        add_filter('form', array($this, 'form'));
        add_filter('woocommerce_payment_gateways', array($this, 'add_transferuj_gateway'));

        $this->init_form_fields();
        $this->init_settings();
        $this->shipping_methods = $this->get_option('shipping_methods', array());
        $this->is_available();
        // Define user set variables
        $this->title = $this->get_option('title');
        $this->debugMode = $this->get_option('debugMode');
        $this->domain = $this->get_option('midDomain' . $this->midId);
        $this->opis = $this->get_option('opis' . $this->midId);
        $this->doplata = $this->get_option(static::DOPLATA . $this->midId);
        $this->kwota_doplaty = $this->get_option(static::KWOTA_DOPLATY . $this->midId);
        $this->description = $this->get_option('description' . $this->midId);
        $this->cardApiKey = $this->get_option('cardApiKey' . $this->midId);
        $this->cardApiPassword = $this->get_option('cardApiPassword' . $this->midId);
        $this->verificationCode = $this->get_option('verificationCode' . $this->midId);
        $this->hashAlg = $this->get_option('hashAlg' . $this->midId);
        $this->keyRSA = $this->get_option('keyRSA' . $this->midId);
        $this->midType = $this->get_option('midType' . $this->midId);
        $this->midCurrency = $this->get_option('midCurrency' . $this->midId);
        $this->midOn = $this->get_option('midOn' . $this->midId);
        $this->autoFinish = (int)$this->get_option('auto_finish_order');

//obliczanie koszyka na nowo jesli jest doplata za tpay.com
        if ((int)$this->doplata !== 0) {
            add_action('woocommerce_cart_calculate_fees', array($this, 'addFeeTpay'), 99);
            add_action('woocommerce_review_order_after_submit', array($this, 'basketReload'));
        }
        $this->supports = array(
            'refunds'
        );
        $this->tableName = $wpdb->prefix . "woocommerce_tpay";
        $this->installDatabase();
        $path = dirname(__FILE__);
        include_once $path . '/lib/src/_class_tpay/Lang.php';
        include_once $path . '/lib/src/_class_tpay/CardApi.php';
        include_once $path . '/lib/src/_class_tpay/PaymentCard.php';
        include_once $path . '/lib/src/_class_tpay/Util.php';
        include_once $path . '/lib/src/_class_tpay/Exception.php';
        include_once $path . '/lib/src/_class_tpay/Validate.php';
    }

    public function init_form_fields()
    {
        include_once 'SettingsTpayCards.php';
        $settingsTpay = new SettingsTpayCards();
        $shippingSettings = $this->basicClass->getShippingMethods();
        if (!is_array($shippingSettings)) {
            $shippingSettings = array();
        }
        $this->form_fields = $settingsTpay->getSettings($shippingSettings);
    }

    public function is_available()
    {
        if ((int)filter_input(INPUT_GET, static::TPAY_ID)) {
            $this->midId = (int)filter_input(INPUT_GET, static::TPAY_ID);

            return parent::is_available();
        } elseif (filter_input(INPUT_POST, static::ORDER_ID1)) {
            $id = explode('|', filter_input(INPUT_POST, static::ORDER_ID1));
            if (isset($id[1])) {
                $this->midId = $id[1];
                return parent::is_available();
            }

            return false;
        } elseif (isset(WC()->session) && !is_null(WC()->session)) {
            if ($this->basicClass->isAvailableForShippingMethod($this->shipping_methods) === false) {
                return false;
            }
            $saleCurrency = get_woocommerce_currency();
            $this->getMidForOrder($saleCurrency);
            if ($this->midId === 11) {
                return false;
            }
            try {
                Validate::validateCardCurrency($saleCurrency);

                return parent::is_available();
            } catch (TException $exception) {
                return false;
            }
        } else {
            return parent::is_available();
        }
    }

    private function getMidForOrder($saleCurrency)
    {
        $counter = 10;
        $validMidId = array();

        $midForCurrency = '';
        $midPLN = '';
        for ($i = 1; $i <= $counter; $i++) {
            if ($this->get_option('midDomain' . $i) === $this->siteDomain) {
                $validMidId[] = $i;
            }
        }
        for ($i = 0; $i < count($validMidId); $i++) {
            $midCurrency = explode(',', $this->get_option('midCurrency' . $validMidId[$i]));
            $midType = $this->get_option('midType' . $validMidId[$i]);
            $midOn = $this->get_option('midOn' . $validMidId[$i]);

            if ((int)$midType === 0 && $saleCurrency === 'PLN' && $midOn !== 'no') {
                $this->midId = $validMidId[$i];
                $midPLN = $validMidId[$i];
                break;
            }
            foreach ($midCurrency as $key => $value) {
                if ((strcasecmp($midCurrency[$key], $saleCurrency) === 0
                        || strcasecmp($midCurrency[$key], filter_input(INPUT_POST, static::CURRENCY)) === 0)
                    && $midOn !== 'no' && (int)$midType === 1
                ) {
                    $this->midId = $validMidId[$i];
                    $midForCurrency = $validMidId[$i];

                } elseif ($midCurrency[$key] === '' && $midOn !== 'no') {
                    $this->midId = $validMidId[$i];
                }
            }
        }
        if (!empty($midForCurrency) && empty($midPLN)) {
            $this->midId = $midForCurrency;
        }
    }

    public function installDatabase()
    {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE `" . $this->tableName . "` (
  id mediumint(9) NOT NULL AUTO_INCREMENT,
  wooId mediumint(9) NOT NULL,
  midId mediumint(9) NOT NULL,
  PRIMARY KEY  (id)
) $charset_collate;";
        dbDelta($sql);
    }

    public function admin_options()
    {
        include_once '_tpl/settingsAdminCards.phtml';
    }

    public function basketReload()
    {
        //przeladowanie koszyka zamowienia po wybraniu platnosci tpay.com
        include_once '_tpl/basketReload.html';
    }

    public function addFeeTpay()
    {
        //dodawanie do zamowienia oplaty za tpay.com
        $feeClass = new AddFee();
        $feeClass->addFeeTpay(static::GATEWAY_ID, $this->doplata, $this->kwota_doplaty);
    }

    public function gateway_communication()
    {
        if (filter_input(INPUT_GET, static::ORDER_ID) && (filter_input(INPUT_GET, static::CARDDATA))) {
            $transactionData = $this->basicClass->collectData(filter_input(INPUT_GET, static::ORDER_ID));
            $orderId = filter_input(INPUT_GET, static::ORDER_ID);
            $transactionData[static::ORDERID] = $orderId . '|' . $this->midId;
            $transactionData[static::CARDDATA] = str_replace(' ', '+', filter_input(INPUT_GET, static::CARDDATA));
            $order = new WC_Order($orderId);
            $transactionData[static::CURRENCY] = method_exists($order, 'get_currency') ? $order->get_currency() :
                $order->get_order_currency();
            $transactionData['opis'] = $this->get_option('opis' . $this->midId)
                . " Zamówienie nr " . $order->get_order_number();
            $response = $this->processCardSale($transactionData);
            $this->setTpayOrder($orderId, $this->midId);
            if (isset($response[static::RESULT])
                && (int)$response[static::RESULT] === 1
                && $response['status'] === 'correct') {
                $paymentCard = new PaymentCard(
                    (string)$this->cardApiKey,
                    (string)$this->cardApiPassword, (string)$this->verificationCode,
                    (string)$this->hashAlg, (string)$this->keyRSA
                );
                $paymentCard->validateSign($response['sign'], $response['sale_auth'], $response['card'],
                    $transactionData['kwota'], $response['date'], 'correct',
                    Validate::validateCardCurrency($transactionData[static::CURRENCY]),
                    isset($response['test_mode']) ? '1' : '', '', '');
                $this->completePayment($orderId, $response);
                $powUrl = $transactionData['pow_url'];
                header("Location: " . $powUrl);
            } elseif (isset($response['3ds_url'])) {
                wp_redirect($response['3ds_url']);
            } else {
                $this->completePayment($orderId, $response);
                $powUrl = $transactionData['pow_url_blad'];
                if ($this->debugMode !== 'no') {
                    var_dump($response);
                } else {
                    header("Location: " . $powUrl);
                }
            }
        } elseif (filter_input(INPUT_GET, 'type') === 'sale') {
            $this->verifyPaymentResponse();
        } else {
            echo 'INVALID DATA';
        }
        //exit must be present in this function!
        exit;
    }

    public function processCardSale($transactionData)
    {
        if ($transactionData['jezyk'] === 'PL') {
            Lang::setLang('pl');
        } else {
            Lang::setLang('en');
        }
        if ($this->debugMode !== 'no') {
            var_dump($transactionData);
        }
        $paymentCard = new PaymentCard(
            $this->cardApiKey, $this->cardApiPassword, $this->verificationCode, $this->hashAlg, $this->keyRSA);
        $_POST[static::CARDDATA] = $transactionData[static::CARDDATA];
        $_POST['client_name'] = $transactionData['nazwisko'];
        $_POST['client_email'] = $transactionData['email'];
        $_POST['card_save'] = 'no';

        return $paymentCard->secureSale(
            $transactionData['kwota'],
            $transactionData[static::ORDERID],
            $transactionData['opis'],
            $transactionData[static::CURRENCY],
            true,
            $transactionData['jezyk'],
            $transactionData['pow_url'],
            $transactionData['pow_url_blad'],
            $transactionData['module']
        );
    }

    private function setTpayOrder($orderId, $midId)
    {
        $orderId = (int)$orderId;
        $midId = (int)$midId;
        $sql = "INSERT INTO $this->tableName SET wooId = $orderId, midId = $midId";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function verifyPaymentResponse()
    {
        $paymentCard = new PaymentCard(
            (string)$this->cardApiKey,
            (string)$this->cardApiPassword, (string)$this->verificationCode,
            (string)$this->hashAlg, (string)$this->keyRSA
        );
        $resp = $paymentCard->handleNotification();
        if (isset($resp[static::ORDER_ID1])) {
            $orderId = explode('|', $resp[static::ORDER_ID1]);
            $order = new WC_Order($orderId[0]);
            $orderCurrency = method_exists($order,
                'get_currency') ? $order->get_currency() : $order->get_order_currency();
            $orderCurrency = Validate::validateCardCurrency($orderCurrency);
            $paymentCard->validateSign($resp['sign'], $resp['sale_auth'], $resp['card'], (double)$order->get_total(),
                $resp['date'], 'correct', $orderCurrency, isset($resp['test_mode']) ? '1' : '', $resp['order_id'],
                $resp['type'], isset($resp['sale_ref']) ? $resp['sale_ref'] : '');
            $this->trId = $resp['sale_auth'];
            $this->completePayment($orderId[0], $resp);
        }
    }

    private function completePayment($orderId, $notification)
    {
        try {
            $order = new WC_Order($orderId);
            if (isset($notification['status']) && $notification['status'] === 'correct') {
                $order->add_order_note(__('Zapłacono.'));
                $order->payment_complete($this->trId);
                if ($this->autoFinish === 1) {
                    $order->update_status('completed');
                }

                return true;
            }
            $reason = '';
            $reason .= isset($notification['reason']) ? $notification['reason'] . ' ' : '';
            $reason .= isset($notification['err_desc']) ? $notification['err_desc'] : '';
            if ($reason !== '') {
                $order->update_status('failed', __('Zapłata nie powiodła się. ' . $reason));

                return true;
            }
            if (isset($notification['type']) && $notification['type'] === 'refund') {
                $order->update_status('refunded', 'Status zamówienia zmieniony na zwrócone.', true);

                return true;
            } else {
                throw new TException('Invalid payment type for tpay.com cards gateway');
            }
        } catch (Exception $exception) {
            Util::log('Exception in completing payment', $exception->getMessage() . print_r($notification, true));

            return false;
        }
    }

    public function payment_fields()
    {
        strcmp(get_locale(), "pl_PL") == 0 ? Lang::setLang('pl') : Lang::setLang('en');
        $data['regulation_url'] = 'https://secure.tpay.com/regulamin.pdf';
        include_once "_tpl/cardForm.phtml";
    }

    public function add_transferuj_gateway($methods)
    {
        $methods[] = static::GATEWAY_NAME;

        return $methods;
    }

    public function process_payment($orderId)
    {
        if (empty($_POST[static::CARDDATA])) {
            return false;
        }
        global $woocommerce;
        $woocommerce->cart->empty_cart();

        return array(
            static::RESULT => static::SUCCESS,
            static::REDIRECT => add_query_arg(array(
                static::CARDDATA => filter_input(INPUT_POST, static::CARDDATA),
                static::TPAY_ID => filter_input(INPUT_POST, static::TPAY_ID),
                static::ORDER_ID => $orderId,
            ), $this->notify_link)
        );

    }

    public function process_refund($order_id, $amount = null, $reason = '')
    {
        $order = new WC_Order($order_id);
        $midId = $this->getOrderMidId($order->get_id());

        $paymentCard = new CardApi(
            $this->get_option('cardApiKey' . $midId),
            $this->get_option('cardApiPassword' . $midId),
            $this->get_option('verificationCode' . $midId),
            $this->get_option('hashAlg' . $midId)
        );
        $currency = Validate::validateCardCurrency(method_exists($order, 'get_currency') ?
            $order->get_currency() : $order->get_order_currency());
        try {
            $paymentCard->refund('', $order->get_transaction_id(), $reason, $amount, $currency);

            return true;
        } catch (Exception $exception) {
            Util::log('Exception in refunding card payment', $exception->getMessage());

            return false;
        }

    }

    private function getOrderMidId($orderId)
    {
        global $wpdb;
        $orderId = (int)$orderId;
        $sql = "SELECT midId FROM $this->tableName WHERE wooId = $orderId";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $result = $wpdb->get_results($sql);

        return $result[0]->midId;
    }

}
