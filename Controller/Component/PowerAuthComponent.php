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
 * CakePOWER :: PowerAuthComponent
 *
 * It extends basic AuthComponent allowing to configure an $accessDeniedRedirect property
 * to strict redirect denied actions to a certain url in place of to redirect to referral url.
 *
 * How to use this component in a controller class (AppController??):
 *
 * public $components = array(
 *     'Auth' => array( 
 *         'className' => 'PowerAuth', 
 *          'accessDeinedRedirect' => array( 'controller'=>'users', 'action'=>'login' ),
 *          [other Auth properties] )
 * );
 */


App::import('Controller/Component', 'AuthComponent');



class PowerAuthComponent extends AuthComponent {
	
	/**
	 * Static Access Denied Page
	 * You can set an CakePHP url string or array to redirect when a deny exception happen.
	 *
	 * Leaving this property to "false" does not change the default AuthComponent behavior
	 * of redirecting to the referral url of the denied request.
	 */
	public $accessDeniedRedirect = null;
	
	public $accessDeniedHardRedirect = false;
	
	
	public function startup(Controller $controller) {
		
		if ($controller->name == 'CakeError') {
			return true;
		}

		$methods = array_flip(array_map('strtolower', $controller->methods));
		$action = strtolower($controller->request->params['action']);

		$isMissingAction = (
			$controller->scaffold === false &&
			!isset($methods[$action])
		);

		if ($isMissingAction) {
			return true;
		}

		if (!$this->_setDefaults()) {
			return false;
		}
		$request = $controller->request;

		$url = '';

		if (isset($request->url)) {
			$url = $request->url;
		}
		$url = Router::normalize($url);
		$loginAction = Router::normalize($this->loginAction);

		$allowedActions = $this->allowedActions;
		$isAllowed = (
			$this->allowedActions == array('*') ||
			in_array($action, array_map('strtolower', $allowedActions))
		);

		if ($loginAction != $url && $isAllowed) {
			return true;
		}

		if ($loginAction == $url) {
			if (empty($request->data)) {
				if (!$this->Session->check('Auth.redirect') && !$this->loginRedirect && env('HTTP_REFERER')) {
					$this->Session->write('Auth.redirect', $controller->referer(null, true));
				}
			}
			return true;
		} else {
			if (!$this->_getUser()) {
				if (!$request->is('ajax')) {
					$this->flash($this->authError);
					$this->Session->write('Auth.redirect', $request->here());
					$controller->redirect($loginAction);
					return false;
				} elseif (!empty($this->ajaxLogin)) {
					$controller->viewPath = 'Elements';
					echo $controller->render($this->ajaxLogin, $this->RequestHandler->ajaxLayout);
					$this->_stop();
					return false;
				} else {
					$controller->redirect(null, 403);
				}
			}
		}
		if (empty($this->authorize) || $this->isAuthorized($this->user())) {
			return true;
		}
		

		$this->flash($this->authError);
		
		
		/** @@CakePOWER@@ **/
		#$controller->redirect($controller->referer('/'), null, true);
		#return false
		
		
		
		// Hard redirect mode - always redirect to this url.
		if ( $this->accessDeniedHardRedirect !== false ) {
			$redirect = $this->accessDeniedHardRedirect;
		
		// Soft redirect used as fallback if referer is not available.
		} else if ( $this->accessDeniedRedirect !== false ) {
			
			// Soft redirect default behavior is to act as a login page to ask user to change it's credentials to grant access to the requested feature.
			if ( $this->accessDeniedRedirect === null && !empty($this->loginAction) ) $this->accessDeniedRedirect = $this->loginAction;
			
			$redirect = $controller->referer($this->accessDeniedRedirect);
		
		// AuthComponent standard redirect.
		} else {
			$redirect = $controller->referer('/');
			
		}
		
		// Prevent the redirect recursion going to the login page if access is denied!
		if ( strpos(strrev($redirect),strrev($this->request->here)) === 0 ) $redirect = $this->loginAction;
		
		// Apply the redirect.
		$controller->redirect($redirect, null, true);
		
		return false;
		/** ##CakePOWER## **/
		
	}
	
	
	
	
/**
 * Refresh logged-in user's data from database.
 * Utility method to be used after profile data changes.
 */	
	function refreshUserData() {
		
		// Can't update user's data cause no login information was found!
		if ( !$this->user('id') ) return false;
		
		
		// Step through authentication objects to find a valid user model to use to update data
		$this->_setDefaults();
		
		if (empty($this->_authenticateObjects)) {
			$this->constructAuthenticate();
		}
		
		foreach ($this->_authenticateObjects as $auth) {
			
			if ( !empty($auth->settings['userModel']) ) {
				
				$result = ClassRegistry::init($auth->settings['userModel'])->findById( $this->user('id') );
				
				if ( $result !== false ) {
					
					// merge related models the AuthComponent way!
					$user = $result[$auth->settings['userModel']];
					unset($user[$auth->settings['fields']['password']]);
					unset($result[$auth->settings['userModel']]);
					
					// update session and return a non-false value, the new user data!
					CakeSession::write(self::$sessionKey, array_merge($user, $result) );
					return $this->user();
				
				}
			
			}
			
		}
		
		// no way to recognise user... 
		return false;
		
	}

}