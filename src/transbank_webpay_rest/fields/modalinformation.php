<?php

if (!defined('JPATH_BASE')) {
    return;
}

jimport('joomla.form.formfield');

class JFormFieldModalInformation extends JFormField
{
    /**
     * Element name.
     *
     * @var string
     */
    public $type = 'ModalInformation';

    protected function getInput()
    {
        return include_once 'modalcontent.php';
    }
}
