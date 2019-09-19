<?php

ini_set("log_errors", 1);
$date = date("Y-m-d H:i:s");
error_log("$date: Hello! Running script /services.php" . PHP_EOL);

header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
header('Access-Control-Max-Age: 1000');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

require_once 'dbConfig.php';
require_once 'API.php';
require_once 'PayfortIntegration.php';
require __DIR__ . '/../vendor/autoload.php';

class MyAPI extends API {

    private $payfort;

    public function __construct($request_uri, $postData, $origin) {

        parent::__construct($request_uri, $postData);
        $this->payfort = new PayfortIntegration();
    }

    public function getCardDetails($args) {
        if ($args['userId'] == null || $args['userId'] == '')
            return array('errNum' => 1, 'errFlag' => 1, 'errMsg' => "user id should not be empty");
        $db = database;
        $client = new MongoDB\Client("mongodb://" . username . ":" . password . "@" . hostname . ":27017/" . $db);
        $condition = array('_id' => new MongoDB\BSON\ObjectID($args['userId']));        

        if($args['UserType'] == 1){
            $response = $client->$db->masters->findOne($condition);
        }else{

           $response = $client->$db->slaves->findOne($condition); 
        }

        
        $cardDatail = [];

        foreach ($response['cardDetails'] as $details) {

            $details['expiry_month'] = substr($details['expiry_date'], 2, 2);
            $details['expiry_year'] = substr($details['expiry_date'], 0, 2);
            unset($details['expiry_date']);
            unset($details['_id']);
            unset($details['merchant_reference']);
            $cardDatail[] = $details;
        }

        if ($response)
            return array('errNum' => 0, 'errFlag' => 0, 'response' => $cardDatail);
        else
            return array('errNum' => 1, 'errFlag' => 1, 'errMsg' => "user id should not be empty");
    }

    public function deleteCard($args) {
        if ($args['userId'] == null || $args['userId'] == '' || $args['token_name'] == null || $args['token_name'] == '')
            return array('errNum' => 1, 'errFlag' => 1, 'errMsg' => "Invalid parameters");

        $db = database;
        $client = new MongoDB\Client("mongodb://" . username . ":" . password . "@" . hostname . ":27017/" . $db);
        $condition = array('_id' => new MongoDB\BSON\ObjectID($args['userId']));
        $pullCondition = array('cardDetails' => array('token_name' => $args['token_name']));
        if((int)$args['UserType'] == (int)1){

            $response = $client->$db->masters->updateOne($condition, array('$pull' => $pullCondition));

        }else{
            
           $response = $client->$db->slaves->updateOne($condition, array('$pull' => $pullCondition));
        }

        if ($response)
            return array('errNum' => 0, 'errFlag' => 0, 'response' => 'Card has been deleted');
        else
            return array('errNum' => 1, 'errFlag' => 1, 'errMsg' => "Error..! while deleting card");
    }

    public function chargeCard($args) {
        

        if ($args['amount'] == null || $args['amount'] == '')
            return array('errNum' => 1, 'errFlag' => 1, 'errMsg' => "Amount should not be empty");
        if ($args['merchant_reference'] == null || $args['merchant_reference'] == '')
            return array('errNum' => 1, 'errFlag' => 1, 'errMsg' => "Merchant reference id should not be empty");
        if ($args['token_name'] == null || $args['token_name'] == '')
            return array('errNum' => 1, 'errFlag' => 1, 'errMsg' => "Token name should not be empty");
        if ($args['email'] == null || $args['email'] == '')
            return array('errNum' => 1, 'errFlag' => 1, 'errMsg' => "Email id should not be empty");
        if ($args['name'] == null || $args['name'] == '')
            return array('errNum' => 1, 'errFlag' => 1, 'errMsg' => "Customer name should not be empty");

            $args['description'] = 'Testing charge';
        $response = $this->payfort->PaymentReqeust($args);

        if ($response['response_message'] == 'Success')
            return array('errNum' => 0, 'errFlag' => 0, 'response' => $response);
        else
            return array('errNum' => 1, 'errFlag' => 1, 'response' => $response);
    }
    
    public function chargeAndCapture($args) {
        if ($args['amount'] == null || $args['amount'] == '')
            return array('errNum' => 1, 'errFlag' => 1, 'errMsg' => "Amount should not be empty");
        if ($args['merchant_reference'] == null || $args['merchant_reference'] == '')
            return array('errNum' => 1, 'errFlag' => 1, 'errMsg' => "Merchant reference id should not be empty");
        if ($args['token_name'] == null || $args['token_name'] == '')
            return array('errNum' => 1, 'errFlag' => 1, 'errMsg' => "Token name should not be empty");
        if ($args['email'] == null || $args['email'] == '')
            return array('errNum' => 1, 'errFlag' => 1, 'errMsg' => "Email id should not be empty");
        if ($args['name'] == null || $args['name'] == '')
            return array('errNum' => 1, 'errFlag' => 1, 'errMsg' => "Customer name should not be empty");

        $args['description'] = 'Testing charge';
        $response = $this->payfort->chargeAndCapture($args);

        if ($response['response_message'] == 'Success')
            return array('errNum' => 0, 'errFlag' => 0, 'response' => $response);
        else
            return array('errNum' => 1, 'errFlag' => 1, 'response' => $response);
    }

    public function captureAmount($args) {

        if ($args['fort_id'] == null || $args['fort_id'] == '')
            return array('errNum' => 1, 'errFlag' => 1, 'errMsg' => "fort_id should not be empty");
        if ($args['amount'] == null || $args['amount'] == '')
            return array('errNum' => 1, 'errFlag' => 1, 'errMsg' => "Amount should not be empty");
        if ($args['merchant_reference'] == null || $args['merchant_reference'] == '')
            return array('errNum' => 1, 'errFlag' => 1, 'errMsg' => "Merchant reference id should not be empty");


        $objFort = new PayfortIntegration();
        $response = $objFort->captureAmountReqeust($args);

        if ($response['response_message'] == 'Success')
            return array('errNum' => 0, 'errFlag' => 0, 'response' => $response);
        else
            return array('errNum' => 1, 'errFlag' => 1, 'response' => $response);
    }
    public function refundAmount($args) {

        if ($args['fort_id'] == null || $args['fort_id'] == '')
            return array('errNum' => 1, 'errFlag' => 1, 'errMsg' => "fort_id should not be empty");
        if ($args['amount'] == null || $args['amount'] == '')
            return array('errNum' => 1, 'errFlag' => 1, 'errMsg' => "Amount should not be empty");
        if ($args['merchant_reference'] == null || $args['merchant_reference'] == '')
            return array('errNum' => 1, 'errFlag' => 1, 'errMsg' => "Merchant reference id should not be empty");


        $objFort = new PayfortIntegration();
        $response = $objFort->refundAmount($args);

        if ($response['response_message'] == 'Success')
            return array('errNum' => 0, 'errFlag' => 0, 'response' => $response);
        else
            return array('errNum' => 1, 'errFlag' => 1, 'response' => $response);
    }

}

if (!array_key_exists('HTTP_ORIGIN', $_SERVER)) {

    $_SERVER['HTTP_ORIGIN'] = $_SERVER['SERVER_NAME'];
}

try {

    $API = new MyAPI($_SERVER['REQUEST_URI'], $_REQUEST, $_SERVER['HTTP_ORIGIN']);


    echo $API->processAPI();
} catch (Exception $e) {

    echo json_encode(Array('error' => $e->getMessage()));
}
?>
