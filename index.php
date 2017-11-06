<?php
require_once 'sqlogin.php';
require_once 'utils.php';
ini_set('session.use_only_cookies', 1); //Deters session hijacking, if I understand.
session_start();

//Not logged in:
if(!isset($_SESSION['username']) && (!isset($_POST['username']) || !isset($_POST['password']))) login();

if(isset($_POST['presenter'])) {
  $presenter = mysql_real_escape_string($_POST['presenter']);
  $topic = mysql_real_escape_string($_POST['topic']);
  $q1 = "INSERT INTO actions(type, week, user, topic) VALUES";
  $q2 = "('present','".thisweek()."','".$presenter."','".$topic."')";
  mysql_query($q1.$q2);
  $q3 = "UPDATE announcement SET presenter='".$presenter."'";
  mysql_query($q3);
  //presenting is worth 20 points
  mysql_query("UPDATE users SET points = points + 20 WHERE username='".$presenter."'");      
  mysql_query("UPDATE users SET diligence = diligence + 20 WHERE username='".$presenter."'");
}

if(isset($_POST['submit']) && isset($_POST['newtext'])) {
  $newtext = mysql_real_escape_string($_POST['newtext']);
  mysql_query("UPDATE announcement SET text='".$newtext."'");
}

if(isset($_POST['update'])) {
  $username = $_SESSION['username'];
  html_header();
  navbar($username, 'announcements');
  echo "<form action=\"index.php\" method=\"post\">";
  echo "<table><tr><td><textarea name=\"newtext\" cols=\"83\" rows=\"20\">";
  $a_result = mysql_query("SELECT text FROM announcement");
  $a_row = mysql_fetch_row($a_result);
  if($a_row[0]) echo $a_row[0];
  echo "</textarea></td></tr><tr><td><input type=\"submit\" value=\"submit\" name=\"submit\"/></td></tr></table></form>";
  html_footer();
}

function announce() {
  $p_result = mysql_query("SELECT presenter FROM announcement");
  $p_row = mysql_fetch_row($p_result);
  $presenter = $p_row[0];
  $t_result = mysql_query("SELECT topic FROM actions WHERE week=".thisweek()." AND type='present'");
  $t_row = mysql_fetch_row($t_result);
  $topic = $t_row[0]; 
  if($presenter) {
    echo "<table><tr><td><b>Next presenter:</b></td><td>".nickname($presenter)."</td></tr>";
    echo "<tr><td><b>Topic:</b></td><td>".$topic."</td></tr></table>";
  }
  else {
    echo "<form action=\"index.php\" method=\"post\">";
    echo "<table><tr><td><b>Next presenter:</b></td><td>";
    $u_result = mysql_query("SELECT username, nickname FROM users WHERE retired=FALSE ORDER BY diligence");
    $num_users = mysql_num_rows($u_result);
    echo "<select name=\"presenter\">";
    for($u = 0; $u < $num_users; ++$u) {
      $namerow = mysql_fetch_row($u_result);
      $cur_username = $namerow[0];
      $cur_nickname = $namerow[1];
      if($cur_username != 'admin') echo "<option value=\"".$cur_username."\">".$cur_nickname."</option>";
    }
    echo "</select></td></tr>";
    echo "<tr><td><b>Topic:</b></td><td><input type=\"text\" name=\"topic\" size=\"80\"/></td></tr>";
    echo "<tr><td colspan=\"2\"><input type=\"submit\" name=\"submit\" value=\"submit\"/></td></tr></table></form>";
  }
  echo "<hr />";
  echo "<table><tr><td><b>Announcements:</b></td></tr><tr><td>";
  $a_result = mysql_query("SELECT text FROM announcement");
  $a_row = mysql_fetch_row($a_result);
  echo nl2br($a_row[0]);
  echo "</td></tr><tr><td>";
  echo "<br /><form action=\"index.php\" method=\"post\"><input type=\"submit\" name=\"update\" value=\"update\"/></form></td></tr></table>";
  echo "<hr />";
  echo "<table class = \"admin\"><tr><td colspan=\"4\"><b>Directory/Scoreboard:</b></td></tr>";
  $score_result = mysql_query("SELECT nickname, email, diligence, points, username FROM users WHERE retired=FALSE ORDER BY diligence DESC");
  $num_users = mysql_num_rows($score_result);
  echo "<tr><td>User</td><td>Email</td><td>HotPoints<a href=\"#\" class=\"tooltip\">(?)<span><img src=\"hotpoints.png\" /></span></a></td><td>Total Points<a href=\"#\" class=\"tooltip\">(?)<span><img src=\"totalpoints.png\" /></span></a></td></tr>";
  for($u = 0; $u < $num_users; ++$u) {
    $score_row = mysql_fetch_row($score_result);
    $score_nickname = $score_row[0];
    $score_email = $score_row[1];
    $score_diligence = $score_row[2];
    $score_total = $score_row[3];
    $score_username = $score_row[4];
    if($score_username != 'admin') echo"<tr><td class=\"admin\">".$score_nickname."</td><td class=\"admin\">".$score_email."</td><td class=\"admin\">".number_format($score_diligence,2)."</td><td class=\"admin\">".$score_total."</td></tr>";
  }
  echo "</table><hr />";
}

//Default mainpage (display announcements):
if(isset($_SESSION['username']) && !isset($_POST['paperid']) && !isset($_POST['remove_id']) && !isset($_POST['comment_id']) && !isset($_POST['update'])) {
  $username = $_SESSION['username'];
  html_header();
  navbar($username, 'announcements');
  echo "<br />";
  announce();
  html_footer();
}

//Process login attempt:
if(isset($_POST['username']) && isset($_POST['password']) && !isset($_SESSION['username'])) {
  html_header();
  $username = $_POST['username'];
  $password = $_POST['password'];
  $uquery = sanitize($username);
  $passhash = loadone('passhash','users','username',$uquery);
  $salt = loadone('salt','users','username',$uquery); 
  if($passhash == sha1($salt.$password)) {
    $_SESSION['username'] = $username;
    navbar($username, 'announcements');
    echo "<br />";
    announce();
  }
  else {
    echo "Incorrect username or password.<br />";
    echo "<a href=\"index.php\">Try again</a> or contact the system administrator.<br />";
  }
  html_footer();
}

?>

