<?php 
class ControllerExtensionPaymentPayWay extends Controller {

	public function index() {

		$data['button_confirm'] = $this->language->get('button_confirm');
		
		$this->load->model('checkout/order');
		
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		
		$data['action'] = $this->config->get('payment_payway_payserverurl') . "api/payment-gateway/v1/payments/purchase";
	
		$payway_currencies = ["USD", "KHR"];
		$order_currency = $order_info['currency_code'];

		if(!in_array($order_currency, $payway_currencies)){
			throw new Exception($order_currency . ' currency is not available for ABA PayWay.');
		}

		$data['currency'] = $order_currency;

		$reqtime = date('YmdHis');
		$data['req_time'] = $reqtime;

		$firstname = $order_info['firstname'];
		$data['firstname'] = $firstname;

		$lastname = $order_info['lastname'];
		$data['lastname'] = $lastname;

		$email = $order_info['email'];
		$data['email'] = $email;

		$telephone = $order_info['telephone'];
		$data['telephone'] = $telephone;

		$amount = (string) $order_info['total'];
		$data['amount'] = $amount;		
		
		$merchantId = $this->config->get('payment_payway_merchant');
		$data['merchant_id'] = $merchantId;
		
		$tranId = $this->session->data['order_id'];
		$data['tran_id'] = $tranId;
		
		$payOption = $this->config->get('payment_payway_option') == "ALL" ? "" : $this->config->get('payment_payway_option');
		$data['payOption'] = $payOption;

		$returnUrl = HTTPS_SERVER . "index.php?route=extension/payment/payway/webhookdata";
		$data['returnUrl'] = $returnUrl;
		
		$cancelUrl = HTTPS_SERVER . 'index.php?route=checkout/checkout';
		$data['cancelUrl'] = $cancelUrl;
		
		$successUrl = HTTPS_SERVER . 'index.php?route=checkout/success';
		$data ['successUrl'] = $successUrl;
		
		$publickey = trim($this->config->get('payment_payway_public_key'));

		if ($publickey) {
			$paywaySecure = new HMACPayWaySecure();
			$secureHash = $paywaySecure->generatePaymentSecureHash($reqtime, $merchantId, $tranId, $amount, $firstname, $lastname, $email, $telephone, $payOption, $returnUrl, $cancelUrl, $successUrl, $order_currency, $publickey);
			$data['secureHash'] = $secureHash;
		} else {
			$data['secureHash'] = '';
		}

		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/payment/payway.twig')) {
			return $this->load->view($this->config->get('config_template') . '/template/extension/payment/payway', $data);
		} else {
			return $this->load->view('extension/payment/payway', $data);
		}		
	}

	# extension/payment/payway/webhookdata
	public function webhookdata(){

		$json = array();

		$this->load->model('checkout/order');

		if($this->request->server['REQUEST_METHOD'] == 'POST'){

			$payway = new Log("payway.log");
			$payway->write("ABA Webhook Data: " . json_encode($this->request->post));

			if(isset($this->request->post['response'])){
				$payway_response = json_decode(html_entity_decode($this->request->post['response']), true);
			}
			else{
				$payway_response = $this->request->post;
			}

			if($payway_response){
				
				if(!isset($payway_response['tran_id']) || empty($payway_response['tran_id'])){
					$json['errors']['tran_id'] = "Transaction ID is empty";
				}
		
				if(!isset($payway_response['apv']) || empty($payway_response['apv'])){
					$json['errors']['apv'] = "APV is empty";
				}
		
				if(!isset($payway_response['status'])){
					$json['errors']['status'] = "Status is empty";
				}
	
				if(!isset($json['errors'])){
		
					$verifyTxn = $this->verifyTransactionStatus($payway_response['tran_id']);
					$order_status_id = $this->getOrderStatus($verifyTxn);
					
					$this->model_checkout_order->addOrderHistory($payway_response['tran_id'], $order_status_id, sprintf("Payment was %s. APV: %s", $verifyTxn["description"] ?? "", $verifyTxn['apv'] ?? ""), false);
				}
				else{
					$this->log->write("Missing field errors: " . json_encode($json));
				}
			}

		}
	}

	private function verifyTransactionStatus($tranId){

		$paywaySecure = new HMACPayWaySecure();

		$url = $this->config->get("payment_payway_payserverurl") . "api/payment-gateway/v1/payments/check-transaction";
		$reqTime = date("YYYYmdHis");
		$publickey = trim($this->config->get('payment_payway_public_key'));
		$merchantId = trim($this->config->get('payment_payway_merchant'));
		$hash = $paywaySecure->getCheckTranHash($reqTime, $merchantId, $tranId, $publickey);
		
		$postfields = array(
			'tran_id' => $tranId,
			'hash' => $hash,
			'req_time' => $reqTime,
			'merchant_id' => $merchantId
		);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_PROXY, null);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // On dev server only!
		$result = curl_exec($ch);
		$result = json_decode($result, true);
		
		$payway = new Log("payway.log");
		$payway->write("ABA Verified Data: " . json_encode($result));

		return $result;
	}

	private function getOrderStatus($verifyTxn){

		$status_code = isset($verifyTxn["status"]) ? $verifyTxn["status"] : 11; // Other Server side Error from ABA
		
		// Convert ABA status to Opencart order status
		// [
		// 	"Approved" => "Complete",
		// 	"Created" => "Processing",
		// 	"Pending" =>  "Pending",
		//	"Declined" => "Denied",
		// 	"Refunded" => "Refunded",
		// 	"Wrong Hash" => "Failed",
		// 	"tran_id not Found" => "Voided",
		// 	"Other Server side Error" => "Failed"
		// ]	
		
		$aba_opencart_statuses = [
			"0" => 5, 
			"1" => 2,
			"2" => 1,
			"3" => 8,
			"4" => 11,
			"5" => 10,
			"6" => 16,
			"11" => 10
		];

		return $aba_opencart_statuses[$status_code];
	}

}
?>