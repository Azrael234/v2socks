<?php
use WHMCS\Database\Capsule;
add_hook('AfterCronJob', 1, function() 
{
	$query = \WHMCS\Database\Capsule::table('tblproducts')->where('servertype', 'V2raySocks')->get();
    $query2 = \WHMCS\Database\Capsule::table('tblhosting')->get();
    $query3 = \WHMCS\Database\Capsule::table('tblservers')->where('type', 'V2raySocks')->get();
	$products = V2RaySocks_QueryToArray($query);
    $clients = V2RaySocks_QueryToArray($query2);
    $servers = V2RaySocks_QueryToArray($query3);
    $pids = V2RaySocks_prase_pid($products);
    $pro = V2RaySocks_get_client_products_with_pids($clients,$pids,array('Active','Suspended'));
    $pro = V2RaySocks_update_network($pro,$servers,V2RaySocks_prase_product_DB($products),$products);
});

function V2RaySocks_QueryToArray($query){
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

function V2RaySocks_prase_pid($products,$module = 'V2raySocks'){
    $product = array();
    foreach($products as $pro){
        if($pro['servertype'] == $module){
            $product[] = $pro['id'];
        }
    }
    return $product;
}

function V2RaySocks_get_client_products_with_pids($products,$pids,$status = array('Active')){
	$product = array();
	foreach($products as $pro){
		if(in_array($pro['packageid'],$pids) && in_array($pro['domainstatus'],$status)){
			$product[] = $pro;
		}
	}
	return $product;
}

function V2RaySocks_update_network($products,$server,$whproduct,$oldproducts){
    foreach($server as $ser){
        $mysql = new mysqli($ser['ipaddress'], $ser['username'], decrypt($ser['password']));
        $servername = 'mysqlserver'.$ser['id'];
        $$servername = $mysql;
    }
    $product = array();
    foreach($products as $pro){
        $sid = $pro['server'];
        $mysql = 'mysqlserver'.$sid;
        $sql = $$mysql;
        $sql->select_db($whproduct[$pro['packageid']]);
        $sqlq = "SELECT * FROM `user` WHERE sid = " . $pro['id'];
        $ssacc = mysqli_fetch_array($sql->query($sqlq),MYSQLI_ASSOC);
        $uasql = "SELECT * FROM `user_usage` WHERE sid = " . $pro['id'] ." ORDER BY `date` LIMIT 1";
        $usagee = mysqli_fetch_array($sql->query($uasql),MYSQLI_ASSOC);
        if(empty($usagee)){
        	$dataa = $ssacc['u'].",".$ssacc['d'].",".$ssacc['u'].",".$ssacc['d'].",".time().",".$pro['id'];
        }else{
			$dataa = ($ssacc['u'] - $usagee['upload']).",".($ssacc['d'] - $usagee['download']).",".$ssacc['u'].",".$ssacc['d'].",".time().",".$pro['id'];
        }
		$upmysql = "INSERT INTO `user_usage` (`upload`,`download`,`tupload`,`tdownload`,`date`,`sid`) VALUES(".$dataa.")";
        $sql->query($upmysql);
    }
    return $product;
}

function V2RaySocks_prase_product_DB($products,$module = 'V2raySocks'){
    $product = array();
    foreach($products as $pro){
        if($pro['servertype'] == $module){
            $product[$pro['id']] = $pro['configoption1'];
        }
    }
    return $product;
}
?>