<?php
/**
 * CakePower - BubbbleCreateBehavior
 * =================================
 * 
 * Adds the conveniend methods "created()" to a model to investigate if last save()
 * action was an INSERT or an UPDATE.
 * 
 * - created() returns "true" if an INSERT query was executed
 * - updated() returns "false" if an UPDATE query was executed
 * 
 * 
 * @author peg
 *
 */
class BubbleEventsBehavior extends CakePowerBehavior {
	
	protected $wasCreated = array();
	protected $wasDeleted = array();
	
	
	
	
	/**
	 * Model's Callbacks
	 */
	
	public function beforeSave( Model $Model ) {
		
		$this->_reset($Model);
	
	}
	
	public function beforeDelete( Model $Model ) {
		
		$this->_reset($Model);
		
	}
	
	public function afterSave( Model $Model, $created ) {
		
		$this->wasCreated[$Model->alias] = $created;
		
	}
	
	public function afterDelete( Model $Model ) {
		
		$this->wasDeleted[$Model->alias] = true;
		
	}
	
	
	
	
	
	/**
	 * New Methods
	 */
	
	public function created( Model $Model ) {
		
		$this->wasCreated += array( $Model->alias=>null ); 
		
		return ( $this->wasCreated[$Model->alias] === true );
	
	}
	
	public function updated( Model $Model ) {
		
		$this->wasCreated += array( $Model->alias=>null ); 
		
		return ( $this->wasCreated[$Model->alias] === false );
	
	}
	
	public function deleted( Model $Model ) {
		
		$this->wasDeleted += array( $Model->alias=>null ); 
		
		return ( $this->wasDeleted[$Model->alias] === true );
	
	}
	
	
	
	
	
	
	protected function _reset( Model $Model ) {
		
		$this->wasDeleted[$Model->alias] = null;
		$this->wasCreated[$Model->alias] = null;
		
	}
	
	
}