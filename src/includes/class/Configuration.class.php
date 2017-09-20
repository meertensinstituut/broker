<?php

/**
 * Broker
 * @package Broker
 */
namespace Broker;

/**
 * Configuration
 */
class Configuration {
  /**
   * Configuration
   *
   * @var array
   */
  public $config;
  /**
   * Solr configuration
   *
   * @var array
   */
  public $solr;
  /**
   * Filename configuration
   *
   * @var string
   */
  private $filename;
  /**
   * Timestamp configuration file
   *
   * @var number
   */
  private $configTimestamp;
  /**
   * Timestamp solr configuration
   *
   * @var number
   */
  private $solrTimestamp;
  /**
   * Constructor
   *
   * @param string $file
   *          configuration file
   */
  public function __construct($file) {
    $this->filename = $file;
    if (file_exists ( $file ) && is_readable ( $file )) {
      $this->load ( $file );
      $this->getSolrConfiguration ( md5 ( $file ), filectime ( $file ) );
    } else {
      $this->filename = null;
      $this->config = null;
      $this->solr = null;
      $this->configTimestamp = null;
      $this->solrTimestamp = null;
    }
  }
  /**
   * Check if configuration is found and processed
   *
   * @return bool
   */
  public function installed() {
    return $this->config != null;
  }
  /**
   * Get configuration item
   *
   * @param string $name          
   * @return unknown
   */
  public function getConfig($name) {
    if ($this->config && is_array ( $this->config ) && isset ( $this->config [$name] ) && is_array ( $this->config [$name] )) {
      return $this->config [$name];
    } else {
      return false;
    }
  }
  /**
   * Get solr configuration item
   *
   * @param unknown $name          
   */
  public function getSolrConfig($name) {
    if ($this->solr && is_array ( $this->solr ) && isset ( $this->solr [$name] ) && is_array ( $this->solr [$name] )) {
      return $this->solr [$name];
    } else {
      return false;
    }
  }
  /**
   * Create url based on optional operation and suboperation
   *
   * @param NULL|string $operation          
   * @param NULL|string $suboperation          
   * @return string
   */
  public function url($operation = null, $suboperation = null) {
    if ($operation == null || ! $operation || ! preg_match ( "/^[a-z]+$/i", $operation )) {
      return SITE_LOCATION;
    } else {
      if ($suboperation == null || ! $suboperation || ! preg_match ( "/^[a-z0-9]+$/i", $suboperation )) {
        if (array_key_exists ( "HTTP_MOD_REWRITE", $_SERVER )) {
          return SITE_LOCATION . $operation . DIRECTORY_SEPARATOR;
        } else {
          return SITE_LOCATION . "?operation=" . urlencode ( $operation );
        }
      } else {
        if (array_key_exists ( "HTTP_MOD_REWRITE", $_SERVER )) {
          return SITE_LOCATION . $operation . DIRECTORY_SEPARATOR . $suboperation . DIRECTORY_SEPARATOR;
        } else {
          return SITE_LOCATION . "?operation=" . urlencode ( $operation ) . "&suboperation=" . urlencode ( $suboperation );
        }
      }
    }
  }
  /**
   * Get expansions
   *
   * @return array
   */
  public function getExpansions() {
    $list = array ();
    $directory = SITE_CONFIG_MODULES_EXPANSION_DIR;
    if (is_dir ( $directory )) {
      if ($dh = opendir ( $directory )) {
        while ( ($file = readdir ( $dh )) !== false ) {
          if (is_file ( $directory . $file ) && preg_match ( "/^([A-Z][A-Za-z0-9]+)Expansion\.class\.php$/", $file, $match )) {
            $expansionName = $match [1];
            $expansionObjectClass = "\\BrokerExpansion\\" . $expansionName . "Expansion";
            if (class_exists ( $expansionObjectClass, true ) && in_array ( "Broker\\Expansion", class_implements ( $expansionObjectClass, true ) )) {
              $list [lcfirst ( $expansionName )] = array (
                  "cached" => $expansionObjectClass::cached (),
                  "description" => $expansionObjectClass::description (),
                  "parameters" => $expansionObjectClass::parameters () 
              );
            }
          }
        }
      }
    }
    return $list;
  }
  /**
   * Get timestamp configuration file
   *
   * @return NULL|number
   */
  public function getConfigTimestamp() {
    return $this->configTimestamp;
  }
  /**
   * Get timestamp automatic solr configuration
   *
   * @return unknown
   */
  public function getSolrTimestamp() {
    return $this->solrTimestamp;
  }
  /**
   * Reset configuration
   */
  public function reset() {
    unlink ( SITE_CACHE_CONFIGURATION_DIR . "solr.json" );
    if (file_exists ( $this->filename ) && is_readable ( $this->filename )) {
      $this->getSolrConfiguration ( md5 ( $file ), filectime ( $file ) );
    }
  }
  /**
   * Load configuration from file
   *
   * @param string $file          
   */
  private function load($file) {
    $old = array ();
    $old = get_defined_vars ();
    include ($file);
    $new = get_defined_vars ();
    $this->config = array ();
    $this->configTimestamp = filemtime ( $file );
    foreach ( $new as $key => $value ) {
      if (! isset ( $old [$key] )) {
        $this->config [$key] = $value;
      }
    }
  }
  /**
   * Create solr configuration if necessary
   *
   * @param string $md5hash          
   * @param number $filetime          
   */
  private function getSolrConfiguration($md5hash, $filetime) {
    $filename = SITE_CACHE_CONFIGURATION_DIR . "solr.json";
    if (file_exists ( $filename )) {
      $data = file_get_contents ( $filename );
      $this->solr = json_decode ( $data, true );
      $this->solrTimestamp = filemtime ( $filename );
      if ($this->solr && json_last_error () == JSON_ERROR_NONE) {
        if (! isset ( $this->solr ["_md5hash"] ) || $this->solr ["_md5hash"] != $md5hash) {
          unlink ( $filename );
        } else if (! isset ( $this->solr ["_filetime"] ) || $this->solr ["_filetime"] != $filetime) {
          unlink ( $filename );
        } else {
          return;
        }
      }
    }
    // reload and regenerate
    $this->solr = array ();
    $this->solr ["_md5hash"] = $md5hash;
    $this->solr ["_filetime"] = $filetime;
    if (isset ( $this->config ["solr"] ) && is_array ( $this->config ["solr"] ) && count ( $this->config ["solr"] ) > 0) {
      foreach ( $this->config ["solr"] as $key => $solrConfiguration ) {
        if (! is_array ( $solrConfiguration )) {
          die ( "Invalid solr configuration '" . $key . "'" );
        } else if (! isset ( $solrConfiguration ["url"] ) || ! is_string ( $solrConfiguration ["url"] ) || trim ( $solrConfiguration ["url"] ) == "") {
          die ( "Invalid or no url in solr configuration '" . $key . "'" );
        } else if (! preg_match ( "/^[a-z0-9]+$/i", $key )) {
          die ( "Invalid name solr configuration '" . $key . "'" );
        } else {
          $this->solr [$key] = array ();
          $this->solr [$key] ["fields"] = array ();
          $this->solr [$key] ["dynamicFields"] = array ();
          $this->solr [$key] ["multiValued"] = array ();
          $this->solr [$key] ["indexed"] = array ();
          $this->solr [$key] ["required"] = array ();
          $this->solr [$key] ["stored"] = array ();
          $this->solr [$key] ["mtas"] = array ();
          $this->solr [$key] ["typeText"] = array ();
          $this->solr [$key] ["typeBoolean"] = array ();
          $this->solr [$key] ["typeString"] = array ();
          $this->solr [$key] ["typeInteger"] = array ();
          $this->solr [$key] ["typeDate"] = array ();
          $this->solr [$key] ["typeLong"] = array ();
          $this->solr [$key] ["typeBinary"] = array ();
          // get schema
          $ch = curl_init ( $solrConfiguration ["url"] . "schema?wt=json" );
          $options = array (
              CURLOPT_HTTPHEADER => array (
                  "Content-Type: application/x-www-form-urlencoded; charset=utf-8" 
              ),
              CURLOPT_RETURNTRANSFER => true 
          );
          curl_setopt_array ( $ch, $options );
          $result = curl_exec ( $ch );
          if ($data = json_decode ( $result, true )) {
            if (isset ( $data ["schema"] ) && is_array ( $data ["schema"] )) {
              $queryParsers = array ();
              $fieldTypes = array ();
              $fieldTypes ["text"] = array ();
              $fieldTypes ["boolean"] = array ();
              $fieldTypes ["string"] = array ();
              $fieldTypes ["integer"] = array ();
              $fieldTypes ["long"] = array ();
              $fieldTypes ["date"] = array ();
              $fieldTypes ["binary"] = array ();
              $fieldTypes ["mtas"] = array ();
              if (isset ( $data ["schema"] ["fieldTypes"] ) && is_array ( $data ["schema"] ["fieldTypes"] )) {
                foreach ( $data ["schema"] ["fieldTypes"] as $item ) {
                  if (isset ( $item ["name"] ) && is_string ( $item ["name"] )) {
                    if (isset ( $item ["class"] ) && is_string ( $item ["class"] )) {
                      if ($item ["class"] == "solr.TextField" || $item ["class"] == "mtas.solr.schema.MtasPreAnalyzedField") {
                        $fieldTypes ["text"] [] = $item ["name"];
                      } else if ($item ["class"] == "solr.BoolField") {
                        $fieldTypes ["boolean"] [] = $item ["name"];
                      } else if ($item ["class"] == "solr.StrField") {
                        $fieldTypes ["string"] [] = $item ["name"];
                      } else if ($item ["class"] == "solr.TrieIntField") {
                        $fieldTypes ["integer"] [] = $item ["name"];
                      } else if ($item ["class"] == "solr.TrieLongField") {
                        $fieldTypes ["long"] [] = $item ["name"];
                      } else if ($item ["class"] == "solr.TrieDateField") {
                        $fieldTypes ["date"] [] = $item ["name"];
                      } else if ($item ["class"] == "solr.BinaryField") {
                        $fieldTypes ["binary"] [] = $item ["name"];
                      }
                    }
                    if (isset ( $item ["postingsFormat"] ) && is_string ( $item ["postingsFormat"] )) {
                      if ($item ["postingsFormat"] == "MtasCodec") {
                        $fieldTypes ["mtas"] [] = $item ["name"];
                      }
                    }
                  }
                }
              }
              if (isset ( $data ["schema"] ["dynamicFields"] ) && is_array ( $data ["schema"] ["dynamicFields"] )) {
                foreach ( $data ["schema"] ["dynamicFields"] as $item ) {
                  if (isset ( $item ["name"] ) && is_string ( $item ["name"] ) && trim ( $item ["name"] ) != "") {
                    $this->solr [$key] ["dynamicFields"] [] = $item ["name"];
                  }
                  $this->solr [$key] = $this->_processSolrConfiguration ( $item, $fieldTypes, $this->solr [$key] );
                }
              }
              if (isset ( $data ["schema"] ["fields"] ) && is_array ( $data ["schema"] ["fields"] )) {
                foreach ( $data ["schema"] ["fields"] as $item ) {
                  if (isset ( $item ["name"] ) && is_string ( $item ["name"] ) && trim ( $item ["name"] ) != "") {
                    $this->solr [$key] ["fields"] [] = $item ["name"];
                  }
                  $this->solr [$key] = $this->_processSolrConfiguration ( $item, $fieldTypes, $this->solr [$key] );
                }
              }
              if (isset ( $data ["schema"] ["uniqueKey"] ) && is_string ( $data ["schema"] ["uniqueKey"] )) {
                $this->solr [$key] ["uniqueKey"] = $data ["schema"] ["uniqueKey"];
              }
              list ( $this->solr [$key] ["exampleFieldText"], $this->solr [$key] ["exampleFieldTextValues"] ) = $this->_findExample ( isset ( $solrConfiguration ["exampleFieldText"] ) ? $solrConfiguration ["exampleFieldText"] : null, $this->solr [$key], $solrConfiguration, array (
                  "title",
                  "name" 
              ), "text", true, null, true, null, false );
              list ( $this->solr [$key] ["exampleFieldString"], $this->solr [$key] ["exampleFieldStringValues"] ) = $this->_findExample ( isset ( $solrConfiguration ["exampleFieldString"] ) ? $solrConfiguration ["exampleFieldString"] : null, $this->solr [$key], $solrConfiguration, array (
                  "title",
                  "name" 
              ), "text", true, null, true, null, false );
              list ( $this->solr [$key] ["exampleFieldInteger"], $this->solr [$key] ["exampleFieldIntegerValues"] ) = $this->_findExample ( isset ( $solrConfiguration ["exampleFieldInteger"] ) ? $solrConfiguration ["exampleFieldInteger"] : null, $this->solr [$key], $solrConfiguration, array (
                  "year" 
              ), "integer", true, null, true, null, false );
              // list ( $this->solr [$key] ["exampleFieldMtas"], $this->solr [$key] ["exampleFieldMtasValues"] ) = $this->_findExample ( isset ( $solrConfiguration ["exampleFieldMtas"] ) ? $solrConfiguration ["exampleFieldMtas"] : null, $this->solr [$key], $solrConfiguration, array (
              // "mtas"
              // ), null, true, null, null, null, true );
              
              list ( $this->solr [$key] ["exampleFieldMtas"], $this->solr [$key] ["exampleFieldMtasWord"], $this->solr [$key] ["exampleFieldMtasLemma"], $this->solr [$key] ["exampleFieldMtasPos"], $this->solr [$key] ["exampleFieldMtasSinglePosition"], $this->solr [$key] ["exampleFieldMtasMultiplePosition"], $this->solr [$key] ["exampleFieldMtasSetPosition"], $this->solr [$key] ["exampleFieldMtasIntersecting"] ) = $this->_findMtasExamples ( isset ( $solrConfiguration ["exampleFieldMtas"] ) ? $solrConfiguration ["exampleFieldMtas"] : null, $this->solr [$key], $solrConfiguration, array (
                  "mtas" 
              ) );
            } else {
              die ( "No schema available for '" . $solrConfiguration ["url"] . "' in configuration '" . $key . "'" );
            }
          } else {
            die ( "No schema available for '" . $solrConfiguration ["url"] . "' in configuration '" . $key . "'" );
          }
          // get config
          $ch = curl_init ( $solrConfiguration ["url"] . "config?wt=json" );
          $options = array (
              CURLOPT_HTTPHEADER => array (
                  "Content-Type: application/x-www-form-urlencoded; charset=utf-8" 
              ),
              CURLOPT_RETURNTRANSFER => true 
          );
          curl_setopt_array ( $ch, $options );
          $result = curl_exec ( $ch );
          if ($data = json_decode ( $result, true )) {
            if (isset ( $data ["config"] ) && is_array ( $data ["config"] )) {
              if (isset ( $data ["config"] ["queryParser"] ) && is_array ( $data ["config"] ["queryParser"] )) {
                foreach ( $data ["config"] ["queryParser"] as $queryParserName => $queryParserConfig ) {
                  if (is_array ( $queryParserConfig ) && isset ( $queryParserConfig ["class"] ) && $queryParserConfig ["class"] == "mtas.solr.search.MtasSolrCQLQParserPlugin") {
                    if (isset ( $queryParserConfig ["name"] ) && is_string ( $queryParserConfig ["name"] )) {
                      $this->solr [$key] ["queryParserCql"] = $queryParserConfig ["name"];
                    }
                  } else if (is_array ( $queryParserConfig ) && isset ( $queryParserConfig ["class"] ) && $queryParserConfig ["class"] == "mtas.solr.search.MtasSolrJoinQParserPlugin") {
                    if (isset ( $queryParserConfig ["name"] ) && is_string ( $queryParserConfig ["name"] )) {
                      $this->solr [$key] ["queryParserJoin"] = $queryParserConfig ["name"];
                    }
                  }
                }
              }
            } else {
              die ( "No configuration available for '" . $solrConfiguration ["url"] . "' in configuration '" . $key . "'" );
            }
          } else {
            die ( "No configuration available for '" . $solrConfiguration ["url"] . "' in configuration '" . $key . "'" );
          }
        }
      }
    } else {
      die ( "No (valid) solr configuration" );
    }
    file_put_contents ( $filename, json_encode ( $this->solr ) );
  }
  /**
   * Process solr configuration
   *
   * @param array $item          
   * @param array $fieldTypes          
   * @param array $configuration          
   * @return unknown
   */
  private function _processSolrConfiguration($item, $fieldTypes, $configuration) {
    if (isset ( $item ["multiValued"] ) && is_bool ( $item ["multiValued"] ) && $item ["multiValued"] == true) {
      $configuration ["multiValued"] [] = $item ["name"];
    }
    if (isset ( $item ["indexed"] ) && is_bool ( $item ["indexed"] ) && $item ["indexed"] == true) {
      $configuration ["indexed"] [] = $item ["name"];
    }
    if (isset ( $item ["required"] ) && is_bool ( $item ["required"] ) && $item ["required"] == true) {
      $configuration ["required"] [] = $item ["name"];
    }
    if (isset ( $item ["stored"] ) && is_bool ( $item ["stored"] ) && $item ["stored"] == true) {
      $configuration ["stored"] [] = $item ["name"];
    }
    if (isset ( $item ["type"] ) && is_string ( $item ["type"] )) {
      if (in_array ( $item ["type"], $fieldTypes ["mtas"] )) {
        $configuration ["mtas"] [] = $item ["name"];
      }
      if (in_array ( $item ["type"], $fieldTypes ["text"] )) {
        $configuration ["typeText"] [] = $item ["name"];
      } else if (in_array ( $item ["type"], $fieldTypes ["boolean"] )) {
        $configuration ["typeBoolean"] [] = $item ["name"];
      } else if (in_array ( $item ["type"], $fieldTypes ["string"] )) {
        $configuration ["typeString"] [] = $item ["name"];
      } else if (in_array ( $item ["type"], $fieldTypes ["integer"] )) {
        $configuration ["typeInteger"] [] = $item ["name"];
      } else if (in_array ( $item ["type"], $fieldTypes ["long"] )) {
        $configuration ["typeLong"] [] = $item ["name"];
      } else if (in_array ( $item ["type"], $fieldTypes ["date"] )) {
        $configuration ["typeDate"] [] = $item ["name"];
      } else if (in_array ( $item ["type"], $fieldTypes ["binary"] )) {
        $configuration ["typeBinary"] [] = $item ["name"];
      }
    }
    return $configuration;
  }
  /**
   * Check field
   *
   * @param string $field          
   * @param array $configuration          
   * @param string $type          
   * @param boolean $indexed          
   * @param boolean $required          
   * @param boolean $stored          
   * @param boolean $multivalued          
   * @param boolean $mtas          
   */
  private function _checkField($field, $configuration, $type = null, $indexed = null, $required = null, $stored = null, $multivalued = null, $mtas = null) {
    if ($field && is_string ( $field )) {
      $checkFields = array ();
      if (in_array ( $field, $configuration ["fields"] )) {
        $checkFields [] = $field;
      } else {
        foreach ( $configuration ["dynamicFields"] as $dynamicField ) {
          $pattern = "/" . str_replace ( "\*", ".*", preg_quote ( $dynamicField ) ) . "/";
          if (preg_match ( $pattern, $field )) {
            $checkFields [] = $dynamicField;
          }
        }
      }
      foreach ( $checkFields as $checkField ) {
        if ($type !== null) {
          switch ($type) {
            case "string" :
              if (! in_array ( $checkField, $configuration ["typeString"] )) {
                continue 2;
              }
              break;
            case "boolean" :
              if (! in_array ( $checkField, $configuration ["typeBoolean"] )) {
                continue 2;
              }
              break;
            case "text" :
              if (! in_array ( $checkField, $configuration ["typeText"] )) {
                continue 2;
              }
              break;
            case "integer" :
              if (! in_array ( $checkField, $configuration ["typeInteger"] )) {
                continue 2;
              }
              break;
            case "date" :
              if (! in_array ( $checkField, $configuration ["typeDate"] )) {
                continue 2;
              }
              break;
            case "long" :
              if (! in_array ( $checkField, $configuration ["typeLong"] )) {
                continue 2;
              }
              break;
            default :
              return false;
          }
        }
        if ($indexed !== null) {
          if ($indexed !== in_array ( $checkField, $configuration ["indexed"] )) {
            continue;
          }
        }
        if ($required !== null) {
          if ($required !== in_array ( $checkField, $configuration ["required"] )) {
            continue;
          }
        }
        if ($stored !== null) {
          if ($stored !== in_array ( $checkField, $configuration ["stored"] )) {
            continue;
          }
        }
        if ($multivalued !== null) {
          if ($multivalued !== in_array ( $checkField, $configuration ["multivalued"] )) {
            continue;
          }
        }
        if ($mtas !== null) {
          if ($mtas !== in_array ( $checkField, $configuration ["mtas"] )) {
            continue;
          }
        }
        return true;
      }
      return false;
    } else {
      return false;
    }
  }
  /**
   * Sort items by levenshtein distance
   *
   * @param array $items          
   * @param array $list          
   */
  private function _sortLevenshtein($items, $list) {
    usort ( $items, function ($a, $b) use ($list) {
      $p1 = 1;
      $p2 = 1;
      $p3 = 1;
      $p4 = 10;
      $d_a = null;
      $d_b = null;
      $a = strtolower ( $a );
      $b = strtolower ( $b );
      foreach ( $list as $item ) {
        $item = strtolower ( $item );
        $lev_a = levenshtein ( $item, $a, $p1, $p2, $p3 ) + ((strpos ( $a, $item ) === false) ? $p4 : 0);
        $lev_b = levenshtein ( $item, $b, $p1, $p2, $p3 ) + ((strpos ( $b, $item ) === false) ? $p4 : 0);
        $d_a = ($d_a == null) ? $lev_a : min ( $d_a, $lev_a );
        $d_b = ($d_b == null) ? $lev_b : min ( $d_b, $lev_b );
      }
      return $d_a === $d_b ? 0 : ($d_a > $d_b ? 1 : - 1);
    } );
    return $items;
  }
  /**
   * Find Mtas examples
   *
   * @param string $configSuggestion          
   * @param array $configuration          
   * @param array $solrConfiguration          
   * @param array $hints          
   */
  private function _findMtasExamples($configSuggestion, $configuration, $solrConfiguration, $hints) {
    $field = null;
    $word = null;
    $lemma = null;
    $pos = null;
    $singlePosition = null;
    $multiplePosition = null;
    $setPosition = null;
    $intersecting = null;
    if ($configSuggestion != null && $this->_checkField ( $configSuggestion, $configuration, null, true, null, null, null, true )) {
      $field = $configSuggestion;
    } else {
      $field = null;
      $fields = $configuration ["fields"];
      if ($hints != null && is_array ( $hints ) && count ( $hints ) > 0) {
        $fields = $this->_sortLevenshtein ( $fields, $hints );
      }
      foreach ( $fields as $optionalField ) {
        if ($this->_checkField ( $optionalField, $configuration, null, true, null, null, null, true )) {
          $field = $optionalField;
          break;
        }
      }
    }
    if ($field) {
      // get prefixes
      $request = "wt=json&rows=0&q=*:*&mtas=true&mtas.prefix=true&mtas.prefix.0.field=" . urlencode ( $field );
      if (isset ( $solrConfiguration ["shards"] ) && count ( $solrConfiguration ["shards"] ) > 0) {
        $shards = implode ( ",", $solrConfiguration ["shards"] );
      } else {
        $shards = null;
      }
      $solr = new \Broker\Solr ( "[configuration]", isset ( $solrConfiguration ["url"] ) ? $solrConfiguration ["url"] : null, "select", $request, $shards, null );
      $response = $solr->getResponse ();
      if (is_object ( $response ) && isset ( $response->mtas ) && isset ( $response->mtas->prefix )) {
        $number = 10;
        $singlePosition = isset ( $response->mtas->prefix [0]->singlePosition ) ? $response->mtas->prefix [0]->singlePosition : array ();
        $multiplePosition = isset ( $response->mtas->prefix [0]->multiplePosition ) ? $response->mtas->prefix [0]->multiplePosition : array ();
        $setPosition = isset ( $response->mtas->prefix [0]->setPosition ) ? $response->mtas->prefix [0]->setPosition : array ();
        $intersecting = isset ( $response->mtas->prefix [0]->intersecting ) ? $response->mtas->prefix [0]->intersecting : array ();
        
        if (count ( $singlePosition ) > 0) {
          // get word
          if (isset ( $solrConfiguration ["exampleMtasPrefixWord"] ) && is_string ( $solrConfiguration ["exampleMtasPrefixWord"] )) {
            $hints = array (
                $solrConfiguration ["exampleMtasPrefixWord"] 
            );
          } else {
            $hints = array (
                "t",
                "t_lc",
                "word" 
            );
          }
          $singlePosition = $this->_sortLevenshtein ( $singlePosition, $hints );
          $word = array (
              $singlePosition [0] 
          );
          $word [] = $this->_findMtasExamplesTermvector ( $solrConfiguration, $field, $shards, $word [0], $number );
          // get lemma
          if (isset ( $solrConfiguration ["exampleMtasPrefixLemma"] ) && is_string ( $solrConfiguration ["exampleMtasPrefixLemma"] )) {
            $hints = array (
                $solrConfiguration ["exampleMtasPrefixLemma"] 
            );
          } else {
            $hints = array (
                "lemma" 
            );
          }
          $singlePosition = $this->_sortLevenshtein ( $singlePosition, $hints );
          $lemma = array (
              $singlePosition [0] 
          );
          $lemma [] = $this->_findMtasExamplesTermvector ( $solrConfiguration, $field, $shards, $lemma [0], $number );
          // get pos
          if (isset ( $solrConfiguration ["exampleMtasPrefixPos"] ) && is_string ( $solrConfiguration ["exampleMtasPrefixPos"] )) {
            $hints = array (
                $solrConfiguration ["exampleMtasPrefixPos"] 
            );
          } else {
            $hints = array (
                "pos" 
            );
          }
          $singlePosition = $this->_sortLevenshtein ( $singlePosition, $hints );
          $pos = array (
              $singlePosition [0] 
          );
          $pos [] = $this->_findMtasExamplesTermvector ( $solrConfiguration, $field, $shards, $pos [0], $number );
        }
        sort ( $singlePosition );
        sort ( $multiplePosition );
        sort ( $setPosition );
        sort ( $intersecting );
      }
    }
    return array (
        $field,
        $word,
        $lemma,
        $pos,
        $singlePosition,
        $multiplePosition,
        $setPosition,
        $intersecting 
    );
  }
  /**
   * Find Mtas examples termvector
   *
   * @param array $solrConfiguration          
   * @param string $field          
   * @param array $shards          
   * @param string $prefix          
   * @param int $number          
   * @return array
   */
  private function _findMtasExamplesTermvector($solrConfiguration, $field, $shards, $prefix, $number) {
    $values = array ();
    $request = "wt=json&rows=0&q=*:*&mtas=true&mtas.termvector=true";
    $request .= "&mtas.termvector.0.field=" . urlencode ( $field );
    $request .= "&mtas.termvector.0.prefix=" . urlencode ( $prefix );
    $request .= "&mtas.termvector.0.number=" . intval ( $number );
    $request .= "&mtas.termvector.0.regexp=[a-zA-Z0-9]*";
    $request .= "&mtas.termvector.0.type=sum";
    $request .= "&mtas.termvector.0.sort.type=sum";
    $request .= "&mtas.termvector.0.sort.direction=desc";
    $solr = new \Broker\Solr ( "[configuration]", isset ( $solrConfiguration ["url"] ) ? $solrConfiguration ["url"] : null, "select", $request, $shards, null );
    $tvresponse = $solr->getResponse ();
    if (is_object ( $tvresponse ) && isset ( $tvresponse->mtas ) && isset ( $tvresponse->mtas->termvector )) {
      $tmpList = $tvresponse->mtas->termvector [0]->list;
      foreach ( $tmpList as $tmpListItem ) {
        $values [] = $tmpListItem->key;
      }
    }
    return $values;
  }
  /**
   * Find example
   *
   * @param string $configSuggestion          
   * @param array $configuration          
   * @param array $solrConfiguration          
   * @param array $hints          
   * @param string $type          
   * @param boolean $indexed          
   * @param boolean $required          
   * @param boolean $stored          
   * @param boolean $multivalued          
   * @param boolean $mtas          
   */
  private function _findExample($configSuggestion, $configuration, $solrConfiguration, $hints = null, $type = null, $indexed = null, $required = null, $stored = null, $multivalued = null, $mtas = null) {
    if ($configSuggestion != null && $this->_checkField ( $configSuggestion, $configuration, $type, $indexed, $required, $stored, $multivalued, $mtas )) {
      $field = $configSuggestion;
    } else {
      $field = null;
      $fields = $configuration ["fields"];
      if ($hints != null && is_array ( $hints ) && count ( $hints ) > 0) {
        $fields = $this->_sortLevenshtein ( $fields, $hints );
      }
      foreach ( $fields as $optionalField ) {
        if ($this->_checkField ( $optionalField, $configuration, $type, $indexed, $required, $stored, $multivalued, $mtas )) {
          $field = $optionalField;
          break;
        }
      }
    }
    // get values
    if ($field != null) {
      $request = "wt=json&terms.fl=" . urlencode ( $field );
      if (isset ( $solrConfiguration ["shards"] ) && count ( $solrConfiguration ["shards"] ) > 0) {
        $shards = implode ( ",", $solrConfiguration ["shards"] );
        $request .= "&shards.qt=terms";
      } else {
        $shards = null;
      }
      $solr = new \Broker\Solr ( "[configuration]", isset ( $solrConfiguration ["url"] ) ? $solrConfiguration ["url"] : null, "terms", $request, $shards, null );
      $response = $solr->getResponse ();
      if (is_object ( $response ) && isset ( $response->terms ) && isset ( $response->terms->{$field} )) {
        if (is_object ( $response->terms->{$field} )) {
          $values = array_keys ( get_object_vars ( $response->terms->{$field} ) );
        } else if (is_array ( $response->terms->{$field} )) {
          $values = $response->terms->{$field};
          foreach ( $values as $key => $value ) {
            if ($key & 1) {
              unset ( $values [$key] );
            }
          }
          $values = array_values ( $values );
        } else {
          $values = null;
        }
      } else {
        $values = null;
      }
    } else {
      $values = null;
    }
    return array (
        $field,
        $values 
    );
  }
  /**
   * Validate, check existence directories
   */
  public static function validate() {
    \Broker\Configuration::validatePath ( "Layout-directory", SITE_LAYOUT_DIR, false, false );
    \Broker\Configuration::validatePath ( "Layout-directory Smarty", SITE_LAYOUT_SMARTY_DIR, false, false );
    \Broker\Configuration::validatePath ( "Layout-directory Smarty - Config", SITE_LAYOUT_SMARTY_CONFIG_DIR, false, false );
    \Broker\Configuration::validatePath ( "Layout-directory Smarty - Templates", SITE_LAYOUT_SMARTY_TEMPLATES_DIR, false, false );
    \Broker\Configuration::validatePath ( "Cache-directory", SITE_CACHE_DIR, true, true );
    \Broker\Configuration::validatePath ( "Cache-directory Configuration", SITE_CACHE_CONFIGURATION_DIR, true, true );
    \Broker\Configuration::validatePath ( "Cache-directory Database", SITE_CACHE_DATABASE_DIR, true, true );
    \Broker\Configuration::validatePath ( "Cache-directory Smarty", SITE_CACHE_SMARTY_DIR, true, true );
    \Broker\Configuration::validatePath ( "Cache-directory Smarty - Cache", SITE_CACHE_SMARTY_CACHE_DIR, true, true );
    \Broker\Configuration::validatePath ( "Cache-directory Smarty - Templates", SITE_CACHE_SMARTY_TEMPLATESC_DIR, true, true );
  }
  /**
   * Validate, check existence
   *
   * @param string $name          
   * @param string $path          
   * @param boolean $writeable          
   * @param boolean $autocreate          
   */
  private static function validatePath($name, $path, $writeable, $autocreate) {
    // check if exists, try to make
    if (! file_exists ( $path ) && (! $autocreate || ! @mkdir ( $path ))) {
      die ( $name . " : " . $path . " does not exist" );
    }
    // check access
    if (! is_readable ( $path )) {
      die ( $name . " : " . $path . " not readable" );
    } else if ($writeable && ! is_writeable ( $path )) {
      die ( $name . " : " . $path . " not writeable" );
    }
  }
}

?>