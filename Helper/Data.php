<?php

namespace Retroitsoln\Esewa\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use PDO;

class Data extends AbstractHelper
{
    const ESEWA_MODE = 'payment/esewa/esewa_test_mode';
    const ESEWA_LIVE_MERCHENT_SECRET = 'payment/esewa/esewa_live_merchant_secret';
    const ESEWA_LIVE_PRODUCT_CODE = 'payment/esewa/esewa_live_product_code';
    const ESEWA_TEST_MERCHANT_SECRET = 'payment/esewa/esewa_test_merchant_secret';
    const ESEWA_TEST_PRODUCT_CODE = 'payment/esewa/esewa_test_product_code';

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function getEsewaMode()
    {
        return $this->scopeConfig->getValue(self::ESEWA_MODE, ScopeInterface::SCOPE_STORE);
    }

    public function getMerchantSecret()
    {
        if(!$this->getEsewaMode())
        {
            return $this->scopeConfig->getValue(
                self::ESEWA_LIVE_MERCHENT_SECRET,
                ScopeInterface::SCOPE_STORE
            );
        } else {
            return $this->scopeConfig->getValue(
                self::ESEWA_TEST_MERCHANT_SECRET,
                ScopeInterface::SCOPE_STORE
            );
        }
    }

    public function getProductCode()
    {
        if(!$this->getEsewaMode())
        {
            return $this->scopeConfig->getValue(
                self::ESEWA_LIVE_PRODUCT_CODE,
                ScopeInterface::SCOPE_STORE
            );
        } else {
            return $this->scopeConfig->getValue(
                self::ESEWA_TEST_PRODUCT_CODE,
                ScopeInterface::SCOPE_STORE
            );
        }
    }

    public function getEsewaUrl()
    {
        if(!$this->getEsewaMode())
        {
            return 'https://epay.esewa.com.np/api/epay/main/v2/form';
        } else {
            return 'https://rc-epay.esewa.com.np/api/epay/main/v2/form';
        }
    }

    public function getStatusCheckUrl()
    {
        if(!$this->getEsewaMode())
        {
            return 'https://epay.esewa.com.np/api/epay/transaction/status/';
        } else {
            return 'https://uat.esewa.com.np/api/epay/transaction/status/';
        }
    }
}