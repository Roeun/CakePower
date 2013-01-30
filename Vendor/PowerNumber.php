<?php
App::uses('CakeNumber', 'Utility');
class PowerNumber extends CakeNumber {
	
/**
 * return factorial of given number (int)
 * @param int $input
 */	
	public static function factor($input) {
		if (!is_int($input)) {
			throw new InvalidArgumentException('Integer expected!');
		}
		if ($input == 1) {
			return $input;
		}
		return $input * self::factor($input-1);
	}
	
}
