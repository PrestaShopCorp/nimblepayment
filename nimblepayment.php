<?php
/*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/


if (! defined('_CAN_LOAD_FILES_'))
	exit();
if (! defined('_PS_VERSION_'))
	exit();

class NimblePayment extends PaymentModule
{
	public function __construct()
	{
		$this->name = 'nimblepayment';
		$this->tab = 'payments_gateways';
		$this->version = '1.0.2';
		$this->author = 'BBVA';

		$this->bootstrap = true;
		parent::__construct();
		$this->page = basename(__FILE__, '.php');
		$this->displayName = $this->l('Nimble Payments');
		$this->description = $this->l('Nimble Payments Gateway');
		$this->confirmUninstall = $this->l('Are you sure about removing these details?');
	}

	public function install()
	{
		//only support for PHP >= 5.3 because Nimble SDK is using namespaces
		if (! version_compare(phpversion(), '5.3', '>='))
		{
			$this->context->controller->errors[] = $this->l('Nimble Payments module only supports PHP versions greater or equal than 5.3');
			return false;
		}

		if (! parent::install()
				|| ! Configuration::updateValue('NIMBLEPAYMENT_URLTPV', 'sandbox')
				|| ! $this->registerHook('payment')
				|| ! $this->registerHook('paymentReturn'))
			return false;
		return true;
	}

	public function uninstall()
	{
	if (!Configuration::deleteByName('NIMBLEPAYMENT_CLIENT_ID')
				|| !Configuration::deleteByName('NIMBLEPAYMENT_CLIENT_SECRET')
				|| !Configuration::deleteByName('NIMBLEPAYMENT_URLTPV')
				|| !Configuration::deleteByName('NIMBLEPAYMENT_NAME')
				|| !Configuration::deleteByName('NIMBLEPAYMENT_DESCRIPTION')
				|| !parent::uninstall())
			return false;
		return true;
	}


	private function postValidation()
	{
		if (Tools::isSubmit('btnSubmit'))
		{
			if (!Tools::getValue('NIMBLEPAYMENT_CLIENT_ID'))
				$this->post_errors[] = $this->l('Client id is required.');
			elseif (!Tools::getValue('NIMBLEPAYMENT_CLIENT_SECRET'))
			$this->post_errors[] = $this->l('Client secret is required.');
			elseif (!Tools::getValue('NIMBLEPAYMENT_NAME'))
			$this->post_errors[] = $this->l('Shop name is required.');
		}
	}

	private function postProcess()
	{
		if (Tools::isSubmit('btnSubmit'))
		{
			Configuration::updateValue('NIMBLEPAYMENT_CLIENT_ID', Tools::getValue('NIMBLEPAYMENT_CLIENT_ID'));
			Configuration::updateValue('NIMBLEPAYMENT_CLIENT_SECRET', Tools::getValue('NIMBLEPAYMENT_CLIENT_SECRET'));
			Configuration::updateValue('NIMBLEPAYMENT_URLTPV', Tools::getValue('NIMBLEPAYMENT_URLTPV'));
			Configuration::updateValue('NIMBLEPAYMENT_NAME', Tools::getValue('NIMBLEPAYMENT_NAME'));
			Configuration::updateValue('NIMBLEPAYMENT_DESCRIPTION', Tools::getValue('NIMBLEPAYMENT_DESCRIPTION'));
		}
		return $this->displayConfirmation($this->l('Settings updated'));
	}

	private function displaynimblepayment()
	{
		return $this->display(__FILE__, 'infos.tpl');
	}

	public function getContent()
	{
		$output = null;

		if (Tools::isSubmit('btnSubmit'))
		{
			$this->postValidation();
			if (!count($this->post_errors))
				$output .= $this->postProcess();
			else
				foreach ($this->post_errors as $err)
					$output .= $this->displayError($err);
		}

		$output .= $this->displaynimblepayment();
		$output .= '<div id="nimble-form">'.$this->renderForm().'</div>';
		return $output;
	}

	public function hookPayment($params)
	{
		if (!$this->active)
			return;
		if (!$this->checkCurrency($params['cart']))
			return;
		$this->smarty->assign(array(
			'this_path' => $this->_path,
			'this_path_bw' => $this->_path,
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/'
		));
		return $this->display(__FILE__, 'payment.tpl');
	}

	public function checkCurrency($cart)
	{
		$currency_order = new Currency($cart->id_currency);
		$currencies_module = $this->getCurrency($cart->id_currency);

		if (is_array($currencies_module))
			foreach ($currencies_module as $currency_module)
				if ($currency_order->id == $currency_module['id_currency'])
					return true;
		return false;
	}

	public function renderForm()
	{
		$this->fields_form[0]['form'] = array (
						'legend' => array(
								'title' => $this->l('Client Details'),
								'icon' => 'icon-edit'
						),
						'input' => array(
								array(
										'type' => 'text',
										'label' => $this->l('Client id'),
										'name' => 'NIMBLEPAYMENT_CLIENT_ID',
								),
								array(
										'type' => 'text',
										'label' => $this->l('Client secret'),
										'name' => 'NIMBLEPAYMENT_CLIENT_SECRET',
								)
						)
		);
		$options = array(
				array(
						'id_option' => 'real',
						'name' => 'Real'
				),
				array(
						'id_option' => 'sandbox',
						'name' => 'Sandbox'
				),
		);
		$this->fields_form[1]['form'] = array (
				'legend' => array(
						'title' => $this->l('Shop Details'),
						'icon' => 'icon-edit'
				),
				'input' => array(
						array(
								'type' => 'text',
								'label' => $this->l('Shop Name'),
								'name' => 'NIMBLEPAYMENT_NAME',
						),
						array(
								'type' => 'textarea',
								'label' => $this->l('Shop Description'),
								'name' => 'NIMBLEPAYMENT_DESCRIPTION',
						),

						array(
						'type' => 'select',
						'label' => $this->l('Url Payment'),
						'name' => 'NIMBLEPAYMENT_URLTPV',
						'options' => array(
										'query' => $options,
										'id' => 'id_option',
										'name' => 'name'
										),
						),
						array(
								'type' => 'text',
								'label' => $this->l('Shop Url OK'),
								'name' => 'NIMBLEPAYMENT_URL_OK',
								'desc' => $this->l('Information only, not editable. This module automatically converts this URL in execution time ,
								 depending if "Friendly URL" is enabled or not. The language parameter is added in execution time.'),
								'readonly' => true,
						),
						array(
								'type' => 'text',
								'label' => $this->l('Shop Url KO'),
								'name' => 'NIMBLEPAYMENT_URL_KO',
								'desc' => $this->l('Information only, not editable. This module automatically converts this URL in execution time ,
								 depending if "Friendly URL" is enabled or not. The language parameter is added in execution time.'),
								'readonly' => true,

						)
				),
				'submit' => array(
						'title' => $this->l('Save'),
				)
		);

		$helper = new HelperForm();
		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper->default_form_language = $lang->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;

		$helper->id = (int)Tools::getValue('id_carrier');
		$helper->identifier = $this->identifier;
		$helper->submit_action = 'btnSubmit';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
		.'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
				'fields_value' => $this->getConfigFieldsValues(),
				'languages' => $this->context->controller->getLanguages(),
				'id_language' => $this->context->language->id
		);

		return $helper->generateForm($this->fields_form);
	}

	public function checkCurrencyNimble($cart)
	{
		$currency_order = new Currency($cart->id_currency);
		$currencies_module = $this->getCurrency($cart->id_currency);

		if (is_array($currencies_module))
			foreach ($currencies_module as $currency_module)
				if ($currency_order->id == $currency_module['id_currency'])
					return true;
		return false;
	}

	public function getConfigFieldsValues()
	{
		return array(
				'NIMBLEPAYMENT_CLIENT_ID' => Tools::getValue('NIMBLEPAYMENT_CLIENT_ID', Configuration::get('NIMBLEPAYMENT_CLIENT_ID')),
				'NIMBLEPAYMENT_CLIENT_SECRET' => Tools::getValue('NIMBLEPAYMENT_CLIENT_SECRET', Configuration::get('NIMBLEPAYMENT_CLIENT_SECRET')),
				'NIMBLEPAYMENT_URLTPV' => Tools::getValue('NIMBLEPAYMENT_URLTPV', Configuration::get('NIMBLEPAYMENT_URLTPV')),
				'NIMBLEPAYMENT_NAME' => Tools::getValue('NIMBLEPAYMENT_NAME', Configuration::get('NIMBLEPAYMENT_NAME')),
				'NIMBLEPAYMENT_DESCRIPTION' => Tools::getValue('NIMBLEPAYMENT_DESCRIPTION', Configuration::get('NIMBLEPAYMENT_DESCRIPTION')),
				'NIMBLEPAYMENT_URL_OK' => Context::getContext()->link->getModuleLink('nimblepayment', 'paymentok', array()),
				'NIMBLEPAYMENT_URL_KO' => Context::getContext()->link->getModuleLink('nimblepayment', 'paymentko', array())
		);
	}
}