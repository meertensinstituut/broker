<?php 

namespace BrokerExpansion;

class ExplodeExpansion implements \Broker\Expansion {
  
  private $value;
  private $split = ",";
  private $trim = true;
  
  public function __construct($value, $configuration) {
    $this->value = $value;
    if($configuration && is_object($configuration)) {
      if(isset($configuration->parameters) && is_object($configuration->parameters)) {
        if(isset($configuration->parameters->split) && is_string($configuration->parameters->split)) {
          $this->split = $configuration->parameters->split;
        }
        if(isset($configuration->parameters->trim)) {
          if($configuration->parameters->trim) {
            $this->trim = true;
          } else {
            $this->trim = false;
          }
        }
      }
    }
  }
  
  public static function cached() : bool {
    return false;
  }
  
  public static function description() : string {
    return "split value";
  }

  public static function parameters() : array {
    return array(
      "split" => "optional, default using \",\"", 
      "trim"=>"optional, default true"       
    );
  }
  
  public function getValues(): array {
    $list = explode($this->split, $this->value);
    if($this->trim) {
      $list = array_map("trim",$list);
    } 
    return $list;
  }
  
  public function getErrors() {
    return null;
  }
}

?>