<?php

declare(strict_types=1);

namespace App\Services\Admin\Csv;

class ImportResult
{
    /**
     * @param  array<int, array{line: int, message: string}>  $errors
     */
    private function __construct(
        public readonly int $created,
        public readonly int $updated,
        public readonly array $errors,
    ) {}

    public static function success(int $created, int $updated): self
    {
        return new self($created, $updated, []);
    }

    /**
     * @param  array<int, array{line: int, message: string}>  $errors
     */
    public static function failed(array $errors): self
    {
        return new self(0, 0, $errors);
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    /**
     * @return array{created: int, updated: int, errors: array<int, array{line: int, message: string}>}
     */
    public function toArray(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'errors' => $this->errors,
        ];
    }
}
