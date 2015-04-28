<?php
/**
* 2007-2014 PrestaShop
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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2014 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

require_once dirname(__FILE__).'/../../library/sdk/lib/Nimble/base/NimbleAPI.php';
use Nimble\Base\NimbleAPI;

class NimblePaymentPaymentModuleFrontController extends ModuleFrontController
{
	public $ssl = true;
	public $display_column_left = false;
	public $nimblepayment_client_secret = '';
	public $nimblepayment_client_id = '';
	public $nimblepayment_urltpv = '';
	public $type_error = 0;
	public $nimbleapi;

	/**
	* @see FrontController::initContent()
	*/
	public function initContent()
	{
		parent::initContent();
		$cart = $this->context->cart;
		if (!$this->module->checkCurrencyNimble($cart))
			Tools::redirect('index.php?controller=order');
		if ($this->validatePaymentData() == true)
		{
			$total = $cart->getOrderTotal(true, Cart::BOTH) * 100;
			$order_num = str_pad($cart->id, 8, '0', STR_PAD_LEFT);
			$paramurl = $order_num.md5($order_num.$this->nimblepayment_client_secret.$total);
			if ($this->authentified() == true)
			$this->sendPayment($total, $paramurl);
		}
		$this->context->smarty->assign(array(
			'this_path' => $this->module->getPathUri(),
			'error' => $this->type_error,
			));
	}

	public function validatePaymentData()
	{
		$this->nimblepayment_client_secret = Tools::getValue('NIMBLEPAYMENT_CLIENT_SECRET', Configuration::get('NIMBLEPAYMENT_CLIENT_SECRET'));
		$this->nimblepayment_client_id = Tools::getValue('NIMBLEPAYMENT_CLIENT_ID', Configuration::get('NIMBLEPAYMENT_CLIENT_ID'));
		$this->nimblepayment_urltpv = Tools::getValue('NIMBLEPAYMENT_URLTPV', Configuration::get('NIMBLEPAYMENT_URLTPV'));

		if ($this->nimblepayment_client_secret == '' || $this->nimblepayment_client_id == '')
		{
			$this->setTemplate('payment_failed.tpl');
			
			//type error = 1
			//show error to the user
			$this->type_error = $this->l('Is not possible to contact with Nimble Payments. Sorry for the inconvenience.');

			//TO DO: and alert to the merchant. sending email maybe? // Tools::getValue('PS_SHOP_EMAIL', Configuration::get('PS_SHOP_EMAIL'))
			return false;
		}
		return true;
	}

	public function authentified()
	{
		$params = array(
			'clientId' => $this->nimblepayment_client_id,
			'clientSecret' => $this->nimblepayment_client_secret,
			'mode' => $this->nimblepayment_urltpv
		);

		try
		{
			$this->nimbleapi = new NimbleAPI($params);
		}
		catch (Exception $e)
		{
			//$this->type_error = $e->getMessage(); //don´t show that to final user
			$this->type_error = $this->l('Is not possible to contact with Nimble Payments. There are authentication problems. Sorry for the inconvenience.');
			$this->setTemplate('payment_failed.tpl');
			return false;
		}
		return true;
	}

	public function sendPayment($total, $paramurl)
	{
		$cart = $this->context->cart;

		$payment = array(
			'amount' => (int)$total,
			'currency' => 'EUR',
			'customerData' => $cart->id,
			'paymentSuccessUrl' => $this->context->link->getModuleLink('nimblepayment', 'paymentok', array('paymentcode' => $paramurl)),
			'paymentErrorUrl' => $this->context->link->getModuleLink('nimblepayment', 'paymentko', array('paymentcode' => $paramurl))
		);

		try
		{
			$response = Nimble\Api\Payments::SendPaymentClient($this->nimbleapi, $payment);
		}
		catch (Exception $e)
		{
			$this->type_error = 3; // problem to send payment
			$this->setTemplate('payment_failed.tpl');
			return false;
		}

		if (!isset($response['error']))
		{
			if ($response['result']['code'] == 200)
				//Tools::redirect($response['data'][0]['paymentUrl']); //old version
				Tools::redirect($response['data']['paymentUrl']);
			else
			{
				$this->setTemplate('payment_failed.tpl');
				$this->type_error = 3; // problem to send payment
			}
		}
		else
		{
			$this->type_error = 4; // an anomaly was detected in the data sent.
			$this->setTemplate('payment_failed.tpl');
			return false;
		}
	}
}