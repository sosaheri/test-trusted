# FUNCIONALIDAD 02: Aplicación Atómica (Fase B) y Consulta de Catálogo

## 1. Descripción de Negocio
Permitir la promoción explícita de los datos validados desde la tabla de staging (`import_run_items`) hacia el catálogo definitivo (`products`). Esta operación debe ser atómica (envuelta en una transacción SQL) e idempotente (ejecutarla varias veces no duplica ni corrompe productos). Además, debe exponer el catálogo de productos con búsqueda, ordenamiento y paginación resueltos en el servidor (*server-side*).

## 2. Insumos Esperados (Inputs / Archivos a Consumir)
- **Rutas API:**
  - `POST /api/import-runs/{id}/apply` (ejecuta la Fase B para una corrida específica).
  - `GET /api/import-runs/{id}` (retorna el estado actual de la corrida y el resumen de ítems).
  - `GET /api/products` (parámetros de consulta: `search`, `page`, `per_page`, `sort_by`, `sort_order`).
- **Contexto de Sesión:** `auth()->user()->company_id` para garantizar que la aplicación y la lectura correspondan exclusivamente al tenant autenticado.

## 3. Entregables y Emisiones (Outputs)
- **Base de Datos:**
  - Migración y Modelo Eloquent para `products` (campos: `id`, `company_id`, `sku`, `name`, `price` [`DECIMAL(18,4)`], `stock` [`DECIMAL(14,6)`], `deleted_at`, `timestamps`).
  - Índice único parcial en PostgreSQL: `UNIQUE(company_id, sku) WHERE deleted_at IS NULL`.
- **Backend:**
  - `ImportRunController@apply`: Ejecuta la migración de filas `valid` desde `import_run_items` hacia `products` utilizando `DB::transaction`. Si la corrida ya tiene estado `applied`, retorna de forma inocua (idempotencia).
  - `ImportRunController@show`: Consulta el progreso y reporte de la corrida de importación.
  - `ProductController@index`: Consulta paginada con filtros server-side blindada por `company_id`.
- **Carpeta de Trabajo:** `.agents/TRABAJOS/[ID_FUNCIONALIDAD]/REVISION_PREVIA.md`.
- **Documentación de Proyecto:**
  - Actualización de decisiones sobre upsert/merging de SKUs existentes en `.agents/DOCS/DECISIONS.md`.
  - Registro de prompts y fallos/diffs corregidos en `.agents/DOCS/AI_AUDIT.md`.
- **Pruebas Automatizadas:** Tests en Pest/PHPUnit para validar:
  - Atomicidad (`rollback` completo si falla la inserción).
  - Idempotencia del endpoint `/apply`.
  - Aislamiento multi-tenant (un tenant no ve ni aplica productos de otro).
  - Precisión decimal sin casteo a `float`.

## 4. Ejecución
*Para desarrollar esta funcionalidad, aplica de principio a fin las 5 fases descritas en `.agents/PROTOCOLO_DE_FASES.md`.*