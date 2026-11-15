<?php

namespace Database\Seeders;

use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    /**
     * Mot de passe de démonstration commun à tous les utilisateurs fictifs.
     * À changer impérativement en production.
     */
    public const DEMO_PASSWORD = 'PatriGest@2026';

    /**
     * Structures administratives fictives du MFB (pilote).
     */
    public function run(): void
    {
        $mfb = Structure::updateOrCreate(
            ['code' => 'MFB'],
            [
                'type' => Structure::TYPE_ADMINISTRATION,
                'name' => 'Ministère des Finances et du Budget',
                'description' => 'Administration pilote de PatriGest.',
            ]
        );

        $directions = [
            ['DSG', 'Direction des Services Généraux'],
            ['DB', 'Direction du Budget'],
            ['DEP', 'Direction des Études et de la Planification'],
        ];

        $services = [
            'DSG' => [
                ['SDA', 'Service du Patrimoine et des Approvisionnements'],
                ['SI', 'Service Informatique'],
            ],
            'DB' => [
                ['SMB', 'Service du Matériel et Logistique'],
            ],
            'DEP' => [],
        ];

        foreach ($directions as [$code, $name]) {
            $direction = Structure::updateOrCreate(
                ['code' => $code],
                [
                    'parent_id' => $mfb->id,
                    'type' => Structure::TYPE_DIRECTION,
                    'name' => $name,
                ]
            );

            foreach ($services[$code] ?? [] as [$scode, $sname]) {
                Structure::updateOrCreate(
                    ['code' => $scode],
                    [
                        'parent_id' => $direction->id,
                        'type' => Structure::TYPE_SERVICE,
                        'name' => $sname,
                    ]
                );
            }
        }

        $this->seedUsers();
    }

    /**
     * Un utilisateur fictif par rôle fonctionnel.
     */
    private function seedUsers(): void
    {
        $patrimoine = Structure::where('code', 'SDA')->first();
        $budget = Structure::where('code', 'SMB')->first();
        $dsg = Structure::where('code', 'DSG')->first();

        $users = [
            ['Kossi Mensah', 'admin-technique', 'admin@mfb.patri.test', null],
            ['Afi Lawson', 'responsable-patrimoine', 'a.lawson@mfb.patri.test', $patrimoine],
            ['Yao Agbeko', 'responsable-direction', 'y.agbeko@mfb.patri.test', $dsg],
            ['Komlan Dossi', 'decideur', 'k.dossi@mfb.patri.test', null],
            ['Essi Ahlinvi', 'agent-comptabilite', 'e.ahlinvi@mfb.patri.test', $budget],
            ['Mawuli Foli', 'agent-domaine-batiment', 'm.foli@mfb.patri.test', $dsg],
            ['Senam Kpodo', 'agent-parc-auto', 's.kpodo@mfb.patri.test', $budget],
            ['Dela Ati', 'agent-inventaire', 'd.ati@mfb.patri.test', $patrimoine],
            ['Peya Sewusu', 'auditeur-inspecteur', 'p.sewusu@mfb.patri.test', null],
        ];

        foreach ($users as [$name, $role, $email, $structure]) {
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'username' => Str::slug($name, '.'),
                    'password' => Hash::make(self::DEMO_PASSWORD),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles([$role]);

            if ($structure) {
                $user->structures()->sync([$structure->id => ['is_primary' => true]]);
            }
        }
    }
}
