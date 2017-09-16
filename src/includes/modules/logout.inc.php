<?php
if ($authentication->accessBasedOnLogin ()) {
  $authentication->logout ();
  header ( "refresh:2;url=" . $configuration->url (null, null) );
} else {
  header ( "Location: " . $configuration->url ("login", null) );
}
?>