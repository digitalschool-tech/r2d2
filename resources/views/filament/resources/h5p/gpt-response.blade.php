@php
    $questions = $getState() ?? [];
@endphp

@if (empty($questions))
    <div class="text-gray-500 italic">No quiz questions available</div>
@else
    <div class="space-y-6">
        @foreach ($questions as $index => $question)
            <div class="bg-white rounded-lg shadow p-4 border border-gray-200">
                <div class="flex items-start">
                    <div class="bg-primary-500 text-white rounded-full w-6 h-6 flex items-center justify-center mr-3 flex-shrink-0">
                        {{ $index + 1 }}
                    </div>
                    <div class="font-medium text-lg text-gray-900 flex-1">
                        {{ $question['question'] ?? 'Unknown question' }}
                    </div>
                    <div class="text-xs text-gray-500 ml-2">
                        {{ isset($question['answers']) && count($question['answers']) === 2 ? 'True/False' : 'Multiple Choice' }}
                    </div>
                </div>

                <div class="mt-3 pl-9 space-y-2">
                    @if (!empty($question['answers']))
                        @foreach ($question['answers'] as $ansIndex => $answer)
                            <div class="flex items-center space-x-2">
                                @if (isset($question['correct']) && $question['correct'] === $ansIndex)
                                    <span class="text-success-500">
                                        <x-heroicon-s-check-circle class="w-5 h-5" />
                                    </span>
                                    <span class="text-success-600 font-medium">{{ $answer }}</span>
                                @else
                                    <span class="text-gray-400">
                                        <x-heroicon-o-circle class="w-5 h-5" />
                                    </span>
                                    <span class="text-gray-600">{{ $answer }}</span>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="text-gray-500 italic">No answer options available</div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif
