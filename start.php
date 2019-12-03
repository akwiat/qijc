<?php
require_once 'vendor/autoload.php';
ini_set('session.use_only_cookies', 1); //Deters session hijacking, if I understand.
session_start();

if(isset($_POST['token']) && !isset($_SESSION['username'])) {
  $CLIENT_ID = "139584810606-lpct333ou0h73fm6fc1dqnj9fs9om2b6.apps.googleusercontent.com";
  $t = $_POST['token']
  echo "received token";
  $client = new Google_Client(['client_id' => $CLIENT_ID]);  // Specify the CLIENT_ID of the app that accesses the backend
  $payload = $client->verifyIdToken($id_token);
  echo "reached ";
  if ($payload) {
    echo "payload: ";
    echo $payload;
    $userid = $payload['sub'];
    // If request specified a G Suite domain:
    //$domain = $payload['hd'];
  } else {
    echo "invalid"
  // Invalid ID token
}
}
?>
