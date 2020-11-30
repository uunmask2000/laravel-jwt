<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Validator;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token)
    {
        try {
            return $this->mix_respone_body([
                // 'access_token' => $token,
                // 'token_type' => 'bearer',
                // 'expires_in' => auth()->factory()->getTTL() * 60,
                // 'user' => auth()->user(),
            ], 200, $token);
        } catch (\Throwable $th) {
            //throw $th;
            return $this->mix_respone_body($th, 400, $token);
        }

    }

    protected function mix_respone_body($error = [], int $code = 0, string $token = '')
    {
        $respone_body = [
            "success" => $code == 200 ? 1 : 0,
            "token" => $token,
            "errorCode" => $code,
            "errorMessage" => $error,
        ];
        if ($token == '') {
            unset($respone_body['token']);
        }

        return response()->json($respone_body, $code);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'email',
            'mobile' => 'string',
            'username' => 'string',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return $this->mix_respone_body($validator->errors(), 422);
        }

        if (!$token = auth()->attempt($validator->validated())) {
            return $this->mix_respone_body(['error' => 'Unauthorized'], 401);
        }

        return $this->createNewToken($token);
    }

    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|confirmed|min:6',
            'mobile' => 'required|regex:/(09)[0-9]{8}/|max:10|unique:users',
            'username' => 'required|string|between:2,100',
        ]);

        if ($validator->fails()) {
            return $this->mix_respone_body($validator->errors(), 400);
        }

        $user = User::create(array_merge(
            $validator->validated(),
            ['password' => bcrypt($request->password)]
        ));

        return $this->mix_respone_body([
            'message' => 'User successfully registered',
            'user' => $user,
        ], 200);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        // return response()->json(['message' => 'User successfully signed out']);
        return $this->mix_respone_body(['message' => 'User successfully signed out'], 200);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        // return $this->createNewToken(auth()->refresh());
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userProfile()
    {
        return response()->json(auth()->user());
    }

    /**
     * post message data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function message(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|between:2,100',
        ]);

        if ($validator->fails()) {
            return $this->mix_respone_body($validator->errors(), 400);
        }

        try {
            JWTAuth::setToken($request->token)->toUser();

            $message = new Message;

            $message->message = $request->message;
            $message->reply = '';
            $message->reply_id = '';

            $message->save();

            return $this->mix_respone_body([
                'message' => 'Write Message successfully',
                'Message' => $message,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return $this->mix_respone_body($th, 400);
        }

    }
    /**
     * post reply message data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function reply(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reply' => 'required|string|between:2,100',
        ]);

        if ($validator->fails()) {
            return $this->mix_respone_body($validator->errors(), 400);
        }

        $message = new Message;
        $message = $message->find($request->input('message_id'));

        try {

            $message2 = new Message;
            $message2->message = '';
            $message2->reply = $request->reply;
            $message2->reply_id = $request->message_id;

            $message2->save();

            return $this->mix_respone_body([
                'message' => 'Reply Message successfully',
                'Message' => $message2,
            ], 200);

        } catch (\Throwable $th) {
            //throw $th;
            return $this->mix_respone_body($th, 400);
        }

    }

}
