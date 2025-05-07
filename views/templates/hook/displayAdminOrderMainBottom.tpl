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

<section id="{$moduleName}-displayAdminOrderMainBottom">
    <div class="card mt-2">
        <div class="card-header">
            <h3 class="card-header-title">
                <img src="{$moduleLogoSrc}" alt="{$moduleDisplayName}" width="20" height="20">
                {$moduleDisplayName}
            </h3>
        </div>
        <div class="card-body">
            {if $message}
                {foreach $message as $key => $value}
                    <p>{$key} : {$value}</p>
                {/foreach}
            {/if}
        </div>
    </div>
</section>