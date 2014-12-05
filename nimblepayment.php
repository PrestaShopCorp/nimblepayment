<?php
if (! defined('_CAN_LOAD_FILES_'))
    exit();
if (! defined('_PS_VERSION_'))
    exit();

class nimblepayment extends PaymentModule
{

    public function __construct ()
    {
        $this->name = 'nimblepayment';
        $this->tab = 'payments_gateways';
        $this->version = '1.0';
        $this->author = 'BBVA';
        
        $config = Configuration::getMultiple(
                array(
                        'NIMBLEPAYMENT_CLIENT_ID',
                        'NIMBLEPAYMENT_CLIENT_SECRET',
                        'NIMBLEPAYMENT_URLTPV',
                        'NIMBLEPAYMENT_KEY',
                        'NIMBLEPAYMENT_NAME',
                        'NIMBLEPAYMENT_DESCRIPTION',
                        'NIMBLEPAYMENT_CODE',
                        'NIMBLEPAYMENT_URL_OK',
                        'NIMBLEPAYMENT_URL_KO'
                ));
        $this->bootstrap = true;
        parent::__construct();
        
        $this->page = basename(__FILE__, '.php');
        $this->displayName = $this->l('nimble');
        $this->description = $this->l('nimble payment gateway');
        $this->confirmUninstall = $this->l('Are you sure about removing these details?');
    }

    public function install ()
    {
        if (! parent::install() || ! Configuration::updateValue('NIMBLEPAYMENT_URL_OK', 
                'http://' . $_SERVER['HTTP_HOST'] . __PS_BASE_URI__ . 'modules/nimblepayment/ok.php') || ! Configuration::updateValue(
                'NIMBLEPAYMENT_URL_KO', 
                'http://' . $_SERVER['HTTP_HOST'] . __PS_BASE_URI__ . 'modules/nimblepayment/ko.php') || ! Configuration::updateValue(
                'NIMBLEPAYMENT_URLTPV', 'http://dev.nimble.com/demo....') || ! $this->registerHook('payment') ||
                 ! $this->registerHook('paymentReturn'))
            return false;
        return true;
    }

    public function uninstall ()
    {
        if (! Configuration::deleteByName('NIMBLEPAYMENT_CLIENT_ID') ||
                 ! Configuration::deleteByName('NIMBLEPAYMENT_CLIENT_SECRET') ||
                 ! Configuration::deleteByName('NIMBLEPAYMENT_URLTPV') ||
                 ! Configuration::deleteByName('NIMBLEPAYMENT_KEY') ||
                 ! Configuration::deleteByName('NIMBLEPAYMENT_NAME') ||
                 ! Configuration::deleteByName('NIMBLEPAYMENT_DESCRIPTION') ||
                 ! Configuration::deleteByName('NIMBLEPAYMENT_CODE') ||
                 ! Configuration::deleteByName('NIMBLEPAYMENT_URL_OK') ||
                 ! Configuration::deleteByName('NIMBLEPAYMENT_URL_KO') || ! parent::uninstall())
            return false;
        return true;
    }

    private function _postValidation ()
    {
        if (Tools::isSubmit('btnSubmit')) {
            if (! Tools::getValue('NIMBLEPAYMENT_CLIENT_ID'))
                $this->_postErrors[] = $this->l('Client id is required.');
            elseif (! Tools::getValue('NIMBLEPAYMENT_CLIENT_SECRET'))
                $this->_postErrors[] = $this->l('Client secret is required.');
            elseif (! Tools::getValue('NIMBLEPAYMENT_NAME'))
                $this->_postErrors[] = $this->l('Commerce name is required.');
            elseif (! Tools::getValue('NIMBLEPAYMENT_CODE'))
                $this->_postErrors[] = $this->l('Commerce code is required.');
        }
    }

    private function _postProcess ()
    {
        if (Tools::isSubmit('btnSubmit')) {
            Configuration::updateValue('NIMBLEPAYMENT_CLIENT_ID', Tools::getValue('NIMBLEPAYMENT_CLIENT_ID'));
            Configuration::updateValue('NIMBLEPAYMENT_CLIENT_SECRET', Tools::getValue('NIMBLEPAYMENT_CLIENT_SECRET'));
            Configuration::updateValue('NIMBLEPAYMENT_URLTPV', Tools::getValue('NIMBLEPAYMENT_URLTPV'));
            Configuration::updateValue('NIMBLEPAYMENT_NAME', Tools::getValue('NIMBLEPAYMENT_NAME'));
            Configuration::updateValue('NIMBLEPAYMENT_DESCRIPTION', Tools::getValue('NIMBLEPAYMENT_DESCRIPTION'));
            Configuration::updateValue('NIMBLEPAYMENT_CODE', Tools::getValue('NIMBLEPAYMENT_CODE'));
        }
        $this->_html .= $this->displayConfirmation($this->l('Settings updated'));
    }

    private function _displaynimblepayment ()
    {
        return $this->display(__FILE__, 'infos.tpl');
    }

    public function getContent ()
    {
        if (Tools::isSubmit('btnSubmit')) {
            $this->_postValidation();
            if (! count($this->_postErrors))
                $this->_postProcess();
            else
                foreach ($this->_postErrors as $err)
                    $this->_html .= $this->displayError($err);
        } else
            $this->_html .= '<br />';
        
        $this->_html .= $this->_displaynimblepayment();
        $this->_html .= $this->renderForm();
        return $this->_html;
    }

    public function renderForm ()
    {
        $this->fields_form[0]['form'] = array(
                'legend' => array(
                        'title' => $this->l('Client Details'),
                        'icon' => 'icon-edit'
                ),
                'input' => array(
                        array(
                                'type' => 'text',
                                'label' => $this->l('Client id'),
                                'name' => 'NIMBLEPAYMENT_CLIENT_ID'
                        ),
                        array(
                                'type' => 'text',
                                'label' => $this->l('Client secret'),
                                'name' => 'NIMBLEPAYMENT_CLIENT_SECRET'
                        )
                )
        );
        
        $options = array(
                array(
                        'id_option' => 'http://dev.nimble.com/....',
                        'name' => 'Real'
                ),
                array(
                        'id_option' => 'http://dev.nimble.com/demo....',
                        'name' => 'Demo'
                )
        );
        
        $this->fields_form[1]['form'] = array(
                'legend' => array(
                        'title' => $this->l('Commerce Details'),
                        'icon' => 'icon-edit'
                ),
                'input' => array(
                        array(
                                'type' => 'text',
                                'label' => $this->l('Commerce Name'),
                                'name' => 'NIMBLEPAYMENT_NAME'
                        ),
                        array(
                                'type' => 'textarea',
                                'label' => $this->l('Commerce Description'),
                                'name' => 'NIMBLEPAYMENT_DESCRIPTION'
                        ),
                        array(
                                'type' => 'text',
                                'label' => $this->l('Commerce Code'),
                                'name' => 'NIMBLEPAYMENT_CODE'
                        ),
                        array(
                                'type' => 'select',
                                'label' => $this->l('Url Payment'),
                                'name' => 'NIMBLEPAYMENT_URLTPV',
                                'options' => array(
                                        'query' => $options,
                                        'id' => 'id_option',
                                        'name' => 'name'
                                )
                        ),
                        array(
                                'type' => 'text',
                                'label' => $this->l('Commerce Url OK'),
                                'name' => 'NIMBLEPAYMENT_URL_OK',
                                'disabled' => TRUE
                        ),
                        array(
                                'type' => 'text',
                                'label' => $this->l('Commerce Url KO'),
                                'name' => 'NIMBLEPAYMENT_URL_KO',
                                'disabled' => TRUE
                        )
                ),
                'submit' => array(
                        'title' => $this->l('Save')
                )
        );
        
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get(
                'PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->id = (int) Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name .
                 '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
                'fields_value' => $this->getConfigFieldsValues(),
                'languages' => $this->context->controller->getLanguages(),
                'id_language' => $this->context->language->id
        );
        
        return $helper->generateForm($this->fields_form);
    }

    public function getConfigFieldsValues ()
    {
        return array(
                'NIMBLEPAYMENT_CLIENT_ID' => Tools::getValue('NIMBLEPAYMENT_CLIENT_ID', 
                        Configuration::get('NIMBLEPAYMENT_CLIENT_ID')),
                'NIMBLEPAYMENT_CLIENT_SECRET' => Tools::getValue('NIMBLEPAYMENT_CLIENT_SECRET', 
                        Configuration::get('NIMBLEPAYMENT_CLIENT_SECRET')),
                'NIMBLEPAYMENT_URLTPV' => Tools::getValue('NIMBLEPAYMENT_URLTPV', 
                        Configuration::get('NIMBLEPAYMENT_URLTPV')),
                'NIMBLEPAYMENT_KEY' => Tools::getValue('NIMBLEPAYMENT_KEY', Configuration::get('NIMBLEPAYMENT_KEY')),
                'NIMBLEPAYMENT_NAME' => Tools::getValue('NIMBLEPAYMENT_NAME', Configuration::get('NIMBLEPAYMENT_NAME')),
                'NIMBLEPAYMENT_DESCRIPTION' => Tools::getValue('NIMBLEPAYMENT_DESCRIPTION', 
                        Configuration::get('NIMBLEPAYMENT_DESCRIPTION')),
                'NIMBLEPAYMENT_CODE' => Tools::getValue('NIMBLEPAYMENT_CODE', Configuration::get('NIMBLEPAYMENT_CODE')),
                'NIMBLEPAYMENT_URL_OK' => Tools::getValue('NIMBLEPAYMENT_URL_OK', 
                        Configuration::get('NIMBLEPAYMENT_URL_OK')),
                'NIMBLEPAYMENT_URL_KO' => Tools::getValue('NIMBLEPAYMENT_URL_KO', 
                        Configuration::get('NIMBLEPAYMENT_URL_KO'))
        );
    }
}