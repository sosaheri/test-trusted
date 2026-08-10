<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';

const products = ref([]);
const search = ref('');

watch(search, async () => {
  const { data } = await axios.get('/api/products?q=' + search.value);
  products.value = data;
});
</script>

<template>
  <v-text-field v-model="search" label="Buscar producto" />
  <v-data-table :items="products" :search="search" />
</template>
