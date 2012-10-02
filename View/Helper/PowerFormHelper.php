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

		#return parent::input( $field, $options );
		return parent::input( $field, $this->_inputOptions($options) );

	}

	protected function _inputOptions( $options = array() ) {
		
		// String options is converted to a label
		if ( is_string($options) ) $options = array( 'label' => $options );
		
		

		// Options default values
		$options+= array(
        	'label' => array()
		);
		
		
	
			
		// String label is converted to array format
		if ( isset($options['label']) && is_string($options['label']) ) {
			$options['label'] = array( 'text'=>$options['label'] );
		}
		
		
		
		// Merge label options with default values
		if ( isset($this->inputDefaults['label']) ) {
			
			// Convert input defaults label into array format
			if ( is_string($this->inputDefaults['label']) ) $this->inputDefaults['label'] = array( 'text'=>$this->inputDefaults['label'] ); 
			
			$options['label'] = PowerSet::pushDiff( $this->inputDefaults['label'], $options['label'] );
			
		}
		
		
		
		
		// Remove empty keys to allow CakePHP defaults to be applied
		if ( is_array($options['label']) ) $options['label'] = PowerSet::filter($options['label']);
		$options = PowerSet::filter($options);
		
		return $options;

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