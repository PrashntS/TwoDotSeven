<?php
namespace TwoDot7\Group;
use \TwoDot7\Util as Util;
use \TwoDot7\Mailer as Mailer;
use \TwoDot7\Database as Database;
#  _____                      _____ 
# /__   \__      _____       |___  |
#   / /\/\ \ /\ / / _ \         / / 
#  / /    \ V  V / (_) |  _    / /  
#  \/      \_/\_/ \___/  (_)  /_/   

class Instance {
    private static $Count = 0;
    private $GroupID;
    private $Meta;
    private $Admin;
    private $Graph;

    function __construct($GroupID) {
        // Constructs the Group Instance. Caches for faster execution.
        $Query = "SELECT * FROM _group WHERE GroupID = :GroupID";
        
        $Response = Database\Handler::Exec($Query)->fetch(\PDO::FETCH_ASSOC);

        if (is_array($Response)) {
            $this->GroupID = $Response['GroupID'];
            $this->Meta = $Response['Meta'];
            $this->Admin = $Response['Admin'];
            $this->Graph = $Response['Graph'];
        }
    }

    public function GetUser($UserName) {
        // Returns user rights and checks if user is part of the group.
        // Used to show the User in the Node.
    }

    public function GetGraph() {
        // Returns user graph, including the Users in the Group.
    }

    public function GetBroadcast() {
        // Returns the Broadcasts targeted for this group.
    }

    public static function ListAll() {
        // Lists all the group.
        
        $Query = "SELECT ID, GroupID, Admin, Meta FROM _group;";
        return \TwoDot7\Database\Handler::Exec($Query)->fetchAll(\PDO::FETCH_ASSOC);
    }

}

class Meta {
    private $GroupID;
    private $Meta;
    public  $Success;
    function __construct($GroupID, $FetchOverride = False, $FetchSourceArray = NULL) {
        $this->GroupID = $GroupID;
        $this->FetchMeta($FetchOverride, $FetchSourceArray);
    }

    function FetchMeta($FetchOverride = False, $FetchSourceArray = NULL) {
        $Response = False;
        if ($FetchOverride) {
            $Response = $FetchSourceArray;
        } else {
            $Query = "SELECT Meta FROM _group WHERE GroupID = :GroupID;";
            $Response = \TwoDot7\Database\Handler::Exec($Query, array('GroupID' => $this->GroupID))->fetch(\PDO::FETCH_ASSOC);
        }
        if ($Response) {
            $this->Success = True;
            $MetaJSON = json_decode($Response['Meta'], true);
            $this->Meta = $MetaJSON ? new Util\Dictionary($MetaJSON) : new Util\Dictionary;
        } else {
            $this->Success = False;
        }
    }
    private function PushMeta() {
        return (int)\TwoDot7\Database\Handler::Exec(
            "UPDATE _group SET Meta = :Meta WHERE GroupID = :GroupID;",
            array(
                'Meta' => json_encode($this->Meta->get()),
                'GroupID' => $this->GroupID
            ))->errorCode() === 0;
    }
    private function MetaHandler($Key, $Data = NULL) {
        if (!is_string($Key)) throw new \TwoDot7\Exception\InvalidArgument("Key should be a valid string.");
        if (is_null($Data)) return $this->Meta->get($Key);
        else {
            $this->Meta->add($Key, $Data);
            return $this->PushMeta();
        }
    }

    public function Get() {
        $Response = new \TwoDot7\Util\Dictionary;
        $Response->add("GroupID", $this->GroupID());
        $Response->add("Description", $this->Description());
        return $Response->get();
    }

    public function GroupID() {
        return $this->GroupID;
    }
    public function Description($Data = NULL) {
        return $this->MetaHandler("Description", $Data);
    }
}

class Graph {

}

class Setup {
    /**
     * Creates the Group, and returns the Generated GroupID.
     */
    public static function Create() {
        if (\TwoDot7\User\Session::Exists() &&
            \TwoDot7\User\Access::Check(array(
                'UserName' => \TwoDot7\User\Session::Data()['UserName'],
                'Domain' => array(
                    'SYSADMIN',
                    'ADMIN',
                    'in.ac.ducic.grpadmin'
                    )
                )) &&
            \TwoDot7\User\Status::Correlate(11, \TwoDot7\User\Status::Get(\TwoDot7\User\Session::Data()['UserName']))) {
            $DatabaseHandler = new \TwoDot7\Database\Handler;

            $Meta = new \TwoDot7\Util\Dictionary;
            $Graph = new \TwoDot7\Util\_List;

            $Defaults = array(
                'GroupID' => self::UniqueGroupID(),
                'Meta' => $Meta->get(False, True),
                'Admin' => \TwoDot7\User\Session::Data()['UserName'],
                'Graph' => $Graph->get(True)
            );

            $Query = "INSERT INTO _group (GroupID, Meta, Admin, Graph) VALUES (:GroupID, :Meta, :Admin, :Graph);";
            $Response = (int) $DatabaseHandler->Query($Query, $Defaults)->errorCode() === 0;
            if ($Response) return $Defaults['GroupID'];
            else return $Response;
        } else throw new \TwoDot7\Exception\AuthError("User not authenticated, or not authorized to perform this operation.");
    }

    /**
     * [Delete description]
     * @param [type] $GroupID [description]
     */
    public static function Delete($GroupID) {
        if (\TwoDot7\User\Session::Exists() &&
            \TwoDot7\User\Access::Check(array(
                'UserName' => \TwoDot7\User\Session::Data()['UserName'],
                'Domain' => array(
                    'SYSADMIN',
                    'ADMIN',
                    'in.ac.ducic.grpadmin'
                    )
                )) &&
            \TwoDot7\User\Status::Correlate(11, \TwoDot7\User\Status::Get(\TwoDot7\User\Session::Data()['UserName']))) {
            $Query = "DELETE FROM _group WHERE GroupID = :GroupID;";
            $Response = \TwoDot7\Database\Handler::Exec($Query, array(
                'GroupID' => $GroupID));
            return ((int) $Response->errorCode() === 0) && ((bool) $Response->rowCount());
        } else throw new \TwoDot7\Exception\AuthError("User not authenticated, or not authorized to perform this operation.");
    }

    /**
     * [$IterCount description]
     * @var integer
     */
    private static $IterCount = 0;

    /**
     * [UniqueGroupID description]
     */
    private static function UniqueGroupID() {
        self::$IterCount++;
        if (self::$IterCount>32) throw new \TwoDot7\Exception\GaveUp("Cannot generate a Unique ID in given time");
        $ID = "grp_".substr(\TwoDot7\Util\Crypt::RandHash(), 0, 16);
        if (\TwoDot7\Util\Redundant::Group($ID)) {
            return self::UniqueGroupID();
        } else return $ID;
    }
}
