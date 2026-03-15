<template>
  <PanelItem :index="index" :field="field">
    <template #value>
      <div v-if="selectedItems.length === 0" class="text-gray-400 text-sm">
        Nenhum item selecionado
      </div>

      <!-- Grouped mode -->
      <div v-else-if="isGrouped" class="space-y-3">
        <div
          v-for="(group, groupIndex) in filteredGroups"
          :key="groupIndex"
        >
          <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-200 uppercase tracking-wide mb-1">
            {{ group.label }}
          </h4>
          <div class="flex flex-wrap gap-1">
            <span
              v-for="item in group.selectedItems"
              :key="item.id"
              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-primary-50 text-primary-700 dark:bg-gray-700 dark:text-gray-100"
            >
              {{ item.label }}
            </span>
          </div>
        </div>
      </div>

      <!-- Flat mode -->
      <div v-else class="flex flex-wrap gap-1">
        <span
          v-for="item in selectedItems"
          :key="item.id"
          class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-primary-50 text-primary-700 dark:bg-gray-700 dark:text-gray-100"
        >
          {{ item.label }}
        </span>
      </div>
    </template>
  </PanelItem>
</template>

<script>
export default {
  props: ['index', 'resource', 'resourceName', 'resourceId', 'field'],

  computed: {
    isGrouped() {
      return this.field.grouped !== false
    },

    selectedIdSet() {
      return new Set((this.field.value || []).map(Number))
    },

    filteredGroups() {
      const groups = this.field.groups || []
      return groups
        .map(group => ({
          label: group.label,
          selectedItems: group.items.filter(item => this.selectedIdSet.has(item.id)),
        }))
        .filter(group => group.selectedItems.length > 0)
    },

    selectedItems() {
      if (this.isGrouped) {
        return this.filteredGroups.flatMap(g => g.selectedItems)
      }
      const items = this.field.groups || []
      return items.filter(item => this.selectedIdSet.has(item.id))
    },
  },
}
</script>
