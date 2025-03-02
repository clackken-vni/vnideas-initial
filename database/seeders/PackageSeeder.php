<?php

namespace Vnideas\Initial\Database\Seeders;

use Illuminate\Database\Seeder;
use Vnideas\Initial\Models\VniPackages;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        VniPackages::create([
            'name' => 'Vnideas Roles Management',
            'description' => 'Package for roles management',
            'install_name' => 'vnideas/roles-management',
            'version' => '1.0.0',
            'activated' => false,
            'installed' => false,
        ]);

        VniPackages::create([
            'name' => 'Vnideas Logs Management',
            'description' => 'Package for logs management',
            'install_name' => 'vnideas/logs-management',
            'version' => '1.0.0',
            'activated' => false,
            'installed' => false,
        ]);
    }
}