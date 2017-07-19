<?php

error_reporting(E_ALL);

if (0 > version_compare(PHP_VERSION, '5')) {
    die('This file was generated for PHP 5');
}

//require_once('Portal Class Diagram/class.Bill_Plan.php');
//require_once('Portal Class Diagram/class.IP_table_Handler.php');
//require_once('Portal Class Diagram/class.Logger.php');
//require_once('Portal Class Diagram/class.NDX_handler.php');

Class User_Access_handler extends CI_Model {

    public $Plan = null;
    public $User = null;
    public $CurntUserID = null;
    public $PurchID = null;
    public $Query_Access = false;
    private $_Query_user = false;
    private $_Query_currentmac = false;
    private $_Query_Access = false;

    public function __construct() {
        parent::__construct();

        $this->load->model("Comman_Settings");
//        $this->RoomNo = $RoomNo;
//        $this->Last_Name = $Last_Name;
//        $this->RegNo = $RegNo;
    }
    
    

    public function load_Access($User, $Plan) {

        if (gettype($User) === 'object' && gettype($Plan) === 'object') {
            $this->User = $User;
            $this->Plan = $Plan;
            return TRUE;
        }
        return FALSE;
    }

    // Check 'User' obj and 'Plan' obj
    public function check_Access() {

        if (gettype($this->User) === 'object' && gettype($this->Plan) === 'object') {
            return TRUE;
        }
        return FALSE;
    }

    // Check in 'currentmac'
    private function _Query_user() {
        if($this->_Query_user)
        {
            return TRUE;
        }
        var_dump($this->User);
        $query = $this->db->query("SELECT `ID`, `CurntUserID`, `MAC`, `RecDate`, `Status` 
                FROM `currentmac`
                WHERE `Status`=? AND `MAC`=? ORDER BY `RecDate` DESC LIMIT 1",array( $this->Comman_Settings->Valid,$this->User->MA));
        if ($query->num_rows() > 0) {

            $currentmac = $query->row_array();
            $this->CurntUserID = $currentmac['CurntUserID'];
            $this->_Query_user = true;
            return TRUE;
        }
        return FALSE;
    }

    // Insert to 'currentuser' and 'currentmac' 
    public function Add_Access() {

        if ($this->check_Access()) {

//            if ($this->Query_Access()) {
//                return FALSE;
//            }

            $data = array(
                'RegNo' => $this->User->RegNo,
                'RoomNo' => $this->User->RoomNo,
                'LastName' => $this->User->Last_Name,
                'Status' => 'Invalid');
            $this->db->where($data);
            $this->db->delete('currentuser');

            $data = array(
                'ResvNo' => $this->User->RegNo,
                'RegNo' => $this->User->RegNo,
                'RoomNo' => $this->User->RoomNo,
                'LastName' => $this->User->Last_Name,
                'FirstName' => "",
                'Username' => $this->User->Username,
                'Password' => $this->User->Password,
                'AuthType' => "",
                'BillingPlan' => $this->Plan->PlanDesc,
                'PlanExpiration' => $this->Plan->Duration,
                'MACUsed' => '1',
                'UsedDuration' => '0',
                'RADIUSUser' => "",
                'RADUsername' => "",
                'RADPassword' => "",
                'Status' => 'Valid' //$this->Comman_Settings->Valid
            );

            $this->db->set("PurchasedDate", "NOW()", FALSE);
            $this->db->set("RecDate", "NOW()", FALSE);
            $currentuser_add = $this->db->insert('currentuser', $data);

            // insert currentuser Table
            if ($currentuser_add) {
                $this->CurntUserID = "".$this->db->insert_id();
                $this->Add_MAC_Access();
            }
            return TRUE;
        }
        return FALSE;
    }

    // insert currentmac Table
    public function Add_MAC_Access() {

        if ($this->check_Access()) {

            if ($this->Query_Access()) {
                return FALSE;
            }

            // insert currentuser Table
            if (is_string($this->CurntUserID)) {

                // Delete currentmac Table
                $data = array(
                    'MAC' => $this->User->MA);
                $this->db->where($data);
                $this->db->delete('currentmac');

                $data = array(
                    'CurntUserID' => $this->CurntUserID,
                    'MAC' => $this->User->MA,
                    'Status' => $this->Comman_Settings->Valid
                );

                // insert currentmac Table
                // 
                // var_dump($this->session->all_userdata());
                $this->db->set("RecDate", "NOW()", FALSE);
                $this->db->insert('currentmac', $data);
            }
            return TRUE;
        }
        return FALSE;
    }

    // Insert to 'purchase' 
    public function Purchase_Access() {

        if ($this->check_Access()) {

            if ($this->Query_Access()) {
                return FALSE;
            }

            $data = array(
                'CurntUserID' => $this->CurntUserID,
                'PlanID' => $this->Plan->Plan_ID,
                'AuthType' => "Room",
                'TransID' => "",
                'TotalAmount' => $this->Plan->Amount,
                'SubAmount' => $this->Plan->Amount,
                'Tax1' => "",
                'MAC' => $this->User->MA,
                'Status' => $this->Comman_Settings->Pending
            );
            $this->db->set("TransDate", "NOW()", FALSE);
            $this->db->insert('purchase', $data);
            $this->PurchID = $this->db->insert_id();
            return TRUE;
        }
        return FALSE;
    }

    public function Remove_Access() {

        if ($this->check_Access()) {
            $this->User = $User;
            $this->Plan = $Plan;
            return TRUE;
        }
        return FALSE;
    }

    // Check in 'currentmac' and 'currentuser'
    public function Query_Access() {

        if ($this->check_Access()) {
            $current_user = $this->_Query_user();
            if ($current_user) {
                
                if($this->_Query_Access)
                {
                    return $this->Query_Access;
                }
                $query = $this->db->query("SELECT `CurntUserID`, `ResvNo`, `RegNo`, `RoomNo`, `LastName`, `FirstName`, `Username`, 
                    `Password`, `AuthType`, `BillingPlan`, `PurchasedDate`, `RecDate`, `PlanExpiration`, `MACUsed`, 
                    `UsedDuration`, `RADIUSUser`, `RADUsername`, `RADPassword`, `Status` 
                    FROM `currentuser`
                    WHERE `Status`=? AND `CurntUserID`=? ",array( $this->Comman_Settings->Valid, $this->CurntUserID));
                if ($query->num_rows() > 0) {
                    $row = $query->row();

                    $row->PurchasedDate;
                    $row->RecDate;

                    $todate = new DateTime("now");
                    $date = new DateTime($row->PurchasedDate);
                    //echo $date->format('Y-m-d H:i:s') . "\n";

                    $date->add(new DateInterval('PT' . $row->PlanExpiration . 'M'));
                    //echo $date->format('Y-m-d H:i:s') . "\n";
                    $this->_Query_Access = true;

                    if ($date > $todate) {
                        $this->Query_Access = true;
                        return TRUE;
                    } else {
                        $this->Query_Access = false;
                    }
                }
            }
        }
        return FALSE;
    }

}

/* end of class Portal Class Diagram_User_Access_handler */
?>