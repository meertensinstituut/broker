<?php

// settings
require_once ("includes/defaults.inc.php");
require_once (SITE_ROOT_DIR . "vendor/autoload.php");

// load configuration
$configuration = new \Broker\Configuration ( SITE_CONFIG_DIR . "config.inc.php" );

// smarty
$smarty = new Smarty ();
$smarty->setTemplateDir ( SITE_LAYOUT_SMARTY_TEMPLATES_DIR );
$smarty->setCompileDir ( SITE_CACHE_SMARTY_TEMPLATESC_DIR );
$smarty->setCacheDir ( SITE_CACHE_SMARTY_CACHE_DIR );
$smarty->setConfigDir ( SITE_LAYOUT_SMARTY_CONFIG_DIR );

// create basic output
header ( "Content-Type: text/html; charset=utf-8" );
$smarty->assign ( "_SITE_LOCATION", SITE_LOCATION );
$smarty->assign ( "_LAYOUT_DIR", LAYOUT_DIR );

if ($configuration->installed ()) {
  
  // session
  session_set_save_handler ( new \Broker\Session ( SITE_CACHE_DATABASE_DIR ) );
  session_name ( "broker" );
  session_start ();
  
  // authentication
  $authentication = new \Broker\Authentication ( $configuration->getConfig ( "authentication" ) );
  
  // create output
  header ( "Content-Type: text/html; charset=utf-8" );
  $smarty->assign ( "_SITE_LOCATION", SITE_LOCATION );
  $smarty->assign ( "_SITE_ROOT_DIR", SITE_ROOT_DIR );
  $smarty->assign ( "_LAYOUT_DIR", LAYOUT_DIR );
  $smarty->assign ( "_authentication", $authentication );
  $smarty->assign ( "_configuration", $configuration );
  if ($authentication->access ()) {
    if (isset ( $_GET ["operation"] ) && is_string ( $operation = $_GET ["operation"] ) && (trim ( $operation ) != "")) {
      $smarty->assign ( "_smartyIncludeModule", $operation );
      if (preg_match ( "/^[a-z]+$/i", $operation )) {
        if (file_exists ( SITE_INCLUDES_MODULES_DIR . $operation . ".inc.php" )) {
          include_once (SITE_INCLUDES_MODULES_DIR . $operation . ".inc.php");
          $smarty->assign ( "_smartyIncludeBlock", "module/block_" . $operation . ".tpl" );
        } else {
          header ( "HTTP/1.0 404 Not Found" );
          $smarty->assign ( "_smartyIncludeBlock", "block_notfound.tpl" );
        }
      } else {
        header ( "HTTP/1.0 404 Not Found" );
        $smarty->assign ( "_smartyIncludeBlock", "block_notfound.tpl" );
      }
    } else {
      $smarty->assign ( "_smartyIncludeModule", "" );
      $smarty->assign ( "_smartyIncludeBlock", "block_home.tpl" );
    }
  } else {
    if (strtoupper ( $_SERVER ['REQUEST_METHOD'] ) == "POST") {
      if(isset ( $_GET ["operation"]) && is_string($_GET ["operation"]) && $_GET["operation"]=="search") {
        header ( "HTTP/1.0 403 Forbidden" );      
        echo ("No access, please register IP or provide a (valid) key to get access");
        exit ();
      }  
    }
    if (file_exists ( SITE_INCLUDES_MODULES_DIR . "login.inc.php" )) {
      include_once (SITE_INCLUDES_MODULES_DIR . "login.inc.php");
      $smarty->assign ( "_smartyIncludeModule", "login" );
      $smarty->assign ( "_smartyIncludeBlock", "module/block_login.tpl" );
    }
  }
  $smarty->display ( "index.tpl" );
} else {
  $smarty->assign ( "_SITE_ROOT_DIR", SITE_ROOT_DIR );
  $smarty->display ( "install.tpl" );
}

?>