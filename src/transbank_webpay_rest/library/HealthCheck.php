<?php

require_once 'TransbankSdkWebpay.php';

use Transbank\Webpay\WebpayPlus;


class HealthCheck
{
    public $commerceCode;
    public $apiKey;
    public $environment;
    public $extensions;
    public $versioninfo;
    public $resume;
    public $fullResume;
    public $ecommerce;
    public $config;

    public function __construct($config)
    {
        $this->config = $config;
        $config['COMMERCE_CODE'] = WebpayPlus::DEFAULT_COMMERCE_CODE;
        $config['API_KEY'] = WebpayPlus::DEFAULT_API_KEY;
        $this->environment = $config['MODO'];
        $this->commerceCode = $config['COMMERCE_CODE'];
        $this->apiKey = $config['API_KEY'];
        $this->ecommerce = $config['ECOMMERCE'];
        // extensiones necesarias
        $this->extensions = [
            'openssl',
            'SimpleXML',
            'dom',
        ];
    }

    // valida version de php
    private function getValidatephp()
    {
        if (version_compare(phpversion(), '7.4', '<=') and version_compare(phpversion(), '7.0.0', '>=')) {
            $this->versioninfo = [
                'status'  => 'OK',
                'version' => phpversion(),
            ];
        } else {
            $this->versioninfo = [
                'status'  => 'Error!: Versión no soportada',
                'version' => phpversion(),
            ];
        }

        return $this->versioninfo;
    }

    // verifica si existe la extension y cual es la version de esta
    private function getCheckExtension($extension)
    {
        if (extension_loaded($extension)) {
            if ($extension == 'openssl') {
                $version = OPENSSL_VERSION_TEXT;
            } else {
                $version = phpversion($extension);
                if (empty($version) or $version == null or $version === false or $version == ' ' or $version == '') {
                    $version = 'PHP Extension Compiled. ver:'.phpversion();
                }
            }
            $status = 'OK';
            $result = [
                'status'  => $status,
                'version' => $version,
            ];
        } else {
            $result = [
                'status'  => 'Error!',
                'version' => 'No Disponible',
            ];
        }

        return $result;
    }

    // obtiene ultima version exclusivamente para virtuemart
    // NOTE: lastrelasevirtuemart
    private function getLastVirtuemartVersion()
    {
        $request_url = 'https://virtuemart.net/releases/vm3/virtuemart_update.xml';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $request_url);
        curl_setopt($curl, CURLOPT_TIMEOUT, 130);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($curl);
        curl_close($curl);

        $xml = simplexml_load_string($response);
        $json = json_encode($xml);
        $arr = json_decode($json, true);
        $version = $arr['update']['version'];

        return $version;
    }

    // funcion para obtener info de cada ecommerce, si el ecommerce es incorrecto o no esta seteado se escapa como respuesta "NO APLICA"
    private function getEcommerceInfo($ecommerce)
    {
        include_once JPATH_ROOT.'/administrator/components/com_virtuemart/version.php';
        $actualversion = vmVersion::$RELEASE; // NOTE: confirmar si es como obtiene la version de ecommerce
        $lastversion = $this->getLastVirtuemartVersion();
        if (!file_exists(JPATH_PLUGINS.'/vmpayment/transbank_webpay_rest/transbank_webpay_rest.xml')) {
            exit;
        } else {
            $xml = simplexml_load_file(JPATH_PLUGINS.'/vmpayment/transbank_webpay_rest/transbank_webpay_rest.xml', null, LIBXML_NOCDATA);
            $json = json_encode($xml);
            $arr = json_decode($json, true);
            $currentplugin = $arr['version'];
        }
        $result = [
            'current_ecommerce_version' => $actualversion,
            'last_ecommerce_version'    => $lastversion,
            'current_plugin_version'    => $currentplugin,
        ];

        return $result;
    }

    // creacion de retornos
    // arma array que entrega informacion del ecommerce: nombre, version instalada, ultima version disponible
    private function getPluginInfo($ecommerce)
    {
        $data = $this->getEcommerceInfo($ecommerce);
        $result = [
            'ecommerce'              => $ecommerce,
            'ecommerce_version'      => $data['current_ecommerce_version'],
            'current_plugin_version' => $data['current_plugin_version'],
            'last_plugin_version'    => $this->getPluginLastVersion($ecommerce, $data['current_ecommerce_version']), // ultimo declarado
        ];

        return $result;
    }

    // arma array con informacion del ultimo plugin compatible con el ecommerce
    /*
    vers_product:
    1 => WebPay Soap
    2 => WebPay REST
    3 => PatPass
    4 => OnePay
    */
    private function getPluginLastVersion($ecommerce, $currentversion)
    {
        return 'Indefinido';
    }

    // lista y valida extensiones/ modulos de php en servidor ademas mostrar version
    private function getExtensionsValidate()
    {
        foreach ($this->extensions as $value) {
            $this->resExtensions[$value] = $this->getCheckExtension($value);
        }

        return $this->resExtensions;
    }

    // crea resumen de informacion del servidor. NO incluye a PHP info
    private function getServerResume()
    {
        $this->resume = [
            'php_version'    => $this->getValidatephp(),
            'server_version' => ['server_software' => $_SERVER['SERVER_SOFTWARE']],
            'plugin_info'    => $this->getPluginInfo($this->ecommerce),
        ];

        return $this->resume;
    }

    // crea array con la informacion de comercio para posteriormente exportarla via json
    private function getCommerceInfo()
    {
        $result = [
            'environment'   => $this->environment,
            'commerce_code' => $this->commerceCode,
            'api_key'   => $this->apiKey,
        ];

        return ['data' => $result];
    }

    // guarda en array informacion de funcion phpinfo
    private function getPhpInfo()
    {
        ob_start();
        phpinfo();
        $info = ob_get_contents();
        ob_end_clean();
        $newinfo = strstr($info, '<table>');
        $newinfo = strstr($newinfo, '<h1>PHP Credits</h1>', true);
        $return = ['string' => ['content' => str_replace('</div></body></html>', '', $newinfo)]];

        return $return;
    }

    public function setCreateTransaction()
    {
        $transbankSdkWebpay = new TransbankSdkWebpay($this->config);
        $amount = 990;
        $buyOrder = '_Healthcheck_';
        $sessionId = uniqid();
        $returnUrl = 'https://webpay3gint.transbank.cl/filtroUnificado/initTransaction';
        $result = $transbankSdkWebpay->createTransaction($amount, $sessionId, $buyOrder, $returnUrl);
        if ($result) {
            if (!empty($result['error']) && isset($result['error'])) {
                $status = 'Error';
            } else {
                $status = 'OK';
            }
        } else {
            if (array_key_exists('error', $result)) {
                $status = 'Error';
            }
        }
        $response = [
            'status'   => ['string' => $status],
            'response' => preg_replace('/<!--(.*)-->/Uis', '', $result),
        ];

        return $response;
    }

    //compila en solo un metodo toda la informacion obtenida, lista para imprimir
    private function getFullResume()
    {
        $this->fullResume = [
            'server_resume'          => $this->getServerResume(),
            'php_extensions_status'  => $this->getExtensionsValidate(),
            'commerce_info'          => $this->getCommerceInfo(),
            'php_info'               => $this->getPhpInfo(),
        ];

        return $this->fullResume;
    }

    private function setpostinstall()
    {
        return false;
    }

    // imprime informacion de comercio y llaves
    public function printCommerceInfo()
    {
        return json_encode($this->getCommerceInfo());
    }

    public function printPhpInfo()
    {
        return json_encode($this->getPhpInfo());
    }

    // imprime resultado la consistencia de certificados y llabves
    public function printCertificatesStatus()
    {
        return json_encode($this->getValidateCertificates());
    }

    // imprime en formato json la validacion de extensiones / modulos de php
    public function printExtensionStatus()
    {
        return json_encode($this->getExtensionsValidate());
    }

    // imprime en formato json informacion del servidor
    public function printServerResume()
    {
        return json_encode($this->getServerResume());
    }

    // imprime en formato json el resumen completo
    public function printFullResume()
    {
        return json_encode($this->getFullResume());
    }

    public function getCreateTransaction()
    {
        return json_encode($this->setCreateTransaction());
    }

    public function getpostinstallinfo()
    {
        return json_encode($this->setpostinstall());
    }
}
