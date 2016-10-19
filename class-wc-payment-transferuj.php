<?php
/**
  /**
 * Transferuj.pl Woocommerce payment module
 *
 * @author Transferuj.pl
 *
 * Plugin Name: Transferuj.pl Woocommerce payment module
 * Plugin URI: http://www.transferuj.pl
 * Description: Brama płatności Transferuj.pl do WooCommerce.
 * Author: Transferuj.pl
 * Author URI: http://www.transferuj.pl
 * Version: 0.3	
 */
// load plugin
add_action('plugins_loaded', 'init_transferuj_gateway');

function init_transferuj_gateway() {

   class WC_Gateway_Transferuj extends WC_Payment_Gateway {

      /**
       * Constructor for the gateway.
       *
       * @access public
       */
      public function __construct() {
         global $woocommerce;

         $this->id = __('transferuj', 'woocommerce');
         $this->icon = apply_filters('woocommerce_transferuj_icon', plugins_url('images/logo-transferuj-50x25.png', __FILE__));
         $this->has_fields = true;

         $this->method_title = __('Transferuj.pl', 'woocommerce');
         $this->notify_link = str_replace('https:', 'http:', add_query_arg('wc-api', 'WC_Gateway_Transferuj', home_url('/')));

         // Add Transferuj.pl as payment gateway
         add_filter('woocommerce_payment_gateways', array($this, 'add_transferuj_gateway'));

         // Load the settings.
         $this->init_form_fields();
         $this->init_settings();

         // Define user set variables
         $this->title = $this->get_option('title');
         $this->description = $this->get_option('description');
         $this->seller_id = $this->get_option('seller_id');
         $this->security_code = $this->get_option('security_code');


         // Actions
         add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

         // Payment listener/API hook
         add_action('woocommerce_api_wc_gateway_transferuj', array($this, 'gateway_communication'));

         add_filter('payment_fields', array($this, 'payment_fields'));
      }

      /**
       * Generates box with gateway name and description, terms acceptance checkbox and channel list
       */
      function payment_fields() {
         if ($this->get_option('bank_list') == "no") {
            echo <<<FORM
			
            <div id="descriptionBox">{$this->description}</div> <br/>
            <div id="termsCheckboxBox">
                <input type="checkbox" id="termsCheckbox" name="terms">
                    <a href="https://transferuj.pl/regulamin.pdf" target="blank">
                                Akceptuje warunki regulaminu korzystania z serwisu Transferuj.pl
                    </a>
                </input> <br/>
            </div>
            

FORM;
            return;
         }
         // get script with channels from Transferuj.pl
         $channels_url = "https://secure.transferuj.pl/channels-" . $this->seller_id . "0.js";
         $JSON = file_get_contents($channels_url);

         // parse the channel list
         $pattern = "!\['(?<id>\d{1,2})','(?<name>.+)','(.+)','(.+)','!";
         preg_match_all($pattern, $JSON, $matches);

         // create list of channels
         $channels = '<select class="channelSelect" id ="channelSelect" name="channel">';
         for ($i = 0; $i < count($matches['id']); $i++) {
            $channels .= '<option value="' . $matches['id'][$i] . '">' .
                    $matches['name'][$i] . "</option>";
         }
         $channels .= '</select>';

         // generate box
         echo <<<FORM
			
            <div id="descriptionBox">{$this->description}</div> <br/>
            <div id="termsCheckboxBox">
                <input type="checkbox" id="termsCheckbox" name="terms">
                    <a href="https://transferuj.pl/regulamin.pdf" target="blank">
                                Akceptuje warunki regulaminu korzystania z serwisu Transferuj.pl
                    </a>
                </input> <br/>
            </div>
            <div id="channelSelectBox">{$channels}</div>

FORM;
      }

      /**
       * Adds Transferuj.pl payment gateway to the list of installed gateways
       * @param $methods
       * @return array
       */
      function add_transferuj_gateway($methods) {
         $methods[] = 'WC_Gateway_Transferuj';
         return $methods;
      }

      /**
       * Generates admin options
       */
      function admin_options() {
         ?>
         <h2><?php _e('Transferuj.pl', 'woocommerce'); ?></h2>
         <table class="form-table">
         <?php $this->generate_settings_html(); ?>
         </table> <?php
      }

      /**
       * Initialise Gateway Settings Form Fields
       *
       * @access public
       * @return void
       */
      function init_form_fields() {
         $this->form_fields = array(
             'enabled' => array(
                 'title' => __('Włącz/Wyłącz', 'woocommerce'),
                 'type' => 'checkbox',
                 'label' => __('Włącz metodę płatności przez Transferuj.pl.', 'woocommerce'),
                 'default' => 'yes'
             ),
             'title' => array(
                 'title' => __('Nazwa', 'woocommerce'),
                 'type' => 'text',
                 'default' => __('Transferuj.pl', 'woocommerce'),
                 'desc_tip' => true,
             ),
             'description' => array(
                 'title' => __('Opis', 'woocommerce'),
                 'type' => 'textarea',
                 'description' => __('Ustawia opis bramki, który widzi użytkownik przy tworzeniu zamówienia.', 'woocommerce'),
                 'default' => __('Zapłać przez Transferuj.pl.', 'woocommerce')
             ),
             'seller_id' => array(
                 'title' => __('ID sprzedawcy', 'woocommerce'),
                 'type' => 'text',
                 'description' => __('Twoje ID sprzedawcy w systemie Transferuj.pl. Liczba co najmniej czterocyfrowa (może być pięciocyfowa), np. 12345', 'woocommerce'),
                 'default' => __('0', 'woocommerce'),
                 'desc_tip' => true,
             ),
             'security_code' => array(
                 'title' => __('Kod bezpieczeństwa', 'woocommerce'),
                 'type' => 'text',
                 'description' => __('Kod bezpieczeństwa Twojego konta na Transferuj.pl.', 'woocommerce'),
                 'default' => __('0', 'woocommerce'),
                 'desc_tip' => true,
             ),
             'bank_list' => array(
                 'title' => __('Włącz/Wyłącz', 'woocommerce'),
                 'type' => 'checkbox',
                 'label' => __('Włącz wybór banku w zamówieniu', 'woocommerce'),
                 'default' => 'yes'
             ),
         );
      }

      /**
       * Sends and receives data to/from Transferuj.pl server
       */
      function gateway_communication() {
		  $ip_table=array(
		'195.149.229.109',
		'148.251.96.163',
		'178.32.201.77',
		'46.248.167.59',
		'46.29.19.106'
		);
         if (in_array($_SERVER['REMOTE_ADDR'], $ip_table) && (!empty($_POST))) {
            $this->verify_payment_response();
         } else if (isset($_GET['order_id'])) {
            $this->send_payment_data($_GET['order_id']);
         }
         exit;
      }

      /**
       * Verifies that no errors have occured during transaction
       */
      function verify_payment_response() {
         $data['order_id'] = base64_decode($_POST['tr_crc']);
         $data['seller_id'] = $_POST['id'];
         $data['transaction_status'] = $_POST['tr_status'];
         $data['transaction_id'] = $_POST['tr_id'];
         $data['total'] = $_POST['tr_amount'];
         $data['error'] = $_POST['tr_error'];
         $data['crc'] = $_POST['tr_crc'];
         $data['checksum'] = $_POST['md5sum'];

         $data['local_checksum'] = md5($data['seller_id'] . $data['transaction_id'] . $data['total'] . $data['crc'] . $this->security_code);

         if (strcmp($data['checksum'], $data['local_checksum']) == 0) {
            if (($data['transaction_status'] == 'TRUE')) {
               if ($data['error'] == 'none') {
                  // transaction successful
                  $this->complete_payment($data['order_id'], 'success', false);
               } else if ($data['error'] == 'overpay') {
                  // payment was bigger than required
                  $this->complete_payment($data['order_id'], 'success', true);
               }
            } else {
               // transaction failed
               $this->complete_payment($data['order_id'], 'failure', false);
            }
         }

         echo 'TRUE'; // data has been received; response for server
      }

      /**
       * Sets proper transaction status for order based on $status
       * @param $order_id ; id of an order
       * @param $status ; status of a transaction, enum : {success, failure}
       * @param $overpay ; whether there was an overpay during payment
       */
      function complete_payment($order_id, $status, $overpay) {
         $order = new WC_Order($order_id);
         if ($status == 'success') {
            if ($overpay) {
               $order->update_status('processing', __('Zapłacono z nadpłatą.'));
            } else {
               $order->update_status('processing', __('Zapłacono.'));
            }
         } else if ($status == 'failure') {
            $order->update_status('failed', __('Zapłata nie powiodła się.'));
         }
      }

      /**
       * Processes payment for the order - see WC_Payment_Gateway
       * @param $order_id ; id of an order
       * @return array
       */
      function process_payment($order_id) {
         global $woocommerce;
         $order = new WC_Order($order_id);
         // Mark as on-hold (we will be awaiting the Transferuj.pl payment)
         $order->update_status('on-hold', __('Awaiting Transferuj.pl payment', 'woocommerce'));
         // Reduce stock levels
         $order->reduce_order_stock();
         // Clear cart
         $woocommerce->cart->empty_cart();
         // Post data and redirect to Transferuj.pl
         return array(
             'result' => 'success',
             'redirect' => add_query_arg(array('terms' => $_POST['terms'], 'order_id' => $order_id, 'channel' => $_POST['channel']), $this->notify_link)
         );
      }

      function validate_fields() {
         if (get_woocommerce_currency() == "PLN")
            return true;
         else {
            echo("Bramka umożliwia tylko płatność w PLN");
            exit;
         }
      }

      /**
       * Handles sending data to the server via post method
       * @param $order_id ; id of an order
       */
      function send_payment_data($order_id) {
         global $wp;
         // get order data
         $order = new WC_Order($order_id);

         $url = "https://secure.transferuj.pl/";

         // populate data array to be posted
         $data['seller_id'] = $this->seller_id;
         $data['security_code'] = $this->security_code;
         $data['kwota'] = $order->get_total();
         $data['opis'] = "Transakcja " . $order_id;
         $data['crc'] = base64_encode($order_id);

         $data['email'] = $order->billing_email;
         $data['nazwisko'] = $order->billing_first_name . ' ' . $order->billing_last_name;
         $data['adres'] = $order->billing_address_1 . ' ' . $order->billing_address_2;
         $data['miasto'] = $order->billing_city;
         $data['kraj'] = $order->billing_country;
         $data['kod'] = $order->billing_postcode;
         $data['telefon'] = $order->billing_phone;
         $data['md5sum'] = md5($data['seller_id'] . $data['kwota'] . $data['crc'] . $data['security_code']);
         $data['pow_url'] = add_query_arg('key', $order->order_key, add_query_arg('order', $order->id, get_permalink(woocommerce_get_page_id('thanks'))));
         $data['pow_url_blad'] = add_query_arg('key', $order->order_key, add_query_arg('order', $order->id, get_permalink(woocommerce_get_page_id('thanks'))));
         $data['wyn_url'] = $this->notify_link;

         if (strcmp(get_locale(), "pl_PL") == 0) {
            $data['jezyk'] = "pl";
         } else if (strcmp(get_locale(), "de_DE") == 0) {
            $data['jezyk'] = "de";
         } else {
            $data['jezyk'] = "en";
         }

         if (isset($_GET['channel'])) {
            $data['kanal'] = $_GET['channel'];
         }
         // else {
         ///    $data['kanal'] = 1;
         // }

         $termsInput = "";
         if (isset($_GET['terms'])) {
            $data['terms'] = $_GET['terms'];
            if ($data['terms'] == 'on') {
               $termsInput = '<input type="hidden" name="akceptuje_regulamin" value="' . $data['terms'] . '"/>';
            }
         }

         $form = <<<FORM

            <form action="{$url}" method="post" id="tr_payment" name="tr_payment">
                <input type="hidden" name="id" value="{$data['seller_id']}"/>
                <input type="hidden" name="kwota" value="{$data['kwota']}"/>
                <input type="hidden" name="opis" value="{$data['opis']}"/>
                <input type="hidden" name="crc" value="{$data['crc']}"/>
                <input type="hidden" name="email" value="{$data['email']}"/>
                <input type="hidden" name="nazwisko" value="{$data['nazwisko']}"/>
                <input type="hidden" name="adres" value="{$data['adres']}"/>
                <input type="hidden" name="miasto" value="{$data['miasto']}"/>
                <input type="hidden" name="kraj" value="{$data['kraj']}"/>
                <input type="hidden" name="kod" value="{$data['kod']}"/>
                <input type="hidden" name="telefon" value="{$data['telefon']}"/>
                <input type="hidden" name="md5sum" value="{$data['md5sum']}"/>
                <input type="hidden" name="pow_url" value="{$data['pow_url']}"/>
                <input type="hidden" name="pow_url_blad" value="{$data['pow_url_blad']}"/>
                <input type="hidden" name="wyn_url" value="{$data['wyn_url']}"/>
                <input type="hidden" name="kanal" value="{$data['kanal']}"/>
                <input type="hidden" name="jezyk" value="{$data['jezyk']}"/>
                {$termsInput}
            </form>
            <script type="text/javascript">
                // post data to server
                document.getElementById('tr_payment').submit();
            </script>
FORM;
         echo $form;
         die();
      }

   }

   new WC_Gateway_Transferuj();
}