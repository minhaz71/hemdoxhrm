<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class ErrorLogService
{
    public function files(): Collection
    {
        return collect(File::glob(storage_path('logs/*.log')) ?: [])
            ->map(fn (string $path) => [
                'name' => basename($path),
                'path' => $path,
                'size' => File::size($path),
                'modified_at' => File::lastModified($path),
            ])
            ->sortByDesc('modified_at')
            ->values();
    }

    public function entries(string $file = 'laravel.log', ?string $level = null, int $limit = 100): Collection
    {
        $path = $this->safePath($file);
        if (! $path || ! File::exists($path)) {
            return collect();
        }

        $lines = collect(array_reverse(file($path, FILE_IGNORE_NEW_LINES) ?: []));

        return $lines
            ->filter(fn (string $line) => str_contains($line, 'local.'))
            ->when($level, fn (Collection $items) => $items->filter(fn (string $line) => str_contains($line, ".{$level}:")))
            ->take($limit)
            ->map(fn (string $line) => $this->parseLine($line))
            ->values();
    }

    public function clear(string $file): void
    {
        $path = $this->safePath($file);
        if ($path && File::exists($path)) {
            File::put($path, '');
        }
    }

    private function safePath(string $file): ?string
    {
        $name = basename($file);
        if (! str_ends_with($name, '.log')) {
            return null;
        }

        return storage_path("logs/{$name}");
    }

    private function parseLine(string $line): array
    {
        preg_match('/^\[(?<date>[^\]]+)\]\s+(?<env>[^.]+)\.(?<level>[A-Z]+):\s+(?<message>.*)$/', $line, $matches);

        return [
            'date' => $matches['date'] ?? null,
            'level' => strtolower($matches['level'] ?? 'info'),
            'message' => $matches['message'] ?? $line,
            'raw' => $line,
        ];
    }
}
