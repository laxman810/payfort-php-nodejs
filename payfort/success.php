<?php
include('header.php');
require_once 'dbConfig.php';
session_start();
require_once 'PayfortIntegration.php';
$objFort = new PayfortIntegration();
require __DIR__ . '/../vendor/autoload.php';
$db = database;
$client = new MongoDB\Client("mongodb://" . username . ":" . password . "@" . hostname . ":27017/" . $db);
$condition = array('_id' => new MongoDB\BSON\ObjectID($_REQUEST['UserId']));
if((int)$_REQUEST['UserType'] == (int)1){
    $dataFromQuery = $client->$db->slaves->findOne($condition);
    $isDefault = true;
    if (isset($dataFromQuery->cardDetails) && count($dataFromQuery->cardDetails) > 0) {
        $isDefault = false;
    }
    $updataData = array("cardDetails" => array('_id' => new MongoDB\BSON\ObjectID(), 'isDefault' => $isDefault, 'fort_id' => $_REQUEST['fort_id'], 'token_name' => $_REQUEST['token_name'], 'payment_option' => $_REQUEST['payment_option'], 'merchant_reference' => $_REQUEST['merchant_reference'], 'card_holder_name' => $_REQUEST['card_holder_name'], 'card_number' => $_REQUEST['card_number'], 'expiry_date' => $_REQUEST['expiry_date']));    
    $response = $client->$db->masters->updateOne($condition, array('$push' => $updataData));
}else{
    $dataFromQuery = $client->$db->slaves->findOne($condition);
    $isDefault = true;
    if (isset($dataFromQuery->cardDetails) && count($dataFromQuery->cardDetails) > 0) {
        $isDefault = false;
    }
    $updataData = array("cardDetails" => array('_id' => new MongoDB\BSON\ObjectID(), 'isDefault' => $isDefault, 'fort_id' => $_REQUEST['fort_id'], 'token_name' => $_REQUEST['token_name'], 'payment_option' => $_REQUEST['payment_option'], 'merchant_reference' => $_REQUEST['merchant_reference'], 'card_holder_name' => $_REQUEST['card_holder_name'], 'card_number' => $_REQUEST['card_number'], 'expiry_date' => $_REQUEST['expiry_date']));
    $response = $client->$db->slaves->updateOne($condition, array('$push' => $updataData));
}
?>
<html>
    <head>
        <link href="https://fonts.googleapis.com/css?family=Lato" rel="stylesheet">   
        <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">  
        <style>
            body{
                font-size: 21px;
                overflow-x: hidden;    
                font-family: 'Lato', sans-serif;
            }

            .Main{
                font-size: 48px;
                font-weight: 700;
                color: #445525;
                ;
            }
            .content{
                margin-top: 20%;
            }
        </style>   
    </head>
    <body>
        <div class="container text-center content">
            <?php
            if (isset($_SESSION['lan'])) {
                if ($_SESSION['lan'] == 'en') {
                    ?>
                    <h1 class="Main" >Success !!!</h1>
                    <h3 class="Msg" >Your card added successfully...</h3>    
                    <?php
                } else {
                    ?>
                    <h1 class="Main" >بنجاح  !!!</h1>
                    <h3 class="Msg" >تم اضافة الكرت الخاص بك بنجاح</h3>
                    <?php
                }
            } else {
                ?>
                <h1 class="Main" >Success !!!</h1>
                <h3 class="Msg" >Your card added successfully...</h3>
                <?php
            }
            ?>

        </div>
    </body>
</html>