<?php

/**
 * Created by tpay.com.
 * Date: 29.01.2018
 * Time: 11:57
 */
class AddFee
{
    public function addFeeTpay($gatewayId, $fee, $feeAmount)
    {
        if ((WC()->session->chosen_payment_method) == $gatewayId) {

            global $woocommerce;
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
    }

}
