<?php
require_once 'sqlogin.php';
//This deters session hijacking, if I understand correctly:
ini_set('session.use_only_cookies', 1);
session_start();

//Not logged in, so shunt to login screen
if(!isset($_SESSION['username'])) die();

$username = $_SESSION['username'];

if(isset($_GET['id'])) $id = mysql_real_escape_string($_GET['id']);
else die();

if(isset($_GET['n'])) $numerator = mysql_real_escape_string($_GET['n']);
else die();

if(isset($_GET['d'])) $denominator = mysql_real_escape_string($_GET['d']);
else die();

$fraction = $numerator/$denominator;
$query = "UPDATE abstracts SET votenumerator='".$numerator."' WHERE id='".$id."'";
mysql_query($query);
$query = "UPDATE abstracts SET votedenominator='".$denominator."' WHERE id='".$id."'";
mysql_query($query);
$query = "UPDATE abstracts SET votefraction='".$fraction."' WHERE id='".$id."'";
mysql_query($query);

echo "id=".$id."n=".$numerator."d=".$denominator;

?>