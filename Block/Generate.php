<?php
namespace Krishaweb\Rma\Block;

class Generate extends \Magento\Framework\View\Element\Template
{
	public function __construct(
		\Magento\Framework\View\Element\Template\Context $context,
		\Magento\Sales\Model\Order $order,
		\Magento\Catalog\Model\Product $product,
		\Krishaweb\Rma\Model\Rma $rma,
		\Krishaweb\Rma\Model\Order $rmaorder,
		\Magento\Catalog\Model\ResourceModel\Eav\Attribute $attributeobj,
		\Magento\Catalog\Block\Product\ImageBuilder $_imageBuilder,
		\Krishaweb\Rma\Model\ReasonFactory $modelReasonFactory,
		\Krishaweb\Rma\Model\ConditionFactory $modelConditionFactory,
		\Krishaweb\Rma\Model\RmaFactory $modelRmaFactory,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		array $data = []
	){
		$this->order = $order;
		$this->rma = $rma;
		$this->rmaorder = $rmaorder;
		$this->product = $product;
		$this->_attributeobj = $attributeobj;
		$this->_imageBuilder = $_imageBuilder;
		$this->_modelReasonFactory = $modelReasonFactory;
		$this->_modelConditionFactory = $modelConditionFactory;
		$this->_modelRmaFactory = $modelRmaFactory;
		$this->scopeConfig = $scopeConfig;
		parent::__construct($context, $data);
	}

	public function getImage($product, $imageId, $attributes = [])
    {
        return $this->_imageBuilder->setProduct($product)
            ->setImageId($imageId)
            ->setAttributes($attributes)
            ->create();
    }

	public function getOrderData($id){
		$order=$this->order->load($id);
		$orderItems = $order->getItems();
		$orderInfo = array();

		foreach ($orderItems as $key => $orderItem) {
			if($orderItem->getProductType() == 'simple'){
				
				$orderInfo[$orderItem->getId()]['id'] = $orderItem->getId();
				$orderInfo[$orderItem->getId()]['product_id'] = $orderItem->getProductId();
				$orderInfo[$orderItem->getId()]['qty'] = $orderItem->getQtyOrdered();
				$product = $this->product->load($orderItem->getProductId());
				$orderInfo[$orderItem->getId()]['img_url'] = $this->getImage($product, 'product_small_image')->getImageUrl();
				$orderInfo[$orderItem->getId()]['name'] = $orderItem->getName();
				
				if(array_key_exists('super_attribute', $orderItem->getProductOptions()['info_buyRequest'])){
					foreach ($orderItem->getProductOptions()['info_buyRequest']['super_attribute'] as $key => $attr) {
						$attribute = $this->_attributeobj->load($key);
						$attributeCode = $attribute->getAttributeCode();
						$attributeLabel = $attribute->getFrontendLabel();
						$optionLabel = $attribute->getSource()->getOptionText($attr);

						$orderInfo[$orderItem->getId()]['options'][$attributeCode]['attribute']['id'] = $key;
						$orderInfo[$orderItem->getId()]['options'][$attributeCode]['attribute']['label'] = $attributeLabel;
						$orderInfo[$orderItem->getId()]['options'][$attributeCode]['value']['id'] = $attr;
						$orderInfo[$orderItem->getId()]['options'][$attributeCode]['value']['label'] = $optionLabel;
					}
				}
			}
		}
		return $orderInfo;
	}

	public function getReasons($rmaType = 'exchange'){
		$reasonModel = $this->_modelReasonFactory->create();
		return $reasonModel->getCollection()
			->addFieldToFilter('status', array('eq' => 1))
			->addFieldToFilter('rma_type', array('eq' => $rmaType));
	}
	
	public function getConditions($rmaType = 'exchange'){
		$conditionModel = $this->_modelConditionFactory->create();
		return $conditionModel->getCollection()
			->addFieldToFilter('status', array('eq' => 1))
			->addFieldToFilter('rma_type', array('eq' => $rmaType));
	}
	
	public function checkRmaAvailable($item_id){
		$_modelRmaFactory = $this->_modelRmaFactory->create();
		return $_modelRmaFactory->getCollection()->addFieldToFilter('item_id',array('eq'=>$item_id));
	}
	
	public function getAvailableRma($item_id){
		$_modelRmaFactory = $this->_modelRmaFactory->create();
		$rmaId = $_modelRmaFactory->getCollection()->addFieldToFilter('item_id',array('eq'=>$item_id))->getFirstItem()->getRmaId();
		return $this->rma->load($rmaId);
	}

	public function isRmaAllowed($orderId){
		$order = $this->order->load($orderId);
		
		// Check if order status is complete
		if($order->getStatus() != 'complete'){
			return ['allowed' => false, 'message' => __('RMA is only allowed for completed orders.')];
		}

		// Get delivery date (using order completed_at or shipped_at)
		$deliveryDate = $order->getUpdatedAt(); // You may need to adjust this based on your delivery tracking
		
		// Get configured days limit
		$daysLimit = $this->scopeConfig->getValue(
			'rma/options/rma_days_limit',
			\Magento\Store\Model\ScopeInterface::SCOPE_STORE
		);

		if(!$daysLimit){
			$daysLimit = 14; // Default 14 days
		}

		// Calculate days difference
		$deliveryTimestamp = strtotime($deliveryDate);
		$currentTimestamp = time();
		$daysDiff = floor(($currentTimestamp - $deliveryTimestamp) / (60 * 60 * 24));

		if($daysDiff > $daysLimit){
			return [
				'allowed' => false, 
				'message' => __('RMA request period has expired. You can request RMA within %1 days of delivery.', $daysLimit)
			];
		}

		return ['allowed' => true, 'message' => ''];
	}

	public function getRmaDaysLimit(){
		return $this->scopeConfig->getValue(
			'rma/options/rma_days_limit',
			\Magento\Store\Model\ScopeInterface::SCOPE_STORE
		) ?: 14;
	}

	/**
	 * Get RMA image upload directory
	 */
	public function getRmaImageUploadUrl()
	{
		return $this->getUrl('rma/rma/upload');
	}
}