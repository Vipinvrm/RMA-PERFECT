<?php
namespace Krishaweb\Rma\Controller\Rma;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session;
use Krishaweb\Rma\Model\OrderFactory;
use Krishaweb\Rma\Model\StatusHistoryFactory;
use Magento\Framework\Controller\ResultFactory;

class SubmitPickup extends Action
{
    protected $_customerSession;
    protected $_rmaOrderFactory;
    protected $_statusHistoryFactory;

    public function __construct(
        Context $context,
        Session $customerSession,
        OrderFactory $rmaOrderFactory,
        StatusHistoryFactory $statusHistoryFactory
    ){
        $this->_customerSession = $customerSession;
        $this->_rmaOrderFactory = $rmaOrderFactory;
        $this->_statusHistoryFactory = $statusHistoryFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        
        // Check if customer is logged in
        if (!$this->_customerSession->isLoggedIn()) {
            $this->messageManager->addErrorMessage(__('Please login to submit pickup details.'));
            return $resultRedirect->setPath('customer/account/login');
        }

        $data = $this->getRequest()->getPostValue();
        
        if (!$data) {
            $this->messageManager->addErrorMessage(__('No data found to save.'));
            return $resultRedirect->setPath('rma/rma/history');
        }

        $rmaOrderId = $this->getRequest()->getParam('rma_order_id');
        
        if (!$rmaOrderId) {
            $this->messageManager->addErrorMessage(__('Invalid RMA ID.'));
            return $resultRedirect->setPath('rma/rma/history');
        }

        try {
            $rmaOrder = $this->_rmaOrderFactory->create()->load($rmaOrderId);
            
            if (!$rmaOrder->getId()) {
                throw new \Exception(__('RMA order not found.'));
            }

            // Validate required fields
            $requiredFields = [
                'customer_pickup_date' => __('Pickup Date'),
                'customer_pickup_time_slot' => __('Time Slot'),
                'customer_pickup_contact_name' => __('Contact Name'),
                'customer_pickup_contact_phone' => __('Contact Phone'),
                'customer_pickup_address' => __('Pickup Address')
            ];

            foreach ($requiredFields as $field => $label) {
                if (empty($data[$field])) {
                    throw new \Exception(__('%1 is required.', $label));
                }
            }

            // Save customer pickup information
            $rmaOrder->setCustomerPickupSubmitted(1);
            $rmaOrder->setCustomerPickupDate($data['customer_pickup_date']);
            $rmaOrder->setCustomerPickupTimeSlot($data['customer_pickup_time_slot']);
            $rmaOrder->setCustomerPickupContactName($data['customer_pickup_contact_name']);
            $rmaOrder->setCustomerPickupContactPhone($data['customer_pickup_contact_phone']);
            $rmaOrder->setCustomerPickupAddress($data['customer_pickup_address']);
            $rmaOrder->setPickupRequestSubmittedAt(date('Y-m-d H:i:s'));
            
            if (!empty($data['customer_pickup_instructions'])) {
                $rmaOrder->setCustomerPickupInstructions($data['customer_pickup_instructions']);
            }
            
            $rmaOrder->save();

            // Add status history entry
            $historyModel = $this->_statusHistoryFactory->create();
            $comment = sprintf(
                'Customer submitted pickup details: Date: %s, Time: %s, Contact: %s (%s)',
                $data['customer_pickup_date'],
                $data['customer_pickup_time_slot'],
                $data['customer_pickup_contact_name'],
                $data['customer_pickup_contact_phone']
            );
            
            $historyModel->setData([
                'rma_order_id' => $rmaOrderId,
                'status' => 'Pickup Details Submitted',
                'comment' => $comment
            ]);
            $historyModel->save();

            $this->messageManager->addSuccessMessage(
                __('Your pickup details have been submitted successfully. Our team will contact you soon.')
            );
            
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('Error submitting pickup details: %1', $e->getMessage())
            );
        }

        return $resultRedirect->setPath('rma/rma/history');
    }
}