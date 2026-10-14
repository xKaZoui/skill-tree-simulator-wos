<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->insert('skill_trees', 'skill_trees');
        foreach (['warrior', 'mage', 'archer'] as $class) {
            $this->insert('skills', "skills_{$class}");
        }
        $this->insert('skill_tree_connections', 'skill_tree_connections');
        $this->insert('change_log_sources', 'change_log_sources');
        $this->insert('change_logs', 'change_logs');
    }

    private function insert(string $table, string $file): void
    {
        $rows = require database_path("data/{$file}.php");

        $rows = array_map(fn (array $row) => array_map(
            fn ($value) => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : $value,
            $row,
        ), $rows);

        foreach (array_chunk($rows, 50) as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }
}
