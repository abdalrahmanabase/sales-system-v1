@if(auth()->user()->branch)
    <span class="text-sm text-gray-400 dark:text-gray-300 ml-4">Branch: {{ auth()->user()->branch->name }}</span>
@endif 