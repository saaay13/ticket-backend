<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        
        // Solo personal de Soporte Técnico (ID 1) con roles Admin o Agent
        $technicians = User::where('department_id', 1)
            ->whereIn('role', ['Admin', 'Agent'])
            ->get();
            
        $agent1 = User::where('email', 'tecnico1@cosabe.edu.bo')->first();
        
        if ($users->isEmpty()) {
            return;
        }

        $tickets = [
            [
                'title' => 'La laptop no enciende después de la actualización de Windows',
                'requester' => $users->random(),
                'assigned_to' => $agent1 ?? ($technicians->random() ?? null),
                'category' => 'Hardware',
                'status' => 'in_progress',
                'priority' => 'critical',
                'description' => 'Después de la última actualización de Windows 11 (KB5034441), mi laptop se queda trabada en la pantalla de inicio y muestra un error de pantalla azul.',
                'tags' => ['windows', 'bsod', 'urgente'],
            ],
            [
                'title' => 'Acceso VPN no funciona desde casa',
                'requester' => $users->random(),
                'assigned_to' => $technicians->random() ?? null,
                'category' => 'Redes',
                'status' => 'open',
                'priority' => 'high',
                'description' => 'No puedo conectarme a la VPN corporativa desde mi red doméstica. Sigo recibiendo el error de autenticación 800.',
                'tags' => ['vpn', 'acceso-remoto'],
            ],
            [
                'title' => 'Solicitud de acceso al sistema de RRHH',
                'requester' => $users->random(),
                'assigned_to' => $technicians->random() ?? null,
                'category' => 'Gestión de Accesos',
                'status' => 'pending',
                'priority' => 'medium',
                'description' => 'Necesito acceso de solo lectura al módulo de SAP RRHH para generar informes para la revisión trimestral.',
                'tags' => ['sap', 'acceso', 'rrhh'],
            ],
            [
                'title' => 'Los correos de Outlook no se sincronizan en el móvil',
                'requester' => $users->random(),
                'assigned_to' => $technicians->random() ?? null,
                'category' => 'Correo Electrónico',
                'status' => 'in_progress',
                'priority' => 'medium',
                'description' => 'Mi aplicación de Outlook en el iPhone no sincroniza los correos nuevos. Ha estado así durante 3 días.',
                'tags' => ['outlook', 'mobil', 'email'],
            ],
            [
                'title' => 'Correo electrónico sospechoso con enlace malicioso recibido',
                'requester' => $users->random(),
                'assigned_to' => $agent1 ?? ($technicians->random() ?? null),
                'category' => 'Seguridad',
                'status' => 'in_progress',
                'priority' => 'critical',
                'description' => 'Recibí un correo que parece ser de nuestro CEO pidiéndome que haga clic en un enlace y verifique mis credenciales. El dominio del correo parece sospechoso.',
                'tags' => ['phishing', 'seguridad', 'urgente'],
            ],
            [
                'title' => 'Impresora del 3er piso no imprime',
                'requester' => $users->random(),
                'assigned_to' => $technicians->random() ?? null,
                'category' => 'Hardware',
                'status' => 'resolved',
                'priority' => 'low',
                'description' => 'La impresora compartida HP LaserJet M404dn en el 3er piso dejó de funcionar esta mañana.',
                'tags' => ['impresora', 'hardware'],
            ],
            [
                'title' => 'Microsoft Teams se cierra al iniciar',
                'requester' => $users->random(),
                'assigned_to' => null,
                'category' => 'Software',
                'status' => 'open',
                'priority' => 'high',
                'description' => 'Microsoft Teams se cierra inmediatamente después de abrirlo. Veo una pantalla de carga y luego se cierra solo.',
                'tags' => ['teams', 'microsoft', 'error'],
            ],
            [
                'title' => 'Configuración de estación de trabajo para nuevo empleado',
                'requester' => $users->random(),
                'assigned_to' => $agent1 ?? ($technicians->random() ?? null),
                'category' => 'Gestión de Accesos',
                'status' => 'pending',
                'priority' => 'medium',
                'description' => 'Tenemos un nuevo empleado que comienza el 10 de marzo. Por favor, prepare una estación de trabajo con el software estándar.',
                'tags' => ['onboarding', 'configuracion', 'nuevo-empleado'],
            ],
        ];

        foreach ($tickets as $ticketData) {
            $category = Category::where('name', $ticketData['category'])->first();
            if (!$category) continue;

            Ticket::create([
                'title' => $ticketData['title'],
                'requester_id' => $ticketData['requester']->id,
                'assigned_to_id' => $ticketData['assigned_to']?->id,
                'category_id' => $category->id,
                'status' => $ticketData['status'],
                'details' => [
                    'description' => $ticketData['description'],
                    'priority' => $ticketData['priority'],
                    'tags' => $ticketData['tags'],
                    'department' => $ticketData['requester']->department?->name ?? 'General',
                ],
            ]);
        }
    }
}
