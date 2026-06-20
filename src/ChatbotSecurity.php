<?php

declare(strict_types=1);

final class ChatbotSecurity
{
    public static function detectLanguage(string $message, string $requested = ''): string
    {
        $requested = strtolower(trim($requested));
        if (in_array($requested, ['ar', 'en'], true)) {
            return $requested;
        }

        return preg_match('/[\x{0600}-\x{06FF}]/u', $message) === 1 ? 'ar' : 'en';
    }

    public static function normalize(string $value): string
    {
        $value = strtr($value, [
            'أ' => 'ا',
            'إ' => 'ا',
            'آ' => 'ا',
            'ى' => 'ي',
            'ة' => 'ه',
            'ؤ' => 'و',
            'ئ' => 'ي',
            '٠' => '0',
            '١' => '1',
            '٢' => '2',
            '٣' => '3',
            '٤' => '4',
            '٥' => '5',
            '٦' => '6',
            '٧' => '7',
            '٨' => '8',
            '٩' => '9',
        ]);
        $value = self::lower($value);
        $value = preg_replace('/[^\p{L}\p{N}\s\.\-\/]/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    public static function keywords(string $message): array
    {
        $normalized = self::normalize($message);
        $parts = preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $stop = [
            'في', 'من', 'عن', 'على', 'الي', 'إلى', 'ما', 'هو', 'هي', 'ده', 'دي', 'هذا', 'هذه',
            'the', 'a', 'an', 'of', 'to', 'for', 'in', 'on', 'is', 'are', 'show', 'me',
        ];
        $out = [];
        foreach ($parts as $part) {
            if (self::length($part) < 2 || in_array($part, $stop, true)) {
                continue;
            }
            $out[] = $part;
        }

        return array_values(array_unique($out));
    }

    public static function looksLikeWriteAction(string $message): bool
    {
        $normalized = self::normalize($message);
        $writeWords = [
            'احذف', 'امسح', 'عدل', 'غير', 'اضف', 'أضف', 'ارسل', 'ابعت', 'اعتمد', 'سدد', 'اقفل', 'صدر',
            'delete', 'remove', 'update', 'change', 'insert', 'send', 'email', 'export', 'approve', 'pay',
        ];
        foreach ($writeWords as $word) {
            if (str_contains($normalized, self::normalize($word))) {
                return true;
            }
        }

        return false;
    }

    public static function looksLikeSecretRequest(string $message): bool
    {
        $normalized = self::normalize($message);
        $secretWords = [
            'password', 'secret', 'token', 'api key', 'credential', 'private chat', 'admin data',
            'كلمه السر', 'كلمة السر', 'باسورد', 'توكن', 'سر', 'محادثات خاصه', 'بيانات الادمن',
        ];
        foreach ($secretWords as $word) {
            if (str_contains($normalized, self::normalize($word))) {
                return true;
            }
        }

        return false;
    }

    public static function lower(string $value): string
    {
        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    }

    public static function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    public static function slice(string $value, int $start, int $length): string
    {
        return function_exists('mb_substr') ? mb_substr($value, $start, $length, 'UTF-8') : substr($value, $start, $length);
    }
}
