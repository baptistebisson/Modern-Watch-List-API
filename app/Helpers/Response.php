<?php
namespace App\Helpers;

class Response
{
    protected $response;
    
    function __construct()
    {
        $this->response = array(
            'error' => true,
            'message' => 'A problem occured',
        );
    }
    
    public function error(bool $error, string  $message) {
        $this->response = array(
            'error' => $error,
            'message' => $message,
        );
    }
    
    public function get(){
        return $this->response;
    }
}
