<?php

/**
 * Created by tpay.com.
 * Date: 29.01.2018
 * Time: 11:57
 */
class AddFee
{
    const WOOCOMMERCE = 'woocommerce';

    public function addFeeTpay($gatewayId, $fee, $feeAmount)
    {
        if ((WC()->session->chosen_payment_method) == $gatewayId) {
            global $woocommerce;
            switch ($fee) {
                case 1:
                    $woocommerce->cart->add_fee(
                        __('Opłata za płatność online', static::WOOCOMMERCE),
                        $feeAmount,
                        true,
                        'standard'
                    );
                    break;
                case 2:
                    $kwota = $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total;
                    $fee = $kwota * $feeAmount / 100;
                    $woocommerce->cart->add_fee(
                        __('Opłata za płatność online', static::WOOCOMMERCE),
                        $fee,
                        true,
                        'standard'
                    );
                    break;
                default:
                    break;
            }
        }
    }

}
