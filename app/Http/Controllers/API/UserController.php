<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Rules\Password;

class UserController extends Controller
{

    /**
     * @param Request $request
     * @return mixed
     */
    //Public function fetch with parameter Request $request to get user data
    public function fetch(Request $request)
    {
        return ResponseFormatter::success($request->user(),'Data profile user berhasil diambil'); //return result user with ResponseFormatter use message "Data profile user berhasil diambil"
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    //Public function login with parameter Request $request
    public function login(Request $request)
    {
        //try is a block of code that can throw an exception and catch is a block of code that will be executed if an error occurs in the try block
        try {
            //variable request will store validate result an array with key email and password 
            $request->validate([ //The rules will store in array 
                'email' => 'email|required',  //key eamil is required and type email
                'password' => 'required' //key password is required 
            ]);
            //After validate, result request will store in $credentials variable
            $credentials = request(['email', 'password']);
            if (!Auth::attempt($credentials)) { //!Auth::attempt($credentials) means if credentials is not match with database will return error response
                return ResponseFormatter::error([ //return error response use ResponseFormatter with message "Unauthorized", status code 500, and Authentication Failed 
                    'message' => 'Unauthorized'
                ],'Authentication Failed', 500);
            }

            $user = User::where('email', $request->email)->first();//$user variable will store result query with email request
            if ( ! Hash::check($request->password, $user->password, [])) { //! Hash::check($request->password, $user->password, []) means if password request is not match with password database will return error response, or password from user request is not match with password from database
                throw new \Exception('Invalid Credentials'); //throw new \Exception('Invalid Credentials') means the error message will be "Invalid Credentials"
            }

            $tokenResult = $user->createToken('authToken')->plainTextToken; //createToken('authToken') means create token with authToken, and plainTextToken means token will be plain text
            return ResponseFormatter::success([ //If request, credentials, and user password match, will return success response with message "Authenticated"
                'access_token' => $tokenResult, 
                'token_type' => 'Bearer',
                'user' => $user
            ],'Authenticated');
        } catch (Exception $error) { //If request, credentials, and user password not match, will return error response with message "Something went wrong"
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error,
            ],'Authentication Failed', 500);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    //Public function register with parameter request
    public function register(Request $request)
    {
        //try is a block of code that can throw an exception
        try {
            $request->validate([ //request use validate with rules. The rules will store in array with key name, username, email, password
                'name' => ['required', 'string', 'max:255'], //rules name required, type string, with max 255 character
                'username' => ['required', 'string', 'max:255', 'unique:users'], //rules username is required, type string, with max 255 character, and 'unique:users' means username must be unique in table users
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],//rules email is required, type string, with max 255 character, and 'unique:users' means email must be unieque in table users
                'password' => ['required', 'string', new Password] //rules password is required, type string, and use "new password" means password must be match with password rules in laravel fortify    
            ]);
            //After validate passed, call User::create thats mean create new user with array data from validate request
            User::create([
                'name' => $request->name,  //key name will store with value from request name. The key will send to database table users
                'email' => $request->email, //key email will store value from request email. The key will send to database table users
                'username' => $request->username, //key username will store value from request username. The key will send to database table users
                'password' => Hash::make($request->password),//key password will store value from request password. The key will send to database table users. Hash::make is a function to make password encrypted
            ]);
            //After create new user, call User::where thats mean select user where email from request email. First() is a function to get first data from database
            $user = User::where('email', $request->email)->first();
            //tokenResults is a variable to store token from user. CreateToken('authToken') is a function to create token with authTOken. plainTextToken is a function to get token
            $tokenResult = $user->createToken('authToken')->plainTextToken;
            //if all process success, return response with success function from ResponseFormatter. The response will send with array data access_token, token_type, and user
            return ResponseFormatter::success([
                'access_token' => $tokenResult, //access_token will store value from tokenResult
                'token_type' => 'Bearer', //token_type will store value Bearer
                'user' => $user //user will store value from user
            ],'User Registered'); //User Registered is a message from success function
        } catch (Exception $error) { //catcth is a block of code that can throw an exception when try block is failed
            return ResponseFormatter::error([ //return response with error function from ResponseFormatter. The response will send with array data message and error
                'message' => 'Something went wrong', //message will store value Something went wrong
                'error' => $error, // error will store value from error 
            ],'Authentication Failed', 500); //Authentication Failed is a message from error function, and 500 is a code error
        }
    }
    //This is logout public function with parameter request
    public function logout(Request $request)
    {
        //This function required token from user. When this function called, token from user logged will be deleted
        $token = $request->user()->currentAccessToken()->delete(); //$token from user logged will be store in $token use currentAccessToken(), after token saved, token will be deleted use delete() function

        return ResponseFormatter::success($token,'Token Revoked'); //If user logged token deleted, will return success response with message Token Revoked
    }

    //This is updateProfile public function with parameter request
    public function updateProfile(Request $request)
    {
        $data = $request->all(); //This request will get all data from form update profile using all() functioin. The result will store in $data variable

        $user = Auth::user(); //This Auth::user() will get user data from token. The result will store in $user variable
        $user->update($data); //$user will update data from $data variable use update() method

        return ResponseFormatter::success($user,'Profile Updated'); //If request update data from user match with database, will return success response with message Profile Updated
    }
}
