<?php

/**
 * Broker
 * @package Broker
 */
namespace Broker;

/**
 * Handle authentication based on IP, key or login/password
 */
class Authentication {
  /**
   * Access based on IP
   *
   * @var boolean
   */
  private $accessBasedOnIP;
  /**
   * Access based on key
   *
   * @var boolean
   */
  private $accessBasedOnKey;
  /**
   * Access based on login
   *
   * @var boolean
   */
  private $accessBasedOnLogin;
  /**
   * Access with admin privileges
   *
   * @var boolean
   */
  private $accessWithAdminPrivileges;
  /**
   * Configuration
   *
   * @var unknown
   */
  private $configuration;
  /**
   * Constructor
   *
   * @param unknown $configuration          
   */
  public function __construct($configuration) {
    if ($configuration && is_array ( $configuration )) {
      $this->configuration = $configuration;
    } else {
      $this->configuration = array ();
    }
    if (isset ( $_SERVER ["HTTP_X_BROKER_KEY"] )) {
      $this->accessBasedOnKey = $this->validateKey ( $_SERVER ["HTTP_X_BROKER_KEY"] );
    } else {
      $this->accessBasedOnKey = false;
    }
    if (! $this->accessBasedOnKey) {
      // session
      session_set_save_handler ( new \Broker\Session ( SITE_CACHE_DATABASE_DIR ) );
      session_name ( "broker" );
      session_start ();
      // checks
      $this->accessBasedOnIP = $this->validateIP ( $_SERVER ["REMOTE_ADDR"] );
      $this->accessBasedOnLogin = isset ( $_SESSION ["login"] ) && $_SESSION ["login"] ? true : false;
      $this->accessWithAdminPrivileges = isset ( $_SESSION ["admin"] ) && $_SESSION ["admin"] ? true : false;
    }
  }
  /**
   * Reset
   */
  public function reset() {
  }
  /**
   * Check for access
   *
   * @return boolean
   */
  public function access() {
    return $this->accessBasedOnIP || $this->accessBasedOnKey || $this->accessBasedOnLogin;
  }
  /**
   * Check for access based on IP
   *
   * @return boolean
   */
  public function accessBasedOnIP() {
    return $this->accessBasedOnIP;
  }
  /**
   * Check for access based on key
   *
   * @return boolean
   */
  public function accessBasedOnKey() {
    return $this->accessBasedOnKey;
  }
  /**
   * Check for access based on login
   *
   * @return boolean
   */
  public function accessBasedOnLogin() {
    return $this->accessBasedOnLogin;
  }
  /**
   * Check for access with admin privileges
   *
   * @return boolean
   */
  public function accessWithAdminPrivileges() {
    return $this->accessBasedOnLogin && $this->accessWithAdminPrivileges;
  }
  /**
   * Get ip
   *
   * @return string
   */
  public function getIP() {
    return $_SERVER ["REMOTE_ADDR"];
  }
  /**
   * Get login
   *
   * @return string|boolean
   */
  public function getLogin() {
    if ($this->accessBasedOnLogin) {
      return isset ( $_SESSION ["login"] ) ? $_SESSION ["login"] : null;
    } else {
      return false;
    }
  }
  /**
   * Get name
   *
   * @return string|boolean
   */
  public function getName() {
    if ($this->accessBasedOnLogin) {
      return isset ( $_SESSION ["name"] ) ? $_SESSION ["name"] : null;
    } else {
      return false;
    }
  }
  /**
   * Logout
   */
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
  /**
   * Login
   *
   * @param string $login          
   * @param string $name          
   * @param boolean $admin          
   */
  private function login($login, $name, $admin) {
    if (! $login || ! is_string ( $login )) {
      $this->logout ();
    } else {
      $this->accessBasedOnLogin = true;
      $_SESSION ["login"] = $login;
      if ($admin && is_bool ( $admin )) {
        $_SESSION ["admin"] = $login;
      } else {
        $_SESSION ["admin"] = null;
      }
      $_SESSION ["name"] = $name && is_string ( $name ) ? $name : $login;
    }
  }
  /**
   * Validate IP
   *
   * @param string $ip          
   * @return boolean
   */
  private function validateIP($ip) {
    if (isset ( $this->configuration ["ip"] ) && is_array ( $this->configuration ["ip"] )) {
      $list = $this->configuration ["ip"];
      $ip = preg_replace ( "/\b0+(?=\d)/", "", $ip );
      if (filter_var ( $ip, FILTER_VALIDATE_IP )) {
        if (filter_var ( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 )) {
          $ip = ip2long ( $ip );
          foreach ( $list as $item ) {
            if (isset ( $item ["ip"] ) && is_string ( $item ["ip"] )) {
              if (preg_match ( "/\//", $item ["ip"] )) {
                list ( $filterIP, $filterRange ) = explode ( "/", $item ["ip"], 2 );
              } else {
                $filterIP = $item ["ip"];
                $filterRange = "";
              }
              $filterIP = preg_replace ( "/\b0+(?=\d)/", "", $filterIP );
              if (filter_var ( $filterIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 )) {
                $filterIP = ip2long ( $filterIP );
                if ($filterRange != null && $filterRange != "" && (intval ( $filterRange ) > 0)) {
                  $filterRange = intval ( $filterRange );
                  $min = (($filterIP) & ((- 1 << (32 - $filterRange))));
                  $max = ((($min)) + pow ( 2, (32 - $filterRange) ) - 1);
                  if ($ip >= $min && $ip <= $max) {
                    return true;
                  }
                  // die ( long2ip ( $filterIP ) . "/" . $filterRange . " : " . $min . " - " . $max );
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
  /**
   * Validate key
   *
   * @param string $key          
   * @return bool
   */
  private function validateKey($key) {
    if ($key && is_string ( $key )) {
      if (isset ( $this->configuration ["key"] ) && is_array ( $this->configuration ["key"] )) {
        foreach ( $this->configuration ["key"] as $item ) {
          if (isset ( $item ["key"] ) && is_string ( $item ["key"] ) && ($item ["key"] == $key)) {
            return true;
          }
        }
      }
    }
    return false;
  }
  /**
   * Validate login
   *
   * @param string $login          
   * @param string $password          
   * @return boolean
   */
  public function validateLogin($login, $password) {
    if ($login && is_string ( $login ) && $password && is_string ( $password )) {
      if (isset ( $this->configuration ["login"] ) && is_array ( $this->configuration ["login"] )) {
        $list = $this->configuration ["login"];
        $login = mb_strtolower ( $login );
        foreach ( $list as $item ) {
          if (is_array ( $item ) && isset ( $item ["login"] ) && is_string ( $item ["login"] ) && ($login == mb_strtolower ( $item ["login"] ))) {
            if (isset ( $item ["password"] ) && is_string ( $item ["password"] ) && hash_equals ( $item ["password"], @crypt ( $password, $item ["password"] ) )) {
              $this->accessBasedOnLogin = true;
              if (isset ( $item ["admin"] ) && $item ["admin"]) {
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