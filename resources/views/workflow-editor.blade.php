<div>
    <div id="drawflow" style="width: 100%; height: 200px; border: 1px solid black;"></div>
    <input id="workflow-json" name="blocks" wire:model.defer="blocks" hidden />
</div>

@vite('resources/js/app.js')

<style>
    .drawflow .drawflow-node{
        width: min-content;
    }
</style>