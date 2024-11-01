<?php  
class ModelExtensionPaymentPayWay extends Model {
  	public function getMethod($address, $total) {
		$this->load->language('extension/payment/aba_payway');
				
		$status = true;
		$method_data = array();
	
		if ($status) {  
      		$method_data = array( 
        		'code'       => 'aba_payway',
        		'title'      => $this->language->get('text_title'),
      			'terms'      => '',
				'sort_order' => $this->config->get('payment_payway_sort_order')
      		);
    	}
   
    	return $method_data;
  	}
}
?>