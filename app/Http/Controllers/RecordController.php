<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Record;
class RecordController extends MirroringController
{
    function __construct(){
    	$this->model = new Record();
    }


    public function create(Request $request){
    	$request = $request->all();
    	$request['code'] = $this->generateCode();
    	
    	$this->insertDB($request);
    	if($this->response['data'] > 0){
    		return json_encode(array('data' => $this->response['data']));
    	}else{
    		return json_encode(array('data' => null));
    	}
    }
    public function retrieveMobile(Request $request){
    	$request = $request->all();
    	$result = Record::where('code', '=', $request['code'])->get();
    	if(sizeof($result) > 0){
    		return json_encode(array('data' => $result));
    	}else{
    		return json_encode(array('data' => null));
    	}
    }

    public function generateCode(){
      $code = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
      $codeExist = Account::where('code', '=', $code)->get();
      if(sizeof($codeExist) > 0){
        $this->generateCode();
      }else{
        return $code;
      }
    }
}
