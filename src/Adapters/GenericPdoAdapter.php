<?php

declare(strict_types=1);

final class GenericPdoAdapter implements AdapterInterface
{
    private PDO $pdo;
    private array $tables;

    public function __construct(PDO $pdo, array $tables)
    {
        $this->pdo = $pdo;
        $this->tables = $tables;
    }

    public static function fromConfig(PDO $pdo, array $config): self
    {
        return new self($pdo, is_array($config['tables'] ?? null) ? $config['tables'] : []);
    }

    public function name(): string
    {
        return 'generic_pdo';
    }

    public function search(string $message, array $context, string $language, int $limit): array
    {
        $keywords = ChatbotSecurity::keywords($message);
        if (!$keywords || !$this->tables) {
            return [];
        }

        $results = [];
        foreach ($this->tables as $table) {
            $tableName = (string)($table['table'] ?? '');
            $fields = is_array($table['search_fields'] ?? null) ? $table['search_fields'] : [];
            if (!$this->safeIdentifier($tableName) || !$fields) {
                continue;
            }

            $safeFields = array_values(array_filter($fields, [$this, 'safeIdentifier']));
            if (!$safeFields) {
                continue;
            }

            $where = [];
            $params = [];
            foreach ($keywords as $i => $keyword) {
                foreach ($safeFields as $field) {
                    $param = ':q_' . count($params);
                    $where[] = "`{$field}` LIKE {$param}";
                    $params[$param] = '%' . $keyword . '%';
                }
            }

            $labelField = $this->safeIdentifier((string)($table['label_field'] ?? $safeFields[0])) ? (string)($table['label_field'] ?? $safeFields[0]) : $safeFields[0];
            $idField = $this->safeIdentifier((string)($table['id_field'] ?? 'id')) ? (string)($table['id_field'] ?? 'id') : 'id';
            $sql = "SELECT * FROM `{$tableName}` WHERE " . implode(' OR ', $where) . " LIMIT :limit";
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $param => $value) {
                $stmt->bindValue($param, $value, PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
            $stmt->execute();

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                $results[] = [
                    'adapter' => $this->name(),
                    'table' => $tableName,
                    'id' => (string)($row[$idField] ?? ''),
                    'title' => (string)($row[$labelField] ?? $tableName),
                    'summary' => $this->rowSummary($row, $safeFields),
                    'url' => $this->rowUrl($table, $row, $idField),
                    'source' => [
                        'label' => (string)($table['label'] ?? $tableName),
                        'ref' => $tableName . ':' . (string)($row[$idField] ?? ''),
                        'type' => 'database',
                    ],
                ];
            }
        }

        return array_slice($results, 0, $limit);
    }

    public function compare(string $message, array $context, string $language, int $limit): array
    {
        $rows = $this->search($message, $context, $language, max(2, $limit));
        return array_slice($rows, 0, min(4, count($rows)));
    }

    public function report(string $message, array $context, string $language, int $limit): array
    {
        $reportTables = array_values(array_filter($this->tables, static function (array $table): bool {
            return !empty($table['report']);
        }));
        if (!$reportTables) {
            return [];
        }

        $results = [];
        foreach ($reportTables as $table) {
            $tableName = (string)($table['table'] ?? '');
            if (!$this->safeIdentifier($tableName)) {
                continue;
            }
            $stmt = $this->pdo->query("SELECT COUNT(*) AS count_rows FROM `{$tableName}`");
            $count = (int)($stmt ? $stmt->fetchColumn() : 0);
            $results[] = [
                'title' => (string)($table['label'] ?? $tableName),
                'summary' => ($language === 'ar' ? 'عدد السجلات: ' : 'Rows: ') . $count,
                'source' => [
                    'label' => (string)($table['label'] ?? $tableName),
                    'ref' => $tableName,
                    'type' => 'database_report',
                ],
            ];
        }

        return array_slice($results, 0, $limit);
    }

    private function rowSummary(array $row, array $fields): string
    {
        $parts = [];
        foreach (array_slice($fields, 0, 5) as $field) {
            if (isset($row[$field]) && trim((string)$row[$field]) !== '') {
                $parts[] = $field . ': ' . ChatbotSecurity::slice((string)$row[$field], 0, 80);
            }
        }

        return implode(' | ', $parts);
    }

    private function rowUrl(array $table, array $row, string $idField): string
    {
        $pattern = (string)($table['url_pattern'] ?? '');
        if ($pattern === '') {
            return '';
        }

        return str_replace('{id}', rawurlencode((string)($row[$idField] ?? '')), $pattern);
    }

    private function safeIdentifier(string $identifier): bool
    {
        return preg_match('/^[A-Za-z0-9_]+$/', $identifier) === 1;
    }
}
