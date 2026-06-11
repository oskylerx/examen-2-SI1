<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // RolSeeder::class,
            CarreraSeeder::class,
            // UsuariosPruebaSeeder::class,
            AdminSeeder::class,
            PostulanteSeeder::class,
            CoordinadorDocentesSeeder::class,
        ]);
    }
}