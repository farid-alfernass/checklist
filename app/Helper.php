<?php
namespace App;

class Helper{
    public function isJson($string){
        return is_string($string) && is_array(json_decode($string, true)) ? true : false;
    }    
}