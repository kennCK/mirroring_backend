<?php

namespace App\Http\Controllers;

use App\Account;
use App\AccountDegree;
use App\AccountInformation;
use App\AccountProfile;
use App\AccountSchool;
use App\AccountSemester;
use App\Semester;
use App\AccountWorkExperience;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Jobs\Email;
class AccountController extends MirroringController
{
     function __construct(){  
        $this->model = new Account();
        $this->validation = array(  
          "email" => "unique:accounts",
          "username"  => "unique:accounts"
        );
        $this->notRequired = array(
          'status',
          'code'
        );
    } 

    public function registration(Request $request){
      $request = $request->all();
      dispatch(new Email($request, 'verification')); // Send Email
    }
    public function create(Request $request){
     $request = $request->all();
     $dataAccount = array(
      'code'      => $this->generateCode(),
      'password'  => Hash::make($request['password']),
      'status'    => 'VERIFIED',
      'email'     => $request['email'],
      'username'  => $request['username'],
      'account_type' => $request['account_type']
     );
     $this->insertDB($dataAccount);
     if($this->response['data'] > 0 ){
        $dataSchoolAccount = array(
          'account_id'  => $this->response['data'],
          'school_id'   => $request['school_id']
        );
        $accountSchool = new AccountSchool();
        $accountSChoolResult = $accountSchool->insert($dataSchoolAccount);
        return $this->response();
     }else{
        return $this->response();
     }
    }

    public function updateByVerification(Request $request){
      $data = $request->all();
      $this->model = new Account();
      $this->updateDB($data);
      return $this->response();
    }

    public function updateAction(Request $request){
      $data = $request->all();
      $this->model = new Account();
      $this->updateDB($data);
      return $this->response();
    }
    public function update(Request $request){ 
      $data = $request->all();
      $result = Account::where('code', '=', $data['code'])->where('username', '=', $data['username'])->get();
      if(sizeof($result) > 0){
        $updateData = array(
          'id'        => $result[0]['id'],
          'password'  => Hash::make($data['password'])
        );
        $this->model = new Account();
        $this->updateDB($updateData);
        if($this->response['data'] == true){
          dispatch(new Email($result[0], 'reset_password'));
          return $this->response();
        }else{
          return response()->json(array('data' => false));
        }
      }else{
        return response()->json(array('data' => false));
      }
    }
    public function hashPassword($password){
      $data['password'] = Hash::make($password);
      return $data;
    }
    public function customValidate($text){
      $validation = array('email' => 'required|email'); 
      return $this->validateReply($text, $validation);
    }

    public function validateReply($text, $validation){
      $validator = Validator::make($text, $validation);
      if($validator->fails()){
        return false;
      }
      else
        return true;
    }

    public function retrieve(Request $request){
      $data = $request->all();
      $this->model = new Account();
      $result = $this->retrieveDB($data);

      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
          $result[$i]['account_information_flag'] = false;
          $result[$i]['account_profile_flag'] = false;
          $accountInfoResult = AccountInformation::where('account_id', '=', $result[$i]['id'])->get();
          $accountProfileResult = AccountProfile::where('account_id', '=', $result[$i]['id'])->orderBy('created_at', 'DESC')->get();
          $accountDegreeResult = AccountDegree::where('account_id', '=', $result[$i]['id'])->orderBy('year_started', 'DESC')->get();
          $accountWorkResult = AccountWorkExperience::where('account_id', '=', $result[$i]['id'])->orderBy('year_started', 'DESC')->get();
          $result[$i]['account_information'] = (sizeof($accountInfoResult) > 0) ? $accountInfoResult[0] : null;
          $result[$i]['account_profile'] = (sizeof($accountProfileResult) > 0) ? $accountProfileResult[0] : null;
          $result[$i]['account_degree'] = (sizeof($accountDegreeResult) > 0) ? $this->insertFlagToResult($accountDegreeResult) : null;
          $result[$i]['account_work'] = (sizeof($accountWorkResult) > 0) ? $this->insertFlagToResult($accountWorkResult) : null;
          $result[$i]['schools'] = $this->getSchools($result[$i]['id']);
          $result[$i]['my_semesters'] = $this->getSemesters($result[$i]['id']);
          $result[$i]['school_semesters'] = ($this->getSchools($result[$i]['id']) === null) ? null : $this->getSchoolSemesters($result[$i]['schools'][0]['id']);
          $i++;
        }
        return response()->json(array('data' => $result));
      }else{
        return $this->response();
      }
    }
    public function insertFlagToResult($result){
      $i = 0;
      foreach ($result as $key) {
        $result[$i]['edit_flag'] = false;
        $i++;
      }
      return $result;
    }

    public function requestReset(Request $request){
      $data = $request->all();
      $result = Account::where('email', '=', $data['email'])->get();
      if(sizeof($result) > 0){
        dispatch(new Email($result[0], 'request_reset'));
        return response()->json(array('data' => true));
      }else{
        return response()->json(array('data' => false));
      }
    }

    public function testMail(Request $request){
      $data = $request->all();
      dispatch(new Email($data, 'test')); // Send Email
    }

    public function generateCode(){
      $code = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 64);
      $codeExist = Account::where('code', '=', $code)->get();
      if(sizeof($codeExist) > 0){
        $this->generateCode();
      }else{
        return $code;
      }
    }

    public function getSchools($accountId){
      $result = AccountSchool::where('account_id', '=', $accountId)->leftJoin('schools', 'schools.id', '=', 'account_schools.school_id')->get();
      return (sizeof($result) > 0) ? $result : null;
    }

    public function getSemesters($accountId){
      $result = AccountSemester::where('account_id', '=', $accountId)->orderBy('created_at', 'DESC')->get();
      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
          $semesterResult = Semester::where('id', '=', $result[$i]['semester_id'])->get();
          $result[$i]['semester_details'] = (sizeof($semesterResult) > 0) ? $semesterResult[0] : null;
          $i++;
        }
        return $result;
      }else{
        return null;
      }
    }

    public function getSchoolSemesters($schoolId){
      $result = Semester::where('school_id', '=', $schoolId)->orderBy('start_date', 'DESC')->get();
      return (sizeof($result) > 0) ? $result : null;
    }

}