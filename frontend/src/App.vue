<script setup>
import { RouterView } from 'vue-router'
import NavBar from './components/NavBar.vue'
import FooterBar from './components/FooterBar.vue'
</script>

<template>
  <div class="min-h-screen flex flex-col bg-maroon-950 relative overflow-x-hidden">

    <!-- ─── Ambient aurora background ─── -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
      <!-- Main aurora blob top-right -->
      <div
        class="absolute -top-40 -right-40 w-[700px] h-[700px] rounded-full opacity-60"
        style="
          background: radial-gradient(circle at 60% 40%, rgba(255,197,32,0.08) 0%, rgba(122,30,61,0.12) 50%, transparent 75%);
          animation: aurora 20s ease-in-out infinite;
        "
      ></div>

      <!-- Secondary aurora blob bottom-left -->
      <div
        class="absolute -bottom-40 -left-40 w-[600px] h-[600px] rounded-full opacity-50"
        style="
          background: radial-gradient(circle at 40% 60%, rgba(122,30,61,0.25) 0%, rgba(255,197,32,0.05) 50%, transparent 70%);
          animation: aurora 26s ease-in-out 8s infinite reverse;
        "
      ></div>

      <!-- Subtle center glow -->
      <div
        class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[400px] rounded-full opacity-20"
        style="
          background: radial-gradient(ellipse at center, rgba(255,197,32,0.06) 0%, transparent 70%);
          animation: aurora 35s linear infinite;
        "
      ></div>

      <!-- Fine grid overlay for depth -->
      <div
        class="absolute inset-0 opacity-[0.025]"
        style="
          background-image:
            linear-gradient(rgba(255,197,32,0.4) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,197,32,0.4) 1px, transparent 1px);
          background-size: 80px 80px;
        "
      ></div>
    </div>

    <NavBar />

    <main class="flex-1 relative z-10">
      <RouterView v-slot="{ Component }">
        <transition name="page" mode="out-in">
          <component :is="Component" />
        </transition>
      </RouterView>
    </main>

    <FooterBar />
  </div>
</template>

<style>
/* ─── Page transition ─── */
.page-enter-active {
  transition: opacity 0.45s cubic-bezier(0.16, 1, 0.3, 1),
              transform 0.45s cubic-bezier(0.16, 1, 0.3, 1);
}
.page-leave-active {
  transition: opacity 0.25s cubic-bezier(0.7, 0, 1, 1),
              transform 0.25s cubic-bezier(0.7, 0, 1, 1);
}
.page-enter-from {
  opacity: 0;
  transform: translateY(16px) scale(0.99);
}
.page-leave-to {
  opacity: 0;
  transform: translateY(-8px) scale(1.01);
}
</style>
