<?php
/**
 * CakePOWER AppController
 *
 * This controller 
 */
 
App::uses('Controller', 'Controller');

// Lazy loads the CakePowerComponent
App::uses( 'CakePowerComponent', 'CakePower.Controller/Component' );

class CakePowerController extends Controller {
	

/**
 * CakePower Info
 * store some static informations about CakePower
 * 
 * components and helpers can be setted to "false" to do not force to load
 * the library. In this case parent class must explicitly include the class into its configuration. 
 */
	protected $__cakePower = array(
		'version' 			=> '1.0',
		'components' 		=> array( 'Session', 'Auth'=>false ),
		'helpers' 			=> array( 'Html', 'Session', 'Paginator', 'Form' ),
	);
	
	
/**
 * CakePower Contructor
 *
 * It fill some configuration properties to use implemented CakePHP classes.
 */
	public function __construct($request = null, $response = null) {
		
		// Automagically loads CakePower's Auth Component Layer
		if ( isset($this->components['Auth']) ) $this->__cakePower['components']['Auth'] = true;
		
		// Loads extended core classes setting an alias to use them with the normal app names.
		foreach( array('components','helpers') as $type ) {
			
			foreach ( $this->__cakePower[$type] as $key=>$val ) {
				
				if ( is_numeric($key) ) {
					$cmp 	= $val;
					$load 	= true;
				} else {
					$cmp 	= $key;
					$load 	= $val;
				}
				
				if ( empty($this->{$type}[$cmp]) && $load === true ) $this->{$type}[$cmp] = array();
				if ( isset($this->{$type}[$cmp]) && empty($this->{$type}[$cmp]['className']) ) $this->{$type}[$cmp]['className'] = 'CakePower.Power'.$cmp;
				
			}
			
		}
		
		// Add a is('rest') detector to the request object.
		// detecting a REST request is responsibility of the PowerApp class!
		$request->addDetector( 'rest', array(
			'callback' => array( 'PowerApp', 'is_rest_request' )
		));
		
		
		parent::__construct( $request, $response );
		
		
		// Set request params to the global access configuration to allow an easy access.
		PowerConfig::set( 'request.params', $this->request->params );
		PowerConfig::set( 'request.data', 	$this->request->data ) ;
		
		// TMP: here will be listed controller's services...
		foreach ( $this->methods as $i=>$val ) {
			if ( substr($val,0,2) == '__' ) $this->methods[$i] = substr($val,2,100);
		}
	
	}
	
	
	
	
	
	
/**	
 * Utilities to add libraries to the controller during the constructor method.
 * Use these methods in your controller's __construct() method
 */
	
	public function addLib( $type, $name, $config = array(), $override = true ) {
		
		if ( !empty($this->{$type}[$name]) && $override !== true ) return false;
		
		$this->{$type}[$name] = $config;
		
		return true;
	
	}
	
	public function addHelper( $name, $config = array(), $override = true ) {
		
		return $this->addLib( 'helpers', $name, $config, $override );
		
	}
	
	public function addComponent( $name, $config = array(), $override = true ) {
		
		return $this->addLib( 'components', $name, $config, $override );
		
	}
	
	public function addModel( $name, $config = array(), $override = true ) {
		
		if ( !in_array( $name, $this->uses) ) $this->uses[] = $name;
		
	}
	
	public function addBehavior( $name, $config = array(), $override = true ) {
		
		return $this->addLib( 'behaviors', $name, $config, $override );
		
	}
	
	
	
	
	
	
	
	
	
/**	
 * tell() replace set() adding implicit support for ajax/rest requests.
 * 
 * it handle the same input required by set()
 */
	public function tell( $key, $val = null, $msg = null ) {
		
		// rest/ajax message as 2nd parameter
		if ( is_array($key) && !empty($val) ) $msg = $val;
		
		// handle simple key/value esportation translating to an array
		if ( !is_array($key) ) $key = array( $key=>$val );
		
		// apply a default message
		if ( empty($msg) ) $msg = 'export';
		
		// Export REST and AJAX data
		if ( $this->Session->restOk( $msg, $key )) return;
		$this->Session->ajaxOk( $msg, $key );
		
		// Export data to the view
		$this->set($key);
	
	}

	
	
	
/**	
 * Shortcut to PowerConfig::pval() method to access a param by name
 * searching multiple places
 */
	public function pval( $key = null, $def = null ) { return PowerConfig::pval($key,$def); }
	
	
	/*
	public function invokeAction(CakeRequest $request) {
		
		try {
			$method = new ReflectionMethod($this, $request->params['action']);

			if ($this->_isPrivateAction($method, $request)) {
				throw new PrivateActionException(array(
					'controller' => $this->name . "Controller",
					'action' => $request->params['action']
				));
			}
			return $method->invokeArgs($this, $request->params['pass']);

		} catch (ReflectionException $e) {
			
			/** @@CakePOWER@@ **
			// Services... //
			$private_method = '__' . $request->params['action'];
			try {
				$method = new ReflectionMethod($this, $private_method);
				return $method->invokeArgs($this, $request->params['pass']);
			} catch (ReflectionException $e) {}
			/** ##CakePOWER## **
			
			
			if ($this->scaffold !== false) {
				return $this->_getScaffold($request);
			}
			throw new MissingActionException(array(
				'controller' => $this->name . "Controller",
				'action' => $request->params['action']
			));
		}
	}
	*/
	
	
	
}