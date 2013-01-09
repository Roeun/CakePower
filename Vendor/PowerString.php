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




class PowerString extends String {


/**
 * Insert some values into a template string based on placeholder rules.
 * @param unknown_type $tpl
 * @param unknown_type $data
 * @param unknown_type $clearer
 * @param unknown_type $options
 *
 * echo PowerString::tpl( 'name: {name}, surname: {surname}', array(
 *     'name' => 'Mark',
 *     'surname' => 'Sheepkeeper'
 * ));
 *
 * -> 'name: Mark, surname: Sheepkeeper'
 *
 */
	public static function tpl( $tpl, $data = array(), $clearer = array(), $options = array() ) {

		$defaults = array( 'clear'=>true, 'clean'=>false, 'before'=>'{', 'after'=>'}' );
		$options += $defaults;

		// Clar "all" will remove the string if all placeholders are empty.
		if ( $clearer == 'all' ) $clearer = array( self::stripPlaceholders($tpl) );

		// Auto set for remove unused placeholder.
		if ( !isset($options['clean']) ) $options['clean'] = true;

		// strip objects from the data array cause an object can't be translated
		// into a string
		//
		// @TODO: may an object be translated to an associative array?
		// mtisi: YES! By encoding and decoding to JSON!
		$data = json_decode(json_encode($data), true);

		$str = self::insert( $tpl, PowerSet::flatten($data), $options );

		if ( $options['clear'] ) $str = self::stripPlaceholders($str,$options);

		return self::clr( $str, $clearer, $options );

	}


/**
 * Apply a cleaner paths to a string.
 * @param string $str
 * @param array $values
 * @param array $options
 *
 * echo PowerString::clr('my **label** is **val**',array(
 *     '**label**',
 *     '**name**' => '!!empty!!'
 * ));
 *
 * result:
 * -> my  is !!empty!!
 *
 * You can pass a list of sub-strings to be removed from the first arguments.
 * Each item can define a custom replace string as associative value.
 *
 *  You can set a custom replace value for all items in the $options argument:
 *  $options['replace'] = 'whatever you want!'
 *
 */
	public static function clr( $str = '', $values = array(), $options = array() ) {

		$defaults = array( 'replace'=>'' );
		$options += $defaults;


		if ( empty($values) || !is_array($values) ) $values = array();


		foreach ( $values as $key=>$val ) {

			if ( is_numeric($key) ) {
				$key = $val;
				$val = $options['replace'];
			}

			$str = str_replace( $key, $val, $str );

		}

		return $str;

	}







/**
 * Strip unused placeholders from a string based in placeholder delimiters.
 * @param string $str
 * @param array $options
 *
 * echo PowerString::stripPlaceholders( 'name: {name}' );
 * -> 'name: '
 *
 * $options[]:
 * before:  placeholder left delimiter
 * after:   placeholder right delimiter
 * replace: a string to use in place of
 */
	public static function stripPlaceholders( $str, $options = array() ) {

		$defaults = array( 'before'=>'{', 'after'=>'}', 'replace'=>'' );
		$options += $defaults;

		preg_match_all("|" . $options['before'] . "(.*)" . $options['after'] . "|U", $str, $matches);
		for ($i=0; $i< count($matches[0]); $i++) {

			$str = str_replace( $matches[0][$i], $options['replace'], $str );

		}

		return $str;

	}



/**
 * Parse a string as placeholder information.
 *
 * A placeholder may be dropped in a string as:
 * {name:type?a=foo1&b=foo2}
 *
 * A mechanism extraxts content in brakets and pass to this method:
 * $str = "name:type?a=foo1&b=foo"
 *
 * The output is an associative array with parsed informations:
 * return array(
 *     'name' => 'name',
 *     'type' => 'type',
 *     'info' => array( 'a'=>'foo1', 'b'=>'foo2' )
 * );
 */
	public static function parsePlaceholder( $str, $options = array() ) {

		$options += array( 'defaultType'=>'text' );

		$return = array(
			'name' 	=> '',
			'type'	=> '',
			'info'	=> array()
		);

		// Ensure the presence of booth var name and options tokens
		if ( strpos($str,'?') === false ) $str.= '?';
		list( $p1, $p2 ) = explode( '?', $str );

		// Parse var name and type
		if ( strpos($p1,':') === false ) $p1.= ':';
		list( $return['name'], $return['type'] ) = explode( ':', $p1 );

		// Parse url info (the querystring token)
		if ( !empty($p2) ) parse_str( $p2, $return['info'] );

		// Set defaults.
		if ( empty($return['type']) ) $return['type'] = $options['defaultType'];

		if ( empty($return['name']) ) return false;
		return $return;

	}




	public static function getFirstTrunk( $str, $sep = '.' ) {

		$tokens = PowerString::tokenize( $str, $sep );
		if ( !is_array($tokens) || !count($tokens) ) return false;

		return $tokens[0];

	}

	public static function getLastTrunk( $str, $sep = '.' ) {

		$tokens = PowerString::tokenize( $str, $sep );
		if ( !is_array($tokens) || !count($tokens) ) return false;

		$tokens = array_reverse($tokens);
		return $tokens[0];

	}





/**
 * Random Password Generation
 * ==========================
 *
 * This utility generates random strings!
 *
 * PowerString::passwd( 5, 8 );
 * -> a string between 5 and 8 chars from all available namespaces
 *
 * PowerString::passwd( 10, 10, 'n' );
 * -> a numeric string of exactly 10 chars
 *
 * PowerString::passwd( 10,20,'abc' );
 * -> a string between 10 and 20 chars, each car is "a" or "b" or "c" from the given custom namespace
 *
 * @var unknown_type
 */
	public static $passwdNamespaces = array(
		'n' => '0123456789',						// numbers
		'c' => 'qazwsxedcrfvtgbyhnujmikolp',		// chars
		'm' => 'QAZWSXEDCRFVTGBYHNUJMIKOLP',		// maius
		's' => ',;.:-_@#*!\\"$%&/()=?^+[]\'',		// special chars
		'i' => '@#-_.',								// mail punctuation
	);

	public static function passwd( $min = 8, $max = null, $space = 'ncmi' ) {

		// charset namespace as first argument
		if ( func_num_args() == 1 && is_string($min) ) {
			$space = $min;
			$min = 8;
			$max = 8;
		}

		// charset namespace as second argument
		if ( func_num_args() == 2 && is_string($max) ) {
			$space = $max;
			$max = null;
		}

		// $max default value for null option
		if ( $max === null ) $max = $min;

		// Specific namespace
		if ( array_key_exists( $space, PowerString::$passwdNamespaces ) ) {
			$space = PowerString::$passwdNamespaces[$space];

		// Composite namespace
		} else if ( in_array( $space, array('nc','nm', 'ncm', 'ns', 'cs', 'ms', 'ni', 'ci', 'mi', 'cmi', 'ncmi' ) ) ) {

			$_space = '';

			for ( $i=0; $i<strlen($space);$i++ ) {

				$_space .= self::$passwdNamespaces[$space[$i]];

			}

			$space = $_space;

		}

		// Full namespace
		if ( empty($space) ) {

			foreach ( array_keys(self::$passwdNamespaces) as $k ) {
				$space.= self::$passwdNamespaces[$k];
			}

		// Custom namespace
		// you can set whathever char you need to use as password char.
		// you can use placeholders for simple namespaces ":n" for number namepace, etc
		//
		// "abc:n:i" means a, b, c, "n" namespace, "i" namespace
		// "abc:" means a, b, c, :
		} else {

			$space = PowerString::insert( $space, self::$passwdNamespaces );

		}

		// Utility vars
		$lspace = strlen($space)-1;
		$passwd = '';

		// Generate random password
		for ( $i=0; $i<rand( $min, $max ); $i++ ) $passwd .= $space[rand(0,$lspace)];
		return $passwd;

	}


/**
 * zeroFill()
 * ==========
 *
 * Prepends a number of chars (0) at the beginning of a given string.
 * zeroFill( 99, 4 ) -> 0099
 */
	function zeroFill( $str, $len = 3, $options = array() ) {

		// Default options
		if ( is_bool($options) ) 	$options = array( 'append'	=> $options );
		if ( is_string($options) ) 	$options = array( 'char'	=> $options );
		$options += array( 'append'=>false, 'char'=>'0' );

		while ( strLen($str) < $len )

			if ( $options['append'] ) {
				$str .= $options['char'];

			} else {
				$str = $options['char'].$str;

			}

		return $str;

	} // EndOf: "zeroFill()" ######################################################################




/**
 * euro()
 * ======
 *
 * float2euro utility
 * changes decimal dot sparator to a slash then adds dots as 1/1000 separator
 */
	function euro( $val = 0 ) {
		$val = str_replace('.',',',$val);
		if ( strPos($val,',') !== false ) {
			$int = subStr($val,0,strPos($val,','));
			$dec = subStr($val,strPos($val,',')+1,strLen($val));
		} else {
			$int = $val;
			$dec = '';
		}

		// Applicazione dei "punti" di separazione delle migliaia.
		$mill 	= '';
		$cont	= 0;

		for ( $i=(strLen($int)-1); $i>=0; $i-- ) {

			if ( $cont == 3 ) {
				$mill = '.' . $mill;
				$cont = 0;
			}

			$mill = substr($int,$i,1) . $mill;
			$cont++;
		}

		if ( empty($mill) ) $mill = "0";
		$dec = substr($dec,0,2);

		return $mill.",".self::zeroFill( $dec, 2, true );

	} // EndOf: "euro()" ##########################################################################



/**
 * lastIndexOf()
 * emulates javascript method
 * @param string $haystack
 * @param string $needle
 */
	public static function lastIndexOf( $haystack = '', $needle = '' ) {

		if ( strpos($haystack,$needle) === false ) return false;

		return strlen($haystack) - strpos(strrev($haystack),$needle) - ( strlen($needle) - 1 );

	}


/**
 * explodeLastOccourrence
 * It works just like explode() and outputs an array of tokens.
 *
 * The difference is this method tokenizes only at the last occourrence of needle:
 *
 *     PowerString::explodeLastOccourrence('/','/path/subpath/file')
 *     -> array( '/path/subpath', 'file' )
 *
 * @param string $needle
 * @param string $haystack
 */

	public static function explodeFirstOccourrence( $needle = '', $haystack = '' ) {

		/*
		$tmp = strpos( $haystack, $needle );

		return array(
			substr( $haystack, 0, $tmp ),
			substr( $haystack, ($tmp+strlen($needle)), strlen($haystack) ),
		);

		??? May following solution performs better?
		*/

		$tokens = explode( $needle, $haystack );

		return array(
			array_shift($tokens),
			implode($needle, $tokens)
		);

	}

	public static function explodeLastOccourrence( $needle = '', $haystack = '' ) {

		/*
		$tmp = PowerString::lastIndexOf( $haystack, $needle );

		return array(
			substr( $haystack, 0, $tmp-1 ),
			substr( $haystack, ($tmp+strlen($needle)-1), strlen($haystack) ),
		);

		??? May following solution performs better?
		*/

		$tokens = explode( $needle, $haystack );

		$tmp = array_pop($tokens);

		return array(
			implode($needle, $tokens),
			$tmp
		);

	}


	/**
	 * convert an url format string to an array:
	 *
	 *     foo=aa&foo1=bb
	 *     -> array(
	 *       'foo' => 'aa',
	 *       'foo1' => 'bb'
	 *     )
	 *
	 */
	public static function str2array( $string ) {

		$list = array();

		foreach ( explode('&',$string) as $item ) {

			if ( strpos($item,'=') !== false ) {

				list( $key, $val ) = PowerString::explodeFirstOccourrence( '=', $item );

				$list[$key] = $val;

			} else {

				$list[] = $val;

			}

		}

		return $list;

	}

}