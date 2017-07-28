<?php
//include
include('config.php');
include('route.php');
include('controller.php');

require_once 'vendor/autoload.php';

//enable for debugging the route

//echo $_SERVER['REQUEST_URI'] . "\n";

//load twig
$loader = new Twig_Loader_Filesystem('views');
$twig = new Twig_Environment($loader, array('cache' => 'cache',));

function debug_to_console( $data ) {
    $output = $data;
    if ( is_array( $output ) )
        $output = implode( ',', $output);

    echo "<script>console.log( 'Debug Objects: " . $output . "' );</script>";
}

//config
Config::set('basepath','ixtheo/themes/ixTheoTheme/site-generator');

//init routing
Route::init();

//base route (startpage)
Route::add('',function(){
    //Do something
    echo 'Welcome :-)';
});

//base route
Route::add('index.php',function(){
    //check if user is logged in
    debug_to_console("index.php");
    global $twig;
    $login = false; //set default to false

    if(isset($_POST['submit'])) {
        $username = $_POST['inputName'];
        $password = $_POST['inputPassword'];

        $jsonLoader = new JsonLoader();
        $login = $jsonLoader->loadPassword($username,$password);

    }

    if ($login == false){
        //display login page

        $template =  $twig->load('/login.twig');
        echo $template->render(array('form_action' => $_SERVER['PHP_SELF']));
    } else {
        //display main page
        echo "You are logged in.";
    }

});

//simple route
Route::add('test.html',function(){
    //Do something
    echo 'Hello from test.html';
});

//complex route with parameter
Route::add('user/(.*)/edit',function($id){
    //Do something
    echo 'Edit user with id '.$id.'<br/>';
});

//accept only numbers as the second parameter. Other chars will result in a 404
Route::add('foo/([0-9]*)/bar',function($var1){
    //Do something
    echo $var1.' is a great number!';
});

//long route
Route::add('foo/bar/foo/bar',function(){
    //Do something
    echo 'hehe :-)<br/>';
});

//crazy route with parameters (Will be triggered on the route pattern above too because it matches too)
//Route::add('(.*)/(.*)/(.*)/(.*)',function($var1,$var2,$var3,$var4){
    //Do something
//    echo 'You have entered: '.$var1.' / '.$var2.' / '.$var3.' / '.$var4.'<br/>';
//});

//Add a 404 Not found Route
Route::add404(function($url){

    //Send 404 Header
    header("HTTP/1.0 404 Not Found");
    echo '404 :-(<br/>';
    echo $url.' not found!';

});

Route::run();


// routing table



// CRUD

//middleware


//load views

