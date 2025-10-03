<script setup>
import '../assets/tailwind.css'
import { ref, onMounted, computed } from 'vue'
import * as tasksService from '../services/tasks'

const tasks = ref([])
const loading = ref(false)
const error = ref(null)

// create form
const formTitle = ref('')
const formBody = ref('')
// filter helpers
const statusOf = (t) => Number(t?.status ?? 1)
const pendingTasks = computed(() => tasks.value.filter(t => statusOf(t) === 1))
const progressTasks = computed(() => tasks.value.filter(t => statusOf(t) === 2))
const doneTasks = computed(() => tasks.value.filter(t => statusOf(t) === 3))
const selectedTab = ref('board') // 'board' | 1 | 2 | 3

function setTab(tab) {
  selectedTab.value = tab
}

// edit modal
const isEditOpen = ref(false)
const editTask = ref(null)
const editTitle = ref('')
const editBody = ref('')

// delete confirm
const isDeleteOpen = ref(false)
const deleteTaskRef = ref(null)

async function loadTasks() {
  loading.value = true
  error.value = null
  try {
    const res = await tasksService.fetchTasks()
    tasks.value = Array.isArray(res.data) ? res.data : []
  } catch (err) {
    error.value = err.message || String(err)
  } finally {
    loading.value = false
  }
}

async function createTask() {
  const title = formTitle.value && formTitle.value.trim()
  const body = formBody.value && formBody.value.trim()
  if (!title) return

  try {
    const payload = { title: title, body: body }
    const created = await tasksService.createTask(payload)
    loadTasks();
    formTitle.value = ''
    formBody.value = ''
  } catch (err) {
    error.value = err.message || String(err)
  }
}

function openEdit(t) {
  editTask.value = t
  editTitle.value = t.title || ''
  editBody.value = t.body || ''
  isEditOpen.value = true
}

async function saveEdit() {
  if (!editTask.value) return
  const title = editTitle.value && editTitle.value.trim()
  const body = editBody.value && editBody.value.trim()
  if (!title) return
  try {
    const payload = { title: title, body: body}
    if (editTask.value._newStatus != null) payload.status = Number(editTask.value._newStatus)
    const updated = await tasksService.updateTask(editTask.value.id, payload)
    loadTasks()
    isEditOpen.value = false
    editTask.value = null
  } catch (err) {
    error.value = err.message || String(err)
  }
}

async function changeStatus(task, newStatus) {
  if (!task) return
  try {
    const payload = { status: Number(newStatus) }
    const updated = await tasksService.changeStatusTask(task.id, payload)
    loadTasks()
  } catch (err) {
    error.value = err.message || String(err)
  }
}

function moveLeft(task) {
  const cur = statusOf(task)
  if (cur > 1) changeStatus(task, cur - 1)
}

function moveRight(task) {
  const cur = statusOf(task)
  if (cur < 3) changeStatus(task, cur + 1)
}

function openDelete(t) {
  deleteTaskRef.value = t
  isDeleteOpen.value = true
}

async function confirmDelete() {
  if (!deleteTaskRef.value) return
  try {
    await tasksService.deleteTask(deleteTaskRef.value.id)
    tasks.value = tasks.value.filter(t => t.id !== deleteTaskRef.value.id)
    isDeleteOpen.value = false
    deleteTaskRef.value = null
  } catch (err) {
    error.value = err.message || String(err)
  }
}

onMounted(loadTasks)
</script>

<template>
<div class="container mx-auto p-4">
    <section class="max-w-3xl mx-auto p-4">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-semibold"> Add New Task</h2>
        <div v-if="loading" class="text-sm text-gray-500">Loading…</div>
      </div>
      <div v-if="error" class="mb-4 text-red-600">Error: {{ error }}</div>
      <form @submit.prevent="createTask" class="mb-6 grid grid-cols-1 gap-2">
        <input v-model="formTitle" placeholder="Title" class="col-span-3 p-2 border rounded" />
        <textarea v-model="formBody" placeholder="Body (optional)" class="col-span-3 p-2 border rounded" ></textarea>
        <div class="sm:col-span-3 flex gap-2">
          <button type="submit" class="p-2 bg-blue-600 text-white rounded">Create</button>
          <button type="button" @click="() => { formTitle=''; formBody=''; }" class="p-2 border rounded">Clear</button>
        </div>
      </form>
  <div class="mb-4 flex gap-2 items-center">
        <button :class="['px-3 py-1 mx-5 rounded-full', selectedTab === 'board' ? 'bg-black' : '']" @click.prevent="setTab('board')">Board</button>
        <button :class="['px-3 py-1 mx-5 rounded-full', selectedTab == 1 ? 'bg-black' : '']" @click.prevent="setTab(1)">Pending</button>
        <button :class="['px-3 py-1 mx-5 rounded-full', selectedTab == 2 ? 'bg-black' : '']" @click.prevent="setTab(2)">In Progress</button>
        <button :class="['px-3 py-1 mx-5 rounded-full', selectedTab == 3 ? 'bg-black' : '']" @click.prevent="setTab(3)">Done</button>
      </div>
      <div v-if="selectedTab === 'board'" class="grid grid-cols-3 sm:grid-cols-3 gap-4">
        <div>
          <h3 class="font-semibold mb-2">Pending</h3>
          <div class="space-y-3  h-full max-h-100 overflow-y-scroll ">
            <div v-for="task in pendingTasks" :key="task.id" class="p-4 bg-white dark:bg-gray-800 rounded shadow-sm">
              <div class="grid grid-cols-1 gap-2">
                <div>
                  <div class="font-medium text-lg">{{ task.title }}</div>
                  <div class="text-sm text-gray-600 mt-1">{{ task.body }}</div>
                </div>
                <div class="flex gap-2 mt-5">
                  <button @click.prevent="moveRight(task)" class="px-2 py-1 border rounded text-sm">→</button>
                  <button @click.prevent="openEdit(task)" class="px-2 py-1 border rounded text-sm">Edit</button>
                  <button @click.prevent="openDelete(task)" class="px-2 py-1 border rounded text-sm text-red-600">Del</button>
                </div>
              </div>
            </div>
          </div>
        </div>
  
        <div>
          <h3 class="font-semibold mb-2">In Progress</h3>
          <div class="space-y-3 h-full max-h-100 overflow-y-scroll ">
            <div v-for="task in progressTasks" :key="task.id" class="p-4 bg-white dark:bg-gray-800 rounded shadow-sm">
              <div class="grid grid-cols-1 gap-2">
                <div>
                  <div class="font-medium text-lg">{{ task.title }}</div>
                  <div class="text-sm text-gray-600 mt-1">{{ task.body }}</div>
                </div>
                <div class="flex gap-2 mt-5">
                  <button @click.prevent="moveLeft(task)" class="px-2 py-1 border rounded text-sm">←</button>
                  <button @click.prevent="moveRight(task)" class="px-2 py-1 border rounded text-sm">→</button>
                  <button @click.prevent="openEdit(task)" class="px-2 py-1 border rounded text-sm">Edit</button>
                  <button @click.prevent="openDelete(task)" class="px-2 py-1 border rounded text-sm text-red-600">Del</button>
                </div>
              </div>
            </div>
          </div>
        </div>
  
        <div>
          <h3 class="font-semibold mb-2">Done</h3>
          <div class="space-y-3 h-full max-h-100 overflow-y-scroll ">
            <div v-for="task in doneTasks" :key="task.id" class="p-4 bg-white dark:bg-gray-800 rounded shadow-sm">
              <div class="grid grid-cols-1 gap-2">
                <div>
                  <div class="font-medium text-lg line-through text-gray-500">{{ task.title }}</div>
                  <div class="text-sm text-gray-600 mt-1">{{ task.body }}</div>
                </div>
                <div class="flex gap-2 mt-5">
                  <button @click.prevent="moveLeft(task)" class="px-2 py-1 border rounded text-sm">←</button>
                  <button @click.prevent="openEdit(task)" class="px-2 py-1 border rounded text-sm">Edit</button>
                  <button @click.prevent="openDelete(task)" class="px-2 py-1 border rounded text-sm text-red-600">Del</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
  
      <!-- single column view for selected status -->
      <div v-else class="space-y-3">
        <div v-if="selectedTab == 1">
          <h3 class="font-semibold mb-2">Pending</h3>
          <div class="space-y-3">
            <div v-for="task in pendingTasks" :key="task.id" class="p-4 bg-white dark:bg-gray-800 rounded shadow-sm">
              <div class="flex justify-between items-start">
                <div>
                  <div class="font-medium text-lg">{{ task.title }}</div>
                  <div class="text-sm text-gray-600 mt-1">{{ task.body }}</div>
                </div>
                <div class="flex gap-2">
                  <button @click.prevent="moveRight(task)" class="px-2 py-1 border rounded text-sm">→</button>
                  <button @click.prevent="openEdit(task)" class="px-2 py-1 border rounded text-sm">Edit</button>
                  <button @click.prevent="openDelete(task)" class="px-2 py-1 border rounded text-sm text-red-600">Del</button>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div v-if="selectedTab == 2">
          <h3 class="font-semibold mb-2">In Progress</h3>
          <div class="space-y-3">
            <div v-for="task in progressTasks" :key="task.id" class="p-4 bg-white dark:bg-gray-800 rounded shadow-sm">
              <div class="flex justify-between items-start">
                <div>
                  <div class="font-medium text-lg">{{ task.title }}</div>
                  <div class="text-sm text-gray-600 mt-1">{{ task.body }}</div>
                </div>
                <div class="flex gap-2">
                  <button @click.prevent="moveLeft(task)" class="px-2 py-1 border rounded text-sm">←</button>
                  <button @click.prevent="moveRight(task)" class="px-2 py-1 border rounded text-sm">→</button>
                  <button @click.prevent="openEdit(task)" class="px-2 py-1 border rounded text-sm">Edit</button>
                  <button @click.prevent="openDelete(task)" class="px-2 py-1 border rounded text-sm text-red-600">Del</button>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div v-if="selectedTab == 3">
          <h3 class="font-semibold mb-2">Done</h3>
          <div class="space-y-3">
            <div v-for="task in doneTasks" :key="task.id" class="p-4 bg-white dark:bg-gray-800 rounded shadow-sm">
              <div class="flex justify-between items-start">
                <div>
                  <div class="font-medium text-lg line-through text-gray-500">{{ task.title }}</div>
                  <div class="text-sm text-gray-600 mt-1">{{ task.body }}</div>
                </div>
                <div class="flex gap-2">
                  <button @click.prevent="moveLeft(task)" class="px-2 py-1 border rounded text-sm">←</button>
                  <button @click.prevent="openEdit(task)" class="px-2 py-1 border rounded text-sm">Edit</button>
                  <button @click.prevent="openDelete(task)" class="px-2 py-1 border rounded text-sm text-red-600">Del</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
  
      <!-- Edit Modal -->
      <div v-if="isEditOpen" class="modal-backdrop">
        <div class="card w-full max-w-xl">
          <h3 class="text-xl font-semibold mb-2">Edit Task</h3>
          <div class="grid grid-cols-1 gap-2">
            <input v-model="editTitle" placeholder="Title" class=" w-full appearance-none rounded-md bg-gray-800 py-1.5 pr-7 pl-3 text-base text-gray-400 *:bg-gray-800 placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6" />
            <textarea v-model="editBody" placeholder="Body" class=" w-full appearance-none rounded-md bg-gray-800 py-1.5 pr-7 pl-3 text-base text-gray-400 *:bg-gray-800 placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6"></textarea>
          </div>
          <div class="mt-3 flex justify-end gap-2">
            <button @click="() => { isEditOpen = false; editTask = null }" class="px-4 py-2 border rounded">Cancel</button>
            <button @click="saveEdit" class="px-4 py-2 bg-green-600 text-white rounded">Save</button>
          </div>
        </div>
      </div>
  
      <!-- Delete Confirm Modal -->
      <div v-if="isDeleteOpen" class="modal-backdrop">
        <div class="card max-w-md">
          <h3 class="text-lg font-semibold">Confirm Delete</h3>
          <p class="mt-2">Are you sure you want to delete <strong>{{ deleteTaskRef?.title }}</strong>?</p>
          <div class="mt-4 flex justify-end gap-2">
            <button @click="() => { isDeleteOpen = false; deleteTaskRef = null }" class="px-4 py-2 border rounded">Cancel</button>
            <button @click="confirmDelete" class="px-4 py-2 bg-red-600 text-white rounded">Delete</button>
          </div>
        </div>
      </div>
    </section>
</div>
</template>
