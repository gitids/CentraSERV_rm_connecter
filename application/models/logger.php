<?php

error_reporting(E_ALL);

if (0 > version_compare(PHP_VERSION, '5')) {
    die('This file was generated for PHP 5');
}

$Comman_Settings_loaded = false;

Class Logger extends CI_Model {

    public $App = "";
    public $UserID = "";
    public $Location = "";
    public $Transaction = "";
    public $Validation = "";
    public $Datetime = "";
    public $MAC = "";
    public $IP = "";
    public $Debug = "Debug";
    public $Log_Debug = true;
    public $intraserv_DB;
    public $Settings = false;
    public $PMS_MODE = false;
    public $ExID = false;
    //public $ExID_Field = 'A3';
    public $ExID_Field = '';
    public $SITE_ID = "";

    /*
     *  0 - false
     *  1 - success
     *  9 - ongoing
     */
    public $Session = array(
        'LS' => false,
        'LE' => false,
        'DR' => false,
        'RPOST' => false,
        'LA_LAST_DT' => 0,
    );

    public function __construct() {
        parent::__construct();

        $this->intraserv_DB = $this->load->database('intraserv', TRUE);
        $this->intraserv_DB->db_select();
        $this->load();

        // Your own constructor code       
    }

    public function load($Force_Refresh = FALSE) {

        global $Comman_Settings_loaded;

        // to Avoid Muti Loads
        if ($Comman_Settings_loaded)
            return TRUE;

        // First Time Load Settings
        $sql = "SELECT cm.`GatewayID` , cm.`HotelName` , cm.`GatewayIP` , cm.`BWUp` , cm.`BWDown` , 
             cm.`MailConfigID` , cm.`RoomValidation` , cm.`BillingPolicy` , cm.`GuestAuthMode` , 
             cm.`SiteMode` , cm.`PMSID` , cm.`PMSStaus` , cm.`PMSIP` , cm.`PMSPort` , cm.`PMSLastUpdate`, 
             `TimeZone`, `NDX_Patch`, `Radius_WAN_Balance`,`Radius_WAN_Balance_Object`, `PMSObject`,`PMS_HI_Status`,`PMS_HI` FROM  `commonsetting` cm 
            LIMIT 1";

        $query = $this->intraserv_DB->query($sql);
        //echo "First Time Load Settings - $Comman_Settings_loaded\n";
        $Settings = array();
        if ($query) {
            if ($query->num_rows() > 0) {

                $this->Settings = $query->row_array();
                $this->_make_Settings();
                $Comman_Settings_loaded = true;
                return TRUE;
            } else {
                // If False
                $Comman_Settings_loaded = true;
                return TRUE;
            }
        }
        return FALSE;
    }

    public function _get_common_settings($Hotel_ID="") {
        $sql = "SELECT cm.`GatewayID` , cm.`HotelName` , cm.`GatewayIP` , cm.`BWUp` , cm.`BWDown` , 
             cm.`MailConfigID` , cm.`RoomValidation` , cm.`BillingPolicy` , cm.`GuestAuthMode` , 
             cm.`SiteMode` , cm.`PMSID` , cm.`PMSStaus` , cm.`PMSIP` , cm.`PMSPort` , cm.`PMSLastUpdate`, 
             `TimeZone`, `NDX_Patch`, `Radius_WAN_Balance`,`Radius_WAN_Balance_Object`, `PMSObject`,`PMS_HI_Status`,`PMS_HI` FROM  `commonsetting` cm 
            Where cm.`Site_ID`=?";

        $query = $this->intraserv_DB->query($sql, $Hotel_ID);
        //echo "First Time Load Settings - $Comman_Settings_loaded\n";
        $Settings = array();
        if ($query) {
            if ($query->num_rows() > 0) {

                $this->Settings = $query->row_array();
                $this->_make_Settings();
                //$Comman_Settings_loaded = true;
                return TRUE;
            } else {
                // If False
                // $Comman_Settings_loaded = true;
                return TRUE;
            }
        }
    }

    public function _make_Settings() {
        if ((is_array($this->Settings) && strlen($this->Settings['TimeZone'])) > 1) {
            date_default_timezone_set($this->Settings['TimeZone']);
            //echo "$this->TimeZone ";
        }
        $this->_load_Session();
    }

    public function _load_Session() {

        $query = $this->intraserv_DB->get('commonsetting');
        if ($query->num_rows() > 0) {
            $row = $query->row_array();
            $this->Settings['PMSObject'] = $row['PMSObject'];
        }
        $TEMP_Session = json_decode($this->Settings['PMSObject'], true);
        //var_dump($this->Session);
        if (is_array($this->Session)) {
            foreach ($this->Session as $key => $value) {
                if (isset($TEMP_Session[$key]))
                    $this->Session[$key] = $TEMP_Session[$key];
            }
        }
    }

    public function _set_Session($inkey, $invalue) {

        //$this->db->trans_start();
        //$this->intraserv_DB->query('LOCK TABLE commonsetting WRITE');
        $query = $this->intraserv_DB->get('commonsetting');
        if ($query->num_rows() > 0) {
            $row = $query->row_array();
            $this->Settings['PMSObject'] = $row['PMSObject'];
            $TEMP_Session = @json_decode($this->Settings['PMSObject'], TRUE);

            $TEMP_Session[$inkey] = $invalue;
            if (is_array($this->Session)) {
                foreach ($this->Session as $key => $value) {
                    if (isset($TEMP_Session[$key]))
                        $this->Session[$key] = $TEMP_Session[$key];
                }
                $this->transaction_log($Log_App = 'PMS_SRV', $this->router->class . '/' . $this->router->method, $Log_Transaction = "PMSObject: " . json_encode($this->Session), "", $Log_Validation = "$inkey : $invalue");
                $this->intraserv_DB->update('commonsetting', array('PMSObject' => json_encode($this->Session)));
            }
        }
        //$this->intraserv_DB->query('UNLOCK TABLES');
        //$this->db->trans_complete();
    }

    public function _set_Http_OK() {

        ignore_user_abort(true);
        set_time_limit(0);

        ob_start();
// do initial processing here
        echo '{"STATUS":"COMPLETE","DESC":"COMPLETE"}'; // send the response
        //header('Content-Type: application/json');
        header("Content-Type: application/json;charset=utf-8");
        header('Connection: close');
        header('Content-Length: ' . ob_get_length());
        ob_end_flush();
        ob_flush();
        flush();
        usleep(100);
    }

    public function email_log($LGData = array()) {

        $this->transaction_log($Log_App = 'RM_SRV', $this->router->class . '/' . $this->router->method, "_Email :- " . json_encode($LGData));
        $Is_send_mail = TRUE;
        if ($Is_send_mail && isset($LGData['Email_To'])) {

            //@$this->load->model("Site_Template");

            $this->load->library('email');
            //$config['protocol'] = 'smtp';
            $config['mailtype'] = 'html';

            $config['smtp_host'] = 'mail.idsworld.com';
            $config['smtp_user'] = 'support@idsworld.com';
            $config['smtp_pass'] = 'Support123';

//            $config['smtp_host'] = '' . $this->Site_Template->getText("SMTP_HOST", true);
//            $config['smtp_user'] = '' . $this->Site_Template->getText("SMTP_USER", true);
//            $config['smtp_pass'] = '' . $this->Site_Template->getText("SMTP_PASS", true);

            $config['smtp_port'] = 587;

            if (strlen($config['smtp_pass']) <= 0)
                return FALSE;

            $from = 'support@idsworld.com';
            $to = $LGData['Email_To'];


//            $from = '' . $this->Site_Template->getText("SMTP_USER", true);
//            $to = '' . $this->Site_Template->getText("Recipient", true);

            $this->email->initialize($config);
            $this->email->from($from);
            $this->email->to($to);

            $this->email->subject("Home Admin User Registration-" . date("Y-m-d H:i:s"));
        }
        else
            return TRUE;


        $Title = "Home Admin User Registration";

        $Contents = '
                <!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>' . $Title . 'Login</title>
</head>

<body>
<p style="font-weight: bold">Dear User</p></br><p>Have a Great Day! Please use following information for access Home System.</p>
                <table width="400" border="2">
  <tr>
    <td colspan="2" align="center" bgcolor="#33ccff"> Home ID : ' . $this->SITE_ID . '</td>
  </tr>
  
  <tr>
    <td width="100">username</td>
    <td width="200">&nbsp;' . $LGData['Username'] . '</td>
  </tr>
  <tr>
    <td>password</td>
    <td>&nbsp;' . $LGData['Password'] . '</td>
  </tr>
  
</table>
</body>
</html>';

        $this->email->message($Contents);
        $this->email->send();
        //$this->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, "Email :- ".$this->email->print_debugger());
    }

    // Transaction_log (*App Name,Controller/Method,*Transaction,*Validation)
    public function transaction_log($Log_App ='RM_SRV', $Log_Loc='', $Log_Transaction='', $MAC="", $Log_Validation="") {

        if (strlen($Log_Transaction) > 60)
            $Log_Transaction = chunk_split($Log_Transaction, 60, ' ');

        $data = array(
            'Site_ID' => $this->SITE_ID,
            'App' => $Log_App,
            'UserID' => $this->UserID,
            'Location' => $Log_Loc,
            'Transaction' => $Log_Transaction,
            'Validation' => json_encode($Log_Validation),
            'MAC' => $MAC,
            'Type' => "Info"
        );

        $db_debug = $this->db->db_debug;
        $this->db->db_debug = false;

        $this->db->set("Datetime", "NOW()", FALSE);
        $this->db->insert('transactionlog', $data);

        $this->db->db_debug = $db_debug;
    }

    // Error_log (*App Name,Controller/Method,*Error)
    public function error_log($Log_App ='Portal', $Log_Loc='', $Log_Err='', $Log_Type="") {

        $this->Log_Debug = (int) $this->Comman_Settings->Log_Debug;

        $this->refresh_mac();
        if ($this->Log_Debug == FALSE && $Log_Type == $this->Debug) {
            return;
        }

        $this->refresh_mac();
        if (strlen($Log_Err) > 60)
            $Log_Err = chunk_split($Log_Err, 60, ' ');

        $data = array(
            'App' => $Log_App,
            'UserID' => $this->UserID,
            'Location' => $Log_Loc,
            'Error_desc' => $Log_Err,
            'MAC' => $this->MAC,
            'IP' => $this->IP
        );

        $db_debug = $this->db->db_debug;
        $this->db->db_debug = false;

        $this->db->set("Datetime", "NOW()", FALSE);
        $this->db->insert('errorlog', $data);

        $this->db->db_debug = $db_debug;
    }

    public function page_counts($Log_App ='Portal', $Log_Loc='Un') {

        $db_debug = $this->db->db_debug;
        $this->db->db_debug = false;

        $count = 1;
        $this->db->select('Location, Count');
        $this->db->where('Location', $Log_Loc);
        $this->db->where('App', $Log_App);
        $query = $this->db->get('page_counts');
        if ($query->num_rows() > 0) {
            $row = $query->row_array();

            $count = $row['Count'];
            settype($count, "integer");
            $count++;

            $data = array(
                'Count' => $count
            );

            $this->db->set("LastDatetime", "NOW()", FALSE);
            $this->db->where('Location', $Log_Loc);
            $this->db->update('page_counts', $data);
        } else {
            $data = array(
                'App' => $Log_App,
                'Location' => $Log_Loc,
                'Count' => $count
            );

            $this->db->set("LastDatetime", "NOW()", FALSE);
            $this->db->insert('page_counts', $data);
        }

        $this->db->db_debug = $db_debug;
    }

}

/* end of class Portal Class Diagram_Logger */
?>