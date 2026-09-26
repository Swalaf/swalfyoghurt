<?php

namespace App\Http\Controllers\Studio;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Services\Studio\ProjectAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use ZipArchive;

class WorkspaceController extends StudioController
{
    public function show(Request $request, Project $project)
    {
        $this->authorizeProject($request, $project);
        $files = $project->files()->get();
        $path = $request->query('file') ?: ($files->firstWhere('path', 'index.html')?->path ?? $files->first()?->path);
        $current = $files->firstWhere('path', $path);

        // Remember recently opened files as editor tabs.
        $tabs = collect($request->session()->get("tabs.{$project->id}", []))->prepend($path)->filter()->unique()
            ->filter(fn ($p) => $files->contains('path', $p))->take(6)->values();
        $request->session()->put("tabs.{$project->id}", $tabs->all());

        return view('studio.workspace', [
            'project' => $project,
            'files' => $files,
            'tree' => $this->tree($files->pluck('path')->all()),
            'current' => $current,
            'tabs' => $tabs,
            'problems' => $this->problems($files),
            'history' => $project->changeSets()->with('files')->take(15)->get(),
            'state' => app(BuilderController::class)->payload($project, 'workspace'),
            'logs' => $project->agentTasks()->where('run', (int) $project->agentTasks()->max('run'))->orderBy('id')->get(),
        ]);
    }

    public function save(Request $request, Project $project, ProjectAgent $agent)
    {
        $this->authorizeProject($request, $project);
        $data = $request->validate(['path' => 'required|string|max:200', 'content' => 'nullable|string|max:2000000', 'original' => 'nullable|string|max:200']);
        $path = $agent->safePath($data['path']);
        if (! $path) {
            return response()->json(['message' => 'That file name isn’t allowed.'], 422);
        }
        if (! empty($data['original']) && $data['original'] !== $path) {
            $project->files()->where('path', $data['original'])->update(['path' => $path]);
        }
        $project->files()->updateOrCreate(['path' => $path], ['content' => $data['content'] ?? '']);
        $project->touch();

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'path' => $path, 'url' => route('studio.projects.code', [$project, 'file' => $path])])
            : redirect()->route('studio.projects.code', [$project, 'file' => $path]);
    }

    public function delete(Request $request, Project $project)
    {
        $this->authorizeProject($request, $project);
        $project->files()->where('path', $request->input('path'))->delete();

        return redirect()->route('studio.projects.code', $project);
    }

    public function download(Request $request, Project $project)
    {
        $this->authorizeProject($request, $project);
        $tmp = tempnam(sys_get_temp_dir(), 'zip');
        $zip = new ZipArchive;
        $zip->open($tmp, ZipArchive::OVERWRITE);
        foreach ($project->files()->get() as $f) {
            $zip->addFromString($project->slug.'/'.$f->path, $f->content);
        }
        $zip->close();

        return response()->download($tmp, $project->slug.'.zip')->deleteFileAfterSend();
    }

    /**
     * Flatten paths into a tree listing with folders first.
     *
     * @return array<int, array{name: string, path: string, depth: int, dir: bool}>
     */
    protected function tree(array $paths): array
    {
        $nodes = [];
        foreach ($paths as $path) {
            $parts = explode('/', $path);
            $acc = '';
            foreach ($parts as $i => $part) {
                $acc = ltrim($acc.'/'.$part, '/');
                $nodes[$acc] ??= ['name' => $part, 'path' => $acc, 'depth' => $i, 'dir' => $i < count($parts) - 1];
            }
        }
        uksort($nodes, function ($a, $b) use ($nodes) {
            $pa = explode('/', $a);
            $pb = explode('/', $b);
            for ($i = 0; $i < min(count($pa), count($pb)); $i++) {
                if ($pa[$i] === $pb[$i]) {
                    continue;
                }
                $aDir = $i < count($pa) - 1 || $nodes[$a]['dir'];
                $bDir = $i < count($pb) - 1 || $nodes[$b]['dir'];

                return $aDir !== $bDir ? ($aDir ? -1 : 1) : strcasecmp($pa[$i], $pb[$i]);
            }

            return count($pa) <=> count($pb);
        });

        return array_values($nodes);
    }

    /** @return array<int, array{kind: string, msg: string, loc: string}> */
    protected function problems($files): array
    {
        $out = [];
        foreach ($files as $f) {
            /** @var ProjectFile $f */
            $ext = $f->extension();
            if ($ext === 'json' && trim($f->content) !== '' && json_decode($f->content) === null) {
                $out[] = ['kind' => 'ERROR', 'msg' => 'Invalid JSON: '.json_last_error_msg(), 'loc' => $f->path];
            }
            if (in_array($ext, ['html', 'htm'], true)) {
                if (! preg_match('/<title>/i', $f->content)) {
                    $out[] = ['kind' => 'WARN', 'msg' => 'Page has no <title>', 'loc' => $f->path];
                }
                preg_match_all('/(?:src|href)="([^"#:?]+)"/i', $f->content, $m);
                foreach ($m[1] as $ref) {
                    $target = ltrim((str_contains($f->path, '/') ? dirname($f->path).'/' : '').$ref, './');
                    if (! Str::endsWith($ref, '/') && ! $files->contains('path', $target)) {
                        $out[] = ['kind' => 'ERROR', 'msg' => "Missing file referenced: {$ref}", 'loc' => $f->path];
                    }
                }
            }
            if ($ext === 'js' && substr_count($f->content, '{') !== substr_count($f->content, '}')) {
                $out[] = ['kind' => 'WARN', 'msg' => 'Unbalanced braces — possible syntax error', 'loc' => $f->path];
            }
        }

        return $out;
    }
}
