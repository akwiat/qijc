<?php
require_once 'sqlogin.php';
require_once 'utils.php';
//This deters session hijacking, if I understand correctly:
ini_set('session.use_only_cookies', 1);
session_start();

//Not logged in, so shunt to login screen
if(!isset($_SESSION['username'])) {
  login();
  die();
}

$username = $_SESSION['username'];

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
<script>
function ajaxRequest() {
  try {
    var request = new XMLHttpRequest();
  }
  catch(e1) {
    try {
      request = new ActiveXObject("Msxml12.XMLHTTP");
    }
    catch(e2) {
      try {
        request = new ActiveXObject("Microsoft.XMLHTTP");
      }
      catch(e3) {
        request = false;
      }
    }
  }
  return request;
}

function votehandle (form) {
  numerator = form.numerator.value;
  denominator = form.denominator.value;
  id = form.name;
  div = document.getElementById(form.name);
  div.innerHTML = numerator + "/" + denominator +" votes";
  other = document.getElementsByClassName("voteform");
  for(i=0; i < other.length; i++) {
    other[i].denominator.defaultValue=denominator;
  }
  url = "votehandler.php?id=" + id + "&n=" + numerator + "&d=" + denominator;
  request = new ajaxRequest();
  request.open("GET", url, true);
  request.onreadystatechange = function() {
    if(this.readyState == 4) {
      if(this.status == 200) {
        //if(this.responseText != null) alert(this.responseText);
        if(this.responseText == null) alert("Ajax error: No data received.");
      }
      else alert("Ajax error: " + this.statusText);
    }
  }
  request.send(null);
}
</script>
</head>
<body>
_HEND;

navbar($username, 'vote');
echo "<br />";

//Close out the week:
if(isset($_POST['closout'])) {
  $query = "UPDATE abstracts SET week='".thisweek()."' WHERE week IS NULL AND votedenominator != 0";
  $result = mysql_query($query);
  mysql_query("UPDATE announcement SET presenter=NULL");
  //decay the diligence by our magic decay factor, which is 0.75
  mysql_query("UPDATE users SET diligence = diligence * 0.75");
  $w_result = mysql_query("SELECT user, topic FROM actions WHERE type='present' AND week=".thisweek());
  if(mysql_num_rows($w_result) >= 1) {
    $w_row = mysql_fetch_row($w_result);
    $w_presenter = $w_row[0];
    $w_topic = $w_row[1];
  }
  else {
    $w_presenter = 'none5028'; //5028 is a magic string so as not to coincide with a username
    $w_topic = 'none';
  }
  mysql_query("INSERT INTO weeks(presenter, topic) VALUES ('".$w_presenter."','".$w_topic."')");
}

//Display current abstracts and voting forms
$query = "SELECT * FROM abstracts WHERE week IS NULL ORDER BY volunteer IS NULL, subtime";
$result = mysql_query($query);
$num_abstracts = mysql_num_rows($result);
if($num_abstracts == 0) echo "No abstracts to display. Please <a href=\"submit.php\">submit</a>!<br />";
for($ab = 0; $ab < $num_abstracts; ++$ab) {
  $row = mysql_fetch_row($result);
  $submitter = $row[0];
  $title = unescape($row[1]);
  $abstract = unescape($row[2]);
  $authors = $row[3];
  $journalref = unescape($row[4]);
  $votenumerator = $row[6];
  $votedenominator = $row[7];
  $votefraction = $row[8];
  $url = $row[9];
  $id = $row[10];
  $volunteer = $row[12];
  echo nickname($submitter);
  if($volunteer != NULL) echo "&rarr;".nickname($volunteer);
  echo "<div class=\"papertitle\">".$title."</div>";
  echo $authors."<br />";
  echo "<a href=\"".$url."\"  target=\"journal\">".$journalref."</a><br />"; 
  echo $abstract."<br />";
  showcomments($id);
  if($votedenominator > 0) echo $votenumerator."&#47;".$votedenominator." votes<br />";
  else {
    echo "<div id=\"".$id."\">";
    echo "<form name=\"".$id."\" class=\"voteform\" action=\"\">";
    echo "<input type=\"text\" name=\"numerator\" size=\"2\" />";
    echo "&#47;"; //forward slash
    echo "<input type=\"text\" name=\"denominator\" size=\"2\" />";
    echo "<input type=\"button\" name=\"vote\" value=\"vote\" onClick=\"votehandle(this.form)\"></button>";
    echo "</form></div>";
  }
  echo "<hr />";
}

if($num_abstracts != 0) {
echo <<<_END
<form action="vote.php" method="post">
<input type="hidden" name="closout" />
<button type="submit">finish week</button>
</form>
_END;
}

html_footer();
?>