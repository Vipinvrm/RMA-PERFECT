<?php
namespace Krishaweb\Rma\Controller\Rma;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class GetOptions extends Action
{
    protected $reasonFactory;
    protected $conditionFactory;

    public function __construct(
        Context $context,
        \Krishaweb\Rma\Model\ReasonFactory $reasonFactory,
        \Krishaweb\Rma\Model\ConditionFactory $conditionFactory
    ) {
        $this->reasonFactory = $reasonFactory;
        $this->conditionFactory = $conditionFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        
        $rmaType = $this->getRequest()->getParam('rma_type', 'exchange');

        try {
            // Get reasons
            $reasonModel = $this->reasonFactory->create();
            $reasons = $reasonModel->getCollection()
                ->addFieldToFilter('status', ['eq' => 1])
                ->addFieldToFilter('rma_type', ['eq' => $rmaType]);

            $reasonsArray = [];
            foreach ($reasons as $reason) {
                $reasonsArray[] = [
                    'id' => $reason->getReasonId(),
                    'title' => $reason->getTitle()
                ];
            }

            // Get conditions
            $conditionModel = $this->conditionFactory->create();
            $conditions = $conditionModel->getCollection()
                ->addFieldToFilter('status', ['eq' => 1])
                ->addFieldToFilter('rma_type', ['eq' => $rmaType]);

            $conditionsArray = [];
            foreach ($conditions as $condition) {
                $conditionsArray[] = [
                    'id' => $condition->getConditionId(),
                    'title' => $condition->getTitle()
                ];
            }

            return $resultJson->setData([
                'success' => true,
                'reasons' => $reasonsArray,
                'conditions' => $conditionsArray
            ]);

        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('An error occurred: %1', $e->getMessage())
            ]);
        }
    }
}