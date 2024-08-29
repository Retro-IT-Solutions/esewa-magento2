<?php

namespace Retroitsoln\Esewa\Cron;

use Psr\Log\LoggerInterface;
use Magento\Framework\App\ResourceConnection;
use Retroitsoln\Esewa\Helper\Data as EsewaHelper;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface as TransactionBuilder;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Cron\Model\Schedule;

class StatusCheck
{
    protected $logger;
    protected $resource;
    protected $esewaHelper;
    protected $orderFactory;
    protected $invoiceService;
    protected $invoiceSender;
    protected $orderRepository;
    protected $transactionBuilder;
    protected $transactionRepository;


    public function __construct(
        LoggerInterface $logger,
        ResourceConnection $resource,
        EsewaHelper $esewaHelper,
        OrderFactory $orderFactory,
        InvoiceService $invoiceService,
        InvoiceSender $invoiceSender,
        OrderRepositoryInterface $orderRepository,
        TransactionBuilder $transactionBuilder,
        TransactionRepositoryInterface $transactionRepository
    ) {
        $this->logger = $logger;
        $this->resource = $resource;
        $this->esewaHelper = $esewaHelper;
        $this->orderFactory = $orderFactory;
        $this->invoiceService = $invoiceService;
        $this->invoiceSender = $invoiceSender;
        $this->orderRepository = $orderRepository;
        $this->transactionBuilder = $transactionBuilder;
        $this->transactionRepository = $transactionRepository;
    }

    public function execute(Schedule $schedule)
    {
        $dt = date('Y-m-d h:i:sa');
        $params = json_decode($schedule->getMessages(), true);
        $orderId = $params['order_id'] ?? null;
        $transactionUuid = $params['transaction_uuid'] ?? null;
        $order = $this->orderRepository->get($orderId);
        $totalAmount = strval($order->getGrandTotal());

        if ($orderId === null or $transactionUuid === null) {
            return;
        }

        $responseData = $this->getEsewaResponse($totalAmount, $transactionUuid);

        if ($responseData['status'] === 'COMPLETE') {
            $this->updateOrderStatus($order);
            $this->createTransaction($order, $responseData);
            $this->createAndSendInvoice($order);
        }
    }

    private function getEsewaResponse($totalAmount, $transactionUuid)
    {
        $productCode = $this->esewaHelper->getProductCode();
        $apiUrl = $this->esewaHelper->getStatusCheckUrl() . "?product_code={$productCode}&total_amount={$totalAmount}&transaction_uuid={$transactionUuid}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        if ($response === false) {
            $this->logger->info("Esewa Cron Job encountered response false at {$transactionUuid}::: " . $ch);
            return;
        }

        curl_close($ch);

        return json_decode($response, true);
    }

    private function updateOrderStatus(Order $order)
    {
        $order->setState(Order::STATE_PROCESSING)
            ->setStatus(Order::STATE_PROCESSING);
        $order->save();
    }

    private function createAndSendInvoice(Order $order)
    {
        if ($order->canInvoice()) {
            $invoice = $this->invoiceService->prepareInvoice($order);
            $invoice->register();
            $invoice->save();

            $this->invoiceSender->send($invoice);
        }
    }

    private function createTransaction(Order $order, $responseData)
    {
        $payment = $order->getPayment();
        $transaction = $this->transactionBuilder
            ->setPayment($payment)
            ->setOrder($order)
            ->setTransactionId($responseData['ref_id'])
            ->setAdditionalInformation(
                [Transaction::RAW_DETAILS => (array)$responseData]
            )
            ->setFailSafe(true)
            ->build(Transaction::TYPE_CAPTURE);

        $this->transactionRepository->save($transaction);
        $this->orderRepository->save($order);
    }
}
