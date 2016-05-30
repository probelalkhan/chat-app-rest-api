<?php
require_once '../include/DbOperation.php';
require_once '../libs/gcm/gcm.php';
require '.././libs/Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

// User id from db - Global Variable
$user_id = NULL;


/**
 * User Registration
 * url - /register
 * method - POST
 * params - name, email
 */
$app->post('/register', function () use ($app) {
    //Verifying parameters
    verifyRequiredParams(array('name', 'email'));

    //Response array
    $response = array();

    //Getting request parameters
    $name = $app->request->post('name');
    $email = $app->request->post('email');

    //Vaidating email
    validateEmail($email);

    //Creating a db object
    $db = new DbOperation();

    //INserting user to database
    $res = $db->createUser($name, $email);

    //If user created
    //Adding the user detail to response
    if ($res == USER_CREATED_SUCCESSFULLY) {
        $response["error"] = false;

        $user = $db->getUser($email);

        $response['id'] = $user['id'];
        $response['name'] = $user['name'];
        $response['email'] = $user['email'];

        echoResponse(201, $response);

        //If user creating failes adding error to response
    } else if ($res == USER_CREATE_FAILED) {
        $response["error"] = true;
        $response["message"] = "Oops! An error occurred while registereing";
        echoResponse(200, $response);

        //If user already exist
        //adding the user data to response
    } else if ($res == USER_ALREADY_EXISTED) {
        $response["error"] = false;
        $user = $db->getUser($email);

        $response['id'] = $user['id'];
        $response['name'] = $user['name'];
        $response['email'] = $user['email'];

        echoResponse(200, $response);
    }
});

/*
 * URL: /send
 * Method: POST
 * parameters: id, message
 * */

//This is used to send message on the chat room
$app->post('/send', function () use ($app) {

    //Verifying required parameters
    verifyRequiredParams(array('id', 'message'));

    //Getting request parameters
    $id = $app->request()->post('id');
    $message = $app->request()->post('message');
    $name = $app->request()->post('name');

    //Creating a gcm object
    $gcm = new GCM();

    //Creating db object
    $db = new DbOperation();

    //Creating response array
    $response = array();

    //Creating an array containing message data
    $pushdata = array();
    //Adding title which would be the username
    $pushdata['title'] = $name;
    //Adding the message to be sent
    $pushdata['message'] = $message;
    //Adding user id to identify the user who sent the message
    $pushdata['id']=$id;

    //If message is successfully added to database
    if ($db->addMessage($id, $message)) {
        //Sending push notification with gcm object
        $gcm->sendMessage($db->getRegistrationTokens($id), $pushdata);
        $response['error'] = false;
    } else {
        $response['error'] = true;
    }
    echoResponse(200, $response);
});

/*
 * URL: /storegcmtoken/:id
 * Method: PUT
 * Parameters: token
 * */

//This will store the gcm token to the database
$app->put('/storegcmtoken/:id', function ($id) use ($app) {
    verifyRequiredParams(array('token'));
    $token = $app->request()->put('token');
    $db = new DbOperation();
    $response = array();
    if ($db->storeGCMToken($id, $token)) {
        $response['error'] = false;
        $response['message'] = "token stored";
    } else {
        $response['error'] = true;
        $response['message'] = "Could not store token";
    }
    echoResponse(200, $response);
});

/*
 * URL: /messages
 * Method: GET
 * */

//This will fetch all the messages available on the database to display on the thread
$app->get('/messages', function () use ($app){
    $db = new DbOperation();
    $messages = $db->getMessages();
    $response = array();
    $response['error']=false;
    $response['messages'] = array();
    while($row = mysqli_fetch_array($messages)){
        $temp = array();
        $temp['id']=$row['id'];
        $temp['message']=$row['message'];
        $temp['userid']=$row['users_id'];
        $temp['sentat']=$row['sentat'];
        $temp['name']=$row['name'];
        array_push($response['messages'],$temp);
    }
    echoResponse(200,$response);
});


//Function to validate email
function validateEmail($email)
{
    $app = \Slim\Slim::getInstance();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["error"] = true;
        $response["message"] = 'Email address is not valid';
        echoResponse(400, $response);
        $app->stop();
    }
}


//Function to display the response in browser
function echoResponse($status_code, $response)
{
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);
    // setting response content type to json
    $app->contentType('application/json');
    echo json_encode($response);
}


//Function to verify required parameters
function verifyRequiredParams($required_fields)
{
    $error = false;
    $error_fields = "";
    $request_params = $_REQUEST;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoResponse(400, $response);
        $app->stop();
    }
}



function authenticate(\Slim\Route $route)
{
    //Implement authentication if needed
}


$app->run();