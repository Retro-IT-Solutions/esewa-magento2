<?php

namespace Retroitsoln\Esewa\Model;

class Esewa extends \Magento\Payment\Model\Method\AbstractMethod
{
    const ESEWA_PAYMENT_CODE = 'esewa';
    protected $_isGateway = true;
    protected $_canUseInternal = true;

    protected $_code = self::ESEWA_PAYMENT_CODE;
    protected $_formBlockType = \Retroitsoln\Esewa\Block\Form\Esewa::class;
    protected $_infoBlockType = \Magento\Payment\Block\Info\Instructions::class;

    public function getInstructions()
    {
        // Fetch instructions from config
        $instructions = $this->getConfigData('instructions');
        
        // Ensure instructions is a string, defaulting to empty string if null
        return trim($instructions !== null ? $instructions : '');
    }
}