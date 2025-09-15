<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DropAllTablesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:drop-all-tables {--force : Bypasses confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drop all tables from the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('force') || $this->confirm('Are you sure you want to drop all tables? This action cannot be undone.')) {
            $tables = DB::select('SHOW TABLES');

            $droplist = [];
            foreach($tables as $table) {
                // Get the first property of the object
                $droplist[] = array_values((array)$table)[0];
            }
            
            if (!empty($droplist)) {
                $droplist = implode(',', $droplist);
                DB::statement('SET FOREIGN_KEY_CHECKS = 0');
                DB::statement("DROP TABLE $droplist");
                DB::statement('SET FOREIGN_KEY_CHECKS = 1');
                $this->info('All tables have been dropped.');
            } else {
                $this->info('No tables to drop.');
            }
        } else {
            $this->info('Action cancelled.');
        }
    }
}
