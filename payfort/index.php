<?php include('header.php') ?>
<style>
    .waller_details {
        text-align: center;
        color: #189d5b;
        margin-top:3%
    }
    .h-seperator {
        width: 100%;
        border-bottom: 2px dashed #C9D0E1;
        margin: 2em auto 2em auto;
        display: block;
        padding: 0em;
        opacity: .3;
        display: none!important;
    }
    body {
        background-color: #fff;
        color: #444;
        margin: 0;
        padding: 0;
        font-size: .9em;
        line-height: 1.4em;
        font-family: Muli;
        font-weight: bold;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        margin: 0 0 0px!important;
    }
    .actions a {
        width: 100%!important;
        padding: 1em;
        display: block;
        background: #d8d8d8;
        text-align: center;
        text-decoration: none;
        font-size: 1.1em;
        border-radius: 4px;
        color: #fff;
        font-weight: 700;
    }
    .actions {
        width: 100%;
        height: auto;
        padding: 0!important;
        overflow: hidden;
        margin: 0!important;
    }
    .wrapper {
        width: 95%!important;
        max-width: 1024px;
        margin: auto;
    }
    section.nav {
        display: none;
    }
    header {
        display: none!important;
    }
    footer {
        display: none!important;
    }
    .form-horizontal .control-label {
        text-align: <?= ($lan_data['lan_dir'] == 'ltr')?"left":"right"?> !important; 
    }
    .form-control {
        text-align: <?= ($lan_data['lan_dir'] == 'ltr')?"right":"left"?>;
        border: none !important;
        box-shadow: none !important;
        background-color: #fff !important;
    }
    .form-group {
        padding-left:5px;
        display: -webkit-box;
        display: -webkit-flex;
        display: -ms-flexbox;
        display:         flex;
        flex-wrap: wrap;
        border-bottom: 1px solid #e4e7f0;
    }
    .form-group > [class*='col-'] {
        display: flex;
        flex-direction: column;
    }
    .control-label{
        color:#a1a6bb;
        padding-top: 7px;
    }
    .form-control{
        color:#222328;
    }
    .datepicker th{
        background: white;
        color:black;
        font-weight: bold;
    }
</style>

<?php
require __DIR__ . '/../vendor/autoload.php'; //Composer installed
require_once 'PayfortIntegration.php';
require_once 'dbConfig.php';
$objFort = new PayfortIntegration();
$amount = $objFort->amount;
$currency = $objFort->currency;
session_start();
$_SESSION['lan'] = $_REQUEST['lan'];
$totalAmount = $amount;


if (isset($_REQUEST['UserId']) AND ! empty($_REQUEST['UserId'])) {

    if(isset($_REQUEST['UserType']) AND ! empty($_REQUEST['UserType']))
    {
        $db = database;
        $client = new MongoDB\Client("mongodb://" . username . ":" . password . "@" . hostname . ":27017/" . $db);
        $condition = array('_id' => new MongoDB\BSON\ObjectID($_REQUEST['UserId']));
        if($_REQUEST['UserType'] == 1){
             $dataFromQuery = $client->$db->masters->findOne($condition);
        }else if($_REQUEST['UserType'] == 2){
            $dataFromQuery = $client->$db->slaves->findOne($condition);
        }else{
            echo 'Please enter valid userType';
            exit;
        }    
        if (empty($dataFromQuery)) {
            echo 'Please send valid UserId';
            exit;
        }
    }else{

        echo 'Please enter user type';
        exit;
    }

} else {
    echo 'Please send UserId';
    exit;
}

?>
<section class="nav">
    <ul>
        <li class="active lead"> Payment Method</li>
        <li class="lead"> Done</li>
    </ul>
</section>
<div class="h-seperator"></div>

<section class="payment-method">
    <ul>
        <li>
        
            <div class="details" style="padding-top:10px;">
                <form id="frm_payfort_payment_merchant_page2" class="form-horizontal">
                    <div class="form-group">
                        <label class="col-xs-5 control-label" for="payfort_fort_mp2_card_holder_name"><?= $lan_arr[$lan_id][0]?></label>
                        <div class="col-xs-6">
                            <input type="text" class="form-control required" name="card_holder_name" id="payfort_fort_mp2_card_holder_name" placeholder="<?= $lan_arr[$lan_id][0]?>" maxlength="50" onkeypress="return onlyAlpha(event, this)">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-5 control-label" for="payfort_fort_mp2_card_number"><?= $lan_arr[$lan_id][1]?></label>
                        <div class="col-xs-6">
                            <input type="tel" class="form-control required" name="card)number" id="payfort_fort_mp2_card_number" placeholder="<?= $lan_arr[$lan_id][1]?>" maxlength="16" onkeypress="return onlyDigit(event, this)">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-5 control-label" for="payfort_fort_mp2_expiry_month"><?= $lan_arr[$lan_id][2]?></label>
                        <div class="col-xs-6">
                            <input type="text" readonly class="datepicker form-control required" placeholder="MM/YY" id="payfort_fort_mp2_expiry">
                        </div>
                    </div>
                    <input type="hidden" id="UserId" value="<?php echo $_REQUEST['UserId'] ?>">
                    <input type="hidden" id="UserType" value="<?php echo $_REQUEST['UserType'] ?>">
                    <div class="form-group">
                        <label class="col-xs-5 control-label" for="payfort_fort_mp2_cvv"><?= $lan_arr[$lan_id][3]?></label>
                        <div class="col-xs-6">
                            <input type="tel" class="form-control required" name="cvv" id="payfort_fort_mp2_cvv" placeholder="<?= $lan_arr[$lan_id][3]?>" maxlength="3" onkeypress="return onlyDigit(event, this)">
                        </div>
                    </div>
                </form>
            </div>
        </li>        
    </ul>
</section>

<div class="h-seperator"></div>



<section class="actions">
    <a class="btn btn-info" id="btn_continue" href="javascript:void(0)" style="background-color: #333333;"><?= $lan_arr[$lan_id][4]?></a>
</section>

<div class="alert alert-warning" style="margin-top:20px;">
    <i class="fa fa-exclamation-triangle"></i> <?= $lan_arr[$lan_id][5]?>
</div>

<script src="plugin/sweetalert-dev.js"></script>
<link rel="stylesheet" type="text/css" href="plugin/sweetalert.css">
<script type="text/javascript" src="vendors/jquery.min.js"></script>
<script type="text/javascript" src="assets/js/jquery.creditCardValidator.js"></script>
<script type="text/javascript" src="assets/js/checkout.js"></script>
<script src="https://uxsolutions.github.io/bootstrap-datepicker/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
<script type="text/javascript">
    function onlyAlpha(event,txtid) {
        var inputValue = event.which;
        //if digits or not a space then don't let keypress work.
        if ((inputValue > 64 && inputValue < 91) // uppercase
                || (inputValue > 96 && inputValue < 123) // lowercase
                || inputValue == 32) { // space
            return;
        }
        event.preventDefault();
    }
    function onlyDigit(evt,txtid) {
        evt = (evt) ? evt : window.event;
        var charCode = (evt.which) ? evt.which : evt.keyCode;
        if (charCode > 31 && (charCode < 48 || charCode > 57)) {
            return false;
        }
        return true;
    }
    $(document).ready(function () {
        $('.datepicker').datepicker({
            autoclose: true,
            format: 'mm/yy',
            startDate: '+3d',
            endDate: '+11y',
            orientation: "bottom <?= ($lan_data['lan_dir'] == 'ltr')?"right":"left"?>",
            startView: "months",
            minViewMode: "months",
            language: '<?= $lan_data['lan_code']?>',
            isRTL: <?= ($lan_data['lan_dir'] == 'ltr')?"false":"true"?>
        });
        $('input:radio[name=payment_option]').click(function () {
            $('input:radio[name=payment_option]').each(function () {
                if ($(this).is(':checked')) {
                    $(this).addClass('active');
                    $(this).parent('li').children('label').css('font-weight', 'bold');
                    $(this).parent('li').children('div.details').show();
                } else {
                    $(this).removeClass('active');
                    $(this).parent('li').children('label').css('font-weight', 'normal');
                    $(this).parent('li').children('div.details').hide();
                }
            });
        });
        var i = 0;
        $('#btn_continue').click(function () {
           
            if(i == 0){
               
                i = 1;
                $('.form-group').removeClass('has-error');
                var flg = false;
                $('.required').each(function(){
    //                var maxlength = $(this).attr('maxLength');
                    var val = $(this).val();
                    if(val == '' || typeof val == 'undefined'){
                        flg = true;
                        $(this).closest('.form-group').addClass('has-error');
                    }
                });
                if(flg){
                    i=0;
                    return;
                }
                var paymentMethod = 'cc_merchantpage2';//$('input:radio[name=payment_option]:checked').val();
                if (paymentMethod == '' || paymentMethod === undefined || paymentMethod === null) {
                    swal({
                        title: 'Pelase Select Payment Method!',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    i=0;
                    return;
                }
                if (paymentMethod == 'cc_merchantpage') {
                    
                    window.location.href = 'confirm-order.php?payment_method=' + paymentMethod;
                }
                if (paymentMethod == 'cc_merchantpage2') {
                    
                    var isValid = payfortFortMerchantPage2.validateCcForm();
                    if (isValid) {
                  
                        getPaymentPage(paymentMethod);
                    }else{
                        
                        i=0;

                    }
                } else {
                    getPaymentPage(paymentMethod);
                }
            }
        });
    });
    var lan_arr = <?= json_encode($lan_arr[$lan_id])?>;
    var payfortFortMerchantPage2 = (function () {
        return {
            validateCcForm: function () {
                this.hideError();
               
                var isValid = payfortFort.validateCardHolderName($('#payfort_fort_mp2_card_holder_name'));
                
                if (!isValid) {
                    
                    this.showError(lan_arr[6]);
                    return false;
                }
                
                isValid = payfortFort.validateCreditCard($('#payfort_fort_mp2_card_number'));
                if (!isValid) {
                   
                    this.showError(lan_arr[7]);
                    return false;
                }
               
                isValid = payfortFort.validateCvc($('#payfort_fort_mp2_cvv'));
                if (!isValid) {
                     
                    this.showError(lan_arr[8]);
                    return false;
                }
                return true;
            },
            showError: function (msg) {
                swal({
                    title: msg,
                    timer: 2000,
                    showConfirmButton: false
                });
            },
            hideError: function () {
                return;
            }
        };
    })();
</script>
<?php include('footer.php') ?>