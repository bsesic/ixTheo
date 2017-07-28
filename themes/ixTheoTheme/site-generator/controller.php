<?php
/**
 * Created by PhpStorm.
 * User: benni
 * Date: 21.07.17
 * Time: 21:36
 */



class JsonLoader
{
    public function loadPassword($username, $password){
        //read password file
        $content = file_get_contents("passwords.json");
        $json_array = json_decode($content, true);


        //iterate trough array
        foreach ($json_array as $key => $val ){
            if(($key == $username) && ($val === md5($password))){
                return true;
                } else { return false; }
        }

    }


}