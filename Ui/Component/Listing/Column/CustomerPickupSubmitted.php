<?php
namespace Krishaweb\Rma\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class CustomerPickupSubmitted extends Column
{
    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['customer_pickup_submitted'])) {
                    $submitted = (int)$item['customer_pickup_submitted'];
                    
                    if ($submitted === 1) {
                        // Customer submitted pickup details
                        $item[$this->getData('name')] = 
                            '<span class="grid-severity-notice" style="background-color: #4caf50; color: white; padding: 4px 8px; border-radius: 3px; font-weight: 600; display: inline-block;">' .
                            '<span>✓ Submitted</span>' .
                            '</span>';
                    } else {
                        // Customer has not submitted pickup details
                        $item[$this->getData('name')] = 
                            '<span class="grid-severity-critical" style="background-color: #f44336; color: white; padding: 4px 8px; border-radius: 3px; font-weight: 600; display: inline-block;">' .
                            '<span>✗ Not Submitted</span>' .
                            '</span>';
                    }
                }
            }
        }

        return $dataSource;
    }
}