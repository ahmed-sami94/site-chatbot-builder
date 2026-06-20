<?php

declare(strict_types=1);

final class LocalRulesEngine
{
    private PDO $pdo;
    private array $config;
    /** @var AdapterInterface[] */
    private array $adapters;

    public function __construct(PDO $pdo, array $config, array $adapters)
    {
        $this->pdo = $pdo;
        $this->config = $config;
        $this->adapters = $adapters;
    }

    public function answer(string $message, array $context, string $language): array
    {
        $sessionId = (int)($context['session_id'] ?? 0);
        $limit = max(1, min(25, (int)($this->config['max_results'] ?? 8)));
        $normalized = ChatbotSecurity::normalize($message);

        if ($this->hasAny($normalized, ['حولني', 'مستشار', 'دعم', 'موظف', 'support', 'human', 'agent', 'consultant'])) {
            return ResponseFactory::make($sessionId, $language, $this->handoffText($language), 'handoff', 0.9, [
                'handoff' => true,
            ]);
        }

        if ($this->hasAny($normalized, ['قارن', 'مقارنه', 'مقارنة', 'compare', 'difference'])) {
            return $this->compare($message, $context, $language, $limit);
        }

        if ($this->hasAny($normalized, ['تقرير', 'مبيعات', 'الشهر', 'report', 'sales', 'summary'])) {
            $report = $this->report($message, $context, $language, $limit);
            if ($report !== null) {
                return $report;
            }
        }

        if (preg_match('/(?:فاتوره|فاتورة|invoice)\s*#?\s*(\d+)/u', $normalized, $match)) {
            $context['context_id'] = $match[1];
        }

        $search = $this->search($message, $context, $language, $limit);
        if ($search !== null) {
            return $search;
        }

        return ResponseFactory::fallback($sessionId, $language);
    }

    private function search(string $message, array $context, string $language, int $limit): ?array
    {
        $matches = [];
        foreach ($this->adapters as $adapter) {
            $matches = array_merge($matches, $adapter->search($message, $context, $language, $limit));
        }

        if (!$matches) {
            return null;
        }

        $sources = array_values(array_filter(array_map(static fn(array $row): array => $row['source'] ?? [], $matches)));
        $cards = array_map(static function (array $row): array {
            return [
                'title' => $row['title'] ?? '',
                'subtitle' => $row['summary'] ?? '',
                'url' => $row['url'] ?? '',
                'tone' => 'info',
            ];
        }, $matches);

        $answer = $language === 'ar'
            ? 'وجدت نتائج من المصادر المسموح بها. راجع البطاقات والمصادر بالأسفل، ويمكنني تضييق البحث برقم أو كود أو فترة.'
            : 'I found matches from approved sources. Review the cards and sources below, and I can narrow the search by number, code, or period.';

        return ResponseFactory::make((int)($context['session_id'] ?? 0), $language, $answer, 'local_search', 0.72, [
            'sources' => $sources,
            'cards' => $cards,
        ]);
    }

    private function compare(string $message, array $context, string $language, int $limit): array
    {
        $rows = [];
        foreach ($this->adapters as $adapter) {
            $rows = array_merge($rows, $adapter->compare($message, $context, $language, $limit));
        }

        if (!$rows) {
            return ResponseFactory::fallback((int)($context['session_id'] ?? 0), $language);
        }

        $tableRows = [];
        foreach (array_slice($rows, 0, 4) as $row) {
            $tableRows[] = [
                'source' => $row['source']['label'] ?? $row['table'] ?? '',
                'item' => $row['title'] ?? '',
                'details' => $row['summary'] ?? '',
            ];
        }

        $answer = $language === 'ar'
            ? 'هذه مقارنة مختصرة من البيانات المتاحة. أي حقل غير ظاهر يعني أنه غير متاح في المصدر الحالي.'
            : 'Here is a concise comparison from available data. Missing fields mean the current source does not provide them.';

        return ResponseFactory::make((int)($context['session_id'] ?? 0), $language, $answer, 'compare', 0.78, [
            'table_rows' => $tableRows,
            'sources' => array_values(array_map(static fn(array $row): array => $row['source'] ?? [], $rows)),
        ]);
    }

    private function report(string $message, array $context, string $language, int $limit): ?array
    {
        $rows = [];
        foreach ($this->adapters as $adapter) {
            $rows = array_merge($rows, $adapter->report($message, $context, $language, $limit));
        }

        if (!$rows) {
            return null;
        }

        $answer = $language === 'ar'
            ? 'هذا ملخص سريع من التقارير والجداول المسموح بها.'
            : 'Here is a quick summary from approved reports and tables.';

        return ResponseFactory::make((int)($context['session_id'] ?? 0), $language, $answer, 'report_summary', 0.75, [
            'table_rows' => array_map(static fn(array $row): array => [
                'report' => $row['title'] ?? '',
                'summary' => $row['summary'] ?? '',
            ], $rows),
            'sources' => array_values(array_map(static fn(array $row): array => $row['source'] ?? [], $rows)),
        ]);
    }

    private function handoffText(string $language): string
    {
        return $language === 'ar'
            ? 'يمكن تحويل المحادثة لمسؤول أو مستشار. سأرفق سياق السؤال والنتائج المتاحة بدون مشاركة أسرار أو بيانات خاصة.'
            : 'I can hand this conversation to a human operator with the current context and safe results, without sharing secrets or private data.';
    }

    private function hasAny(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($text, ChatbotSecurity::normalize($needle))) {
                return true;
            }
        }

        return false;
    }
}
