<?php

/**
 * Constants Definition
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

defined('METADATA_SOURCE_PARAM') or  define('METADATA_SOURCE_PARAM', 'source');
defined('METADATA_SOURCE_VALUE') or define('METADATA_SOURCE_VALUE', 'drupal_commerce');
defined('SIGNATURE_HEADER') or define('SIGNATURE_HEADER', 'x-cc-webhook-signature');
defined('METADATA_ORDER_ID_PARAM') or define('METADATA_ORDER_ID_PARAM', 'orderid');
defined('METADATA_CLIENT_ID_PARAM') or define('METADATA_CLIENT_ID_PARAM', 'clientid');
