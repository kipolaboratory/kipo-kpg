<?php

namespace kipolaboratory\KipoPay;

use Curl\Curl;

/**
 * Class KipoPay - V 0.5.3
 * @package kipolaboratory\KipoKpg
 *
 *
 * @property string $merchant_key
 * @property string $kipo_webgate_url
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
        'Content-Type' => 'application/json',
    ];

    /**
     * Default post data parameters and keys
     *
     * @var array
     */
    private $_post_data = [];

    /**
     * Contain error code explanation
     *
     * @var array
     */
    const ERROR_MESSAGE = [
        -1 => '.خطایی در داده‌های ارسالی وجود دارد،‌ لطفا اطلاعات را بررسی کنید و دوباره ارسال نمایید. (درخواست پرداخت)',
        -2 => 'خطایی در تحلیل داده‌های در سرور کیپو بوجود آمده است، دقایقی دیگر امتحان فرمایید.',
        -3 => 'امکان برقراری ارتباط با سرور کیپو میسر نمی‌باشد.',
        -4 => 'خطایی در داده‌های ارسالی وجود دارد،‌ لطفا اطلاعات را بررسی کنید و دوباره ارسال نمایید. (بررسی تایید پرداخت).',
        -5 => 'پرداخت توسط کاربر لغو شده یا با مشکل مواجه شده است.',
        -6 => 'شماره تماس فروشنده مورد نظر مورد تایید نمی‌باشد.',
        -7 => 'حداقل مبلغ پرداخت 1,000 ریال می‌باشد.',
        -8 => 'حداکثر مبلغ پرداخت 30,0000,000 ریال می‌باشد.',
        -9 => 'شناسه پرداخت ارسالی مورد تایید نمی‌باشد.'
    ];

    const API_GENERATE_TOKEN = 'api/v1/token/generate';
    const API_VERIFY_PAYMENT = 'api/v1/payment/verify';

    /**
     * Kipo server application url
     *
     * @var string
     */
    public $kipo_webgate_url = 'https://webgate.kipopay.com/';

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
        $this->_post_data = [
            'merchant_mobile' => $this->merchant_key,
            'payment_amount' => $amount,
            'callback_url' => $callback_url
        ];

        $curl->setOpts([
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $this->kipo_webgate_url . self::API_GENERATE_TOKEN,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_USERAGENT => 'kipo-kpg-agent',
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        try {
            /**
             * Send post request to kipo server
             */
            $curl->post($this->kipo_webgate_url . self::API_GENERATE_TOKEN, $this->_post_data);
        } catch (\Exception $exception) {
            $error_message = 'CURL request cannot be complete';
        }

        /**
         * Check if there is error
         */
        if (!$curl->error) {
            $response = $curl->response;

            /**
             * Check request is successfully
             */
            if ($curl->httpStatusCode == 200) {
                /** @var string $shopping_key */
                $this->_shopping_key = $response->payment_token;

                return ['status' => true, 'shopping_key' => $this->_shopping_key];
            } else {
                return [
                    'status' => false,
                    'code' => -1,
                    'message' => $this->getErrorMessage(-1),
                ];
            }
        } else {
            if ($curl->errorCode == 422) {
                /** @var array $last_error */
                $last_error = array_pop($curl->response);
                return [
                    'status' => false,
                    'code' => $last_error->message,
                    'message' => $this->getErrorMessage($last_error->message)
                ];
            }

            return [
                'status' => false,
                'code' => -3,
                'message' => $this->getErrorMessage(-3),
            ];
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
        $this->_post_data = [
            'payment_token' => $shopping_key,
        ];

        $curl->setOpts([
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $this->kipo_webgate_url . self::API_VERIFY_PAYMENT,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_USERAGENT => 'kipo-kpg-agent',
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        try {
            /**
             * Send post request to kipo server
             */
            $curl->post($this->kipo_webgate_url . self::API_VERIFY_PAYMENT, $this->_post_data);
        } catch (\Exception $exception) {
            $error_message = 'CURL request cannot be complete';
        }

        /**
         * Check if there is error
         */
        if (!$curl->error) {
            $response = $curl->response;

            /**
             * Check request is successfully
             */
            if ($curl->httpStatusCode == 200) {
                $this->_referent_code = $response->referent_code;

                /**
                 * Check if api return referent_code, show
                 * status true with referent_code and amount
                 */
                if (!is_null($this->_referent_code)) {
                    return [
                        'status' => true,
                        'referent_code' => $response->referent_code,
                        'amount' => $response->payment_amount
                    ];
                }

                return [
                    'status' => false,
                    'code' => -5,
                    'message' => $this->getErrorMessage(-5),
                ];

            } else {
                return [
                    'status' => false,
                    'code' => -4,
                    'message' => $this->getErrorMessage(-4),
                ];

            }
        } else {
            return [
                'status' => false,
                'code' => -3,
                'message' => $this->getErrorMessage(-3),
            ];
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
        $return_error = null;
        if (is_numeric($error_code)) {
            $return_error = self::ERROR_MESSAGE[$error_code];
        }

        return $return_error;
    }
}