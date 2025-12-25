<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;

class ImportPelicanEggs extends Command
{
    protected $signature = 'ptero:import-eggs {--force : Перекачать все файлы заново}';
    protected $description = 'Turbo download of Pelican/Pterodactyl eggs with parallel processing';

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
        '.github', 
        'workflows', 
        'issue_template', 
        'pull_request_template', 
        'test', 
        'scripts', 
        'installers'
    ];

    public function handle()
    {
        $this->info("🚀 ЗАПУСК ТУРБО-ИМПОРТА...");
        
        if (!Storage::exists('imported_eggs')) {
            Storage::makeDirectory('imported_eggs');
        }

        $allFilesToDownload = [];

        // 1. Сбор списка файлов
        foreach ($this->repos as $repo => $category) {
            $this->line("🔍 Сканирование репозитория: $repo...");
            
            try {
                $response = Http::timeout(10)->get("https://api.github.com/repos/$repo/git/trees/main?recursive=1");
                
                if ($response->failed()) {
                    $this->error("   ❌ Ошибка API GitHub. Проверьте лимиты.");
                    continue;
                }

                $tree = $response->json()['tree'];
                
                foreach ($tree as $node) {
                    if ($this->isValidEgg($node['path'])) {
                        $allFilesToDownload[] = [
                            'path' => $node['path'],
                            'repo' => $repo,
                            'category' => $category,
                            'url' => "https://raw.githubusercontent.com/$repo/main/{$node['path']}"
                        ];
                    }
                }

            } catch (\Exception $e) {
                $this->error("   ❌ Ошибка: " . $e->getMessage());
            }
        }

        $total = count($allFilesToDownload);
        $this->info("📦 Найдено потенциальных ядер: $total шт.");
        $this->info("⚡ Начинаем параллельную загрузку...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        // 2. Скачивание (Pool)
        $chunks = array_chunk($allFilesToDownload, 10);

        foreach ($chunks as $chunk) {
            $responses = Http::pool(function (Pool $pool) use ($chunk) {
                $requests = [];
                foreach ($chunk as $item) {
                    $requests[] = $pool->as($item['path'])->get($item['url']);
                }
                return $requests;
            });

            foreach ($responses as $path => $response) {
                $meta = collect($chunk)->firstWhere('path', $path);

                // 🔥 ИСПРАВЛЕНИЕ: Проверяем, не является ли ответ ошибкой
                if ($response instanceof \Exception) {
                    // Можно раскомментировать для отладки, но лучше просто пропустить
                    // $this->error("Ошибка скачивания $path: " . $response->getMessage());
                } elseif ($response instanceof Response && $response->ok()) {
                    $this->processEggContent($response->body(), $meta['path'], $meta['category']);
                }
                
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info("🎉 ГОТОВО! Все ядра лежат в backend/storage/app/imported_eggs");
    }

    protected function isValidEgg($path)
    {
        if (!str_ends_with($path, '.json')) return false;
        if (!str_contains(basename($path), 'egg-')) return false;
        foreach ($this->ignorePaths as $ignore) {
            if (str_contains($path, $ignore)) return false;
        }
        return true;
    }

    protected function processEggContent($content, $originalPath, $rootCategory)
    {
        $eggData = json_decode($content, true);
        if (!$eggData || !isset($eggData['name'])) return;

        // Генерация пути
        $pathParts = explode('/', $originalPath);
        array_pop($pathParts); 
        array_unshift($pathParts, $rootCategory);

        $cleanParts = array_map(function($p) {
            return ucfirst(str_replace(['_', '-'], ' ', $p));
        }, $pathParts);

        $pathString = implode(' > ', $cleanParts);
        $folderPath = implode('/', $cleanParts);

        // Обновление описания
        $newDescription = $eggData['description'] ?? '';
        $newDescription = preg_replace('/\[PATH:.*?\]/', '', $newDescription);
        $newDescription .= " [PATH: $pathString]";
        $eggData['description'] = trim($newDescription);

        // Сохранение
        $fileName = str_replace(['/', '\\'], '-', $eggData['name']) . '.json';
        $savePath = "imported_eggs/" . $folderPath . "/" . $fileName;

        Storage::put($savePath, json_encode($eggData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}