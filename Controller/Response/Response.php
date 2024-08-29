<?php

namespace Retroitsoln\Esewa\Controller\Response;

use Retroitsoln\Esewa\Helper\Data as EsewaHelper;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\App\ResponseFactory;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface as TransactionBuilder;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Framework\UrlInterface;

class Response implements HttpGetActionInterface
{
    protected $request;
    protected $esewaHelper;
    protected $orderRepository;
    protected $invoiceService;
    protected $invoiceSender;
    protected $transactionBuilder;
    protected $transactionRepository;
    protected $responseFactory;
    protected $url;

    public function __construct(
        Context $context,
        EsewaHelper $esewaHelper,
        OrderRepositoryInterface $orderRepository,
        InvoiceService $invoiceService,
        InvoiceSender $invoiceSender,
        TransactionBuilder $transactionBuilder,
        TransactionRepositoryInterface $transactionRepository,
        ResponseFactory $responseFactory,
        UrlInterface $url
    ) {
        $this->request = $context->getRequest();
        $this->esewaHelper = $esewaHelper;
        $this->orderRepository = $orderRepository;
        $this->invoiceSender = $invoiceSender;
        $this->invoiceService = $invoiceService;
        $this->transactionBuilder = $transactionBuilder;
        $this->transactionRepository = $transactionRepository;
        $this->responseFactory = $responseFactory;
        $this->url = $url;
    }

    public function execute()
    {
        $dataEncodedValue = $this->request->getParam('data');
        $failureUrl = $RedirectUrl = $this->url->getUrl('checkout/onepage/failure');

        if (!isset($dataEncodedValue) || empty($dataEncodedValue)) {
            $this->responseFactory
                ->create()
                ->setRedirect($failureUrl)
                ->sendResponse();
            die;
        }


        $dataDecoded = base64_decode($dataEncodedValue);
        $responseData = json_decode($dataDecoded, true);

        $esewaStatus = $this->esewaStatus($responseData);
        if (!$esewaStatus) {
            $this->responseFactory
                ->create()
                ->setRedirect($failureUrl)
                ->sendResponse();
            die;
        }


        $orderId = $this->getOrderId($responseData['transaction_uuid']);
        $order = $this->getOrder($orderId);
        if (!$order) {
            $this->responseFactory
                ->create()
                ->setRedirect($failureUrl)
                ->sendResponse();
            die;            
        }

        if (!$this->esewaValidate($responseData)) {
            $this->responseFactory
                ->create()
                ->setRedirect($failureUrl)
                ->sendResponse();
            die;
        }

        if ($this->updateOrderStatus($order, $responseData)) {
            $this->createTransaction($order, $responseData);
            $this->createAndSendInvoice($order);

            $successUrl = $this->url->getUrl('checkout/onepage/success');
            $this->responseFactory
                ->create()
                ->setRedirect($successUrl)
                ->sendResponse();
            die;
        }
        $this->responseFactory
            ->create()
            ->setRedirect($failureUrl)
            ->sendResponse();
        die;
    }

    private function esewaStatus($data)
    {
        return isset($data['status']) && $data['status'] === 'COMPLETE';
    }

    private function esewaValidate($data)
    {
        $esewaSignature = $data['signature'];
        $merchantSignature = $this->generateSignature($data, $this->esewaHelper->getMerchantSecret(), $this->esewaHelper->getProductCode());
        if ($esewaSignature === $merchantSignature) {
            return true;
        }
        return false;
    }

    private function generateSignature($data, $merchantSecret, $productCode)
    {
        $inputString = sprintf(
            "transaction_code=%s,status=%s,total_amount=%s,transaction_uuid=%s,product_code=%s,signed_field_names=%s",
            $data['transaction_code'],
            $data['status'],
            str_replace(',', '', $data['total_amount']),
            $data['transaction_uuid'],
            $productCode,
            $data['signed_field_names']
        );
        $signature = hash_hmac('sha256', $inputString, $merchantSecret, true);
        return base64_encode($signature);
    }

    private function getOrderId($transaction_uuid)
    {
        $position = strpos($transaction_uuid, '-retro-');
        $orderId = substr($transaction_uuid, 0, $position);
        return $orderId;
    }

    private function getOrder($orderId)
    {
        $order = $this->orderRepository->get($orderId);
        if ($order && $order->getEntityId()) {
            return $order;
        }
        return false;
    }

    private function updateOrderStatus(Order $order, $responseData)
    {
        $order->setState(Order::STATE_PROCESSING)
            ->setStatus(Order::STATE_PROCESSING);
        if ($order->save()) {
            return true;
        }
        return false;
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
            ->setTransactionId($responseData['transaction_code'])
            ->setAdditionalInformation(
                [Transaction::RAW_DETAILS => (array)$responseData]
            )
            ->setFailSafe(true)
            ->build(Transaction::TYPE_CAPTURE);

        $this->transactionRepository->save($transaction);
        $this->orderRepository->save($order);
    }
}
