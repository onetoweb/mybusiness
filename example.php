<?php

require 'vendor/autoload.php';

session_start();

use Onetoweb\MyBusiness\Client;
use Onetoweb\MyBusiness\Token;
use Onetoweb\MyBusiness\Exception\RequestException;

$username = 'username';
$password = 'password';
$baseUri = 'https://CLIENTNAME.mybusiness.nl/api/MyConnect/v1/';


// setup client
$client = new Client($baseUri, $username, $password);

// set token callback to store token
$client->setTokenUpdateCallback(function(Token $token) {
    
    $_SESSION['token'] = [
        'accessToken' => $token->getAccessToken(),
        'refreshToken' => $token->getRefreshToken(),
        'expires' => $token->getExpires(),
    ];
    
});

// load token from storage
if (isset($_SESSION['token'])) {
    
    $token = new Token(
        $_SESSION['token']['accessToken'],
        $_SESSION['token']['refreshToken'],
        $_SESSION['token']['expires']
    );
    
    $client->setToken($token);
    
}

// example api calls
try {
    
    // get products
    $products = $client->get('product', ['page' => 1]);
    
    $productkey = $products['results'][0]['productkey'];
    
    // get product
    $product = $client->get("product/$productkey");
    
    // search products
    $products = $client->get('product', ['products.productname' => '*zwart', 'page' => 1]);
    
    // get webshop categories
    $webshopcategory = $client->get('webshopcategory');
    
    // get stock
    $stock = $client->get('stock');
    
    // get discount lists
    $discountlist = $client->get('discountlist', ['page' => 1]);
    
    // get relations
    $relations = $client->get('relation', ['page' => 1]);
    
    $searchname = $relations['results'][0]['searchname'];
    
    // get relation
    $relation = $client->get("relation/$searchname");
    
    // get relation delivery addresses
    $deliveryaddresses = $client->get("relation/$searchname/deliveryaddress");
    
    $addressname = $deliveryaddress['results'][0]['addressname'];
    
    // get deliveryaddress
    $deliveryaddress = $client->get("relation/$searchname/deliveryaddress/$addressname");
    
    // create relation
    $relation = $client->post('relation', [
        'searchname' => 'searchname',
        'name' => 'company name',
        'firstname' => 'firstname',
        'surname' => 'surname',
        'email' => 'info@example.com',
        'phone' => '0123456789',
    ]);
    
    $searchname = $relation['searchname'];
    
    // create delivery address
    $deliveryaddress = $client->post("relation/$searchname/deliveryaddress", [
        'addressname' => 'addressname',
        'addressrelation' => $searchname,
        'addressheader' => 'addressheader',
        'city' => 'city',
        'postalcode' => '1000AA',
        'streetname' => 'streetname',
        'streetnumber' => '1',
        'streetnumbersuffix' => 'A',
        'defaultaddress' => 'J',
    ]);
    
    $addressname = $deliveryaddress['addressname'];
    
    // get orders
    $orders = $client->get('order', ['page' => 1]);
    
    $nmbr = $orders['results'][0]['nmbr'];
    
    // get order
    $order = $client->get("order/$nmbr");
    
    // create order
    $order = $client->post('order', [
        'order' => [
            'orderrelation' => $searchname,
            'yourorder' => 'yourorder',
            'extreference' => 'extreference'
        ],
    ]);
    
    $nmbr = $order->nmbr;
    
    // add product to order
    $order = $client->post("order/$nmbr/product/$productkey", [
        'productkey' => $productkey,
        'quantity' => 1,
    ]);
    
    $sequence = $order['products'][0]['sequence'];
    
    // remove product from the order
    $order = $client->delete("order/$nmbr/product/$productkey/sequence/$sequence");
    
    // get assets
    $assets = $client->get('asset', ['page' => 1]);
    
    $assetNmbr = $assets['results'][0]['nmbr'];
    
    // get asset
    $asset = $client->get("asset/$assetNmbr");
    
    // get reservations
    $reservations = $client->get('reservation', ['page' => 1]);
    
    // get reservation
    $assetNmbr = $reservations['results'][0]['nmbr'];
    $sequence = $reservations['results'][0]['sequence'];
    
    $reservation = $client->get("reservation/$assetNmbr/$sequence");
    
    // delete delivery address
    $client->delete("relation/$searchname/deliveryaddress/$addressname");
    
    // delete relation
    $client->delete("relation/$searchname");
    
    // delete order
    $client->delete("order/$nmbr");
    
} catch (RequestException $requestException) {
    
    if ($requestException->getCode() == 404) {
        
        // no results
        echo 'no results';
    }
    
    // contains (error) response from the api
    $error = json_decode($requestException->getMessage());
}