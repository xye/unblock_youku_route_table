<?php

$url = 'https://raw.githubusercontent.com/karlcheong/unblock_youku_privoxy_rules/master/basehosts';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$html_content = curl_exec($ch);
curl_close($ch);

//echo $html_content;

$ip_url_list = explode("\n", $html_content);
//print_r($ip_url_list);

$host_list = array();

foreach($ip_url_list as $ip_url){
	if($ip_url == "")
		continue;
	$ip_list = explode(" ", $ip_url);
	if(substr($ip_list[0], 0, 1) != "#")
	$host_list[] = $ip_list[2];
}

print_r($host_list);

$host_ip_list = array();
foreach($host_list as $host_num => $host_url){
	echo "checking {$host_url} \n";
	
	$ip = `dig $host_url  A +short nslookup`; // the backticks execute the command in the shell

	$ips = array();
	if(preg_match_all('/((?:\d{1,3}\.){3}\d{1,3})/', $ip, $match) > 0){
	    $host_ip_list[$host_url] = $match[1];
	    $ips = $match[1];
	}
	
	print_r($ips);
	
}

//print_r($host_ip_list);

$command_text = "";
$vpn_gw = "192.168.8.1";
foreach($host_ip_list as $host_name => $ip_list){
	foreach($ip_list as $ip){
		$command_text .= "ip route add $ip via $vpn_gw\n";
	}
}
echo $command_text;

$command_text_without_newline = substr($command_text, 0, -1);
$router_command_text = <<< EOQ
sleep 35
echo "#!/bin/sh" > /tmp/pptpd_client/checkvpn
echo "while [ 1 ]" >> /tmp/pptpd_client/checkvpn
echo "do" >> /tmp/pptpd_client/checkvpn
echo "$command_text_without_newline" >> /tmp/pptpd_client/checkvpn
echo "sleep 10" >> /tmp/pptpd_client/checkvpn
echo "done" >> /tmp/pptpd_client/checkvpn
chmod 755 /tmp/pptpd_client/checkvpn
sh /tmp/pptpd_client/checkvpn &\n
EOQ;

echo $router_command_text;
