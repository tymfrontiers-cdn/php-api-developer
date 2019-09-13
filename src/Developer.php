<?php
namespace TymFrontiers\API;
use \TymFrontiers\MultiForm,
    TymFrontiers\InstanceError;

class Developer{
  use \TymFrontiers\Helper\MySQLDatabaseObject,
      \TymFrontiers\Helper\Pagination;

  protected static $_primary_key = 'user';
  protected static $_db_name = MYSQL_DEV_DB;
  protected static $_table_name = "developer";
  protected static $_db_fields = [
    "user",
    "status",
    "_created"
  ];

  public $user;
  public $status="PENDING";

  protected $_status = ["PENDING","ACTIVE","SUSPENDED","BANNED"];

  public $errors = [];

  function __construct($developer=''){
    self::_checkEnv ();
    global $database;
    if( \is_array($developer) ){
      $this->_createNew($developer);
    }else{
      if( !empty($developer) ) $this->_objtize($developer);
    }
  }

  public function setStatus (string $status ) {
    if( !empty($this->user) ){
      $status = \strtoupper($status);
      if( \in_array($status,$this->_status) && $this->status !== 'BANNED' ){
        $this->status = $status;
        return $this->_update();
      }
    }
    return false;
  }

  private function _createNew (array $developer) {
    self::_checkEnv ();
    global $database;

    if( \is_array($developer) && \array_key_exists('user',$developer) ){
      // get info from MYSQL_BASE_DB . user table
      $usr_object = new MultiForm(MYSQL_BASE_DB,'user');
      $primary_key = $usr_object->primaryKey ();
      if (!\in_array("status", $usr_object::tableFields ())) {
        $this->errors['self'][] = [0,256, "Configuration error: [user] table does not have status column.",__FILE__, __LINE__];
        return false;
      }
      $user = $usr_object->findBySql("SELECT `{$primary_key}`, status FROM :db:.:tbl: WHERE :pkey: = '{$database->escapeValue($developer['user'])}' LIMIT 1");
      if ( $user && $user[0]->status == 'ACTIVE' ) {
        $this->user = $developer['user'];
        $this->status = "PENDING";
        if( $this->_create() ){
          return true;
        }else{
          $this->user = null;
          $this->errors['self'][] = [0,256, "Request failed at this this tym.",__FILE__, __LINE__];
          $this->mergeErrors ();
        }
      }
    }
    return false;
  }
  private function _objtize(string $developer){
    self::_checkEnv ();
    global $database;

    $developer = \strtoupper($database->escapeValue($developer));
    $obj = self::findBySql("SELECT * FROM :db:.:tbl: WHERE user='{$developer}' LIMIT 1");
    if( $obj ){
      foreach ($obj[0] as $key => $value) {
        if( \property_exists(__CLASS__,$key) ) $this->$key = $value;
      }
      return true;
    }else{
      $this->errors['Self'][] = [0,256,"No Developer was found with give [user] ID: ({$developer}).",__FILE__,__LINE__];
    }
    return false;
  }
  private static function _checkEnv(){
    global $database;
    if ( !$database instanceof \TymFrontiers\MySQLDatabase ) {
      if(
        !\defined("MYSQL_DEV_DB") ||
        !\defined("MYSQL_SERVER") ||
        !\defined("MYSQL_DEVELOPER_USERNAME") ||
        !\defined("MYSQL_DEVELOPER_PASS")
      ){
        throw new \Exception("Required defination(s)[MYSQL_BASE_DB, MYSQL_SERVER, MYSQL_USER_USERNAME, MYSQL_USER_PASS] not [correctly] defined.", 1);
      }
      // check if guest is logged in
      $_GLOBAL['database'] = new \TymFrontiers\MySQLDatabase(MYSQL_SERVER,MYSQL_DEVELOPER_USERNAME,MYSQL_DEVELOPER_PASS,self::$_db_name);
    }
  }

}
