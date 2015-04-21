<?php
	/**
	 * Created by PhpStorm.* User: jesper* Date: 15-03-17 * Time: 15:09
	 *
	 * @author    Jesper Johansson jesper@boxedlogistics.se
	 * @copyright Billmate AB 2015
	 * @license   OpenSource
	 */

	/*
	 * Class for BillmateCardpay related stuff
	 */

	class BillmateCardpay extends BillmateGateway {

		public function __construct()
		{
			parent::__construct();
			$this->name                 = 'billmatecardpay';
			$this->displayName          = $this->l('Billmate Cardpay');
			$this->testMode             = Configuration::get('BCARDPAY_MOD');
			$this->min_value            = Configuration::get('BCARDPAY_MIN_VALUE');
			$this->max_value            = Configuration::get('BCARDPAY_MAX_VALUE');
			$this->sort_order           = Configuration::get('BCARDPAY_SORTORDER');
			$this->limited_countries    = array('se');
			$this->allowed_currencies   = array('SEK','EUR');
			$this->authorization_method = Configuration::get('BCARDPAY_AUTHORIZATION_METHOD');
			$this->validation_controller = $this->context->link->getModuleLink('billmategateway', 'billmateapi', array('method' => 'cardpay'));
			$this->icon                 = file_exists(_PS_MODULE_DIR_.'billmategateway/views/img/'.Tools::strtolower($this->context->language->iso_code).'/card.png') ? 'billmategateway/views/img/'.Tools::strtolower($this->context->language->iso_code).'/card.png' : 'billmategateway/views/img/en/card.png';
		}

		/**
		 * Returns Payment info for appending in payment methods list
		 */
		public function getPaymentInfo($cart)
		{
			if (Configuration::get('BCARDPAY_ENABLED') == 0)
				return false;
			if ($this->min_value > $this->context->cart->getOrderTotal())
				return false;
			if ($this->max_value < $this->context->cart->getOrderTotal())
				return false;

			if(!in_array($this->context->currency->iso_code,$this->allowed_currencies))
				return false;

			return array(
				'sort_order' => $this->sort_order,
				'name'       => $this->displayName,
				'type'       => $this->name,
				'method' => 'cardpay',
				'controller' => $this->validation_controller,
				'icon'       => $this->icon
			);
		}

		public function getSettings()
		{
			$settings       = array();
			$statuses       = OrderState::getOrderStates((int)$this->context->language->id);
			$currency       = Currency::getCurrency((int)Configuration::get('PS_CURRENCY_DEFAULT'));
			$statuses_array = array();
			foreach ($statuses as $status)
				$statuses_array[$status['id_order_state']] = $status['name'];


			$settings['activated'] = array(
				'name'     => 'cardpayActivated',
				'required' => false,
				'type'     => 'checkbox',
				'label'    => $this->l('Enabled'),
				'desc'     => $this->l('Should Billmate Cardpay be Enabled'),
				'value'    => (Tools::safeOutput(Configuration::get('BCARDPAY_ENABLED'))) ? 1 : 0,

			);

			$settings['testmode'] = array(
				'name'     => 'cardpayTestmode',
				'required' => false,
				'type'     => 'checkbox',
				'label'    => $this->l('Test Mode'),
				'desc'     => $this->l('Enable Test Mode'),
				'value'    => (Tools::safeOutput(Configuration::get('BCARDPAY_MOD'))) ? 1 : 0
			);

			$settings['3dsecure']   = array(
				'name'  => 'cardpay3dsecure',
				'type'  => 'checkbox',
				'label' => $this->l('Enable 3D secure'),
				'desc'  => '',
				'value' => (Tools::safeOutput(Configuration::get('BCARDPAY_3DSECURE'))) ? 1 : 0
			);
			$settings['promptname'] = array(
				'name'  => 'cardpayPromptname',
				'type'  => 'checkbox',
				'label' => $this->l('Prompt Name'),
				'desc'  => '',
				'value' => (Tools::safeOutput(Configuration::get('BCARDPAY_PROMPT'))) ? 1 : 0
			);

			$settings['authorization'] = array(
				'name'    => 'cardpayAuthorization',
				'type'    => 'radio',
				'label'   => $this->l('Authorization Method'),
				'desc'    => '',
				'value' => Configuration::get('BCARDPAY_AUTHORIZATION_METHOD'),
				'options' => array(
					'authorize' => $this->l('Authorize'),
					'sale'      => $this->l('Sale')
				)
			);

			$settings['order_status']  = array(
				'name'     => 'cardpayBillmateOrderStatus',
				'required' => true,
				'type'     => 'select',
				'label'    => $this->l('Set Order Status'),
				'desc'     => $this->l(''),
				'value'    => (Tools::safeOutput(Configuration::get('BCARDPAY_ORDER_STATUS'))) ? Tools::safeOutput(Configuration::get('BCARDPAY_ORDER_STATUS')) : Tools::safeOutput(Configuration::get('PS_OS_PAYMENT')),
				'options'  => $statuses_array
			);
			$settings['minimum_value'] = array(
				'name'     => 'cardpayBillmateMinimumValue',
				'required' => false,
				'value'    => (float)Configuration::get('BCARDPAY_MIN_VALUE'),
				'type'     => 'text',
				'label'    => $this->l('Minimum Value ').' ('.$currency['sign'].')',
				'desc'     => $this->l(''),
			);
			$settings['maximum_value'] = array(
				'name'     => 'cardpayBillmateMaximumValue',
				'required' => false,
				'value'    => Configuration::get('BCARDPAY_MAX_VALUE') != 0 ? (float)Configuration::get('BCARDPAY_MAX_VALUE') : 99999,
				'type'     => 'text',
				'label'    => $this->l('Maximum Value ').' ('.$currency['sign'].')',
				'desc'     => $this->l(''),
			);
			$settings['sort'] = array(
				'name'     => 'cardpayBillmateSortOrder',
				'required' => false,
				'value'    => Configuration::get('BCARDPAY_SORTORDER'),
				'type'     => 'text',
				'label'    => $this->l('Sort Order'),
				'desc'     => $this->l(''),
			);

			return $settings;

		}
	}