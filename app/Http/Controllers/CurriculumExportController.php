<?php

namespace App\Http\Controllers;

use App\Models\Curriculum;
use Illuminate\Http\Request;

class CurriculumExportController extends Controller
{
    public function exportCsv(Request $request)
{
    $unit = $request->query('unit');

    if (!$unit) {
        return response()->json(['error' => 'Unit parameter is required'], 400);
    }

    // Use LIKE for partial matching, case-insensitive
    $lessons = Curriculum::where('unit', 'LIKE', '%' . $unit . '%')->get(['lesson', 'unit', 'content']);

    $filename = 'lessons_filtered.csv';

    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
    ];

    $callback = function () use ($lessons) {
        $file = fopen('php://output', 'w');
        fputcsv($file, ['Lesson', 'Unit', 'Content']);

        foreach ($lessons as $lesson) {
            fputcsv($file, [
                $lesson->lesson,
                $lesson->unit,
                str_replace(["\r", "\n"], [' ', ' '], $lesson->content),
            ]);
        }

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}
}
