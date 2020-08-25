<?php

/**
 * Cron
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

require_once __DIR__ . '/BleumiPay/PaymentCron.php';
require_once __DIR__ . '/BleumiPay/OrderCron.php';
require_once __DIR__ . '/BleumiPay/RetryCron.php';

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

use \Drupal\commerce_bleumipay\BleumiPay\PaymentCron;
use \Drupal\commerce_bleumipay\BleumiPay\OrderCron;
use \Drupal\commerce_bleumipay\BleumiPay\RetryCron;

/**
 * Cron 
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

class Cron implements CronInterface
{
    /**
     * Database Connection.
     *
     * @var Connection
     */
    protected $connection;

    /**
     * Entity Type Manager Interface.
     *
     * @var EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * Logger Interface.
     *
     * @var LoggerInterface 
     */
    protected $logger;

    /**
     * Configuration Factory Interface.
     *
     * @var ConfigFactoryInterface 
     */
    protected $configFactory;

    /**
     * Constructor
     * 
     * @param $connection        Database Connection
     * @param $entityTypeManager Entity Type Manager Interface
     * @param $logger            Logger Interface
     * @param $configFactory     Configuration Factory Interface
     * 
     * @return void
     */
    public function __construct(Connection $connection, EntityTypeManagerInterface $entityTypeManager, LoggerInterface $logger, ConfigFactoryInterface $configFactory)
    {
        $this->connection = $connection;
        $this->entityTypeManager = $entityTypeManager;
        $this->logger = $logger;
        $this->configFactory = $configFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Request $request)
    {
        $job = null;
        $jobId = $_GET["id"];
        switch ($jobId) {
            case "payment":
                $job = new PaymentCron (
                    $this->logger 
                );
                break;
            case "order":
                $job = new OrderCron (
                    $this->logger
                );
                break;
            case "retry":
                $job = new RetryCron (
                    $this->logger
                );
                break;
            default:
                echo "cron id not supplied. valid values = ['payment', 'order', 'retry']";
                return;
                break;
        }
        $job->execute();
    }

}
