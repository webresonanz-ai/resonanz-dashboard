<script setup>
import { ref, computed } from 'vue'
import { useRouter, useRoute, RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { Music2, Mail, Lock, Eye, EyeOff, ArrowRight, AlertCircle, Loader2 } from 'lucide-vue-next'

const router = useRouter()
const route  = useRoute()
const auth   = useAuthStore()

// ─── Form state ───────────────────────────────────────────────
const email    = ref('')
const password = ref('')
const showPw   = ref(false)
const loading  = ref(false)
const apiError = ref('')

// ─── Field-level errors ───────────────────────────────────────
const emailTouched    = ref(false)
const passwordTouched = ref(false)

const emailError = computed(() => {
  if (!emailTouched.value) return ''
  if (!email.value) return 'Email is required.'
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) return 'Enter a valid email address.'
  return ''
})

const passwordError = computed(() => {
  if (!passwordTouched.value) return ''
  if (!password.value) return 'Password is required.'
  return ''
})

const isValid = computed(() => !emailError.value && !passwordError.value && email.value && password.value)

// ─── Submit ───────────────────────────────────────────────────
async function handleSubmit() {
  emailTouched.value = true
  passwordTouched.value = true

  if (!isValid.value) return

  loading.value  = true
  apiError.value = ''

  try {
    await auth.login(email.value, password.value)
    const redirect = route.query.redirect
    router.push(redirect ? decodeURIComponent(String(redirect)) : { name: 'dashboard' })
  } catch (err) {
    apiError.value = err.message ?? 'Login failed. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center px-4 py-16 relative">

    <!-- ─── Ambient decorations ─── -->
    <div class="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
      <div class="absolute top-1/4 -left-32 w-96 h-96 rounded-full blur-3xl opacity-30"
           style="background: radial-gradient(circle, rgba(255,197,32,0.12), transparent 70%)"></div>
      <div class="absolute bottom-1/4 -right-32 w-96 h-96 rounded-full blur-3xl opacity-20"
           style="background: radial-gradient(circle, rgba(122,30,61,0.3), transparent 70%)"></div>
    </div>

    <div class="w-full max-w-md relative z-10">

      <!-- ─── Logo + heading ─── -->
      <div class="text-center mb-8">
        <RouterLink to="/" class="inline-flex flex-col items-center gap-3 group">
          <div class="w-16 h-16 rounded-2xl bg-gold-gradient flex items-center justify-center
                      shadow-gold group-hover:shadow-gold-lg group-hover:scale-110
                      group-hover:rotate-6 transition-all duration-300">
            <Music2 class="w-8 h-8 text-maroon-950" stroke-width="2.5" />
          </div>
          <span class="font-serif text-2xl font-bold gold-shimmer">Resonanz</span>
        </RouterLink>

        <h1 class="mt-5 text-3xl font-bold text-white">Welcome back</h1>
        <p class="mt-1.5 text-gray-400 text-sm">Sign in to your Resonanz account</p>
      </div>

      <!-- ─── Card ─── -->
      <div class="glass-card p-8 relative overflow-hidden animated-border">
        <!-- Shimmer line at top -->
        <div class="absolute top-0 left-0 h-px w-full"
             style="background: linear-gradient(90deg, transparent, rgba(255,197,32,0.4), transparent);
                    animation: shimmer 4s linear infinite;"></div>

        <!-- API error banner -->
        <transition name="fade-down">
          <div v-if="apiError"
               class="flex items-start gap-3 px-4 py-3 mb-5 rounded-xl
                      bg-red-500/10 border border-red-500/30 text-red-400 text-sm">
            <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
            <span>{{ apiError }}</span>
          </div>
        </transition>

        <form @submit.prevent="handleSubmit" class="space-y-5" novalidate>

          <!-- Email -->
          <div>
            <label for="email" class="block text-sm font-medium text-gray-300 mb-2">
              Email address
            </label>
            <div class="relative group">
              <div class="absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none
                          transition-colors duration-300"
                   :class="emailError ? 'text-red-400' : 'text-gold-500/60 group-focus-within:text-gold-400'">
                <Mail class="w-4.5 h-4.5 w-[18px] h-[18px]" />
              </div>
              <input
                id="email"
                v-model="email"
                type="email"
                autocomplete="email"
                placeholder="you@email.com"
                @blur="emailTouched = true"
                class="w-full pl-10 pr-4 py-3 bg-maroon-950/60 rounded-xl text-white
                       placeholder-gray-500 transition-all duration-300 outline-none
                       border focus:shadow-gold"
                :class="emailError
                  ? 'border-red-500/60 focus:border-red-400'
                  : 'border-gold-500/20 focus:border-gold-400 hover:border-gold-500/40'"
              />
            </div>
            <transition name="err">
              <p v-if="emailError" class="mt-1.5 text-xs text-red-400 flex items-center gap-1">
                <AlertCircle class="w-3 h-3 shrink-0" />{{ emailError }}
              </p>
            </transition>
          </div>

          <!-- Password -->
          <div>
            <div class="flex items-center justify-between mb-2">
              <label for="password" class="block text-sm font-medium text-gray-300">Password</label>
              <a href="#" class="text-xs text-gold-400 hover:text-gold-300 transition-colors">
                Forgot password?
              </a>
            </div>
            <div class="relative group">
              <div class="absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none
                          transition-colors duration-300"
                   :class="passwordError ? 'text-red-400' : 'text-gold-500/60 group-focus-within:text-gold-400'">
                <Lock class="w-[18px] h-[18px]" />
              </div>
              <input
                id="password"
                v-model="password"
                :type="showPw ? 'text' : 'password'"
                autocomplete="current-password"
                placeholder="Your password"
                @blur="passwordTouched = true"
                class="w-full pl-10 pr-11 py-3 bg-maroon-950/60 rounded-xl text-white
                       placeholder-gray-500 transition-all duration-300 outline-none
                       border focus:shadow-gold"
                :class="passwordError
                  ? 'border-red-500/60 focus:border-red-400'
                  : 'border-gold-500/20 focus:border-gold-400 hover:border-gold-500/40'"
              />
              <button
                type="button"
                @click="showPw = !showPw"
                class="absolute right-3.5 top-1/2 -translate-y-1/2
                       text-gold-500/50 hover:text-gold-400 transition-colors p-0.5"
                :aria-label="showPw ? 'Hide password' : 'Show password'"
              >
                <EyeOff v-if="showPw" class="w-[18px] h-[18px]" />
                <Eye    v-else        class="w-[18px] h-[18px]" />
              </button>
            </div>
            <transition name="err">
              <p v-if="passwordError" class="mt-1.5 text-xs text-red-400 flex items-center gap-1">
                <AlertCircle class="w-3 h-3 shrink-0" />{{ passwordError }}
              </p>
            </transition>
          </div>

          <!-- Submit -->
          <button
            type="submit"
            :disabled="loading"
            class="w-full inline-flex items-center justify-center gap-2 py-3.5
                   bg-gold-gradient text-maroon-950 font-bold rounded-xl
                   shadow-gold btn-magnetic relative overflow-hidden group
                   disabled:opacity-70 disabled:cursor-not-allowed mt-2"
          >
            <span class="absolute inset-0 -translate-x-full group-hover:translate-x-full
                         bg-gradient-to-r from-transparent via-white/30 to-transparent
                         transition-transform duration-500 ease-in-out"></span>
            <transition name="icon-swap" mode="out-in">
              <span v-if="loading" key="loading" class="flex items-center gap-2 relative">
                <Loader2 class="w-4 h-4 animate-spin" /> Signing in…
              </span>
              <span v-else key="idle" class="flex items-center gap-2 relative">
                Sign In <ArrowRight class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" />
              </span>
            </transition>
          </button>

        </form>

        <!-- Divider -->
        <div class="flex items-center gap-3 my-6">
          <div class="flex-1 h-px bg-gold-500/15"></div>
          <span class="text-xs text-gray-500">New to Resonanz?</span>
          <div class="flex-1 h-px bg-gold-500/15"></div>
        </div>

        <!-- Register link -->
        <RouterLink
          to="/register"
          class="flex items-center justify-center gap-2 w-full py-3 rounded-xl
                 border border-gold-500/30 text-gold-400 font-medium text-sm
                 hover:bg-gold-500/8 hover:border-gold-500/60 transition-all duration-300
                 btn-magnetic group"
        >
          Create an account
          <ArrowRight class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" />
        </RouterLink>
      </div>

      <!-- Back link -->
      <p class="text-center mt-6 text-sm text-gray-500">
        <RouterLink to="/" class="hover:text-gold-400 transition-colors">
          ← Back to home
        </RouterLink>
      </p>

    </div>
  </div>
</template>

<style scoped>
.fade-down-enter-active { transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
.fade-down-leave-active { transition: all 0.2s ease; }
.fade-down-enter-from   { opacity: 0; transform: translateY(-8px); }
.fade-down-leave-to     { opacity: 0; transform: translateY(-4px); }

.err-enter-active { transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1); }
.err-leave-active { transition: all 0.15s ease; }
.err-enter-from   { opacity: 0; transform: translateY(-4px); }
.err-leave-to     { opacity: 0; }

.icon-swap-enter-active, .icon-swap-leave-active { transition: all 0.2s ease; }
.icon-swap-enter-from { opacity: 0; transform: translateY(6px); }
.icon-swap-leave-to   { opacity: 0; transform: translateY(-6px); }
</style>
