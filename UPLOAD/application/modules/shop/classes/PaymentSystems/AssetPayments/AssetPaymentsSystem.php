<?php

class AssetPaymentsSystem extends BasePaymentProcessor {

public $order_id = '';
public $template_vars = array();

public $URL_CreatePayment = 'https://assetpayments.us/checkout/pay';
//public $URL_MerchatInformation = 'http://payment.kaznachey.net/api/PaymentInterface/GetMerchatInformation';

public function __construct() {

$this->order = ShopCore::app()->SPaymentSystems->getOrder();

}

//Сохранение параметров
public function saveSettings(SPaymentMethods $paymentMethod) {

$saveKey = $paymentMethod->getId() . '_AssetPaymentsData';
ShopCore::app()->SSettings->set($saveKey, serialize($_POST['asset']));
return true;

}

//Загрузка параметров
protected function loadSettings() {

$settingsKey = $this->paymentMethod->getId() . '_AssetPaymentsData';
$data = unserialize(ShopCore::app()->SSettings->$settingsKey);
if ($data === false) $data = array();
return array_map('encode', $data);

}

//ХЗ для чего
public function add_array($arr) {

if (count($arr) > 0) {
$this->template_vars = array_merge($this->template_vars, $arr);
return TRUE;
}

return FALSE;

}

//Вспомогательное
function AssetPayments_Request ($req_uri, $req_data) {

$req_data = json_encode($req_data);

if (function_exists('curl_init')) {

$fp_init = curl_init();
curl_setopt($fp_init, CURLOPT_URL, $req_uri);
curl_setopt($fp_init, CURLOPT_POST, true);
curl_setopt($fp_init, CURLOPT_POSTFIELDS, $req_data);
curl_setopt($fp_init, CURLOPT_RETURNTRANSFER, true);

curl_setopt($fp_init, CURLOPT_HTTPHEADER, array(
'Expect: ',
'Content-Type: application/json; charset=UTF-8',
'Content-Length: '. strlen($req_data))
);

$fp_res = curl_exec($fp_init);
curl_close($fp_init);

}

else {

$opts = array(
'http'=>array(
'method' => 'POST',
'header' => 'Content-Length: ' . strlen($req_data) . "\r\nContent-Type: application/json\r\n",
'content' => $req_data,
)
);

$context = stream_context_create($opts); 
$fp_res = @file_get_contents($req_uri, 0, $context);

}

return $fp_res;

}

//Параметры (в админке)
public function getAdminForm() {

$data = $this->loadSettings();

$form = '

<div class="control-group">
<label class="control-label" for="inputRecCount">MerchantID</label>
<div class="controls">
<input type="text" name="asset[MerchantID]" value="' . $data['MerchantID'] . '"  />
</div>
</div>

<div class="control-group">
<label class="control-label" for="inputRecCount">SecretKey</label>
<div class="controls">
<input type="text" name="asset[SecretKey]" value="' . $data['SecretKey'] . '"/>
</div>
</div>

<div class="control-group">
<label class="control-label" for="inputRecCount">TemplateID (Default=19)</label>
<div class="controls">
<input type="text" name="asset[TemplateID]" value="' . $data['TemplateID'] . '"/>
</div>
</div>

';

return $form;

}


//Создание формы для оплаты
public function getForm() {

$k_Result = $_GET ['Result'];
$k_OrderId = $_GET ['OrderId'];

if ($k_Result && $k_OrderId) {

ob_end_clean();

header("Content-Type: text/html; charset=utf-8");

if ($k_Result == 'success') $mes = "<p class=\"valid\">Спасибо! Ваш заказ #$k_OrderId оплачен.</p>";
if ($k_Result == 'failed') $mes = "<p class=\"invalid\">Платеж по заказу #$k_OrderId не прошел, либо находится в обработке.</p>";

?>

<html>
	<head>
		<style>
			body{background-color: #527496; font: normal 13px Verdana,sans-serif;}
			.message_container{background-color: #fff; width: 50%; text-align:center; margin: auto; margin-top: 100px; padding: 50px;}
			.valid {color: green;}
			.invalid {color: red;}
		</style>
	</head>
	
	<body>
		<div class='message_container'> <h4><?=$mes;?></h4> 
		<input type='button' value=' Закрыть ' onCLick="location='http://<?=$_SERVER['HTTP_HOST'];?>';">
		</div>
	
	</body>
</html>

<?

exit;

}

$order_key = $this->order->getKey();
$paymentMethod = $this->paymentMethod->getId();

$data = $this->loadSettings();

$req_data = array(
'MerchantID' => $data ['MerchantID'],
'Signature' => md5($data ['MerchantID'] . $data ['SecretKey'])
);

$fp_res = $this->AssetPayments_Request($this->URL_MerchatInformation, $req_data);

$Form_DATA = '';
$fp_res = json_decode($fp_res, 1);
$PaySystems_a = $fp_res ['PaySystems'];

foreach ($PaySystems_a as $PaySystem) {

if ($Form_DATA) $checked = '';
else $checked = ' checked="checked"';

$PS_ID = $PaySystem ['Id'];
$PS_Name = $PaySystem ['PaySystemName'];

$Form_DATA .= "<input type=\"radio\" name=\"PSystem_ID\" value=\"$PS_ID\"$checked> $PS_Name <br/>";

}

$Form_DATA .= "<input type=\"hidden\" name=\"pm\" value=\"$paymentMethod\"><br/>";

$this->render('AssetPayments', array(
'action' => shop_url("cart/view/$order_key/"),
'Form_DATA' => $Form_DATA,
));

return;

}

//Инициализация оплаты и callBack ответ
public function processPayment() {

$PSystem_ID = $_GET ['PSystem_ID'];

$order_key = $this->order->getKey();
$order_id = $this->order->getId();
$paymentMethod = $this->paymentMethod->getId();
$CurrencyId = $this->paymentMethod->getCurrencyId();

$data = $this->loadSettings();
$MerchantID = $data ['MerchantID'];
$TemplateID = $data ['TemplateID'];
$SecretKey = $data ['SecretKey'];

$currencies = SCurrenciesQuery::create()->find();
foreach ($currencies as $c) $this->currencies[$c->getId()] = $c;
$currency = $this->currencies[$CurrencyId];

$currency_Code = $currency->code;
$currency_Rate = $currency->getRate();

$TotalPrice = 0;
$PSystem_ID = '3';
//$send_data ['SelectedPaySystemId'] = $PSystem_ID;

$db = \CI::$APP->db;
$db->where('shop_orders_products.order_id', $order_id);
$db->join('shop_products', 'shop_orders_products.product_id=shop_products.id', 'left outer');

$Products = $db->get('shop_orders_products')->result_array();
foreach ($Products as $orderProduct) {
	$product_item = array();
	$product_item ['ImageUrl'] = productImageUrl($orderProduct['mainModImage']);
	$product_item ['ProductItemsNum'] = number_format($orderProduct['quantity'], 2, '.', '');
	$product_item ['ProductName'] = $orderProduct['product_name'];
	$product_item ['ProductPrice'] = number_format($orderProduct['price'] * $currency_Rate, 2, '.', '');
	$product_item ['ProductId'] = $orderProduct['product_id'];

	//$send_data ['Products'][] = $product_item;
	$TotalPrice += $product_item ['ProductPrice'] * $product_item ['ProductItemsNum'];
	$product_count += $product_item ['ProductItemsNum'];
}

$TotalPrice = number_format($TotalPrice, 2, '.', '');
$product_count = number_format($product_count, 2, '.', '');

//Инициализация
if ($PSystem_ID && !$_GET['back']) {

if ($user_id = $this->order->getUserId()) {
    $profile = SUserProfileQuery::create()->filterById($user_id)->findone();
}else{
	$user_id = 1;
}

	//****Currency fix****//
	if (stristr($currency_Code, 'R') === 0) $currency_Code = 'RUB';
	elseif (stristr($currency_Code, 'UA') === 0) $currency_Code = 'UAH';
	elseif (stristr($currency_Code, 'US') === 0) $currency_Code = 'USD';
	elseif (stristr($currency_Code, 'E') === 0) $currency_Code = 'EUR';
	
	$ip = getenv('HTTP_CLIENT_IP')?:
			  getenv('HTTP_X_FORWARDED_FOR')?:
			  getenv('HTTP_X_FORWARDED')?:
			  getenv('HTTP_FORWARDED_FOR')?:
			  getenv('HTTP_FORWARDED')?:
			  getenv('REMOTE_ADDR');
			  
	$out_summ = ShopCore::app()->SCurrencyHelper->convert($this->order->getTotalPrice(), $this->paymentMethod->getCurrencyId());
	
	$user_id = $this->order->getUserId();
	$profile = SUserProfileQuery::create()->filterById($user_id)->findone();
	
	
	//****Required variables****//	
		$option['TemplateId'] = $TemplateID;
		$option['CustomMerchantInfo'] = 'Order N.'. '' .$order_id;
		$option['MerchantInternalOrderId'] = $order_id;
		$option['StatusURL'] = shop_url("cart/view/$order_key?back=1&pm=$paymentMethod");	
		$option['ReturnURL'] = shop_url("cart/view/$order_key?back=1");
		$option['AssetPaymentsKey'] = $MerchantID;
		$option['Amount'] = $this->order->total_price;	
		$option['Currency'] = $currency_Code;
		$option['CountryISO'] = 'UKR';
		$option['IpAddress'] = $ip;
		
	//****Customer data and address****//
		$option['FirstName'] = $this->order->user_full_name;
        $option['Email'] = $this->order->user_email;
        $option['Phone'] = $this->order->user_phone;
        $option['Address'] = $this->order->user_deliver_to. ', ' .$this->order->user_city;
        $option['City'] = $this->order->user_city;
	
	//****Adding cart details****//
	
		$order_total = 0;  //Calculating order total to get shipping cost
		foreach ($Products as $orderProduct) {
			$option['Products'][] = array(
				"ProductId" => $orderProduct['product_id'],
				"ProductName" => $orderProduct['product_name'],
				"ProductPrice" => number_format($orderProduct['price'] * $currency_Rate, 2, '.', ''),
				"ProductItemsNum" => number_format($orderProduct['quantity'], 2, '.', ''),
				"ImageUrl" => productImageUrl($orderProduct['mainModImage']),
			);
			$order_total += $orderProduct['price'] * $orderProduct['quantity'];
		}
	
	//****Adding shipping method****//
	
		$shipping_cost = $order_info['total'] - $order_total; //Calculating shipping cost
		$option['Products'][] = array(
				"ProductId" => '12345',
				"ProductName" => $this->order->getSDeliveryMethods()->getName(),
				"ProductPrice" => $this->order->getDeliveryPrice(),
				"ImageUrl" => 'https://assetpayments.com/dist/css/images/delivery.png',
				"ProductItemsNum" => 1,
			);	

$data = base64_encode( json_encode($option) );		

?>
	<html>
        <head>
        <meta http-equiv="content-type" content="text/html; charset=utf-8" />            
        </head>
        <body>
		
		<form method="POST" id="paymentform" name = "paymentform" action="https://assetpayments.us/checkout/pay" style="display:none;">
            <input type="hidden" name="data" value="<?php print $data?>" />
        </form>
        <br>
        <script type="text/javascript">document.getElementById('paymentform').submit();</script>
        </body>
    </html>
<?php

$data = json_decode($res, 1);

$ErrorCode = $data ['ErrorCode'];
$ExternalForm = $data ['ExternalForm'];
if ($ErrorCode) die("ErrorCode=$ErrorCode");

$ExternalForm = base64_decode($ExternalForm);

die($ExternalForm);

}else {//CallBack оплаты

		$json = json_decode(file_get_contents('php://input'), true);
		
		$key = $MerchantID;
		$secret = $SecretKey;
		$transactionId = $json['Payment']['TransactionId'];
		$signature = $json['Payment']['Signature'];
		$order_id = $json['Order']['OrderId'];
		$status = $json['Payment']['StatusCode'];
		$requestSign =$key.':'.$transactionId.':'.strtoupper($secret);
		$sign = hash_hmac('md5',$requestSign,$secret);
		
		if ($status == 1 && $sign == $signature) {
			$this->setOrderPaid();			
		} 
		if ($status == 2 && $sign == $signature) {
			
			
		}

$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : file_get_contents('php://input');

$req_data = json_decode($HTTP_RAW_POST_DATA, 1);
$MerchantInternalPaymentId = abs(intval($req_data ['MerchantInternalPaymentId']));
$Signature = strtoupper($req_data ['Signature']);
$Sum = $req_data ['Sum'];
$ErrorCode = $req_data ['ErrorCode'];
$MerchantInternalUserId = $req_data ['MerchantInternalUserId'];
$CustomMerchantInfo = $req_data ['CustomMerchantInfo'];

if (!$MerchantInternalPaymentId) return false;
if ($ErrorCode) return false;

$Sum = number_format($Sum, 2, '.', ''); //857.00 => 857 WTF!!!

$signature_true = strtoupper(md5($ErrorCode . $order_id . $MerchantInternalUserId . $Sum . $CustomMerchantInfo . $MerchantSecretKey));
if ($Signature != $signature_true) return false;

if ($this->order->getPaid() == true) return ERROR_ORDER_PAID_BEFORE;

$this->setOrderPaid();

die("OK{$order_id}");

}

}

}

?>