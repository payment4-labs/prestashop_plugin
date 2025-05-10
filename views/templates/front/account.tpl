{**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    Amirhosein Taghizadeh <amirtz.dev@gmail.com>
 * @copyright Payment4 2025
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 *}

{extends file='customer/page.tpl'}

{block name='page_title'}
  <h1 class="h1">{$moduleDisplayName|escape:'htmlall':'UTF-8'} - {l s='Transactions' d='Modules.Payment4.Account'}</h1>
{/block}

{block name='page_content'}
  {if $orderPayments}
    <table class="table table-striped table-bordered">
      <thead class="thead-default">
      <tr>
        <th>{l s='Order reference' d='Modules.Payment4.Account'}</th>
        <th>{l s='Payment method' d='Modules.Payment4.Account'}</th>
        <th>{l s='Transaction reference' d='Modules.Payment4.Account'}</th>
        <th>{l s='Amount' d='Modules.Payment4.Account'}</th>
        <th>{l s='Date' d='Modules.Payment4.Account'}</th>
      </tr>
      </thead>
      <tbody>
      {foreach from=$orderPayments item=orderPayment}
        <tr>
          <td>{$orderPayment.order_reference|escape:'htmlall':'UTF-8'}</td>
          <td>{$orderPayment.payment_method|escape:'htmlall':'UTF-8'}</td>
          <td>{$orderPayment.transaction_id|escape:'htmlall':'UTF-8'}</td>
          <td>{$orderPayment.amount_formatted|escape:'htmlall':'UTF-8'}</td>
          <td>{$orderPayment.date_formatted|escape:'htmlall':'UTF-8'}</td>
        </tr>
      {/foreach}
      </tbody>
    </table>
  {else}
    <div class="alert alert-info">{l s='No transaction' d='Modules.Payment4.Account'}</div>
  {/if}
{/block}
