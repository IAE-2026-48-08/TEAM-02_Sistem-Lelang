<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SsoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SsoController extends Controller
{
    public function __construct(private SsoService $ssoService) {}

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        try {
            $result = $this->ssoService->loginUser(
                $request->email,
                $request->password
            );

            return response()->json([
                'success' => true,
                'message' => 'Login berhasil',
                'data'    => [
                    'token'      => $result['token'],
                    'token_type' => 'Bearer',
                    'user'       => [
                        'id'         => $result['sso_user']->id,
                        'email'      => $result['sso_user']->email,
                        'full_name'  => $result['sso_user']->full_name,
                        'nim'        => $result['sso_user']->nim,
                        'local_role' => [
                            'name'         => $result['sso_user']->localRole->name,
                            'display_name' => $result['sso_user']->localRole->display_name,
                        ],
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 401);
        }
    }

    public function me(Request $request): JsonResponse
    {
        $ssoUser = $request->input('sso_user');

        return response()->json([
            'success' => true,
            'data'    => [
                'id'               => $ssoUser->id,
                'sso_subject'      => $ssoUser->sso_subject,
                'email'            => $ssoUser->email,
                'full_name'        => $ssoUser->full_name,
                'nim'              => $ssoUser->nim,
                'token_type'       => $ssoUser->token_type,
                'local_role'       => [
                    'name'         => $ssoUser->localRole->name,
                    'display_name' => $ssoUser->localRole->display_name,
                ],
                'last_login_at'    => $ssoUser->last_login_at,
                'token_expires_at' => $ssoUser->token_expires_at,
            ],
        ]);
    }
}