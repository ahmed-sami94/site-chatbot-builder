<?php

declare(strict_types=1);

final class ResponseFactory
{
    public static function make(int $sessionId, string $language, string $answer, string $tool, float $confidence = 0.6, array $extra = []): array
    {
        return array_merge([
            'success' => true,
            'session_id' => $sessionId,
            'answer' => $answer,
            'tool' => $tool,
            'confidence' => $confidence,
            'sources' => [],
            'links' => [],
            'cards' => [],
            'table_rows' => [],
            'calculations' => [],
            'suggestions' => self::suggestions($language),
            'handoff' => false,
        ], $extra);
    }

    public static function empty(int $sessionId, string $language): array
    {
        $answer = $language === 'ar'
            ? 'اكتب سؤالك عن الموقع أو النظام أو التقارير أو المنتجات، وسأبحث في المصادر المسموح بها.'
            : 'Type your question about the website, system, reports, or products, and I will search approved sources.';

        return self::make($sessionId, $language, $answer, 'empty', 0.2);
    }

    public static function blocked(int $sessionId, string $language, string $reason): array
    {
        $answer = $language === 'ar'
            ? 'لا يمكنني تنفيذ هذا الطلب من الدردشة. أستطيع البحث والتحليل والتقارير فقط، وأي إجراء تعديل أو إرسال يحتاج تأكيد وصلاحيات داخل النظام.'
            : 'I cannot perform that action from chat. I can search, analyze, and report only; any write or send action needs system permissions and confirmation.';

        if ($reason === 'secrets') {
            $answer = $language === 'ar'
                ? 'لا يمكنني عرض كلمات مرور أو أسرار أو بيانات خاصة. يمكنني فقط استخدام المصادر المسموح بها حسب الصلاحيات.'
                : 'I cannot reveal passwords, secrets, or private data. I can only use approved sources within the current permissions.';
        }

        if ($reason === 'history_disabled') {
            $answer = $language === 'ar'
                ? 'سجل المحادثة غير متاح من الواجهة العامة. فعله فقط من خلال جلسات آمنة أو نظام دخول داخل التطبيق.'
                : 'Chat history is not exposed from the public API. Enable it only behind a safe session or authenticated app wrapper.';
        }

        return self::make($sessionId, $language, $answer, 'blocked_' . $reason, 0.95, [
            'handoff' => true,
            'suggestions' => self::suggestions($language),
        ]);
    }

    public static function fallback(int $sessionId, string $language): array
    {
        $answer = $language === 'ar'
            ? 'لم أجد إجابة مؤكدة في المصادر المتاحة. يمكنني تضييق البحث إذا ذكرت رقم، كود، فترة، تقرير، أو اسم منتج.'
            : 'I could not find a confirmed answer in the available sources. Try adding a number, code, period, report, or product name.';

        return self::make($sessionId, $language, $answer, 'clarify', 0.25, ['handoff' => true]);
    }

    public static function suggestions(string $language): array
    {
        if ($language === 'ar') {
            return [
                'اعرض تقرير المبيعات الشهر ده',
                'قارن بين المنتجين دول',
                'فين بيانات الفاتورة رقم 123؟',
                'حولني لمستشار',
            ];
        }

        return [
            'Show this month sales report',
            'Compare these products',
            'Find invoice 123',
            'Connect me with support',
        ];
    }
}
