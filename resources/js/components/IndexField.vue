<template>
  <span class="text-sm text-gray-600 dark:text-gray-400">
    {{ field.indexValue || '0 permissões' }}
  </span>
</template>

<script>
export default {
  props: ['resourceName', 'field'],
}
</script>
