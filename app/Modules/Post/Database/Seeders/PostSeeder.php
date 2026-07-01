<?php

namespace Modules\Post\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Post\Models\Post;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        Post::create(['name' => 'Sample Post']);

        // Uncomment to use factory if available
        // Post::factory()->count(10)->create();
    }
}
