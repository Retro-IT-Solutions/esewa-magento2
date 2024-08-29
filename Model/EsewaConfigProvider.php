<?php
namespace Retroitsoln\Esewa\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Helper\Data as PaymentHelper;

class EsewaConfigProvider implements ConfigProviderInterface
{
    const ESEWA_PAYMENT_CODE = Esewa::ESEWA_PAYMENT_CODE;

    /**
     * @var \Magento\Payment\Model\Method\AbstractMethod[]
     */
    protected $methods = [];

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @param PaymentHelper $paymentHelper
     * @param Escaper $escaper
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        Escaper $escaper
    ) {
        $this->escaper = $escaper;
        $this->methods[self::ESEWA_PAYMENT_CODE] = $paymentHelper->getMethodInstance(self::ESEWA_PAYMENT_CODE);
    }

    public function getConfig()
    {
        $config = [];
        if ($this->methods[self::ESEWA_PAYMENT_CODE]->isAvailable()) {
            $config['payment']['instructions'][self::ESEWA_PAYMENT_CODE] = $this->getInstructions();
        }
        return $config;
    }

    protected function getInstructions()
    {
        $instructions = $this->methods[self::ESEWA_PAYMENT_CODE]->getInstructions();
        $instructions = $instructions !== null ? $instructions : '';
        
        return nl2br($this->escaper->escapeHtml(trim($instructions)));
    }
}
