<?php

namespace Krishaweb\Rma\Block\Adminhtml\Status\Edit\Tab;
 
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
		$model = $this->_coreRegistry->registry('status_item');
		$form = $this->_formFactory->create();
 
		$fieldset = $form->addFieldset(
			'base_fieldset',
			['legend' => __('General')]
		);
 
		if ($model->getId()) {
			$fieldset->addField(
				'status_id',
				'hidden',
				['name' => 'status_id']
			);
		}
		
		$fieldset->addField(
			'title',
			'text',
			[
				'name' => 'title',
				'label' => __('Status Title'),
				'title' => __('Status Title'),
				'required' => true,
				'class' => 'required-entry',
				'note' => __('Example: Approved, Rejected, Pending, Processing')
			]
		);
		
		$data = $model->getData();
		$form->setValues($data);
		$this->setForm($form);
 
		return parent::_prepareForm();
	}
	
	public function getTabLabel(){
		return __('Status Information');
	}
	
	public function getTabTitle(){
		return __('Status Information');
	}
	
	public function canShowTab(){
		return true;
	}
	
	public function isHidden(){
		return false;
	}
}