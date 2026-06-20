<?php

declare(strict_types=1);

interface AdapterInterface
{
    public function name(): string;

    public function search(string $message, array $context, string $language, int $limit): array;

    public function compare(string $message, array $context, string $language, int $limit): array;

    public function report(string $message, array $context, string $language, int $limit): array;
}
