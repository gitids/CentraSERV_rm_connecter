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
class PMS_Model extends CI_Model {
    /*
     * A private variable to represent each column in the database
     */

    private $postpms_id;
    private $query_id;
    private $pmstransaction_id;
    private $_username;
    private $_password;
    private $_RN = false;
    private $_LN = false;
    private $_RegNo = false;
    private $Iptables = FALSE;
    public $Settings = array();
    public $Sudo = "sudo ";
    public $at_Sch_Remove = '/usr/bin/atrm ';
       public  $STR = "";
       public  $END = "";

    function __construct() {
        parent::__construct();
        $this->load->model("Logger");
        $this->STR = pack('C', 0x02);
        $this->END = pack('C', 0x03);
    }

    /*
     * SET's & GET's
     * Set's and get's allow you to retrieve or set a private variable on an object
     */


    /**
      ID
     * */

    /**
     * @return int [$this->_id] Return this objects ID
     */
    public function insert_Post_PMS($para = array()) {
        //$query = $this->db->query('SELECT `ID`, `RoomNo`, `LastName`, `RegNo`, `Amount1`, `Amount2`, `Amount3`, `Amount4`, `Tax1`, `Tax2`, `Tax3`, `Tax4`, `NameRes`, `Request`, `ReqDT`, `RStatus`, `Response`, `ResDT`, `TransID` FROM `postpms`');
        //	return $query->row();
        try {

            $this->db->trans_start();
            $transaction_ID = isset($para['TransID']) ? $para['TransID'] : $this->getPOSTTransaction_ID();
            //$transaction_ID = isset($para['TransID']) ? $para['TransID'] : "";
            $data = array(
                'PlanName' => isset($para['PlanName']) ? $para['PlanName'] : "",
                'Amount' => isset($para['Amount']) ? $para['Amount'] : "",
                'TotalAmount' => isset($para['Amount']) ? $para['Amount'] : "",
                //'Plan_Desc' => isset($para['Plan_Desc']) ? $para['Plan_Desc'] : "",
                'MAC' => isset($para['MAC']) ? $para['MAC'] : "",
                'RoomNo' => isset($para['RoomNo']) ? $para['RoomNo'] : "",
                'LastName' => isset($para['LastName']) ? $para['LastName'] : "",
                'RegNo' => isset($para['RegNo']) ? $para['RegNo'] : "",
                'Status' => "Q",
                'TransID' => $transaction_ID
            );


            $this->db->set("AddDatetime", "NOW(3)", FALSE);
            $this->db->insert('posting', $data);
            $this->postpms_id = $this->db->insert_id();

            $data = array(
                'RefID' => $this->postpms_id,
                'Mode' => "P",
                'Status' => "Q"
            );

            $this->db->set("AddDT", "NOW(3)", FALSE);
            $this->db->insert('pmstransaction', $data);
            $this->pmstransaction_id = $this->db->insert_id();
            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                // generate an error... or use the log_message() function to log your error
                return FALSE;
            }
            else
            {
                // Continue Query
                $this->load->model("Socket");
                $this->Socket->send_PMS($this->STR.$this->pmstransaction_id.$this->END);
            }
            return TRUE;
        } catch (Exception $exc) {
            //echo $exc->getTraceAsString();
            return FALSE;
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

    public function insert_Query_PMS($para = array()) {
        //$query = $this->db->query('SELECT `ID`, `RoomNo`, `LastName`, `RegNo`, `Amount1`, `Amount2`, `Amount3`, `Amount4`, `Tax1`, `Tax2`, `Tax3`, `Tax4`, `NameRes`, `Request`, `ReqDT`, `RStatus`, `Response`, `ResDT`, `TransID` FROM `postpms`');
        //	return $query->row();
        try {

            $this->db->trans_start();
            //$transaction_ID = $this->getQUERYTransaction_ID();
            $transaction_ID = (strlen($para['TransID']) > 0) ? $para['TransID'] : $this->getQUERYTransaction_ID();
            $data = array(
                //'Plan_Desc' => isset($para['Plan_Desc']) ? $para['Plan_Desc'] : "",
                'RoomNo' => isset($para['RoomNo']) ? $para['RoomNo'] : "-1",
                'Status' => "Q",
                'TransID' => $transaction_ID
            );

            if (!$para['LastName'] === FALSE) {
                $data = array_merge($data, array(
                    'LastName' => $para['LastName']));
            }

            if (!$para['RegNo'] === FALSE) {
                $data = array_merge($data, array(
                    'RegNo' => $para['RegNo']));
            }

            $this->_RN = $para['RoomNo'];
            $this->_LN = $para['LastName'];
            $this->_RegNo = $para['RegNo'];

            $this->db->set("AddDatetime", "NOW(3)", FALSE);
            $this->db->insert('query', $data);
            $this->query_id = $this->db->insert_id();

            $data = array(
                'RefID' => $this->query_id,
                'Mode' => "Q",
                'Status' => "Q"
            );

            $this->db->set("AddDT", "NOW(3)", FALSE);
            $this->db->insert('pmstransaction', $data);
            $this->pmstransaction_id = $this->db->insert_id();
            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                // generate an error... or use the log_message() function to log your error
                return FALSE;
            }
            else
            {
                // Continue Query
                $this->load->model("Socket");
                $this->Socket->send_PMS($this->STR.$this->pmstransaction_id.$this->END);
            }
            return TRUE;
        } catch (Exception $exc) {
            //echo $exc->getTraceAsString();
            return FALSE;
        }
    }

    public function Internel_Purchase($PurchID = null, $MAC =null) {

        try {

            if (is_null($PurchID) || is_null($MAC))
                return FALSE;

            $query = $this->db->query("SELECT `PurchID`, `CurntUserID`, `PlanID`, `AuthType`, `TransID`, `TotalAmount`, `SubAmount`, `Tax1`, `Tax2`, `Tax3`, `Tax4`, `MAC`, `Status`, `TransDate`
            FROM `purchase` 
            WHERE `Status`=? AND `PurchID`=? AND `MAC`=? LIMIT 1", array('Pending', $PurchID, $MAC));
            if ($query->num_rows() > 0) {

                $purchase = $query->row_array();
                $data = array(
                    'Status' => 'valid'
                );

                //$this->db->trans_start();
                $this->db->where('PurchID', $PurchID);
                $this->db->update('purchase', $data);

                $this->db->where('CurntUserID', $purchase['CurntUserID']);
                $this->db->update('currentuser', $data);

                $this->db->where('CurntUserID', $purchase['CurntUserID']);
                $this->db->where('MAC', $MAC);
                $this->db->update('currentmac', $data);
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

    public function getQuery_Result() {

        $max_responese_time_sec = 20;
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
                return array("STATUS" => "TIMEOUT", "DESC" => "PMS Transaction TIMEOUT " . $max_responese_time_sec . " SECs");
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
    public function getPost_Result() {

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
                return array("STATUS" => "TIMEOUT", "DESC" => "PMS RESPONSE TIMEOUT " . $max_responese_time_sec . " SECs");
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

    private function getPOSTTransaction_ID() {

        try {

            $MAX_ID = '1';
            $query = $this->db->query('SELECT MAX(`TransID`) AS MAXID FROM `posting`');
            if ($query->num_rows() > 0) {
                $row = $query->row();
                $MAX_ID = (int) $row->MAXID + 1;
            }
            return $MAX_ID;
        } catch (Exception $exc) {
            return FALSE;
        }
    }

    private function getQUERYTransaction_ID() {

        try {

            $MAX_ID = '1';
            $query = $this->db->query('SELECT MAX(`TransID`) AS MAXID FROM `query`');
            if ($query->num_rows() > 0) {
                $row = $query->row();
                $MAX_ID = (int) $row->MAXID + 1;
            }
            return $MAX_ID;
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

    public function removeUserPurchase_MACs($RegNo = '-1') {

        $return = true;
        $query = $this->db->get_where('purchase', array('RegNo' => $RegNo));
        if ($query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                if ($this->GOPurchID_MACs($row['PurchID']) == FALSE) {
                    $return = false;
                }
            }
        }
        return $return;
    }

    
    public function updateGuestExtend($RegNo = '-1', $GDDate="now") {
//2016-02-23
        $data = array();
        $return = FALSE;
        $query = $this->db->query("SELECT `PurchID`, `CurntUserID`,`Desc`, `RegNo`, `RoomNo`, `LastName`, `PlanID`, `TransDate`,TIMESTAMPDIFF(MINUTE,`TransDate`,'$GDDate 23:59:59') AS 'DIFF' FROM `purchase` WHERE `PlanID`=(SELECT `PlanID` FROM `roomplan` WHERE `Duration`='-1' and `PlanStatus`='1') and `RegNo`='$RegNo'");
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

                $this->db->where('CurntUserID', $purchase_rec['CurntUserID']);
                if ($this->db->update('currentuser', $data)) {
                    $return = TRUE;
                }
            }else{
                 $return = FALSE;
            }
        }

        return $return;
    }

    public function GOPurchID_MACs($Transaction_ID) {

        $this->Comman_Settings_load();
        $return = true;

        $this->db->distinct();
        $query = $this->db->get_where('maclog', array('PurchID' => $Transaction_ID));
                
        $query_purchase = $this->db->get_where('purchase', array('PurchID' => $Transaction_ID));
        $row_purchase=$query_purchase->row_array();
        
        if ($query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                $this->removePassword($row_purchase['LastName'], $row_purchase['RoomNo']);
                $this->setCurrentUserInvalid($row_purchase['CurntUserID'], $row_purchase['RoomNo']);
                if ($this->remove_MACs($row['MAC']) == FALSE) {
                    $return = false;                    
                    $this->Logger->transaction_log($Log_App ='PMS_SRV', $this->router->class . '/' . $this->router->method, $Log_Transaction='GO: PurchID:'.$Transaction_ID.' , CurntUserID:'.$row_purchase['CurntUserID'].' - GO Failed', $row['MAC'], $Log_Validation="");
                }
                else
                $this->Logger->transaction_log($Log_App ='PMS_SRV', $this->router->class . '/' . $this->router->method, $Log_Transaction='GO: PurchID:'.$Transaction_ID.' , CurntUserID:'.$row_purchase['CurntUserID'].' - GO', $row['MAC'], $Log_Validation="");
            }
        }
        else
        {
            $this->Logger->transaction_log($Log_App ='PMS_SRV', $this->router->class . '/' . $this->router->method, $Log_Transaction='GO: PurchID:'.$Transaction_ID.' Not Found', $row['MAC'], $Log_Validation="");
        }
        return $return;
    }
    
    public function removePurchID_MACs($Transaction_ID) {

        $this->Comman_Settings_load();
        $return = true;

        $this->db->distinct();
        $query = $this->db->get_where('maclog', array('PurchID' => $Transaction_ID));
                
        $query_purchase = $this->db->get_where('purchase', array('PurchID' => $Transaction_ID));
        $row_purchase=$query_purchase->row_array();
        
        if ($query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                
                $this->removePassword($row_purchase['LastName'], $row_purchase['RoomNo']);
                $this->setCurrentUserInvalid($row_purchase['CurntUserID'], $row_purchase['RoomNo']);
                if ($this->remove_MACs($row['MAC']) == FALSE) {
                    $return = false;$this->Logger->transaction_log($Log_App ='PMS_SRV', $this->router->class . '/' . $this->router->method, $Log_Transaction='RM: PurchID:'.$Transaction_ID.' , CurntUserID:'.$row_purchase['CurntUserID'].' - Remove Failed', $row['MAC'], $Log_Validation="");
                }
                else
                $this->Logger->transaction_log($Log_App ='PMS_SRV', $this->router->class . '/' . $this->router->method, $Log_Transaction='RM: PurchID:'.$Transaction_ID.' , CurntUserID:'.$row_purchase['CurntUserID'].' - Removed', $row['MAC'], $Log_Validation="");
            }
        }
        else
        {
            $this->Logger->transaction_log($Log_App ='PMS_SRV', $this->router->class . '/' . $this->router->method, $Log_Transaction='RM: PurchID:'.$Transaction_ID.' No Record Found', '', "");
        }
        if ($return) {
            $data = array(
                'Status' => 'RPinvalid'
            );
            $this->db->where('PurchID', $Transaction_ID);
            $this->db->update('purchase', $data);
        }
        return $return;
    }

    
    public function removePassword($LastName = '0', $RoomNo='0') {

        $query = $this->db->get_where('guest_profile', array('LastName' => $LastName,'RoomNo'=>$RoomNo));
        $ID = '0';
        if ($query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {                
                $ID = $row['ID'];
                $this->db->where('UserID', $row['ID']);
                $this->db->delete('device_reg'); 
            }
        }
        $this->db->where('ID', $ID);        
        return $this->db->delete('guest_profile');         
    }

    public function setCurrentUserInvalid($CurntUserID = '0', $RoomNo='0') {

        $return = FALSE;
        $data = array(
            'Status' => 'invalid'
        );

        $this->db->where(array('CurntUserID' => $CurntUserID, 'RoomNo' => $RoomNo));
        if ($this->db->update('currentuser', $data))
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
        $query = $this->db->get_where('pt_mac_table', array('MAC' => $MAC));
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

            $query = $this->db->query("SELECT `ID`, `JOB_ID`, `MAC`, `CREATED_TIME`, `JOB_TIME`, 
            `DESC` FROM `pt_at_jobs` WHERE MAC=? 
                AND JOB_TIME>=NOW()", array($MAC));
            if ($query->num_rows() > 0) {

                foreach ($query->result_array() as $result) {

                    $result['JOB_ID'] = trim($result['JOB_ID']);
                    $cm_rm = $this->Sudo . $this->at_Sch_Remove . $result['JOB_ID'];
                    exec($cm_rm);
                    $this->db->simple_query("UPDATE `pt_at_jobs` SET `STATUS`='SK' WHERE `JOB_ID`='" . $result['JOB_ID'] . "'");
                }
            }
            $this->db->simple_query("UPDATE `pt_mac_table` SET `INTERNET_STATUS`='0',`LAST_ACCESS`= NOW(),`EXPIRE_TIME`=NOW()
                WHERE `MAC`='" . $MAC . "'");
            $this->db->simple_query("DELETE FROM `currentmac` WHERE `MAC`='" . str_replace(":", "",$MAC) . "'");
        }
    }

    public function Comman_Settings_load() {
        $sql = "SELECT `GatewayID`, `GatewayIP`, `BWUp`, `BWDown`, `MailConfigID`, `RoomValidation`,
            `BillingPolicy`, `GuestAuthMode`, `SiteMode`, `PMSID`, `PMSStaus`, `PMSIP`, `PMSPort`,
            `PMSLastUpdate`,
             TIMESTAMPDIFF(SECOND,`PMSLastUpdate`,NOW()) AS 'PMS_Interval',(SELECT COUNT('ID') 
             FROM  `sitemaster`) AS 'SiteLive' 
             FROM `commonsetting` LIMIT 1";
        $query = $this->db->query($sql);
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
                $query = $this->db->query($sql);
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