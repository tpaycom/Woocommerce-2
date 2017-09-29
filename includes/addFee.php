<?php

/*
 *
 * @author tpay.com
 *
 * Author URI: http://www.tpay.com
 */


if ((WC()->session->chosen_payment_method) == static::GATEWAY_ID) {

    global $woocommerce;
    $fee = $this->doplata;
    $feeAmount = $this->kwota_doplaty;
    switch ($fee) {
        case 1:
            $woocommerce->cart->add_fee('Opłata za płatność online', $feeAmount, true, 'standard');
            break;
        case 2:
            $kwota = $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total;
            $fee = $kwota * $feeAmount / 100;
            $woocommerce->cart->add_fee('Opłata za płatność online', $fee, true, 'standard');
            break;
        default:
            break;
    }
}
