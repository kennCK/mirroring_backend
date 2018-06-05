<?php

namespace App\Http\Controllers;

use App\Account;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
class AccountController extends MirroringController
{
     function __construct(){  
        $this->model = new Account();
        $this->validation = array(  
          "email" => "unique:accounts",
          "username"  => "unique:accounts"
        );
    } 


    public function create(Request $request){
      $request = $request->all();
      $request['account_type'] = 'USER';
      $request['password'] = Hash::make($request['password']);
      $this->insertDB($request);
      return $this->response();
    }

    public function loginMobile(Request $request){
      $request = $request->all();
      $result = Account::where('username', '=', $request['username'])->get();
      if(sizeof($result) > 0){
        if(Hash::check($request['password'], $result[0]['password'])){
          return response()->json(array('data' => $result[0], 'message' => null));
        }else{
          return response()->json(array('data' => null, 'message' => 'Invalid Credentials'));
        }
      }else{
        return response()->json(array('data' => null, 'message' => 'Invalid Credentials'));
      }
    }
}