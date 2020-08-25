<?php

/**
 * CronInterface
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

namespace Drupal\commerce_bleumipay;

use Symfony\Component\HttpFoundation\Request;

/**
 * CronInterface 
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

interface CronInterface {

  /**
   * Processes an incoming CRON request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return mixed
   *   The request data array.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function process(Request $request);

}
