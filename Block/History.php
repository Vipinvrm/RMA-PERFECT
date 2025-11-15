<?php
namespace Krishaweb\Rma\Block;

class History extends \Magento\Framework\View\Element\Template
{
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Krishaweb\Rma\Model\OrderFactory $rmaOrderFactory,
        \Krishaweb\Rma\Model\StatusHistoryFactory $statusHistoryFactory,
        \Magento\Sales\Model\Order $order,
        \Krishaweb\Rma\Model\StatusFactory $statusFactory
    ){
        $this->_customerSession = $customerSession;
        $this->_rmaOrderFactory = $rmaOrderFactory;
        $this->_statusHistoryFactory = $statusHistoryFactory;
        $this->_order = $order;
        $this->_statusFactory = $statusFactory;
        parent::__construct($context);
    }
    
    public function getRmaOrders()
    {
        $customerId = $this->_customerSession->getCustomerId();
        if (!$customerId) {
            return [];
        }
        
        // Get all orders for this customer
        $orderCollection = $this->_order->getCollection()
            ->addFieldToFilter('customer_id', $customerId);
        
        $orderIds = [];
        foreach ($orderCollection as $order) {
            $orderIds[] = $order->getId();
        }
        
        if (empty($orderIds)) {
            return [];
        }
        
        // Get RMA orders
        $rmaOrders = $this->_rmaOrderFactory->create()->getCollection()
            ->addFieldToFilter('order_id', ['in' => $orderIds])
            ->setOrder('created_at', 'DESC');
        
        return $rmaOrders;
    }
    
    public function getStatusHistory($rmaOrderId)
    {
        $history = $this->_statusHistoryFactory->create()->getCollection()
            ->addFieldToFilter('rma_order_id', $rmaOrderId)
            ->setOrder('created_at', 'ASC');
        
        return $history;
    }
    
    public function getOrder($orderId)
    {
        return $this->_order->load($orderId);
    }
    
    public function getCurrentStatus($rmaOrderId)
    {
        $rmaOrder = $this->_rmaOrderFactory->create()->load($rmaOrderId);
        $statusId = $rmaOrder->getStatus();
        
        if (!$statusId) {
            return 'Pending';
        }
        
        $status = $this->_statusFactory->create()->load($statusId);
        return $status->getTitle() ?: 'Pending';
    }
    
    public function canSubmitPickup($rmaOrder)
    {
        // Customer can submit pickup if:
        // 1. RMA is approved
        // 2. Pickup details not yet submitted
        $statusId = $rmaOrder->getStatus();
        $status = $this->_statusFactory->create()->load($statusId);
        $statusTitle = strtolower($status->getTitle());
        
        return (
            (strpos($statusTitle, 'approved') !== false || strpos($statusTitle, 'accept') !== false) 
            && !$rmaOrder->getCustomerPickupSubmitted()
        );
    }
    
    public function isPickupScheduled($rmaOrder)
    {
        return $rmaOrder->getPickupScheduled();
    }
    
    public function getRmaOrder($rmaOrderId)
    {
        return $this->_rmaOrderFactory->create()->load($rmaOrderId);
    }
}