<?php
declare(strict_types=1);

namespace Bank\Service;
use \Datetime;

// class for currencies.
class Currency
{
	//class instance handle
	private static $instance = null;
	
	//array for the currencies formally having no decimal cents
	private static $noDecimalCurrencyArray = ["JPY"];
	
	//url to retrive exchange rates
	private static $url = 'https://developers.paysera.com/tasks/api/currency-exchange-rates';
	
	//caching calculated rates;
	private static $euroRates = [];

	private function __construct(){ }
	
	private function __clone() { }

	private static function isNoDecimal(string $currency): bool
	{
		return in_array($currency,self::$noDecimalCurrencyArray);
	}

	public static function roundUp(float $amount, string $currency): float
	{
		//for noDecimal currencies reurns minimal integer which is greater or equal to $amount
		//otherwise rounds up $amount to 2 decimal plases

		//decides whether the currency has or not decimal cents
		$noDecimal = self::isNoDecimal($currency);

		if ($noDecimal) return ceil($amount);
		else return ceil($amount*100)/100;
	}

	public static function exchangeRate($currency){
		//returns exchange rate of the currency to 'EUR'
		//rates are provided from url
		//returns 1 if the currency was not found in returned json
		//this issue must be handled according to company rules and regulations 

		$today = new DateTime();
		$today->setTime(0,0);
		if (isset(self::$euroRates['date']))
			$rateDate = new DateTime(self::$euroRates['date']);
		else
			$rateDate = new DateTime('1970-01-01');

		if ($rateDate != $today) {
			$ch = curl_init(self::$url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$response = curl_exec($ch);
			self::$euroRates = json_decode($response,true);//caching calculated rates;
			curl_close($ch);
		}
		if (isset(self::$euroRates['rates'][$currency]))
			return self::$euroRates['rates'][$currency];
		else return 1;
	}
}