{*
*
* NOTICE OF LICENSE
*
*
*  @author 
*  @copyright  
*  @license    
*}
<link href="{$module_dir|escape}views/css/nimble.css" rel="stylesheet" type="text/css" media="all">
<div class="row">
	<div class="col-xs-12 col-md-6">
        <p class="payment_module">
            <a 
            class="nimblepayment bankwire" 
            href="{$link->getModuleLink('nimblepayment', 'payment')|escape:'html':'UTF-8'}" 
            title="{l s='Pay by Nimble' mod='nimblepayment'}">
			<img src="{$module_dir|escape}views/img/nimble.png" alt="{l s='Pay by Nimble' mod='nimblepayment'}"/>
            	{l s='Pay by Credit card' mod='nimblepayment'} 
            </a>
        </p>
    </div>
</div>