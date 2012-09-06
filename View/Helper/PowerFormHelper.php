<?php
/**
 * PowerFormHelper
 */

App::import( 'View/Helper', 'FormHelper' );







class PowerFormHelper extends FormHelper {
	
	
	
	
	
/**	
 * Handle multiple submit buttons with different values to
 * be routed to different backend actions.
 * 
 * end( 'save', 'save_and_exit', 'abort' )
 * end( 'save'=>'Save', 'abort'=>array('lable'=>'Abort Action', 'class'=>'abort_class'), 'save_and_exit' );
 * 
 */
	function end( $options = null ) {
		
		
		
		return parent::end($options);
	
	}
	
	
/**
 * error()
 * options string value shortcut
 */	
	function error( $field = '', $message = null, $options = array() ) {
		
		// translate string option to the array required version
		if ( is_string($options) ) $options = array( 'class'=>$options );
		
		return parent::error( $field, $message, $options );
	
	}
	
}