<?php

include("config.php");			// Configuration files

$ver = phpversion();

$HooPass="";
if (isset($_COOKIE['HooPass'])){
	$HooPass = $_COOKIE['HooPass'];
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


// Check cookie to see if we are in admin mode
if($HooPass == $ADMIN_COOKIE) {
	// should we delete the cookie and start?
	if ("$exit_admin"=="true") {
		print "You have been logged out";
	}else{

		// Or offer entry as USER or ADMIN?
		print "<HTML><BODY>\n";
	
		print "<p>You are already ADMIN - Your options are:<UL>";
		print "<LI><a href=\"$SITE_URL$SITE_DIR\">Back to directory</a></LI>";
		print "<LI><a href=\"?exit_admin=1\">Exit ADMIN</a></LI>";
		print "</UL></P>\n";
	
		print "<p>PHP ver $ver</p>";
		print "</BODY></HTML>\n";
		exit;
	}
}

// Check to see if we are being posted a set of USER/PASS
if (($USER == $ADMIN_USER) && ($PASS == $ADMIN_PASS)) {
	setcookie("HooPass", $ADMIN_COOKIE);
	header ("Location: $SITE_URL$SITE_DIR");
	exit;
}

// Bad login attempt? 
if (($USER != "") || ($PASS != "")) {
	print "Invalid login";
}

print "<HTML><BODY>\n";

print "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"POST\">";
print "<table bgcolor=\"#CCCCCC\">";
print "<tr><td>Login Name:</td><td><input type=\"text\" size=\"10\" name=\"USER\"></td></tr>";
print "<tr><td>Password:</td><td><input type=\"password\" size=\"10\" name=\"PASS\"></td></tr>";
print "<tr><td></td><td align=\"right\"><input type=\"submit\" name=\"LOGIN\" value=\"LOGIN\"></td></tr>";
print "</table></form>\n";
print "<p>PHP ver $ver</p>";

print "</BODY></HTML>\n";

?>



