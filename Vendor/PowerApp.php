<?php

class PowerApp {

	
	
	
	
/**
 * Collects a list of CakePower internal methods.
 * These methods are not listed in controller actions.
 * 
 * @var array
 */	
	private static $_powerMethods = null;

	
	
	
	
/**
 * Build a multi dimensional array of available actions.
 * Plugin/controller/action
 * 
 * Example of outputted data:
 * array(
 *     'CakePanel' => array(
 *         'UsersController' => array(
 *             'admin_edit' => array(
 *                 'method' => 'admin_edit',
 *                 'label' => custom label setted in controller\'s $adminActions property'
 *             ),
 *         	   'admin_add' => array()
 *         ),
 *     ),
 *     'App' => array(
 *         'FooController' => array()
 *     )
 * )
 */
	public static function adminActions() {
		
		$data = array();
		
		foreach ( PowerConfig::get('app.plugins') as $pluginName ) {
			
			$controllers = self::adminPluginActions($pluginName);
			
			// Skip empty plugins.
			if ( !empty($controllers) ) $data[$pluginName] = $controllers;
			
		}
		
		$data['App'] = self::adminAppActions();
		
		return $data;
		
	}

	
	
	
	
	
/**	
 * Build a mono dimensional list of admin actions available into the app.
 * 
 * Example of outputted data:
 * array(
 *     'UsersController.admin_edit' => array(
 *         'controller' => 'UsersController',
 *         'method' => 'admin_edit',
 *         'label' => 'custom label setted in controller\'s $adminActions property' 
 *     )
 * )
 */
	public static function adminActionsList() {
		
		$actionsList = array();
		
		foreach ( self::adminActions() as $plugin=>$controllers ) {
			
			foreach ( $controllers as $controller=>$actions ) {
				
				foreach ( $actions as $action=>$info ) {
					
					$info['controller'] = $controller;
					
					$actionsList[$controller.'.'.$action] = $info;
				
				}
				
			}
			
		}
		
		return $actionsList;
		
	}
	
	
	
	public static function adminPluginActions( $pluginName = '' ) {
		
		$data = array();
		
		foreach ( App::objects( $pluginName . '.Controller') as $controllerName ) {
			
			// Skip the app controller
			if ( $controllerName == $pluginName . 'AppController' ) continue;
			
			$actions = self::adminControllerActions( $controllerName, $pluginName );
			
			if ( !empty($actions) ) $data[$controllerName] = $actions;
			
		}
		
		return $data;
		
	}
	
	public static function adminAppActions() {
		
		$data = array();
		
		foreach ( App::objects('Controller') as $controllerName ) {
			
			$actions = self::adminControllerActions( $controllerName );
			
			if ( !empty($actions) ) $data[$controllerName] = $actions;
		
		}
		
		return $data;
		
	}
	
	public static function adminControllerActions( $controllerName = '', $pluginName = '' ) {
		
		// Import class file into the system.
		if ( !self::_importController($controllerName,$pluginName) ) return false;
		
		// Fetch all class methods.
		$methods = get_class_methods($controllerName);
		
		// Fetch class based actions info.
		$classVars = get_class_vars($controllerName);
		
		// Setup the actions array.
		$actions = array('*'=>array(
			'method' 	=> '*',
			'label'		=> '(*) - controllo completo'
		));
		if ( empty($methods) ) return $actions;
		
		// Check controller's actions.
		foreach ( $methods as $method ) {
			
			// Check if the method is an admin action or not.
			if ( self::isPowerMethod($method) ) continue;
			if ( strpos($method,'admin_') === false ) continue;
			
			$actions[$method] = array(
				'method' 	=> $method,
				'label'		=> $method
			);
		
		}
		
		// If there is no admin actions the controller will be skipped!
		if ( count($actions) == 1 ) return array();
		
		// Merge collected actions with the informations stored internally in the controller class.
		if ( !empty($classVars['adminActions']) ) {
			
			foreach ( $classVars['adminActions'] as $key=>$val ) {
				
				if ( !is_array($val) ) $val = array( 'label'=>$val );
				
				$classVars['adminActions'][$key] = $val;
				
			}
			
			$actions = PowerSet::merge( $actions, $classVars['adminActions'] );
		}
		
		// Fix internal values.
		foreach ( $actions as $name=>$info ) {
			
			if ( empty($info['method']) ) 	$info['method'] = $name;
			if ( empty($info['label']) )	$info['label']	= $info['method'];
			
			$info['label'] = __($info['label']);
			
			$actions[$name] = $info;
			
		}
		
		return $actions;
		
	}
	
	
	
	public static function isPowerMethod( $methodName = '' ) {
		
		if ( empty(self::$_powerMethods) ) self::_buildPowerMethods();
		
		return in_array( $methodName, self::$_powerMethods );
	
	}
	

	
	
	
/**	
 * Build the internal list of protected methods.
 * All Controller's and CakePowerController methods are excluded from the actions list.
 */
	private static function _buildPowerMethods() {
		
		self::$_powerMethods = array();
		
		App::import( 'CakePower.Controller', 'CakePowerController' );
		
		foreach ( get_class_methods('CakePowerController') as $method ) {
			
			self::$_powerMethods[] = $method;
			
		}
		
	}
	

	
	
/**
 * Imports a controller's source file into the system.
 * 
 * NOTICE: I found problems using App::import() for App/Controller files...
 * In this case I do need a php require_once() call.
 *  
 * @param string $controllerName
 * @param string $pluginName
 */	
	private static function _importController( $controllerName, $pluginName = '' ) {
		
		$context = 'Controller';
		
		// App Controller
		if ( empty($pluginName) ) {
			
			$imported = App::import( $controllerName, $context );
			
			if ( !class_exists($controllerName) ) {
			
				foreach ( App::path($context) as $tmp ) {
	
					require_once( $tmp . $controllerName . '.php' );
					
				}
				
				$imported = class_exists($controllerName);
			
			}
			
		
		// Plugins Controller
		} else {
			$context = $pluginName . '.' . $context;
			$imported = App::import( $context, $controllerName );
			
		}
		
		return $imported;
		
	}
	
	
	

	
	
	
/**	
 * Loads an EventListner Class into the CakeEventManager
 * =====================================================
 * 
 * PowerApp::loadEventListener( 'Vendor', 'Foo', 'MyEventClass' );
 * 
 * Enter description here ...
 * @param unknown_type $repo
 * @param unknown_type $sub
 * @param unknown_type $class_name
 */
	
	public static function loadEventListener( $repo, $sub, $class_name = '' ) {
		
		// No sub-package folder present
		if ( empty($class_name) ) {
			$class_name = $sub;
			$sub 		= '';
		}
		
		App::uses( 'CakeEventListener', 'Event' );
		App::uses( 'Folder', 			'Utility' );
		
		foreach ( App::path($repo) as $repo_path ) {
			
			$repo_path .= $sub . DS;
			
			if ( !file_exists($repo_path) ) continue;
			
			// Import and add instance to the CakeEventManager
			if ( App::import( $repo.'/'.$sub, $class_name ) ) CakeEventManager::instance()->attach( new $class_name() );
			
		}
		
	}
	
	
/**
 * Loads all classes files found inside a package
 * ==============================================
 * 
 * PowerApp::loadEventListeners( 'Vendor', 'AppEvents' );
 * 
 * @param unknown_type $repo
 * @param unknown_type $sub
 */	
	public static function loadEventListeners( $repo, $sub ) {
		
		App::uses( 'CakeEventListener', 'Event' );
		App::uses( 'Folder', 			'Utility' );
		
		foreach ( App::path($repo) as $repo_path ) {
			
			$repo_path .= $sub . DS;
			
			if ( !file_exists($repo_path) ) continue;
			
			$fld 	= new Folder($repo_path);
			$dir 	= $fld->read();
			foreach ( $dir[1] as $file_name ) {
				
				if ( PowerString::getLastTrunk($file_name) !== 'php' ) continue;
				$class_name = PowerString::getFirstTrunk($file_name);
				
				// Import and add instance to the CakeEventManager
				if ( App::import( $repo.'/'.$sub, $class_name ) ) CakeEventManager::instance()->attach( new $class_name() );
				
			}
		
		}
	
	}
	
	
	
	
	
	
/**	
 * Tryies to intercept a REST action...
 * i'm not sure about this code... it is used by Request::addDetector
 */
	public static function is_rest_request() {
		
		// Test if the controller is a REST mapped resource.
		if ( !in_array( PowerConfig::get('request.params.controller'), Router::mapResources(array()) ) ) return false;
		
		// A request with no extension set often rapresent a standard CakePHP request!
		if ( !PowerConfig::exists('request.params.ext') ) return false;
		
		return true;
	
	}
	

}