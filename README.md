# SilverStripe Currency-Converter module
Retrieve, store and apply currency conversions in your SilverStripe site.

## Installation
```
composer require internetrix/currency-converter
```

## Displaying rates
To output a converted currency, call the ConvertibleCurrency class and include the amount. 
If the amount is not in the base currency, include the amount's currency. 
If it requires conversion to a specific currency, include that target currency.
`ConvertibleCurrency::convert($amount, $amountCurrency, $targetCurrency)`

From inside a template, simply add "AutoConversion" to the output variable. To get the currency symbol, append "Full". AutoConversion can also accept two parameters: base and target currency.
`<h2>Total Price: $Total.AutoConversion.Full</h2>`

To switch the display rate, add the target currency to the URL using a "uscc" variable (it then persists for the session.) For example, to show aussie dollars:
`http://somewhere.com/mypage/?uscc=AUD` 

## Refreshing the rates
To get the latest currency conversion rates, run the included task with a daily cron. It populates the database with conversions between all configured cuurencies.
`/dev/tasks/UpdateCurrencyRatesTask`

## API Configuration
The module connects to Fixer.io for the latest currency conversion rates as supplied by the European Central Bank. Currently it reports 31 currencies. To use a different API, update the config.yml
`latest_currency_rates_api : 'http://api.fixer.io/latest'`

The currency locale information is listed in the yaml file, this is not comprehensive and may need updating. Amend it to include/exclude the rates saved into the database, using the `other_currencies` section.

Note there is one additional lookup to geoplugin.com to determine the current user's locale. 
