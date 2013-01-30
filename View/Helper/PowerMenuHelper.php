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
 * CakePower - PowerMenu (Helper)
 * -------------------------------------------
 * 
 * Extends PowerTreeHelper to creates lists or another kind of data from
 * a PowerMenu path.
 * 
 */

App::import( 'Helper', 'CakePower.PowerTree' );


class PowerMenuHelper extends PowerTreeHelper {
	
	public function setActive( $path = '' ) {
		
		PowerMenu::setActive( $path );
		
	}

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
		
		$node['PowerMenu']['params'] = PowerHtmlHelper::tagOptions( $node['PowerMenu']['params'], array(
			'class' => '',
			'title' => $node['PowerMenu']['show']
		), 'title');
		
		// sets up the active class for the item
		if ( $node['PowerMenu']['active'] ) $node['PowerMenu']['params']['class'] .= ' active';
		
		return $this->subject()->Html->link( $node['PowerMenu']['show'], $node['PowerMenu']['url'], $node['PowerMenu']['params'] );
		
	}
	
	function itemOptions( $opt, $node, $depth ) {
		
		// sets up the active class for the item
		if ( $node['PowerMenu']['active'] ) $opt['class'] .= ' active';
		
		return $opt;
		
	}

}