<?php
class UpdateCurrencyRatesTask extends BuildTask {

	protected $title = 'Update Currency Rates Task';

	public function run($request) {
		increase_time_limit_to();

		//check update all rates
		$rate = CurrencyConversionRate::create();	/* @var $rate CurrencyConversionRate */
		$rate->generateRequiredCurrencies(); // generate any new rates
		$number = $rate->updateCurrenciesRates();
		
		DB::alteration_message("Updated {$number[0]} rates out of {$number[1]} for {$number[2]} currencies.", 'created');
	}

}