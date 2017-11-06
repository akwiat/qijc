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
navbar($username, 'submit');

$valid = FALSE;

function manual_form() {
echo <<<_END
<form method="post" action="manual.php">
<table>
<tr>
<td>Title:</td><td><input type="text" name="title" size="85"/></td>
</tr><tr>
<td>Authors:</td><td><input type="text" name="authors" size="85"/></td>
</tr><tr>
<td>Journal Ref:</td><td><input type="text" name="journalref" size="85"/></td>
</tr><tr>
<td>URL:</td><td><input type="text" name="url" size="85"/></td>
</tr><tr>
<td>Abstract:</td><td>
<textarea name="abstract" cols="88" rows="20"/>
</textarea></td></tr>
<tr><td>Category:</td><td>
<input type="radio" name="vol" value="no" checked>I'm seeking volunteers to discuss this paper.<br />
<input type="radio" name="vol" value="yes">I'm volunteering to discuss this paper.<br /><br />
</td></tr>
<td></td><td><input type="submit" value="submit" /></td></tr>
</table>
</form>
_END;
}

if(!isset($_POST['title']) && 
   !isset($_POST['authors']) &&
   !isset($_POST['journalref']) &&
   !isset($_POST['url']) &&
   !isset($_POST['abstract'])) {

  $valid = TRUE;
  echo "<br />";
  manual_form();
}

if(isset($_POST['title']) && 
   isset($_POST['authors']) &&
   isset($_POST['abstract'])) {

  if(strlen($_POST['title']) > 0 && 
     strlen($_POST['authors']) > 0 &&
     strlen($_POST['abstract']) > 0) { //let's allow empty url and empty journalref

    $valid = TRUE;

    $firstname = loadone('firstname', 'users', 'username', $username);
    $title = mysql_real_escape_string($_POST['title']);
    $authstring = mysql_real_escape_string($_POST['authors']);
    $journal_ref = mysql_real_escape_string($_POST['journalref']);
    if(strlen($journal_ref) == 0) $journal_ref = 'unknown';
    $url = mysql_real_escape_string($_POST['url']);
    $abstract = mysql_real_escape_string($_POST['abstract']);
    $vol = mysql_real_escape_string($_POST['vol']);

    $query = "SELECT submitter FROM abstracts WHERE journalref='".$journal_ref."'";
    $result = mysql_query($query);
    if(mysql_num_rows($result) > 0) {
      $othersubs = mysql_fetch_row($result);
      echo nickname($othersubs[0])." already submitted that.<br />";
    }
    else {
      if(!preg_match("/^http:/", $url)) $url = "http://".$url;
      if($vol == 'yes') {
        $q1 = "INSERT INTO abstracts(submitter, title, authors, abstract, journalref, url, votedenominator, volunteer) VALUES";
        $q2 = "('".$username."','".$title."','".$authstring."','".$abstract."','".$journal_ref."','".$url."','0','".$username."')";        
      }
      else {
        $q1 = "INSERT INTO abstracts(submitter, title, authors, abstract, journalref, url, votedenominator) VALUES";
        $q2 = "('".$username."','".$title."','".$authstring."','".$abstract."','".$journal_ref."','".$url."','0')";
      }
      mysql_query($q1.$q2);
      //get credit
      $aq1 = "INSERT INTO actions(type, week, user) VALUES";
      $aq2 = "('submit','".thisweek()."','".$username."')";
      mysql_query($aq1.$aq2);
      //submission is worth 5 points
      mysql_query("UPDATE users SET points = points + 5 WHERE username='".$username."'");      
      mysql_query("UPDATE users SET diligence = diligence + 5 WHERE username='".$username."'");
      if($vol == 'yes') {
        //get credit
        $aq1 = "INSERT INTO actions(type, week, user) VALUES";
        $aq2 = "('volunteer','".thisweek()."','".$username."')";
        mysql_query($aq1.$aq2);
        //volunteering is worth 10 points
        mysql_query("UPDATE users SET points = points + 10 WHERE username='".$username."'");      
        mysql_query("UPDATE users SET diligence = diligence + 10 WHERE username='".$username."'");
      }
    }
    submitbar();
    current_abstracts();
  }
}

if($valid == FALSE) {
  echo "<br />";
  echo "Error in query. Try again, go back to the <a href=\"index.php\">main console</a>, or contact the system administrator.<br />";
  manual_form();
}

html_footer();

?>
