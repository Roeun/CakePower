<?php 
/**
 * CakePower - IsActive Behavior
 * =============================
 * 
 * Usefull to handle models with an active/disactive flag field like accounts!
 * 
 * - quick and DRY filter active models only (look ad "beforeFind() docs)
 * - investigate model's active status
 * - activate/deactivate utilities
 * - toggle utility
 * 
 * @author peg
 *
 */
class IsActiveBehavior extends CakePowerBehavior {
	
	protected $_defaultValues = array(
		
		// flag field name inside model's schema
		'flagField' 		=> 'is_active',
		
		// combination of operator and value to filter active models
		'activeValue' 		=> 1,	// assigned as "active" status value when updating
		'activeOperator'	=> '',
		'activeCheck'		=> 1,
		
		// combination of operator and value to filter inactive models
		'inactiveValue' 	=> 0,	// assigned as "inactive" status value when updating
		'inactiveOperator'	=> '<>',
		'inactiveCheck'		=> 1
	
		/**
		 * "operator" and "check" values are used to compose sql finder query this way:
		 * "{modelAlias}.{flagField} {oparator}" => {check}
		 * 
		 * this way you can use whatever kind of flag fields you need from boolean to
		 * numeric to strings!
		 */
	
	);
	
	

	
	
	
	
	
	
	
// -------------------------------------------------- //
// ---[[   F I L T E R I N G   M E T H O D S   ]] --- //
// -------------------------------------------------- //	
	
/**	
 * Listen to "active" and "inactive" $query keys to augment filter conditions
 * with active status check.
 * 
 * $this->Model->find('all',array( 'active'=>true ));
 * $this->Model->find('all',array( 'inactive'=>true ));
 * 
 * $this->Model->find('all',array( 'only'=>'active' ));
 * $this->Model->find('all',array( 'only'=>'inactive' ));
 * 
 * $this->Model->find('all','active');
 * $this->Model->find('all','inactive');
 * 
 */
	public function beforeFind( Model $Model, $query = array() ) {
		
		$query += array( 'conditions'=>array() );
		
		
		// Handle scalar value as query
		if ( array_key_exists('0',$query) && in_array($query[0],array('active','inactive')) ) {
			$query['only'] = $query[0];
			unset($query[0]);
		}
		
		// Handle "only" key
		if ( isset($query['only']) && in_array($query['only'],array('active','inactive')) ) {
			$query += array( 'active'=>true, 'inactive'=>true );
			unset($query['active']);
			unset($query['inactive']);
			$query[$query['only']] = true;
			unset($query['only']);
		}
		
		
		
		// Compose the field name to check for model's active status
		$field 	= $Model->alias . '.' . $this->settings[$Model->alias]['flagField'];
		
		// Apply only active filter
		if ( isset($query['active']) ) {
			
			$operator 	= !empty($this->settings[$Model->alias]['activeOperator']) ? ' ' . $this->settings[$Model->alias]['activeOperator'] : '';
			$value		= $this->settings[$Model->alias]['activeCheck'];
			
			$query += array( 'conditions'=>array() );
			$query['conditions'][ $field . $operator ] = $value;
			
			unset($query['active']);
		
		// Apply inactive filter only
		} elseif ( isset($query['inactive']) ) {
			
			$operator 	= !empty($this->settings[$Model->alias]['inactiveOperator']) ? ' ' . $this->settings[$Model->alias]['inactiveOperator'] : '';
			$value		= $this->settings[$Model->alias]['inactiveCheck'];
			
			$query += array( 'conditions'=>array() );
			$query['conditions'][ $field . $operator ] = $value;
			
			unset($query['inactive']);
			
		}
		
		return $query;
	
	}
	
	
/**	
 * Shortcut to find active only models
 */
	public function findActive( Model $Model, $query = array() ) {
		
		$query+= array( 'active'=>true, 'inactive'=>true );
		unset($query['inactive']);
		
		return $Model->find( 'all', $query );
	
	}

	
/**
 * Shortcut to find inactive only models
 */
	public function findInactive( Model $Model, $query = array() ) {
		
		$query+= array( 'active'=>true, 'inactive'=>true );
		unset($query['active']);
		
		return $Model->find( 'all', $query );
	
	}
	
	
	
	

	
	
	
	
	
	
	
// ------------------------------------------------ //
// ---[[   C H E C K I N G   M E T H O D S   ]] --- //
// ------------------------------------------------ //
	
	public function isActive( Model $Model, $input = array() ) {
		
		return $this->_get( $Model, $input ) == $this->settings[$Model->alias]['activeValue'];
		
	}
	
	public function isInactive( Model $Model, $input = array() ) {
	
		return !$this->isActive( $Model, $input );
		
	}
	
	
	
	
	
	
	
	
	
	
// ------------------------------------------------ //
// ---[[   U P D A T I N G   M E T H O D S   ]] --- //
// ------------------------------------------------ //

/**	
 * Set a model as active.
 * updates datasource without validations!
 */
	public function activate( Model $Model, $input = array() ) {
		
		return $this->_set( $Model, $input, $this->settings[$Model->alias]['activeValue'] );
	
	}

/**
 * Set a model as inactive.
 * updates datasource without validations!
 */
	public function deactivate( Model $Model, $input = array() ) {
		
		return $this->_set( $Model, $input, $this->settings[$Model->alias]['inactiveValue'] );
	
	}
	
/**	
 * Changes the status of a model from active to inactive and reverse.
 */
	public function toggle( Model $Model, $input = array() ) {
		
		if ( $this->isActive($Model,$input) ) {
			return $this->deactivate($Model,$input);
			
		} else {
			return $this->activate($Model,$input);
			
		}
		
	}
	
	
	
	
	
	
	
	
	
	
// ------------------------------------------------ //
// ---[[   I N T E R N A L   M E T H O D S   ]] --- //
// ------------------------------------------------ //
	
	
/**	
 * Updates flag field value.
 * updates datasource without validations!
 */
	protected function _set( Model $Model, $input = array(), $value = null ) {
		
		if ( $value === null ) return false;
		
		$data = array( $Model->alias=>array(
			$Model->primaryKey 							=> $this->_getId( $Model, $input ),
			$this->settings[$Model->alias]['flagField'] => $value
		));
		
		return $Model->save( $data, false );
		
	}
	
	
/**	
 * Get flag field values from a number of input types.
 */
	protected function _get( Model $Model, $input = array() ) {
		
		// If a model id was given then try to read from the datasource to get
		// a data array for the model.
		// If $input contain nothing then $Model->id value is used to filter!
		if ( !isset($input[$Model->alias][$this->settings[$Model->alias]['flagField']]) ) {
			
			$conditions = array(
				'conditions' 	=> array( $Model->alias.'.'.$Model->primaryKey => $this->_getId( $Model, $input ) ),
				'fields' 		=> array( $Model->alias.'.'.$this->settings[$Model->alias]['flagField'] ),
				'recursive'		=> -1 
			);
			
			$input = $Model->find('first',$conditions);
			
			// If record was not found returns inactive value!
			if ( !$input ) return $this->settings[$Model->alias]['inactiveValue'];
			
		}
		
		// Return active field value as defined by settings
		return $input[$Model->alias][$this->settings[$Model->alias]['flagField']];
		
	}
	
/**
 * Get model id from multiple input types!
 */	
	protected function _getId( Model $Model, $input = array() ) {
		
		// numeric ID was given
		if ( is_numeric($input) ) return $input;
		
		// model's data array was given
		if ( isset($input[$Model->alias][$Model->primaryKey]) ) return $input[$Model->alias][$Model->primaryKey];
		
		return $Model->id;
		
	}
	
	
}