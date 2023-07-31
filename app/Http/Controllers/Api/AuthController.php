<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth,Hash,Validator;
use App\Models\User;
 
class AuthController extends BaseController
{
    public function __construct(){

    }

    /**
     * User Registraction
    */
    public function register(Request $request){
        try{            
            $requestData = $request->all();
            $validator   = Validator::make($requestData,[
                'name'      => 'required|max:55',
                'email'     => 'email|required|unique:users',
                'password'  => 'required|confirmed'
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors(),422);  
            }
            $requestData['password'] = Hash::make($requestData['password']);
            $user = User::create($requestData);
            return $this->sendResponse($user, 'User register successfully.');
        }catch(Exception $e){
            return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.',401);  
        }
    }

    /**
     * User Login
    */
    public function login(Request $request){
        try{
            $requestData = $request->all();
            $validator = Validator::make($requestData,[
                'email'     => 'email|required',
                'password'  => 'required'
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors(),422);  
            }

            if(! auth()->attempt($requestData)){
                return $this->sendError('Invalid Username or Password', [],422);  
            }
            $accessToken = auth()->user()->createToken('authToken')->accessToken;
            return $this->sendResponse(['user' => auth()->user(), 'access_token' => $accessToken], 'User register successfully.');
        }catch(Exception $e){
            return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.',401);  
        }
    }

    /**
     * User profile
    */
    public function profile(Request $request){
        $user = $request->user();
        return response()->json(['user' => $user], 200);
    }

    /**
     * User logout
    */
    public function logout (Request $request){
        try{
            $token = $request->user()->token();
            $token->revoke();
            $response = ['status' => true, 'message' => 'You have been successfully logged out!'];
            return $this->sendResponse([], 'You have been successfully logged out!');
        }catch(CustomException $e) {
            return response()->json(['error' => 'UnAuthorised Access'], 401);
        }        
    }

}
