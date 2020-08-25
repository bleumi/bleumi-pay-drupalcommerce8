<?php

/**
 * BleumiPayForm
 *
 * PHP version 5
 *
 * @category  Bleumi
 * @package   Bleumi_BleumiPay
 * @author    Bleumi Pay <support@bleumi.com>
 * @copyright 2020 Bleumi, Inc. All rights reserved.
 * @license   MIT; see LICENSE
 * @link      http://pay.bleumi.com
 */

namespace Drupal\commerce_bleumipay\PluginForm\OffsiteRedirect;

require_once __DIR__ . '/../../BleumiPay/const.php';
require_once __DIR__ . '/../../BleumiPay/APIHandler.php';

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * BleumiPayForm 
 *
 * PHP version 5
 *
 * @category  Bleumi
 * @package   Bleumi_BleumiPay
 * @author    Bleumi Pay <support@bleumi.com>
 * @copyright 2020 Bleumi, Inc. All rights reserved.
 * @license   MIT; see LICENSE
 * @link      http://pay.bleumi.com
 */

class BleumiPayForm extends PaymentOffsiteForm
{

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);

        /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
        $payment = $this->entity;
        /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $paymentGatewayPlugin */
        $paymentGatewayPlugin = $payment->getPaymentGateway()->getPlugin();
        /** @var \Drupal\commerce_order\Entity\Order $order */
        $order = $payment->getOrder();
        $totalPrice = $order->getTotalPrice();
        $paymentGatewayPlugin->createPayment($payment);

        $data = array(
            "id" => $payment->getOrderId(),
            "total" => $totalPrice->getNumber(),
            "currency" => $totalPrice->getCurrencyCode(),
            "success" => $form['#return_url'],
            "cancel" => $form['#cancel_url']
        );

        $api = new \Drupal\commerce_bleumipay\BleumiPay\APIHandler();
        $result = $api->create($data);
        $redirect_data = array();
        if (!empty($result) && !empty($result['url'])) {
            $redirect_data = array();
            return $this->buildRedirectForm($form, $form_state, $result['url'], $redirect_data, PaymentOffsiteForm::REDIRECT_GET);
        } else {
            return drupal_set_message(t('Apologies. Checkout with Bleumi Pay does not appear to be working at the moment. Please try again.'), 'error');
        }    
    }

}
