<?php
/**
 * UserAgent Utility
 * @author: Marco Pegoraro
 * @web: http://consulenza-web.com
 */

if ( !function_exists('ua') ) {
function ua() {
	
	// String to rapresent request's device. Used by page_cache plugin to profile caching info    #
	$ua['_id'] 	= '';
	$ua['_sid']	= '';

	/**
	 * Platform sniffing
	 */
	$ua['lin'] = 0; $ua['mac'] = 0; $ua['win'] = 0;
	
	// Fix calls without a browser (es curl or remote file_get_contents)
	if ( empty($_SERVER['HTTP_USER_AGENT']) ) $_SERVER['HTTP_USER_AGENT'] = '';
	
	$ua['lin']			= preg_match('/linux/i', $_SERVER['HTTP_USER_AGENT']) != false;
	$ua['mac']			= preg_match('/macintosh|mac os x/i', $_SERVER['HTTP_USER_AGENT']) != false;
	$ua['win']			= preg_match('/windows|win32/i', $_SERVER['HTTP_USER_AGENT']) != false;
	
	
	/**
	 * Browser sniffing
	 */
	$ua['ie'] = 0; $ua['ff'] = 0; $ua['ch'] = 0; $ua['sf'] = 0; $ua['op'] = 0; $ua['nt'] = 0;
	
	if ( preg_match('/MSIE/i',$_SERVER['HTTP_USER_AGENT']) && !preg_match('/Opera/i',@$u_agent) ) {
		$ua['ie'] 			= 1;
		$ua['browser_name'] = 'MSIE';
	} elseif ( preg_match('/Firefox/i',$_SERVER['HTTP_USER_AGENT']) ) {
		$ua['ff'] 			= 1;
		$ua['browser_name']	= 'Firefox';
	} elseif ( preg_match('/Chrome/i',$_SERVER['HTTP_USER_AGENT']) ) {
		$ua['ch'] 			= 1;
		$ua['browser_name'] = 'Chrome';
	} elseif ( preg_match('/Safari/i',$_SERVER['HTTP_USER_AGENT']) ) {
		$ua['sf'] 			= 1;
		$ua['browser_name']	= 'Safari';
	} elseif ( preg_match('/Opera/i',$_SERVER['HTTP_USER_AGENT']) ) {
		$ua['op'] 			= 1;
		$ua['browser_name']	= 'Opera';
	} elseif ( preg_match('/Netscape/i',$_SERVER['HTTP_USER_AGENT']) ) {
		$ua['nt'] 			= 1;
		$ua['browser_name'] = 'Netscape';
	} else {
		$ua['browser_name'] = 'unknown';
	}
	
	/**
	 * Version sniffing
	 */
	$ua['_known'] 		= array('Version', $ua['browser_name'], 'other');
	$ua['_pattern'] 	= '#(?<browser>' . join('|', $ua['_known']) . ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
	    
	if (!preg_match_all($ua['_pattern'], $_SERVER['HTTP_USER_AGENT'], $ua['_matches'])) {}
	
	$ua['_i'] = count($ua['_matches']['browser']);
	if ( $ua['_i'] != 1) {
		if (strripos($_SERVER['HTTP_USER_AGENT'],"Version") < strripos($_SERVER['HTTP_USER_AGENT'],$ua['browser_name'])) {
			$ua['version']= $ua['_matches']['version'][0];
		} else {
			$ua['version']= @$ua['_matches']['version'][1];
		}
	} else {
		$ua['version']= $ua['_matches']['version'][0];
	}
	    
	// check if we have a number
	if ( $ua['version'] == null || $ua['version'] == "") { $ua['version'] = "?"; }
	
	// Clear unused vars.
	unset($ua['_known']);
	unset($ua['_pattern']);
	unset($ua['_matches']);
	unset($ua['_i']);
	
	
	/**
	 * Major version
	 */
	
	$ua['mversion'] = substr( $ua['version'], 0, strpos($ua['version'],'.') );
	
	
	
	
	// Mobile browsers sniffing
	$ua['iphone'] 		= strpos($_SERVER['HTTP_USER_AGENT'],"iPhone") 		!== false;
	$ua['ipad'] 		= strpos($_SERVER['HTTP_USER_AGENT'],"iPad") 		!== false;
	$ua['ipod']			= strpos($_SERVER['HTTP_USER_AGENT'],"iPod") 		!== false;
	$ua['android'] 		= strpos($_SERVER['HTTP_USER_AGENT'],"Android") 	!== false;
	$ua['palmpre'] 		= strpos($_SERVER['HTTP_USER_AGENT'],"webOS") 		!== false;
	$ua['blackberry'] 	= strpos($_SERVER['HTTP_USER_AGENT'],"BlackBerry") 	!== false;
	
	
	// Is mobile?
	if ( $ua['iphone'] || $ua['ipad'] || $ua['ipod'] || $ua['android'] || $ua['palmpre'] || $ua['blackberry'] ) $ua['mobile'] = 1;
	
	// Is desktop?
	if ( empty($ua['mobile']) && ( $ua['ie'] || $ua['sf'] || $ua['ff'] || $ua['ch'] || $ua['op'] || $ua['nt'] ) ) $ua['desktop'] = 1;
	
	// Is apple?
	if ( $ua['iphone'] || $ua['ipad'] || $ua['ipod'] || $ua['sf'] ) $ua['apple'] = 1;
	
	// Is apple mobile?
	if ( $ua['iphone'] || $ua['ipad'] || $ua['ipod'] ) $ua['apple_mobile'] = 1;
	
	// Is mobile touch?
	if ( $ua['iphone'] || $ua['ipad'] || $ua['ipod'] || $ua['android'] ) $ua['mobile_touch'] = 1;
	
	// Is apple desktop?
	if ( $ua['sf'] && $ua['mac'] ) $ua['apple_desktop'] = 1;
	
	// Is nice browser?
	if ( !$ua['ie'] || $ua['mversion'] >= 9 ) $ua['nice'] = 1;
	
	// Is bad browser?
	if ( $ua['ie'] && $ua['mversion'] < 9 ) $ua['bad'] = 1;
	
	// Is touch?
	if ( $ua['ipod'] || $ua['ipad'] || $ua['iphone'] || $ua['android'] ) $ua['touch'] = 1;
	
	
	
	// It compose device
	if ( isset($ua['lin']) ) 			$ua['_id'] = 'lin';
	if ( isset($ua['win']) ) 			$ua['_id'] = 'win';
	if ( isset($ua['mac']) ) 			$ua['_id'] = 'mac';
	if ( isset($ua['desktop']) ) 		$ua['_id'] .= '_desktop';
	if ( isset($ua['mobile']) ) 		$ua['_id'] .= '_mobile';
	if ( isset($ua['touch']) ) 		$ua['_id'] .= '_touch';
	$ua['_id'] .= '_' . strtolower($ua['browser_name']) . '_' . $ua['mversion'];
	
	
	if ( isset($ua['iphone']) ) 		$ua['_id'] .= '_iphone';
	if ( isset($ua['ipod']) ) 			$ua['_id'] .= '_ipod';
	if ( isset($ua['ipad']) ) 			$ua['_id'] .= '_ipad';
	if ( isset($ua['android']) ) 		$ua['_id'] .= '_android';
	if ( isset($ua['palmpre']) ) 		$ua['_id'] .= '_palmpre';
	if ( isset($ua['blackberry']) ) 	$ua['_id'] .= '_blackberry';
	$ua['_sid'] 				= md5($ua['_id']);
	
	
	
	return $ua; 
}}



/*
header('Content-type:text/plain');
echo $_SERVER['HTTP_USER_AGENT']."\r\n"."\r\n";
print_r($ua);
*/
?>