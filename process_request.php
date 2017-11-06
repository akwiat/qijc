<?php
require_once 'sqlogin.php';
require_once 'utils.php';

if(!isset($_POST['firstname'])
   || !isset($_POST['lastname'])
   || !isset($_POST['username'])
   || !isset($_POST['email'])
   || !isset($_POST['password'])
   || !isset($_POST['password2'])) {
  relogin();
  die();
}

//otherwise
$firstname = $_POST['firstname'];
$lastname = $_POST['lastname'];
$username = $_POST['username'];
$email = $_POST['email'];
$password = $_POST['password'];
$password2 = $_POST['password2'];
html_header();
$ok = TRUE;
if(sanitize($username) != $username) { //check whether username is safe
  echo "Invalid special characters in username.<br />";
  echo "Please <a href=\"request.html\">try again</a><br />";
  $ok = FALSE;
}
if($ok){ //check whether username is in use
  $query = "SELECT passhash FROM users WHERE username='".$username."'";
  $result = mysql_query($query);
  if(!$result) {
    echo "Failure: ".mysql_error()." <br />";
    $ok = FALSE;
  }
  if($ok && mysql_num_rows($result)) {
    echo "Username ".$username." already in use.<br />";
    echo "Please <a href=\"request.html\">try again</a><br />";
    $ok = FALSE;
  }
}
if($ok) { //check whether firstname is safe
  if(sanitize($firstname) != $firstname) { //check whether username is safe
    echo "Invalid special characters in first name.<br />";
    echo "Please <a href=\"request.html\">try again</a><br />";
    $ok = FALSE;
  }
}
if($ok) { //check whether lastname is safe
  if(sanitize($lastname) != $lastname) { //check whether username is safe
    echo "Invalid special characters in last name.<br />";
    echo "Please <a href=\"request.html\">try again</a><br />";
    $ok = FALSE;
  }
}
if($ok) { //check whether email is safe
  if(sanitize($email) != $email) { //check whether username is safe
    echo "Invalid special characters in email address.<br />";
    echo "Please <a href=\"request.html\">try again</a><br />";
    $ok = FALSE;
  }
}
if($ok) { //check whether passwords match
  if($password != $password2) {
    echo "Passwords don't match. Typo suspected.<br />";
    echo "Please <a href=\"request.html\">try again</a><br />";
    $ok = FALSE;
  }
}    
if($ok) { //check whether password is safe
  if(sanitize($password) != $password) {
    echo "Invalid special characters in password.<br />";
    echo "Please <a href=\"request.html\">try again</a><br />";
    $ok = FALSE;
  }
}
if($ok) { //check whether password matches username
  if($password == $username) {
    echo "Do not use your username as your password.<br />";
    echo "Please <a href=\"request.html\">try again</a><br />";
    $ok = FALSE;
  }
}    
if($ok) { //check whether password is at least eight characters
  if(strlen($password) < 8) {
    echo "Password must be at least eight characters long.<br />";
    echo "Please <a href=\"request.html\">try again</a><br />";
    $ok = FALSE;
  }
}
if($ok) { //add to requests
  //some example I saw online also used strtr('+','.') to postprocess the output of
  //base64_encode. This doesn't seem to be necessary based on my testing, however.
  //$salt = "abc2+2CH/hh=";
  $salt = base64_encode(openssl_random_pseudo_bytes(32));
  $queryhead = "INSERT INTO requests(username, salt, passhash, firstname, lastname, email) VALUES";
  $querytail =  "('".$username."','".$salt."','".sha1($salt.$password)."','".$firstname."','".$lastname."','".$email."')";
  //echo($queryhead.$querytail);
  //echo "<br />";
  $result = mysql_query($queryhead.$querytail);
  if(!$result) echo "Failure: ".mysql_error()." <br />";
  else {
    //send an email to admin:
    $to = loadone('email', 'users', 'username', 'admin');
    $message = $firstname." ".$lastname." has requested an account.";
    $headers = "From: request@qijc.org"."\r\n";
    $headers .= 'Reply-To: request@qijc.org';
    mail($to, 'account request', $message, $headers);
    //inform the user:
    echo "Your request has been sent to the moderator.<br />";
    echo "You will be notified upon acceptance.<br />";
  }
}
html_footer();

?>

