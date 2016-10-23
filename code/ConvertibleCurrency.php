<?php
class ConvertibleCurrency extends Currency {
	
	protected $currencyCode;
	
	protected $conversionRate = 1;
	
	public function getConversionRate(){
		return $this->conversionRate;
	}
	
	/**
	 * Enable currency conversion according to user's selected currency. It will update value, symbol, code, etc...
	 *
	 * e.g. call this function in template like - $Total.AutoConversion.Full
	 */
	public function AutoConversion($currency=null,$target=null){
		//currency code.
		$this->currencyCode = $target ? $target : self::currencyCode();
	
		//setup related info.
		$this->setupCurrencyInfo($this->currencyCode);
		
		//update rate.
		$baseCode = $currency ? $currency : ConvertibleCurrency::config()->base_currency;
		$this->updateConversionRate($baseCode, $this->currencyCode);
		
		//update value by rate
		$this->updateConvertedValue();
		
		return $this;
	}
	
	/**
	 * setup selected currency code.
	 * 
	 * e.g.
	 * 	- setup symbol.
	 * 	- setup locale title
	 * 
	 * @param string $code
	 */
	protected function setupCurrencyInfo($code){
		//get currency info first.
		$infoArray = $this->getCurrencyInfo($code);
		//setup symbol, locale title, etc....
		if(is_array($infoArray)){
			//setup currency symbol
			if(isset($infoArray['symbol']) && $infoArray['symbol']){
				Config::inst()->update(__CLASS__, 'currency_symbol', $infoArray['symbol']);
			}
		}
	}
	
	protected function updateConversionRate($base, $target){
		//default
		$this->conversionRate = 1;

		if($base && $base != $target){
			//get conversion rate
			$rate = CurrencyConversionRate::get()->filter(array(
				'Base' 		=> $base,
				'Target' 	=> $target
			))->first();
				
			if($rate && $rate->Rate){
				$this->conversionRate = $rate->Rate;
			}
		}
		
		return $this;
	}
	
	protected function updateConvertedValue(){
		$this->value = $this->value * $this->conversionRate;
		return $this;
	}
	
	/**
	 * get currency info by code
	 * 
	 * @param string $code
	 * @return array | boolean false
	 */
	protected function getCurrencyInfo($code){
		//make sure code are upper cases.
		$code = strtoupper($code);
		//find the code in the info pool.
		$allInfo = ConvertibleCurrency::config()->currencies_info;
		if($allInfo && isset($allInfo[$code]) && count($allInfo[$code])){
			return $allInfo[$code];
		}
		
		return false;
	}
	
	static function getCurrencyCodeForCountry($code){
		$code = strtoupper($code);
		$allInfo = ConvertibleCurrency::config()->currencies_info;
		if( $allInfo & is_array($allInfo) ){
			foreach( $allInfo as $a=>$k ){
				if( is_array($k['country']) && in_array($code, $k['country']) )
					return $a;
			}
		}
		return null;
	}
	
	static function convert($value, $currency=null, $target=null){
		$converter = ConvertibleCurrency::create();
		
		$converter->setValue($value);
		
		return $converter->AutoConversion($currency,$target)->getValue();
	}
	
	public function currencySymbol($code = null){
		if($code === null){
			$code = self::currencyCode();
		}
		
		$infoArray = $this->getCurrencyInfo($code);
		
		if(is_array($infoArray)){
			//setup currency symbol
			if(isset($infoArray['symbol']) && $infoArray['symbol']){
				return $infoArray['symbol'];
			}
		}
		
		return '';
	}
	
	static function currencyCode(){
		//not specified. try to get it from session or get the default.
		if(CurrencyConversionRate::getCurrencyCode()){
			//try to get it from Session first. User might set a custom currecny code.
			return CurrencyConversionRate::getCurrencyCode();
		}else{
			//get default one.
			return ConvertibleCurrency::config()->base_currency;
		}
		
		return '';
	}
	
	public function forTemplate(){
		return $this->Nice();
	}
	
	public function Full(){
		if($this->config()->currency_symbol == $this->currencyCode){
			return $this->Nice();
		}
	
		return $this->currencyCode . ' ' . $this->Nice();
	}
	
}