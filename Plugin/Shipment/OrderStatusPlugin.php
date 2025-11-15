<?php
/**
 * File: Plugin/Shipment/OrderStatusPlugin.php
 * 
 * Plugin to automatically change order status to "Shipped" when shipment is created
 * Respects admin configuration settings for customization
 */

namespace Krishaweb\Rma\Plugin\Shipment;

use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class OrderStatusPlugin
{
    /**
     * Configuration paths
     */
    const XML_PATH_AUTO_CHANGE_STATUS = 'rma/shipment_automation/auto_change_status';
    const XML_PATH_TARGET_STATUS = 'rma/shipment_automation/target_status';
    const XML_PATH_ADD_COMMENT = 'rma/shipment_automation/add_comment';
    const XML_PATH_NOTIFY_CUSTOMER = 'rma/shipment_automation/notify_customer';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * After shipment is saved, update order status to "Shipped"
     *
     * @param Shipment $subject
     * @param Shipment $result
     * @return Shipment
     */
    public function afterSave(Shipment $subject, $result)
    {
        try {
            // Check if auto status change is enabled
            $isEnabled = $this->scopeConfig->isSetFlag(
                self::XML_PATH_AUTO_CHANGE_STATUS,
                ScopeInterface::SCOPE_STORE
            );

            if (!$isEnabled) {
                return $result;
            }

            // Get the order from the shipment
            $order = $subject->getOrder();
            
            // Check if order exists and is in "Processing" state
            if ($order && $order->getId()) {
                $currentState = $order->getState();
                $currentStatus = $order->getStatus();
                
                // Only update if order is in processing state
                if ($currentState === Order::STATE_PROCESSING) {
                    // Get target status from configuration or use default
                    $targetStatus = $this->scopeConfig->getValue(
                        self::XML_PATH_TARGET_STATUS,
                        ScopeInterface::SCOPE_STORE
                    );
                    
                    if (!$targetStatus) {
                        $targetStatus = 'shipped';
                    }

                    // Check if we should add a comment
                    $addComment = $this->scopeConfig->isSetFlag(
                        self::XML_PATH_ADD_COMMENT,
                        ScopeInterface::SCOPE_STORE
                    );

                    // Check if we should notify customer
                    $notifyCustomer = $this->scopeConfig->isSetFlag(
                        self::XML_PATH_NOTIFY_CUSTOMER,
                        ScopeInterface::SCOPE_STORE
                    );
                    
                    // Set order state to complete and status to configured status
                    $order->setState(Order::STATE_COMPLETE);
                    $order->setStatus($targetStatus);
                    
                    // Add comment to order history if enabled
                    if ($addComment) {
                        $comment = __(
                            'Order status automatically changed to "%1" after shipment #%2 creation.',
                            $targetStatus,
                            $subject->getIncrementId()
                        );
                        
                        $order->addCommentToStatusHistory(
                            $comment,
                            $targetStatus,
                            $notifyCustomer
                        );
                    }
                    
                    // Save the order
                    $order->save();
                    
                    $this->logger->info(
                        'Order status automatically updated after shipment creation',
                        [
                            'order_id' => $order->getId(),
                            'order_increment_id' => $order->getIncrementId(),
                            'shipment_id' => $subject->getId(),
                            'shipment_increment_id' => $subject->getIncrementId(),
                            'previous_status' => $currentStatus,
                            'new_status' => $targetStatus,
                            'customer_notified' => $notifyCustomer
                        ]
                    );
                }
            }
        } catch (\Exception $e) {
            // Log the error but don't prevent shipment creation
            $this->logger->error(
                'Error updating order status after shipment creation: ' . $e->getMessage(),
                [
                    'exception' => $e,
                    'order_id' => isset($order) ? $order->getId() : null,
                    'shipment_id' => $subject->getId()
                ]
            );
        }
        
        return $result;
    }
}