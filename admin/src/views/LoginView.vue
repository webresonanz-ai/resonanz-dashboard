<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { Music2, Mail, Lock, Eye, EyeOff, Loader2 } from 'lucide-vue-next'

const auth   = useAuthStore()
const router = useRouter()
const email    = ref('admin@resonanz.org')
const password = ref('')
const showPw   = ref(false)
const loading  = ref(false)
const error    = ref('')

async function submit() {
  error.value   = ''
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    router.push('/')
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-gray-950 flex items-center justify-center px-4">
    <!-- Background glow -->
    <div class="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
      <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-[600px] h-[300px] rounded-full blur-3xl opacity-20"
           style="background:radial-gradient(ellipse, rgba(255,197,32,0.4) 0%, transparent 70%)"></div>
    </div>

    <div class="w-full max-w-sm relative z-10">
      <!-- Logo -->
      <div class="text-center mb-8">
        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-gold-400 to-gold-600 mx-auto mb-4
                    flex items-center justify-center shadow-2xl shadow-gold-500/30">
          <Music2 class="w-8 h-8 text-gray-950" stroke-width="2.5" />
        </div>
        <h1 class="text-2xl font-serif font-bold text-white">Resonanz Admin</h1>
        <p class="text-sm text-gray-400 mt-1">Sign in to manage your content</p>
      </div>

      <!-- Card -->
      <div class="admin-card p-7">
        <!-- Error -->
        <transition name="slide-up">
          <div v-if="error" class="flex items-center gap-2 px-3.5 py-2.5 mb-4 rounded-xl
                                   bg-red-500/10 border border-red-500/25 text-red-400 text-sm">
            {{ error }}
          </div>
        </transition>

        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="form-label">Email</label>
            <div class="relative">
              <Mail class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none" />
              <input v-model="email" type="email" required placeholder="admin@resonanz.org"
                     class="form-input pl-10" autocomplete="email" />
            </div>
          </div>

          <div>
            <label class="form-label">Password</label>
            <div class="relative">
              <Lock class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none" />
              <input v-model="password" :type="showPw ? 'text' : 'password'" required
                     placeholder="••••••••" class="form-input pl-10 pr-10" autocomplete="current-password" />
              <button type="button" @click="showPw=!showPw"
                      class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300 transition-colors">
                <EyeOff v-if="showPw" class="w-4 h-4" /><Eye v-else class="w-4 h-4" />
              </button>
            </div>
          </div>

          <button type="submit" :disabled="loading" class="btn-primary w-full justify-center py-3 mt-2">
            <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
            {{ loading ? 'Signing in…' : 'Sign In' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>
