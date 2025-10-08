<?php

namespace App\Models;

use App\Core\DB;
use PDO;

class User
{
    /**
     * Find a user by their Kakao ID or create a new one if they don't exist.
     */
    public static function findOrCreateFromKakao(array $kakaoUser): array
    {
        $pdo = DB::getInstance();

        // Check if user exists
        $stmt = $pdo->prepare("SELECT * FROM sys_users WHERE kakao_id = ?");
        $stmt->execute([$kakaoUser['id']]);
        $user = $stmt->fetch();

        if ($user) {
            // User exists, return their data
            return $user;
        }

        // User does not exist, create a new one
        $stmt = $pdo->prepare(
            "INSERT INTO sys_users (kakao_id, nickname, email, status)
             VALUES (?, ?, ?, 'pending')"
        );
        $stmt->execute([
            $kakaoUser['id'],
            $kakaoUser['properties']['nickname'] ?? null,
            $kakaoUser['kakao_account']['email'] ?? null,
        ]);

        // Return the newly created user
        $id = $pdo->lastInsertId();
        $stmt = $pdo->prepare("SELECT * FROM sys_users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}