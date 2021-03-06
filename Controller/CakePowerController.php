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
 * CakePOWER AppController
 *
 * This controller 
 */
 
App::uses('Controller', 'Controller');

// Lazy loads the CakePowerComponent
App::uses('CakePowerComponent', 'CakePower.Controller/Component');

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
		
		// Alias CakePower libraries
		$this->aliasLibs('components', $this->__cakePower['components'], 'CakePower.Power');
		$this->aliasLibs('helpers', $this->__cakePower['helpers'], 'CakePower.Power');
		
		// Add a is('rest') detector to the request object.
		// detecting a REST request is responsibility of the PowerApp class!
		$request->addDetector( 'rest', array(
			'callback' => array( 'PowerApp', 'is_rest_request' )
		));
		
		
		
		// -- evt --
		// CakePower.beforeConstruct 
		CakeEventManager::instance()->dispatch($e =new CakeEvent('CakePower.beforeConstruct',$this,array(
			'request' 	=> $request,
			'response' 	=> $response
		)));
		
		if ( !empty($e->results['request']) ) 	$request 	= $e->results['request'];
		if ( !empty($e->results['response']) ) 	$response 	= $e->results['response'];
		// -- evt --
		
		
		
		parent::__construct( $request, $response );
		
		
		// Set request params to the global access configuration to allow an easy access.
		PowerConfig::set( 'request.params', $this->request->params );
		PowerConfig::set( 'request.data', 	$this->request->data );
		
		
		// TMP: here will be listed controller's services...
		foreach ( $this->methods as $i=>$val ) {
			if ( substr($val,0,2) == '__' ) $this->methods[$i] = substr($val,2,100);
		}
		
		// Loads Event Listeners
		foreach ( PowerConfig::get('app.plugins') as $pluginName ) PowerApp::loadEventListeners( 'EventListener', '', $pluginName );
		PowerApp::loadEventListeners( 'EventListener' );
		
		
		
		// -- evt --
		// CakePower.onLoad 
		CakeEventManager::instance()->dispatch($e = new CakeEvent('CakePower.onConstruct',$this,array(
			'request' 	=> $request,
			'response' 	=> $response
		)));
		// -- evt --
		
		
	}
	
	
	/**
	 * Alias a list of libraries to specific classNames.
	 * 
	 * Pretend you realized your custom FooHtmlHelper that extends CakePHP's core HtmlHelper.
	 * If you want to use your class without change View API you need to alias 
	 * 
	 * View's Html helper to your custom class:
	 *     $this->aliasLib('helpers','Html,'Foo')
	 * 
	 * or a custom class from a plugin:
	 *     $this->aliasLib('helpers','Html,'CustomPlugin.Foo')
	 * 
	 * 
	 * @param string $type library type [helpers|components|behaviors]
	 * @param array $list [Html,Session,Auth,...]
	 * @param string $prefix [CakePower.Power|CakePanel.Panel|...]
	 */
	protected function aliasLibs($type, $list, $prefix = '') {
		if (is_string($list)) {
			$list = array($list);
		}
		foreach ($list as $key=>$val) {
			if ( is_numeric($key) ) {
				$cmp 	= $val;
				$load 	= true;
			} else {
				$cmp 	= $key;
				$load 	= $val;
			}
			
			if ( empty($this->{$type}[$cmp]) && $load === true ) $this->{$type}[$cmp] = array();
			if ( isset($this->{$type}[$cmp]) && empty($this->{$type}[$cmp]['className']) ) $this->{$type}[$cmp]['className'] = $prefix.$cmp;
		}
	}
	
	
	
	
	
/**	
 * Utilities to add libraries to the controller during the constructor method.
 * Use these methods in your controller's __construct() method
 */
	
	public function addLib($type, $name, $config = array(), $override = true) {
		$this->{$type} = PowerSet::def($this->{$type});
		if (!empty($this->{$type}[$name]) && $override !== true) return false;
		$this->{$type}[$name] = $config;
		return true;
	}
	
	public function addHelper($name, $config = array(), $override = true) {
		return $this->addLib('helpers', $name, $config, $override);
	}
	
	public function addComponent($name, $config = array(), $override = true) {
		return $this->addLib('components', $name, $config, $override);
	}
	
	public function addBehavior($name, $config = array(), $override = true) {
		return $this->addLib('behaviors', $name, $config, $override);
	}
	
	
	/**	
	 * Appends a model to the $uses key of the controller
	 * 
	 * @param string $name
	 */
	public function addModel($name) {
		$this->uses = PowerSet::def($this->uses);
		if (!in_array($name, $this->uses)) {
			$this->uses[] = $name;	
		}
	}
	
	
	
	
	
	
	
	
	
/**	
 * tell() replace set() adding implicit support for ajax/rest requests.
 * 
 * it handle the same input required by set()
 */
	public function tell( $key = null, $val = null, $msg = null ) {
		
		if ( func_num_args() === 1 && is_string($key) ) {
			$msg = $key;
			$key = null;
			$val = null;
		}
		
		// rest/ajax message as 2nd parameter
		if ( is_array($key) && !empty($val) ) $msg = $val;
		
		// handle simple key/value esportation translating to an array
		if ( !is_array($key) && $key !== null ) $key = array( $key=>$val );
		
		// Merge with already setted view vars
		if ( !empty($this->viewVars) ) {
			if ( empty($key) ) $key = array();
			$key = PowerSet::merge( $this->viewVars, $key );
		}
		
		// apply a default message
		if ( empty($msg) ) $msg = 'export';
		
		// Export REST and AJAX data
		if ( $this->Session->restMsg( $msg, $key )) return true;
		$this->Session->ajaxMsg( $msg, $key );
		
		// Export data to the view
		$this->set($key);
	
	}
	
	
	
	public function redirect($url, $status = null, $exit = true) {
		
		// AJAX Redirect reqest
		if (
			$this->Session->ajaxResponse( null, 'redirect', array(
				'redirect' 	=> $url,
				'status'	=> $status ? $status : 307
			))
		
		) return;
			
		// REST redirect request
		if (
		
			$this->Session->restResponse( null, 'redirect', array(
				'redirect' 	=> $url,
				'status'	=> $status ? $status : 307
			))
		
		) return;
		
		parent::redirect($url,$status,$exit);
		
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