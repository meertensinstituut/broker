<?php

namespace Broker;

class Solr {
  private $request = null;
  private $handler = null;
  private $url = null;
  private $shards = null;
  private $cache = null;
  private $configuration = null;
  public function __construct(string $configuration, string $url, string $handler, string $request, $shards, $cache) {
    $this->request = $request;
    $this->handler = $handler;
    $this->url = $url;
    $this->shards = $shards;
    $this->cache = $cache;
    $this->configuration = $configuration;    
  }
  public function getResponse() {
    $finalRequest = $this->request;
    if ($this->shards != null) { 
      $finalRequest = ($finalRequest?$finalRequest."&":"")."shards=".urlencode($this->shards);
    }    
    if($this->cache!=null) {
      list($id, $response) = $this->cache->check($this->configuration, $this->url . $this->handler."/", $finalRequest);
      if($id && $response) {
        if($data = json_decode ( $response )) {
          return $data;
        } else {
          $this->cache->del($id);
        }  
      }
    }
    $ch = curl_init ( $this->url . $this->handler."/" );
    $options = array(
        CURLOPT_HTTPHEADER => array ("Content-Type: application/x-www-form-urlencoded; charset=utf-8"),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $finalRequest
    );
    curl_setopt_array ( $ch, $options );
    $result = curl_exec ( $ch );
    
    if (($data = json_decode ( $result ))) {
      //cache
      if($this->cache!=null && !isset($data->error)) {
        $this->cache->create($this->configuration, $this->url . $this->handler."/", $finalRequest, $result);
      }
      // return data
      return $data;
    } else {
      throw new \Broker\SolrException("No valid json from ".$this->url, 500);      
    }
  }
}

?>