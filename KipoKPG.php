<?php

namespace kipolaboratory\KipoPay;

use Curl\Curl;

/**
 * Class KipoPay
 * @package kipolaboratory\KipoKpg
 */
class KipoKPG
{
    public $merchant_key = '';

    private $_shopping_key = '';
    private $_referent_code = '';

    /**
     * Default headers
     * don't change default values
     *
     * @var array
     */
    private $_headers = [
        'Accept' => 'application/json',
        'IP' => '127.0.0.1',
        'OS' => 'web',
        'SC' => 'false',
        'SK' => '.',
        'Content-Type' => 'application/json',
    ];

    /**
     * Default post data parameters and keys
     *
     * @var array
     */
    private $_post_data = [
        'Command' => [
            'Sign' => '',
        ],
        'OrderAt' => '',
        'OrderID' => '100000',
        'Profile' => [
            'HID' => '+989901001001',
            'SID' => '00000000-0000-0000-0000-000000000000',
        ],
        'Session' => [
            '' => ''
        ],
        'Version' => [
            'AID' => 'kipo1-alpha',
        ],
        'RawData' => [
        ]
    ];

    /**
     * Contain error code explanation
     *
     * @var array
     */
    const ERROR_MESSAGE = [
        -1 => 'خطایی در داده‌های ارسالی وجود دارد،‌ لطفا اطلاعات را بررسی کنید و دوباره ارسال نمایید. (درخواست پرداخت)',
        -2 => 'امکان برقراری ارتباط با سرور کیپو میسر نمی‌باشد.',
        -3 => 'امکان برقراری ارتباط با سرور کیپو میسر نمی‌باشد.',
        -4 => 'خطایی در داده‌های ارسالی وجود دارد،‌ لطفا اطلاعات را بررسی کنید و دوباره ارسال نمایید. (بررسی تایید پرداخت)',
        -5 => 'پرداخت توسط کاربر لغو شده یا با مشکل مواجه شده است',
    ];

    /**
     * Kipo server application url
     *
     * @var string
     */
    public $request_url = 'https://kpg.kipopay.com:8091/V1.0/processors/json/';

    public $kipo_webgate_url = 'http://webgate.kipopay.com/';

    /**
     * KipoKPG constructor.
     * @param array $config
     */
    public function __construct($config)
    {
        /**
         * Check merchant_key
         */
        if (isset($config['merchant_key']) AND !empty($config['merchant_key']))
            $this->merchant_key = $config['merchant_key'];
    }

    /**
     * Get two parameter for amount and call back url and send data
     * to kipo server, retrieve shopping key to start payment, after
     * shopping key received, render form must be called or create form
     * manually
     *
     * @param $amount
     * @param $callback_url
     * @return array
     * @throws \ErrorException
     */
    public function KPGInitiate($amount, $callback_url)
    {
        $curl = new Curl();

        $curl->setHeaders($this->_headers);

        /**
         * Set specific post data for current request
         */
        $post_data = $this->_post_data;
        $post_data['Command']['Sign'] = 'KPG@KPG/Initiate';
        $post_data['OrderAt'] = date("Ymdhis");
        $post_data['RawData'] = [
            'MerchantKy' => $this->merchant_key,
            'Amount' => $amount,
            'BackwardUrl' => $callback_url
        ];

        $curl->setOpts([
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $this->request_url,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_USERAGENT => 'kipopay-kpg-agent',
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        try {
            /**
             * Send post request to kipo server
             */
            $curl->post($this->request_url, json_encode($post_data));
        } catch (\Exception $exception) {
            $error_message = 'CURL request cannot be complete';
        }

        /**
         * Check if there is error
         */
        if ($curl->error) {
            $error_message = $curl->error;
        }

        if (empty($error_message)) {
            $response = $curl->response;

            /**
             * Check request is successfully
             */
            if ($response->Outcome == "0000") {
                /** @var string $shopping_key */
                $shopping_key = $response->RawData->ShoppingKy;
                $this->_shopping_key = $shopping_key;

                return ['status' => true, 'shopping_key' => $shopping_key];
            } else {
                return ['status' => false, 'message' => -1];

            }
        } else {
            if ($curl->errorCode == 28)
                return ['status' => false, 'message' => -2];

            return ['status' => false, 'message' => -3];
        }
    }

    /**
     * Send inquery request to kipo server and retrieve
     * payment status, if response contain ReferingID, that
     * payment was successfully
     *
     * @param $shopping_key
     * @return array
     * @throws \ErrorException
     */
    public function KPGInquery($shopping_key)
    {
        $curl = new Curl();

        $curl->setHeaders($this->_headers);

        /**
         * Set specific post data for current request
         */
        $post_data = $this->_post_data;
        $post_data['Command']['Sign'] = 'KPG@KPG/Inquery';
        $post_data['OrderAt'] = date("Ymdhis");
        $post_data['RawData'] = [
            'ShoppingKy' => $shopping_key,
        ];

        $curl->setOpts([
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $this->request_url,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_USERAGENT => 'kipopay-kpg-agent',
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        try {
            /**
             * Send post request to kipo server
             */
            $curl->post($this->request_url, json_encode($post_data));
        } catch (\Exception $exception) {
            $error_message = 'CURL request cannot be complete';
        }

        /**
         * Check if there is error
         */
        if ($curl->error) {
            $error_message = $curl->error;
        }

        if (empty($error_message)) {
            $response = $curl->response;

            /**
             * Check request is successfully
             */
            if ($response->Outcome == "0000") {
                $this->_referent_code = $response->RawData->ReferingID;

                if (!is_null($this->_referent_code))
                    return ['status' => true, 'referent_code' => $this->_referent_code];

                return ['status' => false, 'message' => -5];

            } else {
                return ['status' => false, 'message' => -4];

            }
        } else {
            return ['status' => false, 'message' => -3];
        }
    }

    /**
     * This function render a simple form to
     * redirect user to kipo webgate with shopping key
     *
     * @param $shopping_key
     */
    public function renderForm($shopping_key)
    {
        ?>
        <form id="kipopay-gateway" method="post" action="<?= $this->kipo_webgate_url ?>"
              style="display: none;">
            <input type="hidden" id="sk" name="sk" value="<?= $shopping_key ?>"/>
        </form>
        <script language="javascript">document.forms['kipopay-gateway'].submit();</script>
        <?php
    }

    /**
     * Retrieve to user shopping key property
     *
     * @return string
     */
    public function getShoppingKey()
    {
        return $this->_shopping_key;
    }

    /**
     * Retrieve to user shopping key property
     *
     * @return string
     */
    public function getReferentCode()
    {
        return $this->_referent_code;
    }

    /**
     * Retrieve error message
     *
     * @param $error_code
     * @return mixed|null
     */
    public function getErrorMessage($error_code)
    {
        return (isset(self::ERROR_MESSAGE[$error_code])) ? self::ERROR_MESSAGE[$error_code] : null;
    }
}