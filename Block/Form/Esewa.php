<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Retroitsoln\Esewa\Block\Form;

/**
 * Block for Custom payment method form
 */
class Esewa extends \Magento\OfflinePayments\Block\Form\AbstractInstruction
{
    /**
     * Custom payment template
     *
     * @var string
     */
    protected $_template = 'Retroitsoln_Esewa::form/esewa.phtml';
}