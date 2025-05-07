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
  <h1 class="h1">{$moduleDisplayName} - {l s='Transactions' mod='payment4'}</h1>
{/block}

{block name='page_content'}
  {if $orderPayments}
    <table class="table table-striped table-bordered">
      <thead class="thead-default">
      <tr>
        <th>{l s='Order reference' mod='payment4'}</th>
        <th>{l s='Payment method' mod='payment4'}</th>
        <th>{l s='Transaction reference' mod='payment4'}</th>
        <th>{l s='Amount' mod='payment4'}</th>
        <th>{l s='Date' mod='payment4'}</th>
      </tr>
      </thead>
      <tbody>
      {foreach from=$orderPayments item=orderPayment}
        <tr>
          <td>{$orderPayment.order_reference}</td>
          <td>{$orderPayment.payment_method}</td>
          <td>{$orderPayment.transaction_id}</td>
          <td>{$orderPayment.amount_formatted}</td>
          <td>{$orderPayment.date_formatted}</td>
        </tr>
      {/foreach}
      </tbody>
    </table>
  {else}
    <div class="alert alert-info">{l s='No transaction' mod='payment4'}</div>
  {/if}
{/block}
