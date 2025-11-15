<?php

namespace Krishaweb\Rma\Controller\Adminhtml\Rma;

use \Magento\Backend\App\Action;
use \Magento\Backend\App\Action\Context;
use \Magento\Framework\View\Result\PageFactory;

class Save extends Action {
	protected $_resultPageFactory;
	protected $_resultPage;
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
	
	public function execute(){
		$data = $this->getRequest()->getPostValue();
		$resultRedirect = $this->resultRedirectFactory->create();
		
		if (!$data) {
			$this->messageManager->addErrorMessage(__('No data found to save.'));
			return $resultRedirect->setPath('*/*/');
		}
		
		$rma_id = $this->getRequest()->getParam('rma_id');
		$status = $this->getRequest()->getParam('adminstatus');
		$comment = $this->getRequest()->getParam('comment');
		
		if (!$rma_id) {
			$this->messageManager->addErrorMessage(__('Invalid RMA ID.'));
			return $resultRedirect->setPath('*/*/');
		}
		
		if (!$status) {
			$this->messageManager->addErrorMessage(__('Please select a status.'));
			return $resultRedirect->setPath('*/*/edit', ['id' => $rma_id]);
		}
		
		try {
			$model = $this->_objectManager->create('Krishaweb\Rma\Model\Order');
			$model->load($rma_id);
			
			if (!$model->getId()) {
				throw new \Exception(__('RMA order not found.'));
			}
			
			// Check if status has changed
			$oldStatus = $model->getStatus();
			$statusModel = $this->_objectManager->create('Krishaweb\Rma\Model\Status');
			$statusModel->load($status);
			$newStatusTitle = $statusModel->getTitle();
			
			if ($oldStatus != $status) {
				// Update RMA order status
				$model->setStatus($status);
				$model->save();
				
				// Add status history entry
				$historyModel = $this->_statusHistoryFactory->create();
				$historyModel->setData([
					'rma_order_id' => $rma_id,
					'status' => $newStatusTitle,
					'comment' => $comment ? $comment : ''
				]);
				$historyModel->save();
			}
			
			$this->messageManager->addSuccessMessage(__('RMA status has been updated successfully.'));
			
			if ($this->getRequest()->getParam('back')) {
				return $resultRedirect->setPath('*/*/edit', ['id' => $rma_id, '_current' => true]);
			}
			
			$this->_objectManager->get('Magento\Backend\Model\Session')->setFormData(false);
			return $resultRedirect->setPath('*/*/');
			
		} catch (\Exception $e) {
			$this->messageManager->addExceptionMessage($e, __('Something went wrong while saving: %1', $e->getMessage()));
		}
		
		$this->_getSession()->setFormData($data);
		return $resultRedirect->setPath('*/*/edit', ['id' => $rma_id]);
	}
	
	protected function _isAllowed(){
		return $this->_authorization->isAllowed('Krishaweb_Rma::showrma');
	}
}