<?php
/**
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
 */
if (!defined('_PS_VERSION_')) {
    exit;
}
/**
 * This Controller display transactions in customer account
 */
class Payment4AccountModuleFrontController extends ModuleFrontController
{
    /**
     * {@inheritdoc}
     */
    public $auth = true;

    /**
     * {@inheritdoc}
     */
    public $authRedirection = 'my-account';

    /**
     * {@inheritdoc}
     */
    public function initContent()
    {
        parent::initContent();

        $orderPaymentsQuery = new DbQuery();
        $orderPaymentsQuery->select(
            'op.order_reference, op.amount, op.id_currency, op.payment_method, op.transaction_id, op.card_number, op.card_brand, op.card_expiration, op.card_holder, op.date_add'
        );
        $orderPaymentsQuery->from('order_payment', 'op');
        $orderPaymentsQuery->innerJoin('orders', 'o', 'op.order_reference = o.reference');
        $orderPaymentsQuery->where('o.id_customer = ' . (int) $this->context->customer->id);
        $orderPaymentsQuery->where('o.module = "' . pSQL($this->module->name) . '"');

        $orderPayments = Db::getInstance((bool) _PS_USE_SQL_SLAVE_)->executeS($orderPaymentsQuery);

        if (false === empty($orderPayments)) {
            foreach ($orderPayments as $key => $orderPayment) {
                $orderPayments[$key]['amount_formatted'] = Tools::displayPrice(
                    $orderPayment['amount'],
                    (int) $orderPayment['id_currency']
                );

                $formattedDate = Tools::displayDate(
                    $orderPayment['date_add'],
                    true
                );
                $orderPayments[$key]['date_formatted'] = $formattedDate;
            }
        }

        $this->context->smarty->assign([
            'moduleDisplayName' => $this->module->displayName,
            'orderPayments' => $orderPayments,
        ]);

        $this->setTemplate('module:payment4/views/templates/front/account.tpl');
    }
}
