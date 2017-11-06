<?php
require_once 'sqlogin.php';
require_once 'utils.php';
ini_set('session.use_only_cookies', 1); //Deters session hijacking, if I understand.
session_start();

//Not logged in, so shunt to login screen
if(!isset($_SESSION['username'])) {
  login();
  die();
}

$username = $_SESSION['username'];

$done = FALSE;

//weeks from ancient era
if(isset($_GET['aw'])) {
//we need to add some extra CSS to the header
echo <<<_HEND
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<title>Quantum Information Journal Club</title>
<link rel="stylesheet" type="text/css" href="qijc.css" />
<script type="text/x-mathjax-config">
  MathJax.Hub.Config({tex2jax: {inlineMath: [['$','$']]}});
</script>
<script type="text/javascript" src="https://cdn.mathjax.org/mathjax/latest/MathJax.js?config=TeX-AMS-MML_HTMLorMML">
</script>
<style type="text/css">
body {font-family:Arial,Helvetica,sans-serif;}
.title {font-weight:bold}
.author {color:green}
.comments {font-style:italic}
.highlight {color:red}
</style>
</head>
<body>
_HEND;
  navbar($username, 'history');
  echo "<br />";
  $aweek = mysql_real_escape_string($_GET['aw']);
  echo $aweek."<br />";
  $aweek_html = file_get_contents("/var/www/archive/presentation-".$aweek.".html");
  $spitout = get_string_between($aweek_html,"</center>", "</body>");
  echo $spitout;
  html_footer();
  exit();
}

html_header();
navbar($username, 'history');
echo "<br />";

//slides
if(isset($_GET['m'])) {
  if($_GET['m'] == 'o') { //ancient era
    $slides_html = file_get_contents("/var/www/slides/slides.html");
    echo $slides_html;
    html_footer();
    exit();
  }
  if($_GET['m'] == 'n') { //modern era
    $result = mysql_query("SELECT number, presenter, topic, filename, date FROM weeks ORDER BY number");
    $num_pres = mysql_num_rows($result);
    for($pres = 0; $pres < $num_pres; ++$pres) {
      $p_row = mysql_fetch_row($result);
      $p_week = $p_row[0];
      $p_user = $p_row[1];
      $p_topic = $p_row[2];
      $p_file = $p_row[3];
      if($p_user == 'none5028') $p_nick = "None";
      else $p_nick = nickname($p_user);
      $p_date = $p_row[4];
      $p_datestring = date('m-d-Y',strtotime($p_date));
      echo "<table><tr>";
      echo "<td>Week:</td><td>".$p_week." (".$p_datestring.")</td></tr>";
      echo "<tr><td>Presenter:</td><td>".$p_nick."</td></tr>";
      echo "<tr><td>Topic:</td><td>".$p_topic."</td></tr>";
      if($p_file) echo "<tr><td>Slides:</td><td><a href=\"slideserve.php?s=".$p_file."\">pdf</a></td></tr>";
      echo "</table><hr />";
    }
    html_footer();
    exit();
  }
}

//year from ancient era
if(isset($_GET['year'])) {
  $year = mysql_real_escape_string($_GET['year']);
  if($year == 2008) echo file_get_contents("/var/www/archive/2008.html");
  if($year == 2009) echo file_get_contents("/var/www/archive/2009.html");
  if($year == 2010) echo file_get_contents("/var/www/archive/2010.html");
  if($year == 2011) echo file_get_contents("/var/www/archive/2011.html");
  if($year == 2012) echo file_get_contents("/var/www/archive/2012.html");
  if($year == 2013) echo file_get_contents("/var/www/archive/2013.html");
  if($year == 2014) echo file_get_contents("/var/www/archive/2014.html");
  html_footer();
  exit();
}


//week from modern era
if(isset($_GET['week'])) {
  $week = mysql_real_escape_string($_GET['week']);
  //handle uploaded file:
  if(isset($_FILES['slidefile'])) {
    if ($_FILES['slidefile']['error'] !== UPLOAD_ERR_OK) echo "<b>Upload failed with error ".$_FILES['slidefile']['error']."</b><br />";
    else {
      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $mime = finfo_file($finfo, $_FILES['slidefile']['tmp_name']);
      if($mime != 'application/pdf' && $mime != 'application/x-pdf') echo "<span class=\"errmsg\">Pdf files only, please.</span><br />";
      else {
        $safename = mysql_real_escape_string(basename($_FILES['slidefile']['name'])); 
        $target_file = '/var/www/slides/'.$safename;
        if(file_exists($target_file)) echo "<span class=\"errmsg\">Sorry, file already exists.</span><br />";
        else {
          if($_FILES['slidefile']['size'] > 7000000) echo "<span class=\"errmsg\">Sorry, uploads can be at most 7MB.</span><br />";
          else {
            $success = move_uploaded_file($_FILES['slidefile']['tmp_name'], $target_file);
            if($success) {
              mysql_query("UPDATE weeks SET filename='".$safename."' WHERE number=".$week);
              echo "File uploaded.<br />";
            }
            else echo "<span class=\"errmsg\">File upload failure.</span><br />";
          }
        }
      }
    }
  }
  //normal behavior:
  $result = mysql_query("SELECT * FROM abstracts WHERE week='".$week."' ORDER BY votefraction DESC");
  $rows = mysql_num_rows($result);
  for($ab = 0; $ab < $rows; ++$ab) {
    $row = mysql_fetch_row($result);
    $submitter = $row[0];
    $title = unescape($row[1]);
    $abstract = unescape($row[2]);
    $authors = $row[3];
    $journalref = unescape($row[4]);
    //$subtime = $row[5];
    $votenumerator = $row[6];
    $votedenominator = $row[7];
    $url = $row[9];
    $id = $row[10];
    $volunteer = $row[12];
    if($ab == 0) {
      $weekdate = loadone('date', 'weeks', 'number', $week);
      $date = date('m-d-Y',strtotime($weekdate));
      echo "<b>Week ".$week.":</b> votes from ".$date."<br />";
      $action_query = "SELECT presenter, topic, filename FROM weeks WHERE number=".$week;
      $action_result = mysql_query($action_query);
      $presenters = mysql_num_rows($action_result);
      if($presenters > 0) {
        $pres_row = mysql_fetch_row($action_result);
        $pres_username = $pres_row[0];
        $topic = $pres_row[1];
        $slidefile = $pres_row[2];
        if($pres_username == 'none5028') $nick = "None";
        else $nick = nickname($pres_username);
        echo "<b>Presenter:</b> ".$nick."<br />";
        echo "<b>Topic:</b> ".$topic."<br />";
        if($slidefile) echo "<a href=\"slideserve.php?s=".$slidefile."\">slides</a><br />";
        else {
          echo "<form action=\"history.php?week=".$_GET['week']."\" method=\"post\" enctype=\"multipart/form-data\">";
          echo "Upload slides:";
          echo "<input type=\"file\" name=\"slidefile\" id=\"slidefile\">";
          echo "<input type=\"submit\" value=\"Upload Pdf\" name=\"submit\">";
          echo "</form>";
	} 
      }
      echo "<br /><hr />";
    }
    echo nickname($submitter);
    if($volunteer != NULL) echo "&rarr;".nickname($volunteer);
    echo "<br />";
    echo "<div class=\"papertitle\">".$title."</div>";
    echo $authors."<br />";
    echo "<a href=\"".$url."\" target=\"journal\">".$journalref."</a><br />"; 
    echo $abstract."<br />";
    showcomments($id);
    //this if-statement should always evaluate true, but just in case...
    if($votedenominator > 0) echo $votenumerator."&#47;".$votedenominator." votes<br />";
    echo "<hr />";
  }
  html_footer();
  exit();
}

//default behavior
$result = mysql_query("SELECT number, date FROM weeks");
$rows = mysql_num_rows($result);
echo "<b>Modern era:</b><br />";
echo "<a href=\"history.php?m=n\">Presentations</a><br />";
for($w = 1; $w <= $rows; ++$w) {
  $row = mysql_fetch_row($result);
  $week = $row[0];
  $weekdate = $row[1];
  $weekstring = date('m-d-Y',strtotime($weekdate));
  echo "<a href=\"history.php?week=".$week."\">week ".$week."(".$weekstring.")</a><br />"; 
}
echo "<br /><b>Ancient era:</b><br />";
echo "<a href=\"history.php?m=o\">Presentations</a><br />";
echo "<a href=\"history.php?year=2014\">2014</a><br />";
echo "<a href=\"history.php?year=2013\">2013</a><br />";
echo "<a href=\"history.php?year=2012\">2012</a><br />";
echo "<a href=\"history.php?year=2011\">2011</a><br />";
echo "<a href=\"history.php?year=2010\">2010</a><br />";
echo "<a href=\"history.php?year=2009\">2009</a><br />";
echo "<a href=\"history.php?year=2008\">2008</a><br />";
html_footer();

?>