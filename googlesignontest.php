<?php
    // some php stuff
?>
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
