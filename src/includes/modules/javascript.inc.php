<?php
header ( "Content-Type: text/javascript; charset=utf-8" );
header ( "Access-Control-Allow-Origin: *" );
header ( "Access-Control-Allow-Headers: content-type" );

if (isset ( $_GET ["suboperation"] ) && is_string ( $_GET ["suboperation"] ) && trim ( $_GET ["suboperation"] ) != "") {
  if ($_GET ["suboperation"] == "examples") {
    $output = array();
    //collect examples
    $output["examples"] = array();
    $directory = SITE_LAYOUT_DIR."examples".DIRECTORY_SEPARATOR;
    if (is_dir($directory)) {
      if ($dh = opendir($directory)) {
        while (($file = readdir($dh)) !== false) {
          if(is_file($directory.$file) && preg_match("/^([0-9]+[0-9a-z_]*\.)?([a-z0-9\_]+)\.(html|php)$/i",$file,$match)) {
            $name = str_replace("_"," ",$match[2]);
            if(preg_match("/^(([0-9]+)(_[0-9a-z]+)?(_[0-9a-z]+)?)\.$/i", $match[1], $submatches)) {
              $code = $submatches[1];
            } else {
              $code = "";
            }
            $output["examples"][] = array("title"=>trim($name), "code" => $code, "url"=>SITE_LOCATION.LAYOUT_DIR."/examples/".$file);
          }
        }
        closedir($dh);
      }
    }
    //collect expansions
    $output["expansion"] = $configuration->getExpansions();
    //collect configurations
    $output["solr"] = array();
    foreach($configuration->solr AS $key => $value) {
      if(preg_match("/^[a-z0-9]+$/i",$key)) {
        $output["solr"][$key] = $value;
      }      
    }
    echo(json_encode($output));
  } 
} 
exit();
?>