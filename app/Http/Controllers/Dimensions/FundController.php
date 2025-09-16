<?php

namespace App\Http\Controllers\Dimensions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class FundController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    public function index()
    {
        return view('funds.index');
    }

    public function data()
    {
        $q = DB::table('funds')->select('id', 'code', 'name', 'is_restricted');
        return DataTables::of($q)
            ->addColumn('restricted', fn($r) => $r->is_restricted ? 'Yes' : 'No')
            ->addColumn('actions', function ($row) {
                $edit = '<button class="btn btn-xs btn-secondary btn-edit" data-id="' . $row->id . '" data-code="' . e($row->code) . '" data-name="' . e($row->name) . '" data-restricted="' . (int)$row->is_restricted . '">Edit</button>';
                $delUrl = route('funds.destroy', $row->id);
                $del = '<button class="btn btn-xs btn-danger btn-delete" data-url="' . $delUrl . '">Delete</button>';
                return $edit . ' ' . $del;
            })
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:funds,code'],
            'name' => ['required', 'string', 'max:255'],
            'is_restricted' => ['nullable', 'boolean'],
        ]);
        DB::table('funds')->insert([
            'code' => $data['code'],
            'name' => $data['name'],
            'is_restricted' => !empty($data['is_restricted']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return back()->with('success', 'Fund saved');
    }

    public function update(Request $request, int $id)
    {
        try {
            $data = $request->validate([
                'code' => ['required', 'string', 'max:50', 'unique:funds,code,' . $id],
                'name' => ['required', 'string', 'max:255'],
                'is_restricted' => ['nullable', 'boolean'],
            ]);

            $updated = DB::table('funds')->where('id', $id)->update([
                'code' => $data['code'],
                'name' => $data['name'],
                'is_restricted' => !empty($data['is_restricted']),
                'updated_at' => now(),
            ]);

            if ($updated) {
                return response()->json(['success' => true, 'message' => 'Fund updated successfully']);
            } else {
                return response()->json(['success' => false, 'message' => 'Fund not found or no changes made'], 404);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Fund update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the fund'
            ], 500);
        }
    }

    public function destroy(int $id)
    {
        $used = DB::table('journal_lines')->where('fund_id', $id)->exists();
        $usedProjects = DB::table('projects')->where('fund_id', $id)->exists();
        if ($used || $usedProjects) {
            return response()->json(['message' => 'Cannot delete a fund in use'], 422);
        }
        DB::table('funds')->where('id', $id)->delete();
        return response()->json(['ok' => true]);
    }
}
