<?php
/**
 * File: Model/Config/Source/RmaType.php
 * 
 * Source model for RMA Type dropdown (Exchange/Return)
 */

namespace Krishaweb\Rma\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class RmaType implements ArrayInterface
{
    /**
     * Return array of options as value-label pairs
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'exchange', 'label' => __('Exchange')],
            ['value' => 'return', 'label' => __('Return')]
        ];
    }
}