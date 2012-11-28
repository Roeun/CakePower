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
	protected function loadModel($model) {
		
		// Search for a model instance from the controller
		if ( isset($this->Controller->$model) && $this->Controller->$model instanceof Model) {
			$this->$model = $this->Controller->$model;
		
		// Load model from registry
		} else {
			$this->$model = ClassRegistry::init($model);	
			
		}
		
		return $this->$model;
	}
	
	
	
/**	
 * Shortcut to PowerConfig::pval() method to access a param by name
 * searching multiple places
 */
	public function pval( $key = null, $def = null ) { return PowerConfig::pval($key,$def); }
	
	
	

}