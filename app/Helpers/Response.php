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
    
    public function error(array $error) {
        $this->response = array(
            'error' => $error[0],
            'message' => $error[1],
        );
    }
    
    public function get(){
        return $this->response;
    }
}
