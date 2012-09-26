<?php
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

}