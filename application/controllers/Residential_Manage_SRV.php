<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Residential_Manage_SRV extends CI_Controller {

    /**
     * Index Page for this controller.
     *
     * Maps to the following URL
     * 		http://example.com/index.php/welcome
     * 	- or -  
     * 		http://example.com/index.php/welcome/index
     * 	- or -
     * Since this controller is set as the default controller in 
     * config/routes.php, it's displayed at http://example.com/
     *
     * So any other public methods not prefixed with an underscore will
     * map to /index.php/welcome/<method_name>
     * @see http://codeigniter.com/user_guide/general/urls.html
     */
    protected $_session = null;

    public function __construct() {
        parent::__construct();

        $this->load->model("Logger");
        // Your own constructor code
    }

    public function index() {
        echo 'test';
    }

    public function home_verify_post() {

        $Status = "0";
        $Des = array();
        $_DESC = array('Home_ID' => $this->input->post('Home_ID'), 'Verification_Code' => $this->input->post('Verification_Code'));

        $data = array(
            'Home_ID' => $this->input->post('Home_ID') ? $this->input->post('Home_ID') : $this->input->get('Home_ID'),
            'Verification_Code' => $this->input->post('Verification_Code') ? $this->input->post('Verification_Code') : $this->input->get('Verification_Code')
        );

        if ($data['Home_ID'])
            $this->Logger->SITE_ID = trim($data['Home_ID']);

        $this->Logger->transaction_log($Log_App = 'RM_SRV', $this->router->class . '/' . $this->router->method, $Log_Transaction = "POST:" . json_encode($data));

        if (($data['Home_ID']) && ($data['Verification_Code'])) {
            $this->Logger->SITE_ID = trim($data['Home_ID']);

            $sql = "SELECT hr.`Site_ID`,su.`RoleID` FROM `home_reg` hr 
                Left Join `systemuser` su ON hr.`Site_ID`=su.`Site_ID` 
                WHERE hr.`Site_ID`=? and hr.`Verification_Key`=? ";

            $query_home = $this->db->query($sql, array($this->input->post('Home_ID'), $this->input->post('Verification_Code')));

            if ($query_home && $query_home->num_rows() > 0) {
                $Status = "1";
                $Des = array('Des' => "Please create admin for manage users");

                foreach ($query_home->result_array() as $row) {
                    if ($row['RoleID'] == 1) {
                        //Already admin created
                        $Status = "2";
                        $Des = array('Des' => "Already Admin user created");
                    }
                }
            } else {
                $Status = "0";
                $Des = array('Des' => "Home ID and Verification code mismatched");
            }
        } else {
            $Status = "0";
            $Des = array('Des' => "Incorrect data posted");
        }

        $_DESC = array_merge($_DESC, $Des);

        $this->Logger->transaction_log($Log_App = 'RM_SRV', $this->router->class . '/' . $this->router->method, $Log_Transaction = "POST Res:" . json_encode(array("STATUS" => $Status, "DESC" => $_DESC)));

        echo json_encode(array("STATUS" => $Status, "DESC" => $_DESC));
    }

    public function home_reg_post() {

        $Status = "0";
        $_DESC = array();

        $data = array(
            'Home_ID' => $this->input->post('Home_ID') ? $this->input->post('Home_ID') : $this->input->get('Home_ID'),
            'Peak_Usage_Volume' => $this->input->post('Peak_Usage_Volume') ? $this->input->post('Peak_Usage_Volume') : $this->input->get('Peak_Usage_Volume'),
            'oFF_Peak_Usage_Volume' => $this->input->post('oFF_Peak_Usage_Volume') ? $this->input->post('oFF_Peak_Usage_Volume') : $this->input->get('oFF_Peak_Usage_Volume'),
            'Peak_Start_Time' => $this->input->post('Peak_Start_Time') ? $this->input->post('Peak_Start_Time') : $this->input->get('Peak_Start_Time'),
            'Peak_End_Time' => $this->input->post('Peak_End_Time') ? $this->input->post('Peak_End_Time') : $this->input->get('Peak_End_Time'),
            'Off_Peak_Start_Time' => $this->input->post('Off_Peak_Start_Time') ? $this->input->post('Off_Peak_Start_Time') : $this->input->get('Off_Peak_Start_Time'),
            'Off_Peak_End_Time' => $this->input->post('Off_Peak_End_Time') ? $this->input->post('Off_Peak_End_Time') : $this->input->get('Off_Peak_End_Time'),
            'Package_Recycle_Day' => $this->input->post('Package_Recycle_Day') ? $this->input->post('Package_Recycle_Day') : $this->input->get('Package_Recycle_Day')
        );

        $_DESC = $data;

        if ($data['Home_ID'])
            $this->Logger->SITE_ID = trim($data['Home_ID']);

        $this->Logger->transaction_log($Log_App = 'RM_SRV', $this->router->class . '/' . $this->router->method, $Log_Transaction = "POST:" . json_encode($data));

        if (($data['Home_ID']) && ($data['Peak_Usage_Volume']) && ($data['oFF_Peak_Usage_Volume']) && ($data['Peak_Start_Time']) && ($data['Peak_End_Time']) && ($data['Off_Peak_Start_Time']) && ($data['Off_Peak_End_Time']) && ($data['Package_Recycle_Day'])) {
            $this->Logger->SITE_ID = trim($data['Home_ID']);

            $data_update = array(
                'Peak_Usage_Volume' => $data['Peak_Usage_Volume'],
                'oFF_Peak_Usage_Volume' => $data['oFF_Peak_Usage_Volume'],
                'Peak_Start_Time' => $data['Peak_Start_Time'],
                'Peak_End_Time' => $data['Peak_End_Time'],
                'Off_Peak_Start_Time' => $data['Off_Peak_Start_Time'],
                'Off_Peak_End_Time' => $data['Off_Peak_End_Time'],
                'Package_Recycle_Day' => $data['Package_Recycle_Day']
            );

            $this->db->where('Site_ID', $data['Home_ID']);
            if ($this->db->update('home_reg', $data_update)) {
                $Status = "1";
                $Des = array('Des' => "Home Registration Success");
            }
        } else {
            $Status = "0";
            $Des = array('Des' => "Incorrect data posted");
        }

        $_DESC = array_merge($_DESC, $Des);

        $this->Logger->transaction_log($Log_App = 'RM_SRV', $this->router->class . '/' . $this->router->method, $Log_Transaction = "POST Res:" . json_encode(array("STATUS" => $Status, "DESC" => $_DESC)));

        echo json_encode(array("STATUS" => $Status, "DESC" => $_DESC));
    }

    public function admin_reg_post() {

        $Status = "0";
        $_DESC = array();

        $data = array(
            'Home_ID' => $this->input->post('Home_ID') ? $this->input->post('Home_ID') : $this->input->get('Home_ID'),
            'First_Name' => $this->input->post('First_Name') ? $this->input->post('First_Name') : $this->input->get('First_Name'),
            'Last_Name' => $this->input->post('Last_Name') ? $this->input->post('Last_Name') : $this->input->get('Last_Name'),
            'Email' => $this->input->post('Email') ? $this->input->post('Email') : $this->input->get('Email'),
            'Mobile' => $this->input->post('Mobile') ? $this->input->post('Mobile') : $this->input->get('Mobile')
        );

        $_DESC = $data;
        if ($data['Home_ID'])
            $this->Logger->SITE_ID = trim($data['Home_ID']);

        $this->Logger->transaction_log($Log_App = 'RM_SRV', $this->router->class . '/' . $this->router->method, $Log_Transaction = "POST:" . json_encode($data));

        if (($data['Home_ID']) && ($data['First_Name']) && ($data['Last_Name']) && ($data['Email'])) {
            
            //check with email_id
            
            
            $this->Logger->SITE_ID = trim($data['Home_ID']);

            //Default Admin user UN & PW set
            $data_insert = array(
                'Site_ID' => $data['Home_ID'],
                'FirstName' => $data['First_Name'],
                'LastName' => $data['Last_Name'],
                'Email' => $data['Email'],
                'MobileNo' => $data['Mobile'],
                'RoleID' => 1,
                'Username' => 'Admin',
                'Password' => 'admin',
                'IsActive' => 1
            );

            if ($this->db->insert('systemuser', $data_insert)) {
                $Status = "1";
                $Des = array('Des' => "Admin User Created");

                $Send_mail = array(
                    'Email_To' => $data['Email'],
                    'Username' => 'Admin',
                    'Password' => 'admin'
                );
                $this->Logger->email_log($Send_mail);

                //Send email to above address
            }
        } else {
            $Status = "0";
            $Des = array('Des' => "Incorrect data posted");
        }

        $_DESC = array_merge($_DESC, $Des);

        $this->Logger->transaction_log($Log_App = 'RM_SRV', $this->router->class . '/' . $this->router->method, $Log_Transaction = "POST Res:" . json_encode(array("STATUS" => $Status, "DESC" => $_DESC)));

        echo json_encode(array("STATUS" => $Status, "DESC" => $_DESC));
    }

    public function user_auth_post() {

        $Status = "Invalid";
        $_DESC = array('RoomNo' => $this->input->post('RoomNo'), 'LastName' => $this->input->post('LastName'));


        $data = array(
            'Site_ID' => $this->input->post('Site_ID') ? $this->input->post('Site_ID') : $this->input->get('Site_ID'),
            'RoomNo' => $this->input->post('RoomNo') ? $this->input->post('RoomNo') : $this->input->get('RoomNo'),
            'LastName' => $this->input->post('LastName') ? $this->input->post('LastName') : $this->input->get('LastName'),
            'RegNo' => $this->input->post('RegNo') ? $this->input->post('RegNo') : $this->input->get('RegNo'),
            'MAC' => $this->input->post('MAC') ? $this->input->post('MAC') : $this->input->get('MAC')
        );

        //check with Internal_PMS
        $sql = "SELECT `RoomNo`, `LastName`, `RegNo`, `ResvNo`, `ArrivalDT`, `DepartureDT`, `Share`, `VIPStatus`, `Title`, `NoPost`, `Language`, `TransDate`, `A0`, `A1`, `A2`, `A3`, `A4`, `A5`, `A6`, `A7`, `A8`, `A9` FROM `pms` 
                        WHERE `Site_ID`=? AND `RoomNo`=? AND `LastName`=?";


        //$query_users = $this->db->query($sql, array($this->Hotel_Guest->RegNo, $this->Hotel_Guest->RoomNo, $this->Hotel_Guest->Last_Name));
        $query_users = $this->db->query($sql, array($this->input->post('Site_ID'), $this->input->post('RoomNo'), $this->input->post('LastName')));

        if ($query_users && $query_users->num_rows() > 0) {
            foreach ($query_users->result_array() as $row_user) {
                $Status = "Valid";
                $_DESC = array('RoomNo' => $row_user['RoomNo'],
                    'LastName' => $row_user['LastName'],
                    'RegNo' => $row_user['RegNo'],
                );
                $Plan_data['Plans'] = $this->get_group_plans($this->input->post('Site_ID'));
                $_DESC = array_merge($_DESC, $Plan_data);
            }
        }

        //echo json_encode($Reponse);
        echo json_encode(array("STATUS" => $Status, "DESC" => $_DESC));
    }

    public function get_group_plans($HID="") {
        $plan_groups = array();
        $sql = "SELECT `PlanID`, `PlanText`, `PlanDesc`, `Amount`, `RadiusPlan`, `XY`, `Duration`, `Validity`, `MACLimit`, `IPType`, `GroupPolicy`, `GroupName`, `BWUp`, `BWDown`, `MaxByteUp`, `MaxByteDown`, `PostPMS`, `NumOfPerDay`, `PlanStatus` FROM `roomplan`  WHERE `PlanStatus`='1' AND `Site_ID`='$HID' AND `GroupPolicy`='1' GROUP BY `PlanID`,`GroupName`";

        //$query_groups = $this->db->query($sql, array($Site_ID));
        $query_groups = $this->db->query($sql);
        if ($query_groups && $query_groups->num_rows() > 0) {
            $plan_groups = $query_groups->result_array();
        }

        return $plan_groups;
    }

    public function Create_NDX_XML_POST() {

        $Status = "Failed";
        $_DESC = array('Msg' => "User Not Created");

        $data = array(
            'Site_ID' => $this->input->post('Site_ID') ? $this->input->post('Site_ID') : $this->input->get('Site_ID'),
            'RoomNo' => $this->input->post('RoomNo') ? $this->input->post('RoomNo') : $this->input->get('RoomNo'),
            'LastName' => $this->input->post('LastName') ? $this->input->post('LastName') : $this->input->get('LastName'),
            'RegNo' => $this->input->post('RegNo') ? $this->input->post('RegNo') : $this->input->get('RegNo'),
            'MAC' => $this->input->post('MAC') ? $this->input->post('MAC') : $this->input->get('MAC'),
            'PlanID' => $this->input->post('PlanID') ? $this->input->post('PlanID') : $this->input->get('PlanID')
        );


        $this->Comman_Settings->load(True, $this->input->post('Site_ID'));
        $this->Comman_Settings->SITE_ID = $this->input->post('Site_ID');
        $this->load->model("NDX_User_Model");
        $this->load->model("NDX_XML");
        $this->load->model("Hotel_Guest");


//        echo json_encode($this->Comman_Settings->Settings);
//        die();
        //$this->load->model("Bill_Plan");
        $this->session->set_userdata('MA', $data['MAC']);
        if ($this->Hotel_Guest->setGuest($data['RoomNo'], $data['LastName']))
//                $_DESC = array(
//                        'Msg' => "User Not Created",
//                        'RoomNo'=>$this->hotel_guest->RoomNo,
//                    'RoomNo'=>$this->hotel_guest->RoomNo);
//        echo json_encode(array("STATUS" => $Status, "DESC" => $_DESC));
//        die();
            $this->Hotel_Guest->toObject($this->session->userdata('Guest'));
        if (isset($data['PlanID'])) {

            if ($this->Bill_Plan->setValidPlan($this->Hotel_Guest, $data['PlanID'])) {
                $this->_Internal_Purchase(True);

                $this->_perDayExpire();
                for ($retry = 0; $retry < $this->Comman_Settings->AAA_retry; $retry++) {
                    $NDX_USER = strtoupper($data['RoomNo'] . '-' . $data['LastName'] . '-' . $data['MAC']);
                    $NDX_USER = str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), ' ', $NDX_USER);
                    $this->NDX_User_Model->_BANDWIDTH_UP = ceil($this->Bill_Plan->BWUp);
                    $this->NDX_User_Model->_BANDWIDTH_DOWN = ceil($this->Bill_Plan->BWDown);
                    $this->NDX_User_Model->_IP_TYPE = strtoupper($this->Bill_Plan->IPType);
                    $this->NDX_User_Model->_USER_NAME = $NDX_USER;
                    $this->NDX_User_Model->_MA_ADDR = $data['MAC'];
                    $this->NDX_User_Model->_EXPIRY_TIME = ceil($this->Bill_Plan->Duration); // Minutes
//
//                    echo json_encode($this->Hotel_Guest);
//                    die();
                    $_DESC = array(
                        'Msg' => "User Not Created",
                        'RoomNo' => $this->Hotel_Guest->RoomNo,
                        'LastName' => $this->Hotel_Guest->Last_Name,
                        'Username' => $NDX_USER,
                        'BW_UP' => ceil($this->Bill_Plan->BWUp),
                        'BW_Down' => ceil($this->Bill_Plan->BWDown),
                        'IP_Type' => strtoupper($this->Bill_Plan->IPType),
                        'MAC' => $data['MAC'],
                        'Duration' => $this->Bill_Plan->Duration
                    );
                    if ($this->NDX_XML->NDX_XML_USER_ADD($this->NDX_User_Model)) {
                        //// Transaction_log (*App Name,Controller/Method,*Transaction,*Validation)
                        $Status = "Success";
                        $_DESC = array(
                            'Msg' => "User Created",
                            'Username' => $NDX_USER,
                            'Duration' => $this->Bill_Plan->Duration
                        );
                        // $this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "NDX User Create XML - Successful - Retry -" . $retry);
                        //return TRUE;
                    }
                }
            }
        }


        //$this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "NDX_XML_USER_ADD FAILED", $Log_Validation = '');
        //// Transaction_log (*App Name,Controller/Method,*Transaction,*Validation)
        //$this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "NDX User Create XML - Failed - Retry -" . $retry);

        echo json_encode(array("STATUS" => $Status, "DESC" => $_DESC));
        //return FALSE;
    }

    public function Create_Radius_User() {
        
    }

    public function Create_Cambium_User() {

        $Status = "Failed";
        $_DESC = array('Msg' => "User Not Created");

        $data = array(
            'Site_ID' => $this->input->post('Site_ID') ? $this->input->post('Site_ID') : $this->input->get('Site_ID'),
            'RoomNo' => $this->input->post('RoomNo') ? $this->input->post('RoomNo') : $this->input->get('RoomNo'),
            'LastName' => $this->input->post('LastName') ? $this->input->post('LastName') : $this->input->get('LastName'),
            'RegNo' => $this->input->post('RegNo') ? $this->input->post('RegNo') : $this->input->get('RegNo'),
            'MAC' => $this->input->post('MAC') ? $this->input->post('MAC') : $this->input->get('MAC'),
            'PlanID' => $this->input->post('PlanID') ? $this->input->post('PlanID') : $this->input->get('PlanID')
        );

        $this->Logger->transaction_log($Log_App = 'Portal_SRV', $this->router->class . '/' . $this->router->method, $Log_Transaction = "POST:" . json_encode(array("STATUS" => $Status, "DESC" => $data)));


        if (($data['MAC'])) {

            $this->Comman_Settings->load(True, $this->input->post('Site_ID'));
            $this->Comman_Settings->SITE_ID = $this->input->post('Site_ID');
            $this->load->model("NDX_User_Model");
            $this->load->model("Hotel_Guest");


//        echo json_encode($this->Comman_Settings->Settings);
//        die();
            $this->load->model("Bill_Plan");
            $this->session->set_userdata('MA', $this->validate_MAC($data['MAC']));
            if ($this->Hotel_Guest->setGuest($data['RoomNo'], $data['LastName']))
//                $_DESC = array(
//                        'Msg' => "User Not Created",
//                        'RoomNo'=>$this->hotel_guest->RoomNo,
//                    'RoomNo'=>$this->hotel_guest->RoomNo);
//        echo json_encode(array("STATUS" => $Status, "DESC" => $_DESC));
//        die();
                $this->Hotel_Guest->toObject($this->session->userdata('Guest'));
            if (isset($data['PlanID'])) {

                if ($this->Bill_Plan->setValidPlan($this->Hotel_Guest, $data['PlanID'])) {
                    $this->_Internal_Purchase(True);

//                $this->_perDayExpire();
                    $Status = "User Created";
                    $_DESC = $this->_Create_Internet_User("Cambium");
                }
            }
        }


        $this->Logger->transaction_log($Log_App = 'Portal_SRV', $this->router->class . '/' . $this->router->method, $Log_Transaction = "POST:" . json_encode(array("STATUS" => $Status, "DESC" => $_DESC)));
        echo json_encode(array("STATUS" => $Status, "DESC" => $_DESC));
    }

    public function validate_MAC($MAC="") {
        if ($MAC) {
            $MAC = str_replace(array('-', ':'), '', $MAC);
        }
        return $MAC;
    }

    public function _Internal_Purchase($No_Post = false) {

        $this->load->model("User_Access_handler");

        if ($this->User_Access_handler->load_Access($this->Hotel_Guest, $this->Bill_Plan)) {

            $CUID = $this->User_Access_handler->Add_Access($No_Post);
            $PID = $this->User_Access_handler->Purchase_Access($No_Post);
            if ($PID) {   //// Transaction_log (*App Name,Controller/Method,*Transaction,*Validation)
                $this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "Internal Purchase - Set ");
            } else {   //// Transaction_log (*App Name,Controller/Method,*Transaction,*Validation)
                $this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "Internal Purchase - Set Failed ");
            }

            if ($CUID > 0 && $PID > 0) {
                $IDs = array("Purchase" => $PID, "CurntUserID" => $CUID);
                return $IDs;
            }
        }
        return FALSE;

        //$this->Comman_Settings->_loadStatus();
    }

    public function _Create_Internet_User($Gateway_Type="") {

        //        if ($this->Comman_Settings->Iptables) {
//            if ($this->_Create_IPtables())
//                return TRUE;
//        } else
        if ($this->Bill_Plan->RadiusPlan) {
            //// Transaction_log (*App Name,Controller/Method,*Transaction,*Validation)
            $this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "Create Internet User - Radius XML Initiated ");


            //For Nomadix
            if ($Gateway_Type == "Nomadix") {
                if ($this->_Create_NDX_Radius()) {
                    $this->session->unset_userdata('AutoUpgrade');
                    return TRUE;
                }
            } else {//Change for cnPilot E400
                return $this->_Create_cnPilot_Radius();
//                if ($this->_Create_cnPilot_Radius()) {
//                    $this->session->unset_userdata('AutoUpgrade');
//                    return TRUE;
//                }
            }
        } else {
            //// Transaction_log (*App Name,Controller/Method,*Transaction,*Validation)
            $this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "Create Internet User - NDX XML Initiated ");

            if ($this->_Create_NDX_XML()) {
                $this->session->unset_userdata('AutoUpgrade');
                return TRUE;
            }
        }
        //// Transaction_log (*App Name,Controller/Method,*Transaction,*Validation)
        $this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "Create Internet User - Failed ");

        return FALSE;
    }

    public function _Create_IPtables() {

        if ($this->Iptables->isRulesValid()) {
            if ($this->Iptables->isAllowed()) {
                $this->Comman_Settings->_loadStatus();
            } else {
                if ($this->Iptables->allow_Internet_Access($this->Bill_Plan->Duration, $this->Bill_Plan->BWDown, $this->Bill_Plan->BWUp)) {
                    if ($this->Iptables->isAllowed())
                        return TRUE;
                    else {
                        $this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "I.AAA User Create Post- Failed ");
                        $this->Comman_Settings->_loadPortal($MSG = "in_aaa_user_err");
                    }
                } else {
                    $this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "I.AAA User Create - Failed ");
                    $this->Comman_Settings->_loadPortal($MSG = "in_aaa_user_err");
                }
            }
        } else {
            $this->Comman_Settings->_loadPortal($MSG = "in_aaa_off");
            die();
        }
    }

    public function _Create_NDX_XML() {

        $this->load->model("NDX_User_Model");
        $this->load->model("NDX_XML");
        $this->_perDayExpire();
        for ($retry = 0; $retry < $this->Comman_Settings->AAA_retry; $retry++) {
            $NDX_USER = strtoupper($this->Hotel_Guest->RoomNo . '-' . $this->Hotel_Guest->Last_Name . '-' . $this->session->userdata('MA'));
            $NDX_USER = str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), ' ', $NDX_USER);
            $this->NDX_User_Model->_BANDWIDTH_UP = ceil($this->Bill_Plan->BWUp);
            $this->NDX_User_Model->_BANDWIDTH_DOWN = ceil($this->Bill_Plan->BWDown);
            $this->NDX_User_Model->_IP_TYPE = strtoupper($this->Bill_Plan->IPType);
            $this->NDX_User_Model->_USER_NAME = $NDX_USER;
            $this->NDX_User_Model->_MA_ADDR = $this->session->userdata('MA');
            $this->NDX_User_Model->_EXPIRY_TIME = ceil($this->Bill_Plan->Duration); // Minutes

            if ($this->NDX_XML->NDX_XML_USER_ADD($this->NDX_User_Model)) {
                //// Transaction_log (*App Name,Controller/Method,*Transaction,*Validation)
                $this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "NDX User Create XML - Successful - Retry -" . $retry);
                return TRUE;
            }
        }
        //$this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "NDX_XML_USER_ADD FAILED", $Log_Validation = '');
        //// Transaction_log (*App Name,Controller/Method,*Transaction,*Validation)
        $this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "NDX User Create XML - Failed - Retry -" . $retry);

        return FALSE;
    }

    public function _Create_cnPilot_Radius() {


        $Radius_Pass = "T_1234";

        $_N_last = 0;
//        $sql = "SELECT `CurntUserID`, `ResvNo`, `RegNo`, `RoomNo`, `LastName`, `FirstName`, `Username`, `Password`, `AuthType`, `BillingPlan`, `PurchasedDate`, `RecDate`, `PlanExpiration`, `MACUsed`, `UsedDuration`, `RADIUSUser`, `RADUsername`, `RADPassword`, `Status` FROM `currentuser`  WHERE `Status`='valid' AND `RoomNo`=?  AND `RegNo`=? AND `LastName`=? ORDER BY `RecDate` DESC";
//        $query = $this->db->query($sql, array($this->Hotel_Guest->RoomNo,$this->Hotel_Guest->RegNo, $this->Hotel_Guest->Last_Name));
//        if ($query) {
//            if ($query->num_rows() > 0) {
//                $row = $query->row_array();
//                $_N_last = "" . substr($row['RADUsername'], strrpos($row['RADUsername'], '-') + 1);
//                settype($_N_last, "integer");
//                $_N_last++;
//                //$this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "_N_last " . $row['RADUsername'] . $row['RADUsername'] . " " . $_N_last, $Log_Validation = '');
//            }
//        }

        $this->load->model("User_Mac_Auth_Handler");
        $this->User_Mac_Auth_Handler->load_Access_MAC();

        $User_Gname = "";
        if ($this->Bill_Plan->GroupPolicy) {
            $User_Gname = substr($this->Bill_Plan->GroupName, 0, 1);
            $User_Gname = "" . strtoupper($User_Gname) . '-';
        }

        $_N_last = $this->User_Mac_Auth_Handler->CurntUserID;
        $NDX_USER = strtoupper($this->Hotel_Guest->RoomNo . '-' . $User_Gname . $this->Hotel_Guest->Last_Name . '-' . $_N_last);
        $NDX_USER = str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), ' ', $NDX_USER);
        $NDX_USER = str_replace(array(',', ":", ";", "*", "|", "~", "!", "/", "|", "@", "#", "$", "%", "^", "&", "(", ")", "+", ".", "{", "}", "?", "[", "]", "<", ">", "=", "`"), '', $NDX_USER);
        $this->NDX_User_Model->_USER_NAME = $NDX_USER;

        $this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "User_Mac_Auth_Handler :" . json_encode($this->User_Mac_Auth_Handler), $Log_Validation = '');

        $Radius_User_Exist = false;
        if ($this->User_Mac_Auth_Handler->CurntUserID) {

            $cu_query = $this->db->get_where('currentuser', array('CurntUserID' => $this->User_Mac_Auth_Handler->CurntUserID,
                        'RADUsername' => $NDX_USER,
                        'RADPassword' => $Radius_Pass,
                        'RADIUSUser' => TRUE));
            if ($cu_query && $cu_query->num_rows() > 0) {
                $Radius_User_Exist = TRUE;
            } else {
                $data = array(
                    'CurntUserID' => $this->User_Mac_Auth_Handler->CurntUserID
                        //,'Status' => $this->Comman_Settings->Valid
                );
                $set_data = array('RADUsername' => $NDX_USER,
                    'RADPassword' => $Radius_Pass,
                    'RADIUSUser' => TRUE
                );
                $this->db->where($data);
                $this->db->update('currentuser', $set_data);
            }
        }

        if ($Radius_User_Exist || $this->_Create_Radius_cnPilot_User($NDX_USER, $Radius_Pass)) {

            //$this->_perDayExpire($NDX_USER);
//            $this->load->model("NDX_User_Model");
//            $this->load->model("NDX_XML");
//            // for ($retry = 0; $retry < $this->Comman_Settings->AAA_retry; $retry++) {
//            $this->NDX_User_Model->_USER_NAME = $NDX_USER;
//            $this->NDX_User_Model->_PASSWORD = $Radius_Pass;
//            $this->NDX_User_Model->_MA_ADDR = $this->session->userdata('MA');
            //$this->NDX_XML->NDX_RADIUS_LOGOUT($this->NDX_User_Model);
//            if ($this->NDX_XML->NDX_RADIUS_LOGIN($this->NDX_User_Model)) {
//                //$this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "NDX_RADIUS_USER_ADD OK", $Log_Validation = '');
//                //// Transaction_log (*App Name,Controller/Method,*Transaction,*Validation)
//
//                $this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "NDX Radius User Create XML - Successful - Retry -" . $retry);
//                if ($this->Comman_Settings->AAA_Radius_DB_Query) {
//                    $this->load->model("Radius_User_Model");
//                    return $this->Radius_User_Model->Check_Radius_User($this->session->userdata('MA'), $NDX_USER);
//                }
//                else
//                    return TRUE;
//            }
            //}
            //$this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "NDX_RADIUS_USER_ADD FAILED", $Log_Validation = '');
            //// Transaction_log (*App Name,Controller/Method,*Transaction,*Validation)
            // $this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "NDX Radius User Create XML - Failed - Retry -" . $retry);
            //  return FALSE;
        }
        $data = array(
            'ga_user' => $NDX_USER,
            'ga_pass' => $Radius_Pass
        );
        $this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "User creation :" . json_encode($data), $Log_Validation = '');


        return $data;
    }

    public function _Create_NDX_Radius() {

        $Radius_Pass = "T_1234";

        $_N_last = 0;
//        $sql = "SELECT `CurntUserID`, `ResvNo`, `RegNo`, `RoomNo`, `LastName`, `FirstName`, `Username`, `Password`, `AuthType`, `BillingPlan`, `PurchasedDate`, `RecDate`, `PlanExpiration`, `MACUsed`, `UsedDuration`, `RADIUSUser`, `RADUsername`, `RADPassword`, `Status` FROM `currentuser`  WHERE `Status`='valid' AND `RoomNo`=?  AND `RegNo`=? AND `LastName`=? ORDER BY `RecDate` DESC";
//        $query = $this->db->query($sql, array($this->Hotel_Guest->RoomNo,$this->Hotel_Guest->RegNo, $this->Hotel_Guest->Last_Name));
//        if ($query) {
//            if ($query->num_rows() > 0) {
//                $row = $query->row_array();
//                $_N_last = "" . substr($row['RADUsername'], strrpos($row['RADUsername'], '-') + 1);
//                settype($_N_last, "integer");
//                $_N_last++;
//                //$this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "_N_last " . $row['RADUsername'] . $row['RADUsername'] . " " . $_N_last, $Log_Validation = '');
//            }
//        }

        $this->load->model("User_Mac_Auth_Handler");
        $this->User_Mac_Auth_Handler->load_Access_MAC();

        $User_Gname = "";
        if ($this->Bill_Plan->GroupPolicy) {
            $User_Gname = substr($this->Bill_Plan->GroupName, 0, 1);
            $User_Gname = "" . strtoupper($User_Gname) . '-';
        }

        $_N_last = $this->User_Mac_Auth_Handler->CurntUserID;
        $NDX_USER = strtoupper($this->Hotel_Guest->RoomNo . '-' . $User_Gname . $this->Hotel_Guest->Last_Name . '-' . $_N_last);
        $NDX_USER = str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), ' ', $NDX_USER);
        $NDX_USER = str_replace(array(',', ":", ";", "*", "|", "~", "!", "/", "|", "@", "#", "$", "%", "^", "&", "(", ")", "+", ".", "{", "}", "?", "[", "]", "<", ">", "=", "`"), '', $NDX_USER);
        $this->NDX_User_Model->_USER_NAME = $NDX_USER;

        //$this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "User_Mac_Auth_Handler :" . json_encode($this->User_Mac_Auth_Handler), $Log_Validation = '');

        $Radius_User_Exist = false;
        if ($this->User_Mac_Auth_Handler->CurntUserID) {

            $cu_query = $this->db->get_where('currentuser', array('CurntUserID' => $this->User_Mac_Auth_Handler->CurntUserID,
                        'RADUsername' => $NDX_USER,
                        'RADPassword' => $Radius_Pass,
                        'RADIUSUser' => TRUE,
                        'Site_ID' => $this->Comman_Settings->SITE_ID));
            if ($cu_query && $cu_query->num_rows() > 0) {
                $Radius_User_Exist = TRUE;
            } else {
                $data = array(
                    'CurntUserID' => $this->User_Mac_Auth_Handler->CurntUserID,
                    'Site_ID' => $this->Comman_Settings->SITE_ID
                        //,'Status' => $this->Comman_Settings->Valid
                );
                $set_data = array('RADUsername' => $NDX_USER,
                    'RADPassword' => $Radius_Pass,
                    'RADIUSUser' => TRUE
                );
                $this->db->where($data);
                $this->db->update('currentuser', $set_data);
            }
        }

        if ($Radius_User_Exist || $this->_Create_Radius_User($NDX_USER, $Radius_Pass)) {

            $this->_perDayExpire($NDX_USER);

            $this->load->model("NDX_User_Model");
            $this->load->model("NDX_XML");
            for ($retry = 0; $retry < $this->Comman_Settings->AAA_retry; $retry++) {
                $this->NDX_User_Model->_USER_NAME = $NDX_USER;
                $this->NDX_User_Model->_PASSWORD = $Radius_Pass;
                $this->NDX_User_Model->_MA_ADDR = $this->session->userdata('MA');

                //$this->NDX_XML->NDX_RADIUS_LOGOUT($this->NDX_User_Model);
                if ($this->NDX_XML->NDX_RADIUS_LOGIN($this->NDX_User_Model)) {
                    //$this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "NDX_RADIUS_USER_ADD OK", $Log_Validation = '');
                    //// Transaction_log (*App Name,Controller/Method,*Transaction,*Validation)

                    $this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "NDX Radius User Create XML - Successful - Retry -" . $retry);
                    if ($this->Comman_Settings->AAA_Radius_DB_Query) {
                        $this->load->model("Radius_User_Model");
                        return $this->Radius_User_Model->Check_Radius_User($this->session->userdata('MA'), $NDX_USER);
                    }
                    else
                        return TRUE;
                }
            }
            //$this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "NDX_RADIUS_USER_ADD FAILED", $Log_Validation = '');
            //// Transaction_log (*App Name,Controller/Method,*Transaction,*Validation)
            $this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "NDX Radius User Create XML - Failed - Retry -" . $retry);

            return FALSE;
        }
        return FALSE;
    }

    public function _perDayExpire_OLD() {

        // If Duration is Over Per Day
        if (ceil($this->Bill_Plan->Duration) > 1440 && $this->Comman_Settings->BC_PC == FALSE) {

            //Coupan Auth
            if ($this->Hotel_Guest->isRoomLogin() == FALSE) {
                if ($this->Comman_Settings->Coupan_PerDay_Expire == TRUE) {

                    if ($this->Bill_Plan->RadiusPlan) {

                        $this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "Coupan PerDay Expire - SKIPPED Due to Radius: {$this->Bill_Plan->Duration} ");

                        // Radius Coupon Group Issue Need to Resolve
                        /*
                          $upd_data = array(
                          'RADUsername' => ''
                          );
                          $data_whr = array(
                          'RoomNo' => $this->Hotel_Guest->RoomNo
                          );

                          $this->db->where($data_whr);
                          if ($this->db->update('currentuser', $upd_data)) {
                          $exp_date = new DateTime("now");
                          $NDX_Exp = true;

                          $this->Bill_Plan->NDX_ExpirationDT = $exp_date->format('Y-m-d\T23:59');
                          $this->Radius_User_Model->set_Nomadix_Expiration($this->Bill_Plan->NDX_ExpirationDT);
                          $this->Radius_User_Model->set_Nomadix_Expiration($exp_date->format('Y-m-d\T23:59'));
                          }
                         */
                    } else {
                        $now = strtotime(date('Y-m-d H:i:s'));
                        $exp_date = strtotime(date(date('Y-m-d') . " 23:59:00"));
                        $this->Bill_Plan->Duration = ceil(($exp_date - $now) / 60);
                        //// Transaction_log (*App Name,Controller/Method,*Transaction,*Validation)
                        $this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "Coupan PerDay Expire - Duration: {$this->Bill_Plan->Duration} ");
                    }
                }
            }
            // Room Auth
            else {

                // All Room Plans is Over Per Day ( Duration > '1440' )
                if ($this->Comman_Settings->Room_PerDay_Expire == TRUE) {

                    $now = strtotime(date('Y-m-d H:i:s'));
                    $exp_date = strtotime(date(date('Y-m-d') . " 23:59:00"));
                    $this->Bill_Plan->Duration = ceil(($exp_date - $now) / 60);

                    if ($this->Bill_Plan->RadiusPlan) {
                        $upd_data = array(
                            'RADUsername' => ''
                        );
                        $data_whr = array(
                            'RegNo' => $this->Hotel_Guest->RegNo,
                            'Site_ID' => $this->Comman_Settings->SITE_ID
                        );
                        if ($this->Comman_Settings->ExID)
                            $data_whr['ExID'] = $this->Hotel_Guest->ExID;

                        $this->db->where($data_whr);
                        if ($this->db->update('currentuser', $upd_data)) {
                            $exp_date = new DateTime("now");
                            $NDX_Exp = true;

                            $this->Bill_Plan->NDX_ExpirationDT = $exp_date->format('Y-m-d\T23:59');
                            $this->Radius_User_Model->set_Nomadix_Expiration($this->Bill_Plan->NDX_ExpirationDT);
                            $this->Radius_User_Model->set_Nomadix_Expiration($exp_date->format('Y-m-d\T23:59'));
                        }
                    }
                    //// Transaction_log (*App Name,Controller/Method,*Transaction,*Validation)
                    $this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "Room PerDay Expire - Duration: {$this->Bill_Plan->Duration} ");
                }
                // FOC Plan is Over Per Day ( Duration == '-1' )
                else if ($this->Comman_Settings->FOC_PerDay_Expire) {

                    $now = strtotime(date('Y-m-d H:i:s'));
                    $exp_date = strtotime(date(date('Y-m-d') . " 23:59:00"));
                    $this->Bill_Plan->Duration = ceil(($exp_date - $now) / 60);

                    if ($this->Bill_Plan->RadiusPlan) {
                        $sql_chkout_plan = "SELECT `PlanID`, `Duration` FROM `roomplan` WHERE `PlanID`=? AND `Duration`='-1' AND `PlanStatus`>=1";
                        $query_sql = $this->db->query($sql_chkout_plan, array($this->Bill_Plan->Plan_ID));
                        if ($query_sql->num_rows() > 0) {
                            $reslut = $query_sql->row_array();
                            $upd_data = array(
                                'RADUsername' => ''
                            );
                            $data_whr = array(
                                'PlanID' => $reslut['PlanID'],
                                'RegNo' => $this->Hotel_Guest->RegNo,
                                'Site_ID' => $this->Comman_Settings->SITE_ID
                            );
                            if ($this->Comman_Settings->ExID)
                                $data_whr['ExID'] = $this->Hotel_Guest->ExID;

                            $this->db->where($data_whr);
                            if ($this->db->update('currentuser', $upd_data)) {
                                $exp_date = new DateTime("now");
                                $NDX_Exp = true;

                                $this->Bill_Plan->NDX_ExpirationDT = $exp_date->format('Y-m-d\T23:59');
                                $this->Radius_User_Model->set_Nomadix_Expiration($this->Bill_Plan->NDX_ExpirationDT);
                                $this->Radius_User_Model->set_Nomadix_Expiration($exp_date->format('Y-m-d\T23:59'));
                            }
                        }
                    }
                    //// Transaction_log (*App Name,Controller/Method,*Transaction,*Validation)
                    $this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "FOC PerDay Expire - Duration: {$this->Bill_Plan->Duration} ");
                } else {
                    
                }
            }
        }
    }

    public function _perDayExpire($NDX_USER = '') {

        // If Duration is Over Per Day
        if (ceil($this->Bill_Plan->Duration) > 1440 && $this->Comman_Settings->BC_PC == FALSE) {

            //Coupan Auth
            if ($this->Hotel_Guest->isRoomLogin() == FALSE) {
                if ($this->Comman_Settings->Coupan_PerDay_Expire == TRUE) {

                    if ($this->Bill_Plan->RadiusPlan) {

                        $this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "Coupan PerDay Expire - SKIPPED Due to Radius: {$this->Bill_Plan->Duration} ");

                        // Radius Coupon Group Issue Need to Resolve
                        /*
                          $upd_data = array(
                          'RADUsername' => ''
                          );
                          $data_whr = array(
                          'RoomNo' => $this->Hotel_Guest->RoomNo
                          );

                          $this->db->where($data_whr);
                          if ($this->db->update('currentuser', $upd_data)) {
                          $exp_date = new DateTime("now");
                          $NDX_Exp = true;

                          $this->Bill_Plan->NDX_ExpirationDT = $exp_date->format('Y-m-d\T23:59');
                          $this->Radius_User_Model->set_Nomadix_Expiration($this->Bill_Plan->NDX_ExpirationDT);
                          $this->Radius_User_Model->set_Nomadix_Expiration($exp_date->format('Y-m-d\T23:59'));
                          }
                         */
                    } else {
                        $now = strtotime(date('Y-m-d H:i:s'));
                        $exp_date = strtotime(date(date('Y-m-d') . " 23:59:00"));
                        $this->Bill_Plan->Duration = ceil(($exp_date - $now) / 60);
                        //// Transaction_log (*App Name,Controller/Method,*Transaction,*Validation)
                        $this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "Coupan PerDay Expire - Duration: {$this->Bill_Plan->Duration} ");
                    }
                }
            }
            // Room Auth
            else {

                // All Room Plans is Over Per Day ( Duration > '1440' )
                if ($this->Comman_Settings->Room_PerDay_Expire == TRUE) {

                    $now = strtotime(date('Y-m-d H:i:s'));
                    $exp_date = strtotime(date(date('Y-m-d') . " 23:59:00"));
                    $this->Bill_Plan->Duration = ceil(($exp_date - $now) / 60);

                    if ($this->Bill_Plan->RadiusPlan) {

                        $this->load->model("Radius_User_Model");
                        $exp_date = new DateTime("now");
                        $NDX_Exp = true;

                        $this->Bill_Plan->NDX_ExpirationDT = $exp_date->format('Y-m-d\T23:59');
                        $this->Radius_User_Model->set_Nomadix_Expiration($this->Bill_Plan->NDX_ExpirationDT);
                        //$this->Radius_User_Model->set_Nomadix_Expiration($exp_date->format('Y-m-d\T23:59'));

                        $this->Radius_User_Model->Update_Radius_User($NDX_USER);
                    }
                    //// Transaction_log (*App Name,Controller/Method,*Transaction,*Validation)
                    $this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "Room PerDay Expire - Duration: {$this->Bill_Plan->Duration} ");
                }
                // FOC Plan is Over Per Day ( Duration == '-1' )
                else if ($this->Comman_Settings->FOC_PerDay_Expire) {

                    $now = strtotime(date('Y-m-d H:i:s'));
                    $exp_date = strtotime(date(date('Y-m-d') . " 23:59:00"));
                    $this->Bill_Plan->Duration = ceil(($exp_date - $now) / 60);

                    if ($this->Bill_Plan->RadiusPlan) {
                        $sql_chkout_plan = "SELECT `PlanID`, `Duration` FROM `roomplan` WHERE `PlanID`=? AND `Duration`='-1' AND `PlanStatus`>=1";
                        $query_sql = $this->db->query($sql_chkout_plan, array($this->Bill_Plan->Plan_ID));
                        if ($query_sql->num_rows() > 0) {
                            $this->load->model("Radius_User_Model");

                            $exp_date = new DateTime("now");
                            $NDX_Exp = true;

                            $this->Bill_Plan->NDX_ExpirationDT = $exp_date->format('Y-m-d\T23:59');
                            $this->Radius_User_Model->set_Nomadix_Expiration($this->Bill_Plan->NDX_ExpirationDT);
                            //$this->Radius_User_Model->set_Nomadix_Expiration($exp_date->format('Y-m-d\T23:59'));

                            $this->Radius_User_Model->Update_Radius_User($NDX_USER);
                        }
                    }
                    //// Transaction_log (*App Name,Controller/Method,*Transaction,*Validation)
                    $this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "FOC PerDay Expire - Duration: {$this->Bill_Plan->Duration} ");
                } else {
                    
                }
            }
        } else if (ceil($this->Bill_Plan->Duration) <= 1440 && ($this->Comman_Settings->FOC_PerDay_Expire || $this->Comman_Settings->Room_PerDay_Expire == TRUE)) {

            if ($this->Bill_Plan->RadiusPlan) {
                $this->load->model("Radius_User_Model");
                $NDX_Exp = true;

                $this->Radius_User_Model->set_Nomadix_Expiration($this->Bill_Plan->NDX_ExpirationDT);
                $this->Radius_User_Model->Update_Radius_User($NDX_USER);
            }
            //// Transaction_log (*App Name,Controller/Method,*Transaction,*Validation)
            $this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = " PerDay Expire - Duration Less Than OneDay : {$this->Bill_Plan->Duration} ");
        }
    }

    public function _Create_Radius_cnPilot_User($NDX_USER, $Radius_Pass) {

        $this->load->model("Radius_User_Model");

        //$this->Radius_User_Model->set_Nomadix_Expiration($this->Bill_Plan->NDX_ExpirationDT);
        $this->Radius_User_Model->set_Session_Timeout(($this->Bill_Plan->Duration) * 60);
        //$this->Radius_User_Model->set_Nomadix_Bw_Down($this->Bill_Plan->BWDown);
        //$this->Radius_User_Model->set_Nomadix_Bw_Up($this->Bill_Plan->BWUp);
//        if (strtoupper($this->Bill_Plan->IPType) == "PUBLIC")
//            $this->Radius_User_Model->set_Nomadix_IP_Upsell("1");
//        else
//            $this->Radius_User_Model->set_Nomadix_IP_Upsell("0");
        //$this->Radius_User_Model->set_Nomadix_Goodbye_URL("http://www.yahoo.com/");
//        if ($this->Bill_Plan->GroupPolicy) {
//            $this->Radius_User_Model->Add_User_Group($this->Bill_Plan->GroupName . '-' . $NDX_USER);
//            //// Transaction_log (*App Name,Controller/Method,*Transaction,*Validation)
//            $this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "Radius User Group Create -" . $this->Bill_Plan->GroupName . '-' . $NDX_USER);
//        }

        if ($this->Radius_User_Model->Create_Radius_User($NDX_USER, $Radius_Pass, 0, "", $this->Bill_Plan->GroupBW)) {
            //// Transaction_log (*App Name,Controller/Method,*Transaction,*Validation)
            $this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "Radius User Created - Successful -" . $NDX_USER);
            //$this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "Radius GroupBW -" . $this->Bill_Plan->GroupBW);

            return TRUE;
        }
        //// Transaction_log (*App Name,Controller/Method,*Transaction,*Validation)
        $this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "Radius User Created - Failed -" . $NDX_USER);
        return FALSE;
    }

    public function _Create_Radius_User($NDX_USER, $Radius_Pass) {

        $this->load->model("Radius_User_Model");

        $this->Radius_User_Model->set_Nomadix_Expiration($this->Bill_Plan->NDX_ExpirationDT);
        //$this->radius_user_model->set_Session_Timeout(600);
        $this->Radius_User_Model->set_Nomadix_Bw_Down($this->Bill_Plan->BWDown);
        $this->Radius_User_Model->set_Nomadix_Bw_Up($this->Bill_Plan->BWUp);
        if (strtoupper($this->Bill_Plan->IPType) == "PUBLIC")
            $this->Radius_User_Model->set_Nomadix_IP_Upsell("1");
        else
            $this->Radius_User_Model->set_Nomadix_IP_Upsell("0");

        //$this->Radius_User_Model->set_Nomadix_Goodbye_URL("http://www.yahoo.com/");

        if ($this->Bill_Plan->GroupPolicy) {
            $this->Radius_User_Model->Add_User_Group($this->Bill_Plan->GroupName . '-' . $NDX_USER);
            //// Transaction_log (*App Name,Controller/Method,*Transaction,*Validation)
            $this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "Radius User Group Create -" . $this->Bill_Plan->GroupName . '-' . $NDX_USER);
        }

        if ($this->Radius_User_Model->Create_Radius_User($NDX_USER, $Radius_Pass, $this->Bill_Plan->GroupPolicy, $this->Bill_Plan->GroupName, $this->Bill_Plan->GroupBW)) {
            //// Transaction_log (*App Name,Controller/Method,*Transaction,*Validation)
            $this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "Radius User Created - Successful -" . $NDX_USER);
            //$this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "Radius GroupBW -" . $this->Bill_Plan->GroupBW);

            return TRUE;
        }
        //// Transaction_log (*App Name,Controller/Method,*Transaction,*Validation)
        $this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "Radius User Created - Failed -" . $NDX_USER);
        return FALSE;
    }

    //public function _get_Du

    public function _get_User_Info() {

        if ($this->Bill_Plan->RadiusPlan) {
            $NDX_USER_Info = $this->NDX_XML->NDX_GET_SUBSCRIBER_QUERY_CURRENT($this->session->userdata('MA'), TRUE);
        }
        else
            $NDX_USER_Info = $this->NDX_XML->NDX_GET_USER_QUERY($this->session->userdata('MA'), TRUE);
        //$this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "NDX_GET_USER_QUERY ", $Log_Validation = '');

        if (is_array($NDX_USER_Info)) {
            $UserInfo_Status = array();
            $UserInfo_Status['AAA_SECONDS'] = '';
            $UserInfo_Status['AAA_DATA_KB'] = '';
            $this->session->set_userdata('UserInfo_Status', TRUE);
            return $NDX_USER_Info;
        } else {
            return FALSE;
        }
    }

    public function TT() {

        $NDX_USER_Info = $this->NDX_XML->NDX_GET_USER_QUERY("D4:97:0B:47:F5:16", TRUE);
        //$this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "NDX_GET_USER_QUERY " . json_encode($NDX_USER_Info), $Log_Validation = '');

        if (is_array($NDX_USER_Info)) {
            $UserInfo_Status = array();
            $UserInfo_Status['AAA_SECONDS'] = '';
            $UserInfo_Status['AAA_DATA_KB'] = '';
            $this->session->set_userdata('UserInfo_Status', $UserInfo);
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function _ndx_Online() {

        $NDX_Online = $this->NDX_XML->NDX_ONLINE_CHECK();
        if ($NDX_Online) {
            //// Transaction_log (*App Name,Controller/Method,*Transaction,*Validation)
            $this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "NDX Available - Valid");

            return TRUE;
        } else {
            //// Transaction_log (*App Name,Controller/Method,*Transaction,*Validation)
            $this->Logger->transaction_log($Log_App = 'Portal', $this->router->class . '/' . $this->router->method, $Log_Transaction = "NDX Available - Invalid");

            return FALSE;
        }
    }

    public function _no_mac() {
        header('Location: ' . base_url());
        die("Mac Not Found");
    }

    public function _error($code = null) {
        //settings
        die("Page Error Found");
    }

}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */