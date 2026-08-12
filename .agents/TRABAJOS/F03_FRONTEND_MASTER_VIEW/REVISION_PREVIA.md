# Revisión Previa y Validación — Funcionalidad 03 (Frontend Master View)

## 1. FASE 1: Análisis técnico del requisito
### 1.1 Diagnóstico del estado actual
La funcionalidad F03 no parte de un dashboard completo. El repo ya incluye:
- un placeholder de vista maestra en [resources/js/App.vue](../../../resources/js/App.vue)
- un componente legado en [resources/js/components/LegacyProductRow.vue](../../../resources/js/components/LegacyProductRow.vue)
- backend ya implementado para import-runs y products en [routes/api.php](../../../routes/api.php)

La evidencia del proyecto confirma que la capa de UI debe cumplir dos requisitos simultáneos:
1. Refactorizar el componente legacy sin romper el contrato de props y emits.
2. Construir una pantalla maestra B2B con upload, polling, catálogo, drawer y apply.

### 1.2 Requisitos funcionales reales a cubrir
- Crear una única pantalla maestra desde la cual el usuario pueda:
  - subir CSV
  - ver progreso del import run
  - ver resumen de filas válidas/rechazadas
  - abrir detalle en drawer o diálogo
  - lanzar apply
- La tabla de catálogo debe ser server-side y arquitectónicamente segura por tenant.
- El componente `LegacyProductRow.vue` debe migrarse a Composition API sin cambiar su API pública.

### 1.3 Casos de borde a resolver antes de construir UI
#### Pregunta 1 — ¿Qué debe hacer la UI mientras el import está en `pending` o `processing`?
Debe usar polling corto y reintentar hasta que el estado pase a `validated` o `failed`, sin bloquear la pantalla.

#### Pregunta 2 — ¿Cómo se debe mantener el contrato legacy?
Se debe conservar intacto:
- `props.product`
- `props.selected`
- `props.readonly`
- emits `select` y `quantity-change`

#### Pregunta 3 — ¿Qué comportamiento de búsqueda y ordenamiento debe usarse?
La tabla debe usar server-side pagination y sort, con `search` debounced para no saturar la API.

#### Pregunta 4 — ¿Qué se asume sobre la UX si la importación falla?
Debe abrir el detalle de errores y mostrar los motivos por fila, pero no ocultar la corrida ni la posibilidad de reintentar.

---

## 2. FASE 2: Planificación y validación previa
### 2.1 Arquitectura propuesta
#### A. Refactor del componente legacy
Se propone migrar [resources/js/components/LegacyProductRow.vue](../../../resources/js/components/LegacyProductRow.vue) a `<script setup>` identificando exactamente estos puntos:
- `defineProps` con validación equivalente al `validator` previo
- `defineEmits` con los mismos eventos: `select` y `quantity-change`
- `draftStock`, `editingStock`, `formattedPrice`, `stockLooksLow` con la lógica equivalente a la versión legacy
- `watch` y métodos persistidos con la misma semántica

Regla explícita: no cambiar el contrato público del componente. La migración es de implementación, no de API.

#### B. Pantalla maestra
La vista principal debe incorporar estos bloques:
- uploader de CSV con `v-file-input`
- estado de import run con `v-alert` y `v-progress-linear`
- tabla de catálogo con `v-data-table-server`
- búsqueda con debounce (300–500 ms)
- drawer/modal de resultados con summary y errores
- botón de `apply` que dispara `POST /api/import-runs/{id}/apply`

#### C. Polling
La estrategia propuesta es polling cada 2 segundos mientras el estado sea `pending`, `processing` o `validated` y evitar polling agresivo cuando la corrida ya está en terminal (`applied` o `failed`).

### 2.2 Reglas de negocio y UX a documentar antes de código
- La pantalla debe respetar el contexto del tenant autenticado y no permitir operar sobre otra empresa.
- La frecuencia de polling no debe ser constante indefinida: se debe detener al llegar a estados terminales.
- El refactor de `LegacyProductRow.vue` no puede cambiar el nombre del componente ni la firma de props/emits.
- La búsqueda en tabla debe reducir la carga al servidor con debounce y usar el endpoint real `/api/products`.
- Los idiomas y ayuda visual deben seguir el lenguaje del ERP, sin introducir falsos conceptos que no estén respaldados por la API.

### 2.3 Plan de trabajo real
1. Inspeccionar y mapear props/emits del componente legacy.
2. Reescribir el componente en `<script setup>` sin cambiar la API pública.
3. Diseñar la estructura de la pantalla maestra y definir el estado inicial del import run.
4. Implementar el flujo de upload y polling contra `GET /api/import-runs/{id}`.
5. Integrar la tabla de catálogo con server-side pagination y búsqueda debounced.
6. Diseñar el drawer de detalle con estadísticas y errores por fila.
7. Integrar el botón de apply y refinar la UX con feedback visual.
8. Escribir pruebas de renderizado y eventos para el componente legado y la vista maestra.

### 2.4 Puntos de diseño resueltos por evidencia
- La UI debe usar la API real, no suposiciones de rutas o payloads.
- El refactor no es una reescritura del componente, sino una migración de implementación preservando compatibilidad.
- La estrategia de polling es una decisión de UX con base en el estado de la corrida y el tiempo de respuesta esperado del backend.
- Se evita introducir componentes nuevos no requeridos por la funcionalidad del challenge.

---

## 3. Archivos implicados
La implementación de F03 impactará principalmente estos archivos:
- [resources/js/App.vue](../../../resources/js/App.vue)
- [resources/js/components/LegacyProductRow.vue](../../../resources/js/components/LegacyProductRow.vue)
- [routes/api.php](../../../routes/api.php)
- [app/Http/Controllers/ProductController.php](../../../app/Http/Controllers/ProductController.php)
- [app/Http/Controllers/ImportRunController.php](../../../app/Http/Controllers/ImportRunController.php)
- [app/Models/Product.php](../../../app/Models/Product.php)

---

## 4. Gate de aprobación de FASE 2
La F03 no puede entrar a la ejecución de código con decisiones improvisadas. Debe quedar marcada como aprobada solo si se cumplen estas condiciones:
- [x] Se verificó el contrato original del componente legacy y la compatibilidad de props/emits.
- [x] Se definió la estrategia de polling realista para la corrida de importación.
- [x] Se definió la estructura del catálogo server-side con búsqueda debounced y paginación por servidor.
- [x] Se documentó el enfoque de UX y la decisión de diseño en `.agents/DOCS/DECISIONS.md`.

> Estado actual: plan preparado y documentado con arquitectura, evidencia y criterios de aprobación. No se procede a la Fase 3 sin revisar esta base y confirmar que el plan sea ejecutable en el repo real.

## 5. Evidencia de validación previa
- Fuente de verdad: [resources/js/App.vue](../../../resources/js/App.vue), [resources/js/components/LegacyProductRow.vue](../../../resources/js/components/LegacyProductRow.vue), [routes/api.php](../../../routes/api.php).
- Requisito validado: la UI debe funcionar con la API actual y respetar el contrato legacy del componente.
- Resultado esperado: no rompe la compatibilidad de la interfaz ni la relación con la API real del proyecto.
