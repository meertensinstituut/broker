<?php

/**
 * Broker expansion
 * @package Broker
 * @subpackage Expansion
 */
namespace BrokerExpansion;

/**
 * LexiconINTExpansion
 */
class DistanceExpansion implements \Broker\Expansion {
  private $value;
  private $configuration;
  private $brokerConfiguration;
  private $solrConfiguration;
  private $errors;
  private $method;
  private $prefix;
  private $field;
  private $minimum;  
  private $maximum;  
  private $number;  
  private $regexp;  
  private $parameter;
  private static $defaultMinimum = 0;
  private static $defaultMaximum = 1;
  private static $defaultNumber = 100;
  
  /**
   * @param string|array $value
   * @param unknown $expansionConfiguration
   */
  public function __construct($value, $expansionConfiguration, $brokerConfiguration, $solrConfiguration) {
    $this->errors = array();
    $this->value = $value;
    $this->configuration = $expansionConfiguration;   
    $this->brokerConfiguration = $brokerConfiguration;
    $this->solrConfiguration = $solrConfiguration;
    $this->minimum = self::$defaultMinimum;
    $this->maximum = self::$defaultMaximum;
    $this->number = self::$defaultNumber;
    $this->regexp = null;
    $this->parameter = null;
    if (isset ( $expansionConfiguration->parameters ) && is_object ( $expansionConfiguration->parameters )) {
      if (isset ( $expansionConfiguration->parameters->method ) && is_string ( $expansionConfiguration->parameters->method )) {
        $this->method = $expansionConfiguration->parameters->method;
      } else {
        $this->errors[] = "no (valid) method provided";
      }
      if (isset ( $expansionConfiguration->parameters->prefix ) && is_string ( $expansionConfiguration->parameters->prefix )) {
        $this->prefix = $expansionConfiguration->parameters->prefix;
      } else {
        $this->errors[] = "no (valid) prefix provided";
      }
      if (isset ( $expansionConfiguration->parameters->field ) && is_string ( $expansionConfiguration->parameters->field )) {
        $this->field = $expansionConfiguration->parameters->field;
      } else {
        $this->errors[] = "no (valid) field provided";
      }
      if (isset ( $expansionConfiguration->parameters->minimum ) && is_numeric ( $expansionConfiguration->parameters->minimum )) {
        $this->minimum = $expansionConfiguration->parameters->minimum;
      } else if (isset ( $expansionConfiguration->parameters->minimum )) {
        $this->errors[] = "invalid minimum provided";
      }
      if (isset ( $expansionConfiguration->parameters->maximum ) && is_numeric ( $expansionConfiguration->parameters->maximum )) {
        $this->maximum = $expansionConfiguration->parameters->maximum;
      } else if (isset ( $expansionConfiguration->parameters->maximum )) {
        $this->errors[] = "invalid maximum provided";
      }
      if (isset ( $expansionConfiguration->parameters->number ) && is_int ( $expansionConfiguration->parameters->number )) {
        $this->number = $expansionConfiguration->parameters->number;
      } else if (isset ( $expansionConfiguration->parameters->number )) {
        $this->errors[] = "invalid number provided";
      }
      if (isset ( $expansionConfiguration->parameters->regexp ) && is_string ( $expansionConfiguration->parameters->regexp )) {      
        $this->regexp = $expansionConfiguration->parameters->regexp;
      } else if (isset ( $expansionConfiguration->parameters->regexp )) {
        $this->errors[] = "invalid regexp provided";
      }
      if (isset ( $expansionConfiguration->parameters->parameter ) && is_object ( $expansionConfiguration->parameters->parameter )) {
        $this->parameter = $expansionConfiguration->parameters->parameter;        
      } else if (isset ( $expansionConfiguration->parameters->parameter )) {
        $this->errors[] = "invalid parameter provided";
      }
    }  
  }
  /**
   *
   * {@inheritDoc}
   *
   * @see \Broker\Expansion::cached()
   */
  public static function cached() {
    return true;
  }
  /**
   *
   * {@inheritDoc}
   *
   * @see \Broker\Expansion::description()
   */
  public static function description() {
    return "expansion returning words with distance within limits";
  }
  /**
   *
   * {@inheritDoc}
   *
   * @see \Broker\Expansion::parameters()
   */
  public static function parameters() {
    return array (
        "method" => "obligatory, mtas distance function (for example levenshtein or damerauLevenshtein)",
        "prefix" => "obligatory",
        "field" => "obligatory",
        "minimum" => "optional, default using " . self::$defaultMinimum ,
        "maximum" => "optional, default using " . self::$defaultMaximum ,
        "number" => "optional, default using " . self::$defaultNumber ,
        "regexp" => "optional, default none",
        "parameter" => "optional object with mtas distance parameters, default none"
    );    
  }
  /**
   *
   * {@inheritDoc}
   *
   * @see \Broker\Expansion::getValues()
   */
  public function getValues() {  
    $list = array();
    $request = new \stdClass ();
    $request->configuration = $this->solrConfiguration;
    $request->response = new \stdClass ();
    $request->response->mtas = new \stdClass ();
    $request->response->mtas->termvector = array();
    $request->response->mtas->termvector[0] = new \stdClass ();
    $request->response->mtas->termvector[0]->field =  $this->field;
    $request->response->mtas->termvector[0]->prefix = $this->prefix;  
    $request->response->mtas->termvector[0]->sort = new \stdClass ();
    $request->response->mtas->termvector[0]->sort->type = "sum";
    $request->response->mtas->termvector[0]->sort->direction = "desc";
    $request->response->mtas->termvector[0]->number = $this->number;
    if($this->regexp!=null) {
      $request->response->mtas->termvector[0]->regexp = $this->regexp;
    }
    $request->response->mtas->termvector[0]->distances = array();
    $request->response->mtas->termvector[0]->distances[0] = new \stdClass ();
    $request->response->mtas->termvector[0]->distances[0]->type = $this->method;
    $request->response->mtas->termvector[0]->distances[0]->base = $this->value;
    $request->response->mtas->termvector[0]->distances[0]->minimum = $this->minimum;
    $request->response->mtas->termvector[0]->distances[0]->maximum = $this->maximum;
    if($this->parameter!=null) {
      $request->response->mtas->termvector[0]->distances[0]->parameter = $this->parameter;
    }
    //parse
    $parser = new \Broker\Parser ($request, $this->brokerConfiguration, null, null, null, null);    
    if(count($parser->getWarnings ())>0) {
      foreach($parser->getWarnings() AS $warning) {
        $this->errors[] = $warning;
      }
    } else if(count($parser->getErrors ())>0) {
      foreach($parser->getErrors() AS $error) {
        $this->errors[] = $error;
      }
    } else {
      try {
          $solr = new \Broker\Solr ( $parser->getConfiguration (), $parser->getUrl (), "select", $parser->getRequest (), $parser->getRequestAddition(), $parser->getShards () != null ? implode ( ",", $parser->getShards () ) : null, $parser->getCache () );
          $solrResponse = $solr->getResponse ();
          if ($solrResponse && is_object ( $solrResponse )) {
            if(isset($solrResponse->mtas) && is_object($solrResponse->mtas)) {
              if(isset($solrResponse->mtas->termvector) && is_array($solrResponse->mtas->termvector)) {
                if(count($solrResponse->mtas->termvector)>0 && is_object($solrResponse->mtas->termvector[0])) {
                  if(isset($solrResponse->mtas->termvector[0]->list) && is_array($solrResponse->mtas->termvector[0]->list)) {
                    foreach($solrResponse->mtas->termvector[0]->list AS $item) {
                      if(is_object($item) && isset($item->key) && is_string($item->key)) {
                        $list[] = $item->key;
                      }
                    }
                  }
                }                
              }
            }            
          }                             
      } catch ( \Broker\SolrException $se ) {
          $this->errors[] = $se->getMessage ();
      } catch ( \Exception $e ) {
          $this->errors[] = $solr->getResponse ();
      }    
    }     
    return $list;
  }
  /**
   *
   * {@inheritDoc}
   *
   * @see \Broker\Expansion::getErrors()
   */
  public function getErrors() {
    if (count ( $this->errors ) > 0) {
      return $this->errors;
    } else {
      return null;
    }
  }
  
}

?>