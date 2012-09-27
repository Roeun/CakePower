<?php
/**
 * PowerFormHelper
 */

App::import( 'View/Helper', 'FormHelper' );




class PowerFormHelper extends FormHelper {
	
	
	// Default options for create() method.
	protected $formClass 		= '';
	protected $formStyle		= '';
	protected $inputDefaults 	= '';





	/**
	 * Form open tag utility.
	 * some options default values should be hard coded into the
	 * helper's class and should be extended by instance options.
	 * 
	 * you can set some "override{Property}" options to tell
	 * helper to override defaults with instance configuration options. 
	 *
	 * @overrideClass
	 * @overrideStyle
	 * @overrideInputDefaults
	 */

	public function create( $model = '', $options = array() ) {

		$options += array(
            'class'			=> '',
        	'style'			=> '',
            'inputDefaults' => array()
		);

		// Extend form class string
		if ( !isset($options['overrideClass']) ) {
			 
			if ( empty($options['class']) ) {
				$options['class'] = $this->formClass;
			} else {
				$options['class'] = $this->formClass . ' ' . $options['class'];
			}

		}

		// Extend form style string
		if ( !isset($options['overrideStyle']) ) {
			if ( empty($options['style']) ) {
				$options['style'] = $this->formStyle;
			} else {
				$options['style'] = $this->formStyle . ' ' . $options['style'];
			}
		}

		// Extends or overrid input defaults from instance configuration
		if ( !isset($options['overrideInputDefaults']) ) $options['inputDefaults'] = PowerSet::merge($this->inputDefaults, $options['inputDefaults']);
		
		// Unset override indicators
		unset($options['overrideClass']);
		unset($options['overrideStyle']);
		unset($options['overrideInputDefaults']);

		// array_filter() remove empty params
		return parent::create($model, array_filter($options) );

	}
	
	
/**	
 * generate an input form field
 * a string option translates to field's label
 */
	public function input( $field, $options = '' ) {
		
		// Options defaults
        if (is_string($options)) $options = array('label' => $options);
        
        return parent::input( $field, $options );
		
	}


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