<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'Writing',
            'Coding',
            'Marketing',
            'Research',
            'Productivity',
            'Education',
            'Design',
            'Analysis',
            'Brainstorming',
            'Email',
            'SQL',
            'Debugging',
            'Refactoring',
            'Documentation',
            'Testing',
        ];

        foreach ($names as $name) {
            Tag::firstOrCreate(['name' => $name]);
        }
    }
}
