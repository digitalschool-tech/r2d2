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
                        {{ (int)$index + 1 }}
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
                                @if (isset($question['correct']) && $question['correct'] === (int)$ansIndex)
                                    <span class="text-success-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                    <span class="text-success-600 font-medium">{{ $answer }}</span>
                                @else
                                    <span class="text-gray-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 9.75L15 15m0-5.25L9 15" />
                                        </svg>
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
