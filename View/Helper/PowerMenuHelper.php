<?php
/**
 * CakePower - PowerMenu (Helper)
 * -------------------------------------------
 * 
 * Extends PowerTreeHelper to creates lists or another kind of data from
 * a PowerMenu path.
 * 
 */

App::import( 'Helper', 'CakePower.PowerTree' );


class PowerMenuHelper extends PowerTreeHelper {

	public function generate( $path, $config = array() ) {
		
		$tree = PowerMenu::getTree($path);
		
		$config += array(
			'listOpt'	=> array(),
			'callable' 	=> new PowerMenuHelper__TreeHelperExtension 
		);
		
		
		
		// Handle list's DOM properties to be coded into the main config.
		if ( isset($config['id']) ) {
			$config['listOpt']['id'] = $config['id'];
			unset($config['id']);
		}
		
		if ( isset($config['class']) ) {
			$config['listOpt']['class'] = $config['class'];
			unset($config['class']);
		}
		
		if ( isset($config['style']) ) {
			$config['listOpt']['style'] = $config['style'];
			unset($config['style']);
		}
		
		
		return parent::generate( $tree, $config );
		
	}

}

class PowerMenuHelper__TreeHelperExtension extends TreeHelperExtension {
	
	function displayLogic( $node ) {
		
		if ( empty($node['PowerMenu']['show']) ) $node['PowerMenu']['show'] = $node['PowerMenu']['_name'];
		
		// sets up a configuration array for the link item
		$opt = array( 'class'=>'' );
		
		// extends the node text DOM params with something stored into the node data.
		if ( !empty($node['PowerMenu']['params']) ) $opt = PowerSet::merge( $opt, $node['PowerMenu']['params'] );
		
		// sets up the active class for the item
		if ( $node['PowerMenu']['active'] ) $opt['class'] .= ' active';
		
		return $this->subject()->Html->link( $node['PowerMenu']['show'], $node['PowerMenu']['url'], $opt );
		
	}

}