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




App::import( 'View/Helper', 'SessionHelper' );



class PowerSessionHelper extends SessionHelper {
	
	public $flashKeys = array( 'message', 'flash', 'info', 'auth', 'alert', 'ko', 'error', 'ok', 'success' );
	
	// sets up a custom template path to load typed flash messages.
	// could take values like:
	// 'flash/{key}' to use custom elements like:
	// -> Elements/flash/error.ctp
	// -> Elements/flash/success.ctp
	//
	// by default is set to NULL to let CakePHP default elements to be used!
	public $flashTpl = null;

	
	
/**	
 * Output a list of flash messages by listening for some notable
 */
	public function flashes( $keys = array(), $attrs = array() ) {
		
		// Handle requests with an ALL keys param!
		if ( $keys === ALL ) $keys = null;
		
		// Fetch the list of flash keys to listen from the global configuration object.
		// Applies a default hard coded list to grant minimal support.
		if ( empty($keys) ) $keys = PowerConfig::get( 'app.flashes', $this->flashKeys );
		
		// Step through the list of keys to flash out!
		$out = '';
		
		foreach ( $keys as $k ) $out .= $this->flash( $k, $attrs );
		
		return $out;
		
	}
	
	
	
	public function flash( $key=ALL, $attrs=array() ) {
		
		// shortcuts to flashes
		if ( $key === ALL ) 	return $this->flashes( null, $attrs );
		if ( is_array($key) ) 	return $this->flashes( $key, $attrs );
		
		// Implement custom flash search path template from helper's properties
		if ( empty($attrs['element']) && !empty($this->flashTpl) ) $attrs['element'] = $this->flashTpl; 
		
		// Parse the "key" param from the name of the element to render as flash message template
		if ( !empty($attrs['element']) ) {
			
			$attrs['element'] = PowerString::tpl( $attrs['element'], array(
				'key' 	=> $key
			));
			
		}
		
		// Standard key based flash message
		if ( !empty($key) && is_string($key) ) return parent::flash( $key, $attrs );
		
		// Multiple flash messages ( $key = null )
		return $this->flashes( $key, $attrs );
	
	}
	
}
	
	