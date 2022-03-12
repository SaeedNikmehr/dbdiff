<?php
define('MAIN',[
    'servername'=>'127.0.0.1',
    'username'=>'root',
    'password'=>'',
    'dbschema'=>'information_schema',
    'dbname'=>'test'
]);
define('SECCOND',[
    'servername'=>'192.168.1.1',
    'username'=>'test',
    'password'=>'test',
    'dbschema'=>'information_schema',
    'dbname'=>'test2',
]);
ini_set('memory_limit', '-1');
ini_set('memory_limit', '-1');
ini_set('max_allowed_packet', 500);
set_time_limit(300);

class DB{
    public $mainSchema;
    public $seccondSchema;
    public function __construct(){
        try {
            $this->mainSchema = new PDO("mysql:host=".MAIN['servername'].";dbname=".MAIN['dbschema'].";charset=UTF8", MAIN['username'], MAIN['password']);
            $this->mainSchema->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
        }

        try {
            $this->seccondSchema = new PDO("mysql:host=".SECCOND['servername'].";dbname=".SECCOND['dbschema'].";charset=UTF8", SECCOND['username'], SECCOND['password']);
            $this->seccondSchema->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
        }
    }

    public function connectToMain(){
        try {
            $con = new PDO("mysql:host=".MAIN['servername'].";dbname=".MAIN['dbname'].";charset=UTF8", MAIN['username'], MAIN['password']);
            $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
        }
        return $con;
    }

    public function connectToSeccond(){
        try {
            $con = new PDO("mysql:host=".SECCOND['servername'].";dbname=".SECCOND['dbname'].";charset=UTF8", SECCOND['username'], SECCOND['password']);
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

class main extends DB{
    public function __construct(){
        parent::__construct();
    }

    public function mainTableSchema($table = null){
        if ($table == null)
            $sql = "SELECT TABLE_NAME,TABLE_ROWS FROM TABLES WHERE TABLE_SCHEMA = '".MAIN['dbname']."'";
        else
            $sql = "SELECT TABLE_ROWS FROM TABLES WHERE TABLE_SCHEMA = '".MAIN['dbname']."'  AND TABLE_NAME ='".$table."'";
        $stmt = $this->mainSchema->prepare($sql);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $result =$stmt->fetchAll();
        /*echo "<pre>";
        var_dump($result);die();*/
        return $result;
    }

    public function seccondTableSchema(){
        $sql = "SELECT TABLE_NAME ,TABLE_ROWS FROM TABLES WHERE TABLE_SCHEMA = '".SECCOND['dbname']."'";
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
                    TABLE_SCHEMA = '".MAIN['dbname']."' AND TABLE_NAME ='".$tableName."'";

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
                    TABLE_SCHEMA = '".SECCOND['dbname']."' AND TABLE_NAME ='".$tableName."'";

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
                    TABLE_SCHEMA = '".MAIN['dbname']."' AND TABLE_NAME ='".$table."'";

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
                    TABLE_SCHEMA = '".SECCOND['dbname']."' AND TABLE_NAME ='".$table."'";

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
                    TABLE_SCHEMA = '".MAIN['dbname']."' AND TABLE_NAME ='".$table."'";

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
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Comparison 2 Database</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
</head>
<body style="background: #fffde6">

<div class="container-fluid" >
    <div class="row">
        <div class="col-8">
            <p style="margin-top: 20px">MAIN TABLE : <?php echo MAIN['dbname']; ?></p>
            <!--            <div class="table-responsive">-->
            <table class="table table-sm table-dark table-hover ">
                <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">table name</th>
                    <th scope="col">table rows</th>
                    <th scope="col">comparison</th>
                    <th scope="col">differences (columns)</th>
                    <th scope="col">actions</th>
                </tr>
                </thead>
                <tbody>
                <?php $i =0; foreach ($mainTables  as $mainTable):
                    $searchTable = $main->searchForTable($mainTable['TABLE_NAME'] , $seccondTables); ?>
                    <tr>
                        <th scope="row"><?php echo ++$i;?></th>
                        <td><?php echo $mainTable['TABLE_NAME'] ;?></td>
                        <td><?php echo $mainTable['TABLE_ROWS'] ;?></td>
                        <td><?php if ($searchTable <= 0) echo "does not exist";else echo "exist";?></td>
                        <td>
                            <?php
                            if ($searchTable <= 0){
                                echo "---";
                            } else{
                                $fields = $main->fieldsComparison($mainTable['TABLE_NAME']);
                                if (empty($fields)){
                                    echo "nothing found";
                                } else{
                                    foreach ($fields as $field){
                                        echo "<span style='background: #ea3aea70;border-radius: 1px;padding-right: 4px;padding-left: 4px;margin-left: 4px;'> " .$field['COLUMN_NAME'] . "</span>" ;
                                    }
                                }
                            }
                            ?>
                        </td>
                        <td>
                            <?php if ($searchTable <= 0){
                                echo "<a href='".$_SERVER['PHP_SELF']."?create-table=".$mainTable['TABLE_NAME']."'><button class='btn-sm btn-success'>add table</button></a>";
                            }else{
                                if (empty($fields)){
                                    if ($main->compareData($mainTable['TABLE_NAME']) == false){
                                        echo "<a href='".$_SERVER['PHP_SELF']."?insert-table=".$mainTable['TABLE_NAME']."'><button class='btn-sm btn-danger'>export data</button></a>";
                                    }else{
                                        echo "---";
                                    }

                                } else{
                                    ?>
                                    <form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
                                        <input type="hidden" name="table" value="<?php echo $mainTable['TABLE_NAME'];?>">
                                        <input type="hidden" name="columns" value='<?php echo htmlentities(serialize($fields))?>'>
                                        <button type="submit" class='btn-sm btn-primary'>add differences</button>
                                    </form>
                                    <?php
                                }
                            } ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <!--            </div>-->
        </div>


        <div class="col-4" >
            <p style="margin-top: 20px">SECCOND TABLE : <?php echo SECCOND['dbname']; ?></p>
            <table class="table table-sm table-dark">
                <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">table name</th>
                    <th scope="col">table rows</th>
                </tr>
                </thead>
                <tbody>
                <?php $i =0; foreach ($seccondTables  as $seccondTable):?>
                    <tr>
                        <th scope="row"><?php echo ++$i;?></th>
                        <td><?php echo $seccondTable['TABLE_NAME'] ;?></td>
                        <td><?php echo $seccondTable['TABLE_ROWS'] ;?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
</body>
</html>
