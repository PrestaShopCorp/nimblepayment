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

/** error_reporting(E_ALL);
* ini_set('display_errors', 1);
*/

include(dirname(__FILE__).'/nimblepayment.php');
class NimblePaymentPaymentOkModuleFrontController extends ModuleFrontController
{
	public $nimblepayment_client_secret = '';
	/**
	 * @see FrontController::initContent()
	 */

	public function initContent()
	{
		parent::initContent();
		$code = Tools::getValue('paymentcode');
		$cart = (int)Tools::substr($code, 0, 8); /** devuelve "d" */

		$this->nimblepayment_client_secret = Tools::getValue('NIMBLEPAYMENT_CLIENT_SECRET', Configuration::get('NIMBLEPAYMENT_CLIENT_SECRET'));
		$cart = new Cart($cart);
		$numpedido = Tools::substr($code, 0, 8);
		$total_url = str_replace('.', '', $cart->getOrderTotal(true, Cart::BOTH));
		$paramurl = $numpedido.md5($numpedido.$this->nimblepayment_client_secret.$total_url);

		if ($paramurl == $code)
		{
			$total = $cart->getOrderTotal(true, Cart::BOTH);
			$mailvars = array();
			$nimble = new nimblepayment();
			$nimble->validateOrder($cart->id, _PS_OS_PAYMENT_, $total, $nimble->displayName, null, $mailvars, null, false, $cart->secure_key);
			$customer = new Customer($cart->id_customer);
			Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id
				.'&id_module='.$nimble->module->id
				.'&id_order='.$nimble->module->currentOrder
				.'&key='.$customer->secure_key);
		}
	}
}
