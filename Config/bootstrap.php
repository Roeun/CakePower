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
 * CakePower bootstrap.
 *
 * Allow plugins load dinamically.
 */



// Used to calculate execution time.
define( 'POWER_START', microtime() );

define( 'TRUE', 	true );
define( 'FALSE', 	false );
define( 'ALL', 		true );
define( 'NONE', 	false );
define( 'ON', 		true );
define( 'OFF', 		false );



/**
 * Import Libraries.
 */

# UTH is a deprecated class!
#App::import( 'Vendor', 'CakePower.Uth' );

// Utilities
App::import( 'Vendor', 'CakePower.Basics' );
App::import( 'Vendor', 'CakePower.Ua' );
App::import( 'Vendor', 'CakePower.Uth' );
App::import( 'Vendor', 'CakePower.PowerSet' );
App::import( 'Vendor', 'CakePower.PowerString' );
App::import( 'Vendor', 'CakePower.PowerConfig' );
App::import( 'Vendor', 'CakePower.PowerMenu' );
App::import( 'Vendor', 'CakePower.PowerApp' );

App::uses('PowerNumber', 'CakePower.Vendor');



// Models
App::import( 'Model', 'CakePower.CakePowerModel' );

// Helpers
App::import( 'View/Helper', 'CakePower.CakePowerHelper' );

// CakePower initializer controller
App::uses( 'CakePowerController', 'CakePower.Controller' );



/**
 * Split a package from a class into a string:
 * 
 *     Vendor/ClassName
 *     -> array(
 *       'Vendor',
 *       'ClassName'
 *     )
 * 
 */
function packageSplit( $className ) {
	
	if ( strpos($className,'/') ) {
		return PowerString::explodeLastOccourrence('/',$className);
		
	} else {
		return array( null, $className );
		
	}

}

/**
 * Transform a package string into $className, $package vars:
 * 
 *     Plugin.Package/ClassName
 *     -> array(
 *       'ClassName',
 *       'Plugin.Package'
 *     )
 */
function packageCmp( $className ) {
	
	list( $plugin, $className ) 	= pluginSplit($className);
	list( $package, $className ) 	= packageSplit($className);
	
	if ( !empty($plugin) ) $package = $plugin . '.' . $package;
	
	return array($className,$package);
	
}




/**
 * Adds new top level package used to automagically loading of events listener classes.
 * These classes should be placed into plugins and are loaded by CakePOWER bootstrap!
 */

App::build(array(
    'EventListener' => array('%s' . 'EventListener' . DS)
), App::REGISTER);








/**
 * PowerConfiguration
 * this class works like CakePhp's Configure but allow to perform a lot of operation
 * to a tree data.
 */
PowerConfig::set(array(
	
	// store info about running application.
	'app' => array(
	
		// CakePlugin::loaded() list after this bootstrap
		'plugins' => array(),
	
	),
	
	
	
	// store info about the actual request.
	'request' => array(
		
		// Some info about the window url location fetched from the server side info
		'location' => array(
			'root'		=> powerSelfProtocol() . '://' . powerSelfHost() . powerSelfPort() . Router::url('/'),
			'href' 		=> powerSelfHref(),
			'url'		=> powerSelfUrl(),
			'search'	=> powerSelfSearch()
			
		),
		
		// Controller's request params placeholder.
		// It is filled up in the CakePowerController::__construct method.
		'params' => array(),
		
		// Cliente recognition library
		'ua' => ua()
	
	),
	
	
	// store info about plugin's configuration.
	// for each plugin is sored it's path, loading order, load configuration
	//
	// PowerConfig::get('plugin.MyPlugin.path');
	// PowerConfig::get('plugin.MyPlugin.order');
	// PowerConfig::get('plugin.MyPlugin.load.bootstrap');
	//
	// There is a key "config" where each plugin can store internal configurations.
	'plugin' => array(
		
		// Internal configuration for CakePower
		'CakePower' => array(
			
			// This key will contain all menus defined in the sistem and handler by the PanelMenu class and the PanelMenu Helper
			'menu' => array(
				
				// Container for admin menus
				'admin' => array(),
				
				// Container for public menus
				'public' => array()
				
			)
	
		)
	
	),

));














/**
 * Plugins automagically management.
 *
 * plugins are listed like in a CakePlugin::loadAll() action but here we look for
 * 3 Config files existance to configure the CakePlugin::load() method.
 *
 * bootrap.php and routes.php
 * if these files exists a plugin load configuration is automagically given
 * to the CakePlugin::load() method.
 *
 * plugin.php
 * if this file exits it is included into the flow.
 * 
 * This file may define a $config = array(); to be merged with the $plugin info.
 * Here is the place to manually alter the loading order of the plugin.
 */

$plugins = array();

foreach ( App::objects('plugins') as $plugin ) {
	
	foreach (App::path('plugins') as $path) {
		if (!is_string($plugin)) {
			continue;
		}
		if (is_dir($path.$plugin)) {
			
			// Collect plugin's informations.
			$pluginConfig = array(
				'_info' => array(
					'name' 		=> $plugin,
					'base' 		=> $path,
					'path'		=> $path . $plugin . DS,
					'load' 		=> array(),
				),
				'order' => 5000,
			);
			
			// CakePower is not being loaded but it's info is quequed as other plugins!
			if ( $plugin == 'CakePower' ) {		
				PowerConfig::set( 'plugin.CakePower', $pluginConfig);
			
			// Try to auto configure login loading settings.
			} else {
				
				// Look for bootstrap and routes existance to configure CakePlugin::load()
				if ( file_exists($pluginConfig['_info']['path'] . 'Config' . DS . 'bootstrap.php' )) 	$pluginConfig['_info']['load']['bootstrap'] 	= true;
				if ( file_exists($pluginConfig['_info']['path'] . 'Config' . DS . 'routes.php' )) 		$pluginConfig['_info']['load']['routes'] 		= true;
				
				// Look for a plugin.php configuration file to extend plugin informations.
				// This file may define a $config array to be merged with the actual plugin's informations.
				if ( file_exists($pluginConfig['_info']['path'] . 'Config' . DS . 'plugin.php' )) {
					
					$plugin = array();
					
					require_once($pluginConfig['_info']['path'] . 'Config' . DS . 'plugin.php');
					
					// Extend the load key configuration.
					if ( array_key_exists('load', $plugin) ) {
						$pluginConfig['_info']['load'] = array_merge($pluginConfig['_info']['load'],$plugin['load']);
						unset($plugin['load']); 
					}
					
					// Extend plugin's configurations.
					$pluginConfig = array_merge($pluginConfig,$plugin);
					
				}
			
				$plugins[] = $pluginConfig;
				
			}
			
		}
		
	}
	
}



// Sort Plugins to match configuration order.
if ( !empty($plugins) ) $plugins = Set::sort( $plugins, '{n}.order', 'asc' );

// Load Plugins and write PowerConfig database.
foreach ( $plugins as $plugin ) {
	
	if ( $plugin == 'CakePower' ) continue;
	
	
	/**
	 * Plugins Loading Events
	 * here CakePower trigger two events to allow other plugins to control loading flow.
	 * it is possible to listen to this event and prevent a plugin to be loaded by 
	 * passing a "skip" parameter to the event result array. 
	 */
	
	// Dispatch General Plugin Event 
	$e = new CakeEvent('CakePower.beforeLoadPlugin',null,$plugin);
	CakeEventManager::instance()->dispatch($e);
	if ( !empty($e->result['skip']) ) continue;
	
	
	
	/**
	 * Load the plugin
	 */
	
	CakePlugin::load( $plugin['_info']['name'], $plugin['_info']['load'] );
	
	// Check if plugin has been loaded and queque it into the PowerConfig data.
	if ( !CakePlugin::loaded($plugin['_info']['name']) ) continue;
	PowerConfig::set( 'plugin.'.$plugin['_info']['name'], $plugin );
	
	
	
	// Load plugin's bootstrap event listeners
	PowerApp::loadEventListeners( 'EventListener', 'Bootstrap', $plugin['_info']['name'] );
	
	
}

// Set up loaded plugins info to the app configuration key.
PowerConfig::set( 'app.plugins', CakePlugin::loaded() );




// Loads application level bootstrap's event listener classes
PowerApp::loadEventListeners( 'EventListener', 'Bootstrap' );




/**
 * Event: "pluginsLoaded()"
 */
CakeEventManager::instance()->dispatch( new CakeEvent('CakePower.pluginsLoaded') );





#PowerConfig::ddebug();
#powerTime();
