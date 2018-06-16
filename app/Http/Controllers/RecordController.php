<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Record;
use Carbon\Carbon;
class RecordController extends MirroringController
{
    function __construct(){
    	$this->model = new Record();
    }


    public function create(Request $request){
    	$data = $request->all();
    	if($request->hasFile('pdf')){
    		$date = Carbon::now()->toDateString();
    		$time = str_replace(':', '_',Carbon::now()->toTimeString());
    		$ext 	= $request->file('pdf')->extension();
    		$filename = $data['account_id'].'_'.$date.'_'.$time.'.'.$ext;
    		$request->file('pdf')->storeAs('files', $filename);
    		$this->model = new AccountProfile();
    		$insertData = array(
    			'account_id'	=> $data['account_id'],
    			'type'				=> $data['type'],
    			'url'					=> '/storage/files/'.$filename,
    			'filename' 		=> $request->file('pdf')->getClientOriginalName(),
    			'code' 				=> $this->generateCode()
    		);
    		$this->insertDB($insertData);
    		return json_encode(array('data' => $this->response['data']));
    	}else{
    		return json_encode(array('data' => null));
    	}
    }
    public function retrieve(Request $request){
    	$request = $request->all();
    	$result = Record::where($request['column'], '=', $request['value'])->get();
    	if(sizeof($result) > 0){
    		return json_encode(array('data' => $result));
    	}else{
    		return json_encode(array('data' => null));
    	}
    }

    public function generateCode(){
      $code = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
      $codeExist = Record::where('code', '=', $code)->get();
      if(sizeof($codeExist) > 0){
        $this->generateCode();
      }else{
        return $code;
      }
    }
}
