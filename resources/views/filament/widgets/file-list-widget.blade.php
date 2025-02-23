<x-filament::widget>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr>
                    <th class="px-4 py-2">File</th>
                    <th class="px-4 py-2">Created</th>
                    <th class="px-4 py-2">Size</th>
                </tr>
            </thead>
            <tbody>
                @forelse($this->files as $file)
                    <tr>
                        <td class="border px-4 py-2">
                            <a href="{{ Storage::url($file['path']) }}" target="_blank">
                                {{ $file['filename'] }}
                            </a>
                        </td>
                        <td class="border px-4 py-2">
                            {{ $file['created_at']->format('M j, Y H:i:s') }}
                        </td>
                        <td class="border px-4 py-2">
                            {{ \App\Filament\Widgets\FileListWidget::formatBytes($file['size']) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-2">No files found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament::widget>
