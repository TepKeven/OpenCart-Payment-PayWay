<?php 

class HMACPayWaySecure {

	public function generatePaymentSecureHash($reqtime, $merchantId, $tranId, $amount, $firstname, $lastname, $email, $telephone, $payOption, $returnUrl, $cancelUrl, $successUrl, $currency, $publickey) {

		$tranInfo = $reqtime . $merchantId . $tranId . $amount . $firstname . $lastname . $email . $telephone . $payOption . $returnUrl . $cancelUrl . $successUrl . $currency;
		return base64_encode(hash_hmac('sha512', $tranInfo, $publickey, true));

	}

	public function getCheckTranHash($reqTime, $merchantId, $tranId, $publickey) {
		
		$tranInfo = $reqTime . $merchantId . $tranId;
		return base64_encode(hash_hmac('sha512', $tranInfo, $publickey, true));
	}

}
?>