<?php

/**
 * Broker
 * @package Broker
 */
namespace Broker;

/**
 * Handles solr requests
 */
class Solr {
  /**
   * Request
   *
   * @var string
   */
  private $request = null;
  /**
   * Handler
   *
   * @var string
   */
  private $handler = null;
  /**
   * Url
   *
   * @var string
   */
  private $url = null;
  /**
   * Shards
   *
   * @var array
   */
  private $shards = null;
  /**
   * Cache
   *
   * @var \Broker\Cache
   */
  private $cache = null;
  /**
   * Configuration
   *
   * @var unknown
   */
  private $configuration = null;
  /**
   * Constructor
   *
   * @param string $configuration          
   * @param string $url          
   * @param string $handler          
   * @param string $request          
   * @param array $shards          
   * @param \Broker\Cache $cache          
   */
  public function __construct($configuration, $url, $handler, $request, $shards, $cache) {
    $this->request = $request;
    $this->handler = $handler;
    $this->url = $url;
    $this->shards = $shards;
    $this->cache = $cache;
    $this->configuration = $configuration;
  }
  /**
   * Perform request and return response object
   *
   * @return unknown
   * @throws \Broker\SolrException
   */
  public function getResponse() {
    $finalRequest = $this->request;
    if ($this->shards != null) {
      $finalRequest = ($finalRequest ? $finalRequest . "&" : "") . "shards=" . urlencode ( $this->shards );
    }
    if ($this->cache != null) {
      list ( $id, $response ) = $this->cache->check ( $this->configuration, $this->url . $this->handler . "/", $finalRequest );
      if ($id && $response) {
        if ($data = json_decode ( $response )) {
          return $data;
        } else {
          $this->cache->del ( $id );
        }
      }
    }
    $ch = curl_init ( $this->url . $this->handler . "/" );
    $options = array (
        CURLOPT_HTTPHEADER => array (
            "Content-Type: application/x-www-form-urlencoded; charset=utf-8" 
        ),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $finalRequest 
    );
    curl_setopt_array ( $ch, $options );
    $result = curl_exec ( $ch );
    
    if (($data = json_decode ( $result ))) {
      // cache
      if ($this->cache != null && ! isset ( $data->error )) {
        $this->cache->create ( $this->configuration, $this->url . $this->handler . "/", $finalRequest, $result );
      }
      // return data
      return $data;
    } else {
      throw new \Broker\SolrException ( "No valid json from " . $this->url, 500 );
    }
  }
}

?>