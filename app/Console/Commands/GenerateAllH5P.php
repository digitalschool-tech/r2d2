<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Curriculum;
use App\Http\Controllers\MoodleController;
use Illuminate\Http\Request;

class GenerateAllH5P extends Command
{
    protected $signature = 'h5p:generate-all {unit?}';
    protected $description = 'Generate and upload H5P for all lessons (optionally by unit)';

    public function handle()
    {
        $controller = new MoodleController();

        $unit = $this->argument('unit');

        if ($unit) {
            $this->info("Filtering by unit: $unit");
            $curricula = Curriculum::whereRaw('BINARY `unit` = ?', [$unit])->get();
        } else {
            $this->info("Processing all units...");
            $curricula = Curriculum::all();
        }


        foreach ($curricula as $curriculum) {
            $this->info("Processing: Unit {$curriculum->unit}, Lesson {$curriculum->lesson}");

            $request = new Request([
                'lesson' => $curriculum->lesson,
                'unit' => $curriculum->unit,
                'course_id' => 100103,
                'section_id' => 1,
                'student_id' => 1,
            ]);

            $response = $controller->generateH5PAndUpload($request);
            $this->line($response->getContent());
        }

        $this->info('All done!');
    }
}
