<?php
namespace Krishaweb\Rma\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\StoreManagerInterface;

class Email extends AbstractHelper
{
    protected $_transportBuilder;
    protected $_inlineTranslation;
    protected $_storeManager;

    public function __construct(
        Context $context,
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        StoreManagerInterface $storeManager
    ) {
        $this->_transportBuilder = $transportBuilder;
        $this->_inlineTranslation = $inlineTranslation;
        $this->_storeManager = $storeManager;
        parent::__construct($context);
    }

    public function sendPickupSubmittedNotification($rmaOrder, $customerEmail, $customerName)
    {
        try {
            $this->_inlineTranslation->suspend();

            $templateVars = [
                'customer_name' => $customerName,
                'rma_id' => $rmaOrder->getRmaOrderId(),
                'order_id' => $rmaOrder->getOrderId(),
                'pickup_date' => $rmaOrder->getCustomerPickupDate(),
                'pickup_time' => $rmaOrder->getCustomerPickupTimeSlot(),
                'pickup_address' => $rmaOrder->getCustomerPickupAddress()
            ];

            $storeId = $this->_storeManager->getStore()->getId();

            // Send to customer
            $transport = $this->_transportBuilder
                ->setTemplateIdentifier('rma_pickup_submitted_customer') // You need to create this email template
                ->setTemplateOptions(['area' => 'frontend', 'store' => $storeId])
                ->setTemplateVars($templateVars)
                ->setFrom([
                    'name' => $this->scopeConfig->getValue('trans_email/ident_general/name'),
                    'email' => $this->scopeConfig->getValue('trans_email/ident_general/email')
                ])
                ->addTo($customerEmail, $customerName)
                ->getTransport();

            $transport->sendMessage();

            // Send notification to admin
            $adminEmail = $this->scopeConfig->getValue('trans_email/ident_general/email');
            $transport = $this->_transportBuilder
                ->setTemplateIdentifier('rma_pickup_submitted_admin') // You need to create this email template
                ->setTemplateOptions(['area' => 'adminhtml', 'store' => $storeId])
                ->setTemplateVars($templateVars)
                ->setFrom([
                    'name' => $this->scopeConfig->getValue('trans_email/ident_general/name'),
                    'email' => $this->scopeConfig->getValue('trans_email/ident_general/email')
                ])
                ->addTo($adminEmail)
                ->getTransport();

            $transport->sendMessage();

            $this->_inlineTranslation->resume();

            return true;
        } catch (\Exception $e) {
            $this->_logger->error('RMA Pickup Email Error: ' . $e->getMessage());
            return false;
        }
    }
}