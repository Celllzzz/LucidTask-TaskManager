<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $team->name }} {{ __('Workspace') }}
            </h2>
            <span class="px-3 py-1 text-xs font-semibold rounded-full {{ $team->owner_id === auth()->id() ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-800' }}">
                {{ $team->owner_id === auth()->id() ? 'Leader' : 'Member' }}
            </span>
        </div>
    </x-slot>

    <div class="py-12" x-data="{ 
        inviteModalOpen: false, 
        taskModalOpen: false,
        
        // State untuk Create Role Logic
        isCreatingRole: false, // Apakah sedang membuka form role baru?
        teamRoles: {{ $team->roles->toJson() }}, // Daftar role tim saat ini
        newRoleName: '',
        selectedPermissions: [],
        
        // Fungsi Create Role via AJAX
        async createRole() {
            if (!this.newRoleName || this.selectedPermissions.length === 0) {
                alert('Please enter role name and select at least one permission.');
                return;
            }

            try {
                const response = await fetch('{{ route('teams.roles.store', $team) }}', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json', 
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ 
                        name: this.newRoleName, 
                        permissions: this.selectedPermissions 
                    })
                });

                if (!response.ok) throw new Error('Failed to create role');
                
                const newRole = await response.json();
                
                // Tambahkan role baru ke list & pilih otomatis
                this.teamRoles.push(newRole);
                
                // Reset Form & Kembali ke Invite
                this.newRoleName = '';
                this.selectedPermissions = [];
                this.isCreatingRole = false; 
                
                // Set dropdown invite ke role baru (opsional logic)
                // document.getElementById('role_id').value = newRole.id; 

            } catch (error) {
                console.error(error);
                alert('Failed to create role.');
            }
        }
    }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg">
                    <ul class="list-disc pl-5">
                        @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold text-lg">Team Members</h3>
                        @if($team->owner_id === auth()->id())
                            <button @click="inviteModalOpen = true" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">
                                + Invite Member
                            </button>
                        @endif
                    </div>
                    <div class="overflow-x-auto border border-gray-200 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($team->members as $member)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="h-8 w-8 rounded-full bg-indigo-500 flex items-center justify-center text-white font-bold mr-3">
                                                    {{ substr($member->name, 0, 1) }}
                                                </div>
                                                <div class="text-sm font-medium text-gray-900">{{ $member->name }}</div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            @php
                                                $roleId = $member->pivot->role_id;
                                               
                                                $roleName = $team->roles->firstWhere('id', $roleId)->name ?? 'Unknown';
                                            @endphp

                                            @if($team->owner_id === $member->id)
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    {{ $roleName }} (Owner)
                                                </span>
                                            @else
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                    {{ $roleName }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $member->pivot->created_at->format('d M Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            @if($team->owner_id === auth()->id() && $member->id !== auth()->id())
                                                <form action="{{ route('teams.members.destroy', [$team, $member]) }}" method="POST" onsubmit="return confirm('Are you sure you want to remove this member?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900">Remove</button>
                                                </form>
                                            @endif
                                            
                                            @if($team->owner_id !== auth()->id() && $member->id === auth()->id())
                                                 <form action="{{ route('teams.members.destroy', [$team, $member]) }}" method="POST" onsubmit="return confirm('Leave this team?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900">Leave Team</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg"
                 x-data="{
                    tasks: {{ $teamTasks->toJson() }},
                    statuses: {{ $teamStatuses->toJson() }},
                    editingTaskId: null,
                    editingStatusId: null,

                    // Fungsi Update Status
                    async updateStatus(task) {
                        // Jangan update jika tidak berubah
                        if (task.status_id == this.editingStatusId) {
                            this.editingTaskId = null;
                            return;
                        }

                        try {
                            const response = await fetch(`/teams/tasks/${task.id}`, {
                                method: 'PUT',
                                headers: { 
                                    'Content-Type': 'application/json', 
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({ status_id: this.editingStatusId })
                            });

                            if (!response.ok) throw new Error('Failed');

                            const updatedTask = await response.json();
                            
                            // Update data lokal
                            const index = this.tasks.findIndex(t => t.id === task.id);
                            if (index !== -1) {
                                // Kita update status task secara manual agar reaktif
                                this.tasks[index].status_id = updatedTask.status_id;
                                this.tasks[index].status = updatedTask.status; // Update relasi status (warna/nama)
                            }
                            
                        } catch (error) {
                            alert('Failed to update status.');
                            console.error(error);
                        }
                        this.editingTaskId = null;
                    }
                 }"
            >
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold text-lg">Team Tasks</h3>
                        <button @click="taskModalOpen = true" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            Add Team Task
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto border border-gray-200 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Task Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deadline</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status (Edit)</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="task in tasks" :key="task.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="task.name"></td>
                                        
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div class="flex items-center">
                                                <template x-if="task.assignee">
                                                    <div class="flex items-center">
                                                        <div class="h-6 w-6 rounded-full bg-blue-400 flex items-center justify-center text-white text-xs font-bold mr-2" x-text="task.assignee.name.charAt(0)"></div>
                                                        <span x-text="task.assignee.name"></span>
                                                    </div>
                                                </template>
                                                <template x-if="!task.assignee">
                                                    <span class="italic text-gray-400">Unassigned</span>
                                                </template>
                                            </div>
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span x-text="task.deadline ? new Date(task.deadline).toLocaleDateString('en-GB', {day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute:'2-digit'}) : '-'"></span>
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span x-show="editingTaskId !== task.id" 
                                                  @click="editingTaskId = task.id; editingStatusId = task.status_id"
                                                  class="text-sm cursor-pointer px-2 inline-flex leading-5 font-semibold rounded-full border border-transparent hover:border-gray-300"
                                                  :style="{ 
                                                      backgroundColor: task.status ? task.status.color + '20' : '#e5e7eb', 
                                                      color: task.status ? task.status.color : '#374151' 
                                                  }"
                                                  x-text="task.status ? task.status.name : 'No Status'">
                                            </span>
                                            
                                            <select x-show="editingTaskId === task.id"
                                                    x-model="editingStatusId"
                                                    @change="updateStatus(task)"
                                                    @click.away="editingTaskId = null"
                                                    class="text-sm form-select rounded-md border-gray-300 py-1 pl-2 pr-8"
                                                    x-init="$nextTick(() => { if(editingTaskId === task.id) $el.focus() })">
                                                <option value="">-- No Status --</option>
                                                <template x-for="status in statuses" :key="status.id">
                                                    <option :value="status.id" x-text="status.name" :selected="status.id == task.status_id"></option>
                                                </template>
                                            </select>
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            @if($team->owner_id === auth()->id())
                                                <button @click="
                                                    if(confirm('Delete task?')) {
                                                        fetch(`/teams/tasks/${task.id}`, {
                                                            method: 'DELETE',
                                                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                                                        }).then(res => {
                                                            if(res.ok) tasks = tasks.filter(t => t.id !== task.id);
                                                        });
                                                    }
                                                " class="text-red-600 hover:text-red-900">Delete</button>
                                            @endif
                                        </td>
                                    </tr>
                                </template>
                                
                                <template x-if="tasks.length === 0">
                                    <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">No tasks found for you.</td></tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="inviteModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-75" style="display: none;" x-transition.opacity>
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
                
                <h3 class="text-lg font-semibold mb-4" x-text="isCreatingRole ? 'Create New Role' : 'Invite New Member'"></h3>
                
                <div x-show="!isCreatingRole">
                    <form action="{{ route('teams.members.store', $team) }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">User Email</label>
                            <input type="email" name="email" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Assign Role</label>
                            <div class="flex gap-2">
                                <select name="role_id" id="role_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
                                    <template x-for="role in teamRoles" :key="role.id">
                                        <option :value="role.id" x-text="role.name"></option>
                                    </template>
                                </select>
                                <button type="button" @click="isCreatingRole = true" class="mt-1 px-3 py-2 border border-gray-300 rounded-md text-sm bg-gray-50 hover:bg-gray-100 text-gray-600">
                                    + New
                                </button>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-2">
                            <button type="button" @click="inviteModalOpen = false" class="py-2 px-4 text-sm text-gray-700 bg-gray-100 rounded-md">Cancel</button>
                            <button type="submit" class="py-2 px-4 text-sm text-white bg-indigo-600 rounded-md">Invite</button>
                        </div>
                    </form>
                </div>

                <div x-show="isCreatingRole">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Role Name</label>
                        <input type="text" x-model="newRoleName" placeholder="e.g. Designer, QA" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Permissions</label>
                        <div class="h-32 overflow-y-auto border border-gray-200 rounded p-2 space-y-2">
                            @foreach($availablePermissions as $perm)
                                <label class="flex items-center space-x-2 text-sm">
                                    <input type="checkbox" value="{{ $perm->id }}" x-model="selectedPermissions" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    <span>{{ $perm->description ?? $perm->slug }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex justify-end space-x-2">
                        <button type="button" @click="isCreatingRole = false" class="py-2 px-4 text-sm text-gray-700 bg-gray-100 rounded-md">Back</button>
                        <button type="button" @click="createRole()" class="py-2 px-4 text-sm text-white bg-green-600 rounded-md hover:bg-green-700">Save Role</button>
                    </div>
                </div>

            </div>
        </div>

        <div x-show="taskModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-75" style="display: none;" x-transition.opacity>
            <div @click.away="taskModalOpen = false" class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
                <h3 class="text-lg font-semibold mb-4">Create Team Task</h3>
                <form action="{{ route('teams.tasks.store', $team) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Task Name</label>
                        <input type="text" name="name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Assign To</label>
                        <select name="assigned_to_user_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
                            <option value="">-- Unassigned --</option>
                            @foreach($team->members as $member) <option value="{{ $member->id }}">{{ $member->name }}</option> @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Deadline</label>
                        <input type="datetime-local" name="deadline" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
                    </div>
                    <div class="flex justify-end space-x-2 mt-4">
                        <button type="button" @click="taskModalOpen = false" class="py-2 px-4 text-sm text-gray-700 bg-gray-100 rounded-md">Cancel</button>
                        <button type="submit" class="py-2 px-4 text-sm text-white bg-indigo-600 rounded-md">Create Task</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>