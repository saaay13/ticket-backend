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
                'name' => 'Respuesta por Defecto',
                'type' => 'response',
                'content' => "Hola,\n\nGracias por contactar al equipo de soporte. Estamos revisando su solicitud y le responderemos a la brevedad.\n\nSaludos cordiales,\nEquipo de Soporte IT",
                'metadata' => ['category_id' => null, 'is_public' => true, 'variables' => ['{{name}}', '{{ticket_number}}']],
            ],
            [
                'name' => 'Nota Interna',
                'type' => 'internal_comment',
                'content' => 'Nota interna: (agregue sus notas aquí)',
                'metadata' => ['category_id' => null, 'is_public' => false, 'variables' => []],
            ],
        ];

        foreach ($templates as $template) {
            Template::create(array_merge($template, ['created_by' => null]));
        }
    }
}
