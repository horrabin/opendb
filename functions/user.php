<?php
/* 	
	Open Media Collectors Database
	Copyright (C) 2001,2006 by Jason Pell

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

include_once("./functions/database.php");
include_once("./functions/logging.php");
include_once("./functions/utils.php");
include_once("./functions/address_type.php");

function is_logged_in_user($uid)
{
	return $uid == get_opendb_session_var('user_id');
}

function is_user_active($uid)
{
	$query = "SELECT active_ind FROM user WHERE user_id = '$uid'";
	$result = db_query($query);
	if ($result && db_num_rows($result)>0)
	{
		$found = db_fetch_assoc($result);
		db_free_result($result);

		if($found['active_ind'] == 'Y')
		{
			return TRUE;
		}
	}
	//else
	return FALSE;
}

/**
	Is this a new user
*/
function is_user_not_activated($uid)
{
	$query = "SELECT active_ind FROM user WHERE user_id = '$uid'";
	$result = db_query($query);
	if ($result && db_num_rows($result)>0)
	{
		$found = db_fetch_assoc($result);
		db_free_result($result);

		if($found['active_ind'] == 'X')
		{
			return TRUE;
		}
	}
	
	//else
	return FALSE;
}

/**
Are there any users awaiting activation.
*/
function is_exist_users_not_activated()
{
	$query = "SELECT 'X' FROM user WHERE active_ind = 'X'";
	$result = db_query($query);
	if ($result && db_num_rows($result)>0)
	{
		db_free_result($result);
		return TRUE;
	}

	//else
	return FALSE;
}

/**
	Checks if uid actually exists.

	Note: UID cannot different in case alone, so all valid user checks will use case insensitive
	comparison.
*/
function is_user_valid($uid)
{
	// Do a pre-emptive check!
	if(strlen($uid)==0)
		return FALSE;
	
	$query = "SELECT 'x' FROM user WHERE LOWER(user_id) = '".strtolower($uid)."'";

	$result = db_query($query);
	if($result && db_num_rows($result)>0)
	{
		$found = db_fetch_assoc($result);
		db_free_result($result);
		return TRUE;
	}
	//else
	return FALSE;
}

//
// This will return the FULL NAME of the matching user id,
// or if this is empty, the $uid will be returned instead.
// It does not check that the uid is valid, too bad if it is not.
//
function fetch_user_name($uid)
{
	$query = "SELECT IF(LENGTH(fullname)>0,fullname,user_id) as fullname FROM user WHERE user_id = '$uid'";
	$result = db_query($query);
	if($result && db_num_rows($result)>0)
	{
		$found = db_fetch_assoc($result);
		db_free_result($result);
		return trim($found['fullname']);
	}
	//else
	return FALSE;
}

function fetch_user_email($uid)
{
	$query = "SELECT email_addr FROM user WHERE user_id = '$uid'";
	$result = db_query($query);
	if ($result && db_num_rows($result)>0)
	{
		$found = db_fetch_assoc($result);
		db_free_result($result);
		return $found['email_addr'];
	}
	//else
	return FALSE;
}

// returns the user's specified language
function fetch_user_language($uid)
{
	$query = "SELECT language FROM user WHERE user_id = '$uid'";
	$result = db_query($query);
	if ($result && db_num_rows($result)>0)
	{
		$found = db_fetch_assoc($result);
		db_free_result($result);
		return $found['language'];
	}
	//else
	return FALSE;
}

// returns the user's specified theme
function fetch_user_theme($uid)
{
	$query = "SELECT theme FROM user WHERE user_id = '$uid'";
	$result = db_query($query);
	if ($result && db_num_rows($result)>0)
	{
		$found = db_fetch_assoc($result);
		db_free_result($result);
		return $found['theme'];
	}
	//else
	return FALSE;
}

function fetch_user_type($uid)
{
	$query = "SELECT if(length(type)>0,type,'N') as type FROM user WHERE user_id = '$uid'";
	$result = db_query($query);
	if ($result && db_num_rows($result)>0)
	{
		$found = db_fetch_assoc($result);
		db_free_result($result);
		return $found['type'];
	}
	
	//else
	return FALSE;
}

//
// This returns the location of the user as a string.  Or FALSE if not found.
// 
function fetch_user_lastvisit($uid)
{
	$query = "SELECT lastvisit FROM user WHERE user_id = '$uid'";
	$result = db_query($query);
	if ($result && db_num_rows($result)>0)
	{
		$found = db_fetch_assoc($result);
		db_free_result($result);
		return $found['lastvisit'];
	}
	//else
	return FALSE;
}

function fetch_role_r($role_name) {
	$query = "SELECT sr.role_name, 
	IFNULL(stlv.value, sr.description) AS description
	FROM s_role sr
	LEFT JOIN s_table_language_var stlv
	ON stlv.language = '".get_opendb_site_language()."' AND
	stlv.tablename = 's_role' AND
	stlv.columnname = 'description' AND
	stlv.key1 = sr.role_name 
	WHERE sr.role_name = '$role_name'";
	
	$result = db_query($query);
	if ($result && db_num_rows($result)>0)
	{
		$found = db_fetch_assoc($result);
		db_free_result($result);
		return $found;
	}

	return FALSE;
}

/**
 * PUBLICACCESS is explicity excluded from the list of available
 * roles.  Its an internal role for use by the system to define
 * the permissions for public access only.
 */
function fetch_user_role_rs()
{
	$query = "SELECT sr.role_name, 
	IFNULL(stlv.value, sr.description) AS description
	FROM s_role sr
	LEFT JOIN s_table_language_var stlv
	ON stlv.language = '".get_opendb_site_language()."' AND
	stlv.tablename = 's_role' AND
	stlv.columnname = 'description' AND
	stlv.key1 = sr.role_name 
	WHERE sr.role_name <> 'PUBLICACCESS'
	ORDER BY role_name";
	
	$result = db_query($query);
	if($result && db_num_rows($result)>0)
		return $result;
	else
		return FALSE;
}

/**
	Return a resultset of all users, ordered by user_id
	user_id,fullname,type,lastvisit.  Will
	replace fullname with user_id if not defined. We only
	want to replace fullname with user_id if empty in this function
	because it is used for readonly operations.  The fetch_user_r
	is used to populate update forms, so the fullname must be 
	included with its current value, empty or not!

	@param $user_types		Specifies a list of usertypes to return in SQL statment.
							It is specified as an array.  Example: array('A','N','B','G')
							
							NOTE: If a set of user_types is specified, it is assumed that
							only ACTIVE user's should be returned, so a active_ind = 'Y'
							clause is appended to the WHERE.

	@param $active_ind      If not NULL, restrict to users with active_ind=$active_ind
	@param $order_by		Specify an order by column. Options are: user_id, fullname,
							location, type, email, lastvisit
* 	@param $exclude_user	A neat way to exclude one user from the list. Does not 
* 							currently support excluding more than one user.
*/
function fetch_user_rs($user_role_permissions=NULL, $active_ind=NULL, $order_by=NULL, $sortorder="ASC", $include_deactivated_users=FALSE, $exclude_user=NULL, $start_index=NULL, $items_per_page=NULL)
{
	// Uses the special 'zero' value lastvisit = 0 to test for default date value.
	$query = "SELECT DISTINCT u.user_id, 
					u.active_ind, 
					IF(LENGTH(u.fullname)>0,u.fullname,u.user_id) AS fullname, 
					u.user_role, 
					IFNULL(stlv.value, sr.description) AS role_description,
					u.language, 
					u.theme, 
					u.email_addr, 
					IF(u.lastvisit <> 0,UNIX_TIMESTAMP(u.lastvisit),'') AS lastvisit 
	FROM (user u, s_role sr";
	
	$user_permissions_clause = format_sql_in_clause($user_role_permissions);
	
	if($user_permissions_clause != NULL)
	{
		$query .= ", s_role_permission srp) ";
	}
	else
	{
		$query .= ") ";
	}
	
	$query .= "LEFT JOIN s_table_language_var stlv
	ON stlv.language = '".get_opendb_site_language()."' AND
	stlv.tablename = 's_role' AND
	stlv.columnname = 'description' AND
	stlv.key1 = sr.role_name 
	WHERE u.user_role = sr.role_name ";
	
	if($user_permissions_clause != NULL)
	{
		$query .= "AND sr.role_name = srp.role_name 
				AND srp.permission_name IN($user_permissions_clause) ";
	}

	if(strlen($exclude_user)>0)
	{
		$query .= "AND u.user_id NOT IN ('$exclude_user') ";
	}

	if($active_ind!=NULL)
	{
		$query .= "AND u.active_ind = '$active_ind' ";
	}
	else if($include_deactivated_users !== TRUE)
	{
		$query .= "AND u.active_ind = 'Y' ";
	}
	
	if(strlen($order_by)==0)
		$order_by = "u.fullname";

	if($order_by === "user_id")
		$query .= " ORDER BY u.user_id $sortorder";
	else if($order_by === "fullname")
		$query .= " ORDER BY u.fullname $sortorder, u.user_id";
	else if($order_by === "role") 
		$query .= " ORDER BY u.user_role $sortorder, u.fullname, u.user_id";
	else if($order_by === "lastvisit")
		$query .= " ORDER BY u.lastvisit $sortorder, u.fullname, u.user_id";

	if(is_numeric($start_index) && is_numeric($items_per_page))
		$query .= ' LIMIT ' .$start_index. ', ' .$items_per_page;

	$result = db_query($query);
	if($result && db_num_rows($result)>0)
		return $result;
	else
		return FALSE;
}

function fetch_user_cnt($user_role_permissions=NULL, $active_ind=NULL, $include_deactivated_users=FALSE, $exclude_user=NULL)
{
	$query = "SELECT COUNT(u.user_id) AS count FROM user u";

	// List all users who can borrow records.
	$user_permissions_clause = format_sql_in_clause($user_role_permissions);
	if($user_permissions_clause != NULL)
	{
		$query .= ", s_role_permission srp ";
		 
		$where_clause = "u.user_role = srp.role_name AND 
						srp.permission_name IN($user_permissions_clause)";
	}
	
	if(strlen($exclude_user)>0)
	{
		if(strlen($where_clause)>0)
			$where_clause .= " AND ";
		$where_clause .= "u.user_id NOT IN ('$exclude_user')";
	}
	
	if($active_ind!=NULL)
	{
	    if(strlen($where_clause)>0)
			$where_clause .= " AND ";
		$where_clause .= "u.active_ind = '$active_ind'";
	}
	else if($include_deactivated_users !== TRUE)
	{
		if(strlen($where_clause)>0)
			$where_clause .= " AND ";
		$where_clause .= " u.active_ind = 'Y' ";
	}
	
	if(strlen($where_clause)>0)
		$query .= " WHERE $where_clause";
	
	$result = db_query($query);
	if($result && db_num_rows($result)>0)
	{
		$found = db_fetch_assoc($result);
		db_free_result($result);
		if ($found!==FALSE)
			return $found['count'];
	}
	
	return FALSE;
}

function fetch_user_r($uid)
{
	$query = "SELECT u.user_id, 
			u.fullname, 
			u.user_role,
			IFNULL(stlv.value, sr.description) AS role_description, 
			u.language, 
			u.theme, 
			u.email_addr, 
			u.lastvisit 
	FROM (user u, s_role sr) 
	LEFT JOIN s_table_language_var stlv
	ON stlv.language = '".get_opendb_site_language()."' AND
	stlv.tablename = 's_role' AND
	stlv.columnname = 'description' AND
	stlv.key1 = sr.role_name
	WHERE u.user_role = sr.role_name 
		AND u.user_id = '".$uid."'";
	
	$result = db_query($query);
	if ($result && db_num_rows($result)>0)
	{
		$found = db_fetch_assoc($result);
		db_free_result($result);
		return $found;
	}

	return FALSE;
}

function validate_user_passwd($uid, $pwd)
{
	$query = "SELECT pwd FROM user WHERE user_id = '$uid'";
	$result = db_query($query);
	if($result && db_num_rows($result)>0)
	{
		$found = db_fetch_assoc($result);
		db_free_result($result);

		if ($found && md5($pwd)==$found['pwd'])
			return TRUE;
	}

	//else
	return FALSE;
}

function deactivate_user($uid)
{
	$query= "UPDATE user SET active_ind = 'N' WHERE user_id = '$uid'";
	$update = db_query($query);

	if($update && db_affected_rows()>0)
	{
		opendb_logger(OPENDB_LOG_INFO, __FILE__, __FUNCTION__, NULL, array($uid));
		return TRUE;
	}
	else
	{
		opendb_logger(OPENDB_LOG_ERROR, __FILE__, __FUNCTION__, db_error(), array($uid));
		return FALSE;
	}
}

function activate_user($uid)
{
	$query= "UPDATE user SET active_ind = 'Y' WHERE user_id = '$uid'";
	$update = db_query($query);

	if($update && db_affected_rows()>0)
	{
		opendb_logger(OPENDB_LOG_INFO, __FILE__, __FUNCTION__, NULL, array($uid));
		return TRUE;
	}
	else
	{
		opendb_logger(OPENDB_LOG_ERROR, __FILE__, __FUNCTION__, db_error(), array($uid));
		return FALSE;
	}
}

//
// This function will not check if the original password matches before allowing a change, it simply
// makes the change.
//
function update_user_passwd($uid, $pwd)
{
	$query = "UPDATE user SET pwd = '".(strlen($pwd)>0?md5($pwd):"")."' WHERE user_id='$uid'";

	$update = db_query($query);
	// We should not treat updates that were not actually updated because value did not change as failures.
	$rows_affected = db_affected_rows();
	if ($rows_affected !== -1)
	{
		if($rows_affected>0)
			opendb_logger(OPENDB_LOG_INFO, __FILE__, __FUNCTION__, NULL, array($uid, '*'));
		return TRUE;
	}
	else
	{
		opendb_logger(OPENDB_LOG_ERROR, __FILE__, __FUNCTION__, db_error(), array($uid, '*'));
		return FALSE;
	}
}

//
// Will update the last visit value of the specified user.
//
function update_user_lastvisit($uid)
{
	$query= "UPDATE user SET lastvisit=now() WHERE user_id = '$uid'";
	$update = db_query($query);

	// Any failure to update this one should be treated as that, this includes a lastvisit
	// being set to the same value, which should not happen.
	if ($update && db_affected_rows()>0)
	{
		opendb_logger(OPENDB_LOG_INFO, __FILE__, __FUNCTION__, NULL, array($uid));
		return TRUE;
	}
	else
	{
		opendb_logger(OPENDB_LOG_ERROR, __FILE__, __FUNCTION__, db_error(), array($uid));
		return FALSE;
	}
}

/**
	Update User.  
	
		Specify FALSE for $user_role to not attempt to update it.
		Specify FALSE to not update theme.
		Specify FALSE to not update language.
*/
function update_user($uid, $fullname, $language, $theme, $email_addr, $user_role)
{
	$query = "UPDATE user SET ".
					"fullname='".addslashes($fullname)."'".
					", email_addr='".addslashes($email_addr)."'".
					($language!==FALSE?", language='".addslashes($language)."'":"").
					($theme!==FALSE?", theme='".addslashes($theme)."'":"").
					($user_role!==FALSE?", user_role='$user_role'":"").
				" WHERE user_id = '$uid'";
	
	$update = db_query($query);
	// We should not treat updates that were not actually updated because value did not change as failures.
	$rows_affected = db_affected_rows();
	if ($update && $rows_affected !== -1)
	{
		if($rows_affected>0)
			opendb_logger(OPENDB_LOG_INFO, __FILE__, __FUNCTION__, NULL, array($uid, $fullname, $language, $theme, $user_role));
		return TRUE;
	}
	else
	{
		opendb_logger(OPENDB_LOG_ERROR, __FILE__, __FUNCTION__, db_error(), array($uid, $fullname, $language, $theme, $user_role));
		return FALSE;
	}
}

//
// Insert a New user.  Does not check if $uid exists already, before doing so.
// This relies on the user_id UNIQUE constraint.  The $uid is the updating user.
// Will do md5($pwd) before inserting...
//
function insert_user($uid, $fullname, $pwd, $user_role, $language, $theme, $email_addr, $active_ind='Y')
{
	$query = "INSERT INTO user (user_id, fullname, pwd, user_role, email_addr, language, theme, active_ind, lastvisit)".
				"VALUES('".$uid."',".
						"'".addslashes($fullname)."',".
						(strlen($pwd)>0? ("'".md5($pwd)."'") :"NULL").",".
						"'".$user_role."',".
						"'".addslashes($email_addr)."',".
						"'".addslashes($language)."',".
						"'".addslashes($theme)."',".
						"'".$active_ind."',".
						"'0000-00-00 00:00:00')";

	$insert = db_query($query);
	if($insert && db_affected_rows()>0)
	{
		opendb_logger(OPENDB_LOG_INFO, __FILE__, __FUNCTION__, NULL, array($uid, $fullname, '*', $user_role, $language, $theme, $email_addr, $active_ind));
		return TRUE;
	}
	else
	{
		opendb_logger(OPENDB_LOG_ERROR, __FILE__, __FUNCTION__, db_error(), array($uid, $fullname, '*', $user_role, $email_addr, $language, $theme, $email_addr, $active_ind));
		return FALSE;
	}
}

//
// Delete user.  Assumes validation has already been performed.
//
function delete_user($uid)
{
	$query= "DELETE FROM user WHERE user_id = '$uid'";
	$delete = db_query($query);
	if (db_affected_rows()>0)
	{
		opendb_logger(OPENDB_LOG_INFO, __FILE__, __FUNCTION__, NULL, array($uid));
		return TRUE;
	}
	else
	{
		opendb_logger(OPENDB_LOG_ERROR, __FILE__, __FUNCTION__, db_error(), array($uid));
		return FALSE;
	}
}

//---------------------------------------------------
// Utilities
//---------------------------------------------------

// Randomly generates a password of $length characters
function generate_password($length)
{
	// length should be at minimum 4 characters
	if($length<4) $length=5;

	// seed random generator with system time
	srand((double)microtime()*1000000); 

	// make a string containing acceptable characters 
	$symbols = "abcdefghijklmnopqrstuvwxyz" 
		  ."ABCDEFGHIJKLMNOPQRSTUVWXYZ" 
                  ."0123456789"; 

	// loop for $length 
	for($ix = 0; $ix < $length; $ix++) 
	{ 
		// pick random symbol
		$randomNum   = rand(0,strlen($symbols)); 
		$randomChar  = substr($symbols,$randomNum,1); 

		// append random symbol to password
		$randomPass .= $randomChar; 
	} 

	// now returns our random password
	return $randomPass; 
}
?>
