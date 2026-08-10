<script setup>
import LegacyProductRow from './components/LegacyProductRow.vue';
</script>

<template>
  <v-app>
    <v-main>
      <v-container>
        <v-alert type="info" variant="tonal" class="mb-4">
          Placeholder del starter-kit. Construye aquí la pantalla maestra
          (§3.4): carga de CSV, tabla de catálogo server-side, drawer de
          resultados y la acción de <code>apply</code>.
        </v-alert>

        <!--
          LegacyProductRow.vue viene en Options API a propósito (§3.4,
          refactor obligatorio). Este uso de ejemplo NO forma parte del
          contrato de props/emits que debes preservar; bórralo cuando
          integres el componente real en tu tabla.
        -->
        <LegacyProductRow
          :product="{ id: 0, name: 'Ejemplo', sku: 'SKU-0000', price: 0, stock: 0 }"
        />
      </v-container>
    </v-main>
  </v-app>
</template>
