<?php
namespace Krishaweb\Rma\Controller\Rma;

class Save extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;

	public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Magento\Customer\Model\Session $customerSession,
		\Krishaweb\Rma\Model\RmaFactory $rma,
		\Krishaweb\Rma\Model\OrderFactory $rmaorder,
		\Magento\Sales\Model\Order $order,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Framework\View\Result\PageFactory $pageFactory
	){
		$this->_customerSession = $customerSession;
		$this->_rma = $rma;
		$this->rmaorder = $rmaorder;
		$this->order = $order;
		$this->scopeConfig = $scopeConfig;
		$this->_pageFactory = $pageFactory;
		return parent::__construct($context);
	}
	
	public function execute()
	{
		try{
			$data = $this->getRequest()->getPostValue();
			$params = array();
			$resultRedirect = $this->resultRedirectFactory->create();
			
			// Validate RMA Type
			if(!isset($data['rma_type']) || !in_array($data['rma_type'], ['exchange', 'return'])){
				throw new \Magento\Framework\Exception\LocalizedException(
					__('Please select RMA Type (Exchange or Return).')
				);
			}

			$rmaType = $data['rma_type'];

			// Check RMA days limit
			$orderId = $data['order_id'];
			$order = $this->order->load($orderId);
			
			if($order->getStatus() != 'complete'){
				throw new \Magento\Framework\Exception\LocalizedException(
					__('RMA is only allowed for completed orders.')
				);
			}

			$daysLimit = $this->scopeConfig->getValue(
				'rma/options/rma_days_limit',
				\Magento\Store\Model\ScopeInterface::SCOPE_STORE
			) ?: 14;

			$deliveryDate = $order->getUpdatedAt();
			$deliveryTimestamp = strtotime($deliveryDate);
			$currentTimestamp = time();
			$daysDiff = floor(($currentTimestamp - $deliveryTimestamp) / (60 * 60 * 24));

			if($daysDiff > $daysLimit){
				throw new \Magento\Framework\Exception\LocalizedException(
					__('RMA request period has expired. You can request RMA within %1 days of delivery.', $daysLimit)
				);
			}
			
			if(isset($data['check'])){
				foreach ($data['check'] as $key => $value) {
					if($value == 'on'){
						$params[$key]['item_id'] = $data['item_id'][$key];
						$params[$key]['qty_return'] = $data['qty_return'][$key];
						$params[$key]['reason'] = $data['reason'][$key];
						$params[$key]['condition'] = $data['condition'][$key];
						$params[$key]['order_id'] = $data['order_id'];
						$params[$key]['rma_type'] = $rmaType;

						// Handle "Other" reason
						if($data['reason'][$key] == 'Other' || strtolower($data['reason'][$key]) == 'other') {
							if(empty($data['reason_other_text'][$key])) {
								throw new \Magento\Framework\Exception\LocalizedException(
									__('Please specify the reason when selecting "Other".')
								);
							}
							$params[$key]['reason_other_text'] = $data['reason_other_text'][$key];
						}

						// Handle "Other" condition
						if($data['condition'][$key] == 'Other' || strtolower($data['condition'][$key]) == 'other') {
							if(empty($data['condition_other_text'][$key])) {
								throw new \Magento\Framework\Exception\LocalizedException(
									__('Please specify the condition when selecting "Other".')
								);
							}
							$params[$key]['condition_other_text'] = $data['condition_other_text'][$key];
						}

						// Handle uploaded images
						if(isset($data['images'][$key]) && !empty($data['images'][$key])) {
							$images = $data['images'][$key];
							// Validate image count (min 1, max 3)
							$imageCount = count($images);
							if($imageCount < 1 || $imageCount > 3) {
								throw new \Magento\Framework\Exception\LocalizedException(
									__('Please upload between 1 and 3 images.')
								);
							}
							$params[$key]['images'] = json_encode($images);
						}
					}
				}
				
				// Validate all selected items have required fields
				foreach ($params as $key => $param) {
					if($param['qty_return'] == ""){
						throw new \Magento\Framework\Exception\LocalizedException(
							__('Please enter quantity for all selected products.')
						);
					}
					if($param['reason'] == ""){
						throw new \Magento\Framework\Exception\LocalizedException(
							__('Please select reason for all selected products.')
						);
					}
					if($param['condition'] == ""){
						throw new \Magento\Framework\Exception\LocalizedException(
							__('Please select condition for all selected products.')
						);
					}
				}
			} else {
				throw new \Magento\Framework\Exception\LocalizedException(
					__('Please select at least one item.')
				);
			}

			// Create RMA order record
			$rmaorderModel = $this->rmaorder->create();
			$rmaorderModel->setOrderId($data['order_id'])
				->setStatus('Pending')
				->setRmaType($rmaType)
				->save();

			// Create RMA items
			foreach ($params as $key => $return) {
				$rmaModel = $this->_rma->create();
				$rmaModel->setData($return)->save();
			}
			
			$this->messageManager->addSuccess(
				__('Your %1 request has been submitted successfully.', ucfirst($rmaType))
			);
			$resultRedirect->setPath('sales/order/history');
			
		} catch (\Magento\Framework\Exception\LocalizedException $e) {
			$this->messageManager->addErrorMessage($e->getMessage());
			$resultRedirect->setPath('*/*/generate', ['id' => $data['order_id']]);
        } catch(\Exception $e){
			$this->messageManager->addException($e, 
				__('Something went wrong while saving your request.')
			);
			$resultRedirect->setPath('*/*/generate', ['id' => $data['order_id']]);
		}
		
		return $resultRedirect;
	}
}