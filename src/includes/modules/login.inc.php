<?php
/**
 * Module login
 */
if (! $authentication->accessBasedOnLogin ()) {
  if (isset ( $_POST ) && isset ( $_POST ["login"] ) && isset ( $_POST ["password"] )) {
    if ($authentication->validateLogin ( $_POST ["login"], $_POST ["password"] )) {
      if (isset ( $_GET ["operation"] ) && is_string ( $_GET ["operation"] ) && preg_match ( "/^[a-z]+$/i", $_GET ["operation"] )) {
        if ($_GET ["operation"] != "login") {
          if (isset ( $_GET ["suboperation"] ) && is_string ( $_GET ["suboperation"] ) && preg_match ( "/^[a-z]+$/i", $_GET ["suboperation"] )) {
            header ( "refresh:2;url=" . $configuration->url ( $_GET ["operation"], $_GET ["suboperation"] ) );
          } else {
            header ( "refresh:2;url=" . $configuration->url ( $_GET ["operation"], null ) );
          }
        } else if (isset ( $_GET ["suboperation"] ) && is_string ( $_GET ["suboperation"] ) && preg_match ( "/^[a-z]+$/i", $_GET ["suboperation"] )) {
          header ( "refresh:2;url=" . $configuration->url ( $_GET ["suboperation"], null ) );
        } else {
          header ( "refresh:2;url=" . $configuration->url ( null, null ) );
        }
      } else {
        header ( "refresh:2;url=" . $configuration->url ( null, null ) );
      }
    } else {
      header ( "refresh:2;url=" . $configuration->url ( "login", null ) );
    }
  }
} else {
  header ( "Location: " . $configuration->url ( null, null ) );
}
?>