<?php

namespace Krishaweb\Rma\Controller\Adminhtml\Rma;

use \Magento\Backend\App\Action;
use \Magento\Backend\App\Action\Context;
use \Magento\Framework\View\Result\PageFactory;

class Delete extends Action {
    protected $_resultPageFactory;
    protected $_resultPage;
    
    public function __construct(
        Context $context, 
        PageFactory $resultPageFactory
    ){
        parent::__construct($context);
        $this->_resultPageFactory = $resultPageFactory;
    }
    
    public function execute(){
        $id = $this->getRequest()->getParam('id');
        
        if($id > 0){
            try {
                // Delete from RMA Order table
                $rmaOrderModel = $this->_objectManager->create('Krishaweb\Rma\Model\Order');
                $rmaOrderModel->load($id);
                
                if ($rmaOrderModel->getId()) {
                    $orderId = $rmaOrderModel->getOrderId();
                    
                    // First delete all related RMA items
                    $rmaModel = $this->_objectManager->create('Krishaweb\Rma\Model\Rma');
                    $rmaCollection = $rmaModel->getCollection()
                        ->addFieldToFilter('order_id', array('eq' => $orderId));
                    
                    foreach ($rmaCollection as $rmaItem) {
                        $rmaItem->delete();
                    }
                    
                    // Then delete the RMA order
                    $rmaOrderModel->delete();
                    
                    $this->messageManager->addSuccessMessage(__('The RMA has been deleted successfully.'));
                } else {
                    $this->messageManager->addErrorMessage(__('RMA order not found.'));
                }
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(
                    __('Something went wrong while deleting the RMA: %1', $e->getMessage())
                );
            }
        } else {
            $this->messageManager->addErrorMessage(__('Invalid RMA ID.'));
        }
        
        $this->_redirect('rma/rma');
    }
    
    protected function _isAllowed(){
        return $this->_authorization->isAllowed('Krishaweb_Rma::showrma');
    }
}