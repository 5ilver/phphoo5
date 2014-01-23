<?php

// phphoo5 - a yahoo-like link directory written for PHP5
// AGM
//
// Orginal:
// Copyright (C) 1999/2001 Rolf V. Ostergaard http://www.cable-modems.org/phpHoo/
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

// Refer to http://www.webreference.com/perl/xhoo/php1/ for phphoo, the
// first cut done by CDI.

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


// Check cookie to see if we are in admin modeT
if($HooPass == $ADMIN_COOKIE) {
	// should we delete the cookie and start?
	if ("$exit_admin"=="true") {
		setcookie("HooPass", "");
		header ("Location: $SITE_URL$SITE_DIR");
		exit;
	}

	// Or offer entry as USER or ADMIN?
	print "<HTML><BODY>\n";

	print "<p>You are already ADMIN - Your options are:<UL>";
	print "<LI><a href=\"$SITE_URL$SITE_DIR\">Enter as ADMIN</a></LI>";
	print "<LI><a href=\"?exit_admin=1\">Enter as USER</a></LI>";
	print "</UL></P>\n";

	print "<p>PHP ver $ver</p>";
	print "</BODY></HTML>\n";
	exit;
}

// Check to see if we are being posted a set of USER/PASS
if (($USER == $ADMIN_USER) && ($PASS == $ADMIN_PASS)) {
	setcookie("HooPass", $ADMIN_COOKIE);
	header ("Location: $SITE_URL$SITE_DIR");
	exit;
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



