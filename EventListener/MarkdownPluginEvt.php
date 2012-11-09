<?php
/**
 * Markdown Plugin Code Parser
 * ===========================
 * 
 * parses placeholder tags inside markdown source.
 * 
 * ## Property Tag
 * 
 *     {varName.subVar:filter1:filter2}
 * 
 * 
 *
 * @author peg
 */



class MarkdownPluginEvt extends PowerEventListener {

	public $events = array(
		'Markdown.beforeParseViewVars'
	);
	
	public function MarkdownBeforeParseViewVars() {
		
		$string = $this->get('string');
		
		$string = $this->propertyTags($string);
		
		$this->set( 'string', PowerString::tpl( $string, $this->subject->viewVars ) );
		
	}
	
	private function propertyTags( $subject ) {
		
		preg_match_all("|{(.*)}|U", $subject, $matches);
		for ( $i=0; $i<count($matches[0]); $i++ ) {
			
			$tag = $this->tagTokenizer($matches[1][$i]);
			
			// get the value
			$replace = $this->getVal( $tag['subject'] );
			
			// apply filters
			foreach ( $tag['filters'] as $filter ) {
				$replace = $this->applyFilter( $replace, $filter );
			}
			
			// dump non stringable values
			if (in_array(gettype($replace),array('array','object')) ) {
				
				if ( Configure::read('debug') ) {
					ob_start();
					debug($replace);
					$replace = ob_get_clean();
					
				} else {
					$replace = gettype($replace);
					
				}
				
			}
			
			if ( isset($replace) ) $subject = str_replace ($matches[0][$i], $replace, $subject);
			unset($replace);
			
		}
		
		return $subject;
		
	}
	
	
	/**
	 * Tokenizes a tag string to find subject and filters
	 */
	private function tagTokenizer( $subject ) {
		
		// Setup tags tokens
		$tag = array(
			'subject' => $subject,
			'filters' => array()
		);
		
		// Parse filters applied to the tag
		if ( strpos($subject,'|') ) {
			
			list( $tag['subject'], $filters ) = PowerString::explodeFirstOccourrence( '|', $subject );
			
			$tag['filters'] = explode( '|', $filters );
			
		}
		
		return $tag;
		
	}
	
	/**
	 * Tries to find a value occourrence from a row of places
	 */
	private function getVal( $subject ) {
		
		// static value
		if ( substr($subject,0,1) == '!' ) {
			return $this->staticVal(substr($subject,1,strlen($subject)));
		}
		
		// viewVars
		if ( ( $tmp = Set::extract( $subject, $this->subject->viewVars) ) !== null ) {
			return $tmp;
		}
		
		
		// PowerConfig
		if ( PowerConfig::exists($subject) ) {
			return PowerConfig::get($subject);
		}
		
	}
	
	
	
	/**
	 * Parses a static value
	 */
	private function staticVal( $subject ) {
		
		// array or associative array
		// ![key1=val&key2=val]
		if ( substr($subject,0,1) === '[' && substr(strrev($subject),0,1) === ']' ) {
			
			$list = array();
			
			foreach ( explode('&',substr($subject,1,strlen($subject)-2)) as $item ) {
				
				
				if ( strpos($item,'=') !== false ) {
					
					list( $key, $val ) = PowerString::explodeFirstOccourrence( '=', $item );
					
					$list[$key] = $val;
					
				} else {
					
					$list[] = $val;
					
				}
				
			}
			
			return $list;
			
		}
		
		return $subject;
		
	}
	
	
	
	/**
	 * Apply a filter to the given value
	 */
	private function applyFilter( $subject, $filter ) {
		
		// $subject->object->method()
		if ( strpos($filter,'.') !== false ) {
			
			list ( $object, $method ) = explode( '.', $filter );
			
			$callback = array($this->subject->$object,$method);
			
			if ( is_callable($callback) ) {
				
				return call_user_func($callback,$subject);
				
			}
		
		// static method
		} elseif ( strpos($filter,'::') !== false )  {
			
			list ( $object, $method ) = explode( '::', $filter );
			
			$callback = array($object,$method);
			
			if ( is_callable($callback) ) {
				
				return call_user_func($callback,$subject);
				
			}
		
		// $subject->method()
		} elseif ( is_callable(array($this->subject,$filter)) ) {
			
			return call_user_func(array($this->subject,$filter),$subject);
		
		// general function
		} elseif ( function_exists($filter) ) {
			
			return call_user_func($filter,$subject);
			
		}
		
		return $subject;
		
	}
	
}
