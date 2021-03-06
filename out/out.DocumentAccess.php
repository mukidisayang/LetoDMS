<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2006-2008 Malcolm Cowe
//
//    This program is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program; if not, write to the Free Software
//    Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

include("../inc/inc.Settings.php");
include("../inc/inc.Utils.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

function printAccessModeSelection($defMode) {
	print "<select name=\"mode\">\n";
	print "\t<option value=\"".M_NONE."\"" . (($defMode == M_NONE) ? " selected" : "") . ">" . getMLText("access_mode_none") . "</option>\n";
	print "\t<option value=\"".M_READ."\"" . (($defMode == M_READ) ? " selected" : "") . ">" . getMLText("access_mode_read") . "</option>\n";
	print "\t<option value=\"".M_READWRITE."\"" . (($defMode == M_READWRITE) ? " selected" : "") . ">" . getMLText("access_mode_readwrite") . "</option>\n";
	print "\t<option value=\"".M_ALL."\"" . (($defMode == M_ALL) ? " selected" : "") . ">" . getMLText("access_mode_all") . "</option>\n";
	print "</select>\n";
}

if (!isset($_GET["documentid"]) || !is_numeric($_GET["documentid"]) || intval($_GET["documentid"])<1) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

$documentid = $_GET["documentid"];
$document = $dms->getDocument($documentid);

if (!is_object($document)) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

$folder = $document->getFolder();
$docPathHTML = getFolderPathHTML($folder, true). " / <a href=\"../out/out.ViewDocument.php?documentid=".$documentid."\">".$document->getName()."</a>";

if ($document->getAccessMode($user) < M_ALL) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
}

UI::htmlStartPage(getMLText("document_title", array("documentname" => $document->getName())));
UI::globalNavigation($folder);
UI::pageNavigation($docPathHTML, "view_document");

?>

<script language="JavaScript">
function checkForm()
{
	msg = "";
	if ((document.form1.userid.options[document.form1.userid.selectedIndex].value == -1) && 
		(document.form1.groupid.options[document.form1.groupid.selectedIndex].value == -1))
			msg += "<?php printMLText("js_select_user_or_group");?>\n";
	if (msg != "")
	{
		alert(msg);
		return false;
	}
	else
		return true;
}
</script>

<?php
$allUsers = $dms->getAllUsers();

UI::contentHeading(getMLText("edit_document_access"));
UI::contentContainerStart();

if ($user->isAdmin()) {

	UI::contentSubHeading(getMLText("set_owner"));
?>
	<form action="../op/op.DocumentAccess.php">
	<input type="Hidden" name="action" value="setowner">
	<input type="Hidden" name="documentid" value="<?php print $documentid;?>">
	<?php printMLText("owner");?> : <select name="ownerid">
	<?php
	$owner = $document->getOwner();
	foreach ($allUsers as $currUser) {
		if ($currUser->isGuest())
			continue;
		print "<option value=\"".$currUser->getID()."\"";
		if ($currUser->getID() == $owner->getID())
			print " selected";
		print ">" . $currUser->getFullname() . "</option>\n";
	}
	?>
	</select>
	<input type="Submit" value="<?php printMLText("save")?>">
	</form>
<?php

}
UI::contentSubHeading(getMLText("access_inheritance"));

if ($document->inheritsAccess()) {
	printMLText("inherits_access_msg", array(
		"copyurl" => "../op/op.DocumentAccess.php?documentid=".$documentid."&action=notinherit&mode=copy", 
		"emptyurl" => "../op/op.DocumentAccess.php?documentid=".$documentid."&action=notinherit&mode=empty"));
	UI::contentContainerEnd();
	UI::htmlEndPage();
	exit();
}
printMLText("does_not_inherit_access_msg", array("inheriturl" => "../op/op.DocumentAccess.php?documentid=".$documentid."&action=inherit"));

$accessList = $document->getAccessList();

UI::contentSubHeading(getMLText("default_access"));

?>
<form action="../op/op.DocumentAccess.php">
	<input type="Hidden" name="documentid" value="<?php print $documentid;?>">
	<input type="Hidden" name="action" value="setdefault">
	<?php printAccessModeSelection($document->getDefaultAccess()); ?>
	<input type="Submit" value="<?php printMLText("save");?>">
</form>

<?php

UI::contentSubHeading(getMLText("edit_existing_access"));

if (count($accessList["users"]) != 0 || count($accessList["groups"]) != 0) {

	print "<table class=\"defaultView\">";

	foreach ($accessList["users"] as $userAccess) {
		$userObj = $userAccess->getUser();
		print "<form action=\"../op/op.DocumentAccess.php\">\n";
		print "<input type=\"Hidden\" name=\"documentid\" value=\"".$documentid."\">\n";
		print "<input type=\"Hidden\" name=\"action\" value=\"editaccess\">\n";
		print "<input type=\"Hidden\" name=\"userid\" value=\"".$userObj->getID()."\">\n";
		print "<tr>\n";
		print "<td><img src=\"images/usericon.gif\" class=\"mimeicon\"></td>\n";
		print "<td>". $userObj->getFullName() . "</td>\n";
		print "<td>\n";
		printAccessModeSelection($userAccess->getMode());
		print "</td>\n";
		print "<td><span class=\"actions\">\n";
		print "<input type=\"Image\" class=\"mimeicon\" src=\"images/save.gif\">".getMLText("save")." ";
		print "<a href=\"../op/op.DocumentAccess.php?documentid=".$documentid."&action=delaccess&userid=".$userObj->getID()."\"><img src=\"images/del.gif\" class=\"mimeicon\"></a>".getMLText("delete");
		print "</span></td></tr>\n";
		print "</form>\n";
	}

	foreach ($accessList["groups"] as $groupAccess) {
		$groupObj = $groupAccess->getGroup();
		$mode = $groupAccess->getMode();
		print "<form action=\"../op/op.DocumentAccess.php\">";
		print "<input type=\"Hidden\" name=\"documentid\" value=\"".$documentid."\">";
		print "<input type=\"Hidden\" name=\"action\" value=\"editaccess\">";
		print "<input type=\"Hidden\" name=\"groupid\" value=\"".$groupObj->getID()."\">";
		print "<tr>";
		print "<td><img src=\"images/groupicon.gif\" class=\"mimeicon\"></td>";
		print "<td>". $groupObj->getName() . "</td>";
		print "<td>";
		printAccessModeSelection($groupAccess->getMode());
		print "</td>\n";
		print "<td><span class=\"actions\">\n";
		print "<input type=\"Image\" class=\"mimeicon\" src=\"images/save.gif\">".getMLText("save")." ";
		print "<a href=\"../op/op.DocumentAccess.php?documentid=".$documentid."&action=delaccess&groupid=".$groupObj->getID()."\"><img src=\"images/del.gif\" class=\"mimeicon\"></a>".getMLText("delete");
		print "</span></td></tr>";
		print "</form>";
	}
	
	print "</table><br>";
}
?>
<form action="../op/op.DocumentAccess.php" name="form1" onsubmit="return checkForm();">
<input type="Hidden" name="documentid" value="<?php print $documentid?>">
<input type="Hidden" name="action" value="addaccess">
<table>
<tr>
<td><?php printMLText("user");?>:</td>
<td>
<select name="userid">
<option value="-1"><?php printMLText("select_one");?></option>
<?php
foreach ($allUsers as $userObj) {
	if ($userObj->isGuest()) {
		continue;
	}
	print "<option value=\"".$userObj->getID()."\">" . $userObj->getFullName() . "</option>\n";
}
?>
</select>
</td>
</tr>
<tr>
<td><?php printMLText("group");?>:</td>
<td>
<select name="groupid">
<option value="-1"><?php printMLText("select_one");?></option>
<?php
$allGroups = $dms->getAllGroups();
foreach ($allGroups as $groupObj) {
	print "<option value=\"".$groupObj->getID()."\">" . $groupObj->getName() . "</option>\n";
}
?>
</select>
</td>
</tr>
<tr>
<td><?php printMLText("access_mode");?>:</td>
<td>
<?php
printAccessModeSelection(M_READ);
?>
</td>
</tr>
<tr>
<td colspan="2"><input type="Submit" value="<?php printMLText("add");?>"></td>
</tr>
</table>
</form>

<?php
UI::contentContainerEnd();
UI::htmlEndPage();
?>
