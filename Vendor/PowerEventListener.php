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
 * Custom PowerEventListener Class
 * ==============================
 * 
 * This class implements CakeEventListener interface with some utility methods
 * and a pretty overload system to add missing features to the standard events listeners collection class.
 * 
 */
App::uses('CakeEventListener', 'Event');
class PowerEventListener implements CakeEventListener {
	
	
/**
 * Class Name
 * ==========
 * 
 * It's being used to implement specific events name like
 * "{EventName}.Controller.beforeFilter"
 * 
 */	
	public $name 				= '';
	
	
/**	
 * Implemented Events List
 * =======================
 * 
 * It is an alternative to "implementedEvents()" declaration.
 * You can define implemented events here the CakePHP way or you can enjoy some facilities and powerful tools.
 * 
 * After class initialization this property will contain a full map of events/methods.
 * You can use this property to find an method's name by related event's name.
 * 
 * You can define events this way:
 * var events = array(
 *   'Foo.Foo1',
 *   'Foo.Foo1.Foo2' => 'foo_custom_method',
 *   '{ControllerName}.Foo'
 * ) 
 * 
 * CakePower allow you do implement events (and methods) with PLACEHOLDERs so your code get more DRY.
 * - EventClassName
 * - PluginName
 * - ControllerName
 * - ActionName
 * - Named{VarName}: allow you to implement placehoders base on any named querystring valuer
 * 
 */
	public $events 				= array(); // eventName => methodName map
	
	
/**	
 * Methods List
 * ============
 * 
 * Collect an array with all implemented methods, each methods is associated with it's own event name.
 * You can use this property to find an event's name by related method's name.
 * 
 */
	public $methods 			= array(); // methodName => eventName map
	
	
/**
 * Private properties to implement magic variables into event's names
 */	
	protected $_eventReplace  	= array();
	protected $_methodReplace 	= array();
	protected $_swipeReplace 	= array();
	
	
/**	
 * Automagically imports Models, Components and Helpers into the class scope.
 */
	protected $_importProperties 	= true;
	protected $_importedProperties 	= array();
	

	
/**
 * Actual Properties
 * =================
 * 
 * Shortcuts to particular informations about actual event call
 * 
 */
	public $event 				= null;
	public $subject				= null;
	public $data				= null;
	public $eventName 			= '';
	public $methodName 			= '';
	
	

	
/**	
 * Events Prefix
 * =============
 * 
 * Automagically adds a prefix to all $events keys so you can write less code!
 * 
 * $this->eventsPrefix = 'Foo.';
 * $this->events = array('Foo1','Foo2')
 * 
 * resulting implemented events are:
 * Foo.Foo1 -> fooFoo1()
 * Foo.Foo2 -> fooFoo2()
 * 
 */
	public $eventsPrefix		= '';
	
	
/**	
 * Elements Path Prefix
 * ====================
 * 
 * $this->elementPrefix = 'Foo/';
 * $this->element('foo')
 * 
 * resulting element path:
 * /App/Elements/Foo/foo.ctp
 * 
 */
	public $elementPrefix 		= '';
	
	
/**	
 * Render Var
 * ==========
 * 
 * render() method should render a given element into a defined return key.
 * This setting define what key to use!
 * 
 */
	public $renderVar 			= 'html';
	public $stopOnRender		= false;
	
	
	
	
	
/**	
 * CONSTRUCTOR
 * ===========
 * 
 * Collects top level class name if empty
 * 
 */
	function __construct() {
		
		if ( empty($this->name) ) $this->name = get_called_class();

		// fill up variable replacement values
		$this->_eventReplace = array(
			'EventClassName'	=> $this->name,
			'PluginName'		=> PowerConfig::get( 'request.params.plugin', '' ),
			'ControllerName'	=> PowerConfig::get( 'request.params.controller', '' ),
			'ActionName'		=> PowerConfig::get( 'request.params.action', '' )
		); 
		
		$this->_methodReplace = array(
			'EventClassName'	=> '',
			'PluginName'		=> ucfirst(PowerConfig::get( 'request.params.plugin', '' )),
			'ControllerName'	=> ucfirst(PowerConfig::get( 'request.params.controller', '' )),
			'ActionName'		=> ucfirst(PowerConfig::get( 'request.params.action', '' ))
		);
		
		// append named params variable replacement values
		if ( PowerConfig::isArray('request.params.named') ) foreach ( PowerConfig::get('request.params.named') as $k=>$v ) {
			$this->_eventReplace['Named'.ucfirst($k)] 	= $v;
			$this->_methodReplace['Named'.ucfirst($k)] 	= ucfirst($v);
		}
		
		// variable replacemente swipe values
		$this->_swipeReplace = array( '..'=>'.' );
	
	}
	
	
	
/**	
 * implementedEvents() - default implementation
 * ============================================
 * 
 * 
 * 
 */
	public function implementedEvents() {
		
		$declaredEvents 	= $this->events;
		$implementedEvents 	= array();
		
		// Reset internal map of implemented events
		$this->events 		= array();
		$this->methods		= array();
		
		// Build map of implemented events
		foreach ( $declaredEvents as $event=>$method ) {
			
			// Only event name was given.
			// should evaulate an automated internal method to bind
			if ( is_int($event) ) {
				$event 	= $method;
				
				$method = PowerString::tpl( $event, $this->_methodReplace, $this->_swipeReplace );
				$method = lcfirst(Inflector::camelize(str_replace('.','_',$method)));
			
			// Method was given as a value for the event key
			} else {
				$method = PowerString::tpl( $method, $this->_methodReplace, $this->_swipeReplace );
			}
			
			// Event's should be prefixed with a custom global property
			$event 	= PowerString::tpl( $this->eventsPrefix . $event, $this->_eventReplace, $this->_swipeReplace );
			
			$this->events[$event] 		= $method;
			$this->methods[$method]		= $event;
			
			$implementedEvents[$event]	= '__evt__' . $method;
		}
		
		#debug($this->events);
		#debug($this->methods);
		#debug($implementedEvents);
		
		return $implementedEvents;
	
	}
	
	
	
	
	
	
/**	
 * Dinamic call of class events methods
 * ====================================
 * 
 * Core's "implementedEvents()" tries to call class methods prefixed by "__evt__" string.
 * This way every access to class methods pass through this logic.
 * 
 * Here we store a class scoped reference to the CakeEvent and the requested method name.
 * So we can creates some interesting automagically methods like "element" and "render" 
 * 
 */
	public function __call( $name, $args ) {
		
		// Reset internal values
		$this->event 		= null;
		$this->eventName	= null;
		$this->methodName 	= null;
		$this->subject		= null;
		$this->data			= null;
		
		// Free imported properties
		foreach ( $this->_importedProperties as $p ) unset($this->$p);
		
		// Indagates passed arguments
		if ( count($args) > 0 ) {
			
			// Tries to fetch an internal link to the given CakeEvent if available
			if ( gettype($args[0]) === 'object' && get_class($args[0]) === 'CakeEvent' ) {
				$this->event 	=& $args[0];
				$this->subject 	=& $this->event->Subject();
				$this->data		=& $this->event->data;
			}
			
		}
		
		// Imports subject properties
		// request, Models, Components, Helpers
		if ( $this->_importProperties && $this->subject ) {
			
			// Controller
			if ( is_subclass_of($this->subject, 'Controller') ) {
				
				// Import Request
				$this->request =& $this->subject->request;
				$this->_importedProperties[] = 'request';
				
				// Import Models
				foreach ( $this->subject as $k=>$v ) {
					
					if ( is_object($v) && is_subclass_of($v, 'Model') ) {
						$this->{$k} =& $this->subject->$k;	
						$this->_importedProperties[] = $k;
					}
				}
				
				// Import Components
				foreach ( $this->subject->components as $k=>$v ) {
					if ( is_int($k) ) $k = $v;
					$this->$k =& $this->subject->$k;
					$this->_importedProperties[] = $k;
				}
				
			}
			
			// Model
			// !! "data" property should not be imported cause conflict with
			// event's "data" property!
			if ( is_subclass_of($this->subject, 'Model') ) {
				
				// Import Linked Models
				foreach ( $this->subject as $k=>$v ) {
					
					if ( is_object($v) && is_subclass_of($v, 'Model') ) {
						$this->{$k} =& $this->subject->$k;	
						$this->_importedProperties[] = $k;
					}
				}
				
			}
			
			// View
			if ( get_class($this->subject) === 'View' ) {
				
				// Import Request
				$this->request =& $this->subject->request;
				$this->_importedProperties[] = 'request';
				
				// Import Helpers
				foreach ( $this->subject->helpers as $k=>$v ) {
					if ( is_int($k) ) $k = $v;
					$this->$k =& $this->subject->$k;
					$this->_importedProperties[] = $k;
				}
				
			}
			
		}
		
		
		// Indagates request function's name if there is an event based implementation
		if ( strpos($name,'__evt__') !== false ) {
			
			$name = substr($name,'7',255);
			
			if ( method_exists($this,$name) ) {
				
				$this->methodName 	= $name;
				$this->eventName 	= $this->methods[$name];
				
				// Run event's method
				ob_start();
				$val = call_user_method_array( $name, $this, $args );
				$tmp = ob_get_clean();
				
				/**
				 * Start session before output anything!!!
				 * this is a must instruction. i spent 1 hour solving an issue tied up with session!
				 * 
				 * CakePHP lazily loads session (CakeSession) the first time a session is needed.
				 * If some callbacks output anything es with debug a session need to be initializated
				 * before to send data to the client.
				 */
				if ( !empty($tmp) ) {
					App::uses('CakeSession', 'Model/Datasource');
					CakeSession::start();
					echo $tmp;
				}
				
				// Automagically render() call for events methods
				if ( $val === true ) {
					$this->render();
						
				} else if ( is_string($val) && !empty($val) ) {
					$this->render($val);
					
				} else if ( is_array($val) ) {
					$this->render( null, $val );
				}
				
				
			
			}
				
		}
		
	}
	
	
	
/**	
 * Returns Values to the Event Caller
 * ==================================
 * 
 * CakePHP coherence api
 * 
 */
	public function set( $key = '', $val = '' ) {
		
		// set a row of values to rsults
		if ( is_array($key) ) {
			
			foreach( $key as $k=>$v ) $this->set( $k, $v );
			
			return;
		
		}
		
		// set a single value to results
		$this->event->result[$key] = $val;
		
	}

	
/**
 * Return a template scoped element
 * ================================
 * 
 * elements must lies into:
 * /Element/ProjectTemplates/{TemplateClassName}/
 * 
 * If an empty name is given the "actualMethodName" is used to render a binded element name.
 * 
 */	
	public function element( $name = '', $data = array() ) {
		
		if ( !$this->event ) return;
		
		if ( empty($name) && $this->methodName ) 		$name = $this->methodName;
		if ( empty($data) ) 							$data = $this->event->data;
		
		$element_name = $this->elementPrefix . $this->name . '/' . $name;
		#ddebug($element_name);
		
		return $this->subject->element( $element_name, $data );
	
	}
	
	
	
	
	
/**	
 * Render an Element as Event Result
 * =================================
 * 
 * 
 */
	public function render( $name = '', $data = array() ) {
		
		if ( !$this->event ) return;
		
		// Assign a rendered element to the render result key 
		$this->event->result[$this->renderVar] = $this->element( $name, $data );
		
		// Automagically stopping events
		if ( $this->stopOnRender ) $this->event->stopPropagation();
	
	}
	
	
/**	
 * Shortcut to the stopPropagation() event's utility
 */
	public function stop() {
		
		$this->event->stopPropagation();
		
	}
	
	
	
	
	

	
	
}