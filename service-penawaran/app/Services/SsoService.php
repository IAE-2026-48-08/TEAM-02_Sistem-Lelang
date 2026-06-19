<?php

namespace App\Services;

use App\Models\LocalRole;
use App\Models\SsoUser;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SsoService
{
    private string $baseUrl;
    private string $apiKey;
    private string $nim;

    public function __construct()
    {
        $this->baseUrl = env('CENTRAL_SERVER_URL');
        $this->apiKey  = env('CENTRAL_TEAM_API_KEY');
        $this->nim     = env('CENTRAL_TEAM_NIM');
    }

    public function loginM2M(): string
    {
        $response = Http::post("{$this->baseUrl}/api/v1/auth/token", [
            'api_key' => $this->apiKey,
            'nim'     => $this->nim,  
        ]);

        if (! $response->successful()) {
            Log::error('[SSO] M2M login gagal', ['response' => $response->body()]);
            throw new RuntimeException('SSO M2M login gagal: ' . $response->body());
        }

        $token = $response->json('token')
            ?? throw new RuntimeException('Token tidak ditemukan di response SSO');

        $ttl = $response->json('expires_in', 3600);
        Cache::put('iae_m2m_token', $token, $ttl);

        Log::info('[SSO] M2M login berhasil', [
            'app_name' => $response->json('app.name'),
            'team'     => $response->json('app.team'),
        ]);

        return $token;
    }

    public function getM2MToken(): string
    {
        $cached = Cache::get('iae_m2m_token');

        if ($cached) {
            Log::debug('[SSO] Pakai M2M token dari cache');
            return $cached;
        }

        Log::debug('[SSO] Cache kosong, request M2M token baru');
        return $this->loginM2M();
    }

    public function loginUser(string $email, string $password): array
{
    $response = Http::post("{$this->baseUrl}/api/v1/auth/token", [
        'email'    => $email,
        'password' => $password,
    ]);

    if (! $response->successful()) {
        Log::warning('[SSO] User login gagal', ['email' => $email]);
        throw new RuntimeException('Email atau password salah');
    }

    $token = $response->json('token')
        ?? throw new RuntimeException('Token tidak ditemukan di response SSO');

    $payload = $this->decodeAndVerify($token);
    $ssoUser = $this->mapToLocalRole($token, $payload);

    Log::info('[SSO] User login berhasil', [
        'email'      => $email,
        'local_role' => $ssoUser->localRole->name,
    ]);

    return [
        'token'    => $token,
        'sso_user' => $ssoUser,
        'payload'  => $payload,
    ];
}

    public function decodeAndVerify(string $token): array
    {
        $jwks = $this->getPublicKeys();

        try {
            JWT::$leeway = 300;
            $keys        = JWK::parseKeySet($jwks);
            $decoded     = JWT::decode($token, $keys);

            return (array) $decoded;
        } catch (\Exception $e) {
            Log::error('[SSO] JWT verification gagal', ['error' => $e->getMessage()]);
            throw new RuntimeException('Token tidak valid: ' . $e->getMessage());
        }
    }

    public function mapToLocalRole(string $rawToken, array $payload): SsoUser
    {
        $tokenType = $payload['token_type'] ?? 'user';

        if ($tokenType === 'm2m') {
            $subject  = $payload['sub'] ?? null;
            $email    = ($payload['app']['client_id'] ?? 'unknown') . '@m2m.iae.internal';
            $fullName = $payload['app']['name'] ?? null;
            $nim      = $payload['app']['team'] ?? null;
        } else {
            $profile  = (array) ($payload['profile'] ?? []);
            $subject  = $payload['sub'] ?? null;
            $email    = $profile['email']  ?? $subject;
            $fullName = $profile['name']   ?? null;
            $nim      = $profile['nim']    ?? null;
        }

        $expiresAt = isset($payload['exp'])
            ? \Carbon\Carbon::createFromTimestamp($payload['exp'])
            : now()->addHour();

        if (! $subject || ! $email) {
            throw new RuntimeException('Payload JWT tidak lengkap (sub/email kosong)');
        }

        $roleId = $this->resolveLocalRoleId($email, $tokenType, $payload);

        $ssoUser = SsoUser::updateOrCreate(
            ['sso_subject' => $subject],
            [
                'email'            => $email,
                'full_name'        => $fullName,
                'nim'              => $nim,
                'token_type'       => $tokenType,
                'sso_payload'      => $payload,
                'local_role_id'    => $roleId,
                'last_jwt_token'   => $rawToken,
                'token_expires_at' => $expiresAt,
                'last_login_at'    => now(),
            ]
        );

        Log::info('[SSO] User dipetakan ke role lokal', [
            'email'      => $email,
            'role_id'    => $roleId,
            'token_type' => $tokenType,
        ]);

        return $ssoUser->load('localRole');
    }

    private function getPublicKeys(): array
    {
        return Cache::remember('iae_jwks', 3600, function () {
            $response = Http::get("{$this->baseUrl}/api/v1/auth/jwks");

            if (! $response->successful()) {
                throw new RuntimeException('Gagal mengambil JWKS dari server SSO');
            }

            Log::info('[SSO] JWKS berhasil diambil dari server');

            return $response->json();
        });
    }

    private function resolveLocalRoleId(string $email, string $tokenType, array $payload): int
    {
        if ($tokenType === 'm2m') {
            return LocalRole::where('name', 'admin')->value('id');
        }

        return LocalRole::where('name', 'bidder')->value('id');
    }
}