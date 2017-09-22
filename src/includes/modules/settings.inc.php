<?php
/**
 * Module settings
 */
if (! $authentication->accessBasedOnLogin ()) {
  $authentication->logout ();
  header ( "Location: " . $configuration->url ( "login", "settings" ) );
  exit ();
} else {
  $cache = new \Broker\Cache ( SITE_CACHE_DATABASE_DIR, $configuration, null );
  $status = new \Broker\Status ( SITE_CACHE_DATABASE_DIR, $configuration, $cache );
  $collection = new \Broker\Collection ( SITE_CACHE_DATABASE_DIR, $configuration );
  $expansionCache = new \Broker\ExpansionCache ( SITE_CACHE_DATABASE_DIR );
  $session = new \Broker\Session ( SITE_CACHE_DATABASE_DIR );
  
  if (strtoupper ( $_SERVER ['REQUEST_METHOD'] ) == "POST") {
    if (isset ( $_POST ["reset"] )) {
      if ($authentication->accessWithAdminPrivileges ()) {
        $cache->reset ();
        $status->reset ();
        $collection->reset ();
        $expansionCache->reset ();
        $session->reset ();
        $configuration->reset ();
      }
    }
    header ( "Location: " . $configuration->url ( "settings", null ) );
    exit ();
  }
  
  $smarty->assign ( "_cacheNumber", $cache->number () );
  $smarty->assign ( "_statusNumber", $status->number () );
  $smarty->assign ( "_collectionNumber", $collection->number () );
  $smarty->assign ( "_expansionCacheNumber", $expansionCache->number () );
  $smarty->assign ( "_sessionNumber", $session->number () );
  $smarty->assign ( "_configurationDate", $configuration->getConfigTimestamp () );
  $smarty->assign ( "_solrDate", $configuration->getSolrTimestamp () );
}
?>