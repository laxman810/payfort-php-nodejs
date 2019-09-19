<?php

if(!isset($_REQUEST['r'])) {
    echo 'Page Not Found!';
    exit;
}
require_once 'PayfortIntegration.php';
$objFort = new PayfortIntegration();


if($_REQUEST['r'] == 'getPaymentPage') {
    $objFort->processRequest($_REQUEST['paymentMethod'],$_REQUEST['userId'],$_REQUEST['userType']);
}
elseif($_REQUEST['r'] == 'merchantPageReturn') {
    $objFort->processMerchantPageResponse();
}
elseif($_REQUEST['r'] == 'processResponse') {
    $objFort->processResponse($_REQUEST['UserId'],$_REQUEST['UserType']);
}
else{

    echo 'Page Not Found!';
    exit;
}
?>

