<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PrepareMysqlTestDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prepare-test-db';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a test database if not exists in MySQL';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $databaseName = 'news_aggregation_test';

        $this->info("Creating database {$databaseName} if not exists");

        // check if the database exists
        $databaseExists = DB::select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$databaseName}'");

        if (!empty($databaseExists)) {
            $this->info("Database {$databaseName} already exists");
            return;
        }

        // create the database
        DB::statement("CREATE DATABASE {$databaseName}");

        $this->info("Database {$databaseName} created successfully");
    }
}
