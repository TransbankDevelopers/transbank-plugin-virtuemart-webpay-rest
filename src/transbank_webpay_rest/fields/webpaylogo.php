<?php

defined('JPATH_BASE') or exit();

jimport('joomla.form.formfield');

class JFormFieldWebpayLogo extends JFormField
{
    /**
     * Element name.
     *
     * @var string
     */
    public $type = 'WebpayLogo';

    protected function getInput()
    {
        vmJsApi::addJScript('/plugins/vmpayment/transbank_webpay_rest/transbank_webpay_rest/assets/js/admin.js');
        $url = 'https://www.transbank.cl/';
        $logo = '<img src="/plugins/vmpayment/transbank_webpay_rest/transbank_webpay_rest/assets/images/logo-small-new.png" width="100" height="91"/>';
        $html = '<p>
                    <a target="_blank" href="'.$url.'"  >'.$logo.'</a>
                </p>
                <span>
                    <button class="btn btn-lg btn-danger" data-toggle="modal" data-target="#tb_commerce_mod_info">Información</button>
                <span>';
        return $html;
    }
}
