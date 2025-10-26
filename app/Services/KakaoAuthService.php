<?php

namespace App\Services;

use App\Core\SessionManager;

class KakaoAuthService
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private SessionManager $session;

    public function __construct(SessionManager $session)
    {
        $this->clientId = KAKAO_CLIENT_ID;
        $this->clientSecret = KAKAO_CLIENT_SECRET;
        $this->redirectUri = KAKAO_REDIRECT_URI;
        $this->session = $session;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getAuthorizationUrl(): string
    {
        $state = bin2hex(random_bytes(16));
        $this->session->set('oauth2state', $state);

        $params = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'state' => $state,
        ];
        return 'https://kauth.kakao.com/oauth/authorize?' . http_build_query($params);
    }

    /**
     * @param string $code
     * @return array
     * @throws \Exception
     */
    public function getAccessToken(string $code): array
    {
        $params = [
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'code' => $code,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://kauth.kakao.com/oauth/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $token = json_decode($response, true);

        if (isset($token['error'])) {
            throw new \Exception('액세스 토큰을 가져오는 데 실패했습니다: ' . $token['error_description']);
        }

        return $token;
    }

    /**
     * @param string $accessToken
     * @return array
     * @throws \Exception
     */
    public function getUserProfile(string $accessToken): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://kapi.kakao.com/v2/user/me');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $userInfo = json_decode($response, true);

        if (isset($userInfo['code'])) {
            throw new \Exception('사용자 프로필을 가져오는 데 실패했습니다: ' . $userInfo['msg']);
        }

        return $userInfo;
    }
}
