<?php
/**
 * PowerHtmlHelper
 * Extends CakePHP core's HtmlHelper adding usefull features and behaviors.
 */

App::import( 'View/Helper', 'HtmlHelper' );


// Less Support Libraries.
App::uses('Folder',		'Utility');
App::uses('File',		'Utility');
App::uses('Component',	'Controller');

// Thrd party libraries
App::import( 'CakePower.Vendor/thrd', 'lessc' );
App::import( 'CakePower.Vendor/thrd', 'cssmin' );




// Less constants.
if ( !defined('CACHE_LESS') ) 	define( 'CACHE_LESS', CACHE . 'less' . DS );
if ( !defined('LESS_URL') ) 	define( 'LESS_URL', 'less'.DS );




class PowerHtmlHelper extends HtmlHelper {
	
	
	
	
	
	public function __construct(View $View, $settings = array()) {
		
		// Adds tags to the form helper.
		$this->_tags['thead'] = '<thead%s>%s</thead>';
		$this->_tags['tbody'] = '<tbody%s>%s</tbody>';
		
		parent::__construct($View, $settings);
		
		// Setup Less parsing folders.
		$this->lessFolder 	= new Folder(WWW_ROOT.'less', true, 0755 );
		$this->lessCache	= new Folder(CACHE_LESS, true, 0755 );
		$this->cssFolder 	= new Folder(WWW_ROOT.'css', true, 0755 );
		
	}
	
	
	
	
	
	
	
	
/**	
 * css()
 * ------------------------------------
 * add possibility to 
 * - handle conditional CSS inclusions
 * - define per-item options
 * 
 * INLINE CSS:
 * echo $this->Html->css( 'ie-style', null, array( 'if'=>'IE', 'media'=>'screen' ));
 * 
 * INCLUDE A LIST OF CSS TO THE VIEW'S "css" BLOCK:
 * $this->Html->css(array(
 *     'all',
 *     'print'   => array( 'media'=>'print' ),
 *     'mobile'  => array( 'media'=>'all and (max-width:500px)' ),
 *     'ie'      => array( 'if'=>'ie' ), 
 *     'old-ie'  => 'lte IE 8'
 * ),null,array( 'inline'=>false ));
 * 
 * 
 */
	public function css($path, $rel = null, $options = array()) {
		
		$options += array('block' => null, 'inline' => true);
		
		if (!$options['inline'] && empty($options['block'])) {
			$options['block'] = __FUNCTION__;
		}
		unset($options['inline']);
		

		if (is_array($path)) {
			$out = '';
			
			
			/** @@CakePOWER@@ **/
			/*
			foreach ($path as $i) {
				$out .= "\n\t" . $this->css($i, $rel, $options);
			}
			*/
			/**
			 * DOC:
			 * each css file in an array list may be defined as a simple file name ("style.css" or "style")
			 * or can be defined as an associative array with the file name as key and an options array as
			 * detailed options to be used for this item only.
			 * 
			 * array(
			 *     'css1',
			 *     'css2',
			 *     'ie-css' => array( 'if'=>'IE' ),
			 *     'ie7-css' => array( 'if'=>'IE 7' ),
			 *     'old-ie' => 'lte IE 8' // "if" is the default option listen if a scalar value is given
			 * )
			 * 
			 * for conditional expression documentation read this:
			 * http://www.quirksmode.org/css/condcom.html
			 * 
			 */
			foreach ($path as $i=>$opt) {
				
				// Default scalar value for associative declarations.
				if ( !is_array($opt) && !is_numeric($i) ) $opt = array( 'if'=>$opt );
				
				// Css file only declarations.
				if ( !is_array($opt) ) {
					$i 		= $opt;
					$opt 	= array();
				}
				
				// Allow to per-item $rel param configuration
				$_rel = $rel;
				if ( isset($opt['rel']) ) {
					$_rel = $opt['rel'];
					unset($opt['rel']);
				}
				
				$out .= "\n\t" . $this->css($i, $_rel, PowerSet::merge($options,$opt) );			
			}
			/** --CakePOWER-- **/
			
			
			
			
			if (empty($options['block'])) {
				return $out . "\n";
			}
			return;
		}

		if (strpos($path, '//') !== false) {
			$url = $path;
		} else {
			
			/** @@CakePOWER@@ **/
			// Compile less source to the css output file.
			// If debug > 0 
			$source = $this->assetPath($path, $options + array('pathPrefix' => LESS_URL, 'ext' => '.less'));
			$target = $this->assetPath($path, $options + array('pathPrefix' => CSS_URL, 'ext' => '.css', 'exists'=>false));
			if ( ( !file_exists($target) || Configure::read('debug') ) && file_exists($source) ) $this->auto_compile_less($source, $target);
			/** --CakePOWER-- **/
			
			
			$url = $this->assetUrl($path, $options + array('pathPrefix' => CSS_URL, 'ext' => '.css'));

			if (Configure::read('Asset.filter.css')) {
				$pos = strpos($url, CSS_URL);
				if ($pos !== false) {
					$url = substr($url, 0, $pos) . 'ccss/' . substr($url, $pos + strlen(CSS_URL));
				}
			}
		}

		if ($rel == 'import') {
			
			/** @@CakePOWER@@ **/
			#$out = sprintf($this->_tags['style'], $this->_parseAttributes($options, array('inline', 'block'), '', ' '), '@import url(' . $url . ');');
			$out = sprintf($this->_tags['style'], $this->_parseAttributes($options, array('inline', 'block', 'if', 'debug' ), '', ' '), '@import url(' . $url . ');');
			/** --CakePOWER-- **/
			
		} else {
			if ($rel == null) {
				$rel = 'stylesheet';
			}
			
			/** @@CakePOWER@@ **/
			#$out = sprintf($this->_tags['css'], $rel, $url, $this->_parseAttributes($options, array('inline', 'block'), '', ' '));
			$out = sprintf($this->_tags['css'], $rel, $url, $this->_parseAttributes($options, array('inline', 'block', 'if', 'debug' ), '', ' '));
			/** --CakePOWER-- **/
			
		}
		
		
		/** @@CakePOWER@@ **/
		/* apply conditional comment */
		if ( !empty($options['if']) ) {
			$out = '<!--[if ' . $options['if'] . ']>' . $out . '<![endif]-->';
		}
		
		if ( !empty($options['debug']) ) {
			$out = "\r\n" . $out;	
		}
		/** --CakePOWER-- **/
		

		if (empty($options['block'])) {
			return $out;
		} else {
			$this->_View->append($options['block'], $out);
		}
		
	}
	
	
	
	
	
	
/**	
 * OVERRIDE
 * adds the ability to import a require.js script with a CakePHP notated "data-main" option.
 */
	public function script($url, $options = array()) {
		if (is_bool($options)) {
			list($inline, $options) = array($options, array());
			$options['inline'] = $inline;
		}
		$options = array_merge(array('block' => null, 'inline' => true, 'once' => true), $options);
		if (!$options['inline'] && empty($options['block'])) {
			$options['block'] = __FUNCTION__;
		}
		unset($options['inline']);

		if (is_array($url)) {
			$out = '';
			
			/** @@CakePOWER@@ **/
			/*
			foreach ($url as $i) {
				$out .= "\n\t" . $this->script($i, $options);
			}
			*/
			/**
			 * DOC:
			 * each js file in an array list may be defined as a simple file name ("script.js" or "script")
			 * or can be defined as an associative array with the file name as key and an options array as
			 * detailed options to be used for this item only.
			 * 
			 * array(
			 *     'script1',
			 *     'script2',
			 *     'html5shiv' => array( 'if'=>'lt IE 9' ),
			 *     'html5shiv' => 'lte IE 9' // "if" is the default option listen if a scalar value is given
			 * )
			 * 
			 * for conditional expression documentation read this:
			 * http://www.quirksmode.org/css/condcom.html
			 * 
			 */
			foreach ($url as $i=>$opt) {
				
				// Default scalar value for associative declarations.
				if ( !is_array($opt) && !is_numeric($i) ) $opt = array( 'if'=>$opt );
				
				// Css file only declarations.
				if ( !is_array($opt) ) {
					$i 		= $opt;
					$opt 	= array();
				}
				
				$out .= "\n\t" . $this->script($i, PowerSet::merge($options,$opt) );			
			}
			/** --CakePOWER-- **/
			
			if (empty($options['block'])) {
				return $out . "\n";
			}
			return null;
		}
		if ($options['once'] && isset($this->_includedScripts[$url])) {
			return null;
		}
		$this->_includedScripts[$url] = true;

		if (strpos($url, '//') === false) {
			$url = $this->assetUrl($url, $options + array('pathPrefix' => JS_URL, 'ext' => '.js'));

			if (Configure::read('Asset.filter.js')) {
				$url = str_replace(JS_URL, 'cjs/', $url);
			}
		}
		
		
		
		
		/** @@CakePOWER@@ **/
		if ( strpos($url,'require') !== false && isset($options['data-main']) && strpos($options['data-main'],'//') === false ) {
			$options['data-main'] = $this->assetUrl($options['data-main'], array('pathPrefix' => JS_URL, 'ext' => '.js'));
		}
		/** --CakePOWER-- **/
		
		
		
		
		
		$attributes = $this->_parseAttributes($options, array('block', 'once'), ' ');
		
		$out = sprintf($this->_tags['javascriptlink'], $url, $attributes);
		
		
		/** @@CakePOWER@@ **/
		/* apply conditional comment */
		if ( !empty($options['if']) ) {
			$out = '<!--[if ' . $options['if'] . ']>' . $out . '<![endif]-->';
		}
		
		if ( !empty($options['debug']) ) {
			$out = "\r\n" . $out;	
		}
		/** --CakePOWER-- **/

		if (empty($options['block'])) {
			return $out;
		} else {
			$this->_View->append($options['block'], $out);
		}
	}
	
	
	
	
	
	
	
	
	
/**
 * tag()
 * ------------------------------------
 * add possibility to nest multiple tags inside $text property.
 * 
 * // The CakePHP way:
 * $text = 'This is a ';
 * $text.= $this->Html->link( 'link', 'http://cakepower.org' );
 * echo $this->Html->tag( 'p', $text );
 * 
 * // The CakePower way:
 * echo $this->Html->tag( 'p', array(
 *     'This is a ',
 *     $this->Html->link( 'link', 'http://cakepower.org' )
 * ));
 * 
 * You can nest how many declarations as you wish/need containing code verbosity of
 * declarate many temporary vars!
 * 
 * // Full array configuration:
 * echo $this->Html->tag(array(
 * 		'name' 	=> 'div',
 * 		'class' => 'class1 class2',
 * 		'id'	=> 'tag-id',
 * 		'content' => array(
 * 			'row1',
 * 			$this->Html->link( .. ),
 * 			'another content',
 *  		$this->Html->tag( 'p', 'test' ) // nested tag
 * 		)
 * ));
 * 
 * 
 * @param unknown_type $name
 * @param unknown_type $text
 * @param unknown_type $options
 */	
	public function tag( $name, $text = null, $options = array()) {
		
		// Full array configuration.
		// allow to set up every option in a single array.
		if ( is_array($name) ) {
			
			$name += array( 'name'=>'div', 'content'=>'', 'options'=>array() );
			
			$tagName = $name['name'];
			unset($name['name']);
			
			$content = $name['content'];
			unset($name['content']);
			
			$name = PowerSet::merge( $name, $name['options'] );
			unset($name['options']);
			
			return $this->tag( $tagName, $content, $name );
			
		} 
		
		if ( is_array($text) ) {
			
			$_text = $text;
			
			$text = '';
			
			foreach ( $_text as $item ) {
				
				$text.= $item;
			
			}
			
		}
		
		// Prevent blank attributes to be appended to the HTML
		if ( empty($options['class']) ) 		unset($options['class']);
		if ( empty($options['id']) ) 			unset($options['id']);
		if ( empty($options['style']) ) 		unset($options['style']);
		
		// Use the CakePHP's parent method to output the HTML source.
		return parent::tag( $name, $text, $options );
	
	}

	
/**	
 * Complete override of the CakePHP's HtmlHelper::div()
 * It behaves like the original method but using CakePower's tag() 
 * all empty tag attributes are cleaned!
 */
	public function div( $class, $text = null, $options = array()) {
		
		// Support for full-array configuration.
		if ( is_array($class) ) {
			
			$options = $class;
			$options+= array( 'class'=>'', 'content'=>'' );
			
			$class = $options['class'];
			unset($options['class']);
			
			$text = $options['content'];
			unset($options['content']);
			
			return $this->div( $class, $text, $options );
		
		}
		
		$options['class'] = $class;
		
		return $this->tag( 'div', $text, $options );
	
	}

	
/**
 * Acts like "div()" but allow to pass div's ID as first parameter
 */	
	public function idiv( $id, $text = null, $options = array() ) {
		
		$options += array( 'class'=>'' );
		
		$options['id'] = $id;
		
		return $this->div( $options['class'], $text, $options );
		
	}
	
	
	public function p( $text = '', $options = array() ) {
		
		// Full array configuration
		if ( is_array($text) ) {
			
			$options = $text += array( 'content'=>'' );
			
			$text = $options['content'];
			unset($options['content']);
		
		}
		
		$options += array( 'class'=>'' );
		
		$class = $options['class'];
		unset($options['class']);
		
		return $this->para( $class, $text, $options );
		
	}
	

/**	
 * Utility to create a DOM named item form a view's block of data.
 * 
 * It uses VIEW::fetch() to grab contents from desired view block then creates
 * a DOM item (div as default) with block's name as ID property.
 * 
 * You can customize default tagName and all DOM options used in the "tag()" method.
 * 
 * $this->Html->block( 'sidebar' ) --> <div id="sidebar">... sidebar content ...</div>
 * $this->Html->block( 'sidebar', 'col-dx' ) --> <div id="sidebar" class="col-dx">... sidebar content ...</div>
 * $this->Html->block( 'sidebar', array( 'class'=>'col-dx', 'style'=>'text-align:right' ) ) --> <div id="sidebar" class="col-dx" style="text-align:right">... sidebar content ...</div>
 * 
 * // full array configuration
 * $this->Html->block(array(
 * 	'name' 		=> 'sidebar',
 * 	'class' 	=> 'col-dx',
 * 	'style' 	=> 'text-align:right',
 * 	'tagName' 	=> 'p'
 * ));
 * 
 * --> <p id="sidebar" class="col-dx" style="text-align:right">... sidebar content ...</p>
 * 
 * Blog's Entry:
 * http://movableapp.com/2012/07/cakephp-using-view-blocks-the-cakepower-way/
 * 
 */
	public function block( $id, $options = array() ) {
		
		// allow a full-array configuration
		if ( is_array($id) ) {
			
			$id += array( 'name'=>'' );
			
			$options = $id;
			
			$id = $options['name'];
			
			unset($options['name']);
			
		}
		
		// class as string parameter
		if ( is_string($options) ) $options = array( 'class'=>$options );
		
		// options default values
		$options += array(
			'id'			=> $id,
			'tagName'		=> 'div',
			'hideOnEmpty' 	=> false 
		);
		
		// fetch the text
		$content = $this->_View->fetch( $id );
		
		// option "hideOnEmpty" check
		if ( empty($content) && $options['hideOnEmpty'] !== false ) return;
		unset($options['hideOnEmpty']);
		
		// extract the tagName from the config options
		$tagName = $options['tagName'];
		unset($options['tagName']);
		
		// return the block
		return $this->tag(array(
			'name' 		=> $tagName,
			'content'	=> $content,
			'options'	=> $options
		));
	
	}
	
	
	
/**	
 * Override
 * Automagically disable escape as default options if images are linked.
 */
	public function link($title, $url = null, $options = array(), $confirmMessage = false) {
		
		if ( strpos($title,'<img src') !== false ) $options += array( 'escape'=>false );
		if ( strpos($title,'<span></span>') !== false ) $options += array( 'escape'=>false );
		
		return parent::link( $title, $url, $options, $confirmMessage );
		
	}
	
	
	
	
	
	
	
	
	
	
	
/**	
 * Interface to an authorization layer.
 * Test an if an url can be accessed. 
 * 
 * Return values:
 * true: 	the url can be accessed
 * false: 	the url is denied
 * null: 	it is no possibile to check the url (external urls...)
 */
	public function allowUrl( $url = '' ) { return true; }
	
	
	
	
	
	
	
	
	
	
	

	
/**	
 * Actions Link
 * these methods expose some actions that user may use in the view AS CONCEPTS.
 * 
 * Each method generates a simple link that implement a class.
 * It is a UI assets stuff to apply css rules and behaviors to that class!
 * 
 * Some UI kit like Twitter Bootstrap supplies some action driven components (aka buttons)
 */
	
	
	public function action( $url = array(), $options = array() ) {
		
		if ( is_string($options) ) $options = array( 'text'=>$options );
		
		$options += array( 'text'=>'', 'class'=>'ui-action' );
		
		if ( strpos($options['class'],'ui-action') === false ) $options['class'] = 'ui-action ' . $options['class'];
		
		// data-icon
		if ( isset($options['icon']) ) {
			$options['data-icon'] = $options['icon'];
			unset($options['icon']);
		}
		
		// data-confirm-msg
		if ( isset($options['confirm']) ) {
			if ( !is_array($options['confirm']) ) $options['confirm'] = array( 'msg'=>$options['confirm'] );
			$options['data-confirm-msg'] = $options['confirm']['msg'];
			unset($options['confirm']);
		}
		
		$text = $options['text'];
		unset($options['text']);
		
		return $this->link( $text, $url, $options );
		
	}
	
	public function editAction( $url = array(), $options = array() ) {
		
		if ( is_string($options) ) $options = array( 'text'=>$options );
		
		$options += array( 'text'=>'edit', 'class'=>'' );
		
		if ( strpos($options['class'],'ui-action-edit') === false ) {
			$options['class'] = 'ui-action-edit ' . $options['class'];	
		}
		
		return $this->action( $url, $options );
		
	}
	
	public function deleteAction( $url = array(), $options = array() ) {
		
		if ( is_string($options) ) $options = array( 'text'=>$options );
		
		$options += array( 'text'=>'delete', 'class'=>'' );
		
		if ( strpos($options['class'],'ui-action-delete') === false ) {
			$options['class'] = 'ui-action-delete ' . $options['class'];
		}
		
		return $this->action( $url, $options );
		
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
/**	
 * Less Integration methods
 * It parse a LESS file into a CSS.
 */
	protected function auto_compile_less($lessFilename, $cssFilename) {
		
		
		// Check if cache & output folders are writable and the less file exists.
		if (!is_writable(CACHE.'less')) {
			trigger_error(__d('cake_dev', '"%s" directory is NOT writable.', CACHE.'less'), E_USER_NOTICE);
			return;
		}
		
		if (file_exists($lessFilename) == false) {
			trigger_error(__d('cake_dev', 'File: "%s" not found.', $lessFilename), E_USER_NOTICE);
			return;
		}

		// Cache location
		$cacheFilename = CACHE.'less'.DS.str_replace('/', '_', str_replace($this->lessFolder->path, '', $lessFilename).".cache");

		// Load the cache
		if (file_exists($cacheFilename)) {
			$cache = unserialize(file_get_contents($cacheFilename));
		} else {
			$cache = $lessFilename;
		}

		$new_cache = lessc::cexecute($cache);
		
		// Minify the css source!
		// Minification occours only if  debug is turned to 0!
		if ( class_exists('CssMin') && Configure::read('debug') === 0 ) $new_cache['compiled'] = CssMin::minify($new_cache['compiled']);
		
		if (!is_array($cache) || $new_cache['updated'] > $cache['updated'] || file_exists($cssFilename) === false) {
			$cssFile = new File($cssFilename, true);
			if ($cssFile->write($new_cache['compiled']) === false) {
				if (!is_writable(dirname($cssFilename))) {
					trigger_error(__d('cake_dev', '"%s" directory is NOT writable.', dirname($cssFilename)), E_USER_NOTICE);
				}
				trigger_error(__d('cake_dev', 'Failed to write "%s"', $cssFilename), E_USER_NOTICE);
			}

			$cacheFile = new File($cacheFilename, true);
			$cacheFile->write(serialize($new_cache));
		}
		
	}
	
	
	
	
	
	

}