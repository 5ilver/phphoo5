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

Class MySQL
{
    var $DBASE;        // Name of database to use
    var $USER;         // Database username
    var $PASS;         // Database R/W password
    var $SERVER;       // Server name
    var $CAT_TBL;      // MySQL table name for the categories table
    var $LNK_TBL;      // MySQL table name for the links table

	var $CONN = "";
	var $TRAIL = array();

    // constructor:
    function MySQL()
    {
        global $SQL_DBASE;
        global $SQL_USER;
        global $SQL_PASS;
        global $SQL_SERVER;
        global $SQL_CAT_TBL;
        global $SQL_LNK_TBL;

        $this->DBASE = $SQL_DBASE;
        $this->USER  = $SQL_USER;
        $this->PASS  = $SQL_PASS;
        $this->SERVER = $SQL_SERVER;
        $this->CAT_TBL = $SQL_CAT_TBL;
        $this->LNK_TBL = $SQL_LNK_TBL;
    }

	function error($text)
	{
		$no = mysql_errno();
		$msg = mysql_error();
		echo "[$text] ( $no : $msg )<BR>\n";
		exit;
	}

	function init ()
	{
		$user = $this->USER;
		$pass = $this->PASS;
		$server = $this->SERVER;
		$dbase = $this->DBASE;

		$conn = mysql_connect($server,$user,$pass);
		if(!$conn) {
			$this->error("Connection attempt failed");
		}
		if(!mysql_select_db($dbase,$conn)) {
			$this->error("Dbase Select failed");
		}
		$this->CONN = $conn;
		return true;
	}

//	*****************************************************************
//						MySQL Specific methods
//	*****************************************************************


	function select ($sql="", $column="")
	{
		if(empty($sql)) { return false; }
		if(!preg_match("/^SELECT/",$sql))
		{
			echo "<H2>Wrong select function silly! $sql</H2>\n";
			return false;
		}
		if(empty($this->CONN)) { return false; }
		$conn = $this->CONN;
		$results = mysql_query($sql,$conn);
		if( (!$results) or (empty($results)) ) {
			mysql_free_result($results);
			return false;
		}
		$count = 0;
		$data = array();
		while ( $row = mysql_fetch_array($results))
		{
			$data[$count] = $row;
			$count++;
		}
		mysql_free_result($results);
		return $data;
	}

	function insert ($sql="")
	{
		if(empty($sql)) { return false; }
		if(!preg_match("/^INSERT/",$sql))
		{
			echo "<H2>Wrong insert function silly! $sql</H2>\n";
			return false;
		}
		if(empty($this->CONN))
		{
			echo "<H2>No connection!</H2>\n";
			return false;
		}
		$conn = $this->CONN;
		$results = mysql_query($sql,$conn);
		if(!$results)
		{
			echo "<H2>No results!</H2>\n";
			echo mysql_errno().":  ".mysql_error()."<P>";
			return false;
		}
		$results = mysql_insert_id();
		return $results;
	}

	function sql_query ($sql="")
	{
		if(empty($sql)) { return false; }
		if(empty($this->CONN)) { return false; }
		$conn = $this->CONN;
		$results = mysql_query($sql,$conn);
		if(!$results)
		{
			echo "<H2>Query went bad!</H2>\n";
			echo mysql_errno().":  ".mysql_error()."<P>";
			return false;
		}
		return $results;
	}

	function sql_cnt_query ($sql="")
	{
		if(empty($sql)) { return false; }
		if(empty($this->CONN)) { return false; }
		$conn = $this->CONN;
		$results = mysql_query($sql,$conn);
		if( (!$results) or (empty($results)) ) {
			mysql_free_result($results);
			return false;
		}
		$count = 0;
		$data = array();
		while ( $row = mysql_fetch_array($results))
		{
			$data[$count] = $row;
			$count++;
		}
		mysql_free_result($results);
		return $data[0][0];
	}


//	*****************************************************************
//						phpHoo Specific Methods
//	*****************************************************************

	function get_Cats ($CatParent= "")
	{
		if(empty($CatParent) || ($CatParent == "0"))
		{
			$CatParent = "IS NULL";
		} else {
			$CatParent = "= $CatParent";
		}
		$sql = "SELECT CatID,CatName FROM $this->CAT_TBL WHERE CatParent $CatParent ORDER BY CatName";
		$results = $this->select($sql);
		return $results;
	}

//	The primer for a recursive query
	function get_ParentsInt($CatID="")
	{
		if(empty($CatID) || ($CatID == "0")) { return false; }
		unset($this->TRAIL);
		$this->TRAIL = array();
		$this->get_Parents($CatID);
	}

//	Use get_ParentsInt(), NOT this one!
//	The power of recursive queries

	function get_Parents ($CatID="")
	{
		if( (empty($CatID)) or ("$CatID" == "NULL")) { return false; }
		$sql = "SELECT CatID,CatParent,CatName from $this->CAT_TBL where CatID = $CatID";

		$conn = $this->CONN;
		$results = mysql_query($sql,$conn);
		if( (!$results) or (empty($results)) ) {
			mysql_free_result($results);
			return false;
		}

		while ( $row = mysql_fetch_array($results))
		{
			$trail = $this->TRAIL;
			$count = count($trail);
			$trail[$count] = $row;
			$this->TRAIL = $trail;
			$id = $row["CatParent"];
			$this->get_Parents($id);
		}
		return true;
	}

	function get_CatIDFromName($CatName="")
	{
		if(empty($CatName)) { return false; }
		$sql = "SELECT CatID from $this->CAT_TBL where CatName = '$CatName'";
		$results = $this->select($sql);
		if(!empty($results))
		{
			$results = $results[0]["CatID"];
		}
		return $results;
	}

	function get_CatNames($CatID="")
	{
		if($CatID == 0) { return "Top"; }
		$single = false;
		if(!empty($CatID))
		{
			$single = true;
			$CatID = "WHERE CatID = $CatID";
		}
		$sql = "SELECT CatName from $this->CAT_TBL $CatID";
		$results = $this->select($sql);
		if($single)
		{
			if(!empty($results))
			{
				$results = $results[0]["CatName"];
			}
		}
		return $results;
	}

	function get_AllCats()
	{
        $cat = $this->CAT_TBL;
		$sql = "SELECT CatID,CatName FROM $cat";
		$results = $this->select($sql);
		return $results;
	}

	function get_Links($CatID = "")
	{
		if(empty($CatID))
		{
			$CatID = "= 0";
		} else {
			$CatID = "= $CatID";
		}

		$sql = "SELECT * FROM $this->LNK_TBL WHERE  (Approved != 0) AND CatID $CatID ORDER BY LinkName";
		$results = $this->select($sql);
		return $results;
	}

	function get_OneLink($LinkID = "")
	{
		if(empty($LinkID)) {
			$err_msg = "No LinkID given.";
			return false;
		}

		$sql = "SELECT * FROM $this->LNK_TBL WHERE LinkID=$LinkID";
		$results = $this->select($sql);
		return $results;
	}

	function get_Submissions()
	{
		$sql = "SELECT * FROM $this->LNK_TBL WHERE  (Approved = 0) ORDER BY Url";
		$results = $this->select($sql);
		return $results;
	}

	function get_CatFromLink($LinkID="")
	{
		if(empty($LinkID)) { return false; }
		$sql = "SELECT CatID FROM $this->LNK_TBL WHERE LinkID = $LinkID";
		$results = $this->select($sql);
		if(!empty($results))
		{
			$results = $results[0]["CatID"];
		}
		return $results;
	}

	// Check if a CatID is indeed in the table of valid categories
	function isValidCatID($CatID="")
	{
		if ($CatID=="") { return false; }
		if ($CatID=="0") { return true; }
		$sql = "SELECT * FROM $this->CAT_TBL WHERE CatID = $CatID";
		$results = $this->select($sql);
		if (empty($results)) { return false; }
		return true;
	}

	function search ($keywords = "")
	{
		if(empty($keywords)) { return false; }

		$DEBUG = ""; // set DEBUG == "\n" to see this query

		$keywords = trim(urldecode($keywords));
		$keywords = ereg_replace("([    ]+)"," ",$keywords);

		if(!ereg(" ",$keywords))
		{
			// Only 1 keyword
			$KeyWords[0] = "$keywords";
		} else {
			$KeyWords = explode(" ",$keywords);
		}

		$sql = "SELECT DISTINCT LinkID,CatID,Url,LinkName,Description FROM $this->LNK_TBL WHERE (Approved != 0) AND ( $DEBUG ";
		$count = count($KeyWords);

		if( $count == 1)
		{
			$single = $KeyWords[0];
			$sql .= " (Description LIKE '%$single%') OR (LinkName LIKE '%$single%') OR (Url LIKE '%$single%') ) ORDER BY LinkName $DEBUG ";
		} else {
			$ticker = 0;
			while ( list ($key,$word) = each ($KeyWords) )
			{
				$ticker++;
				if(!empty($word))
				{
					if($ticker != $count)
					{
						$sql .= " ( (Description LIKE '%$word%') OR (LinkName LIKE '%$word%') OR (Url LIKE '%$word%') ) OR $DEBUG ";
					} else {
						// Last condition, omit the trailing OR
						$sql .= " ( (Description LIKE '%$word%') OR (LinkName LIKE '%$word%') OR (Url LIKE '%$word%') ) $DEBUG ";
					}
				}
			}
			$sql .= " ) ORDER BY LinkName $DEBUG";
		}

		if(!empty($DEBUG)) { echo "<PRE>$sql\nTicker [$ticker]\nCount [$count]</PRE>\n"; }

		$results = $this->select($sql);
		return $results;
	}

	function suggest ($postData="",&$err_msg)
	{
		$err_msg="";
		global $AUTOAPPROVE;

		if( (empty($postData)) or (!is_array($postData)) ) {
			$err_msg = "No data submitted or not an array of data";
			return false;
		}

		$CatID = $_POST["CatID"];
		$Url = addslashes($_POST["Url"]);
		$Description = addslashes($_POST["Description"]);
		$LinkName = addslashes($_POST["LinkName"]);
		$SubmitName = addslashes($_POST["SubmitName"]);
		$SubmitEmail = addslashes($_POST["SubmitEmail"]);
		$SubmitDate = time();

		if(!$this->isValidCatID($CatID)) {
			$err_msg = "Invalid category.";
			return false;
		}
		if(empty($Url)) {
			$err_msg = "No URL specified.";
			return false;
		}
		if(empty($Description)) {
			$err_msg = "No description given.";
			return false;
		}
		if(empty($LinkName)) {
			$err_msg = "No link name given.";
			return false;
		}
		if(empty($SubmitName)) {
			$err_msg = "No name given.";
			return false;
		}
		if(empty($SubmitEmail)) {
			if ($REQUIRE_SUBMIT_EMAIL) {
				$err_msg = "No email address given.";
				return false;
			} else {
				$SubmitEmail = "anonymous";
			}
		}

		$Approved = 0;
		if("$AUTOAPPROVE" == "on") { $Approved = 1; }

		$sql = "INSERT INTO $this->LNK_TBL ";
		$sql .= "(CatID,Url,LinkName,Description,SubmitName,SubmitEmail,SubmitDate,Approved) ";
		$sql .= "values ";
		$sql .= "($CatID,'$Url','$LinkName','$Description','$SubmitName','$SubmitEmail',$SubmitDate,$Approved) ";
		$results = $this->insert($sql);

		// Set cookie to remember name and email
		setcookie("UserName", $SubmitName,time()+3600*24*30*6);
		setcookie("UserEmail", $SubmitEmail,time()+3600*24*30*6);

		return $results;
	}

	function update ($postData="",&$err_msg)
	{
		$err_msg="";

		if( (empty($postData)) or (!is_array($postData)) ) {
			$err_msg = "No data submitted or not an array of data";
			return false;
		}

		$LinkID = $_POST["LinkID"];
		$CatID = $_POST["CatID"];
		$Url = addslashes($_POST["Url"]);
		$Description = addslashes($_POST["Description"]);
		$LinkName = addslashes($_POST["LinkName"]);
		$SubmitName = addslashes($_POST["SubmitName"]);
		$SubmitEmail = addslashes($_POST["SubmitEmail"]);
		$SubmitDate = time();

		if(!$this->isValidCatID($CatID)) {
			$err_msg = "Invalid category.";
			return false;
		}
		if(empty($Url)) {
			$err_msg = "No URL specified.";
			return false;
		}
		if(empty($Description)) {
			$err_msg = "No description given.";
			return false;
		}
		if(empty($LinkName)) {
			$err_msg = "No link name given.";
			return false;
		}
		if(empty($SubmitName)) {
			$err_msg = "No name given.";
			return false;
		}
		if(empty($SubmitEmail)) {
			if ($REQUIRE_SUBMIT_EMAIL) {
				$err_msg = "No email address given.";
				return false;
			} else {
				$SubmitEmail = "anonymous";
			}
		}

		$Approved = 0;
		if($this->AUTOAPPROVE) { $Approved = 1; }

		$sql = "UPDATE $this->LNK_TBL SET ";
		$sql .= "CatID=$CatID,";
		$sql .= "Url='$Url',";
		$sql .= "LinkName='$LinkName',";
		$sql .= "Description='$Description',";
		$sql .= "SubmitName='$SubmitName',";
		$sql .= "SubmitEmail='$SubmitEmail',";
		$sql .= "SubmitDate=$SubmitDate,";
		$sql .= "Approved=$Approved";
		$sql .= " WHERE LinkID='$LinkID'";
		$results = $this->sql_query($sql);
		return $results;
	}

	function approve ($LinkID="",&$err_msg)
	{
		$err_msg="";

		if(empty($LinkID)) {
			$err_msg = "No LinkID given.";
			return false;
		}

		$sql = "UPDATE $this->LNK_TBL SET Approved=1 WHERE LinkID='$LinkID'";
		$results = $this->sql_query($sql);
		return $results;
	}

	function disapprove ($LinkID="",&$err_msg)
	{
		$err_msg="";

		if(empty($LinkID)) {
			$err_msg = "No LinkID given.";
			return false;
		}

		$sql = "UPDATE $this->LNK_TBL SET Approved=0 WHERE LinkID='$LinkID'";
		$results = $this->sql_query($sql);
		return $results;
	}

	function delete_link ($LinkID="",&$err_msg)
	{
		$err_msg="";

		if(empty($LinkID)) {
			$err_msg = "No LinkID given.";
			return false;
		}

		$sql = "DELETE FROM $this->LNK_TBL WHERE LinkID='$LinkID'";
		$results = $this->sql_query($sql);
		return $results;
	}

	function add_cat ($postData="",&$err_msg)
	{
		$err_msg="";

		if( (empty($postData)) or (!is_array($postData)) ) {
			$err_msg = "No data submitted or not an array of data";
			return false;
		}

		$CatParent = $_POST["CatID"];
		if (empty($CatParent) || ($CatParent == "0") || ($CatParent == "top")) {
			$CatParent = "NULL";
		}
		$CatName = addslashes($_POST["NewCatName"]);

		if(empty($CatName)) {
			$err_msg = "No new category name given.";
			return false;
		}

		$sql = "INSERT INTO $this->CAT_TBL ";
		$sql .= "(CatName,CatParent) ";
		$sql .= "values ";
		$sql .= "('$CatName',$CatParent) ";
		$results = $this->insert($sql);
		return $results;
	}

	function get_approved_cnt ()
	{
		$sql = "select count(*) from $this->LNK_TBL where approved=1 ";
		$results = $this->sql_cnt_query($sql);
		return $results;
	}

	function get_not_approved_cnt ()
	{
		$sql = "select count(*) from $this->LNK_TBL where approved=0 ";
		$results = $this->sql_cnt_query($sql);
		return $results;
	}

	// Return number of approved links in a specific category
	function get_LinksInCat_cnt($CatID="")
	{
		if(empty($CatID)) { return 0; }
		$sql = "select count(*) from $this->LNK_TBL where CatID=$CatID and approved=1";
		$results = $this->sql_cnt_query($sql);
		return $results;
	}

	// Return number of subcategories in a specific category
	function get_CatsInCat_cnt($CatID="")
	{
		if(empty($CatID)) { return 0; }
		$sql = "select count(*) from $this->CAT_TBL where CatParent=$CatID ";
		$results = $this->sql_cnt_query($sql);
		return $results;
	}

	// Watch out: another recursive query!
	// Returns the total number of links in the category and all subcategories thereof.
	function get_TotalLinksInCat_cnt($CatID="")
	{
		if(empty($CatID) || ($CatID == "0")) { return "0"; }
		$sum = 0;

		// Sum all subcategories from here

		$sql = "SELECT * from $this->CAT_TBL where CatParent = $CatID";
		$conn = $this->CONN;
		$results = mysql_query($sql,$conn);
		if( (!$results) or (empty($results)) ) {
			mysql_free_result($results);
			return ($sum);
		}

		while ($row = mysql_fetch_array($results))
		{
			$id = $row["CatID"];
			$sum = $sum + $this->get_TotalLinksInCat_cnt($id);
		}

		// Then add this category

		$sum = $sum + $this->get_LinksInCat_cnt($CatID);

		return ($sum);
	}

}	//	End Class
?>
