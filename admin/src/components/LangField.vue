<script setup>
defineProps({
  label: { type: String, required: true },
  multiline: { type: Boolean, default: false },
  rows: { type: Number, default: 3 },
  placeholderEn: { type: String, default: '' },
  placeholderId: { type: String, default: '' },
})

const model = defineModel({ default: () => ({ en: '', id: '' }) })
</script>

<template>
  <div>
    <label class="form-label">{{ label }}</label>
    <div class="grid sm:grid-cols-2 gap-3">
      <div>
        <textarea
          v-if="multiline"
          v-model="model.en"
          :rows="rows"
          class="form-input resize-none"
          :placeholder="placeholderEn || `${label} (EN)`"
        />
        <input
          v-else
          v-model="model.en"
          class="form-input"
          :placeholder="placeholderEn || `${label} (EN)`"
        />
        <p class="text-[11px] text-gray-500 mt-1">English</p>
      </div>
      <div>
        <textarea
          v-if="multiline"
          v-model="model.id"
          :rows="rows"
          class="form-input resize-none"
          :placeholder="placeholderId || `${label} (ID)`"
        />
        <input
          v-else
          v-model="model.id"
          class="form-input"
          :placeholder="placeholderId || `${label} (ID)`"
        />
        <p class="text-[11px] text-gray-500 mt-1">Indonesia</p>
      </div>
    </div>
  </div>
</template>
