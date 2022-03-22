<?php

namespace DB\Main;

use DB\Connection\Connection as DB;
use PDO;
use PDOException;

class Main extends DB{
    public function __construct(){
        parent::__construct();
    }

    public function mainTableSchema($table = null){
        if ($table == null)
            $sql = "SELECT TABLE_NAME,TABLE_ROWS FROM TABLES WHERE TABLE_SCHEMA = '".FIRST_DB['dbname']."'";
        else
            $sql = "SELECT TABLE_ROWS FROM TABLES WHERE TABLE_SCHEMA = '".FIRST_DB['dbname']."'  AND TABLE_NAME ='".$table."'";
        $stmt = $this->mainSchema->prepare($sql);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $result =$stmt->fetchAll();
        /*echo "<pre>";
        var_dump($result);die();*/
        return $result;
    }

    public function seccondTableSchema(){
        $sql = "SELECT TABLE_NAME ,TABLE_ROWS FROM TABLES WHERE TABLE_SCHEMA = '".SECCOND_DB['dbname']."'";
        $stmt = $this->seccondSchema->prepare($sql);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $result =$stmt->fetchAll();
        /*echo "<pre>";
        var_dump($result);die();*/
        return $result;
    }

    public function fieldsComparison($tableName){
        $mainDBsql = "
                SELECT 
                    COLUMN_NAME ,
                    IS_NULLABLE ,
                    DATA_TYPE ,
                    COLUMN_TYPE ,
                    COLUMN_KEY
                FROM 
                    COLUMNS
                WHERE 
                    TABLE_SCHEMA = '".FIRST_DB['dbname']."' AND TABLE_NAME ='".$tableName."'";

        $mainDBstmt = $this->mainSchema->prepare($mainDBsql);
        $mainDBstmt->execute();
        $mainDBstmt->setFetchMode(PDO::FETCH_ASSOC);
        $mainDBresult =$mainDBstmt->fetchAll();

        $seccondDBsql = "
                SELECT 
                    COLUMN_NAME ,
                    IS_NULLABLE ,
                    DATA_TYPE ,
                    COLUMN_TYPE ,
                    COLUMN_KEY
                FROM 
                    COLUMNS
                WHERE 
                    TABLE_SCHEMA = '".SECCOND_DB['dbname']."' AND TABLE_NAME ='".$tableName."'";

        $seccondDBstmt = $this->seccondSchema->prepare($seccondDBsql);
        $seccondDBstmt->execute();
        $seccondDBstmt->setFetchMode(PDO::FETCH_ASSOC);
        $seccondDBresult =$seccondDBstmt->fetchAll();

        $diferences = [];

        foreach ($mainDBresult as $mainResult){
            $i = 0;
            foreach ($seccondDBresult as $seccondResult){
                if ($mainResult['COLUMN_NAME'] == $seccondResult['COLUMN_NAME']&&
                    $mainResult['IS_NULLABLE'] == $seccondResult['IS_NULLABLE'] &&
                    $mainResult['DATA_TYPE'] == $seccondResult['DATA_TYPE'] &&
                    $mainResult['COLUMN_TYPE'] == $seccondResult['COLUMN_TYPE']&&
                    $mainResult['COLUMN_KEY'] == $seccondResult['COLUMN_KEY']){
                    $i =1;
                    break;
                }
            }
            if ($i== 0){
                $diferences[]=[
                    'COLUMN_NAME'=>$mainResult['COLUMN_NAME'],
                    'IS_NULLABLE'=>$mainResult['IS_NULLABLE'],
                    'DATA_TYPE'=>$mainResult['DATA_TYPE'],
                    'COLUMN_TYPE'=>$mainResult['COLUMN_TYPE'],
                    'COLUMN_KEY'=>$mainResult['COLUMN_KEY'],
                ];
            }
        }
        //$diferences = array_intersect_key( $diferences , array_unique( array_map('serialize' , $diferences ) ) );
        return $diferences;
    }

    public function searchForTable($tableName, $array) {
        foreach ($array as $key => $val) {
            if ($val['TABLE_NAME'] === $tableName) {
                return true;
            }
        }
        return false;
    }

    public function createTbale($table){
        $mainCon = $this->connectToMain();
        $sql ="SHOW CREATE TABLE `$table`";
        $stmt = $mainCon->query($sql, PDO::FETCH_ASSOC);
        $table = $stmt->fetch();
        $table = $table['Create Table'];
        $mainCon = null;
        $seccondCon = $this->connectToSeccond();
        try {
            $seccondCon->exec($table);
            //echo "Table created successfully";
        } catch(PDOException $e) {
            echo $e->getMessage();
        }
        $seccondCon = null;
    }

    public function addColumns($table , $columns){
        $sql = "";
        foreach ($columns as $key=> $column){
            $nullable = "";
            $comma = count($columns) > $key+1 ? " ," : "";
            if ($column['IS_NULLABLE'] == 'NO') $nullable = 'NOT NULL';
            $sql .=  $column['COLUMN_NAME']." ".$column['COLUMN_TYPE']." ".$nullable . $comma;
        }
        $sql = "ALTER TABLE `$table` ADD COLUMN (" . $sql .")";
        /*var_dump($sql);*/
        $seccondCon = $this->connectToSeccond();
        try {
            $seccondCon->exec($sql);
            //echo "columns added successfully";
        } catch(PDOException $e) {
            echo $e->getMessage();
        }
        $seccondCon = null;
    }

    public function compareData($table){

        $mainDBsql = "
                SELECT 
                    ROUND((SUM(data_length+index_length+data_free) + (COUNT(*) * 300 * 1024))/1048576,2) AS MegaBytes,
                    sum( data_length + index_length ) ,
                    sum( data_free ),
                    TABLE_ROWS ,
                    DATA_LENGTH 
                FROM 
                    TABLES
                WHERE 
                    TABLE_SCHEMA = '".FIRST_DB['dbname']."' AND TABLE_NAME ='".$table."'";

        $mainDBstmt = $this->mainSchema->prepare($mainDBsql);
        $mainDBstmt->execute();
        $mainDBstmt->setFetchMode(PDO::FETCH_ASSOC);
        $mainDBresult =$mainDBstmt->fetchAll();

        $seccondDBsql = "
                SELECT 
                    ROUND((SUM(data_length+index_length+data_free) + (COUNT(*) * 300 * 1024))/1048576,2) AS MegaBytes,
                    sum( data_length + index_length ),
                    sum( data_free ),
                    TABLE_ROWS ,
                    DATA_LENGTH 
                FROM 
                    TABLES
                WHERE 
                    TABLE_SCHEMA = '".SECCOND_DB['dbname']."' AND TABLE_NAME ='".$table."'";

        $seccondDBstmt = $this->seccondSchema->prepare($seccondDBsql);
        $seccondDBstmt->execute();
        $seccondDBstmt->setFetchMode(PDO::FETCH_ASSOC);
        $seccondDBresult =$seccondDBstmt->fetchAll();
        if ( $mainDBresult[0]['MegaBytes'] == $seccondDBresult[0]['MegaBytes']){
            //var_dump($mainDBresult);
            //var_dump($seccondDBresult);
            return true;

        }else{
            //var_dump($mainDBresult);
            //var_dump($seccondDBresult);
            return false;
        }
    }

    public function getColumns($table){
        $mainDBsql = "
                SELECT 
                    COLUMN_NAME
                FROM 
                    COLUMNS
                WHERE 
                    TABLE_SCHEMA = '".FIRST_DB['dbname']."' AND TABLE_NAME ='".$table."'";

        $mainDBstmt = $this->mainSchema->prepare($mainDBsql);
        $mainDBstmt->execute();
        $mainDBstmt->setFetchMode(PDO::FETCH_ASSOC);
        $mainDBresult =$mainDBstmt->fetchAll();
        $columns=[];
        foreach ($mainDBresult as $column){
            array_push($columns,$column['COLUMN_NAME']);
        }
        return $columns;
    }

    public function insertData($table){
        $seccondCon = $this->connectToSeccond();
        $mainCon = $this->connectToMain();
        $rows = $this->mainTableSchema($table)[0]['TABLE_ROWS'];
        $limit = 1000;
        $offset = 0;

        //truncate seccond table
        $sql = "TRUNCATE TABLE `$table` " ;
        try {
            $seccondCon->exec($sql);
            //echo "Table truncated successfully";
        } catch(PDOException $e) {
            echo $e->getMessage();
        }

        if ($rows > 0){
            while ($offset < $rows){
                try {
                    $seccondCon->beginTransaction();
                    $sql2 = "SELECT * FROM $table LIMIT $offset , $limit";
                    $selectSql = $mainCon->prepare($sql2);
                    $selectSql->execute();
                    $selectSql->setFetchMode(PDO::FETCH_ASSOC);
                    $selectResult =$selectSql->fetchAll();
                    $rowCount = $selectSql->rowCount();
                    $offset += $rowCount;
                    /*var_dump($result2);*/
                    //var_dump($rowCount);

                    $insertSql = "INSERT INTO $table";
                    $query = "";
                    foreach( $selectResult as $key=> $row ) {
                        $array_keys = array_keys($selectResult);
                        $comma = end($array_keys) != $key ? " , " : "";
                        $query .= "( ";
                        foreach ($row as $key1=>$value){
                            $array_keys = array_keys($row);
                            $comma1 = end($array_keys) != $key1 ? " , " : "";
                            $value = '"'. addslashes($value) .'"';
                            $query .= $value . $comma1;
                        }
                        $query .= " ) " . $comma;
                    }

                    $columns = "(" . implode(',',$this->getColumns($table)) . ")";
                    $insertSql .= $columns . " VALUES " . $query ;
                    //var_dump($insertSql);
                    $res = $seccondCon->prepare($insertSql);
                    $res->execute();

                    //var_dump($rowCount1);

                    $seccondCon->commit();
                } catch (PDOException $e) {
                    $seccondCon->rollback();
                    echo "error:" . $e->getMessage();
                }
            }
        }


    }

    public function __destruct(){
        parent::__destruct();
    }
}

$main = new main();

$mainTables= $main->mainTableSchema();
$seccondTables = $main->seccondTableSchema();


if (isset($_GET['create-table'])){
    $main->createTbale($_GET['create-table']);
    header('Location:'.$_SERVER['PHP_SELF']);
}

if (isset($_GET['insert-table'])){
    $main->insertData($_GET['insert-table']);
    //$main->compareData($_GET['insert-table']);
    header('Location:'.$_SERVER['PHP_SELF']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST"){
    $main->addColumns($_POST['table'],unserialize($_POST['columns']));
}
