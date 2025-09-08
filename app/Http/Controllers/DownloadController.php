<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class DownloadController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:reports.view');
    }

    public function index()
    {
        $files = [];
        foreach (Storage::files('public/pdfs') as $path) {
            $files[] = [
                'name' => basename($path),
                'url' => Storage::url($path),
                'size' => Storage::size($path),
                'modified' => date('Y-m-d H:i:s', Storage::lastModified($path)),
            ];
        }
        usort($files, function ($a, $b) {
            return strcmp($b['modified'], $a['modified']);
        });
        return view('downloads.index', compact('files'));
    }
}
