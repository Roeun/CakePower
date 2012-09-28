<?php

class CakePowerComponent extends Component {
	
	protected $Controller = null;
	
	
	
	
/**
 * Import a link to the controller
 */	
	public function initialize($controller) {
		
		parent::initialize($controller);
		
		$this->Controller =& $controller;
		
	}
	
	
	
	
/**	
 * Import a model into component namespace
 */
	protected function loadModel( $model ) {
		
		// Search for a model instance from the controller
		if ( isset($this->Controller->$model) && $this->Controller->$model instanceof Model ) {
			$this->$model = $this->Controller->$model;
		
		// Load model from registry
		} else {
			$this->$model = ClassRegistry::init($model);	
			
		}
	
	}
	
	
	
/**	
 * Shortcut to PowerConfig::pval() method to access a param by name
 * searching multiple places
 */
	public function pval( $key = null, $def = null ) { return PowerConfig::pval($key,$def); }
	
	
	

}