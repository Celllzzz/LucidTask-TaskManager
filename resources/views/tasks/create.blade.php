<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add New Personal Task') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="{
            statuses: {{ $statuses->toJson() }}, 
            selectedStatusId: '',
            modalOpen: false,
            newStatusName: '',
            newStatusColor: '#3b82f6',
            isLoading: false,

            // Fungsi untuk MENYIMPAN status baru dari popup
            async storeNewStatus() {
                if (this.newStatusName.trim() === '') return;
                this.isLoading = true;
                
                try {
                    const response = await fetch('{{ route('statuses.storePersonal') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ name: this.newStatusName, color: this.newStatusColor })
                    });

                    if (!response.ok) throw new Error('Failed to save status');
                    
                    const newStatus = await response.json();
                    
                    this.statuses.push(newStatus); // 1. Tambahkan status baru ke array 'statuses' (dropdown akan otomatis update)
                    this.selectedStatusId = newStatus.id; // 2. Langsung pilih status yang baru dibuat
                    this.modalOpen = false; // 3. Tutup popup
                    this.newStatusName = ''; // 4. Reset form
                    this.newStatusColor = '#3b82f6';

                } catch (error) {
                    console.error('Error storing status:', error);
                    alert('Failed to save status.');
                }
                this.isLoading = false;
            }
        }">

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <form action="{{ route('tasks.storePersonal') }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700">Task Name</label>
                            <input type="text" name="name" id="name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>

                        <div class="mb-4">
                            <label for="description" class="block text-sm font-medium text-gray-700">Description (Optional)</label>
                            <textarea name="description" id="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                        </div>

                        <div class="mb-4">
                            <label for="deadline" class="block text-sm font-medium text-gray-700">Deadline (Optional)</label>
                            <input type="datetime-local" name="deadline" id="deadline" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>

                        <div class="mb-4">
                            <label for="status_id" class="block text-sm font-medium text-gray-700">Status</label>
                            <div class="flex items-center space-x-2 mt-1">
                                <select name="status_id" id="status_id" x-model="selectedStatusId" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">-- No Status --</option>
                                    <template x-for="status in statuses" :key="status.id">
                                        <option :value="status.id" :style="{ color: status.color }" x-text="status.name"></option>
                                    </template>
                                </select>
                                <button type="button" @click="modalOpen = true" class="flex-shrink-0 py-2 px-3 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    Add
                                </button>
                            </div>
                            @error('status_id')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="flex items-center gap-4">
                            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Save Task
                            </button>
                            <a href="{{ route('dashboard') }}" class="text-sm text-gray-600 hover:text-gray-900">
                                Cancel
                            </a>
                        </div>
                    </form>

                </div>
            </div>
        </div>


        <div 
            x-show="modalOpen" 
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-75"
            style="display: none;"
        >
            <div 
                @click.away="modalOpen = false" 
                x-show="modalOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-90"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-90"
                class="bg-white rounded-lg shadow-xl w-full max-w-md p-6"
            >
                <h3 class="text-lg font-semibold mb-4">Add New Status</h3>
                
                <form @submit.prevent="storeNewStatus()">
                    <div class="mb-3">
                        <label for="new_status_name" class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" id="new_status_name" x-model="newStatusName" :disabled="isLoading" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
                    </div>
                    <div class="mb-3">
                        <label for="new_status_color" class="block text-sm font-medium text-gray-700">Color</label>
                        <input type="color" id="new_status_color" x-model="newStatusColor" :disabled="isLoading" class="mt-1 block w-full h-10">
                    </div>
                    <div class="mt-4 flex justify-end space-x-2">
                        <button type="button" @click="modalOpen = false" class="py-2 px-4 rounded-md text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200">
                            Cancel
                        </button>
                        <button type="submit" :disabled="isLoading" class="py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50">
                            <span x-show="!isLoading">Save Status</span>
                            <span x-show="isLoading">Saving...</span>
                        </button>
                    </div>
                </form>

            </div>
        </div>

    </div>
</x-app-layout>