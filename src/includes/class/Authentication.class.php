<?php

namespace Broker;

class Authentication {
  private $accessBasedOnIP;
  private $accessBasedOnKey;
  private $accessBasedOnLogin;
  private $accessWithAdminPrivileges;
  private $configuration;
  public function __construct($configuration) {
    if ($configuration && is_array ( $configuration )) {
      $this->configuration = $configuration;
    } else {
      $this->configuration = array ();
    }
    if(isset($_SERVER["HTTP_X_BROKER_KEY"])) {
      $this->accessBasedOnKey = $this->validateKey ( $_SERVER["HTTP_X_BROKER_KEY"] );  
    } else {
      $this->accessBasedOnKey = false;
    }
    if(!$this->accessBasedOnKey) {
      // session
      session_set_save_handler ( new \Broker\Session ( SITE_CACHE_DATABASE_DIR ) );
      session_name ( "broker" );
      session_start ();
      //checks
      $this->accessBasedOnIP = $this->validateIP ( $_SERVER ["REMOTE_ADDR"] );
      $this->accessBasedOnLogin = isset ( $_SESSION ["login"] ) && $_SESSION ["login"] ? true : false;
      $this->accessWithAdminPrivileges = isset ( $_SESSION ["admin"] ) && $_SESSION ["admin"] ? true : false;
    }        
  }
  public function reset() {
    
  }
  public function access() {
    return $this->accessBasedOnIP || $this->accessBasedOnKey || $this->accessBasedOnLogin;
  }
  public function accessBasedOnIP() {
    return $this->accessBasedOnIP;
  }
  public function accessBasedOnKey() {
    return $this->accessBasedOnKey;
  }
  public function accessBasedOnLogin() {
    return $this->accessBasedOnLogin;
  }
  public function accessWithAdminPrivileges() {
    return $this->accessBasedOnLogin && $this->accessWithAdminPrivileges;
  }
  public function getIP() {
    return $_SERVER ["REMOTE_ADDR"];
  }
  public function getLogin() {
    if ($this->accessBasedOnLogin) {
      return isset ( $_SESSION ["login"] ) ? $_SESSION ["login"] : null;
    } else {
      return false;
    }
  }
  public function getName() {
    if ($this->accessBasedOnLogin) {
      return isset ( $_SESSION ["name"] ) ? $_SESSION ["name"] : null;
    } else {
      return false;
    }
  }
  public function logout() {
    $this->accessBasedOnLogin = false;
    if (isset ( $_SESSION ["login"] ) || isset ( $_SESSION ["name"] )) {
      $_SESSION ["login"] = null;
      $_SESSION ["admin"] = null;
      $_SESSION ["name"] = null;
      unset ( $_SESSION ["login"] );
      unset ( $_SESSION ["admin"] );
      unset ( $_SESSION ["name"] );
    }
  }
  private function login($login, $name, $admin) {
    if (! $login || ! is_string ( $login )) {
      $this->logout();
    } else {
      $this->accessBasedOnLogin = true;
      $_SESSION ["login"] = $login;
      if($admin && is_bool($admin)) {
        $_SESSION ["admin"] = $login;
      } else {
        $_SESSION ["admin"] = null;
      }
      $_SESSION ["name"] = $name && is_string ( $name ) ? $name : $login;
    }
  }
  private function validateIP($ip) {
    if (isset ( $this->configuration ["ip"] ) && is_array ( $this->configuration ["ip"] )) {
      $list = $this->configuration ["ip"];
      $ip = preg_replace ( "/\b0+(?=\d)/", "", $ip );
      if (filter_var ( $ip, FILTER_VALIDATE_IP )) {
        if (filter_var ( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 )) {
          $ip = ip2long ( $ip );
          foreach ( $list as $item ) {
            if(isset($item["ip"]) && is_string($item["ip"])) {
              if (preg_match ( "/\//", $item["ip"] )) {
                list ( $filterIP, $filterRange ) = explode ( "/", $item["ip"], 2 );
              } else {
                $filterIP = $item["ip"];
                $filterRange = "";
              }
              $filterIP = preg_replace ( "/\b0+(?=\d)/", "", $filterIP );
              if (filter_var ( $filterIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 )) {
                $filterIP = ip2long ( $filterIP );
                if ($filterRange != null && $filterRange != "" && (intval ( $filterRange ) > 0)) {
                  $filterRange = intval ( $filterRange );
                  $min =  ( ($filterIP) & ((- 1 << (32 - $filterRange))) );
                  $max =  ( ( ( $min )) + pow ( 2, (32 - $filterRange) ) - 1 );
                  if($ip>=$min && $ip<=$max) {
                    return true;
                  }
                  //die ( long2ip ( $filterIP ) . "/" . $filterRange . " : " . $min . " - " . $max );
                } else if ($ip == $filterIP) {
                  return true;
                }
              }
            }
          }
        } else if (filter_var ( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 )) {
          foreach ( $list as $item ) {
            if (preg_match ( "/\//", $item )) {
              list ( $filterIP, $filterRange ) = explode ( "/", $item, 2 );
            } else {
              $filterIP = $item;
              $filterRange = "";
            }
            $filterIP = preg_replace ( "/\b0+(?=\d)/", "", $filterIP );
            if (filter_var ( $filterIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 )) {
              if ($filterRange != null && $filterRange != "" && (intval ( $filterRange ) > 0)) {
                $filterRange = intval ( $filterRange );
                // todo: not supported for now
              } else if (inet_pton ( $ip ) == inet_pton ( $filterIP )) {
                return true;
              }
            }
          }
        }
      }
    }
    return false;
  }
  private function validateKey(string $key): bool {
    if($key && is_string($key)) {
      if (isset ( $this->configuration ["key"] ) && is_array ( $this->configuration ["key"] )) {
        foreach($this->configuration ["key"] AS $item) {
          if(isset($item["key"]) && is_string($item["key"]) && ($item["key"]==$key)) {
            return true; 
          }
        }
      }
    }
    return false;
  }
  public function validateLogin($login, $password): bool {
    if ($login && is_string ( $login ) && $password && is_string ( $password )) {
      if (isset ( $this->configuration ["login"] ) && is_array ( $this->configuration ["login"] )) {
        $list = $this->configuration ["login"];
        $login = mb_strtolower ( $login );
        foreach ( $list as $item ) {
          if (is_array ( $item ) && isset ( $item ["login"] ) && is_string ( $item ["login"] ) && ($login == mb_strtolower ( $item ["login"] ))) {
            if (isset ( $item ["password"] ) && is_string ( $item ["password"] ) && hash_equals ( $item ["password"], @crypt ( $password, $item ["password"] ) )) {
              $this->accessBasedOnLogin = true;
              if(isset($item["admin"]) && $item["admin"]) {
                $this->login ( $item ["login"], $item ["name"], true );
              } else {
                $this->login ( $item ["login"], $item ["name"], false );
              }
              return true;
            }
          }
        }
      }
    }
    return false;
  }
}