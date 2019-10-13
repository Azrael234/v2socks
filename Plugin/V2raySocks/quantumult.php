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
    //$groupName = trim("到期时间" . $service->nextduedate . "|剩余流量" . round(Convert($usage["transfer_enable"] - $usage["u"] - $usage["d"], 'bytes', 'gb')) . "G");
    $groupName = trim($package->name);
	foreach($noder as $nodee){
		$nodee = explode('|', $nodee);
		$results .= make_vmess($nodee, $usage['uuid'], $groupName) . PHP_EOL;
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

function make_vmess($nodee, $uuid, $groupName){
    $str = $nodee[0] . ' = vmess, ' . $nodee[1] . ', ' . $nodee[2] . ', none, "' . $uuid . '", group=' . $groupName;
    if($nodee[4]){
        $str .= ', over-tls=true, tls-host=' . $nodee[5];
    }
    if($nodee[7] != "tcp"){
        $str .= ', certificate=1, obfs=ws, obfs-path="' . $nodee[6] . '", obfs-header="Host: ' . $nodee[1] . '[Rr][Nn]User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 12_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148"';
    }
    return "vmess://".base64_encode($str);  
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