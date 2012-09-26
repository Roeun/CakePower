<?php
App::uses('Model', 'Model');
App::uses('CakePowerBehavior', 'CakePower.Model/Behavior');

class CakePowerModel extends Model {

	
	
	
	
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
	
	
	
	
	
	
}