<?php
require_once(__DIR__ . '/../vendor/autoload.php');
require_once('LogHandler.php');

use Transbank\Webpay\Configuration;
use Transbank\Webpay\Webpay;
use Transbank\Webpay\WebpayPlus;

class TransbankSdkWebpay {
    
    var $transaction;
    
    function __construct($config) {
        $this->log = new LogHandler();
        if (isset($config)) {
            $environment = isset($config["MODO"]) ? $config["MODO"] : 'TEST';
            WebpayPlus::setApiKey($config['API_KEY']);
            WebpayPlus::setCommerceCode($config['COMMERCE_CODE']);
            WebpayPlus::setIntegrationType($environment);
        }
    }
    
    public function initTransaction($amount, $sessionId, $buyOrder, $returnUrl) {
        try{
            $txDate = date('d-m-Y');
            $txTime = date('H:i:s');
            $this->log->logInfo('initTransaction - amount: ' . $amount . ', sessionId: ' . $sessionId .
                ', buyOrder: ' . $buyOrder . ', txDate: ' . $txDate . ', txTime: ' . $txTime);
            
            $response = WebpayPlus\Transaction::create($buyOrder, $sessionId, $amount, $returnUrl);
            $this->log->logInfo('initTransaction - initResult: ' . json_encode($response));
            if (isset($response) && isset($response->url) && isset($response->token)) {
                return array(
                    "url" => $response->url,
                    "token_ws" => $response->token
                );
            }
            throw new Exception('No se ha creado la transacción para, amount: ' . $amount . ', sessionId: ' . $sessionId . ', buyOrder: ' . $buyOrder);
            
        } catch(Exception $e) {
            $result = array(
                "error" => 'Error al crear la transacción',
                "detail" => $e->getMessage()
            );
            $this->log->logError(json_encode($result));
            return $result;
        }
        return array();
    }
    
    public function commitTransaction($tokenWs) {
        $result = array();
        try{
            $this->log->logInfo('getTransactionResult - tokenWs: ' . $tokenWs);
            if ($tokenWs == null) {
                throw new Exception("El token webpay es requerido");
            }
            return WebpayPlus\Transaction::commit($tokenWs);
        } catch(Exception $e) {
            $result = array(
                "error" => 'Error al confirmar la transacción',
                "detail" => $e->getMessage()
            );
            $this->log->logError(json_encode($result));
        }
        return $result;
    }
}
