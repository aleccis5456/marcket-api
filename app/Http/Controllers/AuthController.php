<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function index(){
        $user = User::all();
        return response()->json($user, 200);
    }

    public function register(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
        ]);

        if($validator->fails()){
            return response()->json([
                'status'=> 400,
                'message' => 'no se que poner xd',
                'errors' => $validator->errors(),
            ]);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
        ]);

        return response()->json([
            'status' => 201,
            'message' => 'user created',
            'user' => $user,
        ],201);
    }

    public function login(Request $request){             
        $validator = Validator::make($request->all(), [            
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if($validator->fails()){
            return response()->json([
                'status'=> 400,
                'message' => 'no se que poner xd',
                'errors' => $validator->errors(),
            ],400);
        }

        $user = User::where('email',$request->email)->first();
        $credentials = $request->only('email', 'password');        

        if(!$user){
            return response()->json(['message' => 'correo no registrado o incorrecto'],404);
        }   
        if(!Hash::check($request->password, $user->password)){
            return response()->json(['message' => 'contraseña incorrecta'],401);
        }

        if(Auth::attempt($credentials)){
            $user = Auth::user();
            $token = $user->createToken('token')->plainTextToken;

            return response()->json([
                'status' => 200,
                'message' => 'user login',
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer'
            ]);
        }
    }

    public function logout(Request $request){
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out successfully'], 200);
    }
}
