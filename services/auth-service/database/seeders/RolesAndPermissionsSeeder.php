<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Permissions fonctionnelles de PatriGest.
     * Les permissions sont indépendantes des noms de rôles.
     */
    public const PERMISSIONS = [
        // Biens (Asset Service)
        'view-assets',
        'create-assets',
        'edit-assets',
        'submit-assets',
        'approve-asset-creation',
        // Opérations (Assignment Service)
        'view-assignments',
        'create-assignments',
        'approve-assignment',
        'create-transfer',
        'approve-transfer',
        // Documents (Document Service)
        'view-documents',
        'manage-documents',
        // Terrain / Maintenance
        'manage-inventory',
        'manage-incidents',
        'manage-maintenance',
        // Pilotage
        'view-reports',
        // Administration (Auth Service)
        'manage-users',
        'manage-roles',
        'manage-settings',
        'manage-structures',
        'view-audit-logs',
    ];

    /**
     * Matrice rôles × permissions.
     * Le rôle admin-technique ne reçoit AUCUNE permission de validation
     * métier : il administre la plateforme sans valider les opérations.
     */
    public const ROLE_PERMISSIONS = [
        'admin-technique' => [
            'manage-users', 'manage-roles', 'manage-settings',
            'manage-structures', 'view-audit-logs',
            'view-assets', 'view-assignments', 'view-reports',
        ],
        'responsable-patrimoine' => [
            'view-assets', 'create-assets', 'edit-assets', 'submit-assets',
            'approve-asset-creation',
            'view-assignments', 'create-assignments', 'create-transfer',
            'view-documents', 'manage-documents',
            'view-reports',
        ],
        'responsable-direction' => [
            'view-assets', 'view-assignments',
            'create-transfer', 'approve-assignment', 'approve-transfer',
            'view-documents', 'view-reports',
        ],
        'decideur' => [
            'view-assets', 'view-assignments',
            'approve-asset-creation', 'approve-assignment', 'approve-transfer',
            'view-documents', 'view-reports',
        ],
        'agent-comptabilite' => [
            'view-assets', 'create-assets', 'edit-assets', 'submit-assets',
            'view-assignments',
            'view-documents', 'manage-documents',
            'view-reports',
        ],
        'agent-domaine-batiment' => [
            'view-assets', 'create-assets', 'edit-assets', 'submit-assets',
            'view-assignments',
            'manage-incidents', 'manage-maintenance',
            'view-documents', 'view-reports',
        ],
        'agent-parc-auto' => [
            'view-assets', 'create-assets', 'edit-assets', 'submit-assets',
            'view-assignments',
            'manage-incidents', 'view-documents',
        ],
        'agent-inventaire' => [
            'view-assets', 'view-assignments',
            'manage-inventory', 'manage-incidents',
            'view-documents',
        ],
        'auditeur-inspecteur' => [
            'view-assets', 'view-assignments', 'view-documents',
            'manage-incidents', 'view-reports',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($permissions);
        }
    }
}
