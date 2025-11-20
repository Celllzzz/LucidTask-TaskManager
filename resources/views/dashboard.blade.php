<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard (Personal Workspace)') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="{ 
            statusModalOpen: false, 
            isLoading: false,
            tasks: {{ $tasks->toJson() }},
            pageStatuses: {{ $statuses->toJson() }},
            modalStatuses: [],
            
            // --- State Filter ---
            filterStatus: 'all', 
            filterDeadline: 'all', // State baru untuk filter deadline
            
            // --- State Modal ---
            newStatusName: '', 
            newStatusColor: '#3b82f6',
            editingStatusId: null, 
            editStatusName: '',
            editStatusColor: '',

            // --- State Inline Editing ---
            editingTask: { id: null, field: null, buffer: null },

            // --- GETTER UNTUK FILTER TUGAS (STATUS + DEADLINE) ---
            get filteredTasks() {
                return this.tasks.filter(task => {
                    // 1. Cek Filter Status
                    const statusMatch = (this.filterStatus === 'all') ||
                                        (this.filterStatus === 'none' && !task.status_id) ||
                                        (task.status_id == this.filterStatus);

                    // 2. Cek Filter Deadline
                    let deadlineMatch = true;
                    if (this.filterDeadline !== 'all') {
                        if (this.filterDeadline === 'no_date') {
                            deadlineMatch = !task.deadline;
                        } else if (task.deadline) {
                            const d = new Date(task.deadline);
                            const now = new Date();
                            
                            // Reset jam untuk perbandingan hari yang akurat
                            const todayStart = new Date(); todayStart.setHours(0,0,0,0);
                            const dStart = new Date(d); dStart.setHours(0,0,0,0);

                            if (this.filterDeadline === 'today') {
                                // Cek apakah tanggal sama persis dengan hari ini
                                deadlineMatch = dStart.getTime() === todayStart.getTime();
                            } else if (this.filterDeadline === 'overdue') {
                                // Cek apakah waktu deadline sudah lewat dari SKRG
                                deadlineMatch = d < now; 
                            } else if (this.filterDeadline === 'week') {
                                // Cek 7 hari ke depan
                                const nextWeek = new Date(todayStart);
                                nextWeek.setDate(todayStart.getDate() + 7);
                                deadlineMatch = d >= now && d <= nextWeek;
                            }
                        } else {
                            // Jika filter aktif tapi task tidak punya deadline
                            deadlineMatch = false;
                        }
                    }

                    // Return true jika KEDUA filter cocok (AND logic)
                    return statusMatch && deadlineMatch;
                });
            },

            // Memuat status untuk modal
            async loadModalStatuses() {
                this.isLoading = true;
                try {
                    const response = await fetch('{{ route('statuses.getPersonal') }}', {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    this.modalStatuses = await response.json();
                } catch (error) { console.error('Error loading statuses:', error); }
                this.isLoading = false;
            },
            
            // Menyimpan status baru
            async storeModalStatus() {
                if (this.newStatusName.trim() === '') return;
                this.isLoading = true;
                try {
                    const response = await fetch('{{ route('statuses.storePersonal') }}', {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/json', 
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json', 
                            'X-Requested-With': 'XMLHttpRequest' 
                        },
                        body: JSON.stringify({ name: this.newStatusName, color: this.newStatusColor })
                    });
                    if (!response.ok) throw new Error('Failed to save status');
                    const newStatus = await response.json();
                    this.modalStatuses.push(newStatus);
                    this.pageStatuses.push(newStatus);
                    this.newStatusName = ''; 
                    this.newStatusColor = '#3b82f6';
                } catch (error) { alert('Failed to save status.'); }
                this.isLoading = false;
            },
            
            // Menghapus status
            async deleteModalStatus(id) {
                if (!confirm('Are you sure you want to delete this status?')) return;
                this.isLoading = true;
                try {
                    const response = await fetch(`/statuses/personal/${id}`, {
                        method: 'DELETE',
                        headers: { 
                            'Content-Type': 'application/json', 
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json', 
                            'X-Requested-With': 'XMLHttpRequest' 
                        }
                    });
                    if (!response.ok) throw new Error('Failed to delete status');
                    this.modalStatuses = this.modalStatuses.filter(s => s.id !== id);
                    this.pageStatuses = this.pageStatuses.filter(s => s.id !== id);
                } catch (error) { alert('Failed to delete status.'); }
                this.isLoading = false;
            },
            
            // Memperbarui status
            async updateModalStatus() {
                if (this.editStatusName.trim() === '') return;
                this.isLoading = true;
                try {
                    const id = this.editingStatusId;
                    const response = await fetch(`/statuses/personal/${id}`, {
                        method: 'PUT',
                        headers: { 
                            'Content-Type': 'application/json', 
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json', 
                            'X-Requested-With': 'XMLHttpRequest' 
                        },
                        body: JSON.stringify({ name: this.editStatusName, color: this.editStatusColor })
                    });
                    if (!response.ok) throw new Error('Failed to update status');
                    const updatedStatus = await response.json();
                    let indexModal = this.modalStatuses.findIndex(s => s.id === id);
                    if (indexModal !== -1) this.modalStatuses[indexModal] = updatedStatus;
                    let indexPage = this.pageStatuses.findIndex(s => s.id === id);
                    if (indexPage !== -1) this.pageStatuses[indexPage] = updatedStatus;
                    this.cancelEditing();
                } catch (error) { alert('Failed to update status.'); }
                this.isLoading = false;
            },
            
            startEditing(status) {
                this.editingStatusId = status.id;
                this.editStatusName = status.name;
                this.editStatusColor = status.color;
            },
            
            cancelEditing() {
                this.editingStatusId = null;
                this.editStatusName = '';
                this.editStatusColor = '';
            },

            isEditing(taskId, field) {
                return this.editingTask.id === taskId && this.editingTask.field === field;
            },
            
            startTaskEditing(task, field) {
                this.editingTask.id = task.id;
                this.editingTask.field = field;
                if (field === 'deadline') {
                    if (task.deadline) {
                        const d = new Date(task.deadline);
                        const yyyy = d.getFullYear();
                        const mm = (d.getMonth() + 1).toString().padStart(2, '0');
                        const dd = d.getDate().toString().padStart(2, '0');
                        const hh = d.getHours().toString().padStart(2, '0');
                        const min = d.getMinutes().toString().padStart(2, '0');
                        this.editingTask.buffer = `${yyyy}-${mm}-${dd}T${hh}:${min}`;
                    } else {
                        this.editingTask.buffer = null; 
                    }
                } else if (field === 'status_id') {
                    this.editingTask.buffer = task.status_id;
                } else {
                    this.editingTask.buffer = task[field];
                }
            },
            
            cancelTaskEditing() {
                this.editingTask.id = null;
                this.editingTask.field = null;
                this.editingTask.buffer = null;
            },
            
            async saveTaskEdit(task) {
                const originalValue = this.editingTask.field === 'status_id' ? task.status_id : task[this.editingTask.field];
                let bufferValue = this.editingTask.buffer;
                let originalNormalized = originalValue;

                if (bufferValue === '') bufferValue = null;
                
                if (this.editingTask.field === 'deadline' && originalValue) {
                     const d = new Date(originalValue);
                     const yyyy = d.getFullYear();
                     const mm = (d.getMonth() + 1).toString().padStart(2, '0');
                     const dd = d.getDate().toString().padStart(2, '0');
                     const hh = d.getHours().toString().padStart(2, '0');
                     const min = d.getMinutes().toString().padStart(2, '0');
                     originalNormalized = `${yyyy}-${mm}-${dd}T${hh}:${min}`;
                }

                if (bufferValue == originalNormalized) { 
                    this.cancelTaskEditing();
                    return;
                }
                
                const field = this.editingTask.field;
                let value = this.editingTask.buffer; 

                if ((field === 'status_id' || field === 'deadline') && value === '') {
                    value = null;
                }
                
                try {
                    const response = await fetch(`/tasks/personal/${task.id}`, {
                        method: 'PUT',
                        headers: { 
                            'Content-Type': 'application/json', 
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ [field]: value })
                    });
                    
                    if (!response.ok) {
                        if (response.status === 422) {
                            const errorData = await response.json();
                            const firstError = Object.values(errorData.errors)[0][0];
                            alert(`Validation failed: ${firstError}`);
                        } else {
                            throw new Error('Failed to update task');
                        }
                    } else {
                        const updatedTask = await response.json();
                        const index = this.tasks.findIndex(t => t.id === task.id);
                        if (index !== -1) {
                            this.tasks[index] = updatedTask;
                        }
                        this.cancelTaskEditing();
                    }

                } catch (error) {
                    console.error('Save Task Error:', error); 
                    alert('Failed to update task.');
                }
            }
        }">

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <div class="mb-6 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 w-full lg:w-auto">
                            <a href="{{ route('tasks.createPersonal') }}" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Add New Task
                            </a>
                            
                            <div class="flex flex-col sm:flex-row gap-2">
                                <div class="flex items-center">
                                    <label for="filterStatus" class="mr-2 text-sm text-gray-600 font-medium">Status:</label>
                                    <select x-model="filterStatus" id="filterStatus" class="text-sm form-select rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <option value="all">All</option>
                                        <option value="none">No Status</option>
                                        <template x-for="status in pageStatuses" :key="status.id">
                                            <option :value="status.id" x-text="status.name"></option>
                                        </template>
                                    </select>
                                </div>

                                <div class="flex items-center sm:ml-4">
                                    <label for="filterDeadline" class="mr-2 text-sm text-gray-600 font-medium">Deadline:</label>
                                    <select x-model="filterDeadline" id="filterDeadline" class="text-sm form-select rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <option value="all">All</option>
                                        <option value="today">Today</option>
                                        <option value="week">Next 7 Days</option>
                                        <option value="overdue">Overdue</option>
                                        <option value="no_date">No Deadline</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <button @click="statusModalOpen = true; loadModalStatuses()" class="text-sm font-medium text-gray-600 hover:text-gray-900 whitespace-nowrap">
                            Manage Statuses
                        </button>
                    </div>

                    <h3 class="font-semibold text-lg mb-4">Your Tasks</h3>
                    <div class="overflow-x-auto border border-gray-200 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">No.</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deadline</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="(task, index) in filteredTasks" :key="task.id">
                                    <tr class="hover:bg-gray-50">
                                        
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span x-text="index + 1"></span>
                                        </td>
                                        
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span x-show="!isEditing(task.id, 'name')" 
                                                  @click="startTaskEditing(task, 'name')" 
                                                  class="text-sm font-medium text-gray-900 cursor-pointer"
                                                  x-text="task.name"></span>
                                            
                                            <input type="text" 
                                                   x-show="isEditing(task.id, 'name')"
                                                   x-model="editingTask.buffer" 
                                                   @keydown.enter="saveTaskEdit(task)" 
                                                   @keydown.escape="cancelTaskEditing()"
                                                   @click.away="saveTaskEdit(task)" 
                                                   class="text-sm form-input rounded-md border-gray-300" 
                                                   x-init="$nextTick(() => { if(isEditing(task.id, 'name')) $el.focus() })">
                                        </td>
                                        
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span x-show="!isEditing(task.id, 'status_id')"
                                                  @click="startTaskEditing(task, 'status_id')"
                                                  class="text-sm cursor-pointer px-2 inline-flex leading-5 font-semibold rounded-full"
                                                  :style="{ 
                                                      backgroundColor: task.status ? task.status.color + '20' : '#e5e7eb', 
                                                      color: task.status ? task.status.color : '#374151' 
                                                  }"
                                                  x-text="task.status ? task.status.name : 'None'">
                                            </span>
                                            <select x-show="isEditing(task.id, 'status_id')"
                                                    x-model="editingTask.buffer"
                                                    @change="saveTaskEdit(task)"
                                                    @click.away="cancelTaskEditing()"
                                                    class="text-sm form-select rounded-md border-gray-300" 
                                                    x-init="$nextTick(() => { if(isEditing(task.id, 'status_id')) $el.focus() })">
                                                <option value="">-- No Status --</option>
                                                <template x-for="status in pageStatuses" :key="status.id">
                                                    <option :value="status.id" x-text="status.name"></option>
                                                </template>
                                            </select>
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span x-show="!isEditing(task.id, 'deadline')"
                                                  @click="startTaskEditing(task, 'deadline')"
                                                  class="text-sm text-gray-700 cursor-pointer"
                                                  x-text="task.deadline ? new Date(task.deadline).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : 'No Date'">
                                            </span>
                                            <input type="datetime-local"
                                                   x-show="isEditing(task.id, 'deadline')"
                                                   x-model="editingTask.buffer"
                                                   @change="saveTaskEdit(task)"
                                                   @click.away="cancelTaskEditing()"
                                                   class="text-sm form-input rounded-md border-gray-300" 
                                                   x-init="$nextTick(() => { if(isEditing(task.id, 'deadline')) $el.focus() })">
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <form action="#" @submit.prevent="
                                                // --- DELETE TASK (TANPA ALERT) ---
                                                fetch(`/tasks/personal/${task.id}`, {
                                                    method: 'DELETE',
                                                    headers: { 
                                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                        'Accept': 'application/json',
                                                        'X-Requested-With': 'XMLHttpRequest'
                                                    }
                                                })
                                                .then(response => {
                                                    if (response.ok) {
                                                        // Hapus dari array, filter akan otomatis update
                                                        tasks = tasks.filter(t => t.id !== task.id);
                                                        // ALERT DIHAPUS
                                                    } else {
                                                        alert('Failed to delete task.');
                                                    }
                                                })
                                                .catch(error => {
                                                    console.error('Delete Task Error:', error);
                                                });
                                            ">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                </template>
                                
                                <template x-if="filteredTasks.length === 0">
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center"> 
                                            No tasks found matching filters.
                                        </td>
                                    </tr>
                                </template>

                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>

        <div 
            x-show="statusModalOpen" 
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
                @click.away="statusModalOpen = false" 
                x-show="statusModalOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-90"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-90"
                class="bg-white rounded-lg shadow-xl w-full max-w-md p-6"
            >
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Manage Statuses</h3>
                    <button @click="statusModalOpen = false" class="text-gray-500 hover:text-gray-800">&times;</button>
                </div>

                <div class="mb-4">
                    <h4 class="font-medium mb-2">Your Statuses</h4>
                    <div class="space-y-2 max-h-40 overflow-y-auto">
                        <template x-if="isLoading && modalStatuses.length === 0">
                            <p class="text-sm text-gray-500">Loading statuses...</p>
                        </template>
                        <template x-if="!isLoading && modalStatuses.length === 0">
                            <p class="text-sm text-gray-500">You have no custom statuses.</p>
                        </template>
                        <template x-for="status in modalStatuses" :key="status.id">
                            <div class="p-2 rounded border border-gray-200">
                                <div x-show="editingStatusId === status.id" class="space-y-2">
                                    <input type="text" x-model="editStatusName" class="block w-full text-sm rounded-md border-gray-300 shadow-sm">
                                    <div class="flex items-center justify-between">
                                        <input type="color" x-model="editStatusColor" class="block w-1/3 h-8">
                                        <div class="flex space-x-2">
                                            <button @click="cancelEditing()" class="text-xs text-gray-600">Cancel</button>
                                            <button @click="updateModalStatus()" :disabled="isLoading" class="text-xs text-green-600 font-semibold">Save</button>
                                        </div>
                                    </div>
                                </div>
                                <div x-show="editingStatusId !== status.id" class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <span class="w-4 h-4 rounded-full mr-2" :style="{ backgroundColor: status.color }"></span>
                                        <span x-text="status.name"></span>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button @click="startEditing(status)" :disabled="isLoading" class="text-xs text-gray-500 hover:text-gray-800">Edit</button>
                                        <button @click="deleteModalStatus(status.id)" :disabled="isLoading" class="text-xs text-red-500 hover:text-red-800">Delete</button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <hr class="my-4">

                <h4 class="font-medium mb-2">Add New Status</h4>
                <form @submit.prevent="storeModalStatus()">
                    <div class="mb-3">
                        <label for="status_name" class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" id="status_name" x-model="newStatusName" :disabled="isLoading" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
                    </div>
                    <div class="mb-3">
                        <label for="status_color" class="block text-sm font-medium text-gray-700">Color</label>
                        <input type="color" id="status_color" x-model="newStatusColor" :disabled="isLoading" class="mt-1 block w-full h-10">
                    </div>
                    <button type="submit" :disabled="isLoading" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50">
                        <span x-show="!isLoading">Add Status</span>
                        <span x-show="isLoading">Saving...</span>
                    </button>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>