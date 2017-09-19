<?php
/**
 * Defaults
 * @package Broker
 */
define ( "SITE_LOCATION", rtrim($dirname ( $_SERVER ["SCRIPT_NAME"] ), "DIRECTORY_SEPARATOR") . DIRECTORY_SEPARATOR );
define ( "SITE_ROOT_DIR", rtrim(realpath ( dirname ( dirname ( __FILE__ ) ) ), "DIRECTORY_SEPARATOR") . DIRECTORY_SEPARATOR );
define ( "CONFIG_DIR", "config" );
define ( "CONFIG_MODULES_DIR", "modules" );
define ( "CONFIG_MODULES_EXPANSION_DIR", "expansion" );
define ( "SITE_CONFIG_DIR", SITE_ROOT_DIR . CONFIG_DIR . DIRECTORY_SEPARATOR );
define ( "SITE_CONFIG_MODULES_DIR", SITE_CONFIG_DIR . CONFIG_MODULES_DIR . DIRECTORY_SEPARATOR );
define ( "SITE_CONFIG_MODULES_EXPANSION_DIR", SITE_CONFIG_MODULES_DIR . CONFIG_MODULES_EXPANSION_DIR . DIRECTORY_SEPARATOR );
define ( "INCLUDES_DIR", "includes" );
define ( "INCLUDES_CLASS_DIR", "class" );
define ( "INCLUDES_MODULES_DIR", "modules" );
define ( "SITE_INCLUDES_DIR", SITE_ROOT_DIR . INCLUDES_DIR . DIRECTORY_SEPARATOR );
define ( "SITE_INCLUDES_CLASS_DIR", SITE_INCLUDES_DIR . INCLUDES_CLASS_DIR . DIRECTORY_SEPARATOR );
define ( "SITE_INCLUDES_MODULES_DIR", SITE_INCLUDES_DIR . INCLUDES_MODULES_DIR . DIRECTORY_SEPARATOR );
define ( "LAYOUT_DIR", "layout" );
define ( "LAYOUT_SMARTY_DIR", "smarty" );
define ( "LAYOUT_SMARTY_TEMPLATES_DIR", "templates" );
define ( "LAYOUT_SMARTY_CONFIG_DIR", "config" );
define ( "SITE_LAYOUT_DIR", SITE_ROOT_DIR . LAYOUT_DIR . DIRECTORY_SEPARATOR );
define ( "SITE_LAYOUT_SMARTY_DIR", SITE_LAYOUT_DIR . LAYOUT_SMARTY_DIR . DIRECTORY_SEPARATOR );
define ( "SITE_LAYOUT_SMARTY_TEMPLATES_DIR", SITE_LAYOUT_SMARTY_DIR . LAYOUT_SMARTY_TEMPLATES_DIR . DIRECTORY_SEPARATOR );
define ( "SITE_LAYOUT_SMARTY_CONFIG_DIR", SITE_LAYOUT_SMARTY_DIR . LAYOUT_SMARTY_CONFIG_DIR . DIRECTORY_SEPARATOR );
define ( "CACHE_DIR", "cache" );
define ( "CACHE_CONFIGURATION_DIR", "configuration" );
define ( "CACHE_DATABASE_DIR", "database" );
define ( "CACHE_SMARTY_DIR", "smarty" );
define ( "CACHE_SMARTY_CACHE_DIR", "cache" );
define ( "CACHE_SMARTY_TEMPLATESC_DIR", "templates_c" );
define ( "SITE_CACHE_DIR", SITE_ROOT_DIR . CACHE_DIR . DIRECTORY_SEPARATOR );
define ( "SITE_CACHE_CONFIGURATION_DIR", SITE_CACHE_DIR . CACHE_CONFIGURATION_DIR . DIRECTORY_SEPARATOR );
define ( "SITE_CACHE_DATABASE_DIR", SITE_CACHE_DIR . CACHE_DATABASE_DIR . DIRECTORY_SEPARATOR );
define ( "SITE_CACHE_SMARTY_DIR", SITE_CACHE_DIR . CACHE_SMARTY_DIR . DIRECTORY_SEPARATOR );
define ( "SITE_CACHE_SMARTY_CACHE_DIR", SITE_CACHE_SMARTY_DIR . CACHE_SMARTY_CACHE_DIR . DIRECTORY_SEPARATOR );
define ( "SITE_CACHE_SMARTY_TEMPLATESC_DIR", SITE_CACHE_SMARTY_DIR . CACHE_SMARTY_TEMPLATESC_DIR . DIRECTORY_SEPARATOR );
/**
 * Autoloader class
 *
 * @param string $class          
 */
function autoLoader($class) {
  if (preg_match ( "/^Broker\\\\([^\\\\]+)$/", $class, $match )) {
    if (file_exists ( SITE_INCLUDES_CLASS_DIR . $match [1] . ".class.php" )) {
      require_once (SITE_INCLUDES_CLASS_DIR . $match [1] . ".class.php");
    } else {
      die ( "class " . $class . " not found" );
    }
  } else if (preg_match ( "/^BrokerExpansion\\\\([^\\\\]+)Expansion$/", $class, $match )) {
    if (file_exists ( SITE_CONFIG_MODULES_EXPANSION_DIR . $match [1] . "Expansion.class.php" )) {
      require_once (SITE_CONFIG_MODULES_EXPANSION_DIR . $match [1] . "Expansion.class.php");
    }
  }
}
spl_autoload_register ( "autoLoader" );

// validate configuration
\Broker\Configuration::validate ();

?>