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
		
		$field = parent::input( $field, $this->_inputOptions($options) );
		
		// fix an empty param outputted in somewhere i don't know!!!
		$field = str_replace(' =""', '', $field);
		
		return $field;

	}

	protected function _inputOptions( $options = array() ) {
		
		// String options is converted to a label
		if ( is_string($options) ) $options = array( 'label'=>$options );
		
		

		// Options default values
		$options+= array(
        	'label' => array()
		);
	
			
		// String label is converted to array format
		if ( isset($options['label']) && is_string($options['label']) ) {
			$options['label'] = array( 'text'=>$options['label'] );
		}
		
		
		
		// Merge label options with default values
		if ( $options['label'] !== false && isset($this->inputDefaults['label']) ) {
			
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
	
	
	
	
	
	
	
// --------------------------- //
// ---[[   X T Y P E S   ]]--- //
// --------------------------- //

	public function xtypeForm($mode, $name, $text, $options) {
		switch ($mode) {
			case 'options':
				$options['allowEmpty'] = true;
				$options['autoRender'] = false;
				if (isset($options['model'])) {
					$name = $options['model'];
				} elseif (isset($options['name'])) {
					$name = $options['name'];
				}
				$options = PowerSet::extend(array(
					'actions' => null,
					'end' => null,
					'fields' => array()
				),$options);
				return array($name, $text, $options);
			case 'tag':
				// fetch ending blocks
				$fields = $options['fields'];
				$actions = $options['actions'];
				$end = $options['end'];
				// form rendered blocks
				$form = $this->create($name, PowerSet::clear($options, array('model', 'name', 'actions', 'end', 'fields')));
				// form fields
				foreach ($fields as $fieldName=>$fieldConfig) {
					if (is_numeric($fieldName)) {
						$fieldName = $fieldConfig;
						$fieldConfig = array();
					}
					$form.= $this->input($fieldName,$fieldConfig);
				}
				// form content
				$form.= $this->Html->tag($text);
				// form actions
				$form.= $this->end($end);
				return $form;
		}
	}
	
	public function xtypeFormEnd($mode, $name, $text, $options) {
		switch ($mode) {
			case 'options':
				$options['allowEmpty'] = true;
				$options['autoRender'] = false;
				break;
			case 'tag':
				return $this->end($text);
		}
	}
	
	public function xtypeInput($mode, $name, $text, $options) {
		switch ($mode) {
			case 'options':
				$options['allowEmpty'] = true;
				if (isset($options['name'])) {
					$name = $options['name'];
					unset($options['name']);
				} elseif (!empty($text)) {
					$name = $text;
				}
				return array($name, $text, $options);
			case 'tag':
				return $this->input($name, $options);
		}
	}
	
	public function xtypeInputs($mode, $name, $text, $options) {
		switch ($mode) {
			case 'options':
				$options['autoRender'] = false;
				return array($name, $text, $options);
			case 'tag':
				$inputs = array();
				foreach ( $text as $field ) {
					$field = $this->Html->atagDefaults($field);
					if (empty($field['name']) && !empty($field['text'])) {
						$field['name'] = $field['text'];
					}
					if (empty($field['name'])) continue;
					$inputs[$field['name']] = PowerSet::clear($field,array('teg','text','name','xtype'));
				}
				return $this->inputs($inputs);
		}
	}
	
	

}