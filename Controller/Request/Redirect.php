<?php

namespace Retroitsoln\Esewa\Controller\Request;

use Retroitsoln\Esewa\Helper\Data as EsewaHelper;
use Retroitsoln\Esewa\Model\EsewaCron;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\ResourceConnection;

class Redirect implements HttpGetActionInterface
{
    protected $checkoutSession;
    protected $urlBuilder;
    private $esewaHelper;
    protected $esewaCron;
    protected $resource;

    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        EsewaHelper $esewaHelper,
        ResourceConnection $resource,
        EsewaCron $esewaCron
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->urlBuilder = $context->getUrl();
        $this->esewaHelper = $esewaHelper;
        $this->resource = $resource;
        $this->esewaCron = $esewaCron;
    }
    public function execute()
    {
        $gatewayUrl = $this->esewaHelper->getEsewaUrl();
        $merchantSecret = $this->esewaHelper->getMerchantSecret();
        $productCode = $this->esewaHelper->getProductCode();
        
        $order = $this->checkoutSession->getLastRealOrder();

        $orderId = $order->getId();
        $transaction_uuid = strval($orderId.'-retro-'.$this->gettransactionUUID());
        $discounctAmount = $order->getDiscountAmount();
        $amount = strval($order->getSubtotal() + $discounctAmount);
        $taxAmount = strval($order->getTaxAmount());
        $shippingAmount = strval($order->getShippingAmount());
        $totalAmount = strval($order->getGrandTotal());
        $signature = $this->generateSignature($totalAmount, $transaction_uuid, $productCode, $merchantSecret);

        $this->esewaCron->scheduleJob($orderId, $transaction_uuid);


        echo "<form action='{$gatewayUrl}' method='post' id='esewa_payment_form'>
                <input type='hidden' id='amount' name='amount' value='{$amount}' />
                <input type='hidden' id='tax_amount' name='tax_amount' value='{$taxAmount}' />
                <input type='hidden' id='total_amount' name='total_amount' value='{$totalAmount}' />
                <input type='hidden' id='transaction_uuid' name='transaction_uuid' value='{$transaction_uuid}' />
                <input type='hidden' id='product_code' name='product_code' value='{$productCode}' />
                <input type='hidden' id='product_service_charge' name='product_service_charge' value='0' />
                <input type='hidden' id='product_delivery_charge' name='product_delivery_charge' value='{$shippingAmount}' />
                <input type='hidden' id='success_url' name='success_url' value='{$this->urlBuilder->getUrl('esewa/response/response')}' />
                <input type='hidden' id='failure_url' name='failure_url' value='{$this->urlBuilder->getUrl('esewa/response/response')}' />
                <input type='hidden' id='signed_field_names' name='signed_field_names' value='total_amount,transaction_uuid,product_code' />
                <input type='hidden' id='signature' name='signature' value='{$signature}' />
                <input type='submit' value='submit' />
                <script type='text/javascript'>document.getElementById('esewa_payment_form').submit();</script>";
        die;
    }

    protected function gettransactionUUID()
    {
        $length = 5;
        return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
    }

    protected function generateSignature($totalAmount, $transaction_uuid, $productCode, $merchantSecret)
    {
        $input_string = "total_amount={$totalAmount},transaction_uuid={$transaction_uuid},product_code={$productCode}";
        $merchant_secret = htmlspecialchars_decode($merchantSecret);
        $signature = hash_hmac('sha256', $input_string, $merchant_secret, true);
        $base64_signature = base64_encode($signature);
        return $base64_signature;
    }
}
