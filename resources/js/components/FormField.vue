<template>
  <DefaultField
    :field="currentField"
    :errors="errors"
    :show-help-text="showHelpText"
    :full-width-content="fullWidthContent"
  >
    <template #field>
      <div v-if="!items || items.length === 0" class="text-gray-500 text-sm">
        Nenhum item disponível
      </div>

      <!-- Grouped mode -->
      <div v-else-if="isGrouped" class="space-y-4">
        <div
          v-for="(group, groupIndex) in items"
          :key="groupIndex"
          class="border border-gray-200 dark:border-gray-700 rounded-lg p-3"
        >
          <label
            class="flex items-center gap-2 mb-2 cursor-pointer font-semibold text-sm text-gray-700 dark:text-gray-300"
          >
            <input
              type="checkbox"
              :checked="getSelectAllState(group.items) === 'checked'"
              :indeterminate.prop="getSelectAllState(group.items) === 'indeterminate'"
              class="checkbox"
              @change="toggleSelectAll(group.items, $event.target.checked)"
            />
            {{ group.label }}
            <span class="text-xs text-gray-400 font-normal">
              ({{ countSelected(group.items) }}/{{ group.items.length }})
            </span>
          </label>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-1 ml-5">
            <label
              v-for="item in group.items"
              :key="item.id"
              class="flex items-center gap-2 cursor-pointer text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200"
            >
              <input
                type="checkbox"
                :value="item.id"
                :checked="selectedIds.has(item.id)"
                class="checkbox"
                @change="toggleItem(item.id, $event.target.checked)"
              />
              {{ item.label }}
            </label>
          </div>
        </div>
      </div>

      <!-- Flat mode (no grouping) -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-1">
        <label
          v-for="item in items"
          :key="item.id"
          class="flex items-center gap-2 cursor-pointer text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200"
        >
          <input
            type="checkbox"
            :value="item.id"
            :checked="selectedIds.has(item.id)"
            class="checkbox"
            @change="toggleItem(item.id, $event.target.checked)"
          />
          {{ item.label }}
        </label>
      </div>
    </template>
  </DefaultField>
</template>

<script>
import { DependentFormField, HandlesValidationErrors } from 'laravel-nova'

export default {
  mixins: [DependentFormField, HandlesValidationErrors],

  props: ['resourceName', 'resourceId', 'field'],

  data() {
    return {
      selectedIds: new Set(),
    }
  },

  computed: {
    isGrouped() {
      return this.currentField.grouped !== false
    },

    items() {
      return this.currentField.groups || []
    },
  },

  methods: {
    setInitialValue() {
      const ids = this.currentField.value || []
      this.selectedIds = new Set(ids.map(Number))
    },

    fill(formData) {
      formData.append(
        this.fieldAttribute,
        JSON.stringify(Array.from(this.selectedIds))
      )
    },

    toggleItem(id, checked) {
      if (checked) {
        this.selectedIds.add(id)
      } else {
        this.selectedIds.delete(id)
      }
      this.selectedIds = new Set(this.selectedIds)
    },

    toggleSelectAll(items, select) {
      for (const item of items) {
        if (select) {
          this.selectedIds.add(item.id)
        } else {
          this.selectedIds.delete(item.id)
        }
      }
      this.selectedIds = new Set(this.selectedIds)
    },

    getSelectAllState(items) {
      if (!items || items.length === 0) return 'unchecked'
      const selectedCount = items.filter(item => this.selectedIds.has(item.id)).length
      if (selectedCount === 0) return 'unchecked'
      if (selectedCount === items.length) return 'checked'
      return 'indeterminate'
    },

    countSelected(items) {
      return items.filter(item => this.selectedIds.has(item.id)).length
    },
  },
}
</script>
