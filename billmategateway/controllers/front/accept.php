<?php
/**
 * Created by PhpStorm.
 * User: jesper
 * Date: 15-03-17
 * Time: 15:32
 */

class BillmatepgatewayAcceptModuleFrontController extends ModuleFrontController {

	public $module;
	protected $method;
	protected $billmate;
	protected $cartId;

	/**
	 * A recursive method which delays order-confirmation until order is processed
	 * @param $cartId Cart Id
	 * @return integer OrderId
	 */

	private function checkOrder($cartId)
	{
		$order = Order::getOrderByCartId($cartId);
		if(!$order){
			sleep(1);
			$this->checkOrder($cartId);
		} else {
			return $order;
		}
	}

	public function postProcess()
	{

		$this->method = Tools::getValue( 'method' );
		$eid          = Configuration::get( 'BILLMATE_ID' );
		$secret       = Configuration::get( 'BILLMATE_SECRET' );
		$ssl          = true;
		$debug        = false;
		require_once( _PS_MODULE_DIR_.'billmategateway/methods/'.'Billmate'.ucfirst($this->method).'.php');

		$class        = 'Billmate' . ucfirst( $this->method );
		$this->module = new $class;

		$testmode = $this->module->testMode;

		$this->billmate = Common::getBillmate( $eid, $secret, $testmode, $ssl, $debug );
		$_POST = !empty($_POST) ? $_POST : $_GET;
		$data = $this->billmate->verify_hash($_POST);
		$order        = $data['orderid'];
		$order        = explode( '-', $order );
		$this->cartId = $order[0];
		$this->context->cart = new Cart( $this->cartId );
		$customer            = new Customer( $this->context->cart->id_customer );

		if(!isset($data['code']) && !isset($data['error']))
		{
			$lockfile = _PS_CACHE_DIR_.$_POST['order_id'];
			$processing = file_exists($lockfile);
			if ($this->context->cart->orderExists() || $processing)
			{
				$orderId = 0;
				if($processing){
					$orderId = $this->checkOrder($this->context->cart->id);
				} else {
					$orderId = Order::getOrderByCartId($this->context->cart->id);

				}


				Tools::redirectLink(__PS_BASE_URI__.'order-confirmation.php?key='.$customer->secure_key.'&id_cart='.(int)$this->context->cart->id.'&id_module='.(int)$this->module->id.'&id_order='.(int)$orderId);
				die;
			}


			file_put_contents($lockfile, 1);


			$total = $this->context->cart->getOrderTotal(true, Cart::BOTH);
			$extra = array('transaction_id' => $data['number']);
			$status = ($this->method == 'cardpay') ? Configuration::get('BCARDPAY_ORDER_STATUS') : Configuration::get('BBANKPAY_ORDER_STATUS');
			$this->module->validateOrder((int)$this->context->cart->id,$status, $total, $this->module->displayName, null, $extra, null, false, $customer->secure_key);

			if((Configuration::get('BCARDPAY_AUTHORIZATION_METHOD') != 'sale' && $this->method == 'cardpay') || $this->method == 'bankpay')
			{
				$values['PaymentData'] = array(
					'number'  => $data['number'],
					'orderid' => ( Configuration::get( 'BILLMATE_SEND_REFERENCE' ) == 1 ) ? $this->module->currentOrderReference : $this->module->currentOrder
				);
				$this->billmate->updatePayment($values);
			}
			unlink($lockfile);
			Tools::redirectLink(__PS_BASE_URI__.'order-confirmation.php?key='.$customer->secure_key.'&id_cart='.(int)$this->context->cart->id.'&id_module='.(int)$this->module->id.'&id_order='.(int)$this->module->currentOrder);
			die();
		}
	}

}