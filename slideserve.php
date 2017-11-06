<?php
require_once 'sqlogin.php';
require_once 'utils.php';
ini_set('session.use_only_cookies', 1); //Deters session hijacking, if I understand.
session_start();

if(!isset($_SESSION['username'])) {
  login();
  die();
}

if(!isset($_GET['s'])) {
  html_header();
  echo "Unable to retrieve filename.";
  html_footer();
  die();
}

$filename=$_GET['s'];

$file = "/var/www/slides/".$filename;

if(!file_exists($file)) {
  html_header();
  echo "Unable to retrieve file.";
  html_footer();
  die();
}

header("Content-type: application/octet-stream");
header('Content-Disposition: attachment; filename="'.$filename.'"');
header("Content-Length: ". filesize($file));
readfile($file);

