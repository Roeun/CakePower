<?php
App::import( 'View/Helper', 'SessionHelper' );



class PowerSessionHelper extends SessionHelper {
	
	public $flashKeys = array( 'auth', 'ko', 'alert', 'ok', 'message', 'flash' );
	
/**	
 * Output a list of flash messages by listening for some notable
 */
	public function flashes( $keys = array(), $attrs = array() ) {
		
		// Fetch the list of flash keys to listen from the global configuration object.
		// Applies a default hard coded list to grant minimal support.
		if ( empty($keys) ) $keys = PowerConfig::get( 'app.flashes', $this->flashKeys );
		
		// Step through the list of keys to flash out!
		$out = '';
		
		foreach ( $keys as $k ) $out .= $this->flash( $k, $attrs );
		
		return $out;
		
	}
	
	
	
	public function flash( $key=null, $attrs=array() ) {
		
		// Standard key based flash message
		if ( !empty($key) && is_string($key) ) return parent::flash( $key, $attrs );
		
		// Multiple flash messages
		return $this->flashes( $key, $attrs );
	
	}
	
}
	
	