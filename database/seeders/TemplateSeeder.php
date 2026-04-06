<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Default Response',
                'type' => 'response',
                'content' => 'Hello,\n\nThank you for contacting support. We are working on your request and will get back to you shortly.\n\nBest regards,\nSupport Team',
                'metadata' => ['category_id' => null, 'is_public' => true, 'variables' => ['{{name}}', '{{ticket_number}}']],
            ],
            [
                'name' => 'Internal Note',
                'type' => 'internal_comment',
                'content' => 'Internal note: (add your notes here)',
                'metadata' => ['category_id' => null, 'is_public' => false, 'variables' => []],
            ],
        ];

        foreach ($templates as $template) {
            Template::create(array_merge($template, ['created_by' => null]));
        }
    }
}
