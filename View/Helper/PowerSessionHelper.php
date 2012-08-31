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
		
		// shortcuts to flashes
		if ( $key === ALL ) 	return $this->flashes( null, $attrs );
		if ( is_array($key) ) 	return $this->flashes( $key, $attrs );
		
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
	
	