<?php
/// A sample piece of code.
/**
 * MgbDagSwitcherApiExternalModule
 *  - CLASS for some features.
 *  
 *  
 *  - MGB - Mass General Brigham RISC. 
 * @author David L. Heskett
 * @version1.0
 * @date20240726
 * @copyright &copy; 2022 Mass General Brigham, RISC, Research Information Science and Computing <a href="https://rc.partners.org//">MGB RISC\</a>  <a href="https://redcap.partners.org/redcap/">redcap.partners.org</a> 
 */
 
namespace MGB\MgbDagSwitcherApiExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

use \HtmlPage;
use \Logging;

class MgbDagSwitcherApiExternalModule extends AbstractExternalModule
{
    public $debugLogFlag;
    public $debug_mode_log;
    public $debug_mode_project;

    private $projectId;

    CONST NAME_IDENTIFIER = 'DagSwitcherApi';

    CONST VIEW_CMD_PROJECT = 'project';
    CONST VIEW_CMD_CONTROL = 'control';
    CONST VIEW_CMD_SYSTEM  = 'system';
    CONST VIEW_CMD_DEFAULT = '';
    
    // **********************************************************************   
    // **********************************************************************   
    // **********************************************************************   

    /**
     * constructor - set up object.
     */
    public function __construct($pid = null) 
    {
        parent::__construct();
        // Other code to run when object is instantiated

        $this->projectId = null;

        $this->debugLogFlag = null;
        $this->debug_mode_log_project = null;
        $this->debug_mode_log_system  = null;

        // project ID of project 
        if ($pid) {
            $projectId = $pid;
        } else {
            $projectId = (isset($_GET['pid']) ? $_GET['pid'] : 0);
        }
        
        if ($projectId > 0) {
            $this->projectId = $projectId;
        } else {
            $projectId = null;
        }

        $this->loadConfig($projectId);
        
        $this->debugLogFlag = ($this->debug_mode_log ? true : false);
    }

    /**
     * checkApiToken - check the API token.
     */
    public function checkApiToken() {

        global $post;

        /** @var \RestRequest $data */
        $data = \RestUtility::processRequest(true);
        //echo 'Check API Token';exit;

        $post = $data->getRequestVars();
    }

    /**
     * loadConfig - configuration settings here.
     */
    public function loadConfig($projectId = 0) 
    {
        if ($projectId > 0) {
            $this->loadProjectConfig($projectId);
        } else {
            $this->loadProjectConfigDefaults();
        }

        $this->loadSystemConfig();

        $this->debugLogFlag = ($this->debug_mode_log_project || $this->debug_mode_log_system ? true : false);
    }

    /**
     * loadSystemConfig - System configuration settings here.
     */
    public function loadSystemConfig() 
    {
        $this->debug_mode_log_system = $this->getSystemSetting('debug_mode_log_system');
        
        // put some of your other config settings here

    }

    /**
     * loadProjectConfig - Project configuration settings here.
     */
    public function loadProjectConfig($projectId = 0) 
    {
        if ($projectId > 0) {
            $this->debug_mode_log_project = $this->getProjectSetting('debug_mode_log_project');

            // put some of your other config settings here
        }
    }

    /**
     * loadProjectConfigDefaults - set up our defaults.
     */
    public function loadProjectConfigDefaults()
    {
        $this->debug_mode_log_project   = false;
    }
    
    /**
     * easyLogMsg - .
     */
    public function easyLogMsg($debugmsg, $shortMsg = '')
    {
        if ($this->debugLogFlag) {
            $this->debugLog($debugmsg, ($shortMsg ? $shortMsg : $debugmsg));
            return true;
        }
        
        return false;
    }
    
    /**
     * alwaysLogMsg - .
     */
    public function alwaysLogMsg($debugmsg, $shortMsg = '')
    {
        $this->performLogging($debugmsg, ($shortMsg ? $shortMsg : $debugmsg));
    }

    /**
     * performLogging - .
     */
    public function performLogging($logDisplay, $logDescription = self::NAME_IDENTIFIER)
    {
        // $sql, $table, $event, $record, $display, $descrip="", $change_reason="",
        //                                  $userid_override="", $project_id_override="", $useNOW=true, $event_id_override=null, $instance=null
        $logSql         = '';
        $logTable       = '';
        $logEvent       = 'OTHER';  // 'event' what events can we have?  DATA_EXPORT, INSERT, UPDATE, MANAGE, OTHER
        $logRecord      = '';

        //$logDisplay     = $debugmsg; // 'data_values'  (table: redcap_log_event)
        //$logDescription = $logDisplayMsg;  // 'description' limit in size is 100 char (auto chops to size)
        
        Logging::logEvent($logSql, $logTable, $logEvent, $logRecord, $logDisplay, $logDescription);
    }
        
    /**
     * debugLog - (debug version) Simplified Logger messaging.
     */
    public function debugLog($debugmsg = '', $logDisplayMsg = self::NAME_IDENTIFIER)
    {
        if (!$this->debugLogFlag) {  // log mode off
            return;
        }
        
        $this->performLogging($debugmsg, $logDisplayMsg);
    }

    /**
     * showJson - show a json parsable page.
     */
    public function showJson($rsp, $convertFlag = false) 
    {
        if ($convertFlag) {
            $rsp = json_encode($rsp);
        }
        
        $jsonheader = 'Content-Type: application/json; charset=utf8';
        header($jsonheader);
        echo $rsp;
    }

    /**
     * viewHtml - the front end part, display what we have put together. This method has an added feature for use with the control center, includes all the REDCap navigation.
     */
    public function viewHtml($msg = 'view', $flag = self::VIEW_CMD_DEFAULT)
    {
        $HtmlPage = new HtmlPage(); 

        switch ($flag) {
            // project
            case self::VIEW_CMD_PROJECT:
                $HtmlPage->ProjectHeader();
              echo $msg;
                $HtmlPage->ProjectFooter();
                break;

            // control
            case self::VIEW_CMD_CONTROL:
                if (!SUPER_USER) {
                    redirect(APP_PATH_WEBROOT); 
                }
    
                global $lang;  // this is needed for these two to work properly
                include APP_PATH_DOCROOT . 'ControlCenter/header.php';
              echo $msg;
                include APP_PATH_DOCROOT . 'ControlCenter/footer.php';
                break;

            // system
            case self::VIEW_CMD_SYSTEM:
            default:
                $HtmlPage->setPageTitle($this->projectName);
                $HtmlPage->PrintHeaderExt();
              echo $msg;
                $HtmlPage->PrintFooterExt();
                break;
        }
    }
    
    /**
     * prettyPrint - an html pretty print of a given array of data. if given htmlformat false then just as text
     */
    public function prettyPrint($data, $htmlFormat = true) 
    {
        if ($htmlFormat) {
            $html = '';
            $pre = '<pre>';
            $pree = '</pre>';
    
            $html .= $pre;
            $formatted = print_r($data, true);

            $html .= htmlspecialchars($formatted, ENT_QUOTES, 'UTF-8', true);
            $html .= $pree;

            return $html;
        }
        
        $text = print_r($data, true);
        
        return $text;
    }

    /**
     * parseChanges - build array of data given user,dagid;
     */
    public function parseChanges($changelist) 
    {
        $listing = array();
        
        if (str_contains($changelist, ';')) {
            $listing = explode(';', $changelist);  // a list of pairs.  user,dagID;user,dagID;....
        } else {
            $listing[] = $changelist; // a single pair, we expect
        }
        
        $listing = array_filter($listing); // trim blank array out
        
        return $listing;
    }


    /**
     * getChangeItem - build array of data give comma list.
     */
    public function getChangeItem($changelist) 
    {
        $listing = array();
        
        if (str_contains($changelist, ',')) {
            $listing = explode(',', $changelist);
        }
        
        return $listing;
    }   
    // **********************************************************************   
    // **********************************************************************   
    // **********************************************************************
} // *** end class

?>
