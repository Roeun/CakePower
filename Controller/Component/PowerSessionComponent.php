<?php
/**
 * CakePOWER :: PowerSessionComponent
 *
 * It extends basic SessionComponent adding shortcuts for flashing messages and other utilities.
 *
 * How to use this component in a controller class (AppController??):
 *
 * public $components = array(
 *     'Session' => array( 'className'=>'PowerSession' )
 * );
 */


App::import('Controller/Component', 'SessionComponent');



class PowerSessionComponent extends SessionComponent {

	protected $Controller;
	
	public function initialize(Controller $controller) {
		parent::initialize($controller);
		$this->Controller = $controller;
	}
	
	

	
	
	
	
	
###################################################################################################
### STANDARD HTTP PAGE REFRESH NOTIFICATION UTILITIES                                           ###
###################################################################################################
	
/**	
 * These methods helps by setting a flash message and redirect to a destination in a single instruction.
 * Each method name define a "type of notification" and the front-end is asked to handle this
 * type by setting up a custom element when rendering the flash message.
 * 
 * $redirect contains a destination url booth in string or array format.
 * redirect works only for non-empty and non-false values!
 * 
 * $options contains params to be given to the element.
 * CakePanel plugin can handle a "title" option for the message!
 * if you pass a string as 3rd param it will be assigned as option[title]
 */
	
	public function flashOk( $str, $redirect = array(), $options = array() ) {
		
		if ( is_string($options) ) $options = array( 'title'=>$options );
		
		$this->setFlash( $str, 'default', $options, 'ok' );
		if ( $redirect ) $this->Controller->redirect( $redirect );
		 
	}
	
	public function flashKo( $str, $redirect = array(), $options = array() ) {
		
		if ( is_string($options) ) $options = array( 'title'=>$options );
		
		$this->setFlash( $str, 'default', $options, 'ko' );
		if ( $redirect ) $this->Controller->redirect( $redirect );
		
	}
	
	public function flashAlert( $str, $redirect = array(), $options = array() ) {

		if ( is_string($options) ) $options = array( 'title'=>$options );
		
		$this->setFlash( $str, 'default', $options, 'alert' );
		if ( $redirect ) $this->Controller->redirect( $redirect );
	
	}
	
	public function flashMsg( $str, $redirect = array(), $options = array() ) {

		if ( is_string($options) ) $options = array( 'title'=>$options );
		
		$this->setFlash( $str );
		if ( $redirect ) $this->Controller->redirect( $redirect );
	
	}
	
	

	

	
	
	
	
	
	
	
	
	
	
	
	

###################################################################################################
### AJAX NOTIFICATION UTILITIES                                                                 ###
###################################################################################################

	
/**	
 * Pack all validation errors into one object to be sent via JSON.
 * This object contains 2 sub-objects: "models" and "fields"
 * 
 * models: will contain errors with a CakePHP model/errors[] notation.
 * fields: will contain an array of fieldID generated with a standard CakePHP naming rule.
 *         each field will contain error description
 */
	public function ajaxErrors() {
		
		$errors = array( 'models'=>array(), 'fields'=>array() );
		
		foreach ( $this->Controller->uses as $modelName ) {
			
			// handle models from plugins with dotted notation.
			if ( strpos($modelName,'.') !== false ) list( $plugin, $modelName ) = explode('.',$modelName);
			
			// skip a model with no errors.
			if ( empty($this->Controller->{$modelName}->validationErrors) ) continue;
			
			// add model's errors info to an object
			$errors['models'][$modelName] = $this->Controller->{$modelName}->validationErrors;
			
			// Calculate each error's field ID. Fastest client usage!
			foreach ( $this->Controller->{$modelName}->validationErrors as $fieldName=>$msgs ) {
				
				$errors['fields'][$modelName.Inflector::camelize($fieldName)] = $msgs[0];
				
			}
			
		}

		return $errors;
		
	}
	
/**	
 * AJAX redirect is allowed only in POST and DELETE mode!
 * Client side form may fore a redirect requset by create a "_redirect" true value in _POST or _GET data.
 */
	
	protected function ajaxRedirect( $redirect ) {
		
		$_redirect = ( $this->Controller->request->is('POST') || $this->Controller->request->is('DELETE') ) ? true : false;
		if ( isset($_GET['_redirect']) ) 	$_redirect = ( $_GET['_redirect'] );
		if ( isset($_POST['_redirect']) ) 	$_redirect = ( $_POST['_redirect'] );
		
		// if redirect is allowed by the action it returns the redirect info.
		if ( $_redirect ) return $redirect;
		
	}
	
	public function forceAjaxRedirect() {
		
		$_POST['_redirect'] = 1;
		$_GET['_redirect'] 	= 1;
		
	}
	
	
/**
 * Ajax Related Notifications
 * 
 * intercept an ajax callback and send to the client a json object with
 * a status information, a message and even some custom data
 */
	
	
	public function ajaxResponse( $status, $message, $options = array() ) {
		
		if ( !$this->Controller->request->is('ajax') ) return;
		
		$data = array(
			
			'CakePower'			=> '1.0',
			'type'				=> 'ajax',
			'status'			=> $status, 
			'message'			=> $message,

			'redirect'			=> '',
			
			'validationErrors'	=> array(),
			'requestData'		=> array(),
			
		);
		
		// Adds controller's validation errors to the data response.
		// you can set "validationErrors = false" in the options to prevent this behavior!
		if ( !isset($options['validationErrors']) ) $options['validationErrors'] = $this->ajaxErrors();
		if ( $options['validationErrors'] === false ) $options['validationErrors'] = array();
		$data['validationErrors'] = $options['validationErrors'];
		unset($options['validationErrors']);
		
		// Adds controller's request data to the data response.
		// you can set "requestData = false" in the options to prevent this behavior!
		if ( !isset($options['requestData']) || $options['requestData'] === true ) $options['requestData'] = $this->Controller->request->data;
		if ( $options['requestData'] === false ) $options['requestData'] = array();
		$data['requestData'] = $options['requestData'];
		unset($options['requestData']);
		
		
		// Export redirect informations to the data array
		if ( isset($options['redirect']) ) {
			if ( !empty($options['redirect']) && is_array($options['redirect']) ) $options['redirect'] = Router::url($options['redirect']);
			$data['redirect'] = $options['redirect'];
			unset($options['redirect']);
		}
		
		// Setup the JSON response with request details and provided data.
		$json = array( '_response'=>$data );
		$json = PowerSet::merge( $json, $options );
		
		if ( !empty($options['status']) ) header('HTTP/1.0 ' . $options['status'] . ' ' . $message, true, 500);
		
		echo json_encode($json);
		exit;
		
	}
	
	
	public function ajaxMsg( $message, $options = array() ) {
		
		$this->ajaxResponse( 'msg', $message, $options );
		
	}
	
	public function ajaxAlert( $message, $options = array() ) {
		
		$this->ajaxResponse( 'alert', $message, $options );
		
	}
	
	public function ajaxOk( $message, $options = array() ) {
		
		$this->ajaxResponse( 'ok', $message, $options );
	
	}
	
	public function ajaxKo( $message, $options = array() ) {
		
		$options+= array( 'status'=>500 );
		
		$this->ajaxResponse( 'ko', $message, $options );
		
	}
	
	
	
	
	

	
	
	
	
	
	
	
	
	
	
###################################################################################################
### REST NOTIFICATION UTILITIES                                                                 ###
###################################################################################################
	
	// Creates the serialization
	protected function _rest_serialize( $options ) {
	
		$_serialize = array();
		
		foreach ( $options as $key=>$val ) {
			
			if ( is_numeric($key) ) {
				$key = $val;
				
			} else {
				$this->Controller->set( $key, $val );
				
			}
			
			$_serialize[] = $key;
		
		}
		
		return $_serialize;
		
	}
	
	public function restResponse( $msg, $message, $options = array() ) {
		
		if ( !$this->Controller->request->is('rest') ) return;
		
		// "_response" data structure.
		$data = array(
			'CakePower'			=> '1.0',
			'type'				=> 'rest',
			'status'			=> $msg, 
			'message'			=> $message,
			 
			'validationErrors'	=> array(),
			'requestData'		=> array(),
			
			// rest specific data
			'_serialize'		=> array()
			
		);
		
		
		// Adds controller's validation errors to the data response.
		if ( empty($options['validationErrors']) ) {
			$data['validationErrors'] = $this->ajaxErrors();
			$data['validationErrors'] = $data['validationErrors']['models'];
		}
		
		// Adds controller's request data to the data response.
		// you can set "requestData = false" in the options to prevent this behavior! 
		if ( !isset($options['requestData']) || $options['requestData'] === true ) $options['requestData'] = $this->Controller->request->data;
		if ( $options['requestData'] === false ) $options['requestData'] = array();
		$data['requestData'] = $options['requestData'];
		unset($options['requestData']);
		
		
		$data['_serialize'] = $this->_rest_serialize($options);
		
		$this->Controller->set( '_response', 	$data );
		$this->Controller->set( '_serialize', 	PowerSet::merge(array('_response'),$data['_serialize']) );
		
		if ( !empty($options['status']) ) header('HTTP/1.0 ' . $options['status'] . ' ' . $message, true, 500);
		
		$this->Controller->render();
		
		return true;
	
	}
	
	public function restOk( $message, $options = array() ) {
		
		return $this->restResponse( 'ok', $message, $options );
		
	}
	
	public function restKo( $message, $options = array() ) {
		
		$options+= array( 'status'=>500 );
		
		return $this->restResponse( 'ko', $message, $options );
		
	}
	
	public function restAlert( $message, $options = array() ) {
		
		return $this->restResponse( 'alert', $message, $options );
		
	}
	
	public function restMsg( $message, $options = array() ) {
		
		return $this->restResponse( 'msg', $message, $options );
		
	}
	
	
	
	

	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
###################################################################################################
### HIGH LEVEL NOTIFICATION UTILITIES                                                           ###
###################################################################################################
	
	public function confirm( $str, $redirect = array(), $options = array() ) {
		
		// Ajax
		$ajaxOptions = PowerSet::merge(array(
			'redirect' 		=> $this->ajaxRedirect($redirect),
			'requestData' 	=> false
		),$options);
		
		$this->ajaxOk( $str, $ajaxOptions );
		
		
		// Rest
		$restOptions = PowerSet::merge(array(
			'requestData' 	=> false
		),$options);
		
		if ( $this->restOk( $str, $restOptions ) ) return;
		
		
		// Http
		$this->flashOk( $str, $redirect );
		
	}
	
	public function error( $str, $redirect = array(), $options = array() ) {
		
		// Ajax
		$ajaxOptions = PowerSet::merge(array(
			'redirect' 		=> $this->ajaxRedirect($redirect),
			'requestData' 	=> false
		),$options);
		
		$this->ajaxKo( $str, $ajaxOptions );
		
		
		// Rest
		$restOptions = PowerSet::merge(array(
			'requestData' 	=> false
		),$options);
		
		if ( $this->restKo( $str, $restOptions ) ) return;
		
		
		// Http
		$this->flashKo( $str, $redirect );
		
	}
	
	public function alert( $str, $redirect = array(), $options = array() ) {
		
		// Ajax
		$ajaxOptions = PowerSet::merge(array(
			'redirect' 		=> $this->ajaxRedirect($redirect),
			'requestData' 	=> false
		),$options);
		
		$this->ajaxAlert( $str, $ajaxOptions );
		
		
		// Rest
		$restOptions = PowerSet::merge(array(
			'requestData' 	=> false
		),$options);
		
		if ( $this->restAlert( $str, $restOptions ) ) return;
		
		
		// Http
		$this->flashAlert( $str, $redirect );
		
	}
	
	public function message( $str, $redirect = array(), $options = array() ) {
		
		// Ajax
		$ajaxOptions = PowerSet::merge(array(
			'redirect' 		=> $this->ajaxRedirect($redirect),
			'requestData' 	=> false
		),$options);
		
		$this->ajaxMsg( $str, $ajaxOptions );
		
		
		// Rest
		$restOptions = PowerSet::merge(array(
			'requestData' 	=> false
		),$options);
		
		if ( $this->restMsg( $str, $restOptions ) ) return;
		
		
		// Http
		$this->flashMsg( $str, $redirect );
		
	}
	
	
	
	
	
	
	
	
	
	
	
	
}