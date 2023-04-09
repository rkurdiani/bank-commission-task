<?php
declare(strict_types=1);

namespace Bank\Service;
use \Datetime;

// singleton class for operations.
class Operation
{
	//class instance handle
	private static $instance = null;
	
	//to handle operatons
	//in real situation operations will be retrieved from DB in real time
	private static $operations = [];
	/*
		0 - operation date in format Y-m-d
		1 - user's identificator, number
		2 - user's type, one of private or business
		3 - operation type, one of deposit or withdraw
		4 - operation amount (for example 2.12 or 3)
		5 - operation currency, one of EUR, USD, JPY
	*/
	
	private function __construct()
	{
		//
	}
	
	private function __clone() { }
	
	public static function getInstance(string $csvFile)
	{
		if (self::$instance == null){
			self::$instance = new Operation();

			//gets operations from csv file
			if (($handle = fopen($csvFile, "r")) !== FALSE) {
				while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
					self::$operations[] = $data;
				}
				fclose($handle);
			}
		}
		return self::$instance;
	}

	private function weekStart($date){
		//if the $date is monday then returns the $date with time setting to midnight
		//otherwise returns last monday for the given date
		//in other words returns first day of the week for the given date
		$monday = clone $date;
		if ($date->format('w') == 1) return $monday->setTime(0,0);
		else return $monday->modify('last monday');
	}

	private function freeOfCharge($userID,$date){
		//returns free of charge amount in EUR for the given $date
		$monday = self::weekStart($date);
		$opCount = 0;
		$result = 1000;

		for ($i = 0; $i < count(self::$operations); $i++) {
			$opDateStr = self::$operations[$i][0];
			$opDate = new DateTime($opDateStr);
			$opDate->setTime(0,$i); // setting minutes to distinguish two withdraw made on the same day
			$opUser = self::$operations[$i][1];
			$opType = self::$operations[$i][3];
			$opAmount = self::$operations[$i][4];
			$opCurr = self::$operations[$i][5];
			
			if (($opDate >= $monday) and ($opDate < $date) and ($opUser == $userID) and ($opType == "withdraw")){
				$opCount++;
				$rate = Currency::exchangeRate($opCurr);
				$result -= $opAmount / $rate;
			}
			
			if (($opCount > 2) or ($result < 0)) return 0;
		}

		return $result;
	}

	private function commission($amount,$freeOfCharge,$operationType,$userType){
		//calculates commission
		//$amount is the amount of the operation
		//$freeOfCharge is the amount which is free of charge for this operation
		//$operationType indicates operation type (deposit or withdraw)
		//$userType indicates user's type (private or business)
		
		if ($operationType == "deposit") return $amount * 0.0003;
		else if ($userType == "business") return $amount * 0.005;
		else if ($amount > $freeOfCharge) return ($amount - $freeOfCharge) * 0.003;
		else return 0;
	}

	public function calculateAllCommissions(){
		//calculates commissions for all operations
		$result = [];
		
		for ($i = 0; $i < count(self::$operations); $i++) {
			$opDateStr = self::$operations[$i][0];
			$opDate = new DateTime($opDateStr);
			$opDate->setTime(0,$i); // setting minutes to distinguish two withdraw made on the same day
			$opUser = self::$operations[$i][1];
			$opUserType = self::$operations[$i][2];
			$opType = self::$operations[$i][3];
			$opAmount = self::$operations[$i][4];
			$opCurr = self::$operations[$i][5];
			
			$rate = Currency::exchangeRate($opCurr);
			$freeOfCharge = self::freeOfCharge($opUser,$opDate) * $rate;
			$commission = self::commission($opAmount,$freeOfCharge,$opType,$opUserType);
			$result[] = Currency::roundUp($commission,$opCurr);

		}
		return $result;
	}

}