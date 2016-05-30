<?php

class DbOperation
{
    private $conn;

    //Constructor
    function __construct()
    {
        require_once dirname(__FILE__) . '/Config.php';
        require_once dirname(__FILE__) . '/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }

    //Function to create a new user
    public function createUser($name, $email)
    {
        if (!$this->isUserExists($email)) {
            $stmt = $this->conn->prepare("INSERT INTO users(name, email) values(?, ?)");
            $stmt->bind_param("ss", $name, $email);
            $result = $stmt->execute();
            $stmt->close();
            if ($result) {
                return USER_CREATED_SUCCESSFULLY;
            } else {
                return USER_CREATE_FAILED;
            }
        } else {
            return USER_ALREADY_EXISTED;
        }
    }

    //Function to get the user with email
    public function getUser($email)
    {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        return $user;
    }

    //Function to check whether user exist or not
    private function isUserExists($email)
    {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    //Function to store gcm registration token in database
    public function storeGCMToken($id, $token)
    {
        $stmt = $this->conn->prepare("UPDATE users SET gcmtoken =? WHERE id=?");
        $stmt->bind_param("si", $token, $id);
        if ($stmt->execute())
            return true;
        return false;
    }

    //Function to get the registration token from the database
    //The id is of the person who is sending the message
    //So we are excluding his registration token as sender doesnt require notification
    public function getRegistrationTokens($id){
        $stmt = $this->conn->prepare("SELECT gcmtoken FROM users WHERE NOT id = ?;");
        $stmt->bind_param("i",$id);
        $stmt->execute();
        $result = $stmt->get_result();
        $tokens = array();
        while($row = $result->fetch_assoc()){
            array_push($tokens,$row['gcmtoken']);
        }
        return $tokens;
    }

    //Function to add message to the database
    public function addMessage($id,$message){
        $stmt = $this->conn->prepare("INSERT INTO messages (message,users_id) VALUES (?,?)");
        $stmt->bind_param("si",$message,$id);
        if($stmt->execute())
            return true;
        return false;
    }

    //Function to get messages from the database
    public function getMessages(){
        $stmt = $this->conn->prepare("SELECT a.id, a.message, a.sentat, a.users_id, b.name FROM messages a, users b WHERE a.users_id = b.id ORDER BY a.id ASC;");
        $stmt->execute();
        $result = $stmt->get_result();
        return $result;
    }

}