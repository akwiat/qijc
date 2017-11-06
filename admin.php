<?php
require_once 'sqlogin.php';
require_once 'utils.php';
ini_set('session.use_only_cookies', 1); //Deters session hijacking, if I understand.
session_start();

if(!isset($_SESSION['username'])) {
  login();
  die();
}

if(isset($_SESSION['username'])) {
  if($_SESSION['username'] != 'admin') {
    html_header();
    navbar($_SESSION['username'], 'admin');
    echo "<br />";
    echo "You must be logged in as \"admin\" to access this page.<br />";
    html_footer();
    die();
  }
}

if (isset($_POST['reject'])) {
  $req_username=$_POST['req_username'];
  $query = "DELETE FROM requests WHERE username='$req_username'";
  mysql_query($query);
}

if(isset($_POST['accept'])) {
  $req_username=$_POST['req_username'];
  $q1 = "SELECT * FROM requests WHERE username='$req_username'";
  $result = mysql_query($q1);
  $row = mysql_fetch_row($result);
  $req_username = $row[0];
  $req_salt = $row[1];
  $req_passhash = $row[2];
  $req_firstname = $row[3];
  $req_lastname = $row[4];
  $req_email = $row[5];
  $q2a = "INSERT INTO users(username, salt, passhash, firstname, lastname, email) VALUES";
  $q2b = "('".$req_username."','".$req_salt."','".$req_passhash."','".$req_firstname."','".$req_lastname."','".$req_email."')";
  mysql_query($q2a.$q2b);
  $collisions = mysql_query("SELECT username, lastname FROM users WHERE firstname='".$req_firstname."'");
  $num_collisions = mysql_num_rows($collisions);
  if($num_collisions > 1) { //recompute nicknames
    for($usr = 0; $usr < $num_collisions; ++$usr) {
      $usrrow = mysql_fetch_row($collisions);
      $usrname = $usrrow[0];
      $usrlast = $usrrow[1];
      $usr_last_initial = $usrlast[0];
      $newnick = $req_firstname." ".$usr_last_initial;
      mysql_query("UPDATE users SET nickname='".$newnick."' WHERE username = '".$usrname."'");
    }
  }
  else mysql_query("UPDATE users SET nickname=firstname WHERE username = '".$req_username."'");
  $q3 ="DELETE FROM requests WHERE username='$req_username'";
  mysql_query($q3);
  $subject = "Quantum Information Journal Club";
  $body = "Dear ".$req_firstname.",\n\n";
  $body .= "Your membership request has been accepted. You may now log in and submit abstracts.\n\n";
  $adminame = loadone('nickname', 'users', 'username', 'admin');
  $body .= "Best regards,\n\n".$adminame; 
  $from = loadone('email', 'users', 'username', 'admin');
  $headers = "From: admin@qijc.org"."\r\n";
  $headers .= 'Reply-To: '.$from;
  //echo "mail: ".$req_email." ".$subject." ".$body." ".$headers."<br />";
  $success = mail($req_email, $subject, $body, $headers);
  if(!$success) "<b>Failed to send message. Try again or contact your system administrator.</b><br />";
  //else echo "Message sent.<br />";
}

$query = "SELECT * FROM requests";
$result = mysql_query($query);
$rows = mysql_num_rows($result);

html_header();
navbar('admin', 'admin');
echo "<br />";
echo "Welcome to the administration page.<br /><br />";
echo $rows." requests pending.<br /><br />";

for($i = 0; $i < $rows; ++$i) {
  $row = mysql_fetch_row($result);
  $req_username = $row[0];
  $req_firstname = $row[3];
  $req_lastname = $row[4];
  $req_email = $row[5];
  echo "<form action=\"admin.php\" method =\"post\">";
  echo "<table class=\"admin\">";
  echo "<tr><td class = \"admin\">username:</td>";
  echo "<td class=\"admin\">".$req_username."</td></tr>";
  echo "<td class=\"admin\">firstname: </td>";
  echo "<td class=\"admin\">".$req_firstname."</td></tr>";
  echo "<tr><td class=\"admin\">lastname: </td>";
  echo "<td class=\"admin\">".$req_lastname."</td></tr>";
  echo "<tr><td class=\"admin\">email: </td>";
  echo "<td class=\"admin\">".$req_email."</td></tr>";
  echo "</table>";
  echo "<input type=\"hidden\" name=\"req_username\" value=\"".$req_username."\" />";
  echo "<input type=\"submit\" name=\"accept\" value=\"accept\"/>";
  echo "<input type=\"submit\" name=\"reject\" value=\"reject\"/>";
  echo "</form><br />";
}

if(isset($_POST['yes'])) {
  $remname = mysql_real_escape_string($_POST['remname']);
  $query = "DELETE FROM users WHERE username='".$remname."'";
  mysql_query($query);
}

if(isset($_POST['removeuser'])) {
  $remname = $_POST['removeuser'];
  if($remname == 'admin') echo "<b>admin cannot be removed.</b><br />";
  else {
    echo "<b>Are you sure you would like to remove user ".$remname."?</b><br />";
    echo "<form action=\"admin.php\" method=\"post\"><table><tr>";
    echo "<td><input type=\"submit\" name=\"yes\" value=\"yes\"/>";
    echo "<input type=\"hidden\" name=\"remname\" value=\"".$remname."\"/>";
    echo "<td><input type=\"submit\" name=\"no\" value = \"no\"/></td>";
    echo "</tr></table></form>";
  }
}

if(isset($_POST['retireuser'])) {
  $retname = $_POST['retireuser'];
  if($retname == 'admin') echo "<b>admin cannot be retired.</b><br />";
  else {
    $query = "UPDATE users SET retired=TRUE WHERE username='".$retname."'";
    mysql_query($query);
  }
}

if(isset($_POST['unretireuser'])) {
  $unretname = $_POST['unretireuser'];
  if($unretname == 'admin') echo "<b>admin cannot be unretired.</b><br />";
  else {
    $query = "UPDATE users SET retired=FALSE WHERE username='".$unretname."'";
    mysql_query($query);
  }
}

if (isset($_POST['resetpwd'])) {
  $resname = $_POST['resetpwd'];
  $tmppwd = randomPassword();
  $salt = loadone('salt','users','username',$resname); 
  $passhash = sha1($salt.$tmppwd);
  $query = "UPDATE users SET passhash='".$passhash."' WHERE username='".$resname."'";
  mysql_query($query);
  $subject = "Temporary Login";
  $firstname = loadone('firstname','users','username',$resname);
  $body = "Dear ".$firstname.",\n\n";
  $body .= "Your password has been reset. Your temporary password is:\n\n";
  $body .= $tmppwd."\n\n";
  $body .= "This has been transmitted to you unencrypted.\n";
  $body .= "Please immediately set a new password using the \"my account\" tab.\n\n";
  $adminame = loadone('nickname', 'users', 'username', 'admin');
  $body .= "Best regards,\n\n".$adminame; 
  $from = loadone('email', 'users', 'username', 'admin');
  $headers = "From: admin@qijc.org"."\r\n";
  $headers .= 'Reply-To: '.$from;
  $to = loadone('email','users','username',$resname);
  $success = mail($to, $subject, $body, $headers);
  if(!$success) "<b>Failed to send message. Try again or contact your system administrator.</b><br />";
  echo "Reset pwd for ".$resname." to ".$tmppwd."<br />";
  echo "Notification has been sent to ".$to."<br />";
}

$result = mysql_query("SELECT username, firstname, lastname, email FROM users");
$numusers = mysql_num_rows($result);
echo $numusers." active users:<br />";

echo "<table class=\"admin\">";
echo "<tr><td class=\"admin\">username</td><td class=\"admin\">first name</td>";
echo "<td class=\"admin\">last name</td><td class=\"admin\">email</td><td class=\"admin\"></td><td class=\"admin\"></td></tr>";
for($user = 0; $user < $numusers; ++$user) {
  $row = mysql_fetch_row($result);
  $r_username = $row[0];
  $r_firstname = $row[1];
  $r_lastname = $row[2];
  $r_email = $row[3];
  echo "<tr><td class=\"admin\">".$r_username."</td>";
  echo "<td class=\"admin\">".$r_firstname."</td>";
  echo "<td class=\"admin\">".$r_lastname."</td>";
  echo "<td class=\"admin\">".$r_email."</td>";
  if($r_username != "admin") {
    echo "<td class=\"admin\"><form action=\"admin.php\" method=\"post\">";
    echo "<input type=\"hidden\" name=\"removeuser\" value=\"".$r_username."\">";
    echo "<button type=\"submit\">remove</button></form></td>";
    echo "<td class=\"admin\"><form action=\"admin.php\" method=\"post\">";
    echo "<input type=\"hidden\" name=\"resetpwd\" value=\"".$r_username."\">";
    echo "<button type=\"submit\">reset password</button></form></td>";
    $r_retired = loadone('retired', 'users', 'username', $r_username);
    echo "<td class=\"admin\"><form action=\"admin.php\" method=\"post\">";
    if($r_retired) {
      echo "<input type=\"hidden\" name=\"unretireuser\" value=\"".$r_username."\">";
      echo "<button type=\"submit\">unretire</button></form></td></tr>";
    }
    else {
      echo "<input type=\"hidden\" name=\"retireuser\" value=\"".$r_username."\">";
      echo "<button type=\"submit\">retire</button></form></td></tr>";
    }
  }
  else echo "<td class=\"admin\"></td><td class=\"admin\"></td></tr>";
}
echo "</table>";

html_footer();
