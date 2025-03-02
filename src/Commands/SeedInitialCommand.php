<?php

namespace Vnideas\Initial\Commands;

use Illuminate\Console\Command;
use Database\Seeders\PackageSeeder;

class SeedInitialCommand extends Command
{
    protected $signature = 'initial:seed';
    protected $description = 'Seed initial package data';

    public function handle(): void
    {
        $this->call(PackageSeeder::class);
        $this->info('Initial package data seeded successfully.');
    }
}