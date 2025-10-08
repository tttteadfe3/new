<?php
// app/Services/KakaoAuth.php
namespace App\Services;

use Exception;

class KakaoAuth {
    private string $clientId;
    private string $redirectUri;

    public function __construct() {
        $this->clientId = KAKAO_CLIENT_ID;
        $this->redirectUri = KAKAO_REDIRECT_URI;
    }

    public function getAuthorizationUrl(): string {
        $url = "https://kauth.kakao.com/oauth/authorize?response_type=code";
        $url .= "&client_id=" . $this->clientId;
        $url .= "&redirect_uri=" . $this->redirectUri;
        return $url;
    }

    public function getTokens(string $code): array {
        $url = "https://kauth.kakao.com/oauth/token";
        $data = http_build_query([
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'code' => $code,
        ]);
        return $this->apiRequest($url, $data);
    }

    public function getUserProfile(string $accessToken): array {
        $url = "https://kapi.kakao.com/v2/user/me";
        $headers = ["Authorization: Bearer " . $accessToken];
        return $this->apiRequest($url, null, $headers);
    }

    private function apiRequest(string $url, ?string $postData = null, array $headers = []): array {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        if ($postData !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            throw new Exception("cURL Error: " . $error);
        }
        
        $result = json_decode($response, true);
        
        if ($httpCode >= 400 || isset($result['error']) || (isset($result['code']) && $result['code'] < 0)) {
            $error_desc = $result['error_description'] ?? $result['msg'] ?? 'Unknown Kakao API error';
            throw new Exception("Kakao API Error (HTTP {$httpCode}): " . $error_desc);
        }

        return $result;
    }
}