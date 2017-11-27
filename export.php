<?php
/**
 * Created by PhpStorm.
 * User: Azad
 * Date: 11/2/2017
 * Time: 16:59
 */

//function parse token from login form
function file_get_contents_curl($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6');
    curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookie.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookie.txt');
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}


//get token
$html = file_get_contents_curl("https://demo.phpmyadmin.net/STABLE/index.php");
$doc = new DOMDocument();
$doc->loadHTML($html);
$inputs = $doc->getElementsByTagName('input');

for ($i = 0; $i < $inputs->length; $i++) {
    $input = $inputs->item($i);
    if($input->getAttribute('name') == 'token');
    $token = $input->getAttribute('value');
}
//end get token


//start authentication
function authentication($url, $data = []){
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6');
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookie.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookie.txt');
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

$url_auth = 'https://demo.phpmyadmin.net/STABLE/index.php';
$auth_data = [
    'pma_username' => 'root', 'pma_password' => '', 'token' => $token, 'server' => '2'
];
authentication($url_auth, $auth_data);
//end authentication


//get inner token
$goal_data = file_get_contents_curl('https://demo.phpmyadmin.net/STABLE/tbl_export.php?db=mysql&table=user&single_table=true');
$goal_dom = new DOMDocument();
@$goal_dom->loadHTML($goal_data);
$xp = new DOMXpath($goal_dom);
$nodes = $xp->query('//input[@name="token"]');
$node = $nodes->item(0);
$inner_token = $node->getAttribute('value');
//end

//start export
function post_request($url, $data = []){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,60);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6');
    curl_setopt($ch, CURLOPT_REFERER, 'https://demo.phpmyadmin.net');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookie.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookie.txt');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_VERBOSE, false);
    $server_output = curl_exec ($ch);
    curl_close ($ch);
    return $server_output;
}

$export_url = 'https://demo.phpmyadmin.net/STABLE/export.php';
$form_data = [
    'token' => $inner_token,
    'db' => 'mysql',
    'table' => 'user',
    'export_type' => 'database',
    'export_myehod' => 'quick',
    'quick_or_custom' => 'custom',
    'db_select[]' => 'mysql',
    'table_select[]' => 'user',
    'table_structure[]' => 'user',
    'table_data[]' => 'user',
    'what' => 'sql',
    'output_format' => 'sendit',
    'charset' => 'utf-8',
    'sql_structure_or_data' => 'structure_and_data'
];

$data = post_request($export_url, $form_data);
file_put_contents('user.sql', $data);
//end