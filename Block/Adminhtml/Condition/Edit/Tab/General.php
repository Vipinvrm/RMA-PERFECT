<?php

namespace Krishaweb\Rma\Block\Adminhtml\Condition\Edit\Tab;
 
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;
use Magento\Cms\Model\Wysiwyg\Config;
 
class General extends Generic implements TabInterface {
	protected $_wysiwygConfig;
	protected $_newsStatus;
	
	public function __construct(
		Context $context,
		Registry $registry,
		FormFactory $formFactory,
		Config $wysiwygConfig,
		array $data = []
	) {
		$this->_wysiwygConfig = $wysiwygConfig;
		parent::__construct($context, $registry, $formFactory, $data);
	}
	
	protected function _prepareForm(){
		$model = $this->_coreRegistry->registry('condition_item');
		$form = $this->_formFactory->create();
 
		$fieldset = $form->addFieldset(
			'base_fieldset',
			['legend' => __('General')]
		);
 
		if ($model->getId()) {
			$fieldset->addField(
				'condition_id',
				'hidden',
				['name' => 'condition_id']
			);
		}
		
		$fieldset->addField(
			'title',
			'text',
			[
				'name' => 'title',
				'label' => __('Condition Title'),
				'title' => __('Condition Title'),
				'required' => true,
				'class' => 'required-entry'
			]
		);

		$fieldset->addField(
			'rma_type',
			'select',
			[
				'name' => 'rma_type',
				'label' => __('RMA Type'),
				'title' => __('RMA Type'),
				'required' => true,
				'values' => [
					['value' => 'exchange', 'label' => __('Exchange')],
					['value' => 'return', 'label' => __('Return')]
				]
			]
		);

		$fieldset->addField(
			'status',
			'select',
			[
				'name' => 'status',
				'label' => __('Status'),
				'title' => __('Status'),
				'required' => true,
				'values' => [
					['value' => 1, 'label' => __('Enabled')],
					['value' => 0, 'label' => __('Disabled')]
				]
			]
		);
		
		$data = $model->getData();
		$form->setValues($data);
		$this->setForm($form);
 
		return parent::_prepareForm();
	}
	
	public function getTabLabel(){
		return __('Condition Information');
	}
	
	public function getTabTitle(){
		return __('Condition Information');
	}
	
	public function canShowTab(){
		return true;
	}
	
	public function isHidden(){
		return false;
	}
}