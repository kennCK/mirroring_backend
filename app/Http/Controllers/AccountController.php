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
}