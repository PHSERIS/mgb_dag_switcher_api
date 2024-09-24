<?php
$modulePid = $project_id;

global $format, $returnFormat, $post;

// Trying to run module using wrong pid, could allow access of module on non-enabled projects
//if($post["projectid"] != $modulePid || $post["projectid"] == "") {
	//die();
//}

$module->initMgbDagSwitcherApi($modulePid);


echo 'dag test: ';
echo '[';
echo $modulePid;
echo ']';

exit;

