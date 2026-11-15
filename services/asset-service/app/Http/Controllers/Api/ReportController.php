<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetDocument;
use App\Models\AssetIncident;
use App\Models\AssetInventory;
use App\Models\AssetInventoryItem;
use App\Models\AssetMaintenance;
use App\Models\AssetSubmission;
use App\Models\AssetTransfer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Vue d'ensemble consolidée du patrimoine.
     * Consommée par le dashboard et les futurs écrans de reporting.
     */
    public function overview(Request $request): JsonResponse
    {
        $assets = Asset::query()
            ->select('status', 'type', DB::raw('COUNT(*) as total'), DB::raw('COALESCE(SUM(value),0) as value'))
            ->groupBy('status', 'type')
            ->get();

        $byType = [];
        $byStatus = [];
        $totalValue = 0;
        foreach ($assets as $row) {
            $byType[$row->type] = ($byType[$row->type] ?? 0) + (int) $row->total;
            $byStatus[$row->status] = ($byStatus[$row->status] ?? 0) + (int) $row->total;
            $totalValue += (float) $row->value;
        }

        $maintenances = AssetMaintenance::query()
            ->select('status', DB::raw('COUNT(*) as total'), DB::raw('COALESCE(SUM(cost),0) as cost'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $incidents = AssetIncident::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $incidentsBySeverity = AssetIncident::query()
            ->whereIn('status', [AssetIncident::STATUS_OUVERT, AssetIncident::STATUS_EN_COURS])
            ->select('severity', DB::raw('COUNT(*) as total'))
            ->groupBy('severity')
            ->pluck('total', 'severity');

        $inventories = AssetInventory::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        // Écarts constatés lors des inventaires clôturés
        $inventoryGaps = AssetInventoryItem::query()
            ->whereIn('status', [AssetInventoryItem::STATUS_ABSENT])
            ->whereHas('inventory', fn ($q) => $q->where('status', AssetInventory::STATUS_CLOS))
            ->count();

        return response()->json([
            'data' => [
                'assets' => [
                    'total' => array_sum($byType),
                    'total_value' => $totalValue,
                    'by_type' => $byType,
                    'by_status' => $byStatus,
                ],
                'workflows' => [
                    'assignments_pending' => AssetAssignment::where('status', 'en_attente')->count(),
                    'transfers_pending' => AssetTransfer::where('status', 'en_attente')->count(),
                    'submissions_pending' => AssetSubmission::where('status', 'en_attente')->count(),
                ],
                'maintenances' => [
                    'en_cours' => (int) ($maintenances[AssetMaintenance::STATUS_EN_COURS]->total ?? 0),
                    'planifiees' => (int) ($maintenances[AssetMaintenance::STATUS_PLANIFIEE]->total ?? 0),
                    'terminees' => (int) ($maintenances[AssetMaintenance::STATUS_TERMINEE]->total ?? 0),
                    'cout_total_terminées' => (float) ($maintenances[AssetMaintenance::STATUS_TERMINEE]->cost ?? 0),
                ],
                'incidents' => [
                    'ouverts' => (int) ($incidents[AssetIncident::STATUS_OUVERT] ?? 0)
                        + (int) ($incidents[AssetIncident::STATUS_EN_COURS] ?? 0),
                    'ouverts_par_severite' => $incidentsBySeverity,
                ],
                'inventories' => [
                    'en_cours' => (int) ($inventories[AssetInventory::STATUS_EN_COURS] ?? 0),
                    'clos' => (int) ($inventories[AssetInventory::STATUS_CLOS] ?? 0),
                    'biens_absents' => $inventoryGaps,
                ],
                'documents_total' => AssetDocument::count(),
            ],
        ]);
    }
}
