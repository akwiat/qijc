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
navbar($username, 'messaging');
echo "<br />";

if(isset($_POST['from'])) {
  $admin = FALSE;
  if(isset($_POST['admin'])) $admin = TRUE;
  $users = FALSE;
  if(isset($_POST['users'])) $users = TRUE;
  $from = $_POST['from'];
  $subject = $_POST['subject'];
  $userbody = $_POST['body'];
  $abstracts = FALSE;
  if(isset($_POST['abstracts'])) $abstracts = TRUE;
  $tostring = "";
  if(!$admin && !$users) {
    echo "You can't send an email with no recipients!<br />";
    echo "<a href=\"message.php\">try again</a><br />";
  }
  else {
    if($admin) {
      $admin_email = loadone('email', 'users', 'username', 'admin');
      $tostring = $tostring.$admin_email;
    }
    if($users) {
      if($admin) $tostring = $tostring.",";
      $result = mysql_query("SELECT email FROM users WHERE retired=FALSE AND username <> 'admin' GROUP BY email");
      $recipients = mysql_num_rows($result);
      for($recipient = 0; $recipient < $recipients; ++$recipient) {
        $row = mysql_fetch_row($result);
        $tostring = $tostring.$row[0];
        if($recipient < $recipients-1) $tostring = $tostring.",";
      }
    }
    $headers = "From: ".$_SESSION['username']."@qijc.org"."\r\n";
    if($abstracts) {
      $result = mysql_query("SELECT * FROM abstracts WHERE week IS NULL ORDER BY subtime DESC");
      $num_abstracts = mysql_num_rows($result);
      if($num_abstracts > 0) {
        $some_abstracts = TRUE;
        $headers .= "MIME-Version: 1.0"."\r\n";
        $headers .= "Content-type: multipart/alternative; boundary=c4d5d00c4725d9ed0b3c8b"."\r\n";
        $body = "--c4d5d00c4725d9ed0b3c8b"."\n";
        $body .= "Content-Type: text/plain; charset=\"utf-8\""."\n";
        $body .= "Content-Transfer-Encoding: 7bit"."\n";
        $body .= $userbody."\n";
        for($ab = 0; $ab < $num_abstracts; ++$ab) {
          $row = mysql_fetch_row($result);
          $submitter = $row[0];
          $title = unescape($row[1]);
          $abstract = unescape($row[2]);
          $authors = $row[3];
          $journalref = unescape($row[4]);
          $url = $row[9];
          $volunteer = $row[12];
          $body .= "\n\nSubmitter: ".nickname($submitter);
          if($volunteer != NULL) $body .= "\nVolunteer: ".nickname($volunteer);
          $body .= "\n".$title."\n";
          $body .= $authors."\n";
          $body .= $url."\n"; 
          $body .= $abstract."\n\n";
        }
        $body .= "--c4d5d00c4725d9ed0b3c8b"."\n";
        $body .= "Content-Type: text/html; charset=\"utf-8\""."\n";
        $body .= "Content-Transfer-Encoding: 7bit"."\n";
        //nl2br converts \n int <br />
        $body .= "<html>\n<head>\n<title>QIJC</title>\n</head>\n<body>\n".nl2br($userbody)."<br /><br />\n";
        //must refetch!
        $result = mysql_query("SELECT * FROM abstracts WHERE week IS NULL ORDER BY subtime DESC");
        for($ab = 0; $ab < $num_abstracts; ++$ab) {
          $row = mysql_fetch_row($result);
          $submitter = $row[0];
          $title = unescape($row[1]);
          $abstract = unescape($row[2]);
          $authors = $row[3];
          $journalref = unescape($row[4]);
          $url = $row[9];
          $volunteer = $row[12];
          $body .= "\n\nSubmitter: ".nickname($submitter);
          if($volunteer != NULL) $body .= "<br />\nVolunteer: ".nickname($volunteer);
          $body .= "<br />\n".$title."<br />\n";
          $body .= $authors."<br />\n";
          $body .= "<a href=\"".$url."\">".$journalref."</a><br />\n"; 
          $body .= $abstract."<br />\n<br />\n";
        }
        $body .= "</body>\n";
        $body .= "</html>\n";
        $body .= "--c4d5d00c4725d9ed0b3c8b--";
      }
    }
    if(!$some_abstracts) { //no abstracts
      $headers .= "MIME-Version: 1.0"."\r\n";
      $headers .= "Content-Type: text/plain; charset=\"utf-8\""."\r\n";
      $body = $userbody;
    }
    $headers .= 'Reply-To: '.$from;
    $success = mail($tostring, $subject, $body, $headers);
    if(!$success) "<b>Failed to send message. Try again or contact your system administrator.</b><br />";
    else {
      echo "Message sent.<br />";
      //echo "Message sent:<br /> ";
      //echo "To: ".$tostring."<br />";
      //echo "From: ".$from."<br />";
      //echo "Subject: ".$subject."<br />";
      //echo "Body: <pre>".$body."</pre>";
    }
  }
}
else {
  $nickname = nickname($username);
  $email = loadone('email', 'users', 'username', $username);
  echo "<form action=\"message.php\" method=\"post\">";
  echo "<table>";
  echo "<tr><td>To:</td><td><input type=\"checkbox\" name=\"admin\" value=\"admin\" />sysadmin";
  echo "<input type=\"checkbox\" name=\"users\" value=\"users\" checked />users</td></tr>";
  echo "<tr><td>From:</td><td><input type=\"text\" name=\"from\" size=\"80\" value=\"".$email."\"></td></tr>";
  echo "<tr><td>Subject:</td><td><input type=\"text\" name=\"subject\" size=\"80\" value=\"Quantum Information Journal Club\">";
  echo "<tr><td>Body:</td><td><textarea name=\"body\" cols=\"83\" rows=\"20\">";
  echo "The abstracts for this week are attached. Please log in if you want to claim a paper to discuss.";
  echo "\n\n";
  echo "Best regards,";
  echo "\n\n";
  echo $nickname;
  echo "</textarea></td></tr>";
  echo "<tr><td>Attach abstracts:</td><td><input type=\"checkbox\" name=\"abstracts\" value=\"abstracts\" checked />";
  echo "</td></tr><tr><td><input type=\"submit\" value=\"Send\"/></td><td></td></tr></table></form>";
}

html_footer();

?>