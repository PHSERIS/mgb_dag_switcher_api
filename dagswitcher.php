<?php

// API Call:  dagswitcher

/* 

* API usage:
    - POST
    - form-data

* body: 
    - token: API Token
    - data: 
        - GetDagsByProject: NA, 
        - GetDagsByUser: user, 
        - AddUserToDag: user,dagid;  repeat, ( using semi colon to separate pairs: user,dagID;user,dagID;.... OR user,dagID; OR user,dagID )
        - RemoveUserFromDag: user,dagid;  repeat
    - command: 
        - GetDagsByProject
        - GetDagsByUser
        - AddUserToDag
        - RemoveUserFromDag

* params: 
    - prefix: mgb_dag_switcher_api
    - type: module
    - NOAUTH
    - page: dagswitcher
    - pid: ( PER PROJECT ID )


* Commands:
    - GetDagsByProject
    - GetDagsByUser
    - AddUserToDag
    - RemoveUserFromDag

* GetDagsByProject 
    - get the full project listing DAG IDs and DAG names
    - ( command ) GetDagsByProject
    - lists DAG IDs and DAG Names
    - RETURNS: JSON: daglist  key = DAG ID, val = DAG Name
 
* GetDagsByUser 
    - list user and DAG IDs
    - ( command ) GetDagsByUser
    - ( find a user and what active DAG enabled )
    - given user name
    - get User, DAG_IDs
    - RETURNS: JSON: user: user name, dags: DAG IDs list

* AddUserToDag  
    - ADD a user to DAG
    - ( command ) AddUserToDag
    - ( data )  formatted:  user,dagid;  repeat
    - changes the users dagID list, enabling, as per DAGSwitcher
    - RETURNS: JSON: user dag list: [user name, dags: DAG IDs list]

* RemoveUserFromDag  
    - REMOVE a user from DAG
    - ( command ) RemoveUserFromDag
    - ( data )  formatted:  user,dagid;  repeat
    - changes the users dagID list, disabling, as per DAGSwitcher
    - RETURNS: JSON: user dag list: [user name, dags: DAG IDs list]

*/

use \DAGSwitcher;

global $format, $returnFormat, $post, $lang;

$changelist = 'none';
$command = 'none';
$getFlag = false;
$dagListFlag = false;
$userListFlag = false;
$userList = array();

$modulePid = $project_id;


$module->checkApiToken();  // make sure we have valid token

// Trying to run module using wrong pid, could allow access of module on non-enabled projects
if($post["projectid"] != $modulePid || $post["projectid"] == "") {
    die();
}

// make a REDCap DAGSwitcher class to handle its functions, namely: GetDagsByUser, saveUserDAG
$dagswitcher = new DAGSwitcher();

// get the project dags list
$projDags = array(0 => $lang['data_access_groups_ajax_23']) + (array)REDCap::getGroupNames();

// get our Data
if (isset($_POST['data'])) {
    $changelist = trim($_POST['data'], '"');    
}

// get our Command
if (isset($_POST['command'])) {
    $command = $_POST['command'];    
}

$module->log('projectid: ' . $modulePid . ' command:' . $command . ' data:' . $changelist);

// limit our commands and protect execution
switch ($command)
{
    case 'AddUserToDag':
        $enabled = true;
        break;
        
    case 'RemoveUserFromDag':
        $enabled = false;
        break;
        
    case 'GetDagsByProject':
        //exit;
        $getFlag = true;
        $dagListFlag = true;
        break;
        
    case 'GetDagsByUser':
        $getFlag = true;
        $userListFlag = true;
        break;
        
    case '':  // stub  also for any blank names
        exit;
        break;
        
    default:
        exit;
        break;
}

// peel out the users and data
$tochange = $module->parseChanges($changelist);

// for just GETTING info:  DAG List, or User DAG IDs
if ($getFlag) {

    if ($dagListFlag) {
        $mydata['daglist'] = $projDags;
        
    }
    if ($userListFlag) {
        $user = trim($changelist, ';'); 
        $mydata['user'] = $user;
        $userdags = $dagswitcher->getUserDAGs($user);
        $mydata['dags'] = $userdags[$user];
    }

    $module->showJson($mydata, true);

    exit;
}

// Process users and dags to add or remove, flagged by $enabled
if ($tochange) {
    foreach ($tochange as $key => $val) {
        $person = $module->getChangeItem($val);

        $user = (isset($person[0]) ? $person[0] : '');  // 0 = user   lisasimpson,29
        
        if ($user == '') {
            continue;
        }

        $dags = (isset($person[1]) ? $person[1] : '');  // 1 = dag ID   lisasimpson,29

        if ($dags == '') {
            continue;
        }

        $userList[] = $user;
        
        $dagswitcher->saveUserDAG($user, $dags, $enabled);
    }
    
}

// give back results
//
if ($userList) {
    foreach ($userList as $user) {
        if ($user == '') {
            continue;
        }

        $userdags = $dagswitcher->getUserDAGs($user);
        $data[$user]['dags'] = $userdags[$user];       
    }
}

$module->showJson($data, true);

exit;


// *****
// *****
//  End Process
// *****
// *****


// ********** ********** ********** **********
// ********** ********** ********** **********
// ********** ********** ********** **********
// END FILE
// ********** ********** ********** **********
// ********** ********** ********** **********
// ********** ********** ********** **********
