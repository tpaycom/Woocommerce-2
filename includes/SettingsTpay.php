<?php

/*
 * Created by PhpStorm.
 * User: user
 * Date: 25.01.2017
 * Time: 13:12
 */

class SettingsTpay
{
    const VISIBILITY_VISIBLE = 'visibility: visible';

    const VISIBILITY_HIDDEN = 'visibility: hidden';

    const TITLE = 'title';

    const TYPE = 'type';

    const DEFAULT_SETTING = 'default';

    const DESCRIPTION = 'description';

    const WOOCOMMERCE = 'woocommerce';

    const SELECT = 'select';

    const OPTIONS = 'options';

    const DESC_TIP = 'desc_tip';

    public function getSettings($charge, $list, $tiles)
    {
        $ukryjD = static::VISIBILITY_VISIBLE;
        $ukryjK = static::VISIBILITY_VISIBLE;
        $ukryjS = static::VISIBILITY_VISIBLE;

        if ($charge == '0') {
            $ukryjD = static::VISIBILITY_HIDDEN;
        }
        if ($list == '1') {
            $ukryjK = static::VISIBILITY_HIDDEN;
        }
        if ($tiles == '1') {
            $ukryjS = static::VISIBILITY_HIDDEN;
        }

        return array(
            'enabled' => array(
                static::TITLE           => __('Włącz/Wyłącz', static::WOOCOMMERCE),
                static::TYPE            => 'checkbox',
                'label'                 => __('Włącz metodę płatności przez tpay.com.', static::WOOCOMMERCE),
                static::DEFAULT_SETTING => 'yes',
                static::DESCRIPTION     => sprintf(__(' <a href="%s" TARGET="_blank">Zarejestruj konto w systemie
  tpay.com</a>.', static::WOOCOMMERCE), 'https://secure.tpay.com/panel/rejestracja.html'),
            ),

            static::TITLE       => array(
                static::TITLE           => __('Nazwa', static::WOOCOMMERCE),
                static::TYPE            => 'text',
                static::DEFAULT_SETTING => __('tpay.com', static::WOOCOMMERCE),
                static::DESC_TIP        => true,
            ),
            static::DESCRIPTION => array(
                static::TITLE           => __('Opis', static::WOOCOMMERCE),
                static::TYPE            => 'textarea',
                static::DESCRIPTION     => __('Ustawia opis bramki, który widzi użytkownik przy tworzeniu zamówienia.'
                    , static::WOOCOMMERCE),
                static::DEFAULT_SETTING => __('System płatności tpay.com to bezpieczny i szybki sposób płatności,
                 który został wybrany przez Odbiorcę płatności w celu przyjęcia od Ciebie zapłaty.'
                    , static::WOOCOMMERCE)
            ),
            'opis'              => array(
                static::TITLE           => __('Tytuł transakcji', static::WOOCOMMERCE),
                static::TYPE            => 'text',
                static::DESCRIPTION     => __('Ustawia opis transakcji, do którego zostanie autoamtycznie
                 dodane "Zamówienie nr (ID)".'
                    , static::WOOCOMMERCE),
                static::DEFAULT_SETTING => __(''
                    , static::WOOCOMMERCE)
            ),
            'seller_id'         => array(
                static::TITLE           => __('ID sprzedawcy', static::WOOCOMMERCE),
                static::TYPE            => 'text',
                static::DESCRIPTION     => __('Twoje ID sprzedawcy w systemie tpay.com.
                 Liczba co najmniej czterocyfrowa (może być pięciocyfowa), np. 12345', static::WOOCOMMERCE),
                static::DEFAULT_SETTING => __('0', static::WOOCOMMERCE),
                static::DESC_TIP        => true,
            ),
            'security_code'     => array(
                static::TITLE           => __('Kod bezpieczeństwa', static::WOOCOMMERCE),
                static::TYPE            => 'text',
                static::DESCRIPTION     => __('Kod bezpieczeństwa Twojego konta na tpay.com.', static::WOOCOMMERCE),
                static::DEFAULT_SETTING => __('0', static::WOOCOMMERCE),
                static::DESC_TIP        => true,
            ),
            'blik_on'           => array(
                static::TITLE           => __('Włącz płatności blikiem na stronie sklepu <br/> (transakcje od 1zł)
 <br/> <a href="https://secure.transferuj.pl/integration/instruction/64">Instrukcja</a>', static::WOOCOMMERCE),
                static::TYPE            => static::SELECT,
                static::DEFAULT_SETTING => '0',
                static::OPTIONS         => array(
                    '0' => __('NIE', static::WOOCOMMERCE),
                    '1' => __('TAK', static::WOOCOMMERCE),
                ),
            ),
            'api_key'           => array(
                static::TITLE           => __('Klucz API', static::WOOCOMMERCE),
                static::TYPE            => 'text',
                static::DESCRIPTION     => __('Klucz API wygenerowany w panelu odbiorcy płatności tpay.com.'
                    , static::WOOCOMMERCE),
                static::DEFAULT_SETTING => __('0', static::WOOCOMMERCE),
                static::DESC_TIP        => true,
            ),
            'api_pass'          => array(
                static::TITLE           => __('Hasło API', static::WOOCOMMERCE),
                static::TYPE            => 'text',
                static::DESCRIPTION     => __('Hasło do klucza API', static::WOOCOMMERCE),
                static::DEFAULT_SETTING => __('0', static::WOOCOMMERCE),
                static::DESC_TIP        => true,
            ),
            'status'            => array(
                static::TITLE           => __('Status zamówienia po opłaceniu w tpay.com', static::WOOCOMMERCE),
                static::TYPE            => static::SELECT,
                static::DEFAULT_SETTING => '0',
                static::OPTIONS         => array(
                    '0' => __('W trakcie realizacji', static::WOOCOMMERCE),
                    '1' => __('Zrealizowane', static::WOOCOMMERCE),
                ),
            ),
            'doplata'           => array(
                static::TITLE           => __('Dopłata doliczana za korzystanie z Transferuj', static::WOOCOMMERCE),
                static::TYPE            => static::SELECT,
                static::DEFAULT_SETTING => '0',
                static::OPTIONS         => array(
                    '0' => __('NIE', static::WOOCOMMERCE),
                    '1' => __('PLN', static::WOOCOMMERCE),
                    '2' => __('%', static::WOOCOMMERCE),
                ),
            ),
            'kwota_doplaty'     => array(
                static::TITLE           => __('Kwota dopłaty', static::WOOCOMMERCE),
                static::TYPE            => "text",
                'css'                   => $ukryjD,
                static::DESCRIPTION     => __('Kwota jaka zostanie doliczona do zamówienia.
                 Jako separator liczb należy wykorzystać kropkę', static::WOOCOMMERCE),
                static::DEFAULT_SETTING => __('0', static::WOOCOMMERCE),
                static::DESC_TIP        => true,
            ),
            'bank_list'         => array(
                static::TITLE           => __('Włącz wybór banku na stronie sklepu', static::WOOCOMMERCE),
                static::TYPE            => static::SELECT,
                static::DEFAULT_SETTING => '0',
                static::OPTIONS         => array(
                    '0' => __('TAK', static::WOOCOMMERCE),
                    '1' => __('NIE', static::WOOCOMMERCE),
                ),
            ),
            'bank_view'         => array(
                static::TITLE           => __('Widok listy kanałów', static::WOOCOMMERCE),
                static::TYPE            => static::SELECT,
                static::DEFAULT_SETTING => '0',
                'css'                   => $ukryjK,
                static::OPTIONS         => array(
                    '0' => __('Kafelki', static::WOOCOMMERCE),
                    '1' => __('Lista', static::WOOCOMMERCE),
                ),
            ),
            'documentation'     => array(
                static::TITLE       => __('Dokumentacja techniczna', static::WOOCOMMERCE),
                static::TYPE        => static::TITLE,
                static::DESCRIPTION => sprintf(__(' <a href="%s" TARGET="_blank">
 Link do dokumentacji Technicznej systemu tpay.com</a>.', static::WOOCOMMERCE), 'https://tpay.com/dokumentacje.html'),
            ),
        );
    }
}

