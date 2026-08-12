# Revisión Previa y Validación — Funcionalidad 02 (Apply y Catálogo)

## 1. FASE 1: Análisis técnico y diagnóstico
### 1.1 Diagnóstico del estado actual
La F02 no parte de cero: la base funcional de la Fase A ya existe en el repo y el flujo de apply ya está parcialmente construido.

Se verificó lo siguiente en el código actual:
- `POST /api/import-runs/{importRun}/apply` está registrada en [routes/api.php](../../../routes/api.php).
- La acción del endpoint vive en [app/Http/Controllers/ImportRunController.php](../../../app/Http/Controllers/ImportRunController.php).
- La lógica de negocio del apply vive en [app/Services/ImportRunApplyService.php](../../../app/Services/ImportRunApplyService.php).
- El catálogo de empresa ya existe con filtro por tenant en [app/Http/Controllers/ProductController.php](../../../app/Http/Controllers/ProductController.php).
- El modelo de producto ya define precision/tenant en [app/Models/Product.php](../../../app/Models/Product.php).

### 1.2 Qué debe resolver esta funcionalidad
La F02 debe cerrar dos responsabilidades que no son triviales:
1. Promover el staging a catálogo real mediante una operación atómica e idempotente.
2. Exponer la lectura del catálogo del tenant con paginación server-side, ordenamiento y búsqueda sin fugas entre compañías.

### 1.3 Preguntas técnicas sin ambigüedad
#### Pregunta 1 — ¿Qué se considera una corrida válida para `apply`?
Debe aplicarse solo sobre `import_run_items` con `status = 'valid'` y con `import_run_id` de una corrida que pertenezca al usuario autenticado.

#### Pregunta 2 — ¿Cómo se resuelve el upsert por SKU?
La regla de negocio es `company_id + sku`, no SKU global. En la misma compañía, el SKU debe ser único y un `apply` repetido debe ser inocuo.

#### Pregunta 3 — ¿Qué pasa si un precio/stock no es convertible?
Se debe rechazar en la Fase A y no permitir que la Fase B lo aplique. El `apply` no debe “corregir” valores ambiguos; solo debe consumir datos ya validados.

#### Pregunta 4 — ¿Qué se hace con productos soft-deleted?
Un SKU eliminado lógicamente no puede bloquear la re-creación del mismo SKU dentro de la misma empresa. Esto debe quedar cubierto por la lógica de upsert con `deleted_at = null` y el filtro por `company_id + sku`.

---

## 2. FASE 2: Planificación y validación previa
### 2.1 Arquitectura propuesta
#### A. `ImportRunController@apply`
Debe permanecer delgado:
- resolver `company_id` desde `CompanyContextService`
- verificar que la corrida pertenece al tenant autenticado
- invocar `ImportRunApplyService::apply(...)`
- devolver `200`/`202` según contrato y no mezclar lógica de negocio en el controlador

#### B. `ImportRunApplyService`
Debe encapsular la lógica de negocio:
- no permitir apply si la corrida no pertenece a la empresa autenticada
- no repetir la acción si ya está en `applied`
- cargar solo `import_run_items` pendientes válidos del run
- aplicar todo dentro de `DB::transaction()`
- usar `updateOrCreate` o una estrategia equivalente con la clave compuesta `company_id + sku`
- limpiar `deleted_at` al recrear un SKU soft-deleted dentro de la misma compañía

#### C. `ProductController@index`
Debe consultar solo con `company_id` del usuario y soportar:
- `search` (nombre o SKU)
- `sort_by` y `sort_dir`
- `per_page` y `page`
- paginación server-side con `paginate()`

### 2.2 Reglas de negocio a documentar antes de escribir código
- Multi-tenancy obligatorio: no usar `company_id` del cliente ni de los datos del CSV.
- `apply` debe ser idempotente: ejecutar dos veces la misma corrida no duplica productos.
- La operación debe ser atómica: si una fila falla durante la promoción, no se debe dejar parcialmente aplicado el catálogo.
- Los decimales deben seguir siendo cadenas numéricas y nunca convertirse a `float`/`double`.
- Rechazar cualquier item inválido en Fase A; no corregir en Fase B.

### 2.3 Plan de trabajo real
1. Revisar y cerrar el contrato de apply con `ImportRun` y `ImportRunItem`.
2. Asegurar el tenant check en el controller y el servicio.
3. Definir la estrategia de upsert por `company_id + sku` con tratamiento de soft deletes.
4. Aplicar validación de `import_run_items` y `product` en una sola transacción.
5. Validar el listado del catálogo con `company_id` del usuario autenticado y filtros server-side.
6. Escribir tests de negocio para:
   - `apply` idempotente
   - `apply` con aislamiento por empresa
   - `listado` de productos por tenant
   - `DECIMAL` sin conversión a float

### 2.4 Puntos de diseño que quedan resueltos por evidencia
- Se mantiene el patrón de no ejecutar lógica de negocio en controladores.
- Se usa un servicio específico para apply y otro para contexto de tenant.
- La decisión de uso de `CompanyContextService` queda explícita como requisito arquitectónico.
- Se evita lógica de negocio dispersa en rutas y modelos.

---

## 3. Archivos implicados
La implementación de F02 impactará principalmente estos archivos:
- [routes/api.php](../../../routes/api.php)
- [app/Http/Controllers/ImportRunController.php](../../../app/Http/Controllers/ImportRunController.php)
- [app/Http/Controllers/ProductController.php](../../../app/Http/Controllers/ProductController.php)
- [app/Services/CompanyContextService.php](../../../app/Services/CompanyContextService.php)
- [app/Services/ImportRunApplyService.php](../../../app/Services/ImportRunApplyService.php)
- [app/Models/Product.php](../../../app/Models/Product.php)
- [database/migrations/2024_01_01_000004_create_products_table.php](../../../database/migrations/2024_01_01_000004_create_products_table.php)
- [tests/Feature/ImportRunFlowTest.php](../../../tests/Feature/ImportRunFlowTest.php)

---

## 4. Gate de aprobación de FASE 2
La F02 no puede entrar a ejecución de código con una decisión improvisada. Debe quedar marcada como aprobada solo si se cumplen las siguientes condiciones:
- [ ] `company_id` se resuelve de `auth()->user()->company_id` y nunca del payload.
- [ ] El `apply` usa transacción y es idempotente.
- [ ] El catálogo es consultado por tenant y no comparte registros entre empresas.
- [ ] Los precios y stock se manejan con precisión decimal sin `float`/`double`.
- [ ] La documentación de decisión está reflejada en `.agents/DOCS/DECISIONS.md`.

> Estado actual: plan preparado y documentado en esta revisión previa. No se procede a la Fase 3 sin revisión del plan y aprobación explícita del flujo propuesto.
