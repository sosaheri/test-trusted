# Revisión Previa y Validación — Funcionalidad 03 (Frontend)

## 1. Análisis del Componente Legacy (`LegacyProductRow.vue`)
- [ ] Inspeccionar las `props` definidas en `LegacyProductRow.vue`.
- [ ] Inspeccionar los eventos declarados en `emits`.
- [ ] Planificar la equivalencia directa a `<script setup>` usando `defineProps` y `defineEmits`.

## 2. Propuesta Técnica de la Pantalla Maestra
- [ ] **Estrategia de Polling:** Definir intervalo de consulta para `GET /api/import-runs/{id}` (ej. 2 segundos) mientras el estado sea `pending` o `validating`[cite: 4].
- [ ] **Tabla Server-Side:** Configuración de `v-data-table-server` enlazada con `GET /api/products` enviando `page`, `itemsPerPage`, `sortBy` y `search`[cite: 4].
- [ ] **Debounce en Búsqueda:** Implementar delay (300ms) en la caja de búsqueda para evitar saturación de peticiones HTTP al servidor.

## 3. Checklist de Transición
- [ ] Justificar la frecuencia de polling elegida en `.agents/DOCS/DECISIONS.md`[cite: 4].
- [ ] Iniciar la Fase 3 (Ejecución de Código).