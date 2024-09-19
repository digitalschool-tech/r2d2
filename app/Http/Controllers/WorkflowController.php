<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Workflow;
use App\Actions\GPTAction;

class WorkflowController extends Controller
{
    public function handle(Request $request, $any)
    {
        // Find the workflow that matches the route
        $workflow = Workflow::where('route', $any)->first();

        // If no workflow is found, return a 404 response
        if (!$workflow) {
            return response()->json(['message' => 'Workflow not found'], 404);
        }

        // Continue processing the workflow if a match is found
        return $this->processWorkflow($workflow);
    }

    /**
     * Process the workflow
     */
    protected function processWorkflow($workflow)
    {
        $prompt = json_decode($workflow["blocks"])[0]->nodeData->prompt;
        $json = GPTAction::handle($prompt);

        // For example, returning the workflow's data for now
        return response()->json(json_decode($json));
    }
}