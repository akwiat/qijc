<?php
require_once 'vendor/autoload.php';
ini_set('session.use_only_cookies', 1); //Deters session hijacking, if I understand.
session_start();
echo "starting";
var_dump($_POST);
var_dump($_SESSION);
if(isset($_POST['token']) && !isset($_SESSION['username'])) {
  $CLIENT_ID = "139584810606-lpct333ou0h73fm6fc1dqnj9fs9om2b6.apps.googleusercontent.com";
  $t = $_POST['token'];
  echo "received token";
  $client = new Google_Client(['client_id' => $CLIENT_ID]);  // Specify the CLIENT_ID of the app that accesses the backend
  $payload = $client->verifyIdToken($t);
  echo "reached ";
  if ($payload) {
    echo "payload: ";
    echo $payload;
    $userid = $payload['sub'];
    // If request specified a G Suite domain:
    //$domain = $payload['hd'];
  } else {
    echo "invalid";
  // Invalid ID token
}
}
?>
<html lang="en">
  <head>
    <meta name="google-signin-scope" content="profile email">
    <meta name="google-signin-client_id" content="139584810606-lpct333ou0h73fm6fc1dqnj9fs9om2b6.apps.googleusercontent.com">
    <script src="https://apis.google.com/js/platform.js" async defer></script>
  </head>
  <body>
    <div class="g-signin2" data-onsuccess="onSignIn" data-theme="dark"></div>
    <script>
      function onSignIn(googleUser) {
        // Useful data for your client-side scripts:
        var profile = googleUser.getBasicProfile();
        console.log("ID: " + profile.getId()); // Don't send this directly to your server!
        console.log('Full Name: ' + profile.getName());
        console.log('Given Name: ' + profile.getGivenName());
        console.log('Family Name: ' + profile.getFamilyName());
        console.log("Image URL: " + profile.getImageUrl());
        console.log("Email: " + profile.getEmail());

        // The ID token you need to pass to your backend:
        var id_token = googleUser.getAuthResponse().id_token;
        console.log("ID Token: " + id_token);
        var xhttp = new XMLHttpRequest();
        xhttp.open("POST", "", false);
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhttp.send("token="+id_token);
        console.log("sent")
      }
    </script>
  </body>
</html>
<!--
<div class="g-signin2" data-onsuccess="onSignIn"></div>
<script src="https://apis.google.com/js/platform.js?onload=init" async defer></script>
<script type="text/javascript">
  function init() {
    console.log("init reached")
    gapi.load('auth2', function() {
      /* Ready. Make a call to gapi.auth2.init or some other API */
    });
  }
  function onSignIn(googleUser) {
  var profile = googleUser.getBasicProfile();
  console.log('ID: ' + profile.getId()); // Do not send to your backend! Use an ID token instead.
  console.log('Name: ' + profile.getName());
  console.log('Image URL: ' + profile.getImageUrl());
  console.log('Email: ' + profile.getEmail()); // This is null if the 'email' scope is not present.
  }
</script>
-->
