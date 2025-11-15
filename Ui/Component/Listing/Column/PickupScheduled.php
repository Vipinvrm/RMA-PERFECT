<?php
/**
 * File: Ui/Component/Listing/Column/PickupScheduled.php
 * 
 * Custom renderer for Pickup Scheduled column
 */
namespace Krishaweb\Rma\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class PickupScheduled extends Column
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
                if (isset($item['pickup_scheduled'])) {
                    $pickupScheduled = (int)$item['pickup_scheduled'];
                    
                    if ($pickupScheduled === 1) {
                        // Pickup is scheduled
                        $item[$this->getData('name')] = 
                            '<span class="grid-severity-notice" style="background-color: #4caf50; color: white; padding: 4px 8px; border-radius: 3px; font-weight: 600; display: inline-block;">' .
                            '<span>✓ Yes</span>' .
                            '</span>';
                    } else {
                        // Pickup not scheduled
                        $item[$this->getData('name')] = 
                            '<span class="grid-severity-minor" style="background-color: #ff9800; color: white; padding: 4px 8px; border-radius: 3px; font-weight: 600; display: inline-block;">' .
                            '<span>✗ No</span>' .
                            '</span>';
                    }
                }
            }
        }

        return $dataSource;
    }
}