<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectFile extends Model
{
    protected $guarded = [];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function extension(): string
    {
        return strtolower(pathinfo($this->path, PATHINFO_EXTENSION));
    }

    public static function iconFor(string $path): array
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($ext) {
            'ts', 'tsx' => ['TS', '#6EA8F7'],
            'js', 'jsx', 'mjs' => ['JS', '#E8C27D'],
            'php' => ['PHP', '#A99BFF'],
            'json' => ['{}', '#E8C27D'],
            'css', 'scss' => ['#', '#5CC8E0'],
            'html', 'htm' => ['<>', '#E0572F'],
            'md' => ['MD', '#6E6E79'],
            'yml', 'yaml' => ['YML', '#E5695E'],
            'py' => ['PY', '#58C98A'],
            default => str_starts_with(basename($path), '.') ? ['≡', '#E8B66B'] : ['≡', '#8A8A94'],
        };
    }
}
