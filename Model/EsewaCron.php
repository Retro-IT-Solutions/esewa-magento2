<?php

namespace Retroitsoln\Esewa\Model;

use Magento\Cron\Model\ScheduleFactory;
use Magento\Cron\Model\ResourceModel\Schedule as ScheduleResource;

class EsewaCron
{
    protected $scheduleFactory;
    protected $scheduleResource;

    public function __construct(
        ScheduleFactory $scheduleFactory,
        ScheduleResource $scheduleResource
    ) {
        $this->scheduleFactory = $scheduleFactory;
        $this->scheduleResource = $scheduleResource;
    }

    public function scheduleJob($orderId, $transactionUuid)
    {
        $schedule = $this->scheduleFactory->create();
        $schedule->setJobCode('esewa_payment_status_check');
        $schedule->setStatus(\Magento\Cron\Model\Schedule::STATUS_PENDING);
        $schedule->setScheduledAt(date('Y-m-d H:i:s', time()+360)); //scheduled time 6 min
        $schedule->setCreatedAt(date('Y-m-d H:i:s', time()));
        $schedule->setMessages(json_encode(['order_id' => $orderId, 'transaction_uuid' => $transactionUuid]));
        $this->scheduleResource->save($schedule);
    }
}
