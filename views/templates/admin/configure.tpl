{*
* 2007-2020 PrestaShop
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
*  @copyright 2007-2020 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

{if isset($confirmation)}{$confirmation}{/if}

<div class="defaultForm form-horizontal">
	<div class="panel" id="fieldset_0">
		<div class="panel-heading">
			<i class="icon-info"></i> Information
		</div>
		<div class="form-wrapper">
			<div class="form-group">
				<label class="control-label col-lg-3">
					{l s='Webhook URL' mod='lyzi'}
				</label>
				<div class="col-lg-9">
					<input type="text" name="LIZY_WEBHOOK_ADDRESS" id="LIZY_WEBHOOK_ADDRESS"
						   value="{$webhook_endpoint|escape:'html':'UTF-8'}" readonly />
					<p class="help-block">
						Please copy this URL and use it to create a new WebHook in your Lizy button form settings.
					</p>
				</div>
			</div>
		</div>
	</div>
</div>
