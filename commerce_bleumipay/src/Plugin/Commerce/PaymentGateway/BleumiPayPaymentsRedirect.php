<?php

/**
 * BleumiPayPaymentsRedirect
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

namespace Drupal\commerce_bleumipay\Plugin\Commerce\PaymentGateway;

require_once __DIR__ . '/../../../BleumiPay/APIHandler.php';

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Provides the Off-site Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "bleumipay_payments_redirect",
 *   label = @Translation("Bleumi Pay - Pay with Digital Currencies"),
 *   display_label = @Translation("Bleumi Pay"),
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_bleumipay\PluginForm\OffsiteRedirect\BleumiPayForm",
 *   }
 * )
 */
class BleumiPayPaymentsRedirect extends OffsitePaymentGatewayBase
{
    /**
     * {@inheritdoc}
     */

    /**
     * @var api_key
     */
    protected $api_key;

    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);

        $this->api_key = !empty($this->configuration['api_key']) ? $this->configuration['api_key'] : '';
        
        $form['api_key'] = [
            '#type' => 'textfield',
            '#title' => $this->t('API Key'),
            '#default_value' => $this->api_key,
            '#description' => $this->t('API Key from Bleumi Pay.'),
            '#required' => TRUE
        ];

        $form['mode']['#access'] = FALSE;

        return $form;
    }


    /**
     * {@inheritdoc}
     */
    public function defaultConfiguration()
    {
        return [
                'api_key' => '',
            ] + parent::defaultConfiguration();
    }

    /**
     * {@inheritdoc}
     */
    public function validateConfigurationForm(array &$form, FormStateInterface $form_state)
    {
        parent::validateConfigurationForm($form, $form_state);

        if (!$form_state->getErrors() && $form_state->isSubmitted()) {
            $values = $form_state->getValue($form['#parents']);
            $this->configuration['api_key'] = $values['api_key'];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitConfigurationForm(array &$form, FormStateInterface $form_state)
    {
        parent::submitConfigurationForm($form, $form_state);
        if (!$form_state->getErrors()) {
            $values = $form_state->getValue($form['#parents']);
            $this->configuration['api_key'] = $values['api_key'];
        }
    }

    public function createPayment(PaymentInterface $payment)
    {
        $order = $payment->getOrder();

        /** @var \Drupal\commerce_payment\PaymentStorageInterface $paymentStorage */
        $paymentStorage = $this->entityTypeManager->getStorage('commerce_payment');

        $paymentAmount = $payment->getAmount();

        $payment = $paymentStorage->create([
            'state' => 'new',
            'amount' => $paymentAmount,
            'payment_gateway' => $this->entityId,
            'payment_method' => 'bleumi_pay',
            'order_id' => $order->id(),
            'test' => $this->getMode() == 'test',
            'authorized' => $this->time->getRequestTime(),
        ]);

        $payment->save();

    }
    
    public function onReturn(OrderInterface $order, Request $request)
    {
        //Preparing to invoke validate
        $callback = $_GET;
        $params = array(
            "hmac_alg" => $callback["hmac_alg"],
            "hmac_input" => $callback["hmac_input"],
            "hmac_keyId" => $callback["hmac_keyId"],
            "hmac_value" => $callback["hmac_value"],
        );
        $api = new \Drupal\commerce_bleumipay\BleumiPay\APIHandler();
        $dbHandler = new \Drupal\commerce_bleumipay\BleumiPay\DBHandler();
        $dbHandler->applyOrderTransition($order,'place'); 
        $order_id = $callback['id'];
        if (!empty($order_id)) {
            //Validating Payment
            $isValid = $api->validatePayment($params);
            //Create an empty row for the order in Bleumi Pay custom data table
            $dbHandler->createOrderMetaData($order_id); 
            if ($isValid) {
                $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
                $payments = $payment_storage->loadByProperties(['order_id' => $order_id]);
                $payment = reset($payments);
                $payment_transition = $payment->getState()->getWorkflow()->getTransition('authorize');
                $payment->getState()->applyTransition($payment_transition);
                $payment->setRemoteId($order_id);
                $payment->setRemoteState('authorization');
                $payment->save();
                //Validate success: transition order status to 'Fulfillment'
                $dbHandler->applyOrderTransition($order,'confirm');
            }
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function onCancel(OrderInterface $order, Request $request)
    {
        $status = $request->get('status');
        drupal_set_message($this->t('Payment @status on @gateway but may resume the checkout process here when you are ready.', [
            '@status' => $status,
            '@gateway' => $this->getDisplayLabel(),
        ]), 'error');
    }
}
