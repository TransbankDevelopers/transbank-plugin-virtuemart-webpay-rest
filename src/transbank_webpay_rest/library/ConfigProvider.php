<?php

defined('_JEXEC') or exit('Restricted access');

if (!class_exists('vmPSPlugin')) {
    require_once VMPATH_PLUGINLIBS.DS.'vmpsplugin.php';
}

if (!class_exists('LogHandler')) {
    require_once 'LogHandler.php';
}

/**
 * Transbank Webpay config provider for outside plugin instance.
 *
 * @autor vutreras (victor.utreras@continuum.cl)
 */
class ConfigProvider
{
    public function __construct()
    {
        $this->log = new LogHandler();
        $this->_config = 0;
        $this->_configXml = 0;
    }

    /**
     * return configuration for the plugin.
     */
    public function getConfig($key = null)
    {
        if ($this->_config === 0) {
            try {
                $this->_config = [];
                $db = JFactory::getDbo();
                $query = $db->getQuery(true);
                $query->select($db->quoteName(['payment_params']));
                $query->from($db->quoteName('#__virtuemart_paymentmethods'));
                $query->where($db->quoteName('payment_element').' = '.$db->quote('transbank_webpay_rest'));
                //$query = 'SELECT `payment_params` FROM `#__virtuemart_paymentmethods` where `payment_element` = ' . $db->quote('transbank_webpay_rest');
                $db->setQuery($query);
                $values = $db->loadObjectList();
                $arr = explode('|', $values[0]->payment_params);
                foreach ($arr as $val) {
                    $kv = explode('="', $val);
                    $k = str_replace('"', '', $kv[0]);
                    $v = str_replace('"', '', $kv[1]);
                    $v = str_replace('\\r\\n', "\n", $v);
                    $v = str_replace('\\', '', $v);
                    $v = str_replace(' ', '', $v);
                    $v = str_replace('-----BEGINRSAPRIVATEKEY-----', '-----BEGIN RSA PRIVATE KEY-----', $v);
                    $v = str_replace('-----ENDRSAPRIVATEKEY-----', '-----END RSA PRIVATE KEY-----', $v);
                    $v = str_replace('-----BEGINCERTIFICATE-----', '-----BEGIN CERTIFICATE-----', $v);
                    $v = str_replace('-----ENDCERTIFICATE-----', '-----END CERTIFICATE-----', $v);
                    $this->_config[$k] = trim($v);
                }
            } catch (Exception $e) {
                $this->log->logError($e);
            }
        }

        return $key != null ? $this->_config[$key] : $this->_config;
    }

    public function getConfigFromXml($key = null)
    {
        if ($this->_configXml === 0) {
            try {
                $this->_configXml = [];
                $xml = simplexml_load_file(JPATH_PLUGINS.'/vmpayment/transbank_webpay_rest/transbank_webpay_rest.xml', null, LIBXML_NOCDATA);
                $json = json_encode($xml);
                $dataConfig = json_decode($json, true);
                $dataConfig = $dataConfig['vmconfig']['fields']['fieldset']['field'];
                foreach ($dataConfig as $dConfig) {
                    $k = $dConfig['@attributes']['name'];
                    $v = $dConfig['@attributes']['default'];
                    if (!empty($k)) {
                        $v = str_replace('\\r\\n', "\n", $v);
                        $v = str_replace('\\', '', $v);
                        $v = str_replace(' ', '', $v);
                        $v = str_replace('-----BEGINRSAPRIVATEKEY-----', '-----BEGIN RSA PRIVATE KEY-----', $v);
                        $v = str_replace('-----ENDRSAPRIVATEKEY-----', '-----END RSA PRIVATE KEY-----', $v);
                        $v = str_replace('-----BEGINCERTIFICATE-----', '-----BEGIN CERTIFICATE-----', $v);
                        $v = str_replace('-----ENDCERTIFICATE-----', '-----END CERTIFICATE-----', $v);
                        $this->_configXml[$k] = trim($v);
                    }
                }
            } catch (Exception $e) {
                $this->log->logError($e);
            }
        }

        return $key != null ? $this->_configXml[$key] : $this->_configXml;
    }
}
