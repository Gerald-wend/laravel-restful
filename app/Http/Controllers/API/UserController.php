<?php

namespace App\Http\Controllers\API;

use Validator;
use Sentinel;
use Activation;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

use App\Models\User;

use App\Http\Requests\UserAllRequest;
use App\Http\Requests\UserGetRequest;
use App\Http\Requests\UserRegisterRequest;
use App\Http\Requests\UserLoginRequest;
use App\Http\Requests\UserActivateRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Requests\UserDeleteRequest;

class UserController extends Controller {

    public function me(){
        return response()->json(Auth::user());
    }

    public function all(UserAllRequest $request) {
        $users = User::paginate();
        return response()->json($users, 200);
    }

    public function get(UserGetRequest $request, string $slug) {
	   $user = User::where('slug', $slug)->first();
        if($user) {
            return response()->json([
              'status' => 'success',
              'data' => $user,
            ], 200);
        } else {
            return response()->json([
              'status' => 'error',
              'message' => 'User not found',
            ], 404);
        }
    }

    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */
    public function register(UserRegisterRequest $request) {
        $credentials = $request->all();
        $user['permissions'] = [
            'view.profile' => true,
            'update.profile' => true,
        ];
        $user = Sentinel::register($credentials);
        if($user) {
            /**
             * Attach default Role
             */
            $default_role = Sentinel::findRoleBySlug('subscribers');
            $default_role->users()->attach($user);
            return response()->json([
               'status' => 'success',
               'message' => 'User Registered Successfully',
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'User Registration failed',
            ]);
        }
    }

    /**
     * Login api
     *
     * @return \Illuminate\Http\Response
     */
    public function login(UserLoginRequest $request) {
        if ($user = Sentinel::stateless([
            'email' => $request->email,
            'password' => $request->password,
          ])) {
            $token = $user->createToken('MyApp')->accessToken;
            return response()->json([
                'status' => 'success',
                'data' => $token
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access',
            ], 401);
        }
    }

    /**
     * Undocumented function
     *
     * @param App\Http\Requests\UserActivateRequest $request
     * @param string $slug the user slug
     * @param string $code the activation code
     * @return JSON
     */
    public function activate(UserActivateRequest $request, string $slug, string $code) {
        if ($user = User::where('slug', $slug)->first()) {
            if (Activation::complete($user, $code)) {
                return response()->json([
                    'status' => 'success'
                ], 200);
            } else {
                return response()->json([
                    'status' => 'error'
                ], 404);
            }
        } else {
            return response()->json([
                'status' => 'error',
            ], 404);
        }
    }

    /**
     * Undocumented function
     *
     * @param Request $request
     * @param integer $id
     * @return JSON
     */
    public function update(UserUpdateRequest $request, string $slug) {
        $user = Sentinel::findBySlug($slug);
        if($user) {
            $user->name = $request->name;
            $user->email = $request->email;
            if($user->save()) {
                return response()->json(['success' => 'User updated'], 201);
            } else {
                return response()->json(['error' => 'User not updated'],500);
            }
        } else {
            return response()->json(['error' => 'Not Found'], 404);
        }
    }

    public function delete(UserDeleteRequest $request, string $slug) {
        $user = Sentinel::findBySlug($slug);
        if($user) {
            if ($user->delete()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'User deleted successfully',
                ], 200);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to delete the user',
                ], 500);
            }
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
            ], 404);
        }
    }
}
