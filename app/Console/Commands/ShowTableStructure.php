<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ShowTableStructure extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'table:show {table}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Показать структуру таблицы';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $table = $this->argument('table');
        
        $columns = DB::select('DESCRIBE ' . $table);
        
        $this->info("=== СТРУКТУРА ТАБЛИЦЫ {$table} ===");
        
        $headers = ['Field', 'Type', 'Null', 'Key', 'Default', 'Extra'];
        $rows = [];
        
        foreach ($columns as $column) {
            $rows[] = [
                $column->Field,
                $column->Type,
                $column->Null,
                $column->Key ?? '',
                $column->Default ?? '',
                $column->Extra ?? '',
            ];
        }
        
        $this->table($headers, $rows);
        
        return 0;
    }
}
