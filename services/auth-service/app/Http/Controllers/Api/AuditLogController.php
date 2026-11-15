<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = AuditLog::with('user:id,name,email')
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->when($request->filled('action'), function ($q) use ($request) {
                $action = $request->string('action')->toString();
                $q->where('action', 'like', "{$action}%");
            })
            ->when($request->filled('from'), fn ($q) => $q->where('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->where('created_at', '<=', $request->date('to')))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(min((int) $request->input('per_page', 25), 100));

        return response()->json($logs);
    }
}
