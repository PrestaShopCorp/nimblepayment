{*
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
*}

<link href="{$this_path|escape}views/css/nimble.css" rel="stylesheet" type="text/css" media="all">
{capture name=path}{l s='Nimble Payments' mod='nimblepayment'}{/capture}

<p><strong>{l s='The payment could not be completed, an error has occurred:' mod='nimblepayment'}</strong> {$error|escape}</p>

<p>
	<a href="javascript:history.back()" title="{l s='Back' mod='nimblepayment'}" class="button-exclusive btn btn-default">
		<i class="icon-chevron-left"></i>
		{l s='Back' mod='nimblepayment'}
	</a>
</p>