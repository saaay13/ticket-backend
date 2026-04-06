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
                'title' => 'Laptop won\'t turn on after Windows update',
                'requester' => $users->random(),
                'assigned_to' => $agent1 ?? ($technicians->random() ?? null),
                'category' => 'Hardware',
                'status' => 'in_progress',
                'priority' => 'critical',
                'description' => 'After the latest Windows 11 update (KB5034441), my laptop gets stuck on the boot screen and shows a blue screen error.',
                'tags' => ['windows', 'bsod', 'urgent'],
            ],
            [
                'title' => 'VPN access not working from home',
                'requester' => $users->random(),
                'assigned_to' => $technicians->random() ?? null,
                'category' => 'Network',
                'status' => 'open',
                'priority' => 'high',
                'description' => 'I am unable to connect to the corporate VPN from my home network. I keep getting authentication error 800.',
                'tags' => ['vpn', 'remote-access'],
            ],
            [
                'title' => 'Request access to HR system',
                'requester' => $users->random(),
                'assigned_to' => $technicians->random() ?? null,
                'category' => 'Access Request',
                'status' => 'pending',
                'priority' => 'medium',
                'description' => 'I need read-only access to the SAP HR module to generate reports for the quarterly review.',
                'tags' => ['sap', 'access', 'hr'],
            ],
            [
                'title' => 'Outlook emails not syncing on mobile',
                'requester' => $users->random(),
                'assigned_to' => $technicians->random() ?? null,
                'category' => 'Email',
                'status' => 'in_progress',
                'priority' => 'medium',
                'description' => 'My Outlook app on iPhone is not syncing new emails. It has been like this for 3 days.',
                'tags' => ['outlook', 'mobile', 'email'],
            ],
            [
                'title' => 'Suspicious email with malicious link received',
                'requester' => $users->random(),
                'assigned_to' => $agent1 ?? ($technicians->random() ?? null),
                'category' => 'Security',
                'status' => 'in_progress',
                'priority' => 'critical',
                'description' => 'I received an email appearing to be from our CEO asking me to click a link and verify my credentials. The email domain looks suspicious.',
                'tags' => ['phishing', 'security', 'urgent'],
            ],
            [
                'title' => 'Printer on 3rd floor not printing',
                'requester' => $users->random(),
                'assigned_to' => $technicians->random() ?? null,
                'category' => 'Hardware',
                'status' => 'resolved',
                'priority' => 'low',
                'description' => 'The shared printer HP LaserJet M404dn on the 3rd floor stopped working this morning.',
                'tags' => ['printer', 'hardware'],
            ],
            [
                'title' => 'Microsoft Teams crashing on startup',
                'requester' => $users->random(),
                'assigned_to' => null,
                'category' => 'Software',
                'status' => 'open',
                'priority' => 'high',
                'description' => 'Microsoft Teams crashes immediately after launching. I see a loading screen and then it closes.',
                'tags' => ['teams', 'microsoft', 'crash'],
            ],
            [
                'title' => 'New employee workstation setup needed',
                'requester' => $users->random(),
                'assigned_to' => $agent1 ?? ($technicians->random() ?? null),
                'category' => 'Access Request',
                'status' => 'pending',
                'priority' => 'medium',
                'description' => 'We have a new employee starting on March 10th. Please prepare a workstation with standard software.',
                'tags' => ['onboarding', 'setup', 'new-employee'],
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
