# FUNCIONALIDAD 03: Pantalla Maestra B2B y Refactor de Componente Legacy

## 1. Descripción de Negocio
Construir una SPA de pantalla única en Vue 3 + Vuetify 3 que centralice todo el flujo de importación y gestión del catálogo de la empresa autenticada[cite: 4]. La interfaz debe permitir cargar archivos CSV, monitorear el progreso de la corrida en segundo plano, revisar el reporte de filas rechazadas en un drawer/diálogo y ejecutar la aplicación atómica (Fase B)[cite: 4]. Asimismo, incluye el refactor obligatorio del componente legacy de Options API a Composition API `<script setup>` manteniendo intacto su contrato de comunicación[cite: 3, 4].

## 2. Insumos Esperados (Inputs / Archivos a Consumir)
- **Componente a Refactorizar:** `resources/js/components/LegacyProductRow.vue` (Options API)[cite: 3].
- **Endpoints de la API:**
  - `POST /api/import-runs` (envío multipart/form-data)[cite: 2, 3].
  - `GET /api/import-runs/{id}` (polling de estado y reporte)[cite: 2, 3, 4].
  - `POST /api/import-runs/{id}/apply` (promoción a catálogo)[cite: 2, 3, 4].
  - `GET /api/products` (listado paginado, búsqueda y ordenamiento)[cite: 2, 3, 4].
- **Librerías UI:** Vuetify 3 (componentes `v-data-table-server`, `v-file-input`, `v-navigation-drawer`, `v-dialog`, `v-alert`, `v-progress-linear`).

## 3. Entregables y Emisiones (Outputs)
- **Frontend Refactorizado:**
  - `resources/js/components/LegacyProductRow.vue` migrado a `<script setup>` preservando intactas sus `props` (`product`) y sus `emits`[cite: 2, 3, 4].
- **Vista Maestra y Componentes:**
  - Vista principal en Vue 3 que integre la tabla del catálogo con paginación server-side, búsqueda debounced y feedback de progreso para las corridas[cite: 4].
  - Drawer o modal de detalle de importación con resumen de estadísticas (válidos/rechazados) y tabla de errores por fila[cite: 4].
- **Carpeta de Trabajo:** `.agents/TRABAJOS/[ID_FUNCIONALIDAD]/REVISION_PREVIA.md`.
- **Documentación de Proyecto:**
  - Actualización de decisiones de UX/UI y frecuencia de polling en `.agents/DOCS/DECISIONS.md`.
  - Bitácora de prompts y diffs de corrección en `.agents/DOCS/AI_AUDIT.md`.
- **Pruebas de Componentes:** Verificación de renderizado de la tabla, respuesta al debouncing de búsqueda y correcta emisión de eventos desde `LegacyProductRow.vue`.

## 4. Ejecución
*Para desarrollar esta funcionalidad, aplica de principio a fin las 5 fases descritas en `.agents/PROTOCOLO_DE_FASES.md`.*