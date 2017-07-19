<?php

/**
 * Includes the User_Model class as well as the required sub-classes
 * @package codeigniter.application.models
 */

/**
 * User_Model extends codeigniters base CI_Model to inherit all codeigniter magic!
 * @author Leon Revill
 * @package codeigniter.application.models
 */
class PMS_Com_Handler extends CI_Model {
    /*
     * A private variable to represent each column in the database
     */

    private $str_in;
    private $pms_raw_array;
    private $pms_object;
    private $query_id;
    private $postpms_id;
    private $pmstransaction_id;
    private $_username;
    private $_password;
    private $_RN = false;
    private $_LN = false;
    private $_RegNo = false;
//private $_MAC = "00e0b409be51";
    private $_MAC = "000000000000";
    public $Settings = array();
    public $Sudo = "sudo ";
    public $STR = "";
    public $END = "";
    private $now_date;
    private $now_time;
    private $map_data = array();
    public $intraserv_DB;
    private $_HI_validate = false;
    public $_HI = '0001';
    public $cloud_srv_active = FALSE;
    private $_MNO = array();
    public $_retry_count = 3;
    public $_previous_Sent = "";

    function __construct() {

        parent::__construct();
        $this->map_data = array(
            "HI" => 'Site_ID',
            "GI" => null,
            "DA" => null,
            "TI" => null,
            "RN" => 'RoomNo',
            "G#" => 'RegNo',
            "GS" => 'Share',
            "GA" => 'ArrivalDT',
            "GD" => 'DepartureDT',
            "GF" => null,
            "GG" => null,
            "GL" => 'Language',
            "GN" => 'LastName',
            "GT" => 'Title',
            "GV" => 'VIPStatus',
            "NP" => 'NoPost',
            "SF" => null,
            "A0" => 'A0',
            "A1" => 'A1',
            "A2" => 'A2',
            "A3" => 'A3',
            "A4" => 'A4',
            "A5" => 'A5',
            "A6" => 'A6',
            "A7" => 'A7',
            "A8" => 'A8',
            "A9" => 'A9',
        );

        $this->_MNO[0] = 'A3'; // PMS DB
        $this->_MNO[1] = 'A3'; // PMS PARAMETER


        $this->load->model("Logger");

        //
        $this->_HI_validate = (int) $this->Logger->Settings['PMS_HI_Status'];

        //$this->_HI = $this->Logger->Settings['PMS_HI'];

        $this->STR = pack('C', 0x02);
        $this->END = pack('C', 0x03);

        date_default_timezone_set("Asia/Colombo");
        $now_date = new DateTime("now");
        $this->now_date = $now_date->format('ymd');
        $this->now_time = $now_date->format('His');

        $this->intraserv_DB = $this->load->database('intraserv', TRUE);
        $this->intraserv_DB->db_select();
        $this->cloud_srv_active = (int) $this->Logger->Settings['PMS_HI_Status'];
    }

    /*
     * SET's & GET's
     * Set's and get's allow you to retrieve or set a private variable on an object
     */

    /**
     * @return int [$this->_id] Return this objects ID
     */
    public function Accept_protocol($in = "") {

        $_Task_Executed = FALSE;
//GI|DA160328|TI155520|RN100|G#110149|GSN|GA160206|GD171223|GFFirstName|GG1234|GLEA|GNDileepa|GTDr|GVN|NPN|SF|
        $this->str_in = str_replace(array($this->STR, $this->END), '', $in);
        $this->pms_raw_array = explode('|', $this->str_in);
        $this->_create_Object();

        $pms_protocol_identy = $this->pms_raw_array[0];
        //  HI Validate
        if ($this->_HI_validate) {

            if (isset($this->pms_object['HI']) && isset($this->pms_object['HI']['0'])) {
                $this->Logger->_get_common_settings($this->pms_object['HI']['0']);
                $this->_HI = $this->Logger->Settings['PMS_HI'];
                if ($this->pms_object['HI']['0'] == $this->_HI)
                    $pms_protocol_identy = $this->pms_raw_array[1];
                else
                    return $_Task_Executed;
            } else {
                return $_Task_Executed;
            }
        }
        //

        switch ($pms_protocol_identy) {
            case 'GI': $this->GI_();
                $_Task_Executed = TRUE;
                break;
            case 'GC': $this->GC_();
                $_Task_Executed = TRUE;
                break;
            case 'GO': $this->GO_();
                $_Task_Executed = TRUE;
                break;
            case 'PL': $this->PL_();
                $_Task_Executed = TRUE;
                break;
            case 'PA': $this->PA_();
                $_Task_Executed = TRUE;
                break;
            case 'LS':
                $this->Logger->_set_Http_OK();
                $this->LS_();
                $_Task_Executed = TRUE;
                break;
            case 'LA': $this->LA_();
                $_Task_Executed = TRUE;
                break;
            case 'LE': $this->LE_();
                $_Task_Executed = TRUE;
                break;
            case 'DS': $this->DS_();
                $_Task_Executed = TRUE;
                break;
            case 'DE': $this->DE_();
                $_Task_Executed = TRUE;
                break;

            default:$_Task_Executed = FALSE;
                break;
        }
//$query = $this->db->query('SELECT `ID`, `RoomNo`, `LastName`, `RegNo`, `Amount1`, `Amount2`, `Amount3`, `Amount4`, `Tax1`, `Tax2`, `Tax3`, `Tax4`, `NameRes`, `Request`, `ReqDT`, `RStatus`, `Response`, `ResDT`, `TransID` FROM `postpms`');
//	return $query->row();
        try {

            return $_Task_Executed;

            reset($this->pms_object);
            $first_key = key($this->pms_object);
            $this->Logger->transaction_log($Log_App = 'PMS_SRV', $this->router->class . '/' . $this->router->method, $Log_Transaction = json_encode($this->pms_object), "", $Log_Validation = $first_key);
            return $_Task_Executed;
        } catch (Exception $exc) {
//echo $exc->getTraceAsString();
            return $_Task_Executed;
        }
    }

    public function _create_Object() {

        $this->pms_object = array();
        foreach ($this->pms_raw_array as $key_value) {
            $key = substr($key_value, 0, 2);
            $value = substr($key_value, 2);

            if (!isset($this->pms_object[$key]))
                $this->pms_object[$key] = array();
            array_push($this->pms_object[$key], $value);
            //$this->pms_object[$key][0] = $value;
            //$this->pms_object[$key] = $value;
        }
    }

    public function PMSS_Web_srv_remote_JSON($paraSTR = "", $Site_ID="") {
        if (strlen($paraSTR) > 0) {

            if (!empty($Site_ID)) {
                $this->_HI = $Site_ID;
            }
            $paraSTR = 'HI' . $this->_HI . '|' . $paraSTR;
            //$paraSTR = 'HI' . $Site_ID . '|' . $paraSTR;

            //return $paraSTR;

            $TIMEOUT = 20;
            //$CLOUD_SRV_URL = "http://127.0.0.1:8079/pms_connector/index.php/PMS/PMSSIn/format/json/";
            //get Remote site IP
            $remote_server_ip = "127.0.0.1";

            $sql = "SELECT `MW_IP` FROM `commonsetting` WHERE `PMS_HI`=?";
            $query_remote_ip = $this->db->query($sql, $this->_HI);
            if ($query_remote_ip && $query_remote_ip->num_rows() > 0) {
                foreach ($query_remote_ip->result_array() as $row_val) {
                    $remote_server_ip = $row_val['MW_IP'];
                }
            }

            //$PMS_LOCAL_CONNECTER_URL = "http://192.168.1.118/pms_local_connector/PMSL/In/format/json/";

            $PMS_LOCAL_CONNECTER_URL = "http://" . $remote_server_ip . "/pms_local_connector/PMSL/In/format/json/";

            $this->load->model("Http_Request");
            $dataPOST = array(
                'msg' => $paraSTR
            );

            $data = array(
                'Site_ID' => $this->_HI,
                'Message' => $paraSTR,
                'Type' => "S",
                'Status' => "Q"
            );

            $this->db->set("AddDT", "NOW(3)", FALSE);
            $this->db->set("ProsDT", "NOW(3)", FALSE);
            $this->db->insert('communication', $data);
            $comm_id = $this->db->insert_id();

            //$returnValue = $this->Http_Request->httpPost($CLOUD_SRV_URL, $dataPOST, $TIMEOUT);
            $returnValue = $this->Http_Request->httpPost($PMS_LOCAL_CONNECTER_URL, $dataPOST, $TIMEOUT);

            $_DESC = "COMPLETE";

            if ($returnValue) {
                $this->db->where('ID', $comm_id);
                $this->db->set("ProsDT", "NOW(3)", FALSE);
                $this->db->update('communication', array(
                    'Status' => "S"
                ));
            } else {
                $this->db->where('ID', $comm_id);
                $this->db->set("ProsDT", "NOW(3)", FALSE);
                $this->db->update('communication', array(
                    'Status' => "I"
                ));
                $_DESC = "IGNORED";
            }
        }
        //echo $returnValue;
        return $returnValue;
    }

    public function PMSS_Web_srv_SOAP($paraSTR = "") {

        $returnValue = FALSE;
        if (strlen($paraSTR) > 0) {

            $paraSTR = 'HI' . $this->_HI . '|' . $paraSTR;
            $TIMEOUT = 20;
            $CLOUD_SRV_URL = "http://192.168.1.222:8079/WM_Service.asmx?WSDL";
            // $CLOUD_SRV_URL = "http://1.186.45.162:8079/WM_Service.asmx?WSDL";

            $CLOUD_SRV_URL = "http://192.168.1.222:8079/WM_Service.asmx?WSDL";
            $CLOUD_SRV_URL = "http://192.168.1.170:8079/index.php/wsdl?wsdl";


            $CLOUD_SRV_URL = "";

            /* Production SAPP-PI */
            $CLOUD_SRV_URL = "http://10.10.58.46:8079/index.php?wsdl";

            /* Dev SAPP-PI */
            //$CLOUD_SRV_URL = "http://140.158.8.66:8079/index.php?wsdl";

            $query = $this->db->get_where('common_srv', array('SRV_FLAG' => 'CLOUD_MW_SRV'));
            if ($query->num_rows() > 0) {
                $row = $query->row_array();
                $CLOUD_SRV_URL = "" . $row['SRV_URL'];
            }

            $data = array(
                'Message' => $paraSTR,
                'Type' => "S",
                'Status' => "Q"
            );

            $this->db->set("AddDT", "NOW(3)", FALSE);
            $this->db->set("ProsDT", "NOW(3)", FALSE);
            $this->db->insert('communication', $data);
            $comm_id = $this->db->insert_id();

            ////////////////////////////////////// Soap Start

            $client = null;
            $response = null;
            try {
                /* Initialize webservice with your WSDL */
                $client = @new SoapClient($CLOUD_SRV_URL);

                /* Set your parameters for the request */
                $params = array(
                    "RecordSet" => $paraSTR,
                );

                /* Invoke webservice method with your parameters, in this case: Function1 */
                //$response = $client->__soapCall("LinkRecord ", array($params));
                $response = @$client->RQ_DataToSAP_PI_Server($params);
                //$response = @$client->__soapCall('RQ_DataToSAP_PI_Server', $params);

                if (strpos(' ' . json_encode($response), 'COMPLETE') > 0) {
                    $returnValue = TRUE;
                } else if (is_object($response) || is_array($response)) {
                    $returnValue = TRUE;
                }
            } catch (Exception $exc) {

                //echo $exc->getTraceAsString();

                $data = array(
                    'Error_desc' => 'RE ' . json_encode($client) . " : " . json_encode($response),
                    'Location' => $this->router->class . '/' . $this->router->method
                );

                //var_dump($response);
                //die();

                $this->db->set("Datetime", "NOW(3)", FALSE);
                //$this->db->set("ProsDT", "NOW(3)", FALSE);
                $this->db->insert('errorlog', $data);
            }

            ////////////////////////////////////// Soap End

            $_DESC = "COMPLETE";

            if ($returnValue) {
                $this->db->where('ID', $comm_id);
                $this->db->set("ProsDT", "NOW(3)", FALSE);
                $this->db->update('communication', array(
                    'Status' => "S"
                ));
            } else {
                $this->db->where('ID', $comm_id);
                $this->db->set("ProsDT", "NOW(3)", FALSE);
                $this->db->update('communication', array(
                    'Status' => "I"
                ));
                $_DESC = "IGNORED";
            }
        }
        //echo $returnValue;
        return $returnValue;
    }

    public function PMSS_Web_srv($paraSTR = "", $Site_ID="") {

        //TCP/IP PMS
        return $this->PMSS_Web_srv_remote_JSON($paraSTR, $Site_ID);
        $returnValue = FALSE;
        //Ginger Hotels
        return $this->PMSS_Web_srv_SOAP($paraSTR);

        $returnValue = FALSE;
        if (strlen($paraSTR) > 0) {

            $paraSTR = 'HI' . $this->_HI . '|' . $paraSTR;
            $TIMEOUT = 20;
            $CLOUD_SRV_URL = "http://127.0.0.1:8079/pms_connector/index.php/PMS/PMSSIn/format/json/";

            $this->load->model("Http_Request");
            $dataPOST = array(
                'msg' => $paraSTR
            );

            $data = array(
                'Message' => $paraSTR,
                'Type' => "S",
                'Status' => "Q"
            );

            $this->db->set("AddDT", "NOW(3)", FALSE);
            $this->db->set("ProsDT", "NOW(3)", FALSE);
            $this->db->insert('communication', $data);
            $comm_id = $this->db->insert_id();

            $returnValue = $this->Http_Request->httpPost($CLOUD_SRV_URL, $dataPOST, $TIMEOUT);

            $_DESC = "COMPLETE";

            if ($returnValue) {
                $this->db->where('ID', $comm_id);
                $this->db->set("ProsDT", "NOW(3)", FALSE);
                $this->db->update('communication', array(
                    'Status' => "S"
                ));
            } else {
                $this->db->where('ID', $comm_id);
                $this->db->set("ProsDT", "NOW(3)", FALSE);
                $this->db->update('communication', array(
                    'Status' => "I"
                ));
                $_DESC = "IGNORED";
            }
        }
        //echo $returnValue;
        return $returnValue;
    }

    public function LA_() {

        sleep(2);
        $LA_ignore = FALSE;
        $this->Logger->_load_Session();

        if ($this->Logger->Session['LS'] == 1) {
            $this->load->model("PMS_Socket");

            if ($this->Logger->Session['DR'] > 1) {
                
            } else if ($this->Logger->Session['DR'] == 0) {

                //$this->Logger->_set_Session('DR', 9);
                // Switch Socket or Web Srv
                $_send_PMS = FALSE;
                $_send_PMS_para = "DR|DA$this->now_date|TI$this->now_time|";
                if ($this->cloud_srv_active > 0) {
                    // HI Status        
                    $this->load->model("PMS_Com_Handler");
                    $_send_PMS = $this->PMS_Com_Handler->PMSS_Web_srv($_send_PMS_para);
                } else {
                    // Local Socket
                    $this->load->model("PMS_Socket");
                    $_send_PMS = $this->PMS_Socket->send_PMS($_send_PMS_para);
                }
                // Switch Socket or Web Srv END

                $LA_ignore = TRUE;
            } else if ($this->Logger->Session['DR'] == 1) {

                if ($this->Logger->Session['RPOST'] == 0) {
                    $this->Logger->_set_Session('RPOST', 9);
                    $this->RePosting_to_PMS();
                    $this->Logger->_set_Session('RPOST', 1);
                }

                $this->intraserv_DB->update('commonsetting', array('PMSStaus' => 'LIVE'));

                return TRUE;
                // For Testing    
                //$this->load->model("PMS_Socket");
                // Switch Socket or Web Srv
                $_send_PMS = FALSE;
                $_send_PMS_para = "LA|DA$this->now_date|TI$this->now_time|";
                if ($this->cloud_srv_active > 0) {
                    // HI Status        
                    $this->load->model("PMS_Com_Handler");
                    $_send_PMS = $this->PMS_Com_Handler->PMSS_Web_srv($_send_PMS_para);
                } else {
                    // Local Socket
                    $this->load->model("PMS_Socket");
                    $_send_PMS = $this->PMS_Socket->send_PMS($_send_PMS_para);
                }
                // Switch Socket or Web Srv END
                // $this->PMS_Socket->send_PMS("LA|DA$this->now_date|TI$this->now_time|");
            } elseif ((int) $this->Logger->Settings['LinkAlive'] && (strtotime('now') - (int) $this->Logger->Session['LA_LAST_DT']) > 5) {

                return;
                $now_date = new DateTime("now");
                $_now_date = $now_date->format('ymd');
                $_now_time = $now_date->format('His');

                $this->Logger->_set_Session('LA_LAST_DT', $now);

                // Switch Socket or Web Srv
                $_send_PMS = FALSE;
                $_send_PMS_para = "LA|DA$_now_date|TI$_now_time|";
                if ($this->cloud_srv_active > 0) {
                    // HI Status        
                    $this->load->model("PMS_Com_Handler");
                    $_send_PMS = $this->PMS_Com_Handler->PMSS_Web_srv($_send_PMS_para);
                } else {
                    // Local Socket
                    $this->load->model("PMS_Socket");
                    $_send_PMS = $this->PMS_Socket->send_PMS($_send_PMS_para);
                }
                // Switch Socket or Web Srv END
            }
        } else {
            
        }
    }

    public function LS_() {

//echo '{"STATUS":"COMPLETE","DESC":"COMPLETE"}';
//return;
        $LR_string = '["LD|DA{DA}|TI{TI}|V#2.2|IFWW|",
"LR|RIGI|FLRNG#A0A1A2A3A4A5A6A7A8A9GNGFGLGVGGGSNPDATIGAGDSF|",
"LR|RIGC|FLRNG#A0A1A2A3A4A5A6A7A8A9GNGFGLGVGGGSNPDATIGAGDRO|",
"LR|RIGO|FLRNG#GSDATISF|",
"LR|RIPL|FLA0A1A2A3A4A5A6A7A8A9WSIDRNG#GNGFGLP#NPGAGDDATI|",
"LR|RIPR|FLG#GNRNTACTIDP#PCPTS1T1T2T3T4T4T6T7T8T9DATIWSPI|",
"LR|RIPA|FLRNASCTG#GNP#IDWSDATI|",
"LR|RIDR|FLDATI|",
"LR|RIDS|FLDATI|",
"LR|RIDE|FLDATI|",
"LR|RIXL|FLG#MIMTRNDATI|",
"LR|RIXD|FLG#MIRNDATI|",
"LR|RIXR|FLG#RNDATI|",
"LR|RIXI|FLBDBIDCG#RNF#FDDATI|",
"LR|RIXB|FLBAG#RNDATI|",
"LR|RINS|FLDATI|",
"LR|RINE|FLDATI|",
"LA|DA{DA}|TI{TI}|"]';

        /*
          $LR_string = '["LD|DA{DA}|TI{TI}|V#2.2|IFWW|",
          "LR|RIGI|FLRNG#A0A1A2A3A4A5A6A7A8A9GNGFGLGVGGGSNPDATIGAGDSF|",
          "LR|RIGC|FLRNG#A0A1A2A3A4A5A6A7A8A9GNGFGLGVGGGSNPDATIGAGDRO|",
          "LR|RIGO|FLRNG#GSDATISF|",
          "LR|RIDR|FLDATI|",
          "LR|RIDS|FLDATI|",
          "LR|RIDE|FLDATI|",
          "LR|RIXL|FLG#MIMTRNDATI|",
          "LR|RIXD|FLG#MIRNDATI|",
          "LR|RIXR|FLG#RNDATI|",
          "LR|RIXI|FLBDBIDCG#RNF#FDDATI|",
          "LR|RIXB|FLBAG#RNDATI|",
          "LR|RINS|FLDATI|",
          "LR|RINE|FLDATI|",
          "LA|DA{DA}|TI{TI}|"]';
         */

        if (strlen($this->Logger->Settings['IniObject']) > 2)
            $LR_array = $this->Logger->Settings['IniObject'];

        $LR_array = @json_decode($LR_string, TRUE);
//PR|WS00e0b409be51|ID499|PI200|DA160405|TI113211|P#499|
// Continue Query

        $LR_Success = TRUE;
        if (is_array($LR_array)) {

            //
            $db_debug = $this->db->db_debug;
            $this->db->db_debug = false;
            $this->db->simple_query('CALL comm_log_backup()');
            $this->db->simple_query('CALL query_log_backup()');
            $this->db->db_debug = $db_debug;

            $this->intraserv_DB->update('commonsetting', array('PMSStaus' => 'STARTING'));
            $this->Logger->_set_Session('LS', 9);
            $this->Logger->_set_Session('DR', 0);
            $this->Logger->_set_Session('RPOST', 0);


            if ($this->cloud_srv_active > 0) {
                // HI Status        
                $this->load->model("PMS_Com_Handler");
            } else {
                // Local Socket
                $this->load->model("PMS_Socket");
            }

            foreach ($LR_array as $key => $LR_row) {

                $LR_modified_row = str_replace("{DA}", "$this->now_date", $LR_row);
                $LR_modified_row = str_replace("{TI}", "$this->now_time", $LR_modified_row);

                if (strpos(' ' . $LR_row, 'LA') > 0 && $LR_Success = TRUE) {
                    $this->Logger->_set_Session('LS', 1);
                    if ($this->Logger->Settings['PMSName'] == 'Fidelio NUB') {
                        $this->Logger->_set_Session('DR', 1);
                    } else {
                        $this->Logger->_set_Session('DR', 0);
                    }
                    $this->intraserv_DB->update('commonsetting', array('PMSStaus' => 'LS'));
                }
                // Switch Socket or Web Srv
                $_send_PMS = FALSE;
                $_send_PMS_para = $LR_modified_row;
                if ($this->cloud_srv_active > 0) {
                    // HI Status 
                    $_send_PMS = $this->PMS_Com_Handler->PMSS_Web_srv($_send_PMS_para);
                } else {
                    // Local Socket
                    $_send_PMS = $this->PMS_Socket->send_PMS($_send_PMS_para);
                }
                // Switch Socket or Web Srv END

                if ($_send_PMS === FALSE) {
                    $LR_Success = FALSE;
                }
            }
        }
        if (TRUE || $LR_Success) {

            /*
              $this->Logger->_set_Session('LS', 1);
              if ($this->Logger->Settings['PMSName'] == 'Fidelio NUB') {
              $this->Logger->_set_Session('DR', 1);
              } else {
              $this->Logger->_set_Session('DR', 0);
              }

              //$this->RePosting_to_PMS();
              $this->intraserv_DB->update('commonsetting', array('PMSStaus' => 'LS'));
             */
        } else {
            $this->Logger->_set_Session('LS', 0);
        }
    }

    function LE_() {

        if ($this->Logger->Session['LE'] > 1) {
            $this->Logger->_set_Session('LS', 0);
            $this->Logger->_set_Session('LE', 1);
            $this->Logger->_set_Session('DR', 0);
            $this->Logger->_set_Session('RPOST', 0);
        } else {
            // Switch Socket or Web Srv
            $_send_PMS = FALSE;
            $_send_PMS_para = "LE|DA$this->now_date|TI$this->now_time|";

            if ($this->cloud_srv_active > 0) {
                // HI Status          
                $this->load->model("PMS_Com_Handler");
                $_send_PMS = $this->PMS_Com_Handler->PMSS_Web_srv($_send_PMS_para);
            } else {
                // Local Socket
                $this->load->model("PMS_Socket");
                $_send_PMS = $this->PMS_Socket->send_PMS($_send_PMS_para);
            }
            // Switch Socket or Web Srv END

            if ($_send_PMS) {
                $this->Logger->_set_Session('LS', 0);
                $this->Logger->_set_Session('LE', 1);
                $this->Logger->_set_Session('DR', 0);
                $this->Logger->_set_Session('RPOST', 0);
                $this->intraserv_DB->update('commonsetting', array('PMSStaus' => 'LE'));
                //$this->response(array("STATUS" => "COMPLETE", "DESC" => "COMPLETE"));
            }
            //else
            //  $this->response(array("STATUS" => "FAILED", "DESC" => "PMSS_Web_srv SUPPORTED"));
        }
    }

    public function DS_() {

        $this->Logger->_set_Session('DR', 9);
        $this->intraserv_DB->update('commonsetting', array('PMSStaus' => 'DATASWAP'));
    }

    public function DE_() {

        //sleep(1);
        // Data End
        $this->Logger->_set_Session('DR', 1);
        //$this->PMS_Socket->send_PMS("DR|DA$this->now_date|$this->now_time|");
        $this->intraserv_DB->update('commonsetting', array('PMSStaus' => 'LIVE'));
    }

    public function PMS_Status_Wait() {

        if ($this->Logger->Settings['PMSStaus'] == 'LIVE') {
            return FALSE;
        }

        $max_responese_time_sec = 20;
        $wait_time_sec = 1;
        ////

        $max_responese_time_sec = 5;
        $wait_time_sec = 4;

        $count_time = 0;

        $PMSStatus = "NOT_SET";
        while ($row = $this->getSettings_Row()) {
            if (is_array($row)) {

                $PMSStatus = strtoupper($row['PMSStaus']);
                if ($PMSStatus === 'LIVE') {
                    return FALSE;
                } else {
                    ;
                }
            }

            sleep($wait_time_sec);
            if ($count_time > $max_responese_time_sec) {
                return array("STATUS" => "TIMEOUT", "DESC" => "PMS NOT STARTED [$PMSStatus] " . $max_responese_time_sec . " SECs");
            }
            $count_time+=$wait_time_sec;
        }
        return FALSE;
    }

    public function GI_() {

        $data = array();
//var_dump($this->pms_object);
        foreach ($this->pms_object as $key => $_value_Array) {

            $_value = $_value_Array[0];
            if (isset($this->map_data[$key])) {
                $value = $_value;
                if (strlen($value) > 0)
                    $data[$this->map_data[$key]] = $value;

//$data[$this->map_data[$key]]=$value;
            }
        }

//        if (isset($this->pms_object['SF'])) {
//            if (isset($this->pms_object['GS']) && $this->pms_object['GS'][0] == 'N')
//                $this->GO_RoomNo();
//        }

        $data_array = array('RegNo' => $this->pms_object['G#'][0]);
        if (isset($this->pms_object[$this->_MNO[1]]) && $this->Logger->Settings['PMSName'] == 'Ginger PMS') {
            $data_array[$this->_MNO[0]] = $this->pms_object[$this->_MNO[1]][0];
        }

        //var_dump($data_array);
        //die();

        $query_pms = $this->db->get_where('pms', $data_array);
        if ($query_pms->num_rows() > 0) {

            $old_row = $query_pms->row_array();
            $exe_GC_srv = FALSE;
            //unset($data['RoomNo']);

            $data_array = array('RegNo' => $this->pms_object['G#'][0]);
            if (isset($this->pms_object[$this->_MNO[1]]) && $this->Logger->Settings['PMSName'] == 'Ginger PMS') {
                $data_array[$this->_MNO[0]] = $this->pms_object[$this->_MNO[1]][0];
            }

            $this->db->set("ChangedBy", "PMS_srv[GI]");
            $this->db->set("ChangeDT", "NOW(3)", FALSE);
            $this->db->where($data_array);
            $this->db->update('pms', $data);


            if ($data['RoomNo'] !== $old_row['RoomNo'])
                $exe_GC_srv = TRUE;
            if ($data['LastName'] !== $old_row['LastName'])
                $exe_GC_srv = TRUE;

            // Olny Execute when RN or GN changed.
            if ($exe_GC_srv)
                $this->GC_srv();
        } else {

            if (isset($this->pms_object['GS'])) {
                if ($this->pms_object['GS'][0] == 'N') {
                    $this->GO_RoomNo();
                } else if (isset($this->pms_object['GS']) && $this->pms_object['GS'][0] == 'Y') {
                    
                }
            }

            $this->db->set("AddedBy", "PMS_srv[GI]");
            $this->db->set("AddDT", "NOW(3)", FALSE);
            $this->db->insert('pms', $data);
        }
//
//        $this->db->set("AddedBy", "PMS_srv");
//        $this->db->set("AddDT", "NOW(3)", FALSE);
//        $this->db->insert('pms', $data);
    }

    public function GC_() {

        if ($this->pms_object['G#'][0]) {

// Room Change
            if (isset($this->pms_object['RO'])) {
                
            } else {
                unset($this->pms_object['RN']);
            }

// Guest Change
            $data = array();
            foreach ($this->pms_object as $key => $_value_Array) {

                $_value = $_value_Array[0];
                if (isset($this->map_data[$key])) {
                    $value = $_value;
                    if (strlen($value) > 0)
                        $data[$this->map_data[$key]] = $value;

//$data[$this->map_data[$key]]=$value;
                }
            }

            $data_array = array('RegNo' => $this->pms_object['G#'][0]);
            if (isset($this->pms_object[$this->_MNO[1]]) && $this->Logger->Settings['PMSName'] == 'Ginger PMS') {
                $data_array[$this->_MNO[0]] = $this->pms_object[$this->_MNO[1]][0];
            }

            $this->db->set("ChangedBy", "PMS_srv[GC]");
            $this->db->set("ChangeDT", "NOW(3)", FALSE);
            $this->db->where($data_array);
            $this->db->update('pms', $data);

            $this->GC_srv();
        }
    }

    public function GO_RoomNo() {

        if (isset($this->pms_object['RN'][0])) {

            $data_array = array('RoomNo' => $this->pms_object['RN'][0]);
            if (isset($this->pms_object[$this->_MNO[1]]) && $this->Logger->Settings['PMSName'] == 'Ginger PMS') {
                $data_array[$this->_MNO[0]] = $this->pms_object[$this->_MNO[1]][0];
            }

            $this->db->where($data_array);
            $this->db->delete('pms');
        }
    }

    // Call GO Service
    public function GC_srv() {

        //return TRUE;
        $returnValue = FALSE;
        if (isset($this->pms_object['G#'])) {

            $TIMEOUT = 20;
            $GUEST_GC_URL = "http://127.0.0.1:8079/index.php/PMS/GCUpdate/format/json/";
            $GUEST_GC_URL = "http://127.0.0.1:8079/pms_connector/index.php/PMS/GCUpdate/format/json/";

            $this->load->model("Http_Request");
            $data = array(
                'RegNo' => $this->pms_object['G#'][0]
            );
            if (isset($this->pms_object['GN']))
                $data['LastName'] = $this->pms_object['GN'][0];
            if (isset($this->pms_object['RN']))
                $data['RoomNo'] = $this->pms_object['RN'][0];

            if (isset($this->pms_object[$this->_MNO[1]]) && $this->Logger->Settings['PMSName'] == 'Ginger PMS') {
                $data['ExID'] = $this->pms_object[$this->_MNO[1]][0];
            }

            $returnValue = $this->Http_Request->httpPost($GUEST_GC_URL, $data, $TIMEOUT);
        }
        return $returnValue;
    }

    // Call GO Service
    public function GO_srv() {

        $returnValue = FALSE;
        if (isset($this->pms_object['G#'])) {

            $TIMEOUT = 20;
            $GUEST_GO_URL = "http://127.0.0.1:8079/index.php/PMS/GORemoveUserDevices/format/json/";
            $GUEST_GO_URL = "http://127.0.0.1:8079/pms_connector/index.php/PMS/GORemoveUserDevices/format/json/";

            $this->load->model("Http_Request");

            $data_array = array('RegNo' => $this->pms_object['G#'][0]);
            if (isset($this->pms_object[$this->_MNO[1]]) && $this->Logger->Settings['PMSName'] == 'Ginger PMS') {
                $data_array['ExID'] = $this->pms_object[$this->_MNO[1]][0];
            }

            $returnValue = $this->Http_Request->httpPost($GUEST_GO_URL, $data_array, $TIMEOUT);
        }
        return $returnValue;
    }

    public function GO_() {

        if (isset($this->pms_object['G#'])) {

            $this->GO_srv();

            $data_array = array('RegNo' => $this->pms_object['G#'][0]);
            if (isset($this->pms_object[$this->_MNO[1]]) && $this->Logger->Settings['PMSName'] == 'Ginger PMS') {
                $data_array[$this->_MNO[0]] = $this->pms_object[$this->_MNO[1]][0];
            }

            $this->db->where($data_array);
            $this->db->delete('pms');
        }
    }

    public function PR_query($param = '') {

        $data = array(
            'TransID' => 0,
            'Request' => NULL,
            'Status' => 'I',
        );

        $this->db->set("AddDatetime", "NOW(3)", FALSE);
        $this->db->insert('query', $data);
        $AI_ID = $this->db->insert_id();
        $PR = "PR|WS$this->_MAC|IDQUERY|PI$this->_RN|DA$this->now_date|TI$this->now_time|P#$this->query_id|";

        $data = array(
            'TransID' => $AI_ID,
            'Request' => $PR,
            'Status' => 'Q',
        );

        $this->db->set("PrcDT", "NOW(3)", FALSE);
        $this->db->where('ID', $AI_ID);
        $this->db->update('query', $data);

//PR|WS00e0b409be51|ID499|PI200|DA160405|TI113211|P#499|
// Continue Query
        // Switch Socket or Web Srv
        $_send_PMS = FALSE;
        $_send_PMS_para = $PR;
        if ($this->cloud_srv_active > 0) {
            // HI Status        
            $this->load->model("PMS_Com_Handler");
            $_send_PMS = $this->PMS_Com_Handler->PMSS_Web_srv($_send_PMS_para);
        } else {
            // Local Socket
            $this->load->model("PMS_Socket");
            $_send_PMS = $this->PMS_Socket->send_PMS($_send_PMS_para);
        }
        // Switch Socket or Web Srv END
        //$this->PMS_Socket->send_PMS($PR);

        $data = array(
            'Status' => 'QQ',
        );

        $this->db->set("ReqDT", "NOW(3)", FALSE);
        $this->db->where('ID', $AI_ID);
        $this->db->update('query', $data);
        return TRUE;
    }

    public function PR_($param = '') {

        /*
          $AI_ID = 0;
          //$this->db->trans_start();
          //$this->db->trans_complete();
          $this->db->select_max('ID', 'MAX_ID');
          $query = $this->db->get('query');
          if ($query->num_rows() > 0) {
          $row = $query->row();

          if ($row->MAX_ID > 0)
          $AI_ID = $row->MAX_ID;
          }
          $AI_ID++;
         */

        $data = array(
            'TransID' => 0,
            'Request' => NULL,
            'Status' => 'I',
        );
        $this->db->set("AddDatetime", "NOW(3)", FALSE);
        $this->db->insert('query', $data);
        $AI_ID = $this->db->insert_id();
        $PR = "PR|WS$this->_MAC|ID499|PI200|DA$this->now_date|TI$this->now_time|P#$AI_ID|PMROOM|";

        $data = array(
            'TransID' => $AI_ID,
            'Request' => $PR,
            'Status' => 'Q',
        );
        $this->db->set("PrcDT", "NOW(3)", FALSE);
        $this->db->where('ID', $AI_ID);
        $this->db->update('query', $data);

//PR|WS00e0b409be51|ID499|PI200|DA160405|TI113211|P#499|
// Continue Query
        // Switch Socket or Web Srv
        $_send_PMS = FALSE;
        $_send_PMS_para = $PR;
        if ($this->cloud_srv_active > 0) {
            // HI Status        
            $this->load->model("PMS_Com_Handler");
            $_send_PMS = $this->PMS_Com_Handler->PMSS_Web_srv($_send_PMS_para);
        } else {
            // Local Socket
            $this->load->model("PMS_Socket");
            $_send_PMS = $this->PMS_Socket->send_PMS($_send_PMS_para);
        }
        // Switch Socket or Web Srv END
        // $this->load->model("PMS_Socket");
        // $this->PMS_Socket->send_PMS($PR);

        $data = array(
            'Status' => 'QQ',
        );

        $this->db->set("ReqDT", "NOW(3)", FALSE);
        $this->db->where('ID', $AI_ID);
        $this->db->update('query', $data);
        return TRUE;
    }

    public function PL_($param = '') {

        if (isset($this->pms_object['ID'][0])) {
            if ($this->getPMS_Type_by_TransactionID($this->pms_object['P#'][0]) == 'QUERY') {
                $this->PL_PA_Query();
            } else {
                $this->PL_PA_Posting();
            }
        }
    }

    public function PA_($param = '') {

        if (isset($this->pms_object['P#'][0])) {
            if ($this->getPMS_Type_by_TransactionID($this->pms_object['P#'][0]) == 'QUERY') {
                $this->PL_PA_Query();
            } else {
                $this->PL_PA_Posting();
            }
        }
    }

    private function dateIsInBetween(DateTime $from, DateTime $to, DateTime $subject) {
        return $subject->getTimestamp() > $from->getTimestamp() && $subject->getTimestamp() < $to->getTimestamp() ? true : false;
    }

    public function PL_PA_Query() {

        if (isset($this->pms_object['P#'][0])) {
            $data = array(
                'Respond' => $this->str_in,
                'Status' => 'P'
            );

            $this->db->set("ResDT", "NOW(3)", FALSE);
            $this->db->where('ID', $this->pms_object['ID'][0]);
            $this->db->update('query', $data);

            $data = array(
                'Status' => 'F'
            );

            $data_query = array('ID' => $this->pms_object['ID'][0]);

            if (isset($this->pms_object[$this->Logger->ExID_Field]) && strlen($this->pms_object[$this->Logger->ExID_Field][0]) > 0) {
                //$data_query['ExID'] = $this->pms_object[$this->Logger->ExID_Field][0];
            }

            $PL_without_ExID = FALSE;

            if (isset($this->pms_object['PL'])) {
                $query = $this->db->get_where('query', $data_query);
                foreach ($query->result_array() as $row) {
                    foreach ($this->pms_object['G#'] as $GS_key => $value) {

                        $ExID_validate = FALSE;
                        if (isset($this->pms_object[$this->Logger->ExID_Field]) && isset($this->pms_object[$this->Logger->ExID_Field][$GS_key]) && strlen($this->pms_object[$this->Logger->ExID_Field][$GS_key]) > 0) {
                            if ($row['ExID'] == $this->pms_object[$this->Logger->ExID_Field][$GS_key])
                                $ExID_validate = TRUE;
                            else
                                $ExID_validate = FALSE;
                        } else if ($this->Logger->Settings['PMSName'] != 'Ginger PMS') {
                            $ExID_validate = TRUE;
                        }

                        if ($ExID_validate && $this->pms_object['RN'][$GS_key] == $row['RoomNo'] && strtoupper($row['LastName']) == strtoupper($this->pms_object['GN'][$GS_key])) {

                            //GA160331|GD160331
                            $GA = new DateTime("20" . $this->pms_object['GA'][$GS_key]);
                            $GD = new DateTime("20" . $this->pms_object['GD'][$GS_key] . " 23:59");

                            $GA_GD_valid = TRUE;
                            $GA_GD_valid = $this->dateIsInBetween($GA, $GD, new DateTime('now'));

                            if ($GA_GD_valid) {
                                // PMS insert/update
                                $data_object = array();
                                //var_dump($this->pms_object);
                                foreach ($this->pms_object as $key => $_value_Array) {

                                    $_value = $_value_Array[0];
                                    if (isset($_value_Array[$GS_key]))
                                        $_value = $_value_Array[$GS_key];

                                    if (isset($this->map_data[$key])) {
                                        $value = $_value;
                                        if (strlen($value) > 0)
                                            $data_object[$this->map_data[$key]] = $value;
                                    }
                                }

                                $query_pms_data = array('RegNo' => $this->pms_object['G#'][$GS_key]);
                                if ($this->Logger->Settings['PMSName'] == 'Ginger PMS' && isset($this->pms_object[$this->Logger->ExID_Field][$GS_key])) {
                                    $query_pms_data[$this->Logger->ExID_Field] = $this->pms_object[$this->Logger->ExID_Field][$GS_key];
                                }

                                $query_pms = $this->db->get_where('pms', $query_pms_data);
                                if ($query_pms->num_rows() > 0) {
                                    $this->db->set("ChangedBy", "PMS_srv[Q]");
                                    $this->db->set("ChangeDT", "NOW(3)", FALSE);
                                    $this->db->where('RegNo', $this->pms_object['G#'][$GS_key]);

                                    if ($this->Logger->Settings['PMSName'] == 'Ginger PMS' && isset($this->pms_object[$this->Logger->ExID_Field])) {
                                        $this->db->where($this->Logger->ExID_Field, $this->pms_object[$this->Logger->ExID_Field][$GS_key]);
                                    }

                                    $this->db->update('pms', $data_object);
                                } else {
                                    $this->db->set("AddedBy", "PMS_srv[Q]");
                                    $this->db->set("AddDT", "NOW(3)", FALSE);
                                    $this->db->insert('pms', $data_object);
                                }

                                if (isset($this->pms_object['G#']))
                                    $data['RegNo'] = $this->pms_object['G#'][$GS_key];
                                if (isset($this->pms_object['GN']))
                                    $data['LastName'] = $this->pms_object['GN'][$GS_key];
                                $data['Status'] = 'S';
                                break;
                            }
                        }
                    }
                }
            }

            $this->db->set("ResDT", "NOW(3)", FALSE);
            $this->db->where('ID', $this->pms_object['ID'][0]);
            $this->db->update('query', $data);
        }
    }

    public function PL_PA_Posting() {

        if (isset($this->pms_object['P#'][0])) {
            $data = array(
                'Respond' => $this->str_in,
                'Status' => 'P'
            );

            $this->db->set("ResDT", "NOW(3)", FALSE);
            $this->db->where('ID', $this->pms_object['ID'][0]);
            $this->db->update('posting', $data);
///
            $data = array(
                'Status' => 'F'
            );

            if (isset($this->pms_object['PA']) && isset($this->pms_object['AS'])) {
                if ($this->pms_object['AS'][0] == 'OK') {
                    $data['Status'] = 'S';
                }
            }

            if ($this->pms_object['ID'][0] == 'RPOST') {
                $PurchID = null;
                $query = $this->db->get_where('posting', array('ID' => $this->pms_object['ID'][0]));
                if ($query->num_rows() > 0) {
                    $row = $query->row_array();
                    $PurchID = $this->pms_object['ID'][0];
                }

                if ($data['Status'] == 'F') {
                    $this->RePosting_Purchase_invalid($PurchID);
                }
                else
                    $this->RePosting_Purchase_valid($PurchID);
            }

            $this->db->set("ResDT", "NOW(3)", FALSE);
            $this->db->where('ID', $this->pms_object['ID'][0]);
            $this->db->update('posting', $data);
        }
    }

    public function get_Post_row() {
        if ($this->postpms_id) {
            $query = $this->db->get_where('posting', array('ID' => $this->postpms_id));
            if ($query->num_rows() > 0) {
                return $query->row_array();
            }
            else
                return FALSE;
        }
        return FALSE;
    }

    public function RePosting_to_PMS() {

        try {

            $RePosting_Success = TRUE;
            $query = $this->db->get_where('posting', array('Status' => 'Q'));
            foreach ($query->result_array() as $posting_row) {
                $transaction_ID = $posting_row['TransID'];

                $PR = str_replace('IDPOST', 'IDRPOST', $posting_row['Request']);
                // Continue Posting
                // Switch Socket or Web Srv
                $_send_PMS = FALSE;
                $_send_PMS_para = $PR;
                if ($this->cloud_srv_active > 0) {
                    // HI Status        
                    $this->load->model("PMS_Com_Handler");
                    $_send_PMS = $this->PMS_Com_Handler->PMSS_Web_srv($_send_PMS_para);
                } else {
                    // Local Socket
                    $this->load->model("PMS_Socket");
                    $_send_PMS = $this->PMS_Socket->send_PMS($_send_PMS_para);
                }
                // Switch Socket or Web Srv END


                if ($_send_PMS === FALSE) {
                    //$this->db->where('ID', $this->query_id);
                    //$this->db->delete('query');
                    $RePosting_Success = FALSE;
                }

                $data = array(
                    'Status' => 'QQ',
                );

                $this->db->set("ReqDT", "NOW(3)", FALSE);
                $this->db->where('ID', $transaction_ID);
                $this->db->update('posting', $data);
            }
            return $RePosting_Success;
        } catch (Exception $exc) {
            //echo $exc->getTraceAsString();
            return FALSE;
        }
    }

    public function insert_Post_PMS($para = array()) {

//$query = $this->db->query('SELECT `ID`, `RoomNo`, `LastName`, `RegNo`, `Amount1`, `Amount2`, `Amount3`, `Amount4`, `Tax1`, `Tax2`, `Tax3`, `Tax4`, `NameRes`, `Request`, `ReqDT`, `RStatus`, `Response`, `ResDT`, `TransID` FROM `postpms`');
//	return $query->row();
        try {

// $this->db->trans_start();
//    $transaction_ID = isset($para['TransID']) ? $para['TransID'] : $this->getPOSTTransaction_ID();
//$transaction_ID = isset($para['TransID']) ? $para['TransID'] : "";
            $data = array(
                'PlanName' => isset($para['PlanName']) ? $para['PlanName'] : "",
                'Amount' => isset($para['Amount']) ? $para['Amount'] : "0",
                'TotalAmount' => isset($para['Amount']) ? $para['Amount'] : "0",
                //'Plan_Desc' => isset($para['Plan_Desc']) ? $para['Plan_Desc'] : "",
                'MAC' => isset($para['MAC']) ? $para['MAC'] : $this->_MAC,
                'RoomNo' => isset($para['RoomNo']) ? $para['RoomNo'] : "",
                'LastName' => isset($para['LastName']) ? $para['LastName'] : "",
                'RegNo' => isset($para['RegNo']) ? $para['RegNo'] : "",
                'Status' => "Q",
                'TransID' => isset($para['TransID']) ? $para['TransID'] : "0",
                'ExID' => isset($para['ExID']) ? $para['ExID'] : "",
                'Site_ID' => isset($para['Site_ID']) ? $para['Site_ID'] : "",
            );

            $this->_HI = $para['Site_ID'];

            $transaction_ID = $this->getPOSTTransaction_ID($data);
            //$data['TransID'] = $this->postpms_id;

            $TA = floor($data['TotalAmount'] * 100);

            $transaction_ID = $this->getPMSTransaction_ID('P', $this->postpms_id);
///////////////
//PR|DA150519|TI113425|WS00e0b40e4003|ID331|G#36912|GNSingh|RN1614|TA80000|CT24 Hours Premium|P#4315|PCT|PTP|

            $this->_previous_Sent = $PR = "PR|DA$this->now_date|TI$this->now_time|WS{$data['MAC']}|ID$this->postpms_id|G#{$data['RegNo']}|GN{$data['LastName']}|RN{$data['RoomNo']}|TA$TA|CT{$data['PlanName']}|P#$this->pmstransaction_id|PCT|PTP|";

            if ($this->Logger->Settings['PMSName'] == 'Ginger PMS') {

                $NP_tag_String = "";
                $NP_tag = $this->getNPY($data['RoomNo'], $data['LastName'], $data['RegNo'], $data['ExID']);
                if ($NP_tag) {
                    $TA = 0;
                    $NP_tag_String = "NPY|";
                }

                $this->_previous_Sent = $PR = "PR|DA$this->now_date|TI$this->now_time|WS{$data['MAC']}|ID$this->postpms_id|G#{$data['RegNo']}|GN{$data['LastName']}|RN{$data['RoomNo']}|TA$TA|CT{$data['PlanName']}|P#$this->pmstransaction_id|$NP_tag_String";

                if ($this->Logger->ExID && strlen($data['ExID']) > 0)
                    $this->_previous_Sent = $PR = "PR|DA$this->now_date|TI$this->now_time|WS{$data['MAC']}|ID$this->postpms_id|{$this->Logger->ExID_Field}{$data['ExID']}|G#{$data['RegNo']}|GN{$data['LastName']}|RN{$data['RoomNo']}|TA$TA|CT{$data['PlanName']}|P#$this->pmstransaction_id|$NP_tag_String";
            }

            $data['Request'] = $PR;

            unset($data['ExID']);

            $this->db->set("PrcDT", "NOW(3)", FALSE);
            $this->db->where('ID', $this->postpms_id);
            $this->db->update('posting', $data);



            // Continue Posting
            // Switch Socket or Web Srv
            $_send_PMS = FALSE;
            $_send_PMS_para = $PR;
            if ($this->cloud_srv_active > 0) {
                // HI Status        
                $this->load->model("PMS_Com_Handler");
                $_send_PMS = $this->PMS_Com_Handler->PMSS_Web_srv($_send_PMS_para);
            } else {
                // Local Socket
                $this->load->model("PMS_Socket");
                $_send_PMS = $this->PMS_Socket->send_PMS($_send_PMS_para);
            }
            // Switch Socket or Web Srv END

            if ($_send_PMS === FALSE) {
                //$this->db->where('ID', $this->query_id);
                //$this->db->delete('query');
                return FALSE;
            }

            $data = array(
                'Status' => 'QQ',
            );

            $this->db->set("ReqDT", "NOW(3)", FALSE);
            $this->db->where('ID', $this->postpms_id);
            $this->db->update('posting', $data);

///////////////
            return TRUE;
        } catch (Exception $exc) {
//echo $exc->getTraceAsString();
            return FALSE;
        }
    }

    private function getNPY($RN ='', $LN ='', $RG ='', $ExID ='') {

        $query_pms_data = array('RegNo' => $RG);
        if ($this->Logger->Settings['PMSName'] == 'Ginger PMS' && strlen($ExID)) {
            $query_pms_data[$this->Logger->ExID_Field] = $ExID;
        }

        $query_pms = $this->db->get_where('pms', $query_pms_data);
        if ($query_pms->num_rows() > 0) {
            $row = $query_pms->row_array();
            if ($row['NoPost'] == 'Y')
                return TRUE;
        }
        return FALSE;
    }

    public function insert_Query_PMS($para = array()) {
//$query = $this->db->query('SELECT `ID`, `RoomNo`, `LastName`, `RegNo`, `Amount1`, `Amount2`, `Amount3`, `Amount4`, `Tax1`, `Tax2`, `Tax3`, `Tax4`, `NameRes`, `Request`, `ReqDT`, `RStatus`, `Response`, `ResDT`, `TransID` FROM `postpms`');
//	return $query->row();
        try {

//$this->db->trans_start();
            $data = array(
//'Plan_Desc' => isset($para['Plan_Desc']) ? $para['Plan_Desc'] : "",
                'RoomNo' => isset($para['RoomNo']) ? $para['RoomNo'] : "-1",
                'ExID' => isset($para['ExID']) ? $para['ExID'] : "",
                'Status' => "Q"
            );

            if (isset($para['MAC']) && strlen($para['MAC']) > 2)
                $this->_MAC = $para['MAC'];

            $Query_ID = $this->getQUERYTransaction_ID($data);
//$transaction_ID = (strlen($para['TransID']) > 0) ? $para['TransID'] : $this->getQUERYTransaction_ID($data);
            $data['TransID'] = $this->query_id;

            if (!$para['LastName'] === FALSE) {
                $data = array_merge($data, array(
                    'LastName' => $para['LastName']));
            }

            if (!$para['Site_ID'] === FALSE) {
                $data = array_merge($data, array(
                    'Site_ID' => $para['Site_ID']));
            }


            if (!$para['RegNo'] === FALSE) {
                $data = array_merge($data, array(
                    'RegNo' => $para['RegNo']));
            }


            $this->_RN = $para['RoomNo'];
            $this->_LN = $para['LastName'];
            $this->_RegNo = $para['RegNo'];
            $this->_HI = $para['Site_ID'];



            $transaction_ID = $this->getPMSTransaction_ID('Q', $this->query_id);


            ///////////////
            $this->_previous_Sent = $PR = "PR|WS$this->_MAC|ID$this->query_id|PI$this->_RN|DA$this->now_date|TI$this->now_time|P#$this->pmstransaction_id|";

            if ($this->Logger->ExID && strlen($data['ExID']) > 0) {
                $this->_previous_Sent = $PR = "PR|WS$this->_MAC|ID$this->query_id|{$this->Logger->ExID_Field}{$data['ExID']}|PI$this->_RN|DA$this->now_date|TI$this->now_time|P#$this->pmstransaction_id|";
            }

            $data_query = array(
                'RoomNo' => $this->_RN,
                'LastName' => $this->_LN,
                'RegNo' => $this->_RegNo,
                'Request' => $PR,
                'Status' => 'Q',
                'Site_ID' => $this->_HI,
            );

            $this->Logger->transaction_log($Log_App = 'PMS_SRV', $this->router->class . '/' . $this->router->method, $Log_Transaction = 'Query_POST: ' . json_encode($data_query), $Log_Validation = "");

            if ($this->Logger->ExID && strlen($data['ExID']) > 0) {
                $data_query['ExID'] = $data['ExID'];
            }

            $this->db->set("PrcDT", "NOW(3)", FALSE);
            $this->db->where('ID', $this->query_id);
            $this->db->update('query', $data_query);


//PR|WS00e0b409be51|ID499|PI200|DA160405|TI113211|P#499|
// Continue Query
            // Switch Socket or Web Srv
            $_send_PMS = FALSE;
            $_send_PMS_para = $PR;
            if ($this->cloud_srv_active > 0) {
                // HI Status        
                $this->load->model("PMS_Com_Handler");
                $_send_PMS = $this->PMS_Com_Handler->PMSS_Web_srv($_send_PMS_para);
            } else {
                // Local Socket
                $this->load->model("PMS_Socket");
                $_send_PMS = $this->PMS_Socket->send_PMS($_send_PMS_para);
            }
            // Switch Socket or Web Srv END


            if ($_send_PMS === FALSE) {
                //$this->db->where('ID', $this->query_id);
                //$this->db->delete('query');
                return FALSE;
            }

            $data = array(
                'Status' => 'QQ',
            );

            $this->db->set("ReqDT", "NOW(3)", FALSE);
            $this->db->where('ID', $this->query_id);
            $this->db->update('query', $data);

///////////////

            return TRUE;
        } catch (Exception $exc) {
            die();
//echo $exc->getTraceAsString();
            return FALSE;
        }
    }

    public function RePosting_Purchase_invalid($PurchID = null) {

        try {
            if ($PurchID > 0)
                $this->removePurchID_MACs($PurchID);
            else
                return FALSE;

            $query = $this->intraserv_DB->query("SELECT `PurchID`, `CurntUserID`, `PlanID`, `AuthType`, `TransID`, `TotalAmount`, `SubAmount`, `Tax1`, `Tax2`, `Tax3`, `Tax4`, `MAC`, `Status`, `TransDate`
            FROM `purchase` 
            WHERE `Status`=? AND `PurchID`=? LIMIT 1", array('Pending', $PurchID));
            if ($query->num_rows() > 0) {

                $purchase = $query->row_array();
                $data = array(
                    'Status' => 'invalid'
                );

                //$this->db->trans_start();
                $this->intraserv_DB->where('PurchID', $PurchID);
                $this->intraserv_DB->update('purchase', array(
                    'Status' => 'RPinvalid'
                ));

                $this->intraserv_DB->where('CurntUserID', $purchase['CurntUserID']);
                $this->intraserv_DB->update('currentuser', $data);

                $this->intraserv_DB->where('CurntUserID', $purchase['CurntUserID']);
                $this->intraserv_DB->where('MAC', $MAC);
                $this->intraserv_DB->update('currentmac', $data);
//if ($this->db->trans_status() === FALSE) {
//    // generate an error... or use the log_message() function to log your error
                //return FALSE;
//}
                return TRUE;
            }
            return FALSE;
        } catch (Exception $exc) {
//echo $exc->getTraceAsString();
            return FALSE;
        }
    }

    public function RePosting_Purchase_valid($PurchID = null) {

        try {
            if (is_null($PurchID))
                return FALSE;

            $query = $this->intraserv_DB->query("SELECT `PurchID`, `CurntUserID`, `PlanID`, `AuthType`, `TransID`, `TotalAmount`, `SubAmount`, `Tax1`, `Tax2`, `Tax3`, `Tax4`, `MAC`, `Status`, `TransDate`
            FROM `purchase` 
            WHERE `Status`=? AND `PurchID`=? LIMIT 1", array('Pending', $PurchID));
            if ($query->num_rows() > 0) {

                $purchase = $query->row_array();
                $data = array(
                    'Status' => 'valid'
                );

                //$this->db->trans_start();
                $this->intraserv_DB->where('PurchID', $PurchID);
                $this->intraserv_DB->update('purchase', array(
                    'Status' => 'RPvalid'
                ));

                $this->intraserv_DB->where('CurntUserID', $purchase['CurntUserID']);
                $this->intraserv_DB->update('currentuser', $data);

                $this->intraserv_DB->where('CurntUserID', $purchase['CurntUserID']);
                $this->intraserv_DB->where('MAC', $MAC);
                $this->intraserv_DB->update('currentmac', $data);
//if ($this->db->trans_status() === FALSE) {
//    // generate an error... or use the log_message() function to log your error
                //return FALSE;
//}
                return TRUE;
            }
            return FALSE;
        } catch (Exception $exc) {
//echo $exc->getTraceAsString();
            return FALSE;
        }
    }

    public function Internel_Purchase($PurchID = null, $MAC =null) {

        return TRUE;
        try {

            if (is_null($PurchID) || is_null($MAC))
                return FALSE;

            $query = $this->intraserv_DB->query("SELECT `PurchID`, `CurntUserID`, `PlanID`, `AuthType`, `TransID`, `TotalAmount`, `SubAmount`, `Tax1`, `Tax2`, `Tax3`, `Tax4`, `MAC`, `Status`, `TransDate`
            FROM `purchase` 
            WHERE `Status`=? AND `PurchID`=? AND `MAC`=? LIMIT 1", array('Pending', $PurchID, $MAC));
            if ($query->num_rows() > 0) {

                $purchase = $query->row_array();
                $data = array(
                    'Status' => 'valid'
                );

//$this->db->trans_start();
                $this->intraserv_DB->where('PurchID', $PurchID);
                $this->intraserv_DB->update('purchase', $data);

                $this->intraserv_DB->where('CurntUserID', $purchase['CurntUserID']);
                $this->intraserv_DB->update('currentuser', $data);

                $this->intraserv_DB->where('CurntUserID', $purchase['CurntUserID']);
                $this->intraserv_DB->where('MAC', $MAC);
                $this->intraserv_DB->update('currentmac', $data);
//if ($this->db->trans_status() === FALSE) {
//    // generate an error... or use the log_message() function to log your error
                return FALSE;
//}
                return TRUE;
            }
            return TRUE;
        } catch (Exception $exc) {
//echo $exc->getTraceAsString();
            return FALSE;
        }
    }

    public function insert_Query_PMS_OLD($para = array()) {
//$query = $this->db->query('SELECT `ID`, `RoomNo`, `LastName`, `RegNo`, `Amount1`, `Amount2`, `Amount3`, `Amount4`, `Tax1`, `Tax2`, `Tax3`, `Tax4`, `NameRes`, `Request`, `ReqDT`, `RStatus`, `Response`, `ResDT`, `TransID` FROM `postpms`');
//	return $query->row();
        try {

            $sql = "INSERT INTO `querypms`
				(`ID`, `RoomNo`, `Name`,RegNo, `RStatus`, `TransID`, `PMSStatus`, `QueryOnly`,ResDT) VALUES
				('$comm_id','$room_no','$lname','$reg_no','QPending','$Trans_comm_id','Pending','1',NOW(3))";

//$transaction_ID = $this->getTransaction_ID();
            $data = array(
                'RoomNo' => isset($para['RoomNo']) ? $para['RoomNo'] : "",
                'Name' => isset($para['LastName']) ? $para['LastName'] : "",
                'RegNo' => isset($para['RegNo']) ? $para['RegNo'] : "",
                'RStatus' => "QPending",
                'PMSStatus' => "Pending",
                'QueryOnly' => "1" //,
//'TransID' => $transaction_ID
            );

            $this->db->set("ResDT", "NOW(3)", FALSE);
            $this->db->insert('querypms', $data);
//$this->postpms_id = $this->db->insert_id();


            if ($this->db->insert('querypms', $data)) {
// generate an error... or use the log_message() function to log your error
                return $this->db->insert_id();
            }
            return FALSE;
        } catch (Exception $exc) {
//echo $exc->getTraceAsString();
            return FALSE;
        }
    }

    /**
     * @param int Integer to set this objects ID to
     */
    public function getSettings_Row() {

        $query = $this->intraserv_DB->get('commonsetting');
        if ($query->num_rows() > 0) {
            return $query->row_array();
        }
        else
            return FALSE;
    }

    public function getQuery_Row() {
        if ($this->query_id) {
            $query = $this->db->get_where('query', array('ID' => $this->query_id));
            if ($query->num_rows() > 0) {
                return $query->row_array();
            }
            else
                return FALSE;
        }
        return FALSE;
    }

    /**
      USERNAME
     * */

    /**
     * @return string [$this->_username] Return this objects username
     */
    public function getTransaction_Result() {

        $max_responese_time_sec = 20;
        $wait_time_sec = 1;

        $count_time = 0;
        while ($result = $this->getTransaction_Row()) {
            if (is_array($result)) {

                $Status = strtoupper($result['Status']);
                if ($Status == 'F') {
                    return array("STATUS" => "INVALID", "DESC" => "Transaction INVALID");
                } elseif ($Status == 'S') {

                    $sql = "SELECT `ID`, `RoomNo`, `LastName`, `RegNo`, `ResvNo`, `ArrivalDT`, `DepartureDT`, `VIPStatus`, `Title`, `NoPost`, `Language`, `TransDate` 
            FROM `pms` ";
                    $Where = "WHERE `ArrivalDT`<=DATE(NOW()) AND `DepartureDT`>=DATE(NOW()) AND ";
                    $Where.=$this->_RegNo ? " `RegNo`='" . $this->_RegNo . "' AND " : "";
                    $Where.=$this->_RN ? " `RoomNo`='" . $this->_RN . "' AND " : "";
                    $Where.=$this->_LN ? " `LastName`='" . $this->_LN . "' AND " : "";
                    $Where.= " 1=1";

                    $query = $this->db->query($sql . $Where);
                    if ($query && $query->num_rows() > 0) {
                        $row = $query->row_array();
                        if (isset($row['RegNo']))
                            return array("STATUS" => "COMPLETE", "REGNO" => $row['RegNo'], "DESC" => "Transaction SUCCESS");
                        else
                            return array("STATUS" => "COMPLETE", "DESC" => "Transaction SUCCESS");
                    }
                    return array("STATUS" => "COMPLETE", "DESC" => "Transaction SUCCESS");
                }
            }

            sleep($wait_time_sec);
            if ($count_time > $max_responese_time_sec) {
                return array("STATUS" => "TIMEOUT", "DESC" => "PMS Transaction TIMEOUT " . $max_responese_time_sec . " SECs");
            }
            $count_time+=$wait_time_sec;
        }
        return FALSE;
    }

    public function getQuery_Result($retry_count =1) {

        $max_responese_time_sec = 30;
        $wait_time_sec = 1;

        $count_time = 0;
        while ($result = $this->getQuery_Row()) {
            if (is_array($result)) {

                $Status = strtoupper($result['Status']);
                if ($Status == 'F') {
                    return array("STATUS" => "INVALID", "DESC" => "Transaction INVALID");
                } elseif ($Status == 'S') {

                    $res_result = array();
                    $res_result["STATUS"] = "COMPLETE";
                    $res_result["DESC"] = "Transaction SUCCESS";
                    if (isset($result['RoomNo']))
                        $res_result["ROOMNO"] = $result['RoomNo'];
                    if (isset($result['LastName']))
                        $res_result["LNAME"] = $result['LastName'];
                    if (isset($result['RegNo']))
                        $res_result["REGNO"] = $result['RegNo'];

                    return $res_result;
                }
            }

            sleep($wait_time_sec);
            if ($count_time > $max_responese_time_sec) {
                return array("STATUS" => "TIMEOUT", "DESC" => "PMS Transaction TIMEOUT ( " . $max_responese_time_sec . " SECs ) * $retry_count Retry Attempt(s)");
            }
            $count_time+=$wait_time_sec;
        }
        return FALSE;
    }

    public function getTransaction_Row() {
        if ($this->pmstransaction_id) {
            $query = $this->db->get_where('pmstransaction', array('ID' => $this->pmstransaction_id));
            if ($query->num_rows() > 0) {
                return $query->row_array();
            }
            else
                return FALSE;
        }
        return FALSE;
    }

    /**
      USERNAME
     * */

    /**
     * @return string [$this->_username] Return this objects username
     */
    public function getPost_Result($retry_count =1) {

        $max_responese_time_sec = 20;
        $wait_time_sec = 1;

        $count_time = 0;
        while ($result = $this->get_Post_row()) {
            if (is_array($result)) {

                $Status = strtoupper($result['Status']);
                if ($Status == 'F') {
                    return array("STATUS" => "INVALID", "DESC" => "TRANSACTION INVALID");
                } elseif ($Status == 'S') {
                    return array("STATUS" => "COMPLETE", "DESC" => "TRANSACTION SUCCESS");
                }
            }

            sleep($wait_time_sec);
            if ($count_time > $max_responese_time_sec) {
                return array("STATUS" => "TIMEOUT", "DESC" => "PMS RESPONSE TIMEOUT ( " . $max_responese_time_sec . " SECs ) * $retry_count Retry Attempt(s)");
            }
            $count_time+=$wait_time_sec;
        }
        return FALSE;
    }

    public function getPostSrv_Result($Transaction_ID) {

        $query = $this->db->order_by("AddDatetime", "desc");
        $query = $this->db->get_where('posting', array('TransID' => $Transaction_ID));
        if ($query->num_rows() > 0) {
            $result = $query->row_array();

            if (is_array($result)) {

                $Status = strtoupper($result['Status']);
                if ($Status == 'F') {
                    return array("STATUS" => "INVALID", "DESC" => "TRANSACTION INVALID");
                } elseif ($Status == 'S') {
                    return array("STATUS" => "COMPLETE", "DESC" => "TRANSACTION SUCCESS");
                } else {
                    return array("STATUS" => "PENDING", "DESC" => "TRANSACTION PENDING");
                }
            }
        }
        return FALSE;
    }

    private function getPMS_Type_by_RefID($RefID = 0) {

        try {

            $query = $this->db->get_where('pmstransaction', array('RefID' => $RefID));
            if ($query->num_rows() > 0) {
                $row = $query->row();

                return '' . $row->Mode;
            }

            return FALSE;
        } catch (Exception $exc) {
            return FALSE;
        }
    }

    private function getPMS_Type_by_TransactionID($id = 0) {

        try {

            $query = $this->db->get_where('pmstransaction', array('ID' => $id));
            if ($query->num_rows() > 0) {
                $row = $query->row();
                if ($row->Mode == 'Q')
                    return 'QUERY';
                else if ($row->Mode == 'P')
                    return 'POST';
            }
            return FALSE;
        } catch (Exception $exc) {
            return FALSE;
        }
    }

    private function getPMSTransaction_ID($Mode = 'Q', $RefID = 0) {

        try {

            $data = array(
                'RefID' => $RefID,
                'Mode' => $Mode,
                'Status' => "Q"
            );

            $this->db->set("AddDT", "NOW(3)", FALSE);
            $this->db->insert('pmstransaction', $data);
            $this->pmstransaction_id = $this->db->insert_id();

            return $this->pmstransaction_id;
        } catch (Exception $exc) {
            return FALSE;
        }
    }

    private function getPOSTTransaction_ID($para = array()) {

        try {

            $data = array(
                'RoomNo' => $para['RoomNo'],
                'Status' => "P"
            );

            $this->db->set("AddDatetime", "NOW(3)", FALSE);
            $this->db->insert('posting', $data);
            $this->postpms_id = $this->db->insert_id();

            return $this->postpms_id;
        } catch (Exception $exc) {
            return FALSE;
        }
    }

    private function getQUERYTransaction_ID($para = array()) {

        try {

            $data = array(
                'RoomNo' => $para['RoomNo'],
                'Status' => "P"
            );

            $this->db->set("AddDatetime", "NOW(3)", FALSE);
            $this->db->insert('query', $data);
            $this->query_id = $this->db->insert_id();

            return $this->query_id;
        } catch (Exception $exc) {
            return FALSE;
        }
    }

    /**
     * @param string String to set this objects username to
     */
    public function setUsername($value) {
        $this->_username = $value;
    }

    /**
      PASSWORD
     * */

    /**
     * @return string [$this->_password] Return this objects password
     */
    public function getPMStable() {

        $query = $this->db->query('SELECT * FROM `pms`');
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }

        return FALSE;
    }

    public function removeUserPurchase_MACs($RegNo = '-1', $ExID = FALSE) {

        $return = true;

        $data_array = array('RegNo' => $RegNo);
        if (strlen($ExID) > 0) {
            $data_array['ExID'] = $ExID;
        }

        $query = $this->intraserv_DB->get_where('purchase', $data_array);
        if ($query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                if ($this->GOPurchID_MACs($row['PurchID']) == FALSE) {
                    $return = false;
                }
            }
        }
        return $return;
    }

    public function GC_intraSERV($para = FALSE) {

        $data = array();
        if (strlen($para['LastName']) > 0)
            $data['LastName'] = $para['LastName'];
        if (strlen($para['RoomNo']) > 0)
            $data['RoomNo'] = $para['RoomNo'];

        $data_array = array('RegNo' => $para['RegNo']);
        if (strlen($para['ExID']) > 0) {
            $data_array['ExID'] = $para['ExID'];
        }

        if (strlen($para['RegNo']) > 0) {
            $this->intraserv_DB->where($data_array);
            $this->intraserv_DB->update('currentuser', $data);

            $this->intraserv_DB->where($data_array);
            $this->intraserv_DB->update('guest_profile', $data);
        }
    }

    public function updateGuestExtend($RegNo = '-1', $GDDate="now", $ExID = FALSE) {
//2016-02-23
        $data = array();
        $return = FALSE;
        $data_ext = '';
        if (strlen($ExID) > 0) {
            $data_ext = " AND `ExID`='$ExID'";
        }
        $query = $this->intraserv_DB->query("SELECT `PurchID`, `CurntUserID`,`Desc`, `RegNo`, `RoomNo`, `LastName`, `PlanID`, `TransDate`,TIMESTAMPDIFF(MINUTE,`TransDate`,'$GDDate 23:59:59') AS 'DIFF' FROM `purchase` WHERE `PlanID`=(SELECT `PlanID` FROM `roomplan` WHERE `Duration`='-1' and `PlanStatus`='1') and `RegNo`='$RegNo' $data_ext");
//        $data="SELECT `PurchID`, `CurntUserID`,`Desc`, `RegNo`, `RoomNo`, `LastName`, `PlanID`, `TransDate`,TIMESTAMPDIFF(MINUTE,`TransDate`,'$GDDate') AS 'DIFF' FROM `purchase` WHERE `PlanID`=(SELECT `PlanID` FROM `roomplan` WHERE `Duration`='-1' and `PlanStatus`='1') and `RegNo`='$RegNo'";
//        return $data;
        if ($query->num_rows() > 0) {
            $purchase_rec = $query->row_array();
//            $Transdate = new DateTime($purchase_rec['TransDate']);
//            $DepartureDate = new DateTime($GDDate);
//            
//            $data2 = array(
//                'trnasDate' => $Transdate,
//                'da'=> $DepartureDate
//            );
//
//            $to_time = strtotime($DepartureDate);
//            $from_time = strtotime($Transdate);
//            $PlanExpireDuration = round(abs($to_time - $from_time) / 60, 2);
            if ($purchase_rec['DIFF'] > 0) {
                $data = array(
                    'PlanExpiration' => $purchase_rec['DIFF']
                );

                $this->intraserv_DB->where('CurntUserID', $purchase_rec['CurntUserID']);
                if ($this->intraserv_DB->update('currentuser', $data)) {
                    $return = TRUE;
                }
            } else {
                $return = FALSE;
            }
        }

        return $return;
    }

    public function GOPurchID_MACs($Transaction_ID) {

        $this->Comman_Settings_load();
        $return = true;

        $this->intraserv_DB->distinct();
        $query = $this->intraserv_DB->get_where('maclog', array('PurchID' => $Transaction_ID));

        $query_purchase = $this->intraserv_DB->get_where('purchase', array('PurchID' => $Transaction_ID));
        $row_purchase = $query_purchase->row_array();

        if ($query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                $this->removePassword($row_purchase['LastName'], $row_purchase['RoomNo'], $row_purchase['RegNo'], $row_purchase['ExID']);
                $this->setCurrentUserInvalid($row_purchase['CurntUserID'], $row_purchase['RoomNo']);
                if ($this->remove_MACs($row['MAC']) == FALSE) {
                    $return = false;
                    $this->Logger->transaction_log($Log_App = 'PMS_SRV', $this->router->class . '/' . $this->router->method, $Log_Transaction = 'GO: PurchID:' . $Transaction_ID . ' , CurntUserID:' . $row_purchase['CurntUserID'] . ' - GO Failed', $row['MAC'], $Log_Validation = "");
                }
                else
                    $this->Logger->transaction_log($Log_App = 'PMS_SRV', $this->router->class . '/' . $this->router->method, $Log_Transaction = 'GO: PurchID:' . $Transaction_ID . ' , CurntUserID:' . $row_purchase['CurntUserID'] . ' - GO', $row['MAC'], $Log_Validation = "");
            }
        }
        else {
            $this->Logger->transaction_log($Log_App = 'PMS_SRV', $this->router->class . '/' . $this->router->method, $Log_Transaction = 'GO: PurchID:' . $Transaction_ID . ' Not Found', $row['MAC'], $Log_Validation = "");
        }
        return $return;
    }

    public function removePurchID_MACs($Transaction_ID) {

        $this->Comman_Settings_load();
        $return = true;

        $this->intraserv_DB->distinct();
        $query = $this->intraserv_DB->get_where('maclog', array('PurchID' => $Transaction_ID));

        $query_purchase = $this->intraserv_DB->get_where('purchase', array('PurchID' => $Transaction_ID));
        $row_purchase = $query_purchase->row_array();

        if ($query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {

                $this->removePassword($row_purchase['LastName'], $row_purchase['RoomNo'], $row_purchase['RegNo'], $row_purchase['ExID']);
                $this->setCurrentUserInvalid($row_purchase['CurntUserID'], $row_purchase['RoomNo']);
                if ($this->remove_MACs($row['MAC']) == FALSE) {
                    $return = false;
                    $this->Logger->transaction_log($Log_App = 'PMS_SRV', $this->router->class . '/' . $this->router->method, $Log_Transaction = 'RM: PurchID:' . $Transaction_ID . ' , CurntUserID:' . $row_purchase['CurntUserID'] . ' - Remove Failed', $row['MAC'], $Log_Validation = "");
                }
                else
                    $this->Logger->transaction_log($Log_App = 'PMS_SRV', $this->router->class . '/' . $this->router->method, $Log_Transaction = 'RM: PurchID:' . $Transaction_ID . ' , CurntUserID:' . $row_purchase['CurntUserID'] . ' - Removed', $row['MAC'], $Log_Validation = "");
            }
        }
        else {
            $this->Logger->transaction_log($Log_App = 'PMS_SRV', $this->router->class . '/' . $this->router->method, $Log_Transaction = 'RM: PurchID:' . $Transaction_ID . ' No Record Found', '', "");
        }
        if ($return) {
            $data = array(
                'Status' => 'RPinvalid'
            );
            $this->intraserv_DB->where('PurchID', $Transaction_ID);
            $this->intraserv_DB->update('purchase', $data);
        }
        return $return;
    }

    public function removePassword($LastName = '0', $RoomNo='0', $RegNo='', $ExID='') {

        $data_array = array('LastName' => $LastName, 'RoomNo' => $RoomNo);
        if (strlen($RegNo) > 0)
            $data_array['RegNo'] = $RegNo;
        if (strlen($ExID) > 0)
            $data_array['ExID'] = $ExID;

        $query = $this->intraserv_DB->get_where('guest_profile', $data_array);
        $ID = '0';
        if ($query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                $ID = $row['ID'];
                $this->intraserv_DB->where('UserID', $row['ID']);
                $this->intraserv_DB->delete('device_reg');
            }
        }
        $this->intraserv_DB->where('ID', $ID);
        return $this->intraserv_DB->delete('guest_profile');
    }

    public function setCurrentUserInvalid($CurntUserID = '0', $RoomNo='0') {

        $return = FALSE;
        $data = array(
            'Status' => 'invalid'
        );

        $this->intraserv_DB->where(array('CurntUserID' => $CurntUserID, 'RoomNo' => $RoomNo));
        if ($this->intraserv_DB->update('currentuser', $data))
            $return = true;
        return $return;
    }

    public function remove_MACs($MAC) {

        if ($this->Iptables) {
            return $this->remove_IPtables_Access($MAC);
        } else {
            return $this->_NDX_user_delete($MAC);
        }
        return FALSE;
    }

    public function _NDX_user_delete($MA = '') {

        $this->load->model("NDX_User_Model");
        $this->load->model("NDX_XML");
        $this->NDX_XML->_url = 'http://' . $this->Settings['GatewayIP'] . ':1111/usg/command.xml';
        for ($retry = 0; $retry < 1; $retry++) {
            $this->NDX_User_Model->_MA_ADDR = $MA;

            if ($this->NDX_XML->NDX_RADIUS_LOGOUT($this->NDX_User_Model)) {
                return TRUE;
            }
            if ($this->NDX_XML->NDX_XML_USER_DELETE($this->NDX_User_Model)) {
                return TRUE;
            }
        }
        return TRUE;
    }

    function remove_IPtables_Access($inMAC) {

        $IP = '';
        $inMAC = str_replace(array(':', '-'), '', strtoupper($inMAC));
        $MAC = join(':', str_split($inMAC, 2));
        $query = $this->intraserv_DB->get_where('pt_mac_table', array('MAC' => $MAC));
        if ($query->num_rows() > 0) {
            $pt_mac_table_row = $query->result_array();
            $IP = $pt_mac_table_row['IP'];
// MAC + IP
            $cm = " $this->Sudo iptables -D internet -t mangle -m mac --mac-source " . $MAC . " -s $IP -j RETURN 2>&1 || ";
// MAC
            $cm = $cm . " $this->Sudo iptables -D internet -t mangle -m mac --mac-source " . $MAC . " -j RETURN 2>&1";

            $out = exec($cm);

// IP BW

            $WAN = 'eth0';
            $LAN = 'eth1';

            $IP_BW_Deny = $this->Sudo . " iptables -vnL FORWARD | grep  '$WAN *$LAN' | grep '$IP' | awk '{system(\\\"sudo iptables -D FORWARD -i $WAN -o $LAN -d  '$IP' -j MARK --set-mark \\\"\\$12)}';$this->Sudo iptables -vnL FORWARD | grep  '$LAN *$WAN' | grep '$IP' | awk '{system(\\\"sudo iptables -D FORWARD -i $LAN -o $WAN -s  '$IP' -j MARK --set-mark \\\"\\$12)}'";
            exec($IP_BW_Deny);

            $query = $this->intraserv_DB->query("SELECT `ID`, `JOB_ID`, `MAC`, `CREATED_TIME`, `JOB_TIME`, 
            `DESC` FROM `pt_at_jobs` WHERE MAC=? 
                AND JOB_TIME>=NOW()", array($MAC));
            if ($query->num_rows() > 0) {

                foreach ($query->result_array() as $result) {

                    $result['JOB_ID'] = trim($result['JOB_ID']);
                    $cm_rm = $this->Sudo . $this->at_Sch_Remove . $result['JOB_ID'];
                    exec($cm_rm);
                    $this->intraserv_DB->simple_query("UPDATE `pt_at_jobs` SET `STATUS`='SK' WHERE `JOB_ID`='" . $result['JOB_ID'] . "'");
                }
            }
            $this->intraserv_DB->simple_query("UPDATE `pt_mac_table` SET `INTERNET_STATUS`='0',`LAST_ACCESS`= NOW(),`EXPIRE_TIME`=NOW()
                WHERE `MAC`='" . $MAC . "'");
            $this->intraserv_DB->simple_query("DELETE FROM `currentmac` WHERE `MAC`='" . str_replace(":", "", $MAC) . "'");
        }
    }

    public function Comman_Settings_load() {
        $sql = "SELECT `GatewayID`, `GatewayIP`, `BWUp`, `BWDown`, `MailConfigID`, `RoomValidation`,
            `BillingPolicy`, `GuestAuthMode`, `SiteMode`, `PMSID`, `PMSStaus`, `PMSIP`, `PMSPort`,
            `PMSLastUpdate`,
             TIMESTAMPDIFF(SECOND,`PMSLastUpdate`,NOW()) AS 'PMS_Interval',(SELECT COUNT('ID') 
             FROM  `sitemaster`) AS 'SiteLive' 
             FROM `commonsetting` LIMIT 1";
        $query = $this->intraserv_DB->query($sql);
//echo "First Time Load Settings - $Comman_Settings_loaded\n";
        $Settings = array();
        if ($query) {
            if ($query->num_rows() > 0) {

                $this->Settings = $row = $query->row_array();
                $this->Iptables = TRUE;
                $sql = "SELECT cs.`GatewayIP`, cs.`BWUp`, cs.`BWDown`, cs.`MailConfigID`,
            cs.`RoomValidation`, cs.`BillingPolicy`, cs.`GuestAuthMode`, cs.`SiteMode`, 
            cs.`PMSID`, cs.`PMSStaus`, cs.`PMSLastUpdate`, cs.`PMSIP`, cs.`PMSPort`,gc.`Name`,
            gc.`Model`, gc.`DeviceID`, gc.`Firmware`, gc.`LicenseExpiry`, gc.`GatewayIP` 
            FROM `commonsetting` cs LEFT OUTER JOIN `gatewayconfiguration` gc
            on cs.`GatewayID` = gc.`GatewayID`";
                $query = $this->intraserv_DB->query($sql);
//echo "First Time Load Settings - $Comman_Settings_loaded\n";
                $Settings = array();
                if ($query) {
                    if ($query->num_rows() > 0) {

                        $row_gw = $query->row_array();
                        if ($row_gw["Name"] == 'Nomadix')
                            $this->Iptables = FALSE;
                        else {
                            $this->Iptables = TRUE;
                        }
                    }
                }
            }
        }
    }

    /**
     * @param string String to set this objects password to
     */
    public function setPassword($value) {
        $this->_password = $value;
    }

    /*
     * Class Methods
     */

    /**
     * 	Commit method, this will comment the entire object to the database
     */
    public function commit() {
        $data = array(
            'username' => $this->_username,
            'password' => $this->_password
        );

        if ($this->_id > 0) {
//We have an ID so we need to update this object because it is not new
            if ($this->db->update("user", $data, array("id" => $this->_id))) {
                return true;
            }
        } else {
//We dont have an ID meaning it is new and not yet in the database so we need to do an insert
            if ($this->db->insert("user", $data)) {
//Now we can get the ID and update the newly created object
                $this->_id = $this->db->insert_id();
                return true;
            }
        }
        return false;
    }

}

?>