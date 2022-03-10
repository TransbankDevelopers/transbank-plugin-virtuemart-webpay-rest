<?php

require_once __DIR__.'/../vendor/autoload.php';
require_once 'LogHandler.php';

use Transbank\Webpay\WebpayPlus;

class TransbankSdkWebpay
{
    public $transaction;
    public $log;

    public function __construct($config)
    {
        $this->log = new LogHandler();
        $this->transaction = new WebpayPlus\Transaction();
        if (isset($config)) {
            $environment = isset($config['MODO']) ? $config['MODO'] : 'TEST';
            if ($environment == 'TEST') {
                $this->transaction->configureForIntegration(WebpayPlus::DEFAULT_COMMERCE_CODE, WebpayPlus::DEFAULT_API_KEY);
            } else {
                $this->transaction->configureForProduction($config['COMMERCE_CODE'], $config['API_KEY']);
            }
        }
    }

    public function createTransaction($amount, $sessionId, $buyOrder, $returnUrl)
    {
        try {
            $txDate = date('d-m-Y');
            $txTime = date('H:i:s');
            $this->log->logInfo('createTransaction - amount: '.$amount.', sessionId: '.$sessionId.
                ', buyOrder: '.$buyOrder.', txDate: '.$txDate.', txTime: '.$txTime);

            $response = $this->transaction->create($buyOrder, $sessionId, $amount, $returnUrl);
            $this->log->logInfo('createTransaction - result: '.json_encode($response));
            if (isset($response) && isset($response->url) && isset($response->token)) {
                return [
                    'url'      => $response->url,
                    'token_ws' => $response->token,
                ];
            }

            throw new Exception('No se ha creado la transacción para, amount: '.$amount.', sessionId: '.$sessionId.', buyOrder: '.$buyOrder);
        } catch (Exception $e) {
            $result = [
                'error'  => 'Error al crear la transacción',
                'detail' => $e->getMessage(),
            ];
            $this->log->logError(json_encode($result));

            return $result;
        }

        return [];
    }

    public function commitTransaction($tokenWs)
    {
        $result = [];

        try {
            $this->log->logInfo('getTransactionResult - tokenWs: '.$tokenWs);
            if ($tokenWs == null) {
                throw new Exception('El token webpay es requerido');
            }

            return $this->transaction->commit($tokenWs);
        } catch (Exception $e) {
            $result = [
                'error'  => 'Error al confirmar la transacción',
                'detail' => $e->getMessage(),
            ];
            $this->log->logError(json_encode($result));
        }

        return $result;
    }
}
