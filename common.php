<?php

	set_include_path(get_include_path() . PATH_SEPARATOR . 'lib');
	
	// Pull in Twilio PHP library
	require 'Twilio/twilio.php';
	
	// Pull in Zend PHP Gdata client library
	require 'Zend/Loader.php';
	Zend_Loader::loadClass('Zend_Gdata');
	Zend_Loader::loadClass('Zend_Gdata_AuthSub');
	Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
	Zend_Loader::loadClass('Zend_Gdata_HttpClient');
	Zend_Loader::loadClass('Zend_Gdata_Calendar');
	
	// Get configs
	global $aConfig;
	$aConfig = parse_ini_file('calendar.ini', true);
	
	// Set our AccountSid and AuthToken
	$sAccountSid = $aConfig['twilio']['sid'];
	$sAuthToken = $aConfig['twilio']['authtoken'];
	
	// Instantiate a new Twilio Rest Client
	$oTwilioClient = new TwilioRestClient($sAccountSid, $sAuthToken);
	
	/**
	 * Attempt to retrieve an account by phone number from the database
	 */
	function getAccount($sPhone)
	{
		global $aConfig;
		mysql_connect($aConfig['mysql']['host'], $aConfig['mysql']['user'], $aConfig['mysql']['pass']);
		
		// Make sure we are using the correct database
		$sDb = $aConfig['mysql']['db_name'];
		mysql_query("use $sDb");
		
		$sQuery = sprintf("SELECT * FROM account WHERE phone='%s'",
			mysql_real_escape_string($sPhone));
		$oResult = mysql_query($sQuery);
		if (!$oResult || mysql_num_rows($oResult) == 0) {
			return false;
		}
		
		return mysql_fetch_assoc($oResult);
	}
	
	/**
	 * Get a Google Calendar API client object
	 */
	function getGoogleClient($sUser, $sPassword)
	{
		return Zend_Gdata_ClientLogin::getHttpClient($sUser, $sPassword, Zend_Gdata_Calendar::AUTH_SERVICE_NAME);
	}
	
	/**
	 * Make a call to the Google Calendar API to add an event
	 * Stolen from Google's API documentation
	 * http://code.google.com/apis/calendar/data/1.0/developers_guide_php.html#AuthClientLogin
	 */
	function createQuickAddEvent($client, $quickAddText) {
		$gdataCal = new Zend_Gdata_Calendar($client);
		$event = $gdataCal->newEventEntry();
		$event->content = $gdataCal->newContent($quickAddText);
		$event->quickAdd = $gdataCal->newQuickAdd('true');
		$newEvent = $gdataCal->insertEvent($event);
	}

?>