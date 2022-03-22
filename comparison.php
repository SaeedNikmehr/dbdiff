
<!doctype html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport"
            content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>Comparison of 2 Database</title>
        <link rel="stylesheet" href="resources/css/bootstrap.min.css">
        <link rel="icon" type="image/x-icon" href="resources/images/compare.png">
    </head>
    <body style="background: #fffde6">

    <div class="container-fluid" >
        <div class="row">
            <div class="col-8">
                <p style="margin-top: 20px">MAIN TABLE : <?=FIRST_DB['dbname'];?> <small>( <?=FIRST_DB['servername'];?> )</small> </p>
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
            </div>


            <div class="col-4" >
                <p style="margin-top: 20px">SECCOND TABLE : <?=SECCOND_DB['dbname'];?> <small>( <?=SECCOND_DB['servername'];?> )</small></p>
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
    <script src="resources/js/jquery-3.2.1.slim.min.js"></script> 
    <script src="resources/js/popper.min.js"></script> 
    <script src="resources/js/bootstrap.min.js"></script> 
    </body>
</html>
