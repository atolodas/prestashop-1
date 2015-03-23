<?php
/**
 * Created by PhpStorm.
 * User: jesper
 * Date: 15-03-16
 * Time: 13:14
 */

	require_once(_PS_MODULE_DIR_.'/billmategateway/library/Common.php');
	require_once(_PS_MODULE_DIR_.'/billmategateway/library/pclasses.php');

class BillmateGateway extends PaymentModule{

	protected $allowed_currencies;
	protected $postValidations;
	protected $postErrors;

	public function __construct()
	{
		$this->name = 'billmategateway';
		$this->moduleName='billmategateway';
		$this->tab = 'payments_gateways';
		$this->version = '2.0';
		$this->author  = 'Billmate AB';

		$this->currencies = true;
		$this->currencies_mode = 'checkbox';

		parent::__construct();
		$this->core = null;
		$this->billmate = null;
		$this->country = null;
		$this->limited_countries = array('se', 'onl', 'dk', 'no', 'fi','gb','us'); //, 'no', 'fi', 'dk', 'de', 'nl'
		$this->verifyEmail = $this->l('My email %1$s is accurate and can be used for invoicing.').'<a id="terms" style="cursor:pointer!important"> '.$this->l('I confirm the terms for invoice payment').'</a>';
		/* The parent construct is required for translations */
		$this->page = basename(__FILE__, '.php');
		$this->displayName = $this->l('Billmate Payment Gateway');
		$this->description = $this->l('Accepts payments by Billmate');
		$this->confirmUninstall = $this->l(
			'Are you sure you want to delete your settings?'
		);
		$this->billmate_merchant_id = Configuration::get('BILLMATE_ID');
		$this->billmate_secret = Configuration::get('BILLMATE_SECRET');
		$installedVersion = Configuration::get('BILLMATE_VERSION');
		// Is the module installed and need to be updated?
		if($installedVersion && version_compare($installedVersion,$this->version,'<'))
			$this->update();

		$this->context->smarty->assign('base_dir', __PS_BASE_URI__);
	}

	public function getContent()
	{
		$html = '';

		if (!empty($_POST) && Tools::getIsset('billmateSubmit'))
		{
			$this->_postValidation();
			if (count($this->postValidations))
				$html .= $this->displayValidations();

			if (count($this->postErrors))
				$html .= $this->displayErrors();
		}

		$html .= $this->displayAdminTemplate();
		return $html;
	}

	public function displayAdminTemplate()
	{
		$tab[] = array(
					'title' => $this->l('Settings'),
					'content' => $this->getGeneralSettings(),
					'icon' => '../modules/'.$this->moduleName.'/images/icon-settings.gif',
					'tab' => 1,
					'selected' => true,

		);
		$i = 2;
		foreach($this->getMethodSettings() as $setting){


			$tab[] = array(
					'title' => $setting['title'],
					'content' => $setting['content'],
					'icon' => '../modules/'.$this->moduleName.'/images/icon-settings.gif',
					'tab' => $i++,
					'selected' => false
			);

		}
		$this->smarty->assign('FormCredential',	'./index.php?tab=AdminModules&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules').'&tab_module='.$this->tab.'&module_name='.$this->name);
		$this->smarty->assign('tab', $tab);
		$this->smarty->assign('moduleName', $this->moduleName);
		$this->smarty->assign($this->moduleName.'Logo', '../modules/'.$this->moduleName.'/img/logo.png');
		$this->smarty->assign('js', array('../modules/'.$this->moduleName.'/views/js/billmate.js'));
		$this->smarty->assign($this->moduleName.'css', '../modules/billmatebank/css/billmate.css');

		return $this->display(__FILE__,'admin.tpl');
	}


	/**
	 * The method takes care of Validation and persisting the posted data
	 */
	public function _postValidation()
	{
		// General Settings

		$billmateId = Tools::getValue( 'billmateId' );
		$billmateSecret = Tools::getValue( 'billmateSecret' );

		if($this->validateCredentials($billmateId,$billmateSecret))
		{
			Configuration::updateValue('BILLMATE_ID', $billmateId);
			Configuration::updateValue('BILLMATE_SECRET', $billmateSecret);
		}
		Configuration::updateValue('BILLMATE_SEND_REFERENCE',Tools::getIsset('sendOrderReference')? 1 : 0);

		// Bankpay Settings
		Configuration::updateValue('BBANKPAY_ENABLED', (Tools::getIsset('bankpayActivated')) ? 1 : 0);
		Configuration::updateValue('BBANKPAY_MOD', (Tools::getIsset('bankpayTestmode')) ? 1 : 0);
		Configuration::updateValue('BBANKPAY_AUTHORIZATION_METHOD',Tools::getValue('bankpayAuthorization'));
		Configuration::updateValue('BBANKPAY_ORDER_STATUS',Tools::getValue('bankpayBillmateOrderStatus'));
		Configuration::updateValue('BBANKPAY_MIN_VALUE',Tools::getValue('bankpayBillmateMinimumValue'));
		Configuration::updateValue('BBANKPAY_MAX_VALUE',Tools::getValue('bankpayBillmateMaximumValue'));

		// Cardpay Settings
		Configuration::updateValue('BCARDPAY_ENABLED', (Tools::getIsset('cardpayActivated')) ? 1 : 0);
		Configuration::updateValue('BCARDPAY_MOD', (Tools::getIsset('cardpayTestmode')) ? 1 : 0);
		Configuration::updateValue('BCARDPAY_3DSECURE', (Tools::getIsset('cardpay3dsecure')) ? 1 : 0);
		Configuration::updateValue('BCARDPAY_PROMPT', (Tools::getIsset('cardpayPromptname')) ? 1 : 0);
		Configuration::updateValue('BCARDPAY_ORDER_STATUS',Tools::getValue('cardpayBillmateOrderStatus'));
		Configuration::updateValue('BCARDPAY_AUTHORIZATION_METHOD',Tools::getValue('cardpayAuthorization'));
		Configuration::updateValue('BCARDPAY_MIN_VALUE',Tools::getValue('cardpayBillmateMinimumValue'));
		Configuration::updateValue('BCARDPAY_MAX_VALUE',Tools::getValue('cardpayBillmateMaximumValue'));

		// Invoice Settings
		Configuration::updateValue('BINVOICE_ENABLED', (Tools::getIsset('invoiceActivated')) ? 1 : 0);
		Configuration::updateValue('BINVOICE_MOD', (Tools::getIsset('invoiceTestmode')) ? 1 : 0);
		Configuration::updateValue('BINVOICE_FEE',Tools::getValue('invoiceFee'));
		Configuration::updateValue('BINVOICE_FEE_TAX',Tools::getValue('invoiceFeeTax'));
		Configuration::updateValue('BINVOICE_ORDER_STATUS',Tools::getValue('invoiceBillmateOrderStatus'));
		Configuration::updateValue('BINVOICE_MIN_VALUE',Tools::getValue('invoiceBillmateMinimumValue'));
		Configuration::updateValue('BINVOICE_MAX_VALUE',Tools::getValue('invoiceBillmateMaximumValue'));

		// partpay Settings
		Configuration::updateValue('BPARTPAY_ENABLED', (Tools::getIsset('partpayActivated')) ? 1 : 0);
		Configuration::updateValue('BPARTPAY_MOD', (Tools::getIsset('partpayTestmode')) ? 1 : 0);
		Configuration::updateValue('BPARTPAY_ORDER_STATUS',Tools::getValue('partpayBillmateOrderStatus'));
		Configuration::updateValue('BPARTPAY_MIN_VALUE',Tools::getValue('partpayBillmateMinimumValue'));
		Configuration::updateValue('BPARTPAY_MAX_VALUE',Tools::getValue('partpayBillmateMaximumValue'));
		if(Configuration::get('BPARTPAY_ENABLED') == 1)
		{
			$pclasses = new pClasses();
			$languages = Language::getLanguages();
			foreach ($languages as $language)
				$pclasses->Save($this->billmate_merchant_id, $this->billmate_secret, 'se',$language['iso_code'],'SEK');
		}
	}

	public function validateCredentials($eid, $secret)
	{
		$billmate = Common::getBillmate($eid,$secret,false);

		$data['PaymentData'] = array(
			'currency' => 'SEK',
			'language' => 'sv',
			'country' => 'se'
		);
		$result = $billmate->getPaymentplans($data);
		if (isset($result['code']) && $result['code'] == '9010' || $result['code'] == '9013')
		{
			$this->postErrors[] = $result['message'];
			return false;
		}
		return true;
	}

	public function displayValidations()
	{
		return '';
	}

	public function displayErrors()
	{
		$this->smarty->assign('billmateErrors',$this->postErrors);
		return $this->display(__FILE__,'error.tpl');
	}

	public function install()
	{
		if(!parent::install()){
			return false;
		}
		// Inactivate status for modules
		Configuration::updateValue('BPARTPAY_ENABLED',0);
		Configuration::updateValue('BINVOICE_ENABLED',0);
		Configuration::updateValue('BCARDPAY_ENABLED',0);
		Configuration::updateValue('BBANKPAY_ENABLED',0);

		Configuration::updateValue('BILLMATE_VERSION',$this->version);
		require_once(_PS_MODULE_DIR_.'/billmategateway/setup/InitInstall.php');
		$installer = new InitInstall(Db::getInstance());
		$installer->install();

		if(!$this->registerHooks()){
			return false;
		}
		return true;
	}

	/**
	 * Function to update if module is installed.
	 * Caution need to implement SetupFileInterface to make sure the install function is there
	 * */
	public function update()
	{
		$filesArrSorted = array();

		$files = new ArrayObject(iterator_to_array(new FilesystemIterator(_PS_MODULE_DIR_.'/billmategateway/setup/updates',FilesystemIterator::SKIP_DOTS)));
		$files->natsort();
		if(count($files) == 0){
			Configuration::updateValue($this->version);
			return;
		}
		foreach ($files as $file)
		{
			$class = $file->getBasename('.php');

			include_once($file->getPathname());

			$updater = new $class();
			$updater->install();
		}

	}

	public function registerHooks()
	{
		return $this->registerHook('displayPayment') &&
	           $this->registerHook('payment') &&
	           $this->registerHook('paymentReturn') &&
	           $this->registerHook('orderConfirmation') &&
	           $this->registerHook('actionOrderStatusUpdate') &&
			   $this->registerHook('displayBackOfficeHeader') &&
			   $this->registerHook('displayAdminOrder') &&
			   $this->registerHook('displayPDFInvoice');
	}

	/**
	 * This hook displays our invoice Fee in Admin Orders below the client information
	 *
	 * @param $hook
	 */
	public function hookDisplayAdminOrder($hook)
	{
		$order_id = 0;
		if (array_key_exists('id_order',$hook))
			$order_id = (int) $hook['id_order'];

		$order = new Order($order_id);

		$payment = OrderPayment::getByOrderId($order_id);
		if($order->module != 'billmateinvoice')
			return;
		$invoiceFee = Configuration::get('BINVOICE_FEE');
		if($invoiceFee == 0){
			return;
		}
		$invoiceFeeTax = Configuration::get('BINVOICE_FEE_TAX');
		if(!$invoiceFee){
			$invoiceFeeTaxRate = 0;
		}
		$tax = new Tax($invoiceFeeTax);
		$taxCalculator = new TaxCalculator(array($tax));

		$taxAmount = $taxCalculator->getTaxesAmount($invoiceFee);

		$totalFee = $invoiceFee + $taxAmount[1];

		$this->smarty->assign('invoiceFeeIncl',$totalFee);
		$this->smarty->assign('invoiceFeeTax',$taxAmount[1]);

		return $this->display(__FILE__,'invoicefee.tpl');
	}

	public function hookDisplayBackOfficeHeader()
	{
		if (isset($this->context->cookie->error) && strlen($this->context->cookie->error) > 2)
		{
			if (get_class($this->context->controller) == "AdminOrdersController")
			{
				$this->context->controller->errors[] = $this->context->cookie->error;
				unset($this->context->cookie->error);
				unset($this->context->cookie->error_orders);
			}
		}
		if(isset($this->context->cookie->diff) && strlen($this->context->cookie->diff) > 2){
			if (get_class($this->context->controller) == "AdminOrdersController")
			{
				$this->context->controller->errors[] = $this->context->cookie->diff;
				unset($this->context->cookie->diff);
				unset($this->context->cookie->diff_orders);
			}
		}
		if (isset($this->context->cookie->api_error) && strlen($this->context->cookie->api_error) > 2){
			if (get_class($this->context->controller) == "AdminOrdersController")
			{
				$this->context->controller->errors[] = $this->context->cookie->api_error;
				unset($this->context->cookie->api_error);
				unset($this->context->cookie->api_error_orders);
			}
		}
		if (isset($this->context->cookie->information) && strlen($this->context->cookie->information) > 2)
		{
			if (get_class($this->context->controller) == "AdminOrdersController")
			{
				$this->context->controller->warnings[] = $this->context->cookie->information;
				unset($this->context->cookie->information);
				unset($this->context->cookie->information_orders);
			}
		}
		if (isset($this->context->cookie->confirmation) && strlen($this->context->cookie->confirmation) > 2)
		{
			if (get_class($this->context->controller) == "AdminOrdersController")
			{
				$this->context->controller->confirmations[] = $this->context->cookie->confirmation;
				unset($this->context->cookie->confirmation);
				unset($this->context->cookie->confirmation_orders);
			}
		}
		if (Tools::getValue('controller') == 'AdminModules' && Tools::getValue('configure') == 'billmategateway')
		{
			$html = '';
			$html = '<link href="/modules/billmategateway/views/css/billmate.css" type="text/css" rel="stylesheet"/>';
			return $html;
		}


	}

	public function hookActionOrderStatusUpdate($params)
	{
		$orderStatus = Configuration::get('BILLMATE_ACTIVATE_STATUS');
		$activate = Configuration::get('BILLMATE_ACTIVATE');
		if ($activate && $orderStatus)
		{
			$order_id = $params['id_order'];

			$id_status = $params['newOrderStatus']->id;
			$order = new Order($order_id);

			$payment = OrderPayment::getByOrderId($order_id);
			$orderStatus = unserialize($orderStatus);
			$modules = array('billmatecardpay', 'billmatebankpay', 'billmateinvoice', 'billmatepartpay');

			if (in_array($order->module,$modules) && in_array($id_status,$orderStatus) && $this->getMethodInfo($order->module,'authorization_method') != 'sale')
			{

				$testMode = $this->getMethodInfo( $order->module, 'testMode' );
				$billmate = Common::getBillmate($this->billmate_merchant_id,$this->billmate_secret, $testMode );
				$paymentInfo = $billmate->getPaymentinfo(array('number' => $payment[0]->transaction_id));
				$paymentStatus = Tools::strtolower( $paymentInfo['PaymentData']['status'] );
				if ($paymentStatus == 'created')
				{
					$total = $paymentInfo['Cart']['Total']['withtax'] / 100;
					$orderTotal = $order->getTotalPaid();
					$diff = $total - $orderTotal;
					$diff = abs($diff);
					if (diff < 1)
					{
						$result = $billmate->activatePayment(array('PaymentData' => array('number' => $payment[0]->transaction_id)));

						if (isset($result['code']))
						{
							$this->context->cookie->error = (isset($result['message'])) ? utf8_encode($result['message']) : utf8_encode($result);
							$this->context->cookie->error_orders = isset($this->context->cookie->error_orders) ? $this->context->cookie->error_orders . ', ' . $order_id : $order_id;
						}
						$this->context->cookie->confirmation = !isset($this->context->cookie->confirmation_orders) ? sprintf($this->l('Order %s has been activated through Billmate.'), $order_id) . ' (<a target="_blank" href="http://online.billmate.se/faktura">' . $this->l('Open Billmate Online') . '</>)' : sprintf($this->l('The following orders has been activated through Billmate: %s'), $this->context->cookie->confirmation_orders . ', ' . $order_id) . ' (<a target="_blank" href="http://online.billmate.se">' . $this->l('Open Billmate Online') . '</a>)';
						$this->context->cookie->confirmation_orders = isset($this->context->cookie->confirmation_orders) ? $this->context->cookie->confirmation_orders . ', ' . $order_id : $order_id;
					}
					elseif (isset($resultCheck['code']))
					{
						if ($resultCheck['code'] == 5220) {
							$mode                             = $testMode ? 'test' : 'live';
							$this->context->cookie->api_error = ! isset( $this->context->cookie->api_error_orders ) ? sprintf( $this->l('Order %s failed to activate through Billmate. The order does not exist in Billmate Online. The order exists in (%s) mode however. Try changing the mode in the modules settings.'), $order_id, $mode ) . ' (<a target="_blank" href="http://online.billmate.se">' . $this->l('Open Billmate Online') . '</a>)' : sprintf( $this->l('The following orders failed to activate through Billmate: %s. The orders does not exist in Billmate Online. The orders exists in (%s) mode however. Try changing the mode in the modules settings.'), $this->context->cookie->api_error_orders, '. ' . $order_id, $mode ) . ' (<a target="_blank" href="http://online.billmate.se">' . $this->l('Open Billmate Online') . '</a>)';
						}
						else
							$this->context->cookie->api_error = $resultCheck['message'];

						$this->context->cookie->api_error_orders = isset($this->context->cookie->api_error_orders) ? $this->context->cookie->api_error_orders.', '.$order_id : $order_id;

					}
					else
					{
						$this->context->cookie->diff = !isset($this->context->cookie->diff_orders) ? sprintf($this->l('Order %s failed to activate through Billmate. The amounts don\'t match: %s, %s. Activate manually in Billmate Online.'),$order_id, $orderTotal, $total).' (<a target="_blank" href="http://online.billmate.se">'.$this->l('Open Billmate Online').'</a>)' : sprintf($this->l('The following orders failed to activate through Billmate: %s. The amounts don\'t match. Activate manually in Billmate Online.'),$this->context->cookie->diff_orders.', '.$order_id) . ' (<a target="_blank" href="http://online.billmate.se">' . $this->l('Open Billmate Online') . '</a>)';
						$this->context->cookie->diff_orders = isset($this->context->cookie->diff_orders) ? $this->context->cookie->diff_orders.', '.$order_id : $order_id;
					}
				}
				elseif($paymentStatus == 'paid') {
					$this->context->cookie->information = !isset($this->context->cookie->information_orders) ? sprintf($this->l('Order %s is already activated through Billmate.'),$order_id).' (<a target="_blank" href="http://online.billmate.se">'.$this->l('Open Billmate Online').'</a>)' : sprintf($this->l('The following orders has already been activated through Billmate: %s'),$this->context->cookie->information_orders.', '.$order_id).' (<a target="_blank" href="http://online.billmate.se">'.$this->l('Open Billmate Online').'</a>)';
					$this->context->cookie->information_orders = isset($this->context->cookie->information_orders) ? $this->context->cookie->information_orders . ', ' . $order_id : $order_id;
				}
				else {
					$this->context->cookie->error = !isset($this->context->cookie->error_orders) ? sprintf($this->l('Order %s failed to activate through Billmate.'),$order_id).' (<a target="_blank" href="http://online.billmate.se">'.$this->l('Open Billmate Online').'</a>)' : sprintf($this->l('The following orders failed to activate through Billmate: %s.'),$this->context->cookie->error_orders.', '.$order_id).' (<a target="_blank" href="http://online.billmate.se">'.$this->l('Open Billmate Online').'</a>)';
					$this->context->cookie->error_orders = isset($this->context->cookie->error_orders) ? $this->context->cookie->error_orders . ', ' . $order_id : $order_id;
				}
			}

		}
		else
			return;
	}

	public function hookDisplayPayment($params)
	{

		return $this->hookPayment($params);
	}

	public function hookPayment($params)
	{


		$methods = $this->getMethods($params['cart']);


		$this->smarty->assign(
			array(
				'var' => array('path' => $this->_path,
				               'this_path_ssl' => (_PS_VERSION_ >= 1.4 ? Tools::getShopDomainSsl(true, true) : '' ).__PS_BASE_URI__.'modules/'.$this->moduleName.'/'),
				'methods' => $methods,
				'ps_version' => _PS_VERSION_
			)
		);

		return $this->display(__FILE__,'payment.tpl');

	}

	public function getFileName()
	{
		return __FILE__;
	}

	public function getMethods($cart)
	{
		$data = array();

		$methodFiles = new FilesystemIterator(_PS_MODULE_DIR_.'/billmategateway/methods',FilesystemIterator::SKIP_DOTS);

		foreach($methodFiles as $file){
			$class = $file->getBasename('.php');


			include_once($file->getPathname());

			$method = new $class();
			$result = $method->getPaymentInfo($cart);
			if(!$result){
				continue;
			}
			$data[] = $result;

		}

		return $data;
	}

	public function getMethodInfo($name,$key){
		$data = array();
		$methodFiles = new FilesystemIterator(_PS_MODULE_DIR_.'billmategateway/methods',FilesystemIterator::SKIP_DOTS);
		foreach($methodFiles as $file){
			$class = $file->getBasename('.php');
			include_once($file->getPathname());
			$method = new $class();
			if($method->name == $name){
				if(property_exists($class,$key))
					return $method->{$key};
			}
		}
	}

	public function getMethodSettings()
	{
		$data = array();

		$methodFiles = new FilesystemIterator(_PS_MODULE_DIR_.'/billmategateway/methods',FilesystemIterator::SKIP_DOTS);

		foreach($methodFiles as $file){
			$class = $file->getBasename('.php');


			include_once($file->getPathname());

			$method = new $class();
			$result = $method->getSettings();
			if(!$result){
				continue;
			}
			$this->smarty->assign(array('settings' => $result, 'moduleName' => $method->displayName));
			$data[$method->name]['content'] = $this->display(__FILE__,'settings.tpl');
			$data[$method->name]['title'] = $method->displayName;
		}

		return $data;
	}

	public function getGeneralSettings(){

		$statuses = OrderState::getOrderStates((int)$this->context->language->id);
		$statuses_array = array();
		foreach ($statuses as $status)
			$statuses_array[$status['id_order_state']] = $status['name'];
		$settings['billmateid'] = array(
			'name' => 'billmateId',
			'required' => true,
			'type' => 'text',
			'label' => $this->l('Billmate ID'),
			'desc' => $this->l('The Billmate ID from Billmateonline'),
			'value' => Configuration::get('BILLMATE_ID'),
		);

		$settings['billmatesecret'] = array(
			'name' => 'billmateSecret',
			'required' => true,
			'type' => 'text',
			'label' => $this->l('Secret'),
			'desc' => $this->l('The secret key from Billmateonline'),
			'value' => Configuration::get('BILLMATE_SECRET')
		);

		$settings['sendorderreference'] = array(
			'name' => 'sendOrderReference',
			'required' => true,
			'type' => 'checkbox',
			'label' => $this->l('Send Reference'),
			'desc' => $this->l('Activate to Send Order reference instead of Order number'),
		    'value' => Configuration::get('BILLMATE_SEND_REFERENCE')
		);

		$activate_status      = Configuration::get( 'BILLMATE_ACTIVATE' );
		$settings['activate'] = array(
			'name' => 'activate',
			'required' => true,
			'type' => 'checkbox',
			'label' => $this->l('Activate Invoices'),
			'desc' => $this->l('Activate Invoices with a certain status in Billmate Online'),
			'value' => $activate_status
		);

		$settings['activateStatuses'] = array(
			'name' => 'activateStatuses',
			'id' => 'activation_options',
			'required' => true,
			'type' => 'multiselect',
			'label' => $this->l('Order statuses for automatic order activation in Billmate Online'),
			'desc' => '',
			'value' => (Tools::safeOutput(Configuration::get('BILLMATE_ACTIVATE_STATUS'))) ? unserialize(Configuration::get('BILLMATE_ACTIVATE_STATUS')) : 0,
			'options' => $statuses_array
		);
		$this->smarty->assign('activation_status',$activate_status);
		$this->smarty->assign(array('settings' => $settings, 'moduleName' => $this->l('Common Settings')));
		return $this->display(__FILE__,'settings.tpl');
	}

	public function hookPaymentReturn($params)
	{
		return $this->hookOrderConfirmation($params);
	}
	public function hookOrderConfirmation($params)
	{
		return $this->display(__FILE__,'orderconfirmation.tpl');
	}
}