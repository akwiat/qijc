<?php
require_once 'utils.php';
ini_set('session.use_only_cookies', 1); //Deters session hijacking, if I understand.
session_start();

$_SESSION = array();
if(session_id() != "" || isset($_COOKIE[session_name()])) 
  setcookie(session_name(), '', time() - 2592000, '/');
session_destroy();

html_header();
echo "You are now logged out.<br />";
echo "<a href=\"index.php\">log in here</a><br />";
html_footer();

?>

