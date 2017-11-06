<?php //utils.php

function sanitize($string) {
  $var = mysql_real_escape_string($string);
  //I don't think the following really matter
  //$var = stripslashes($var);
  //$var = htmlentities($var);
  //$var = strip_tags($var);
  return $var;
}

function unescape($string) {
  $apostrophe = '\\'.'\'';
  $newline = '\\'."\n";
  $nullchar ='\\'."\x00";
  $carriage='\\'."\r";
  $quotemark='\\'."\"";
  $uniescape='\\'."\x1a";;
  $string = str_replace($apostrophe, '\'',$string);
  $string = str_replace($newline, '\n',$string);
  $string = str_replace($nullchar, '\x00',$string);
  $string = str_replace($carriage, '\n', $string);
  $string = str_replace($quotemark, '\"', $string);
  $string = str_replace($uniescape, '\x1a', $string);
  return $string;
}

function loadone($sought, $table, $field, $value) {
  $query = "SELECT $sought FROM $table WHERE $field='".$value."'";
  //echo "query = ".$query."<br />"; //for debugging
  $result = mysql_query($query);
  if(!$result) {
    echo "Failure: ".mysql_error()." <br />";
        return NULL;
  }
  if(mysql_num_rows($result) != 1) return NULL;
  $row = mysql_fetch_row($result);
  return $row[0];
}

function html_header() {
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
</head>
<body>
_HEND;
}

function html_footer() {
  //echo "</center>";
  echo "</body>";
  echo "</html>";
}

function get_string_between($string, $start, $end){
  $string = " ".$string;
  $ini = strpos($string,$start);
  if ($ini == 0) return "";
  $ini += strlen($start);
  $len = strpos($string,$end,$ini) - $ini;
  return substr($string,$ini,$len);
}

function get_all_between($string, $start, $end) {
  $returnval = array();
  $counter = 0;
  $string = " ".$string;
  $ini = strpos($string,$start);
  while($ini != 0) {
    $ini += strlen($start);
    $len = strpos($string,$end,$ini) - $ini;
    $returnval[$counter] = substr($string,$ini,$len);
    ++$counter;
    $string = " ".substr($string, $ini);
    $ini = strpos($string,$start);
  }
  return $returnval;
}

function navbar($username, $section) {
  echo "<div id=\"navbar\">";
  echo "Welcome, ".$username."! ";
  echo "[<a href=\"logout.php\">logout</a>] ";
  if($section == "announcements") echo "&lt;<a href=\"index.php\">announcements</a>&gt; ";
  else echo "[<a href=\"index.php\">announcements</a>] "; 
  if($section == "submit") echo "&lt;<a href=\"submit.php\">submit</a>&gt; ";
  else echo "[<a href=\"submit.php\">submit</a>] ";
  if($username == 'admin') { //admin
    if($section == "admin") echo "&lt;<a href=\"admin.php\">administer</a>";
    else echo "[<a href=\"admin.php\">administer</a>";
    $query = "SELECT username FROM requests";
    $result = mysql_query($query);
    $num_requests = mysql_num_rows($result);
    if($num_requests) echo " (".$num_requests." requests pending)";
    if($section == "admin") echo "&gt;";
    else echo "] ";
  }
  if($section == "vote") echo "&lt;<a href=\"vote.php\">vote</a>&gt; ";
  else echo "[<a href=\"vote.php\">vote</a>] ";
  if($section == "hits") echo "&lt;<a href=\"hits.php\">greatest&nbsp;hits</a>&gt; ";
  else echo "[<a href=\"hits.php\">greatest&nbsp;hits</a>] ";
  if($section == "history") echo "&lt;<a href=\"history.php\">history</a>&gt; ";
  else echo "[<a href=\"history.php\">history</a>] ";
  if($section == "myaccount") echo "&lt;<a href=\"myaccount.php\">my&nbsp;account</a>&gt; ";
  else echo "[<a href=\"myaccount.php\">my&nbsp;account</a>] ";
  if($section == "messaging")   echo "&lt;<a href=\"message.php\">messaging</a>&gt;";
  else echo "[<a href=\"message.php\">messaging</a>]";
  echo "<br />";
  echo "</div>";
}

function showcomments($id) {
  $comquery = "SELECT comment, commenter FROM comments WHERE id='".$id."'";
  $comresult = mysql_query($comquery);
  $num_comments = mysql_num_rows($comresult);
  if($num_comments > 0) {
    echo "<span class=\"comment\">";
    for($com = 0; $com < $num_comments; ++$com) {
      $comrow = mysql_fetch_row($comresult);
      $thiscomment = unescape($comrow[0]);
      $thiscommenter = $comrow[1];
      echo "<br /><i>".$thiscomment."<br />--".nickname($thiscommenter)."</i><br />";
    }
    echo "</span>";
  }
}

function current_abstracts() {
  $query = "SELECT * FROM abstracts WHERE week IS NULL ORDER BY subtime DESC";
  $result = mysql_query($query);
  $num_abstracts = mysql_num_rows($result);
  for($ab = 0; $ab < $num_abstracts; ++$ab) {
    $row = mysql_fetch_row($result);
    $submitter = $row[0];
    $title = unescape($row[1]);
    $abstract = unescape($row[2]);
    $authors = $row[3];
    $journalref = unescape($row[4]);
    $url = $row[9];
    $id = $row[10];
    $volunteer = $row[12];
    echo nickname($submitter);
    if($volunteer != NULL) echo "&rarr;".nickname($volunteer);
    echo "<br /><div class=\"papertitle\">";
    echo $title."<br /></div>";
    echo $authors."<br />";
    echo "<a href=\"".$url."\" target=\"journal\">".$journalref."</a><br />"; 
    echo $abstract."<br />";
    showcomments($id);
    echo "<table><tr>";
    if($submitter == $_SESSION['username']) {
      echo "<td><form action=\"submit.php\" method=\"post\">";
      echo "<input type=\"hidden\" name=\"remove_id\" value=\"".$id."\" />";
      echo "<button type=\"submit\">unsubmit</button>";
      echo "</form></td>";
    }
    if($volunteer == NULL) {
      echo "<td><form action=\"submit.php\" method=\"post\">";
      echo "<input type=\"hidden\" name=\"volunteer_id\" value=\"".$id."\" />";
      echo "<button type=\"submit\">volunteer</button></form></td>";
    }
    echo "<td><form action=\"submit.php\" method=\"post\">";
    echo "<input type=\"hidden\" name=\"comment_id\" value=\"".$id."\" />";
    echo "<button type=\"submit\">comment</button></form></td>";
    echo "</tr></table>";
    echo "<hr />";
  }
}

function relogin() {
echo <<<_END
<!DOCTYPE html>
<html>
<head>
<title>Quantum Information Journal Club</title>
<meta http-equiv="refresh" content="0; url=index.php" />
</head>
<body>
<center>
<a href="index.php">main page</a>
</center>
</body>
</html>
_END;
}

function login() {
echo <<<_LOGEND
<!DOCTYPE html>
<html>
<head>
<title>Quantum Information Journal Club</title>
<link rel="stylesheet" type="text/css" href="qijc.css" />
</head>
<body bgcolor="black">
<center>
<div id="login">
<form method="post" action="index.php">
<table style="width:350px">
<tr>
<td colspan="2"><small>QIJC Version: 0.48</small></td>
</tr>
<tr>
<td><b>Username</b>:</td>
<td><input type="text" name="username" /></td>
</tr>
<tr>
<td><b>Password</b>:</td>
<td><input type="password" name="password" /></td>
</tr>
<tr>
<td></td><td><button type="submit">login</button></td>
</tr>
</form>
<tr><td><b>New users</b>: </td>
<td><a href="request.html">request account</a>
</td>
</tr>
</table>
</div>
</center>
</body>
</html>
_LOGEND;
}

function submitbar() {
  echo "<form method=\"post\" action=\"submit.php\">";
  echo "<input type=\"text\" name=\"paperid\" size=\"85\"/><br />";
  echo "<input type=\"radio\" name=\"vol\" value=\"no\" checked>I'm seeking volunteers to discuss this paper.<br />";
  echo "<input type=\"radio\" name=\"vol\" value=\"yes\">I'm volunteering to discuss this paper.<br /><br />";
  echo "Automatically handles arXiv, Phys. Rev., and Nature journals. ";
  echo "Otherwise, <a href=\"manual.php\">submit manually</a>.<br /><br />";
  echo "Comments (optional):<br />";
  echo "<textarea name=\"comment\" cols=\"88\" rows=\"8\">";
  echo "</textarea><br />";
  echo "<button type=\"submit\">submit url</button><br />";
  echo "</form>";
  echo "<hr />";
}

function thisweek() {
  $week_query = mysql_query("SELECT number FROM weeks ORDER BY number DESC LIMIT 1");
  $week_row = mysql_fetch_row($week_query);
  $lastweek = $week_row[0];
  if($lastweek == NULL) return 1;
  return $lastweek + 1;
}

function nickname($username) {
  $nick_query = mysql_query("SELECT nickname FROM users WHERE username='".$username."'");
  $nick_row = mysql_fetch_row($nick_query);
  return $nick_row[0];
}

function randomPassword() {
  //lowercase l and capital I are hard to distinguish from number 1
  //number 0 is hard to distinguish from capital O and lowercase o
  $alphabet = "abcdefghijkmnpqrstuwxyzABCDEFGHJKLMNPQRSTUWXYZ23456789";
  $pass = "";
  $alphaLength = strlen($alphabet) - 1;
  for ($i = 0; $i < 8; $i++) {
    $n = rand(0, $alphaLength);
    $pass .= $alphabet[$n];
  }
  return $pass;
}

?>
