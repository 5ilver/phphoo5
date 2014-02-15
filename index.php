<?php

include("config.php");			// Configuration information
require("mysql.php");			// Access to all the database functions
if ("$ENABLE_EMAIL" == "on"){
	require("smtp.php");			// Access to mail class
}

// Open the database
$db = new MySQL;
if(!$db->init()) {
	echo "Cannot open database<BR>\n";
	exit;
}

// Prepare SMTP class
if ($ENABLE_EMAIL == "on"){
	$smtp = new smtp_class;
	$smtp->host_name = "yourhost.com";
	$smtp->localhost = "localhost";
}

// Send mail function
function smtp_mail($from, $to, $subject, $body, $replyto = "", $bcc = "")
{
	global $smtp;

	$hdr = array(
		"From: $from",
		"To: $to",
		"Subject: $subject"
		);

	if ($replyto) { $hdr[] = "Reply-to: $replyto"; }

	if ($bcc) { $hdr[] = "Bcc: $bcc"; }

	if (!$smtp->SendMessage($from, array($to), $hdr, $body)) {
		echo "<p>Could not send the message to $to.\nError: ".$smtp->error."</p>\n";
	}
}

// Expand CatID to whole trail
function breadcrumbs($CatID="")
{
	global $db;
	$trail="";

	if(empty($CatID)) { return; }
	$db->get_ParentsInt($CatID);
	$path = $db->TRAIL;
	if(!empty($path))
	{
		while ( list ( $key,$val ) = each ($path))
		{
			$CatID		= stripslashes($val["CatID"]);
			$CatName	= stripslashes($val["CatName"]);
			$trail		= "&nbsp;&gt;&nbsp;<A HREF=\"".$_SERVER['PHP_SELF']."?viewCat=$CatID\">$CatName</A>$trail";
		}
	} else {
		$trail = "";
	}
	return $trail;
}

function breadcrumbs_txt($CatID="")
{
	global $db;
	$trail="";
	if(empty($CatID)) { return; }
	$db->get_ParentsInt($CatID);
	$path = $db->TRAIL;
	if(!empty($path))
	{
		while ( list ( $key,$val ) = each ($path))
		{
			$CatID		= stripslashes($val["CatID"]);
			$CatName	= stripslashes($val["CatName"]);
			if ($trail) {
				$trail	= "$CatName: $trail";
			} else {
				$trail	= $CatName;
			}
		}
	} else {
		$trail = "-";
	}
	return $trail;
}

// Print page header based on template file
function print_header($CatID="",$title="")
{
	global $HEADER_FILE;
	global $db;

	$trail = breadcrumbs($CatID);
	$lnk_cnt = $db->get_approved_cnt();
	$new_cnt = $db->get_not_approved_cnt();

	$filename = $HEADER_FILE;
	$fd = fopen ($filename, "r");
	$c = fread ($fd, filesize ($filename));
	fclose ($fd);

	$c = str_replace("@TRAIL@", $trail, $c);
	$c = str_replace("@TITLE@", $title, $c);
	$c = str_replace("@LNK_CNT@", $lnk_cnt, $c);
	$c = str_replace("@NEW_CNT@", $new_cnt, $c);
	print ($c);
}

// Print page footer based on template file
function print_footer($CatID="",$title="")
{
	global $db;
	global $FOOTER_FILE;

	$lnk_cnt = $db->get_approved_cnt();
	$new_cnt = $db->get_not_approved_cnt();
	$trail = breadcrumbs($CatID);

	$filename = $FOOTER_FILE;
	$fd = fopen ($filename, "r");
	$c = fread ($fd, filesize ($filename));
	fclose ($fd);

	$c = str_replace("@TRAIL@", $trail, $c);
	$c = str_replace("@TITLE@", $title, $c);
	$c = str_replace("@LNK_CNT@", $lnk_cnt, $c);
	$c = str_replace("@NEW_CNT@", $new_cnt, $c);
	print ($c);
}

function show_submissions_list($CatID)
{
	global $db;
	global $SEE_ALL_SUBMISSIONS;
	global $TOP_CAT_NAME;

	if ($SEE_ALL_SUBMISSIONS) {
		$sub = $db->get_Submissions();
	} else {
		// Need to replace with function to show only for this CatID
		$sub = $db->get_Submissions();
	};

	if(!empty($sub))
	{
	print "<UL>\n";
		while ( list ( $key,$val ) = each ($sub))
		{
			$Url		= stripslashes($val["Url"]);
			$LinkName	= stripslashes($val["LinkName"]);
			$Desc		= stripslashes($val["Description"]);
			$Name		= stripslashes($val["SubmitName"]);
			$Email		= stripslashes($val["SubmitEmail"]);
			$SDate		= date("D M j G:i:s T Y", $val["SubmitDate"]);
			$LinkID		= stripslashes($val["LinkID"]);
			$LinkCatID	= stripslashes($val["CatID"]);

			if(!empty($LinkCatID))
			{
				$LinkCatName = breadcrumbs_txt($LinkCatID);
			} else {
				$LinkCatName = "$TOP_CAT_NAME";
			}

			print "<LI>";
			print "<A HREF=\"$Url\" TARGET=\"_BLANK\"><B>$LinkName</B></A> - $Desc<BR>\n";
			print "<small><strong>URL: </strong><font color=\"999999\">$Url</font></small><BR>\n";

			// Print submitter name and email
			print "<small><strong>Name: </strong><A HREF=\"MAILTO:$Email\">$Name</A> - $Email <strong>Date: </strong>$SDate</small><BR>\n";

			// Print category
			print "<small><strong>Category: </strong>$LinkCatName</small><br>\n";

			print "<SMALL>[";
			// Link to approve a sumbission
			print "<A HREF=\"".$_SERVER['PHP_SELF']."?CatID=$CatID&approve=$LinkID\">Approve</A> ";

			// Link to delete a sumbission
			print "<A HREF=\"".$_SERVER['PHP_SELF']."?CatID=$CatID&delete_link=$LinkID\">Delete</A> ";

			// Link to edit a sumbission
			print "<A HREF=\"".$_SERVER['PHP_SELF']."?CatID=$CatID&edit_link=$LinkID\">Edit</A>";
			print "]</SMALL>";
			print "</LI>";
		}
	print "</UL>\n";
	}else{
		print "<center><i>No new submissions</i></center>";
	}
	return;
}

function start_page($CatID="",$title="",$msg="")
{
	global $SITE_URL;
	global $ADMIN_MODE;

	print_header($CatID,$title);

	if(!empty($msg))
	{
		print "\n<CENTER><B>$msg</B></CENTER>\n";
	}else{
		if ( "$ADMIN_MODE" == "true" ){
			print "\n<CENTER><i>ADMIN mode active</i></CENTER>\n";
		}
	}

	print "<P><CENTER><FORM ACTION=\"".$_SERVER['PHP_SELF']."\" METHOD=\"POST\">\n";
	print '
	<INPUT TYPE="TEXT" NAME="KeyWords" SIZE=20>
	<INPUT TYPE="SUBMIT" NAME="Search" VALUE="Search"></FORM></CENTER></P>
	';

	return;
}

function start_browse($CatID="")
{
	global $db;
	global $ADMIN_MODE;
	global $TOP_CAT_NAME;
	global $ANYONE_SUGGEST;

	$data	= $db->get_Cats($CatID);
	$links	= $db->get_Links($CatID);

	$OurCatID = $CatID;

	if(empty($CatID) || ($CatID == "0"))
	{
		$currentID = "top";
		$currentName = "$TOP_CAT_NAME";
	} else {
		$currentID = $CatID;
		$currentName = $db->get_CatNames($CatID);
	}

	// Print list of sub categories
	if(!empty($data))
	{
		$data_cnt = count ($data);
		$data_left = $data_cnt >> 1;
		print '<table width="100%" bordersize="0" cellpadding="0" cellspacing="0"><tr><td width="50%" align="left" valign="top">';
		print "<UL>\n";
		while ( list ( $key,$val ) = each ($data))
		{
			$CatID = stripslashes($val["CatID"]);
			$CatName = stripslashes($val["CatName"]);
			$LinksInCat = $db->get_TotalLinksInCat_cnt($CatID);
			print "<LI><A HREF=\"".$_SERVER['PHP_SELF']."?viewCat=$CatID\"><B>$CatName</B></A>";
			print " <I><SMALL>($LinksInCat)</SMALL></I>";
			print "</LI>\n";
			$data_cnt--;
			if ($data_cnt == $data_left) {
				print '</UL>';
				print '</td><td width="50%" align="left" valign="top">';
				print '<UL>';
			}
		}
		print "</UL>\n";
		print "</td></tr></table>\n";
	}
	$CatID = $OurCatID;	// restore CatID


	// Print list of links
	if(!empty($links))
	{
		print "<UL>\n";
		print "<b>$currentName:</b>\n";
		while ( list ( $key,$val ) = each ($links))
		{
			$Url		= stripslashes($val["Url"]);
			$LinkName	= stripslashes($val["LinkName"]);
			$Desc		= stripslashes($val["Description"]);
			print "<LI>";
			print "<A HREF=\"$Url\" TARGET=\"_BLANK\"><B>$LinkName</B></A> - $Desc<BR>\n";
			print "<small><strong>URL: </strong><font color=\"999999\">$Url</font></small>\n";
			if ("$ADMIN_MODE" == "true") {
				$Name		= stripslashes($val["SubmitName"]);
				$Email		= stripslashes($val["SubmitEmail"]);
				$SDate		= date("D M j G:i:s T Y", $val["SubmitDate"]);
				$LinkID		= stripslashes($val["LinkID"]);

				// Print submitter name and email
				print "<br><small><strong>Name: </strong><A HREF=\"MAILTO:$Email\">$Name</A> - $Email <strong>Date: </strong>$SDate</small><BR>\n";

				// Link to disapprove a sumbission
				print "<SMALL>[<A HREF=\"".$_SERVER['PHP_SELF']."?CatID=$CatID&disapprove=$LinkID\">Disapprove</A> ";

				// Link to edit a sumbission
				print "<A HREF=\"".$_SERVER['PHP_SELF']."?CatID=$CatID&edit_link=$LinkID\">Edit</A>]</SMALL>";
			}
			print "</LI>\n";
		}
		print "</UL>\n";
	}

	if ("$ANYONE_SUGGEST" == "true" || "$ADMIN_MODE" == "true"){
		print "<P><CENTER>";
		print " <A HREF=\"".$_SERVER['PHP_SELF']."?add=$currentID\">Suggest new link</A> ";
		print "</CENTER></P>\n";
	}
	if ("$ADMIN_MODE"=="true"){
		print "\n<HR>\n";
		print "<CENTER><H2>Submissions</H2></CENTER>\n";
		show_submissions_list($CatID);
		$CatID = $OurCatID;	// restore CatID

		// Show form to add a subcategory
		print "\n<HR>\n";
		print "<CENTER><H2>New Catergory</H2></CENTER>\n";
		print "<p><center>
		<form action=\"".$_SERVER['PHP_SELF']."\" method=\"POST\">
		<input type=\"hidden\" name=\"CatID\" value=\"$CatID\">
		<input type=\"hidden\" name=\"add_cat\" value=\"1\">
		<input name=\"NewCatName\" size=\"40\">
		<input type=\"submit\" name=\"submit\" value=\" Create \">
		</form>
		</center></p>\n";
	}

	// Print the footer
	print_footer();

	return;
}

// Print drop-down box for available categories
function show_cat_selection($SelName = "CatID", $IncludeTop = true, $SecSel = 0, $IncludeNone = false)
{
        global $db;
        global $ADMIN_MODE;
        global $TOP_CAT_NAME;

        print "<select name=\"$SelName\">\n";
        if ($IncludeNone) {
                if ($SecSel == -1) {$sel = "selected";} else {$sel = "";}
                print "<option $sel value=\"NONE\">- none -</option>";
                if ($SecSel == 0) {$sel = "selected";} else {$sel = "";}
                print "<option $sel value=\"0\">$TOP_CAT_NAME</option>";
        } elseif ($IncludeTop) {
                if (($SecSel == 0) or ($SecSel == -1)) {$sel = "selected";} else {$sel = "";}
                print "<option $sel value=\"0\">$TOP_CAT_NAME</option>";
        }

        $secs = $db->get_AllCats();

        if(!empty($secs))
        {
                while (list ($key, $val) = each ($secs))
                {
                        // Run for all sections:
                        $CatID   = $val["CatID"];
                        $CatName = breadcrumbs_txt($CatID);

                        if ($CatID == $SecSel) {$sel = "selected";} else {$sel = "";}
                        print "<option $sel value=\"$CatID\">$CatName</option>\n";
                }
        }
        print "</select>\n";

        return;
}

function show_edit_link($LinkID="",$title="",$msg="")
{
	global $db;
	global $TOP_CAT_NAME;
	global $FULL_ADMIN_ACCESS;

	print_header($CatID,$title,$msg);

	$thislink = $db->get_OneLink($LinkID);
	if (empty($thislink)) {
		print "<p>Bad LinkID, nothing returned</p>
		<HR noshade>
		</form></p>
		</html>\n";
		return;
	}

	while ( list ( $key,$val ) = each ($thislink))
	{
		$CatID		= stripslashes($val["CatID"]);
		$Url		= stripslashes($val["Url"]);
		$LinkName	= stripslashes($val["LinkName"]);
		$Desc		= stripslashes($val["Description"]);
		$Name		= stripslashes($val["SubmitName"]);
		$Email		= stripslashes($val["SubmitEmail"]);
		$SDate		= date("D M j G:i:s T Y", $val["SubmitDate"]);
	}

	if(!empty($CatID))
	{
		$LinkCatName = $db->get_CatNames($CatID);
	} else {
		$LinkCatName = "$TOP_CAT_NAME";
	}

	print "<p><H3>Edit a Resource in: <B>$LinkCatName</B></H3><HR>
	<form action=\"".$_SERVER['PHP_SELF']."?update=1\" method=\"POST\">
	<input type=\"hidden\" name=\"LinkID\" value=\"$LinkID\">
	<table border=\"0\" cellpadding=\"2\" cellspacing=\"2\">
	<tr><td align=\"right\"><B>URL:</B></td><td><input name=\"Url\" size=\"40\" VALUE=\"$Url\"></td></tr>
	<tr><td align=\"right\"><B>Title:</B></td><td><input name=\"LinkName\" size=\"40\" VALUE=\"$LinkName\"></td></tr>
	<tr><td align=\"right\"><B>Description:</B></td><td><textarea name=\"Description\" rows=\"3\" cols=\"40\">$Desc</textarea></td></tr>
	<tr><td align=\"right\"><B>Your Name:</B></td><td><input name=\"SubmitName\" size=\"40\" VALUE=\"$Name\"></td></tr>
	<tr><td align=\"right\"><B>Your Email:</B></td><td><input name=\"SubmitEmail\" size=\"40\" VALUE=\"$Email\"></td></tr>
	<tr><td align=\"right\"><B>Category:</B></td><td>";

	show_cat_selection("CatID", True, $CatID);

	print " Date: $SDate</td></tr>
	<tr><td></td><td><input type=\"submit\" name=\"update\" value=\"Update Resource\">
	<input type=\"reset\" value=\" Reset \"></td></tr>
	</table>\n";


	print_footer();

	return;
}

function show_add_link($add = "NULL", $CatName = "Unknown")
{
	global $db;
	global $TOP_CAT_NAME;
	global $FULL_ADMIN_ACCESS;
	global $UserName;		// Cookie
	global $UserEmail;		// Cookie

	print_header($add);
	print "
	<p><H3>Add a link in: <B>$CatName</B></H3><HR noshade>
	<form action=\"".$_SERVER['PHP_SELF']."?suggest=1\" method=\"POST\">
	<input type=\"hidden\" name=\"CatID\" value=\"$add\">
	<table border=\"0\" cellpadding=\"2\" cellspacing=\"2\">
	<tr><td align=\"right\"><B>URL:</B></td><td><input name=\"Url\" size=\"40\" VALUE=\"http://\"></td></tr>
	<tr><td align=\"right\"><B>Title:</B></td><td><input name=\"LinkName\" size=\"40\"></td></tr>
	<tr><td align=\"right\"><B>Description:</B></td><td><textarea name=\"Description\" rows=\"3\" cols=\"40\"></textarea></td></tr>
	<tr><td align=\"right\"><B>Your Name:</B></td><td><input name=\"SubmitName\" value=\"$UserName\" size=\"40\"></td></tr>
	<tr><td align=\"right\"><B>Your Email:</B></td><td><input name=\"SubmitEmail\" value=\"$UserEmail\" size=\"40\"></td></tr>
	<tr><td></td><td><input type=\"submit\" name=\"suggest\" value=\"Submit Resource\">
	<input type=\"reset\" value=\" Reset \"></td></tr>
	</table>
	</form></p>\n";

	print_footer();

	return;
}

// Mail the admin anytime a new link is submitted
function mail_new_link($postData = "")
{
	global $db;
	global $ADMIN_EMAIL;
	global $ENABLE_EMAIL;
	global $AUTOAPPROVE;

	if( (empty($postData)) or (!is_array($postData)) ) { return false; }
	if ($ADMIN_EMAIL == "") { return false; }

	$CatID = $postData["CatID"];
	$Url = addslashes($postData["Url"]);
	$Description = addslashes($postData["Description"]);
	$LinkName = addslashes($postData["LinkName"]);
	$SubmitName = addslashes($postData["SubmitName"]);
	$SubmitEmail = addslashes($postData["SubmitEmail"]);
	$SubmitDate = time();

	// Get category information
	$CatName = breadcrumbs_txt($CatID);
	if (empty($CatName)) {
		$CatName = "Unknown";
	}

	$Subject = "New Link: ";
	$Subject .= substr($LinkName, 0, 60);
	if ($LinkName != substr($LinkName, 0, 60)) {
		$LinkName .= "...";
	}
	$Subject = trim($Subject);

	$Body = "User \"$SubmitName\" <".$SubmitEmail."> submitted this link in category $CatName:\n\n";
	$Body .= "$LinkName at <$Url>\n\n";
	$Body .= "$Description\n\n";
	if ("$AUTOAPPROVE" == "on") {
		$Body .= "This link was auto-approved.\n";
	} else {
		$Body .= "This link needs approval.\n";
		$Body .= "(Use ".$_SERVER['PHP_SELF']."?<secret code> for admin rights!)\n";
	}

	$From = "$SubmitName<".$SubmitEmail.">";

	// Send the email notice if email defined
	if ($ADMIN_EMAIL && "$ENABLE_EMAIL" == "yes") {
		// function smtp_mail($from, $to, $subject, $body, $replyto = "", $bcc = "")
		smtp_mail($ADMIN_EMAIL, $ADMIN_EMAIL, $Subject, $Body, $From);
	}

	return;
}

//	*****************************************************************

// Check cookie to see if we are in admin mode
$HooPass="";
if (isset($_COOKIE['HooPass'])){
	$HooPass=$_COOKIE['HooPass'];
}

if($HooPass == $ADMIN_COOKIE) {
	$ADMIN_MODE = "true";
}

$query = getenv("QUERY_STRING");

$viewCat="";
if (isset($_GET['viewCat'])){
	$viewCat=$_GET['viewCat'];
}

$add="";
if (isset($_GET['add'])){
	$add=$_GET['add'];
}

$add_cat="";
if (isset($_POST['add_cat'])){
	$add_cat=$_POST['add_cat'];
}

$suggest="";
if (isset($_GET['suggest'])){
	$suggest=$_GET['suggest'];
}

$update="";
if (isset($_GET['update'])){
	$update=$_GET['update'];
}

$approve="";
if (isset($_GET['approve'])){
	$approve=$_GET['approve'];
}

$disapprove="";
if (isset($_GET['disapprove'])){
	$disapprove=$_GET['disapprove'];
}

$delete_link="";
if (isset($_GET['delete_link'])){
	$delete_link=$_GET['delete_link'];
}

$edit_link="";
if (isset($_GET['edit_link'])){
	$edit_link=$_GET['edit_link'];
}

$KeyWords="";
if (isset($_POST['KeyWords'])){
	$KeyWords=$_POST['KeyWords'];
}

$CatID="";
if (isset($_GET['CatID'])){
	$CatID=$_GET['CatID'];
}

$enter_admin="";
if (isset($_GET['enter_admin'])){
	$enter_admin = "true";
}
$exit_admin="";
if (isset($_GET['exit_admin'])){
	$exit_admin = "true";
}
$USER="";
if (isset($_POST['USER'])){
	$USER = $_POST['USER'];
}
$PASS="";
if (isset($_POST['PASS'])){
	$PASS = $_POST['PASS'];
}




$HTTP_POST_VARS=$_POST;


if( ($viewCat) or ( (!$HTTP_POST_VARS) and (!$query) ) )
{
	start_page($viewCat);
	start_browse($viewCat);
	exit;

} elseif($add)
{
	if (("$add" == "top") || empty($add)) {
		$add = 0;
		$CatName = "$TOP_CAT_NAME";
	} else {
		$CatName = stripslashes($db->get_CatNames($add));
		if (empty($CatName)) { $CatName = "$TOP_CAT_NAME"; }
	}

	show_add_link($add, $CatName);
	exit;

} elseif($add_cat)
{
	$err_msg = "";
	if ("$ADMIN_MODE" == "true" && $FULL_ADMIN_ACCESS) {
		if(!$db->add_cat($HTTP_POST_VARS,$err_msg))
		{
			$title = "Error Creating Category";
			$msg = "Category not created. ".$err_msg;
		} else {
			$title = "Category Created";
			$msg = "New subcategory created";
		}
	} else {
		$title = "Error Creating Category";
		$msg = "Not authorized for creating categories";
	}
	start_page($CatID,$title,$msg);
	start_browse($CatID);
	exit;

} elseif ($suggest)
{
	$err_msg = "";
	if ("$ANYONE_SUGGEST" == "true" || "$ADMIN_MODE" == "true"){
		if(!$db->suggest($HTTP_POST_VARS,$err_msg))
		{
			$title = "Suggestion Error";
			$msg = "Sugestion not accepted: ".$err_msg;
		} else {
			$title = "Suggestion Submitted";
			$msg = "Suggestion submitted for approval";
			// Also tell the admin about it
			mail_new_link($HTTP_POST_VARS);
		}
	}else{
		$title = "Suggestion Error";
		$msg = "Sugestion not allowed";
	}
	start_page($CatID,$title,$msg);
	start_browse($CatID);
	exit;

} elseif ($update)
{
	$err_msg = "";
	if ( "$ADMIN_MODE" == "true") {
		if(!$db->update($HTTP_POST_VARS,$err_msg))
		{
			$title = "Update Error";
			$msg = "Update failed: ".$err_msg;
		} else {
			$title = "Updated";
			$msg = "Updated entry submitted for approval";
		}
	} else {
		$title = "Update Error";
		$msg = "Not authorized";
	}
	start_page($CatID,$title,$msg);
	start_browse($CatID);
	exit;

} elseif ($approve)
{
	if ( "$ADMIN_MODE" == "true" ) {
		if(!$db->approve($approve,$err_msg))
		{
			$title = "Approval Error";
			$msg = $err_msg;
		} else 	{
			$title = "Approved";
			$msg = "Suggestion approved";
		}
	} else {
		$title = "Approval Error";
		$msg = "Not authorized";
	}
	start_page($CatID,$title,$msg);
	start_browse($CatID);
	exit;

} elseif ($disapprove)
{
	if ( "$ADMIN_MODE" == "true") {
		if(!$db->disapprove($disapprove,$err_msg))
		{
			$title = "Disapproval Error";
			$msg = $err_msg;
		} else 	{
			$title = "Disapproved";
			$msg = "Link disapproved";
		}
	} else {
		$title = "Disapproval Error";
		$msg = "Not authorized";
	}
	start_page($CatID,$title,$msg);
	start_browse($CatID);
	exit;

} elseif ($delete_link)
{
	if ( "$ADMIN_MODE" == "true") {
		if(!$db->delete_link($delete_link,$err_msg))
		{
			$title = "Error deleting submission";
			$msg = $err_msg;
		} else 	{
			$title = "Deleted";
			$msg = "Suggestion deleted";
		}
	} else {
		$title = "Error deleting submission";
		$msg = "Not authorized";
	}
	start_page($CatID,$title,$msg);
	start_browse($CatID);
	exit;

} elseif ($edit_link)
{
	show_edit_link($edit_link,$title,$msg);
	exit;

} elseif ($enter_admin)
{
	if("$ADMIN_MODE" == "true") {
		print "<p>You are ADMIN<UL>";
		print "<LI>You could <a href=\"?exit_admin=1\">Exit ADMIN mode</a></LI>";
		print "<LI>Or just <a href=\"?\">Go back</a></LI>";
		print "</UL></P>\n";
		exit;
	}else{
	
		// Check to see if we are being posted a set of USER/PASS
		if (($USER == $ADMIN_USER) && ($PASS == $ADMIN_PASS)) {
			setcookie("HooPass", $ADMIN_COOKIE);
			header ("Location: ".$_SERVER['PHP_SELF']);
		exit;
		}
	
		// Bad login attempt? 
		if (($USER != "") || ($PASS != "")) {
			print "Invalid login";
		}
	
	
		print "<form action=\"".$_SERVER['PHP_SELF']."?enter_admin=1\" method=\"POST\">";
		print "<table bgcolor=\"#CCCCCC\">";
		print "<tr><td>Login Name:</td><td><input type=\"text\" size=\"10\" name=\"USER\"></td></tr>";
		print "<tr><td>Password:</td><td><input type=\"password\" size=\"10\" name=\"PASS\"></td></tr>";
		print "<tr><td></td><td align=\"right\"><input type=\"submit\" name=\"LOGIN\" value=\"LOGIN\"></td></tr>";
		print "</table></form>\n";
	}
	exit;

} elseif ($exit_admin)
{
	setcookie("HooPass", "");
	header ("Location: ".$_SERVER['PHP_SELF']);

} elseif ($KeyWords)
{
	//start_page();
	$hits = $db->search($KeyWords);
	if( (!$hits) or (empty($hits)) )
	{
		$junk = "";
		$title = "Search Results";
		$msg =  "No Matches";
		start_page($junk,$title,$msg);
	} else {
		$total = count($hits);
		$title = "Search Results";
		$msg = "Search returned $total matches";
		$junk = "";
		start_page($junk,$title,$msg);
		while ( list ($key,$hit) = each ($hits))
		{
			if(!empty($hit))
			{
				$LinkID = $hit["LinkID"];
				$LinkName = stripslashes($hit["LinkName"]);
				$LinkDesc = stripslashes($hit["Description"]);
				$LinkURL = stripslashes($hit["Url"]);
				$CatID = $hit["CatID"];
				$CatName = stripslashes($db->get_CatNames($CatID));
				print "<DL>\n";
				print "<DT><A HREF=\"$LinkURL\" TARGET=\"_NEW\">$LinkName</A>\n";
				print "<DD>$LinkDesc\n";
				print "<DD><B>Found In:</B>&nbsp;<A HREF=\"".$_SERVER['PHP_SELF']."?viewCat=$CatID\">$CatName</A>\n";
				print "</DL>\n";
			}
		}
	}
	print "<P><HR>\n";
	start_browse("");
	exit;

} else {
	// Something terribly bad happened - start fresh
	start_page("","Error","Unknown error");
	start_browse("");
	exit;
}
?>







