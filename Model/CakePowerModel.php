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


App::uses('Model', 'Model');
App::uses('CakePowerBehavior', 'CakePower.Model/Behavior');

class CakePowerModel extends Model {

	
	
	public function __construct( $class = null, $alias = null, $id = null ) {
		
		// Adds core behaviors to every models
		if ( empty($this->actsAs) ) $this->actsAs = array();
		$this->actsAs[] = 'CakePower.BubbleEvents';
		
		parent::__construct( $class, $alias, $id );
	
	}
	
	
	public function loadModel( $model = '' ) {
		
		if ( ClassRegistry::isKeySet($model) ) {
			$object = ClassRegistry::getObject($model);
			
		} else {
			$object = ClassRegistry::init($model, true);
			
		}
		
		$this->{$model} = $object;
		
		return $object;
		
	}
	
	
	/**
	 * __check()
	 * ========
	 * 
	 * Utility function for custom validation methods.
	 * Output the first value for a check array.
	 * 
	 * "$check" passed to a custom validation rule often contains
	 * an associative array
	 * 
	 * // simple check the value of the field
	 * function customValidationMethod( $check ) {
	 *   if ( $this->__check($check) == 'myval' ) return true
	 *   return false;
	 * }
	 * 
	 * // different custom validations based on field's name
	 * function customValidationMethod( $check ) {
	 *   list( $key, $val ) = $this->__check( $check, true );
	 *   
	 *   switch ( $key ) {
	 *   	case 'field1' : ... break;
	 *   	case 'field2' : ... break
	 *   }
	 *   
	 * }
	 * 
	 */
	protected function __check( $check, $list = false ) {
		
		$key = '';
		$val= '';
		
		if ( is_array($check) ) {
			
			$key = array_keys($check);
            $key = $key[0];
			
            $val = array_values($check);
            $val = $val[0];
            
        }
	
        if ( $list ) {
        	return array( $key, $val );
        		
        } else {
        	return $val;
        	
        }
		
	}
	
	
/**
 * fromAssociated()
 * ================
 * 
 * Custom Validation,
 * allow to call a custom validation rule from an associated model.
 * 
 */	
	public function fromAssociated( $check, $model = '', $method = '' ) {
		
		// Strange call
		
		// Integrity controls
		if ( !isset($this->$model) ) 					return false; 
		if ( !method_exists( $this->$model, $method) ) 	return false;
		
		// Call to remote validation rule
		return call_user_func_array( array($this->$model,$method), func_get_args() );
		
	}
	
	
	
/**	
 * equalToField()
 * ==============
 * 
 * Custom Validation,
 * ensure a field value is equal to another field value
 * 
 */
	public function equalToField( $check, $fieldName ) {
		
		if ( !isset($this->data[$this->alias][$fieldName]) ) return false;
		
		return $this->__check($check) === $this->data[$this->alias][$fieldName];
	
	} 
	
	
	
	
	
	
	
	
/**	
 * Shortcut to PowerConfig::pval() method to access a param by name
 * searching multiple places
 */
	public function pval( $key = null, $def = null ) { return PowerConfig::pval($key,$def); }
	
	
	
	/*
	public function afterSave($created) {
		
		parent::afterSave($created);
		
		$this->created = $created;
		
	}
	*/
	
	
	
}