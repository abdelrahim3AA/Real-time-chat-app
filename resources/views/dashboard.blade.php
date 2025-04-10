<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Logged In Message --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    {{ __("Let's chat!") }}
                </div>
            </div>

            {{-- Users List --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mt-6">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($users as $user)
                            <a href="{{ route('chat', $user->id) }}"
                               class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg shadow-md block hover:bg-gray-200 dark:hover:bg-gray-600 transition duration-300 ease-in-out">
                                <h3 class="text-lg font-semibold dark:text-white">{{ $user->name }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-300">{{ $user->email }}</p>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
