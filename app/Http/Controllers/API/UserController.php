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
    
     //Function for handle API get user profile
    public function fetch(Request $request)
    {
        $user = Auth::user()->id;
        $user = User::with('usergroup.group')->where('id',$user)->get();
        return ResponseFormatter::success($user,'Data profile user berhasil diambil'); 
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */

    //Function for handle API Login
    public function login(Request $request)
    {
        
        try {
            
            $request->validate([ 
                'email' => 'email|required',  
                'password' => 'required' 
            ]);
           
            $credentials = request(['email', 'password']);
            if (!Auth::attempt($credentials)) { 
                return ResponseFormatter::error([ 
                    'message' => 'Unauthorized'
                ],'Authentication Failed', 500);
            }

            $user = User::where('email', $request->email)->first();
            if ( ! Hash::check($request->password, $user->password, [])) { 
                throw new \Exception('Invalid Credentials'); 
            }

            $tokenResult = $user->createToken('authToken')->plainTextToken; 
            return ResponseFormatter::success([ 
                'access_token' => $tokenResult, 
                'token_type' => 'Bearer',
                'user' => $user
            ],'Authenticated');
        } catch (Exception $error) { 
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error->getMessage(),
            ],'Authentication Failed', 500);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */

    //Function For Handle API Register
    public function register(Request $request)
    {
        
        try {
            $request->validate([ 
                'name' => ['required', 'string', 'max:255'], 
                'username' => ['required', 'string', 'max:255', 'unique:users'], 
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'string', new Password] 
            ]);
            
            User::create([
                'name' => $request->name,  
                'email' => $request->email, 
                'username' => $request->username, 
                'password' => Hash::make($request->password),
            ]);
            
            $user = User::where('email', $request->email)->first();
           
            $tokenResult = $user->createToken('authToken')->plainTextToken;
            
            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer', 
                'user' => $user 
            ],'User Registered'); 
        } catch (Exception $error) { 
            return ResponseFormatter::error([ 
                'message' => 'Something went wrong', 
                'error' => $error->getMessage(),  
            ],'Authentication Failed', 500); 
        }
    }

    //Function for handle API Logout
    public function logout(Request $request)
    {
        
        $token = $request->user()->currentAccessToken()->delete(); 
        return ResponseFormatter::success($token,'Token Revoked'); 
    }

   //Function for handel API update profile
    public function updateProfile(Request $request)
    {
        $data = $request->all(); 

        $user = Auth::user(); 
        $user->update($data); 

        return ResponseFormatter::success($user,'Profile Updated');
    }
}
