<?php

/**
 * BleumipayPaymentController
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

namespace Drupal\commerce_bleumipay\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\commerce_bleumipay\CronInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * BleumipayPaymentController 
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

class BleumipayPaymentController extends ControllerBase
{
  private $cron;

  public function __construct(CronInterface $cron)
  {
    $this->cron = $cron;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('commerce_bleumipay.cron')
    );
  }

  public function process(Request $request)
  {
    // Get CRON request data and basic processing for the CRON request.
    $this->cron->process($request);
    $response = new Response();
    $response->setContent('');
    return $response;
  }
}
