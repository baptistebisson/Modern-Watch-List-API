<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Token;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class LoginMiddleware
{
    public function handle($request, Closure $next)
    {
        try {
            JWTAuth::parseToken();
            $token = JWTAuth::getToken();
        } catch (JWTException $e) {
            abort(401, JWTAuth::parseToken());
        }

        // Try to verify token
        try {
            // If sucessful, save user on request
            $request->user = JWTAuth::authenticate($token);
        }

        catch (TokenBlacklistedException $e) {
            abort(401, 'Token "' . JWTAuth::manager()->decode($token, false)['jti'] . '" Blacklisted');
        }

        // If token has expired...
        catch (TokenExpiredException $e) {

            try {
                // Try to refresh token
                $token = JWTAuth::refresh($token);
                JWTAuth::setToken($token);

                // Authenticate with new token, save user on request
                $request->user = JWTAuth::authenticate($token);
            }

            // If token refresh period has expired...
            catch(TokenExpiredException $e) {

                // Return 401 status
                abort(401, 'Token Expired');
            }
        }

        return $next($request);
    }
}
