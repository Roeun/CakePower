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
 * PowerHtmlHelper
 * Extends CakePHP core's HtmlHelper adding usefull features and behaviors.
 */

App::import( 'View/Helper', 'HtmlHelper' );


// Less Support Libraries.
App::uses('Folder',		'Utility');
App::uses('File',		'Utility');
App::uses('Component',	'Controller');


// Thrd party libraries
App::import( 'Vendor', 'CakePower.thrd/lessc' );
App::import( 'Vendor', 'CakePower.thrd/cssmin' );


// Less constants.
if ( !defined('CACHE_LESS') ) 	define( 'CACHE_LESS', CACHE . 'less' . DS );
if ( !defined('LESS_URL') ) 	define( 'LESS_URL', 'less'.DS );





class PowerHtmlHelper extends HtmlHelper {
	
	public static $xtypes = array();
	
	/**
	 * A list of safe attributes to be allowed inside a tag()
	 * 
	 * @var array
	 */
	public static $allowedAttributes = array(
		'id',
		'class',
		'style',
		'rel',
		'title',
		'alt',
		'src',
		'href',
		'name'
	);
	
	
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
			
			// "$this->assetPath()" exists only if AppHelpers extends CakePowerHelper!
			// by checking it's existance we remove an ugly notice while installing the CakePOWER!  
			if ( method_exists( $this, 'assetPath') ) {
				$source = $this->assetPath($path, $options + array('pathPrefix' => LESS_URL, 'ext' => '.less'));
				$target = $this->assetPath($path, $options + array('pathPrefix' => CSS_URL, 'ext' => '.css', 'exists'=>false));
				if ( ( !file_exists($target) || Configure::read('debug') ) && file_exists($source) ) $this->auto_compile_less($source, $target);
			}
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
		
		
		
		/**
		 * RequireJS Optimization
		 * ======================
		 * 
		 * mechanism to change javascript source files from js/ to js-compiled/ folder.
		 * this is extremely useful when implement a RequireJS application! 
		 * 
		 * @TODO: check if compiled js folder exists!
		 * 
		 * 
		/** @@CakePOWER@@ **/
		
		// allow to change js default folder from "js" to "js-compiled"
		$jsUrl = substr(JS_URL, 0, strlen(JS_URL)-1);
		
		// setup debug mode into session to test across multiple requests
		if ( isset($_GET['jsdbgOn']) ) 	SessionComponent::write('jsdbg',true);
		if ( isset($_GET['jsdbgOff']) ) SessionComponent::delete('jsdbg');
		
		// decide what js folder to use based on combination on debug status
		if (
				( Configure::read('debug') == 0 && !isset($_GET['jsdbg']) && !SessionComponent::check('jsdbg') ) 	// production mode + debug
			||	( Configure::read('debug') > 0 && ( isset($_GET['jsdbg']) || SessionComponent::check('jsdbg') ) )	// developement mode + production test
		) $jsUrl .= '-compiled';
		
		// Replace in-page scripts urls
		$url = str_replace( JS_URL, $jsUrl.'/', $url );
		
		// RequireJS DATA-MAIN attribute parsing
		if ( strpos($url,'require') !== false && isset($options['data-main']) && strpos($options['data-main'],'//') === false ) {
			$options['data-main'] = $this->assetUrl($options['data-main'], array('pathPrefix' => $jsUrl.'/', 'ext' => '.js'));
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
	
	
	
	
	public function tag($name=null, $text=null, $options=array()) {
		
		if (is_array($name)) {
			return $this->atag($name);
		}
		
		if (empty($name)) {
			$name = 'div';
		}
		
		if (empty($text)) {
			$text = '';
		}
		
		// sets up default options ot handle tag's method behaviors
		$options = $this->tagOptions($options, array(
			'xtype' => 'tag',
			'allowEmpty' => 'span,td,th,i,b,img,input',
			'autoRender' => true,
			'if' => true,
			'else' => null,
		));
		
		// extract Xtype and clear options
		$xtype = $options['xtype'];
		$options = PowerSet::clear($options, 'xtype');
		
		// apply Xtype configuration callback
		if ( ($xtypeOptions = $this->_xtypeOptions($xtype, $name, $text, $options)) !== null) {
			list($name, $text, $options) = $xtypeOptions; 
		}
		
		// handle conditional tag option:
		switch( gettype($options['if']) ) {
			case 'string':
			case 'object':
			case 'array':
				$options['if'] = $this->solveTagConditional($name, $text, $options);
				break;
		}
		
		// apply conditional tag option:
		// QUESTION: "else" should be another tag configuration array to output in-place
		// of the actual or simply an alternative content to replace?
		if ($options['if'] === false) {
			if ($options['else'] !== null) {
				return $this->tag($options['else']);
			} else {
				return;
			}
		}
		
		// apply sub-tags
		if (is_array($text) && $options['autoRender'] === true) {
			$text = $this->atag($text);
		}
		
		// check for empty value to be cleared:
		if ( empty($text) && $options['allowEmpty'] !== true ) {
			if (!in_array($name, explode(',', $options['allowEmpty']))) return;
		}
		
		// clear options array
		$options = PowerSet::clear($options, array(
			'allowEmpty',
			'autoRender',
			'if',
			'else'
		));
		
		// apply xtype content callback
		$xtype = $this->_xtypeTag($xtype, $name, $text, $options);
		if (is_string($xtype)) {
			return $xtype;
		}
		
		// filters non standard attributes
		$options = $this->filterValidTagOptions($options);
		
		// Use the CakePHP's parent method to output the HTML source.
		return parent::tag($name, $text, $options);
		
	}
	
	protected function _xtypeOptions($xtype, $name, $text, $options) {
		foreach ($this->_xtypeGetContext() as $helper=>$xtypes) {
			if (isset($xtypes[$xtype])) {
				if (($tmp = $this->_View->$helper->$xtypes[$xtype]('options', $name, $text, $options)) !== null) {
					return $tmp;
				}
			}
		}
	}
	
	protected function _xtypeTag($xtype, $name, $text, $options) {
		foreach ($this->_xtypeGetContext() as $helper=>$xtypes) {
			if (isset($xtypes[$xtype])) {
				if (($tmp = $this->_View->$helper->$xtypes[$xtype]('tag', $name, $text, $options)) !== null) {
					return $tmp;
				}
			}
		}
	}
	
	protected function _xtypeGetContext() {
		
		// return a cached version of xtype view context
		if (!empty($this->_xtypeContext)) {
			return $this->_xtypeContext;
		}
		
		// build view context
		$this->_xtypeContext = array();
		foreach ($this->_View as $prop=>$val) {
			if (is_object($this->_View->$prop) && is_subclass_of($this->_View->$prop,'Helper')) {
				$this->_xtypeContext[$prop] = array();
				foreach (get_class_methods($this->_View->$prop) as $method) {
					if (substr($method, 0, 5) == 'xtype') {
						$xtype = Inflector::underscore(substr($method, 5, strlen($method)));
						$this->_xtypeContext[$prop][$xtype] = substr($method, 0, strlen($method));
					}
				}
			}
			if (empty($this->_xtypeContext[$prop])) {
				unset($this->_xtypeContext[$prop]);
			}
		}
		
		#ddebug($this->_xtypeContext);
		return $this->_xtypeContext;
		
	}
	
	
	
	protected function solveTagConditional($name, $text, $options) {
		
		// direct callable object
		if (is_callable($options['if'])) {
			return call_user_func($options['if'], $name, $text, $options);
		}
		
		// @TODO: needs to solve more complex callabel configurations like
		// array( method, object )
		// object::method
		
	}
	
	protected function atag( $options = array() ) {
		
		// retro-compatibility notation
		// may trigger a warning to alert that these keywords may not exists anymore!
		if (isset($options['name']) || isset($options['text'])) {
			
			if (isset($options['name'])) {
				#trigger_error('PowerHtmlHelper::atag() "name" key is now deprecated!', E_USER_WARNING );
				$options['tag'] = $options['name'];
				unset($options['name']);
			}
			if (isset($options['text'])) {
				#trigger_error('PowerHtmlHelper::atag() "content" key is now deprecated!', E_USER_WARNING );
				$options['content'] = $options['text'];
				unset($options['text']);
			}
		}
		
		// direct configuration array
		if ( array_key_exists('xtype', $options) || array_key_exists('tag', $options) || array_key_exists('content', $options) || array_key_exists('id', $options) || array_key_exists('class', $options) || array_key_exists('style', $options) ) {
			
			$options = $this->atagDefaults($options);
			
			// apply standard params tag method
			return $this->tag($options['tag'], $options['content'], PowerSet::clear($options, array('tag','content')));
		
		// list of sub-tags, generates a string as output.
		// tag configuration items will be translated to tags, strings or other format will be appended as thei are. 
		} else {
			
			$string = '';
			
			foreach ( $options as $tag ) {
				if ( is_array($tag) ) {
					$string.= $this->tag($tag);
				} else {
					$string.= $tag;
				}
			}
			
			return $string;
			
		}
		
	} 
	
	
	/**
	 * Fetch an implicit content from given array and translates into a full key=>val array
	 * An optional last integer key will be translated into the content key:
	 * 
	 * array(
	 *   'tag' => 'h2',
	 *   'my title'
	 * )
	 * 
	 * become:
	 * 
	 * array(
	 *   'tag' => 'h2',
	 *   'text' => 'my title'
	 * )
	 * 
	 * @param array $options
	 */
	public function atagDefaults($arr, $options=null) {
		
		$options = PowerSet::def($options,array(
			'tagKey' => 'tag',
			'textKey' => 'content'
		));
		
		// search for a last non-associative value for the config array to be used as text or sub-tags
		if ( !array_key_exists($options['textKey'], $arr) ) {
			if ( gettype(array_pop(array_keys($arr))) === 'integer' ) {
				$arr[$options['textKey']] = array_pop($arr);
			}
		}
		
		// apply tag's defaults
		$arr = PowerSet::extend(array(
			$options['tagKey'] => null,
			$options['textKey'] => null,
		),$arr);
		
		return $arr;
	}
	
	
	
	
	
	
	
	/**
	 * Global accessible method to set default values for a tag() options array.
	 * transform a string value into a "style" or "class" attribute depending on string
	 * content.
	 * 
	 * array() -> array( 'id'=>'', 'class'=>'', 'style'=>'' )
	 * 'foo' -> array( 'id'=>'', 'class'=>'foo', 'style'=>'' )
	 * 'color:red' -> array( 'id'=>'', 'class'=>'', 'style'=>'color:red' )
	 * 
	 */
	public static function tagOptions( $arr = '', $defaultValues = null, $options = array() ) {
		
		// apply some defaults attributes to be used in every unspecified case
		if ( $defaultValues === null ) {
			$defaultValues = array( 'id'=>'', 'class'=>'', 'style'=>'' );
		}
		
		$options = PowerSet::def( $options,array(
			'txtAttr' => 'class'
		),'txtAttr');
		
		// parse string configuration into inline CSS style or other given attribute (class)
		if ( is_string($arr) ) {
			
			if ( strpos($arr,':') === false ) {
				$arr = array( $options['txtAttr']=>$arr );
				
			} else {
				$arr = array( 'style'=>$arr );
				
			}
			
		}
		
		return PowerSet::def($arr, $defaultValues, $options['txtAttr']);
	
	}
	
	/**
	 * Define if a tag option name is allowd inside the tag() method
	 * @param unknown_type $key
	 */
	public static function isValidTagOption($key) {
		
		// filter allowed attributes
		if ( in_array($key,self::$allowedAttributes) ) return true;
		
		// allow all data- attributes
		if ( substr($key,0,5) === 'data-' ) return true;
		
		return false;
	}
	
	public static function filterValidTagOptions($options) {
		foreach ($options as $key=>$val) {
			if (!self::isValidTagOption($key)) {
				unset($options[$key]);	
			}
		}
		return $options;
	}
	
	
	
	
	/**
	 * Renders a list of tags
	 * 
	 * @param array $tags
	 */
	public function tags( $tags = array() ) {
		
		trigger_error('tags() is now deprecated and will be removed soon!', E_USER_WARNING ); 
		
		$html = '';
		
		foreach ( $tags as $tag ) {
			
			if ( is_array($tag) ) {
			
				$html.= $this->tag(PowerSet::def($tag,null,'content'));
				
			} else {
				
				$html.= $tag;
				
			}
		
		}
		
		return $html;
	
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
 * Definition List Utility
 * =======================
 * 
 * @param unknown_type $data
 * @param unknown_type $options
 */
	public function dl( $data = '', $options = array() ) {
		
		$options += array( 'dtOptions'=>array(), 'ddOptions'=>array(), 'skipEmptyValues'=>true );
		
		// Build List Body
		ob_start();
		foreach ( $data as $lbl=>$val ) {
			
			if ( $options['skipEmptyValues'] && empty($val) ) continue;
			
			echo $this->tag( 'dt', $lbl, $options['dtOptions'] );
			echo $this->tag( 'dd', $val, $options['ddOptions'] );
			
		}
		
		unset($options['dtOptions']);
		unset($options['ddOptions']);
		unset($options['skipEmptyValues	']);
		
		// Build List Wrapper with Options
		return $this->tag( 'dl', ob_get_clean(), $options );
		
	}
	
	public function listTag( $type = 'ul', $data = array(), $options = array() ) {
		
		if ( !is_array($options) ) $options = array( 'class'=>$options );
		$options += array( 'liOptions'=>array(), 'skipEmptyValues'=>true );
		
		
		// Build List Body
		ob_start();
		foreach ( $data as $content=>$liOptions ) {
			
			if ( is_numeric($content) ) {
				$content = $liOptions;
				$liOptions = array();
			}
			
			$liOptions = PowerSet::merge( $options['liOptions'], $liOptions );
			
			if ( $options['skipEmptyValues'] && empty($content) ) continue;
			
			echo $this->tag( 'li', $content, $liOptions );
			
		}
		
		unset($options['liOptions']);
		unset($options['skipEmptyValues']);
		
		// Build List Wrapper with Options
		return $this->tag( $type, ob_get_clean(), $options );
		
	}
	
	public function ul( $data = '', $options = array() ) {
		
		return $this->listTag( 'ul', $data, $options );
		
	}
	
	public function ol( $data = '', $options = array() ) {
		
		return $this->listTag( 'ol', $data, $options );
		
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
		return $this->tag($tagName, $content, $options);
	
	}
	
	
	
/**	
 * Override
 * Automagically disable escape as default options if images are linked.
 */
	public function link($title, $url = null, $options = array(), $confirmMessage = false) {
		
		if ( strpos($title,'<img src') 		!== false ) $options += array( 'escape'=>false );
		if ( strpos($title,'<span') 		!== false ) $options += array( 'escape'=>false );
		if ( strpos($title,'<strong') 		!== false ) $options += array( 'escape'=>false );
		if ( strpos($title,'<em') 			!== false ) $options += array( 'escape'=>false );
		if ( strpos($title,'<i') 			!== false ) $options += array( 'escape'=>false );
		if ( strpos($title,'<b') 			!== false ) $options += array( 'escape'=>false );
		
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
	
	
// --------------------------- //
// ---[[   X T Y P E S   ]]--- //
// --------------------------- //
	
	public function xtypeLink($mode, $name, $text, $options) {
		switch ($mode) {
			case 'options':
				if (isset($options['show'])) {
					$text = $options['show'];
					unset($options['show']);
				}
				return array($name, $text, $options);
			case 'tag':
				return $this->link($text, $options['url'], $this->filterValidTagOptions(PowerSet::clear($options, 'url')));
		}
	}
	
	
	public function xtypeImage($mode, $name, $text, $options) {
		switch ($mode) {
			case 'options':
				$name = 'img';
				return array($name, $text, $options);
			case 'tag':
				return $this->image($options['src'], $this->filterValidTagOptions(PowerSet::clear($options, 'src')));
		}
	}
	
	
	

}
