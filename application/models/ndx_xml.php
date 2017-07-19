<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class NDX_XML extends CI_Model {

    private $_AAA_Log = true;
    private $_ci;
    private $_string_response;
    public $_url;

    function __construct() {
        //When the class is constructed get an instance of codeigniter so we can access it locally
        parent::__construct();
        $this->_ci = &get_instance();
        //Include the user_model so we can use it
        
        
        //$this->load->model("Comman_Settings");
        //if (!$this->Comman_Settings->load())
          //  $this->_error('settings');
        
        $this->_ci->load->model("ndx_user_model",'ndx_user');
        $this->_ci->load->helper('xml');
        $this->_ci->load->helper('date');
        
        //$this->_url = 'http://'.$this->comman_setting_model->AAA_IP.':1111/usg/command.xml';
        //$this->_url = 'http://'.$this->Comman_Settings->Settings['GatewayIP'].':1111/usg/command.xml';
       // $this->_url = 'http://192.168.1.70:1111/usg/command.xml';
        $this->_AAA_Log = true;
        //Create a new user_model object
        //$this->_ci->ndx_user = new NDX_User_Model();
    }

    public function NDX_XML_REQUEST($xml="<XML></XML>", $TR = '', $APP = 'Portal', $LOC = 'ndx_xml',$Enable_Log = TRUE ) {

        $this->_string_response = "";
        try {

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->_url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-type: application/xml',
                'Content-length: ' . strlen($xml)
            ));

            $AAA_ID =0;
            //  AAA Log_request
            if($Enable_Log)
            $AAA_ID = $this->AAA_LOG_request($APP = 'Portal', $LOC = 'ndx_xml', $TR, $xml);

            $this->_string_response = $output = curl_exec($ch);
            curl_close($ch);

            if (strpos(" " . $output, "<!DOCTYPE HTML") > 0) {
                $this->AAA_LOG_responde($AAA_ID, $VAL = 'INVALID XML', $this->_string_response);
                return FALSE;
            } else if (strlen($output) > 1) {
                $pos = strpos($output, '<XML>');
                if ($pos === false) {
                    $output = '<XML>' . $output . '</XML>';
                }
                $xml_response = new SimpleXMLElement($output);
                // Return Valid Response XML Object

                if ($xml_response === FALSE) {
                    //AAA Log_responde INVALID
                    if($Enable_Log)
                    $this->AAA_LOG_responde($AAA_ID, $VAL = 'INVALID', $this->_string_response);
                    return FALSE;
                } else {
                    //AAA Log_responde VALID
                    if($Enable_Log)
                    $this->AAA_LOG_responde($AAA_ID, $VAL = 'REPLIED', $this->_string_response);
                }

                if (isset($xml_response->USG['RESULT']) && $xml_response->USG['RESULT'] == "ERROR") {
                    if($Enable_Log)
                    $this->AAA_LOG_responde($AAA_ID, $VAL = 'ERROR', $this->_string_response);
                } else if (isset($xml_response->USG['RESULT']) && $xml_response->USG['RESULT'] == "OK") {
                    if($Enable_Log)
                    $this->AAA_LOG_responde($AAA_ID, $VAL = 'VALID', $this->_string_response);
                }

                return array('XML' => $xml_response, 'OUTPUT' => $output, 'AAA_ID' => $AAA_ID); // $response;
            } else {
                if($Enable_Log)
                $this->AAA_LOG_responde($AAA_ID, $VAL = 'INVALID DATA', $this->_string_response);
                return FALSE;
            }
        } catch (Exception $e) {
            return FALSE;
        }
        return FALSE;
    }
    
    
    public function NDX_RADIUS_LOGIN($NDX_User_Model) {
        try {
            
            $this->_ci->ndx_user = $NDX_User_Model;
            //Create a new user_model object
            //$this->_ci->ndx_user = new NDX_User_Model();

            //Set the properties on the user model
            //$this->_ci->ndx_user->set_USER_NAME($username);
            //$this->_ci->ndx_user->set_PASSWORD($password);
            //$this->_ci->ndx_user->set_MA_ADDR($mac);

            $xml = $this->_ci->ndx_user->getXML_RADIUS_LOGIN();

            // XML Request
            //$this->NDX_XML_REQUEST($NDX_User_Model->getXML_USER_DELETE_MAC(), $TR = 'USER_DELETE');
            $array_response = $this->NDX_XML_REQUEST($xml, $TR = 'USER_LOGIN');
            if ($array_response === FALSE) {
                return FALSE;
            } else {
                $xml_response = $array_response['XML'];
                $AAA_ID = $array_response['AAA_ID'];
                $OUTPUT = $array_response['OUTPUT'];
            }

            if (isset($xml_response->USG['RESULT']) && $xml_response->USG['RESULT'] == "OK") {
                return TRUE;
                //transaction_log('Portal', 'querypms_purchase.php', " - NDX_USER_ADD MAC = " . $_SESSION['MAC'] . "", 'Allowed', 'NOW()', "USR: " . $NDX_user, 'MIN: ' . $NDX_Min);
            } else if (isset($xml_response->USG['RESULT']) && $xml_response->USG['RESULT'] == "ERROR") {
                
            }
            return FALSE;
        } catch (Exception $exc) {
            return FALSE;
        }
    }

    public function NDX_XML_USER_ADD($NDX_User_Model) {
        try {
            //Create a new user_model object
            $this->_ci->ndx_user = $NDX_User_Model;

            //Set the properties on the user model

            $xml = $NDX_User_Model->getXML_USER_ADD();

            // XML Request
            $this->NDX_XML_REQUEST($NDX_User_Model->getXML_USER_DELETE(), $TR = 'USER_DELETE');
            $array_response = $this->NDX_XML_REQUEST($xml, $TR = 'USER_ADD');
            if (gettype($array_response) === 'array') {
                $xml_response = $array_response['XML'];
                $AAA_ID = $array_response['AAA_ID'];
                $OUTPUT = $array_response['OUTPUT'];
            } else {                
                return FALSE;                
            }

            if (isset($xml_response->USG['RESULT']) && $xml_response->USG['RESULT'] == "OK") {
                usleep(500000);
                $this->NDX_XML_REQUEST($NDX_User_Model->getXML_CACHE_UPDATE(), $TR = 'CACHE_UPDATE');
                return TRUE;
                //transaction_log('Portal', 'querypms_purchase.php', " - NDX_USER_ADD MAC = " . $_SESSION['MAC'] . "", 'Allowed', 'NOW()', "USR: " . $NDX_user, 'MIN: ' . $NDX_Min);
            } else if (isset($xml_response->USG['RESULT']) && $xml_response->USG['RESULT'] == "ERROR") {
                
            }
            return FALSE;
        } catch (Exception $exc) {
            return FALSE;
        }
    }
    
    
    public function NDX_XML_USER_DELETE($NDX_User_Model) {
        try {
            //Create a new user_model object
            $this->_ci->ndx_user = $NDX_User_Model;

            //Set the properties on the user model
            // XML Request
            
            //USER_DELETE_MAC
            $array_response_mac = $this->NDX_XML_REQUEST($NDX_User_Model->getXML_USER_DELETE_MAC(), $TR = 'USER_DELETE_MAC');
            
            if (gettype($array_response_mac) === 'array' && isset($array_response_mac['XML']->USG['RESULT']) && $array_response_mac['XML']->USG['RESULT'] == "OK") {
                usleep(500000);
                $this->NDX_XML_REQUEST($NDX_User_Model->getXML_CACHE_UPDATE(), $TR = 'CACHE_UPDATE');
                return TRUE;
                //transaction_log('Portal', 'querypms_purchase.php', " - NDX_USER_ADD MAC = " . $_SESSION['MAC'] . "", 'Allowed', 'NOW()', "USR: " . $NDX_user, 'MIN: ' . $NDX_Min);
            }
            //USER_DELETE_MAC END
            
            $xml = $NDX_User_Model->getXML_USER_DELETE();
            $array_response = $this->NDX_XML_REQUEST($xml, $TR = 'USER_DELETE');
            if (gettype($array_response) === 'array') {
                $xml_response = $array_response['XML'];
                $AAA_ID = $array_response['AAA_ID'];
                $OUTPUT = $array_response['OUTPUT'];
            } else {                
                return FALSE;                
            }

            if (isset($xml_response->USG['RESULT']) && $xml_response->USG['RESULT'] == "OK") {
                usleep(500000);
                $this->NDX_XML_REQUEST($NDX_User_Model->getXML_CACHE_UPDATE(), $TR = 'CACHE_UPDATE');
                return TRUE;
                //transaction_log('Portal', 'querypms_purchase.php', " - NDX_USER_ADD MAC = " . $_SESSION['MAC'] . "", 'Allowed', 'NOW()', "USR: " . $NDX_user, 'MIN: ' . $NDX_Min);
            } else if (isset($xml_response->USG['RESULT']) && $xml_response->USG['RESULT'] == "ERROR") {
                
            }
            return FALSE;
        } catch (Exception $exc) {
            return FALSE;
        }
    }


    public function NDX_RADIUS_LOGOUT($NDX_User_Model) {
        try {
            
            $this->_ci->ndx_user = $NDX_User_Model;
            //Set the properties on the user model
            //$this->_ci->ndx_user->set_MA_ADDR($mac);
            $xml = $this->_ci->ndx_user->getXML_RADIUS_LOGOUT();
            //$xml = xml_convert($xml);
            // XML Request
            $array_response = $this->NDX_XML_REQUEST($xml, $TR = 'USER_LOGOUT');
            if (gettype($array_response) === 'array') {
                $xml_response = $array_response['XML'];
                $AAA_ID = $array_response['AAA_ID'];
                $OUTPUT = $array_response['OUTPUT'];
            } else {                
                return FALSE;                
            }
            $this->NDX_XML_REQUEST($NDX_User_Model->getXML_USER_DELETE_MAC(), $TR = 'USER_DELETE_MAC');
            if (isset($xml_response->USG['RESULT']) && $xml_response->USG['RESULT'] == "OK") {
                return TRUE;
                //transaction_log('Portal', 'querypms_purchase.php', " - NDX_USER_ADD MAC = " . $_SESSION['MAC'] . "", 'Allowed', 'NOW()', "USR: " . $NDX_user, 'MIN: ' . $NDX_Min);
            }
            return FALSE;
        } catch (Exception $exc) {
            return FALSE;
        }
    }
    
    public function NDX_ONLINE_CHECK() {

        try {
            //Set the properties on the user model
            $this->_ci->ndx_user->set_MA_ADDR("FFFFFFFFFFFF");
            $xml = $this->_ci->ndx_user->getXML_USER_QUERY();
            //$xml = xml_convert($xml);
            // XML Request
            $array_response = $this->NDX_XML_REQUEST($xml, $TR = 'USER_QUERY');
            if (gettype($array_response) === 'array') {
                $xml_response = $array_response['XML'];
                $AAA_ID = $array_response['AAA_ID'];
                $OUTPUT = $array_response['OUTPUT'];
            } else {                
                return FALSE;                
            }

            if (isset($xml_response->USG['RESULT']) && $xml_response->USG['RESULT'] == "OK") {
                return TRUE;
            } else if (isset($xml_response->USG['RESULT']) && $xml_response->USG['RESULT'] == "ERROR") {
                return TRUE;
            }
            return FALSE;
        } catch (Exception $exc) {
            return FALSE;
        }
    }

    public function NDX_GET_SUBSCRIBER_QUERY_CURRENT($mac,$Enable_Log = FALSE) {

        try {
            //Set the properties on the user model
            $this->_ci->ndx_user->set_MA_ADDR($mac);
            $xml = $this->_ci->ndx_user->getXML_SUBSCRIBER_QUERY_CURRENT();
            //$xml = xml_convert($xml);
            // XML Request
            
            /*
             * <USG RESULT="OK" ID="02BC3C" IP="192.168.1.70">
             * <MAC_ADDR>D0DF9AC868EF</MAC_ADDR>
             * <USER_NAME>104-COORAY-D0DF9AC868EF</USER_NAME>
             * <PASSWORD></PASSWORD>
             * <EXPIRY_TIME UNITS="SECONDS">562</EXPIRY_TIME>
             * <ROOM_NUMBER></ROOM_NUMBER>
             * <PAYMENT_METHOD>NO_PAYMENT</PAYMENT_METHOD>
             * <DATA_VOLUME>4543442</DATA_VOLUME>
             * </USG>
             */
            
//            $xml_response='<USG RESULT="OK" ID="02BC3C" IP="192.168.1.70">
//                <MAC_ADDR>D0DF9AC868EF</MAC_ADDR>
//                <USER_NAME>104-COORAY-D0DF9AC868EF</USER_NAME>
//                <PASSWORD></PASSWORD><EXPIRY_TIME UNITS="SECONDS">562</EXPIRY_TIME>
//                <ROOM_NUMBER></ROOM_NUMBER><PAYMENT_METHOD>NO_PAYMENT</PAYMENT_METHOD>
//                <DATA_VOLUME>4543442</DATA_VOLUME></USG>';          
           // $array_response = $rep = array('XML' => $xml_response, 'OUTPUT' => $xml_response, 'AAA_ID' => "");
            
            $array_response = $this->NDX_XML_REQUEST($xml, $TR = 'SUBSCRIBER_QUERY_CURRENT',$APP = 'Portal', $LOC = 'ndx_xml',$Enable_Log);
            if (gettype($array_response) === 'array') {
                $xml_response = $array_response['XML'];
                $AAA_ID = $array_response['AAA_ID'];
                $OUTPUT = $array_response['OUTPUT'];
            } else {                
                return FALSE;                
            }

            $_SECONDS = 0;
            $_DATA_VOLUME = 0;
            $_DATA_UP = 0;
            $_DATA_DOWN = 0;
            if (isset($xml_response->USG['RESULT']) && $xml_response->USG['RESULT'] == "OK") {
                if (is_object($xml_response->USG)) {                    
                    if ((int)$xml_response->USG->SUBSCRIBER_CURRENT->EXPIRY_TIME_SECS > 0) {
                            $_SECONDS = (int) ($xml_response->USG->SUBSCRIBER_CURRENT->EXPIRY_TIME_SECS);
                    } else {
                        $_SECONDS = (int) (0);
                        //return FALSE;
                    }
                }
                if ($xml_response->USG->SUBSCRIBER_CURRENT->BYTES_TX !== FALSE && $xml_response->USG->SUBSCRIBER_CURRENT->BYTES_RX !== FALSE) {
                    if ($xml_response->USG->SUBSCRIBER_CURRENT->BYTES_TX > 0 || $xml_response->USG->SUBSCRIBER_CURRENT->BYTES_RX>0) {
                        $_DATA_UP = (int) (($xml_response->USG->SUBSCRIBER_CURRENT->BYTES_TX) / (float) 1024);
                        $_DATA_DOWN = (int) (($xml_response->USG->SUBSCRIBER_CURRENT->BYTES_RX) / (float) 1024);
                        $_DATA_VOLUME = (int) (($xml_response->USG->SUBSCRIBER_CURRENT->BYTES_TX) / (float) 1024);
                        $_DATA_VOLUME += (int) (($xml_response->USG->SUBSCRIBER_CURRENT->BYTES_RX) / (float) 1024);
                    } else {
                        $_DATA_VOLUME = (int) (0);
                        //return FALSE;
                    }
                }
                return array('SECONDS' => $_SECONDS, 'DATA_KB' => $_DATA_VOLUME, 'DATA_UP_KB' => $_DATA_UP, 'DATA_DOWN_KB' => $_DATA_DOWN); // $response;
            } else if (isset($xml_response->USG['RESULT']) && $xml_response->USG['RESULT'] == "ERROR") {
                return array('SECONDS' => (int) (0), 'DATA_KB' => (int) (0)); // $response;
                //return FALSE;
            }
            return FALSE;
        } catch (Exception $exc) {
            return FALSE;
        }
    }

    
    public function NDX_GET_USER_QUERY($mac,$Enable_Log= FALSE) {

        try {
            //Set the properties on the user model
            $this->_ci->ndx_user->set_MA_ADDR($mac);
            $xml = $this->_ci->ndx_user->getXML_USER_QUERY();
            //$xml = xml_convert($xml);
            // XML Request
            
            /*
             * <USG RESULT="OK" ID="02BC3C" IP="192.168.1.70">
             * <MAC_ADDR>D0DF9AC868EF</MAC_ADDR>
             * <USER_NAME>104-COORAY-D0DF9AC868EF</USER_NAME>
             * <PASSWORD></PASSWORD>
             * <EXPIRY_TIME UNITS="SECONDS">562</EXPIRY_TIME>
             * <ROOM_NUMBER></ROOM_NUMBER>
             * <PAYMENT_METHOD>NO_PAYMENT</PAYMENT_METHOD>
             * <DATA_VOLUME>4543442</DATA_VOLUME>
             * </USG>
             */
            
//            $xml_response='<USG RESULT="OK" ID="02BC3C" IP="192.168.1.70">
//                <MAC_ADDR>D0DF9AC868EF</MAC_ADDR>
//                <USER_NAME>104-COORAY-D0DF9AC868EF</USER_NAME>
//                <PASSWORD></PASSWORD><EXPIRY_TIME UNITS="SECONDS">562</EXPIRY_TIME>
//                <ROOM_NUMBER></ROOM_NUMBER><PAYMENT_METHOD>NO_PAYMENT</PAYMENT_METHOD>
//                <DATA_VOLUME>4543442</DATA_VOLUME></USG>';          
           // $array_response = $rep = array('XML' => $xml_response, 'OUTPUT' => $xml_response, 'AAA_ID' => "");
            
            $array_response = $this->NDX_XML_REQUEST($xml, $TR = 'USER_QUERY',$APP = 'Portal', $LOC = 'ndx_xml',$Enable_Log);
            if (gettype($array_response) === 'array') {
                $xml_response = $array_response['XML'];
                $AAA_ID = $array_response['AAA_ID'];
                $OUTPUT = $array_response['OUTPUT'];
            } else {                
                return FALSE;                
            }

            $_SECONDS = 0;
            $_DATA_VOLUME = 0;
            if (isset($xml_response->USG['RESULT']) && $xml_response->USG['RESULT'] == "OK") {
                if (isset($xml_response->USG->EXPIRY_TIME['UNITS'])) {
                    if ($xml_response->USG->EXPIRY_TIME > 0) {
                        if ($xml_response->USG->EXPIRY_TIME['UNITS'] == "SECONDS")
                            $_SECONDS = (int) ($xml_response->USG->EXPIRY_TIME);
                        if ($xml_response->USG->EXPIRY_TIME['UNITS'] == "MINUTES")
                            $_SECONDS = (int) ($xml_response->USG->EXPIRY_TIME) * 60;
                    } else {
                        $_SECONDS = (int) (0);
                        //return FALSE;
                    }
                }
                if ($xml_response->USG->DATA_VOLUME !== FALSE) {
                    if ($xml_response->USG->DATA_VOLUME > 0) {
                        $_DATA_VOLUME = (int) (($xml_response->USG->DATA_VOLUME) / (float) 1024);
                    } else {
                        $_DATA_VOLUME = (int) (0);
                        //return FALSE;
                    }
                }
                return array('SECONDS' => $_SECONDS, 'DATA_KB' => $_DATA_VOLUME); // $response;
            } else if (isset($xml_response->USG['RESULT']) && $xml_response->USG['RESULT'] == "ERROR") {
                return array('SECONDS' => (int) (0), 'DATA_KB' => (int) (0)); // $response;
                //return FALSE;
            }
            return FALSE;
        } catch (Exception $exc) {
            return FALSE;
        }
    }
    
    private function AAA_LOG_request($APP='PMS_SRV', $LOC='ndx_xml', $TR='NDX_XML_REQUEST', $XML='<XML>EMTY</XML>') {
        try {
            if ($this->_AAA_Log === FALSE)
                return 0;
            //$AAA_ID = $this->getAAA_LOG_ID();

            $data = array(
                'App' => $APP,
                'Loc' => $LOC,
                'Transaction' => $TR.' -'.$this->_url,
                'Validation' => 'PENDING',
                'Request' => $XML
            );
            $this->_ci->db->set("REQ_datetime", "NOW()", FALSE);
            if($this->_ci->db->insert('AAA_log', $data))
                return  $this->_ci->db->insert_id();

            return 0;
        } catch (Exception $exc) {
            return 0;
        }
    }

    private function AAA_LOG_responde($AAA_ID=0, $VAL='P', $XML='<XML>EMTY</XML>') {

        try {
            if ($AAA_ID === 0 || $this->_AAA_Log === FALSE)
                return FALSE;

            $data = array(
                'Validation' => $VAL,
                'Response' => $XML
            );
            $this->_ci->db->set("RES_datetime", "NOW()", FALSE);
            $this->_ci->db->where('ID', $AAA_ID);
            $this->_ci->db->update('AAA_log', $data);
            return TRUE;
        } catch (Exception $exc) {
            return FALSE;
        }
    }

    private function getAAA_LOG_ID() {

        try {

            $MAX_ID = '1';
            $query = $this->_ci->db->query('SELECT MAX(`ID`) AS MAXID FROM `AAA_log`');
            if ($query->num_rows() > 0) {
                $row = $query->row();
                $MAX_ID = (int) $row->MAXID + 1;
            }
            return $MAX_ID;
        } catch (Exception $exc) {
            return FALSE;
        }
    }

}

?>