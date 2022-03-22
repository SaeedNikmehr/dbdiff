<?php
namespace DB\Connection;

use PDO;
use PDOException;

class Connection{
    public $mainSchema;
    public $seccondSchema;
    public function __construct(){
        try {
            $this->mainSchema = new PDO("mysql:host=".FIRST_DB['servername'].";dbname=".FIRST_DB['dbschema'].";charset=UTF8", FIRST_DB['username'], FIRST_DB['password']);
            $this->mainSchema->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
        }

        try {
            $this->seccondSchema = new PDO("mysql:host=".SECCOND_DB['servername'].";dbname=".SECCOND_DB['dbschema'].";charset=UTF8", SECCOND_DB['username'], SECCOND_DB['password']);
            $this->seccondSchema->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
        }
    }

    public function connectToMain(){
        try {
            $con = new PDO("mysql:host=".FIRST_DB['servername'].";dbname=".FIRST_DB['dbname'].";charset=UTF8", FIRST_DB['username'], FIRST_DB['password']);
            $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
        }
        return $con;
    }

    public function connectToSeccond(){
        try {
            $con = new PDO("mysql:host=".SECCOND_DB['servername'].";dbname=".SECCOND_DB['dbname'].";charset=UTF8", SECCOND_DB['username'], SECCOND_DB['password']);
            $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
        }
        return $con;
    }

    public function __destruct(){
        $this->mainSchema = null;
        $this->seccondSchema = null;
    }
}