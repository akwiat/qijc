<?php
require_once 'sqlogin.php';
require_once 'utils.php';
ini_set('session.use_only_cookies', 1); //Deters session hijacking, if I understand.
session_start();

//Volunteer to present an abstract
if(isset($_SESSION['username']) && isset($_POST['volunteer_id'])) {
  $username = $_SESSION['username'];
  $id = $_POST['volunteer_id'];
  $scooped = loadone('volunteer', 'abstracts', 'id', $id);
  //this could happen if other users are logged in concurrently
  if(strlen($scooped > 0)) echo "<b>Sorry, ".nickname($scooped)."  beat you to the punch</b><br />";
  else {
    $query = "UPDATE abstracts SET volunteer='".$username."' WHERE id='".$id."'";
    mysql_query($query);
    //get credit
    $aq1 = "INSERT INTO actions(type, week, user) VALUES";
    $aq2 = "('volunteer','".thisweek()."','".$username."')";
    mysql_query($aq1.$aq2);
    //volunteering is worth 10 points
    mysql_query("UPDATE users SET points = points + 10 WHERE username='".$username."'");      
    mysql_query("UPDATE users SET diligence = diligence + 10 WHERE username='".$username."'");
  }
}

//Unsubmit an abstract (that current user submitted):
if(isset($_SESSION['username']) && isset($_POST['remove_id'])) {
  html_header();
  $username = $_SESSION['username'];
  navbar($username, 'submit');
  echo "<br />";
  submitbar();
  $id = $_POST['remove_id'];
  $volun = loadone('volunteer', 'abstracts', 'id', $id);
  if($volun == $username) $points = 15;
  else $points = 5;
  $query = "DELETE FROM abstracts WHERE id='".$id."'";
  mysql_query($query);
  //subtract off the 5 or 15 points
  //actually let's not
  //mysql_query("UPDATE users SET diligence = diligence - ".$points." WHERE username='".$username."'");
  //mysql_query("UPDATE users SET points = points - ".$points." WHERE username='".$username."'");
  //strip away any referring comments (implement later)
  current_abstracts();
  html_footer();
}

//Try to scrape submitted abstract:
if(isset($_SESSION['username']) && isset($_POST['paperid'])) {
  html_header();
  $username = $_SESSION['username'];
  $paperid = $_POST['paperid'];
  $comment = $_POST['comment'];
  $vol = $_POST['vol'];
  navbar($username, 'submit');
  echo "<br />";
  submitbar();
  $matches = array();
  $failed = TRUE;
  //old-style arxiv submissions----------------------------------------
  preg_match('/[0-9]{4}\.[0-9]{5}/',$paperid,$matches); //new format
  $batch = count($matches);
  if($batch > 0) $failed = FALSE;
  if($failed) { //old format
    preg_match('/[0-9]{4}\.[0-9]{4}/',$paperid,$matches);
    $batch = count($matches);
    if($batch > 0) $failed = FALSE;
  }
  for($sub = 0; $sub < $batch; ++$sub) {
    $arxiv_num = $matches[$sub];
    $journal_ref = "arXiv:".$arxiv_num;
    $api_url = "http://export.arxiv.org/api/query?id_list=".$arxiv_num;
    $url = "http://arxiv.org/abs/".$arxiv_num;
    $dom = new DOMDocument();
    $dom->load($api_url);
    $entrynodes=$dom->getElementsByTagName('entry');
    foreach ($entrynodes as $entrynode) {
      $titlenodes=$entrynode->getElementsByTagName('title');
      foreach ($titlenodes as $titlenode) {
        //trim removes leading and trailing whitespace and remove newlines
        $title = str_replace("\n", "", trim($titlenode->nodeValue));
      }
      $authornodes=$entrynode->getElementsByTagName('author');
      $authstring = '';
      foreach ($authornodes as $authornode) {
        if($authstring == '') $authstring = trim($authornode->nodeValue); //trim here too
        else $authstring = $authstring.', '.trim($authornode->nodeValue); //trim here too
      }
      $abstractnodes=$entrynode->getElementsByTagName('summary');
      foreach($abstractnodes as $abstractnode) {
        $abstract = $abstractnode->nodeValue;
      }
    }
  }
  //physical review submissions----------------------------------------
  preg_match('/.*aps\.org.*/',$paperid,$matches);
  $batch = count($matches);
  if($batch == 1) {
    $failed = FALSE;
    $pr_string = file_get_contents($matches[0]);
    $abstract = get_string_between($pr_string, "<div class=\"content\"><p>", "</p>");
    $pr_title = get_string_between($pr_string, '<title>', '</title>');
    $journal_ref = get_string_between($pr_string, '<title>', '-');
    $title = substr(strstr($pr_title, '-'),2);
    $authstring = get_string_between($pr_string, "<h5 class=\"authors\">","</h5>");
    $url = $paperid;
  }
  //nature submissions-------------------------------------------------
  preg_match('/nature/', $paperid, $matches);
  $batch = count($matches);
  if($batch == 1) {
    $failed = FALSE;
    $lastslash = strrpos($paperid, '/');
    $lastdot = strrpos($paperid, '.');
    $doilength = $lastdot - $lastslash - 1;
    $doi = substr($paperid, $lastslash+1,$doilength);
    $nature_api_url = "http://www.nature.com/opensearch/request?query=".$doi;
    $nature_string = file_get_contents($nature_api_url);
    $authors = get_all_between($nature_string, "<dc:creator>", "</dc:creator>");
    $num_authors = count($authors);
    $authstring = "";
    for($authnum = 0; $authnum < $num_authors-1; ++$authnum) $authstring = $authstring.$authors[$authnum].", ";
    $authstring=$authstring.$authors[$authnum];
    $title = get_string_between($nature_string, "<dc:title>", "</dc:title>");
    $abstract = get_string_between($nature_string, "<dc:description>", "</dc:description>");
    $abstract = str_replace('&gt;', '>', $abstract);
    $abstract = str_replace('&lt;', '<', $abstract);
    $title = str_replace('&gt;', '>', $title);
    $title = str_replace('&lt;', '<', $title);
    //should probably check for unbalanced <> and if found, change back
    if($abstract[0] == '<' && $abstract[1] == 'p' && $abstract[2] == '>') 
      $abstract = get_string_between($abstract, '<p>', '</p>');
    $pubdate = get_string_between($nature_string, "<prism:publicationDate>", "</prism:publicationDate>");
    $year = substr($pubdate,0,4);
    $pubname = get_string_between($nature_string, "<prism:publicationName>", "</prism:publicationName>");
    $volume = get_string_between($nature_string, "<prism:volume>", "</prism:volume>");
    $number = get_string_between($nature_string, "<prism:number>", "</prism:number>");
    $startpage = get_string_between($nature_string, "<prism:startingPage>", "</prism:startingPage>");
    $endpage = get_string_between($nature_string, "<prism:endingPage>", "</prism:endingPage>");
    $journal_ref = $pubname." ".$volume;
    if($number) $journal_ref = $journal_ref."(".$number.")";
    if($startpage) $journal_ref = $journal_ref.":".$startpage;
    if($endpage) $journal_ref = $journal_ref."-".$endpage;
    $journal_ref = $journal_ref." (".$year.")";
    $url = $paperid;
  }
  //--------------------------------------------------------------------
  if($failed == TRUE) {
    echo "Submission failed. Make sure to include http:// for prl submissions.<br />";
    echo "Failing all else, you can <a href=\"manual.php\">submit manually</a>.<br />";
  }
  else {
    //we'll allow resubmission of a paper submitted in a prior week, but not this week
    $query = "SELECT submitter FROM abstracts WHERE journalref='".$journal_ref."' AND week IS NULL";
    $result = mysql_query($query);
    if(mysql_num_rows($result) > 0) {
      $othersubs = mysql_fetch_row($result);
      $othernick = nickname($othersubs[0]);
      echo "<b>".$othernick." already submitted that.</b><br />";
    }
    else {
      $title = mysql_real_escape_string($title);
      $authstring = mysql_real_escape_string($authstring);
      $abstract = mysql_real_escape_string($abstract);
      $journal_ref = mysql_real_escape_string($journal_ref);
      $paperid = mysql_real_escape_string($paperid);
      if($vol == 'yes') {
        $q1 = "INSERT INTO abstracts(submitter, title, authors, abstract, journalref, url, votedenominator, volunteer) VALUES";
        $q2 = "('".$username."','".$title."','".$authstring."','".$abstract."','".$journal_ref."','".$url."','0','".$username."')";
      }
      else {
        $q1 = "INSERT INTO abstracts(submitter, title, authors, abstract, journalref, url, votedenominator) VALUES";
        $q2 = "('".$username."','".$title."','".$authstring."','".$abstract."','".$journal_ref."','".$url."','0')";
      }
      mysql_query($q1.$q2);
      //get credit:
      $aq1 = "INSERT INTO actions(type, week, user) VALUES";
      $aq2 = "('submit','".thisweek()."','".$username."')";
      mysql_query($aq1.$aq2);
      //submission is worth 5 points
      mysql_query("UPDATE users SET points = points + 5 WHERE username='".$username."'");      
      mysql_query("UPDATE users SET diligence = diligence + 5 WHERE username='".$username."'");
      if(strlen($comment) > 1) {
        $comment = mysql_real_escape_string($comment);
        $result = mysql_query("SELECT id FROM abstracts WHERE title='".$title."'");
        $row = mysql_fetch_row($result);
        $id = $row[0];
        $q1 = "INSERT INTO comments(id, comment, commenter) VALUES";
        $q2 = "('".$id."','".$comment."','".$username."')";
        mysql_query($q1.$q2);
        //comments are worth 2 points
        mysql_query("UPDATE users SET points = points + 2 WHERE username='".$username."'");
        mysql_query("UPDATE users SET diligence = diligence + 2 WHERE username='".$username."'");
        $aq1 = "INSERT INTO actions(type, week, user) VALUES";
        $aq2 = "('submit','".thisweek()."','".$username."')";
        mysql_query($aq1.$aq2);
      }
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
  }
  current_abstracts();
  html_footer();
}

//Add a comment to an abstract
if(isset($_SESSION['username']) && isset($_POST['comment_id'])) {
  html_header();
  $id = $_POST['comment_id'];
  $query = "SELECT title, abstract, authors, journalref, url, volunteer FROM abstracts WHERE id='".$id."'";
  $result = mysql_query($query);
  $row = mysql_fetch_row($result);
  $title = unescape($row[0]);
  $abstract = unescape($row[1]);
  $authors = $row[2];
  $journalref = unescape($row[3]);
  $url = $row[4];
  $volunteer = $row[5];
  echo nickname($volunteer);
  if($volunteer != NULL) echo "&rarr;".$volunteer;
  echo "<br />";
  echo "<div class=\"papertitle\">";
  echo $title."<br />";
  echo "</div>";
  echo $authors."<br />";
  echo "<a href=\"".$url."\">".$journalref."</a><br />"; 
  echo $abstract."<br />";
  echo "<br />Comment:<br />";
  echo "<form action=\"submit.php\" method=\"post\">";
  echo "<input type=\"hidden\" name=\"comid\" value=\"".$id."\" \>";
  echo "<textarea name=\"comment\" cols=\"83\" rows=\"12\"></textarea><br />";
  echo "<button type=\"submit\">submit</button>";
  echo "</form>";
  html_footer();
}

//Put the new comment in the database, for an existing abstract
if(isset($_SESSION['username']) && isset($_POST['comid'])) {
  $username = $_SESSION['username'];
  $id = mysql_real_escape_string($_POST['comid']);
  $comment = mysql_real_escape_string($_POST['comment']);
  $q1 = "INSERT INTO comments(id, comment, commenter) VALUES";
  $q2 = "('".$id."','".$comment."','".$username."')";
  mysql_query($q1.$q2);
  //get credit:
  $aq1 = "INSERT INTO actions(type, week, user) VALUES";
  $aq2 = "('comment','".thisweek()."','".$username."')";
  mysql_query($aq1.$aq2);
  //comments are worth 2 points
  mysql_query("UPDATE users SET points = points + 2 WHERE username='".$username."'");
  mysql_query("UPDATE users SET diligence = diligence + 2 WHERE username='".$username."'");
}

//Default mainpage (display current abstracts and submitbar):
if(isset($_SESSION['username']) && !isset($_POST['paperid']) && !isset($_POST['remove_id']) && !isset($_POST['comment_id'])) {
  $username = $_SESSION['username'];
  html_header();
  navbar($username, 'submit');
  echo "<br />";
  submitbar();
  current_abstracts();
  html_footer();
}

?>