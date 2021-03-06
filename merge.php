<?php

set_time_limit(0);

ignore_user_abort(true);

require_once('includes.php');

$results = array();

$accounts = dbFetch(dbQuery("SELECT `name` FROM `accounts` GROUP BY `name` HAVING COUNT(*) > 1"));

foreach($accounts as $account){
	
	$name = $account['name'];
	
	$rows = dbFetch(dbQuery(sprintf("SELECT `code` FROM `accounts` WHERE `name` = '%s' AND `code` IS NOT NULL LIMIT 1", $name)));
	
	dbQuery(sprintf("DELETE FROM `accounts` WHERE `name` = '%s' AND `creationTime` IS NOT NULL", $name));
	$results['delete'][$name][] = dbAffectedRows();
	
	dbQuery(sprintf("UPDATE IGNORE `accounts` SET `code` = %d, `creationTime` = UNIX_TIMESTAMP(date_entered) WHERE `name` = '%s' AND `creationTime` IS NULL", $rows[0]['code'], $name, $name));
	$results['update'][$name][] = dbAffectedRows();
		
}

print json_encode($results);