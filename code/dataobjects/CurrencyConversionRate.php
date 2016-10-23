<?php
class CurrencyConversionRate extends DataObject {
	
	private static $db = array(
		'Base' 		=> 'Varchar(5)',	//e.g. AUD
		'Target' 	=> 'Varchar(5)',	//e.g. to CNY
		'Rate' 		=> 'Decimal(9,4)'
	);
	
	private static $extensions = array(
		"Versioned('Live')"
	);
	
	public function generateRequiredCurrencies(){
		$otherCurrencies 	= ConvertibleCurrency::config()->currencies_info;
		if( is_array($otherCurrencies) && count($otherCurrencies) ){
			foreach ($otherCurrencies as $baseCurrency=>$junk){
				foreach ($otherCurrencies as $targetCode=>$trash){
					if( $baseCurrency != $targetCode ){
						$rates = $this->getLatestCurrencyRate($targetCode,$baseCurrency);
						$rate = CurrencyConversionRate::get()->filter(array(
							'Base' 		=> $baseCurrency,
							'Target' 	=> $targetCode
						))->first();
						if( !($rate && $rate->ID) && $rates ){
							$rate = CurrencyConversionRate::create();
							$rate->Base 	= $baseCurrency;
							$rate->Target 	= $targetCode;
							$rate->Rate		= $rates;
							$rate->write();
						}
					}
				}
			}
		}
	}

	protected $latestCurrencyRates = array();

	public function updateCurrenciesRates(){
		$rates = CurrencyConversionRate::get();
		
		$number = 0;

		if($rates && $rates->count()){
			foreach($rates as $rateDO){/* @var $rateDO CurrencyConversionRate */
				if($rateDO->Target && $rateDO->Base){
					$latestRate = $this->getLatestCurrencyRate($rateDO->Target, $rateDO->Base);
					if($latestRate){
						$rateDO->Rate = $latestRate;
						$rateDO->write();
						
						$number++;
					}
				}
			}
		}
		
		return array( $number, $rates->Count(), count(ConvertibleCurrency::config()->currencies_info) );
	}
	
	/**
	 * Try to get latest currency rate from API.
	 * 
	 * @param string $base
	 * @param string $target
	 * @return float $rate | int 0
	 */
	public function getLatestCurrencyRate($target, $base = null){
		if($base === null){
			$base = ConvertibleCurrency::config()->base_currency;
		}
		
		if( ! isset($this->latestCurrencyRates[$base][$target])){
			$this->apiGetAllLatestCurrencyRates($base);
		}

		if(isset($this->latestCurrencyRates[$base][$target]) && $this->latestCurrencyRates[$base][$target]){
			return $this->latestCurrencyRates[$base][$target];
		}
		
		return 0;
	}
	
	/**
	 * Get all currencies rates from API for base currency.
	 * 
	 * e.g. 
	 * 	- http://fixer.io/
	 * 	- http://api.fixer.io/latest?base=AUD
	 * 
	 * @param string $base
	 */
	protected function apiGetAllLatestCurrencyRates($base){
		$apiURL = ConvertibleCurrency::config()->latest_currency_rates_api;
		
		if($apiURL){
			$jsonContent = file_get_contents($apiURL . '?base=' . $base);
			if($jsonContent){
				try {
					$results = Convert::json2array($jsonContent);
					if($results && count($results) && isset($results['base']) && $results['base'] == $base && isset($results['rates'])){
						$this->latestCurrencyRates[$base] = $results['rates'];
					}
				}catch (Exception $e){
					//TODO something wrong with the API. send logs to DEV.
				}
			}
		}
	}
	
	
	private static $http_get_var = 'uscc';
	
	static function init_currency(){
		$request = Controller::curr()->request;
		$httpVar = self::config()->http_get_var;
		
		if($request->getVar($httpVar)){
			CurrencyConversionRate::setCurrencyCode($request->getVar($httpVar));
		}
	}
	
	static function setCurrencyCode($code){
		Session::set('CurrencyCode', $code);
		
		return $code;
	}
	
	static function getCurrencyCode(){
		$savedCode 			= Session::get('CurrencyCode');
		$allowedCurrencies 	= self::all_currencies();
		
		//first try to get the country from the IP
		if(!$savedCode && isset($_SERVER['REMOTE_ADDR'])){
			$data = unserialize(file_get_contents('http://www.geoplugin.net/php.gp?ip='.$_SERVER['REMOTE_ADDR']));
			if(isset($data['geoplugin_currencyCode']) && in_array($data['geoplugin_currencyCode'], $allowedCurrencies)){
				$savedCode = $data['geoplugin_currencyCode'];
				CurrencyConversionRate::setCurrencyCode($savedCode);
			}
		}
		
		if(!$savedCode){
			$savedCode = ConvertibleCurrency::config()->base_currency;
			CurrencyConversionRate::setCurrencyCode($savedCode);
		}
		
		return $savedCode;
	}
	
	public static function all_currencies(){
		$baseCurrency 		= ConvertibleCurrency::config()->base_currency;
		$otherCurrencies 	= ConvertibleCurrency::config()->other_currencies;
		
		return array_merge(array($baseCurrency), $otherCurrencies);
	}
	
	static function getAllCurrencies(){
		$currenciesInfo = ConvertibleCurrency::config()->currencies_info;
		$allCurrencies	= self::all_currencies();
		$list 			= ArrayList::create(); /* @var $list ArrayList */

		foreach ($currenciesInfo as $code => $data){
			if( in_array($code,$allCurrencies) ){
				$item = ViewableData::create();
				$item->Title 	= $data['title'];
				$item->Symbol 	= $data['symbol'];
				$item->Code 	= $code;
				$item->Selected = (self::getCurrencyCode() == $code);
				$list->push($item);
			}
		}
		
		return $list;
	}
	
}