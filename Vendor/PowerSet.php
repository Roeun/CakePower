<?php
/**
 * CakePOWER, CREDITS and LICENSING
 * =====================================
 *
 * @author: 	Marco Pegoraro (aka MPeg, @ThePeg)
 * @mail: 		marco(dot)pegoraro(at)gmail(dot)com
 * @blog:		http://movableapp.com
 * @web:		http://cakepower.org
 * 
 * This sofware is distributed under MIT license.
 * Please read "license.txt" document into plugin's root
 * 
 */



class PowerSet extends Set {
	
	
	/**
	 * http://movableapp.com/2012/11/power-set-todef-method-api/
	 * 
	 * @param string $arr
	 * @param mixed $types
	 */
	public static function def( $arr = '', $defaults = array(), $types = array() ) {
		
		if ( !empty($arr) && !is_array($arr) && !empty($types) ) {
			
			// array of types
			if ( is_array($types) ) {
				
				$arr_type = gettype($arr);
				
				foreach ( $types as $type=>$field ) {
					
					if ( $arr_type == $type ) {
						
						$arr = array( $field=>$arr );
							
					}
				
				}
			
			// singular key for all non-array values
			} elseif( is_string($types) ) {
				
				$arr = array( $types=>$arr );
				
			}
		
		}
		
		
		// safe conversion to always output an array
		if ( !is_array($arr) || empty($arr) ) $arr = array();
		if ( !is_array($defaults) || empty($defaults) ) $defaults = array();
		
		// translate scalar array (or items) to associative with null value
		foreach ( $defaults as $key=>$val ) {
			
			if ( is_numeric($key) ) {
				
				unset($defaults[$key]);
				
				$defaults[$val] = null;
			
			}
		
		}
		
		// apply defaults
		$arr = self::extend($defaults,$arr);
		
		return $arr;
		
	}
	
	
	
	public static function todef( $arr = '', $types = array(), $defaults = array() ) {
		
		trigger_error("WARNING!: todef() is now deprecated!",E_USER_WARNING);
		
		return self::def( $arr, $defaults, $types );
		
	}
	
	
	
	public static function is_vector( $arr = array() ) {
		
		return (0 !== array_reduce(
			array_keys($arr),
			array( 'PowerSet', '_is_vector_reduce' ),
			0
        ));
        
	}
	
	public static function is_assoc( $arr = array() ) {
		
		return !self::is_vector($arr);
		
	}
	
	protected static function _is_vector_reduce( $a, $b ) {
		
		return ($b === $a ? $a + 1 : 0);
		
	}

	
	/**
	 * array merge utility
	 * go recursive in merging arrays
	 * 
	 * ## reset array feature:
	 * 
	 * ## reset key feature:
	 * if $b contains "_foo" it will reset $a['foo'] to a null value.
	 * use it to remove values from the source array!
	 * 
	 * @param unknown_type $a
	 * @param unknown_type $b
	 */
	public static function extend() {
		
		$args = func_get_args();
		
		// set up reset key status with a last, boolean param
		$use_reset_key = false;
		if ( gettype($args[count($args)-1]) === 'boolean' ) {
			$use_reset_key = $args[count($args)-1];
			array_pop($args);
		}
		
		$a = current($args);
		while( ($b = next($args)) !== false ) {
			
			// an empty extending value will be ignored
			if ( empty($b) ) continue;
			
			if ( !is_array($a) || empty($a) ) $a = array();
			if ( !is_array($b) || empty($b) ) $a = array();
				
			// if booth arrays are vectors (no associave) apply a "normal" merge method
			if ( self::is_vector($a) && self::is_vector($b) ) return self::merge($a,$b);
			
			// $__overrides__$
			// use reset array filled with keys to be resetted before the extension action. 
			if ( array_key_exists('$__overrides__$', $b) ) {
				foreach ( $b['$__overrides__$'] as $ovKey ) {
					unset($a[$ovKey]);
				}
				unset($b['$__overrides__$']);
			}
			
			foreach ( $b as $key=>$val ) {
				
				// use reset key check
				// this option is disabled by default
				if ( $use_reset_key && substr($key,0,1) === '_' ) {
					$key = substr($key,1,strlen($key));
					$a[$key] = null;
				}
				
				// empty origin or different types of values
				if ( !array_key_exists($key,$a) || gettype($a[$key]) != gettype($val) || !is_array($val) ) {
					$a[$key] = $val;
					
				// some kind of array
				} else {
					$a[$key] = self::extend( $a[$key], $val );
					
				}
				
			}
			
		} 
		
		return $a;
		
	}
	
	
/**
 * [overrides Set::filter]
 * Filters non empty, non 0 and non false items from an array.
 * 
 */
	
	public static function filter( array $var ) {
		
		$var = Hash::filter($var,array('PowerSet', '_filter'));
		
		return $var;
		
	}
	
	public static function _filter($var) {
		
		if ( $var === 0 || $var === '0' || $var === false || !empty($var) ) {
			return true;
		}
		
		return false;
		
	}
	
	
/**	
 * Remove a list of array keys from given array.
 * If $rempove is empty then remove all empty or null values from the array
 * 
 * @param array $var
 * @param unknown_type $remove
 */
	public static function clear(array $var, $remove = array()) {
		
		// remove null or empty keys
		if (empty($remove)) {
			foreach($var as $key=>$val) {
				if (empty($val)) {
					unset($var[$key]);
				}
			}
			return $var;
		}
		
		// string preset to a key list
		if (is_string($remove)) {
			$remove = array($remove);
		}
		
		// remove named keys
		foreach ($remove as $key) {
			unset($var[$key]);
		}
		
		return $var;
		
	}
	
	
	
	
/**
 * Array path notation utilities.
 * 
 * dots2array() translates a dotted string to a path array:
 * 
 * path1.path2.pat3
 * ->
 * array(
 *   'path1',
 *   'path2',
 *   'path3'
 * )
 * 
 * array2dots() is the reverse logic.
 * 
 * dotsParent('path1.path2.path3')
 * -> path1.path2
 * 
 */
	public static function dots2array( $dots = '' ) {
		
		if (is_string($dots)) {
			if (strpos($dots, ".")) {
				return explode(".", $dots);
			}
			return array($dots);
		}
		
		return $dots;
		
	}
	
	public static function array2dots( $array = array() ) {
		
		if ( empty($array) || !is_array($array) ) return '';
		
		return array_reduce( $array, array( 'PowerSet', '_array2dots_walk' ) );
	
	}
	
	protected static function _array2dots_walk($a,$b) {
			
		if ( !empty($a) ) {
			$a.= '.'.$b;
					
		} else {
			$a.=$b;
			
		}
		
		return $a;
		 
	}
	
	public static function dotsParent( $dots = '' ) {
		
		$tmp = self::dots2array($dots);
		
		if ( count($tmp) < 1 ) return $dots;
		
		array_pop($tmp);
		
		return self::array2dots($tmp);
	
	}
	
	
	
	
/**
 * Supplies methods to insert items inside an associative array using a key name as insertion point.
 * 
 * 
 * $arr = array( 'foo1'=>'a', 'foo2'=>'b', 'foo3'=>'c' );
 * 
 * $arr = PowerSet::beforeAssoc( $arr, 'foo2', 'pre-foo2', 'value...' );
 * -> array( 'foo1'=>'a', 'pre-foo2'=>'value...', 'foo2'=>'b', 'foo3'=>'c' )
 * 
 * $arr = PowerSet::afterAssoc( $arr, 'foo2', array('foo2a'=>'value') );
 * -> array( 'foo1'=>'a', 'foo2'=>'b', 'foo2a'=>'value', 'foo3'=>'c' )
 * 
 * You can use both 3rd and 4th params to define new item's name and value or combine them into
 * an associative array as 3rd param.
 * 
 */
	
	public static function beforeAssoc( $set = array(), $matchKey = '', &$newKey = '', &$newVal = '' ) {
		
		if ( !self::_checkBeforeAfterAssoc($set,$matchKey,$newKey,$newVal) ) return false;
		//if ( !self::_checkBeforeAfterAssoc($set,$matchKey,$newKey,$newVal) ) return false;
		
		$newSet = array();
		
		foreach ( $set as $_key=>$_val ) {
			
			// strange notice with some trange characters!
			ob_start();
			if ( $_key == $matchKey ) {
				$newSet[$newKey] = $newVal;	
			}
			ob_get_clean();
			
			$newSet[$_key] = $_val;
			
		}
		
		// You can pass $set by reference when calling this method!
		$set = $newSet;
		
		return $newSet;
		
	}
	
	public static function afterAssoc( $set = array(), $matchKey = '', &$newKey = '', &$newVal = '' ) {
		
		if ( !self::_checkBeforeAfterAssoc($set,$matchKey,$newKey,$newVal) ) return false;
		//if ( !self::_checkBeforeAfterAssoc($set,$matchKey,$newKey,$newVal) ) return false;
		
		$newSet = array();
		
		foreach ( $set as $_key=>$_val ) {
			
			$newSet[$_key] = $_val;
			
			if ( $_key == $matchKey ) $newSet[$newKey] = $newVal;
			
		}
		
		// You can pass $set by reference when calling this method!
		$set = $newSet;
		
		return $newSet;
		
	}
	
	protected static function _checkBeforeAfterAssoc( $set = array(), $matchKey = '', $newKey = '', $newVal = '' ) {
		
		if ( empty($set) ) return false;
		
		if ( !Set::check($set,$matchKey) ) return false;
		
		if ( empty($newKey) ) return false;
		
		// It handle $newKey to contain an associative array with the value for the insertion.
		if ( is_array($newKey) ) {
			 
			$keys = array_keys($newKey);
			
			if ( count($keys) == 1 ) {
				
				$newVal = $newKey[$keys[0]];
				$newKey = $keys[0];
			
			}
			
		}
		
		if ( !is_string($newKey) ) return false;
		
		return true;
		
	}
	
	
	
	
	
	
	
	
/**
 * Supplies insert methods for a vector array.
 * 
 * $arr = array( 'red', 'white', 'green' );
 * 
 * PowerSet::beforeVector( $arr, 'white', 'blue' );
 * -> array( 'red', 'blue', 'white', 'green' )
 * 
 * PowerSet::afterVector( $arr, '{1}', 'blue' );
 * -> array( 'red', 'white', 'blue', 'green' )
 * 
 * You can use both item value or item index {i} to identify the intert key point.
 * 
 */
	
	public static function beforeVector( $set = array(), $key = '', $val = '' ) {
		
		if ( !self::_checkBeforeAfterVector($set,$key,$val) ) return false;
		
		$newSet = array();
			
		foreach ( $set as $i=>$_val ) {
			
			if ( $_val == $key || '{'.$i.'}' == $key ) $newSet[] = $val;
			
			$newSet[] = $_val;
		
		}
		
		// You can pass $set by reference when calling this method!
		$set = $newSet;
		
		return $newSet;
		
	}
	
	public static function afterVector( $set = array(), $key = '', $val = '' ) {
		
		if ( !self::_checkBeforeAfterVector($set,$key,$val) ) return false;
		
		$newSet = array();
			
		foreach ( $set as $i=>$_val ) {
			
			$newSet[] = $_val;
			
			if ( $_val == $key || '{'.$i.'}' == $key ) $newSet[] = $val;
		
		}
		
		// You can pass $set by reference when calling this method!
		$set = $newSet;
		
		return $newSet;
		
	}
	
	protected static function _checkBeforeAfterVector( $set = array(), $key = '', $val = '' ) {
		
		if ( !self::is_vector($set) ) return false;
		
		if ( empty($key) ) return false;
		
		return true;
		
	} 
	
	
	
	

	
/**
 * shortcut methods with data type inspection.
 */
	public static function before( $set = array(), $matchKey = '', $newKey = '', $newVal = '' ) {
		
		if ( self::is_vector($set) ) return self::beforeVector( $set, $matchKey, $newKey );
		
		return self::beforeAssoc( $set, $matchKey, $newKey, $val );
		
	}
	
	public static function after( $set = array(), $matchKey = '', $newKey = '', $newVal = '' ) {
		
		if ( self::is_vector($set) ) return self::afterVector( $set, $matchKey, $newKey );
		
		return self::afterAssoc( $set, $matchKey, $newKey, $val );
		
	}
	
	
	
	
/**
 * Search for keys to override.
 * 
 * key "_style" overrides "style" then is removed from the set.
 * 
 * @param array $config
 */	
	public static function configOverride( $config = array() ) {
		
		foreach ( $config as $key=>$val ) {
			
			// apply overridden value
			if ( substr($key,0,1) === '_' ) {
				$config[substr($key,1,strlen($key))] = $config[$key];
				unset($config[$key]);
			
			// descends into sub arrays
			} elseif ( is_array($val) ) {
				$config[$key] = self::configOverride($val);
			
			}
		
		}
		
		return $config;
		
	}
	

}