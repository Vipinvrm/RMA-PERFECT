<?php

namespace Krishaweb\Rma\Controller\Adminhtml\Rma;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class SavePickup extends Action 
{
	protected $_resultPageFactory;
	protected $_statusHistoryFactory;
	
	public function __construct(
		Context $context, 
		PageFactory $resultPageFactory,
		\Krishaweb\Rma\Model\StatusHistoryFactory $statusHistoryFactory
	){
		parent::__construct($context);
		$this->_resultPageFactory = $resultPageFactory;
		$this->_statusHistoryFactory = $statusHistoryFactory;
	}
	
	public function execute()
	{
		$data = $this->getRequest()->getPostValue();
		$resultRedirect = $this->resultRedirectFactory->create();
		
		if (!$data) {
			$this->messageManager->addErrorMessage(__('No data found to save.'));
			return $resultRedirect->setPath('*/*/');
		}
		
		$rma_id = $this->getRequest()->getParam('rma_id');
		
		if (!$rma_id) {
			$this->messageManager->addErrorMessage(__('Invalid RMA ID.'));
			return $resultRedirect->setPath('*/*/');
		}
		
		try {
			$model = $this->_objectManager->create('Krishaweb\Rma\Model\Order');
			$model->load($rma_id);
			
			if (!$model->getId()) {
				throw new \Exception(__('RMA order not found.'));
			}
			
			// Save pickup information
			$model->setPickupScheduled(1);
			$model->setPickupDate($data['pickup_date']);
			$model->setPickupTimeSlot($data['pickup_time_slot']);
			$model->setPickupContactName($data['pickup_contact_name']);
			$model->setPickupContactPhone($data['pickup_contact_phone']);
			$model->setPickupAddress($data['pickup_address']);
			
			if (!empty($data['courier_name'])) {
				$model->setCourierName($data['courier_name']);
			}
			
			if (!empty($data['tracking_number'])) {
				$model->setTrackingNumber($data['tracking_number']);
			}
			
			if (!empty($data['pickup_instructions'])) {
				$model->setPickupInstructions($data['pickup_instructions']);
			}
			
			$model->save();
			
			// Add status history entry
			$historyModel = $this->_statusHistoryFactory->create();
			$comment = sprintf(
				'Pickup scheduled for %s, %s. Contact: %s (%s)',
				$data['pickup_date'],
				$data['pickup_time_slot'],
				$data['pickup_contact_name'],
				$data['pickup_contact_phone']
			);
			
			$historyModel->setData([
				'rma_order_id' => $rma_id,
				'status' => 'Pickup Scheduled',
				'comment' => $comment
			]);
			$historyModel->save();
			
			$this->messageManager->addSuccessMessage(__('Pickup information has been saved successfully.'));
			
			if ($this->getRequest()->getParam('back')) {
				return $resultRedirect->setPath('*/*/edit', ['id' => $rma_id, '_current' => true]);
			}
			
			return $resultRedirect->setPath('*/*/edit', ['id' => $rma_id]);
			
		} catch (\Exception $e) {
			$this->messageManager->addExceptionMessage(
				$e, 
				__('Something went wrong while saving pickup information: %1', $e->getMessage())
			);
		}
		
		$this->_getSession()->setFormData($data);
		return $resultRedirect->setPath('*/*/edit', ['id' => $rma_id]);
	}
	
	protected function _isAllowed()
	{
		return $this->_authorization->isAllowed('Krishaweb_Rma::showrma');
	}
}