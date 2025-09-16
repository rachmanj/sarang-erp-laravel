<?php

namespace App\Http\Controllers\Dimensions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class DepartmentController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    public function index()
    {
        return view('departments.index');
    }

    public function data()
    {
        $q = DB::table('departments')->select('id', 'code', 'name');
        return DataTables::of($q)
            ->addColumn('actions', function ($row) {
                $edit = '<button class="btn btn-xs btn-secondary btn-edit" data-id="' . $row->id . '" data-code="' . e($row->code) . '" data-name="' . e($row->name) . '">Edit</button>';
                $delUrl = route('departments.destroy', $row->id);
                $del = '<button class="btn btn-xs btn-danger btn-delete" data-url="' . $delUrl . '">Delete</button>';
                return $edit . ' ' . $del;
            })
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:departments,code'],
            'name' => ['required', 'string', 'max:255'],
        ]);
        DB::table('departments')->insert([
            'code' => $data['code'],
            'name' => $data['name'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return back()->with('success', 'Department saved');
    }

    public function update(Request $request, int $id)
    {
        try {
            $data = $request->validate([
                'code' => ['required', 'string', 'max:50', 'unique:departments,code,' . $id],
                'name' => ['required', 'string', 'max:255'],
            ]);

            $updated = DB::table('departments')->where('id', $id)->update([
                'code' => $data['code'],
                'name' => $data['name'],
                'updated_at' => now(),
            ]);

            if ($updated) {
                return response()->json(['success' => true, 'message' => 'Department updated successfully']);
            } else {
                return response()->json(['success' => false, 'message' => 'Department not found or no changes made'], 404);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Department update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the department'
            ], 500);
        }
    }

    public function destroy(int $id)
    {
        $used = DB::table('journal_lines')->where('dept_id', $id)->exists();
        if ($used) {
            return response()->json(['message' => 'Cannot delete a department in use'], 422);
        }
        DB::table('departments')->where('id', $id)->delete();
        return response()->json(['ok' => true]);
    }
}
