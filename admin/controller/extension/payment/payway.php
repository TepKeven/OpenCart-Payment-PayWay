<?php
class ControllerExtensionPaymentPayWay extends Controller {
	
	private $error = array(); 

	public function index() {
		$this->load->language('extension/payment/payway');

		$this->document->setTitle($this->language->get('heading_title'));
		
		$this->load->model('setting/setting');
			
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {			
			$this->model_setting_setting->editSetting('payment_payway', $this->request->post);				
			
			$this->session->data['success'] = $this->language->get('text_success');

		$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}


  		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

 		if (isset($this->error['public_key'])) {
			$data['error_public_key'] = $this->error['public_key'];
		} else {
			$data['error_public_key'] = '';
		}
		
 		if (isset($this->error['merchant'])) {
			$data['error_merchant'] = $this->error['merchant'];
		} else {
			$data['error_merchant'] = '';
		}

		if (isset($this->error['payserverurl'])) {
			$data['error_payserverurl'] = $this->error['payserverurl'];
		} else {
			$data['error_payserverurl'] = '';
		}
		
		$data['breadcrumbs'] = array();

   		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

   		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
		);
		
   		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/payway', 'user_token=' . $this->session->data['user_token'], true)
		);
				
		$data['action'] = $this->url->link('extension/payment/payway', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);
		
		if (isset($this->request->post['payment_payway_merchant'])) {
			$data['payment_payway_merchant'] = $this->request->post['payment_payway_merchant'];
		} else {
			$data['payment_payway_merchant'] = $this->config->get('payment_payway_merchant');
		}	


		if (isset($this->request->post['payment_payway_public_key'])) {
			$data['payment_payway_public_key'] = $this->request->post['payment_payway_public_key'];
		} else {
			$data['payment_payway_public_key'] = $this->config->get('payment_payway_public_key');
		}	


		if (isset($this->request->post['payment_payway_payserverurl'])) {
			$data['payment_payway_payserverurl'] = $this->request->post['payment_payway_payserverurl'];
		} else {
			$data['payment_payway_payserverurl'] = $this->config->get('payment_payway_payserverurl');
		}

		$data['payway_payserverurl'] = array(
			'https://checkout-sandbox.payway.com.kh/' => 'SandBox Mode', 
			'https://checkout.payway.com.kh/' => 'Production Mode'
		);


		if (isset($this->request->post['payment_payway_option'])) {
			$data['payment_payway_option'] = $this->request->post['payment_payway_option'];
		} else {
			$data['payment_payway_option'] = $this->config->get('payment_payway_option');
		}

		$data['payway_options'] = array(
			'ALL'	=> 'All Available ABA Options',
			'cards' => 'Card Payment',
			'abapay' => 'ABA Pay',
			'abapay_deeplink' => 'ABA Pay DeepLink', 
			'wechat' => 'WeChat', 
			'alipay' => 'AliPay',
			'bakong' => 'Bakong'
		);

		$data['payment_payway_webhook'] = HTTPS_CATALOG . "index.php?route=extension/payment/payway/webhookdata";

		if (isset($this->request->post['payment_payway_status'])) {
			$data['payment_payway_status'] = $this->request->post['payment_payway_status'];
		} else {
			$data['payment_payway_status'] = $this->config->get('payment_payway_status');
		}
		
		if (isset($this->request->post['payment_payway_sort_order'])) {
			$data['payment_payway_sort_order'] = $this->request->post['payment_payway_sort_order'];
		} else {
			$data['payment_payway_sort_order'] = $this->config->get('payment_payway_sort_order');
		}
		
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		$this->response->setOutput($this->load->view('extension/payment/payway', $data));
	}
	
	
	private function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/payway')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
		//if (isset($_POST['payment_payway_public_key'])) { 
		if (!$this->request->post['payment_payway_public_key']) {
			$this->error['public_key'] = $this->language->get('error_public_key');
		}
		
		//if (isset($_POST['payment_payway_merchant'])) { 
		if (!$this->request->post['payment_payway_merchant']) {
			$this->error['merchant'] = $this->language->get('error_merchant');
		}

		//if (isset($_POST['payment_payway_public_key'])) { 
		if (!$this->request->post['payment_payway_payserverurl']) {
			$this->error['payserverurl'] = $this->language->get('error_payserverurl');
		}
		
		return !$this->error;
	}
}

?>