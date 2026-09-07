<?php

if (!defined('JPATH_BASE')) {
    exit();
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
