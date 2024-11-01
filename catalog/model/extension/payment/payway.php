<?php  
class ModelExtensionPaymentPayWay extends Model {
  	
    public function getMethod($address, $total) {
		
        $this->load->language('extension/payment/payway');
				
		$method_data = array( 
            'code'       => 'payway',
            'title'      => $this->language->get('text_title'),
            'terms'      => 'PayWay Terms & Conditions',
            'sort_order' => $this->config->get('payment_payway_sort_order')
        );
   
    	return $method_data;
  	}
}
?>