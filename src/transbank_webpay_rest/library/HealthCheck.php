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
        $this->extensions = [
            'openssl',
            'SimpleXML',
            'dom',
        ];
    }

    /**
     * Validates the current PHP version.
     *
     * @return array The status and the current PHP version.
     */
    private function getValidatephp()
    {
        $minVersion = '7.0.0';
        $maxVersion = '7.4.0';
        $currentVersion = phpversion();
        $isValidVersion = version_compare($currentVersion, $minVersion, '>=') && version_compare($currentVersion, $maxVersion, '<=');
        return [
            'status'  => $isValidVersion ? 'OK' : 'Error!: Versión no soportada',
            'version' => $currentVersion,
        ];
    }

    /**
     * Checks if an extension is loaded and retrieves its version.
     *
     * @param string $extension The name of the extension to check.
     *
     * @return array The status and the extension version.
     */
    private function getCheckExtension($extension)
    {
        if (!extension_loaded($extension)) {
            return [
                'status' => 'Error!',
                'version' => 'No disponible'
            ];
        }
        $extensionIsSsl = $extension === 'openssl';
        $extensionVersion = $extensionIsSsl ? OPENSSL_VERSION_TEXT : phpversion($extension);
        return [
            'status' => 'OK',
            'version' => $extensionVersion
        ];
    }

    /**
     * Gets the currently installed Virtuemart version.
     *
     * @return string The installed Virtuemart version
     */
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

    /**
     * Retrieves information about the current ecommerce setup.
     *
     * @return array Array containing the current Virtuemart version,
     *               the current Transbank plugin version, and the latest Virtuemart version.
     */
    private function getEcommerceInfo()
    {
        include_once JPATH_ROOT . '/administrator/components/com_virtuemart/version.php';
        $actualversion = vmVersion::$RELEASE; // NOTE: confirmar si es como obtiene la version de ecommerce
        $lastversion = $this->getLastVirtuemartVersion();
        $pluginPath = JPATH_PLUGINS . '/vmpayment/transbank_webpay_rest/transbank_webpay_rest.xml';

        if (!file_exists($pluginPath)) {
            exit;
        }
        $xml = simplexml_load_file($pluginPath, null, LIBXML_NOCDATA);
        if ($xml === false) {
            exit;
        }
        $currentPluginVersion = (string) $xml->version;
        $result = [
            'current_ecommerce_version' => $actualversion,
            'last_ecommerce_version'    => $lastversion,
            'current_plugin_version'    => $currentPluginVersion,
        ];
        return $result;
    }

    /**
     * Gets the latest public release version from a specified GitHub repository.
     *
     * @param string $repository In the format 'user/repo'.
     *
     * @return string The latest release version.
     */
    private function getLastGitHubReleaseVersion($repository): string
    {
        $baseurl = 'https://api.github.com/repos/' . $repository . '/releases/latest';
        $agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $baseurl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        $content = curl_exec($ch);
        curl_close($ch);
        $con = json_decode($content, true);
        return $con['tag_name'] ?? '';
    }

    /**
     * Constructs an array providing information about the eCommerce platform and the plugin.
     *
     * @param string $ecommerce
     *
     * @return array Array containing the eCommerce name,
     *               the installed version, the current plugin version, and the latest plugin version available.
     */
    private function getPluginInfo($ecommerce)
    {
        $ecommerceInfo = $this->getEcommerceInfo();
        return [
            'ecommerce'              => $ecommerce,
            'ecommerce_version'      => $ecommerceInfo['current_ecommerce_version'],
            'current_plugin_version' => $ecommerceInfo['current_plugin_version'],
            'last_plugin_version'    => $this->getLastGitHubReleaseVersion('TransbankDevelopers/transbank-plugin-virtuemart-webpay-rest')
        ];
    }

    /**
     * Lists and validates PHP extensions/modules
     *
     * @return array The values are arrays containing the status and version of each extension.
     */
    private function getExtensionsValidate()
    {
        foreach ($this->extensions as $value) {
            $this->resExtensions[$value] = $this->getCheckExtension($value);
        }

        return $this->resExtensions;
    }

    /**
     * Gets server information. Does not include PHP info.
     *
     * @return array Array containing the PHP version, server version, and plugin information
     */
    private function getServerResume()
    {
        return [
            'php_version'    => $this->getValidatephp(),
            'server_version' => ['server_software' => $_SERVER['SERVER_SOFTWARE']],
            'plugin_info'    => $this->getPluginInfo($this->ecommerce),
        ];
    }

    /**
     * Creates an array with commerce information
     *
     * @return array  Array containing the environment, commerce code, and API key.
     */
    private function getCommerceInfo()
    {
        return [
            'data' => [
                'environment'   => $this->environment,
                'commerce_code' => $this->commerceCode,
                'api_key'       => $this->apiKey,
            ]
        ];
    }

    /**
     * Creates an array with PHP information.
     *
     * @return array Array containing the PHP information
     */
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

    /**
     * Initializes a transaction.
     *
     * @return array Array containing the status and the response.
     */
    public function setCreateTransaction()
    {
        $transbankSdkWebpay = new TransbankSdkWebpay($this->config);
        $amount = 990;
        $buyOrder = '_Healthcheck_';
        $sessionId = uniqid();
        $returnUrl = 'https://webpay3gint.transbank.cl/filtroUnificado/initTransaction';
        $result = $transbankSdkWebpay->createTransaction($amount, $sessionId, $buyOrder, $returnUrl);
        $status = (isset($result["error"])) ? 'Error' : 'OK';
        return [
            'status' => ['string' => $status],
            'response' => preg_replace('/<!--(.*)-->/Uis', '', $result)
        ];
    }

    /**
     * Gets all information into a single method.
     *
     * @return array Array containing server resume, PHP extensions status,
     *               commerce information, and PHP info.
     */
    private function getFullResume()
    {
        return [
            'server_resume'          => $this->getServerResume(),
            'php_extensions_status'  => $this->getExtensionsValidate(),
            'commerce_info'          => $this->getCommerceInfo(),
            'php_info'               => $this->getPhpInfo(),
        ];
    }

    /**
     * Return the full resume information in JSON format.
     *
     * @return string A JSON containing the full resume information.
     */
    public function printFullResume()
    {
        return json_encode($this->getFullResume());
    }
}
