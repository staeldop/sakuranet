<?php

namespace Pterodactyl\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Artisan; // Добавлено для очистки кеша
use Pterodactyl\Models\Nest;
use Pterodactyl\Models\Egg;
use Pterodactyl\Services\Eggs\Sharing\EggImporterService;
use Pterodactyl\Services\Nests\NestCreationService;
use Illuminate\Support\Str;

class ImportPelicanEggs extends Command
{
    protected $signature = 'p:import-eggs {--force : Перекачать файлы заново} {--dry : Только скачать, без импорта} {--fix-only : Только починить базу без импорта}';
    protected $description = 'Download Pelican/Pterodactyl eggs and import them directly into Panel database.';

    protected $repos = [
        'pelican-eggs/minecraft' => 'Minecraft',
        'pelican-eggs/games-standalone' => 'Games',
        'pelican-eggs/games-steamcmd' => 'Steam Games',
        'pelican-eggs/database' => 'Databases',
        'pelican-eggs/software' => 'Software',
        'pelican-eggs/voice' => 'Voice',
        'pelican-eggs/chatbots' => 'Bots',
    ];

    protected $ignorePaths = [
        '.github', 'workflows', 'issue_template', 'pull_request_template', 
        'test', 'scripts', 'installers', 'license', 'readme', 'changelog'
    ];

    public function handle(EggImporterService $importerService, NestCreationService $nestCreationService)
    {
        // Если запущен с флагом --fix-only, пропускаем скачивание и сразу чиним базу
        if ($this->option('fix-only')) {
            $this->info("🔧 Запуск режима быстрого исправления (FIX ONLY)...");
            $this->fixDatabaseImages();
            $this->clearCache();
            return;
        }

        $this->info("🚀 ЗАПУСК ИМПОРТА (V7: Name Generator)...");
        
        if (!Storage::exists('imported_eggs') || $this->option('force')) {
            $this->downloadEggs();
        }

        if (!$this->option('dry')) {
            $this->importToDatabase($importerService, $nestCreationService);
            $this->fixDatabaseImages();
            $this->clearCache();
        }

        $this->newLine();
        $this->info("🎉 Работа завершена.");
    }

    protected function downloadEggs()
    {
        if (Storage::exists('imported_eggs')) {
            Storage::deleteDirectory('imported_eggs');
        }
        Storage::makeDirectory('imported_eggs');

        $allFilesToDownload = [];

        foreach ($this->repos as $repo => $category) {
            $this->line("🔍 Сканирование: $repo...");
            try {
                $response = Http::timeout(10)->get("https://api.github.com/repos/$repo/git/trees/main?recursive=1");
                if ($response->failed()) continue;
                
                foreach ($response->json()['tree'] as $node) {
                    if ($this->isValidEgg($node['path'])) {
                        $allFilesToDownload[] = [
                            'path' => $node['path'],
                            'repo' => $repo,
                            'category' => $category,
                            'url' => "https://raw.githubusercontent.com/$repo/main/{$node['path']}"
                        ];
                    }
                }
            } catch (\Exception $e) {}
        }

        $total = count($allFilesToDownload);
        $this->info("📦 К скачиванию: $total шт.");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach (array_chunk($allFilesToDownload, 10) as $chunk) {
            $responses = Http::pool(function (Pool $pool) use ($chunk) {
                $requests = [];
                foreach ($chunk as $item) {
                    $requests[] = $pool->as($item['path'])->get($item['url']);
                }
                return $requests;
            });
            foreach ($responses as $path => $response) {
                $meta = collect($chunk)->firstWhere('path', $path);
                if ($response instanceof Response && $response->ok()) {
                    $this->processAndSaveEgg($response->body(), $meta['path'], $meta['category']);
                }
                $bar->advance();
            }
        }
        $bar->finish();
        $this->newLine();
    }

    protected function importToDatabase(EggImporterService $importer, NestCreationService $nestCreator)
    {
        $files = Storage::allFiles('imported_eggs');
        $jsonFiles = array_filter($files, fn($f) => str_ends_with($f, '.json'));
        if (empty($jsonFiles)) return;

        $this->info("🦅 Импорт " . count($jsonFiles) . " ядер...");
        $bar = $this->output->createProgressBar(count($jsonFiles));
        $bar->start();

        foreach ($jsonFiles as $file) {
            try {
                $parts = explode('/', $file);
                $nestName = $parts[1]; 
                $nest = Nest::where('name', $nestName)->first();
                if (!$nest) {
                    $nest = $nestCreator->handle([
                        'name' => $nestName,
                        'description' => 'Auto-imported',
                        'identifier' => Str::slug($nestName) . '_' . Str::random(4),
                    ], 'admin@example.com');
                }

                $importer->handle(new UploadedFile(Storage::path($file), basename($file), 'application/json', null, true), $nest->id);

            } catch (\Exception $e) {
                // Игнорируем ошибки, лечим в fixDatabaseImages
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
    }

    /**
     * Умное исправление имен образов
     */
    protected function fixDatabaseImages()
    {
        $this->newLine();
        $this->info("🔧 ПОЛИРОВКА: Генерация имен для Docker-образов...");
        
        $eggs = Egg::all();
        $fixedCount = 0;

        foreach ($eggs as $egg) {
            $currentImages = $egg->docker_images;
            $newImages = [];
            $needsFix = false;

            if (empty($currentImages) || !is_array($currentImages)) {
                $newImages = ['ghcr.io/pterodactyl/yolks:java_17' => 'Java 17 (Auto-Fix)'];
                $needsFix = true;
            } else {
                foreach ($currentImages as $key => $value) {
                    // Очистка от мусора
                    if (strlen($key) > 200 || strlen($value) > 200) {
                        $needsFix = true;
                        continue;
                    }

                    // Логика нормализации:
                    // 1. Если ключ - число, значит $value - это URL образа. Имя неизвестно.
                    // 2. Если $value пустой или null, значит имя неизвестно.
                    
                    $imageUrl = is_int($key) ? $value : $key;
                    $imageName = is_int($key) ? null : $value;

                    // Если имя пустое или совпадает с URL (некрасиво), генерируем красивое имя
                    if (empty($imageName) || $imageName === $imageUrl) {
                        // Пример: ghcr.io/parkervcp/yolks:debian -> debian
                        // Пример: quay.io/pterodactyl/core:java -> java
                        $parts = explode(':', $imageUrl);
                        $tag = end($parts); // берем то, что после двоеточия
                        $imageName = ucfirst($tag); // Делаем с большой буквы
                        $needsFix = true;
                    }

                    $newImages[$imageUrl] = $imageName;
                }

                if (empty($newImages)) {
                    $newImages = ['ghcr.io/pterodactyl/yolks:java_17' => 'Java 17 (Auto-Fix)'];
                    $needsFix = true;
                }
            }

            if ($needsFix) {
                $egg->docker_images = $newImages;
                $egg->save();
                $fixedCount++;
            }
        }

        $this->info("✨ Обновлены имена у $fixedCount ядер.");
    }

    protected function clearCache()
    {
        $this->info("🧹 Очистка кеша панели...");
        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        Artisan::call('config:clear');
        $this->info("✅ Кеш сброшен.");
    }

    protected function isValidEgg($path)
    {
        if (!str_ends_with($path, '.json')) return false;
        if (!str_contains(basename($path), 'egg-')) return false;
        foreach ($this->ignorePaths as $ignore) {
            if (stripos($path, $ignore) !== false) return false;
        }
        return true;
    }

    protected function processAndSaveEgg($content, $originalPath, $rootCategory)
    {
        $eggData = json_decode($content, true);
        if (!$eggData || !isset($eggData['name'])) return;

        $eggName = $eggData['name'];

        // Fixes
        if (stripos($eggName, 'Foundry') !== false || stripos($eggName, 'VTT') !== false) {
            $eggData['config']['config_from'] = null; 
            $eggData['config']['config_logs'] = ['custom' => false, 'location' => 'logs/foundry.log'];
        }
        if (empty($eggData['author'])) $eggData['author'] = 'support@pelican.dev';
        $eggData['meta'] = ['version' => 'PTDL_v1', 'update_url' => null];

        if (isset($eggData['variables']) && is_array($eggData['variables'])) {
            foreach ($eggData['variables'] as $key => &$var) {
                if (!isset($var['sort'])) $var['sort'] = $key + 1;
                if (empty($var['rules']) || !is_string($var['rules'])) $var['rules'] = 'nullable|string';
            }
            unset($var);
        }

        // Удаляем docker_image из JSON, чтобы не смущать импортер
        unset($eggData['docker_image']); 

        // Path
        $pathParts = explode('/', $originalPath);
        array_pop($pathParts); array_unshift($pathParts, $rootCategory);
        $cleanParts = array_map(fn($p) => ucfirst(str_replace(['_', '-'], ' ', $p)), $pathParts);
        $newDescription = preg_replace('/\[PATH:.*?\]/', '', $eggData['description'] ?? '');
        $newDescription .= " [PATH: " . implode(' > ', $cleanParts) . "]";
        $eggData['description'] = trim($newDescription);

        Storage::put("imported_eggs/" . implode('/', $cleanParts) . "/" . str_replace(['/', '\\'], '-', $eggData['name']) . '.json', json_encode($eggData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}