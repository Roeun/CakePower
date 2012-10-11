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



/**
 * CakePowerBehavior
 */
class CakePowerBehavior extends ModelBehavior {
	
	
/**	
 * behavior configuration default values.
 */
	protected $_defaultValues = array();
	
	
	
	
	public function setup( Model $Model, $settings = array() ) {
		
		$this->_applyDefaultValues( $Model, $settings );
	
	}

	
	
/**	
 * Binds default values to the model's alias settings key.
 */
	protected function _applyDefaultValues( Model $Model, $settings = array() ) {
		
		if ( !isset($this->settings[$Model->alias]) ) {
			$this->settings[$Model->alias] = $this->_defaultValues;
		}
		
		$this->settings[$Model->alias] = array_merge( $this->settings[$Model->alias], (array)$settings );
		
	}
	
	
	
	
/**	
 * Shortcut to PowerConfig::pval() method to access a param by name
 * searching multiple places
 */
	public function pval( $key = null, $def = null ) { return PowerConfig::pval($key,$def); }
	
	

}