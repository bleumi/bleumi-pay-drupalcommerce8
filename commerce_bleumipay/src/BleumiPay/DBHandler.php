<?php
/**
 * DBHandler
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

namespace Drupal\commerce_bleumipay\BleumiPay;

/**
 * DBHandler 
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
class DBHandler
{

    const CRON_COLLISION_SAFE_MINUTES = 10;
    const AWAIT_PAYMENT_MINUTES = 24 * 60;
    
    /**
     * Retrieves the last execution time of the cron job
     *
     * @param $name Column to fetch value for
     *
     * @return string
     */
    public function getCronTime($name)
    {
        $cron_time = date("Y-m-d H:i:s", strtotime("-1 day"));
        $query = \Drupal::database()->select('commerce_bleumipay_cron', 'bpc');
        $query->addField('bpc', $name);
        $query->condition('bpc.id', '1');
        $query->range(0, 1);
        $result = $query->execute()->fetchField();
        if (!empty($result)) {
            $cron_time = $result;
        }
        return $cron_time;
    }

    /**
     * Sets the last execution time of the cron job
     *
     * @param $name Column to update in bleumi_pay_cron table
     * @param $time UNIX date/time value
     *
     * @return void
     */
    public function updateRuntime($name, $time)
    {
        $query = \Drupal::database()->update('commerce_bleumipay_cron');
        $query->fields([$name => $time]);
        $query->condition('id', '1');
        $query->execute();
    }

    /**
     * Get Order
     *
     * @param $order_id ID of the order to get details 
     *
     * @return object
     */
    public function getOrder($order_id)
    {
        $entity_type = 'commerce_order';
        $entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load((int)$order_id);
        return $entity;
    }


    /**
     * Get Order Meta Data
     *
     * @param $order_id    ID of the order to get meta data
     * @param $column_name Column Name
     *
     * @return object
     */
    public function getMeta($order_id, $column_name)
    {
        $query = \Drupal::database()->select('commerce_bleumipay_order', 'cbo');
        $query->addField('cbo', $column_name);
        $query->condition('cbo.order_id', $order_id);
        $query->range(0, 1);
        $result = $query->execute()->fetchField();
        return $result;
    }

    /**
     * Update Meta Data
     * 
     * @param $order_id     Order ID
     * @param $column_name  Column Name
     * @param $column_value Column Value
     * 
     * @return array
     */
    public function updateMetaData($order_id, $column_name, $column_value = null)
    {
        $query = \Drupal::database()->update('commerce_bleumipay_order');
        $query->fields([ $column_name => $column_value]);
        $query->condition('order_id', $order_id);
        $query->execute();
    }

    /**
     * Create Order Meta Data
     * 
     * @param $order_id     Order ID
     * 
     * @return array
     */
    public static function createOrderMetaData($order_id)
    {
        $query = \Drupal::database()->insert('commerce_bleumipay_order');
        $query->fields(['order_id']);
        $query->values([$order_id]);
        $query->execute();        
    }


    /**
     * Delete Order Meta Data
     *
     * @param $order_id    ID of the order to delete
     * @param $column_name Column Name
     *
     * @return void
     */
    public function deleteMetaData($order_id, $column_name)
    {
        return $this->updateMetaData($order_id, $column_name);
    }

    /**
     * Get the (Pending/Awaiting confirmation/Multi Token Payment)
     * order for the orders_id.
     *
     * @param $order_id ID of the order to get the details
     *
     * @return object
     */
    public function getPendingOrder($order_id)
    {
        $query = \Drupal::database()->select('commerce_order', 'co');
        $query->fields('co', array('order_id'))
            ->condition('co.order_id', $order_id)
            ->condition('co.state', ['pending', 'awaitingconfirm', 'multitoken'], 'IN');
        $found_id = $query->execute()->fetchField();
        if (!is_null($found_id)) {
            $entity_type = 'commerce_order';
            $order = \Drupal::entityTypeManager()->getStorage($entity_type)->load($found_id);
            return $order;
        }
        return null;
    }

    /**
     * Get all orders that are modified after $updatedTime
     * Usage: The list of orders processed by Orders cron
     *
     * @param $updatedTime Filter criteria - orders that are modified after this value will be returned
     *
     * @return object
     */
    public function getUpdatedOrders($updatedTime)
    {
        $currentTime = strtotime(date("Y-m-d H:i:s"));
        $query = \Drupal::database()->select('commerce_order', 'co');
        $query->join('commerce_bleumipay_order', 'cbo', 'co.order_id = cbo.order_id');
        $query->addField('co', 'order_id');
        $query->condition('co.state', ['completed', 'canceled'], 'IN');
        $query->condition('co.payment_gateway', 'bleumi_pay');
        $andGroup1 = $query->andConditionGroup()
            ->condition('co.changed', $updatedTime, '>=')
            ->condition('co.changed', $currentTime, '<=');
        $query->condition($andGroup1);
        $orGroup1 = $query->orConditionGroup()
            ->condition('cbo.bleumipay_processing_completed', 'no')
            ->condition('cbo.bleumipay_processing_completed', '')
            ->isNull('cbo.bleumipay_processing_completed');
        $query->condition($orGroup1);
        $query->orderBy('co.changed', 'DESC');
        $result = $query->execute()->fetchAll();
        return $result;
    }
    
    /**
     * Get all orders with status = $orderStatus
     * Usage: Orders cron to get all orders that are in
     * 'awaiting_confirmation' status to check if
     * they are still awaiting even after 24 hours.
     *
     * @param $status Filter criteria - status value
     * @param $field  The field to filter on. ('bleumipay_payment_status', 'state')
     *
     * @return object
     */
    public function getOrdersForStatus($status, $field)
    {
        $query = \Drupal::database()->select('commerce_order', 'co');
        $query->join('commerce_bleumipay_order', 'cbo', 'co.order_id = cbo.order_id');
        $query->addField('co', 'order_id');
        if ($field == 'state') {
            $query->condition('co.' . $field, $status);
        } else {
            $query->condition('cbo.' . $field, $status);
        }
        $query->condition('co.payment_gateway', 'bleumi_pay');
        $orGroup1 = $query->orConditionGroup()
            ->condition('cbo.bleumipay_processing_completed', 'no')
            ->condition('cbo.bleumipay_processing_completed', '')
            ->isNull('cbo.bleumipay_processing_completed');
        $query->condition($orGroup1);  
        $query->orderBy('co.changed', 'DESC');
        $result = $query->execute()->fetchAll();
        return $result;
    }    

    /**
     * Get all orders with transient errors.
     * Used by: Retry cron to reprocess such orders
     *
     * @return array
     */
    public function getTransientErrorOrders()
    {
        $query = \Drupal::database()->select('commerce_order', 'co');
        $query->join('commerce_bleumipay_order', 'cbo', 'co.order_id = cbo.order_id');
        $query->addField('co', 'order_id');
        $query->condition('cbo.bleumipay_transient_error', 'yes');
        $query->condition('co.payment_gateway', 'bleumi_pay');
        $orGroup1 = $query->orConditionGroup()
            ->condition('cbo.bleumipay_processing_completed', 'no')
            ->condition('cbo.bleumipay_processing_completed', '')
            ->isNull('cbo.bleumipay_processing_completed');
        $query->condition($orGroup1);    
        $query->orderBy('co.changed', 'DESC');
        $result = $query->execute()->fetchAll();
        return $result;
    }

    /**
     * Apply the order transition
     *
     * @param $order           The order to be transitioned to new state
     * @param $orderTransition The transition to apply to the order
     * 
     * @return bool
     */
    public function applyOrderTransition($order, $orderTransition)
    {
        $order_state = $order->getState();
        $order_state_transitions = $order_state->getTransitions();
        if (!empty($order_state_transitions) && isset($order_state_transitions[$orderTransition])) {
            $order_state->applyTransition($order_state_transitions[$orderTransition]);
            $order->save();
        }
    }

    /**
     * Check Balance and mark order as processing if sufficient balance is found
     *
     * @param $order        The order to be transitioned to new state
     * @param $payment_info Payment Info
     * @param $data_source  Data Source
     * 
     * @return bool
     */
    public function checkBalanceMarkProcessing($order, $payment_info, $data_source) 
    {
        $order_id = $order->order_id->value;
        $amount = 0;
        try {
            $amount = (float) $payment_info['token_balances'][0]['balance'];
        } catch (\Exception $e) {
        }
        $order_value = $order->getTotalPrice()->getNumber();
        if ($amount >= $order_value) {
            $this->applyOrderTransition($order, 'process');
            $this->updateMetaData($order_id, 'bleumipay_processing_completed', "no");
            $this->updateMetaData($order_id, 'bleumipay_payment_status', "payment-received");
            $this->updateMetaData($order_id, 'bleumipay_data_source', $data_source);
            return true;
        }
        return false;
    }

    /**
     * Get Minutes Difference - Returns the difference in minutes between 2 datetimes
     *
     * @param $dateTime1 start datetime
     * @param $dateTime2 end datetime
     *
     * @return bool
     */
    public function getMinutesDiff($dateTime1, $dateTime2)
    {
        $minutes = abs($dateTime1 - $dateTime2) / 60;
        return $minutes;
    }
}