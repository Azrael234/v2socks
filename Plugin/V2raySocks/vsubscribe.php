<?php
require(dirname(dirname(dirname(dirname(__FILE__)))).'/init.php');
use WHMCS\Database\Capsule;
if(isset($_GET['sid']) && isset($_GET['token'])){
	$sid = $_GET['sid'];
	$token = $_GET['token'];
	$service = \WHMCS\Database\Capsule::table('tblhosting')->where('id', $sid)->where('username', $token)->first();
	if (empty($service)){
		die('Unisset or Uncorrect Token');
	}
	if ($service->domainstatus != 'Active' ) {
        die('Not Active');
    }
	$package = Capsule::table('tblproducts')->where('id', $service->packageid)->first();
	$server = Capsule::table('tblservers')->where('id', $service->server)->first();

	$dbhost = $server->ipaddress ? $server->ipaddress : 'localhost';
	$dbname = $package->configoption1;
	$dbuser = $server->username;
	$dbpass = decrypt($server->password);
	$db = new PDO('mysql:host=' . $dbhost . ';dbname=' . $dbname, $dbuser, $dbpass);
	$usage = $db->prepare('SELECT * FROM `user` WHERE `sid` = :sid');
	$usage->bindValue(':sid', $sid);
	$usage->execute();
	$usage = $usage->fetch();
	$servers = $package->configoption4;
    if($servers == ""){
        $servers = \WHMCS\Database\Capsule::table('tblservers')->where('id', $service->server)->get();
        $servers = V2raySocks_OS_QueryToArray($servers);
        $servers = $servers[0]['assignedips'];
    }
	$noder = explode("\n",$servers);
	$results = "";
    //if (strpos($_SERVER['HTTP_USER_AGENT'], 'Mozilla') !== false) {
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'Shadowrocket') !== false) {
        $tmp =  trim("到期时间:" . $service->nextduedate . "/剩余流量:" . round(Convert($usage["transfer_enable"] - $usage["u"] - $usage["d"], 'bytes', 'gb')) . "G");
        $results .= 'STATUS=' . $tmp . PHP_EOL . 'REMARKS=' . $package->name . PHP_EOL;
    }
	foreach($noder as $nodee){
		$nodee = explode('|', $nodee);
		$results .= make_vmess($nodee,$usage['uuid']) . PHP_EOL;
	}
	echo(str_replace('=','',base64_encode($results)));
}else{
	die('Invaild');
}

function V2raySocks_OS_QueryToArray($query){
    $products = array();
    foreach ($query as $product) {
        $producta = array();
        foreach($product as $k => $produc){
            $producta[$k] = $produc;
        }
        $products[] = $producta;
    }
    return $products;
}

function make_vmess($nodee,$uuid){
    $atr1 = array(
        "add" => $nodee[1],
        "host"=> $nodee[5],
        "id"  => $uuid,
        "net" => $nodee[7],
        "path"=> $nodee[6],
        "port"=> $nodee[2],
        "ps"  => $nodee[0] . "(" . $nodee[8] . ")",
        "tls" => $nodee[4],
        "v"   => 2
    );
    if ($nodee[9]){
        $atr1['aid'] = intval($nodee[9]);
    }else{
        $atr1['aid'] = 64;
    }
    if($nodee[3]){
        $atr1['type'] = $nodee[3];
    }else{
        $atr1['type'] = "none";
    }
    return "vmess://".base64_encode(json_encode($atr1));  
}

function Convert($number, $from, $to){
    $to = strtolower($to);
    $from = strtolower($from);
    switch ($from) {
    case 'gb':
        switch ($to) {
        case 'mb':
            return $number * 1024;
        case 'bytes':
            return $number * 1073741824;
        default:
        }
        return $number;
        break;
    case 'mb':
        switch ($to) {
        case 'gb':
            return $number / 1024;
        case 'bytes':
            return $number * 1048576;
        default:
        }
        return $number;
        break;
    case 'bytes':
        switch ($to) {
        case 'gb':
            return $number / 1073741824;
        case 'mb':
            return $number / 1048576;
        default:
        }
        return $number;
        break;
    default:
    }
    return $number;
}