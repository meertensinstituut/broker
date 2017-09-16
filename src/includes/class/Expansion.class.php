<?php

namespace Broker;

interface Expansion { 
  public function __construct($value, $configuration);
  public static function cached() : bool;
  public static function description() : string;
  public static function parameters() : array;
  public function getValues(): array;  
  public function getErrors();
}