<?php

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

    public function __construct()
    {
        $this->id = __(static::GATEWAY_ID, static::WOOCOMMERCE);

        $this->has_fields = true;

        $this->method_title = __('tpay.com credit cards', static::WOOCOMMERCE);
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
        if (!$this->is_valid_for_use()) {
            $this->enabled = 'no';
        }
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
//obliczanie koszyka na nowo jesli jest doplata za tpay.com
        if ((int)$this->doplata !== 0) {
            add_action('woocommerce_cart_calculate_fees', array($this, 'addFeeTpay'), 99);
            add_action('woocommerce_review_order_after_submit', array($this, 'basketReload'));
        }

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
        $this->form_fields = $settingsTpay->getSettings();
    }

    public function is_valid_for_use()
    {
        if ((int)filter_input(INPUT_GET, static::TPAY_ID)) {
            $this->midId = (int)filter_input(INPUT_GET, static::TPAY_ID);
            return true;
        } elseif (filter_input(INPUT_POST, static::ORDER_ID1)) {
            $id = explode('|', filter_input(INPUT_POST, static::ORDER_ID1));
            $this->midId = $id[1];

        } else {
            $counter = 10;
            $validMidId = array();
            $saleCurrency = get_woocommerce_currency();
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
            if ($this->midId === 11) {
                return false;
            } elseif (!empty($midForCurrency) && empty($midPLN)) {
                $this->midId = $midForCurrency;
            }

            try {
                \tpay\Validate::validateCardCurrency($saleCurrency);
                return true;
            } catch (tpay\TException $exception) {
                return false;
            }
        }
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
        include 'addFee.php';
    }

    public function gateway_communication()
    {
        if (filter_input(INPUT_GET, static::ORDER_ID) && (filter_input(INPUT_GET, static::CARDDATA))) {

            $basicClass = new WC_Gateway_Transferuj();
            $transactionData = $basicClass->collectData(filter_input(INPUT_GET, static::ORDER_ID));
            $orderId = filter_input(INPUT_GET, static::ORDER_ID);
            $transactionData[static::ORDERID] = $orderId . '|' . $this->midId;
            $transactionData[static::CARDDATA] = str_replace(' ', '+', filter_input(INPUT_GET, static::CARDDATA));

            $order = new WC_Order($orderId);
            $transactionData[static::CURRENCY] = method_exists($order,'get_currency') ? $order->get_currency() :
                $order->get_order_currency();
            $transactionData['opis'] = $this->get_option('opis' . $this->midId)
                . " Zamówienie nr " . $order->get_order_number();
            $response = $this->processCardSale($transactionData);
            if (isset($response[static::RESULT]) && (int)$response[static::RESULT] === 1) {
                $paymentCard = new tpay\PaymentCard(
                    (string)$this->cardApiKey,
                    (string)$this->cardApiPassword, (string)$this->verificationCode,
                    (string)$this->hashAlg, (string)$this->keyRSA
                );
                $paymentCard->validateSign($response['sign'], $response['sale_auth'], $response['card'],
                    $transactionData['kwota'], $response['date'], 'correct',
                    \tpay\Validate::validateCardCurrency($transactionData[static::CURRENCY]),
                    isset($response['test_mode']) ? '1' : '', '', '');
                $basicClass->completePayment($orderId, static::SUCCESS, false);

                $powUrl = $transactionData['pow_url'];
                wp_redirect(esc_url($powUrl));

            } elseif (isset($response['3ds_url'])) {
                wp_redirect($response['3ds_url']);
            } else {
                $basicClass->completePayment($orderId, static::FAILURE, false);
                $powUrl = $transactionData['pow_url_blad'];
                if ($this->debugMode !== 'no') {
                    var_dump($response);
                } else {
                    wp_redirect(esc_url($powUrl));
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
        if (strcmp(get_locale(), "pl_PL") == 0) {
            tpay\Lang::setLang('pl');
        } else {
            tpay\Lang::setLang('en');
        }
        if ($this->debugMode !== 'no') {
            var_dump($transactionData);
        }
        $paymentCard = new tpay\PaymentCard(
            (string)$this->cardApiKey,
            (string)$this->cardApiPassword, (string)$this->verificationCode,
            (string)$this->hashAlg, (string)$this->keyRSA
        );
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
            get_locale(),
            $transactionData['pow_url'],
            $transactionData['pow_url_blad']
        );
    }

    public function verifyPaymentResponse()
    {
        $paymentCard = new tpay\PaymentCard(
            (string)$this->cardApiKey,
            (string)$this->cardApiPassword, (string)$this->verificationCode,
            (string)$this->hashAlg, (string)$this->keyRSA
        );
        $resp = $paymentCard->handleNotification();
        if (isset($resp[static::ORDER_ID1])) {
            $basicClass = new WC_Gateway_Transferuj();
            $orderId = explode('|', $resp[static::ORDER_ID1]);
            $order = new WC_Order($orderId[0]);
            $orderCurrency = method_exists($order,'get_currency') ? $order->get_currency() : $order->get_order_currency();
            $orderCurrency = tpay\Validate::validateCardCurrency($orderCurrency);
            $paymentCard->validateSign($resp['sign'],
                $resp['sale_auth'], $resp['card'], (double)$order->get_total(),
                $resp['date'], 'correct', $orderCurrency, isset($resp['test_mode']) ? '1' : '', $resp['order_id']);
            $basicClass->completePayment($orderId[0], static::SUCCESS, false);
        }
    }

    public function payment_fields()
    {
        $fee = (int)$this->doplata;
        $feeAmount = (float)$this->kwota_doplaty;
        $currency = get_woocommerce_currency();

        switch ($fee) {
            case 1:
                \tpay\Lang::l('fee_info');
                echo " <b> " . $feeAmount . $currency . " </b><br/><br/>";
                break;
            case 2:
                $kwota = WC()->cart->cart_contents_total + WC()->cart->shipping_total;
                $kwota = $kwota * $feeAmount / 100;
                $kwota = doubleval($kwota);
                $kwota = number_format($kwota, 2);
                \tpay\Lang::l('fee_info');
                echo "<b>" . $kwota . $currency . "</b><br/><br/>";
                break;
            default:
                break;
        }


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
        $this->order = new WC_Order($orderId);
        // Reduce stock levels
        function_exists('wc_reduce_stock_levels') ? wc_reduce_stock_levels($orderId) :
            $this->order->reduce_order_stock();
        // Clear cart
        $woocommerce->cart->empty_cart();
        return array(
            static::RESULT   => static::SUCCESS,
            static::REDIRECT => add_query_arg(array(
                static::CARDDATA    => filter_input(INPUT_POST, static::CARDDATA),
                static::TPAY_ID     => filter_input(INPUT_POST, static::TPAY_ID),
                static::ORDER_ID    => $orderId,
            ), $this->notify_link)
        );

    }
}
