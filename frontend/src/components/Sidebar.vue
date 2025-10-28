<template>
  <div class="sidebar">
    <div class="sidebar-header">
      <h5 class="mb-0">{{ title }}</h5>
    </div>

    <ul class="sidebar-menu">
      <li
        v-for="item in menuItems"
        :key="item.name"
        :class="{ active: activeSection === item.name }"
        @click="selectSection(item.name)"
      >
        <i :class="item.icon" class="me-2"></i>
        {{ item.label }}
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

interface MenuItem {
  name: string;
  label: string;
  icon: string;
}

interface Props {
  title: string;
  menuItems: MenuItem[];
  modelValue: string;
}

const props = defineProps<Props>();
const emit = defineEmits(['update:modelValue']);

const activeSection = ref(props.modelValue);

const selectSection = (section: string) => {
  activeSection.value = section;
  emit('update:modelValue', section);
};
</script>

<style scoped>
.sidebar {
  width: 250px;
  background: #1e4449;
  min-height: calc(100vh - 56px);
  color: white;
}

.sidebar-header {
  padding: 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  color: #fcc300;
}

.sidebar-menu {
  list-style: none;
  padding: 0;
  margin: 0;
}

.sidebar-menu li {
  padding: 15px 20px;
  cursor: pointer;
  transition: all 0.3s ease;
  border-left: 3px solid transparent;
}

.sidebar-menu li:hover {
  background: rgba(255, 255, 255, 0.1);
  border-left-color: #26d516;
}

.sidebar-menu li.active {
  background: rgba(38, 213, 22, 0.2);
  border-left-color: #26d516;
  color: #26d516;
}

.sidebar-menu li i {
  width: 20px;
}
</style>
