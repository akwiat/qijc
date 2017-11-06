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
html_header();
navbar($username, 'myaccount');
echo "<br />";

if(isset($_POST['newemail'])) {
  $newemail = mysql_real_escape_string($_POST['newemail']);
  mysql_query("UPDATE users SET email='".$newemail."' WHERE username='".$username."'");
  echo "<b>Email updated.</b>";
}

if(isset($_POST['newpassword1'])) {
  $newpassword1 = $_POST['newpassword1'];
  $newpassword2 = $_POST['newpassword2'];
  $oldpassword = $_POST['current_pw'];
  if($newpassword1 != $newpassword2) echo "<b>The new passwords did not match. Typo suspected.</b><br />";
  else {
    if(strlen($newpassword1) < 8) echo "<b>Passwords must be at least eight characters.</b><br />";
    else {
      if($newpassword1 != mysql_real_escape_string($newpassword1)) echo "<b>Password doesn't play nicely with mysql.</b><br />";
      else {
        $salt = loadone('salt', 'users', 'username',$_SESSION['username']);
        $passhash = loadone('passhash', 'users', 'username',$_SESSION['username']);
        if(sha1($salt.$oldpassword) != $passhash) echo "<b>Incorrect current password.</b><br />";
        else {
          $newpasshash = sha1($salt.$newpassword1);
          $query = "UPDATE users SET passhash='".$newpasshash."' WHERE username='".$_SESSION['username']."'";
          mysql_query($query);
          echo "<b>Password updated.</b><br />";
        }
      }
    }
  }
}

$result = mysql_query("SELECT firstname, lastname, email FROM users WHERE username='".$username."'");
$row = mysql_fetch_row($result);
$firstname = $row[0];
$lastname = $row[1];
$email = $row[2];
echo "<table>";
echo "<tr><td>username:</td><td>".$username."</td></tr>";
echo "<tr><td>name:</td><td>".$firstname." ".$lastname."</td></tr>";
echo "<tr><td>email:</td>";
echo "<td><form action=\"myaccount.php\" method=\"post\">";
echo "<input type=\"text\" name=\"newemail\" size=\"30\" value=\"".$email."\">";
echo "<input type=\"submit\" value=\"update email\">";
echo "</form></td></tr></table>";
echo "<hr />";
echo "<form action=\"myaccount.php\" method=\"post\"><table>";
echo "<tr><td>current password:</td>";
echo "<td><input type=\"password\" name=\"current_pw\" /></td></tr>"; 
echo "<tr><td>new password:</td>";
echo "<td><input type=\"password\" name=\"newpassword1\" /></td></tr>";
echo "<tr><td>confirm new password:</td>";
echo "<td><input type=\"password\" name=\"newpassword2\" />";
echo "<input type=\"submit\" value=\"update password\"></td></tr></table></form>";
echo "<hr />";
echo "<b>My recent submissions:</b><br />";

$result = mysql_query("SELECT * FROM abstracts WHERE submitter='".$username."' ORDER BY subtime DESC LIMIT 20");
$rows = mysql_num_rows($result);
for($ab = 0; $ab < $rows; $ab++) {
  $row = mysql_fetch_row($result);
  $title = unescape($row[1]);
  $abstract = unescape($row[2]);
  $authors = $row[3];
  $journalref = unescape($row[4]);
  $subtime = $row[5];
  $votenumerator = $row[6];
  $votedenominator = $row[7];
  $url = $row[9];
  $date = date('m-d-Y',strtotime($subtime));
  echo "<span class=\"papertitle\">".$title."</span> (".$date.") <br />";
  echo $authors."<br />";
  echo "<a href=\"".$url."\" target=\"journal\">".$journalref."</a><br />"; 
  echo $abstract."<br />";
  if($votedenominator > 0) echo $votenumerator."&#47;".$votedenominator." votes<br />";
  echo "<hr />";
}

html_footer();
?>