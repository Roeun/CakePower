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


function ddebug( $var = false, $showHtml = null, $showFrom = true ) {
	
	debug( $var, $showHtml, $showFrom );

	exit;
	
}





function powerTime( $print = true, $die = true ) {
	
	$time 	= round(microtime() - POWER_START, 4);
	$stime	= $time . 's.';
	
	if ( $print === 2 && Configure::read('debug') ) { debug($stime); }
	elseif ( $print ) { echo $stime; }
	
	if ( $die ) die();
	
	return $time;
	
} // EndOf: "powerTime()" #########################################################################

function averageTime( $print = true, $die = true ) {
	
	$time = powerTime(false,false);
	
	$_logPath = TMP . 'averageTime.txt';
	
	//if ( $reset === true ) @unlink($_logPath);
	
	if ( file_exists($_logPath) ) {
		$log = unserialize(file_get_contents($_logPath));
		if ( !is_array($log) ) unset($log);
	}
	
	if ( empty($log) ) {
		$log = array(
			'sum' 	=> 0,
			'tot'	=> 0,
			'avg'	=> 0,
			'time' 	=> array(),
		);
		
	}
	
	$log['time'][] = $time;
	$log['sum'] += $time;
	$log['tot'] += 1;
	$log['avg'] = round( $log['sum'] / $log['tot'], 4 );
	
	@file_put_contents( $_logPath, serialize($log) );
	
	if ( $print === 3 && Configure::read('debug') ) { debug($log); }
	elseif ( $print === 3 ) { print_r($log); }
	elseif ( $print === 2 && Configure::read('debug') ) { debug($log['avg'].' s.'); }
	elseif ( $print ) { echo $log['avg'].' s.'; }
	
	if ( $die ) die();
	
	return $log;
	
} // EndOf: "averageTime()" #######################################################################

function resetAverageTime() {
	
	@unlink( TMP . 'averageTime.txt' );
	
} // EndOf: "resetAverageTime()" ##################################################################










/**
 * Self Request Utilities
 */

function powerSelfProtocol() {
	
	// these info are not available to SHELL!
	if ( empty($_SERVER["HTTPS"]) && empty($_SERVER["SERVER_PROTOCOL"]) ) return;
	
	$s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
	 
	return powerStrLeft(strtolower($_SERVER["SERVER_PROTOCOL"]), "/").$s;
	
}

function powerSelfPort() {
	
	// these info are not available to SHELL!
	if ( empty($_SERVER["SERVER_PORT"]) ) return;
	
	return ($_SERVER["SERVER_PORT"] == 80) ? '' : (':'.$_SERVER["SERVER_PORT"]);
	
}

function powerSelfHost() {
	
	// these info are not available to SHELL!
	if ( empty($_SERVER["SERVER_NAME"]) ) return;
	
	return $_SERVER['SERVER_NAME'];
	
}

function powerSelfUri() {
	
	// these info are not available to SHELL!
	if ( empty($_SERVER["REQUEST_URI"]) ) return;
	
	return $_SERVER['REQUEST_URI'];

}

function powerSelfHref() {
	 
	return powerSelfProtocol() . "://". powerSelfHost() . powerSelfPort() . powerSelfUri() ;
	 
}

function powerSelfUrl() {
	
	$tmp = powerSelfHref();
	
	if ( strpos($tmp,'?') !== false ) {
		
		$tmp = explode( '?', $tmp );

		$tmp = $tmp[0];
		
	} 
	
	return $tmp;

}

function powerSelfSearch() {
	
	$tmp = powerSelfHref();
	
	if ( strpos($tmp,'?') !== false ) {
		
		$tmp = explode( '?', $tmp );

		$tmp = $tmp[1];
		
	} else $tmp = '';
	
	return $tmp;

}

function powerStrLeft($s1, $s2) {
	 
	return substr($s1, 0, strpos($s1, $s2));
	 
}
 




