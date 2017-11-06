<?php
require_once 'sqlogin.php';
require_once 'utils.php';
ini_set('session.use_only_cookies', 1); //Deters session hijacking, if I understand.
session_start();

if(!isset($_SESSION['username'])) {
  relogin();
  die();
}

//otherwise:
html_header();
$username = $_SESSION['username'];
navbar($username, 'hits');
echo "<br />";

if(isset($_GET['timespan'])) {
  $timespan = $_GET['timespan'];
  if($timespan == 'month') {
    echo "<a href=\"hits.php\">greatest hits of all time</a><br />";
    echo "<a href=\"hits.php?timespan=year\">greatest hits of the past 365 days</a><br />";
    echo "<b>Greatest hits of the past 30 days</b><br />";
    $query = "SELECT journalref, title, authors, url, abstract, votenumerator, votedenominator FROM abstracts WHERE votedenominator != 0 AND TIMESTAMPDIFF(DAY, subtime, CURDATE()) < 30 ORDER BY votefraction DESC LIMIT 20";
  }
  if($timespan == 'year') {
    echo "<a href=\"hits.php\">greatest hits of all time</a><br />";
    echo "<b>Greatest hits of the past 365 days</b><br />";
    echo "<a href=\"hits.php?timespan=month\">greatest hits of the past 30 days</a><br />";
    $query = "SELECT journalref, title, authors, url, abstract, votenumerator, votedenominator FROM abstracts WHERE votedenominator != 0 AND TIMESTAMPDIFF(DAY, subtime, CURDATE()) < 365 ORDER BY votefraction DESC LIMIT 20";
  }
}
else {
  echo "<b>Greatest hits of all time</b><br />";
  echo "<a href=\"hits.php?timespan=year\">greatest hits of the past 365 days</a><br />";
  echo "<a href=\"hits.php?timespan=month\">greatest hits of the past 30 days</a><br />";
  $query = "SELECT journalref, title, authors, url, abstract, votenumerator, votedenominator FROM abstracts WHERE votedenominator != 0 ORDER BY votefraction DESC LIMIT 20";
 }
echo "<hr />";
$result = mysql_query($query);
if(!$result) {
  echo "mysql failure!<br />";
  die();
}
$rows = mysql_num_rows($result);
for($ab = 0; $ab < $rows; ++$ab) {
  $row = mysql_fetch_row($result);
  $journalref = unescape($row[0]);
  $title = unescape($row[1]);
  $authors = $row[2];
  $url = $row[3];
  $abstract = unescape($row[4]);
  $numerator = $row[5];
  $denominator = $row[6];
  echo "<div class=\"papertitle\">".$title."</div>";
  echo $authors."<br />";
  echo "<a href=\"".$url."\" target=\"journal\">".$journalref."</a><br />"; 
  echo $abstract."<br />";
  echo $numerator."&#47;".$denominator." votes<br />";
  echo "<hr />";
}

html_footer();
?>