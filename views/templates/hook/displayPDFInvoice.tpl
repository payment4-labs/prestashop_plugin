{**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
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

{if !empty($transaction)}
  <p>{l s='Your Payment4 Transaction ID is: %transaction%.' sprintf=['%transaction%' => $transaction] d='Modules.Payment4.Displaypdfinvoice'}</p>
{/if}
