<?php

namespace App\Http\Controllers\Dimensions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ProjectController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    public function index()
    {
        $this->middleware('permission:projects.view');
        return view('projects.index');
    }

    public function data()
    {
        $this->middleware('permission:projects.view');
        $q = DB::table('projects as p')
            ->select('p.id', 'p.code', 'p.name', 'p.status', 'p.budget_total');
        return DataTables::of($q)
            ->addColumn('actions', function ($row) {
                $edit = '<button class="btn btn-xs btn-secondary btn-edit" data-id="' . $row->id . '" data-code="' . e($row->code) . '" data-name="' . e($row->name) . '" data-status="' . e($row->status) . '" data-budget="' . (float)$row->budget_total . '">Edit</button>';
                $delUrl = route('projects.destroy', $row->id);
                $del = '<button class="btn btn-xs btn-danger btn-delete" data-url="' . $delUrl . '">Delete</button>';
                return $edit . ' ' . $del;
            })
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function store(Request $request)
    {
        $this->middleware('permission:projects.manage');
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:projects,code'],
            'name' => ['required', 'string', 'max:255'],
            'budget_total' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:active,closed'],
        ]);
        DB::table('projects')->insert([
            'code' => $data['code'],
            'name' => $data['name'],
            'budget_total' => $data['budget_total'] ?? 0,
            'status' => $data['status'] ?? 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return back()->with('success', 'Project saved');
    }

    public function update(Request $request, int $id)
    {
        $this->middleware('permission:projects.manage');

        try {
            $data = $request->validate([
                'code' => ['required', 'string', 'max:50', 'unique:projects,code,' . $id],
                'name' => ['required', 'string', 'max:255'],
                'budget_total' => ['nullable', 'numeric', 'min:0'],
                'status' => ['nullable', 'in:active,closed'],
            ]);

            $updated = DB::table('projects')->where('id', $id)->update([
                'code' => $data['code'],
                'name' => $data['name'],
                'budget_total' => $data['budget_total'] ?? 0,
                'status' => $data['status'] ?? 'active',
                'updated_at' => now(),
            ]);

            if ($updated) {
                return response()->json(['success' => true, 'message' => 'Project updated successfully']);
            } else {
                return response()->json(['success' => false, 'message' => 'Project not found or no changes made'], 404);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Project update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the project'
            ], 500);
        }
    }

    public function destroy(int $id)
    {
        $this->middleware('permission:projects.manage');
        $used = DB::table('journal_lines')->where('project_id', $id)->exists();
        if ($used) {
            return response()->json(['message' => 'Cannot delete a project that is referenced by journal lines'], 422);
        }
        DB::table('projects')->where('id', $id)->delete();
        return response()->json(['ok' => true]);
    }
}
