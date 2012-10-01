<?php
/*-----8<--------------------------------------------------------------------
 *
 * BEdita - a semantic content management framework
 *
 * Copyright 2011 ChannelWeb Srl, Chialab Srl
 *
 * This file is part of BEdita: you can redistribute it and/or modify
 * it under the terms of the Affero GNU General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * BEdita is distributed WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the Affero GNU General Public License for more details.
 * You should have received a copy of the Affero GNU General Public License
 * version 3 along with BEdita (see LICENSE.AGPL).
 * If not, see <http://gnu.org/licenses/agpl-3.0.html>.
 *
 *------------------------------------------------------------------->8-----
 */

/**
 * bedita.cfg.php - local installation specific settings,
 					overrides settings in bedita.ini
 *
 * @link			http://www.bedita.com
 * @version			$Revision: 3487 $
 * @modifiedby 		$LastChangedBy: ste $
 * @lastmodified	$LastChangedDate: 2011-12-07 11:02:10 +0100 (mer, 07 dic 2011) $
 *
 * $Id: bedita.cfg.php.sample 3487 2011-12-07 10:02:10Z ste $
 */

define("BEDITA_DEV_SYSTEM", true);

// BEdita instance name
$config["projectName"] = "BEdita 3.2 Populus";


//////////////////////////////////
//								//
//    START - SERVER SETTINGS	//
//								//
//////////////////////////////////


/**
 ** ******************************************
 **  FileSystem Paths, URIs, Files defaults
 ** ******************************************
 */

// BEdita URL
$config['beditaUrl']="http://10.0.0.102/bedita";

/** Multimedia - root folder on filesystem (use absolute path, if you need to change it)
 **
 **	On Linux could be /var/www/bedita/bedita-app/webroot/files
 ** On Windows could be C:\\xampp\\htdocs\\bedita\\bedita-app\\webroot\\files
 ** Or you can use DS as crossplatform directory separator as in default
 ** BEDITA_CORE_PATH . DS . "webroot" . DS . "files"
 ** where BEDITA_CORE_PATH points to bedita/bedita_app dir
 */
define("BEDITA_DB_NAME", "bedita");
//define("BEDITA_DB_NAME", "sadava");
//define("BEDITA_DB_NAME", "bedita_deploy");
// define("BEDITA_DB_NAME", "bedita");
// define("BEDITA_DB_NAME", "sala");
// define("BEDITA_DB_NAME", "mcarchitects");

$config['mediaRoot'] = '/home/bato/workspace/media/' . BEDITA_DB_NAME;

// Multimedia - URL prefix (without trailing slash)
$config['mediaUrl'] = 'http://localhost/workspace/media/' . BEDITA_DB_NAME;

// alternative frontends path (absolute path)
//define('BEDITA_FRONTENDS_PATH', '/var/www/bedita/bedita-frontends');

// alternative bedita modules path (absolute path)
define('BEDITA_MODULES_PATH', '/home/bato/workspace/bedita-plugins');

// alternative bedita addons path (absolute path)
define('BEDITA_ADDONS_PATH', '/home/bato/workspace/bedita-plugins/addons');
//define('BEDITA_ADDONS_PATH', '/home/bato/workspace/addons-test');


/**
 ** ******************************************
 **  System locales available
 **  use locale strings or arrays for fallbacks
 **  (see setLocale php function)
 ** ******************************************
 */

$config["locales"] = array(
	"eng" => array("en_US.utf8", "en_GB.utf8"),
	"ita" => "it_IT.utf8",
);

/**
 ** ******************************************
 **  SMTP and mail support settings
 ** ******************************************
 */

/**
 ** smtp server configuration used for any kind of mail (notifications, newsletters, etc...)
 */
$config['smtpOptions'] = array(
	'port' => '25',
	'timeout' => '30',
	'host' => 'puskas.channelweb.it',
	'username' => 'a.pagliarini@channelweb.it',
	'password' => 'bato75c'
);

/**
 * mail support configuration
 * uncomment and fill to send error messages to your support
 */
//$config["mailSupport"] = array(
//	"from" => "bedita-support@...",
//	'to' => "bedita-support@...",
//	'subject' => "[bedita] error message",
//);

//////////////////////////////////
//								//
//     END - SERVER SETTINGS	//
//								//
//////////////////////////////////


/**
 ** ******************************************
 **  Content and UI Elements defaults
 ** ******************************************
 */

// User Interface default language, choose between bedita-app/locale dir [see also 'multilang' below]
//$config['Config']['language'] = "eng";

// Set 'multilang' true for user choice [also set 'multilang' true if $config['Config']['language'] is set]
// $config['multilang'] = true;

// default object language
// $config['defaultLang'] = "eng";

// ISO-639-3 codes - User interface language options (backend)
// $config['langsSystem'] = array(
//	"eng"	=> "english",
//	"ita"	=> "italiano",
//	"deu"	=> "deutsch",
//	"por"	=> "portuguěs"
// );

// Status of new objects
//$config['defaultStatus'] = "draft" ;

// TinyMCE Rich Text Editor for long_text ['false' to disable - defaults true]
// $config['mce'] = true;


/**
 ** ******************************************
 **  Login (backend) and Security Policies
 ** ******************************************
 */
//
// A simple example with a simple password regexp rule, uncomment and change according to your needs
//
//$config['loginPolicy'] = array (
//	"maxLoginAttempts" => 3,
//	"maxNumDaysInactivity" => 60,
//	"maxNumDaysValidity" => 10,
//	"passwordRule" => "/\w{4,}/", // regexp to match for valid passwords (empty => no regexp)
//	"passwordErrorMessage" => "Password must contain at least 4 valid alphanumeric characters", // error message for passwrds not matching given regexp
//);



/**
 ** ******************************************
 **  Local installation specific settings
 ** ******************************************
 */


/**
 ** Relations - local objects' relation types
 ** define here custom semantic relations
 */
// $config["objRelationType"] = array(
// 		"language" => array()
// );

// One-way relation, array of objRelationType keys
// $config["cfgOneWayRelation"] = array();

// Reserved words [avoided in nickname creation]
// $config["cfgReservedWords"] = array();

/**
 * Lang selection options ISO-639-3 - Language options for contents
 */
//$config['langOptions'] = array(
//	"ita"	=> "italiano",
//	"eng"	=> "english",
//	"spa"	=> "espa&ntilde;ol",
//	"por"	=> "portugu&ecirc;s",
//	"fra"	=> "fran&ccedil;ais",
//	"deu"	=> "deutsch"
//) ;


// add langs.iso.php to language options for content
//$config['langOptionsIso'] = false;


// default values for fulltext search
// $config['searchFields'] = array(
//	'ModelName' => array('title'=> 6, 'description' => 4),
//) ;


// specific css filename for newsletter templates
//$config['newsletterCss'] = "base.css";

/**
 * save history navigation

 * "sessionEntry" => number of history items in session
 * "showDuplicates" => false to not show duplicates in history session
 * "trackNotLogged" => true save history for all users (not logged too)
 */
//$config["history"] = array(
//	"sessionEntry" => 5,
//	"showDuplicates" => false,
//	"trackNotLogged" => false
//);



$config['soap'] = array (
	"default" => array (
		'useLib' => 'nusoap', // values: soap (PHP module), nusoap (NuSOAP library)
		'wsdl' => "http://www.myzanichelli.it/myzanichelliws/index.php?wsdl", // wsdl resource, local or url
		'debugLevel' => 9,
		'endpoint' => '', // specify a different endpoint)
	)
);

?>
