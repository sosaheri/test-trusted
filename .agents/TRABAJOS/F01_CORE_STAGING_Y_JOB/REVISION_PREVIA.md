# Revisión Previa y Validación — Funcionalidad 01 (Staging)

## 1. Decisión de alcance
La F01 no es solo “subir un CSV”. Es la creación de la base de ingestión del catálogo B2B con separación clara entre:
- Fase A: staging y validación,
- Fase B: apply transaccional a `products`.

La evidencia del proyecto confirma que no se puede asumir que existe ninguna de estas piezas:
- `routes/api.php` solo tiene TODOs para `/products` y `/import-runs`.
- No hay migración ni modelo para `import_runs`, `import_run_items` ni `Product` con scope multi-tenant.
- La autenticación base existe, pero el flujo de importación no.

Por ello, esta funcionalidad incluye crear el contrato HTTP y la infraestructura de persistencia si aún no existe.

---

## 2. Decisión arquitectónica concreta
### 2.1 Endpoints que sí o sí deben existir
Se implementarán estas rutas dentro del scope de F01:
- `GET /api/products` — listado server-side paginado/buscado por empresa.
- `POST /api/import-runs` — recibe multipart/form-data con archivo CSV, crea el `ImportRun` y despacha el job.
- `GET /api/import-runs/{id}` — estado actual, filas válidas/rechazadas y metadata de ejecución.
- `POST /api/import-runs/{id}/apply` — aplica solo los registros válidos del staging a `products` dentro de `DB::transaction`.

### 2.2 Controladores
Se crearán o ajustarán los siguientes controladores, y deben permanecer delgados:
- `App\Http\Controllers\ProductController`
  - `index()` para listado con `company_id` del usuario autenticado.
- `App\Http\Controllers\ImportRunController`
  - `store()` para subir CSV y crear la corrida;
  - `show()` para recuperar estado;
  - `apply()` para promociar staging a catálogo real.

Regla explícita: los controladores no ejecutan lógica de negocio. Solo validan entrada HTTP, llaman servicios y devuelven la respuesta. La lógica real va a `App\Services\...` y a `Jobs`.

### 2.3 Capa de seguridad y validación del tenant
Se aplicarán estas reglas sin excepción:
- `company_id` debe resolverse en un punto explícito de contexto de empresa, no solo como campo del modelo.
- Se recomienda un servicio de contexto de tenant, por ejemplo `CompanyContextService` o `TenantContextResolver`, que derive el valor desde `auth()->user()->company_id` y lo exponga a los servicios.
- No se acepta `company_id` del cliente ni del CSV.
- Cada operación de importación debe verificar que el `ImportRun` y los `ImportRunItem` pertenecen al mismo `company_id` del usuario autenticado.
- La validación del tenant se aplicará también antes del `apply` para impedir fugas entre compañías.
- La subida debe validar: archivo presente, tipo CSV, tamaño razonable, nombre y extensión válidos.
- Los datos monetarios y de stock deben respetar el tipo de dato fiscal del proyecto: `DECIMAL(18,4)` y `DECIMAL(14,6)`.
- Se evitará cualquier `float`/`double` a nivel de modelo y persistencia.

Esto corrige un punto importante: el tenant no debe depender de “poner un campo en el modelo y ya está”; debe existir una capa de resolución y de validación en la capa de servicio para evitar fugas de datos en jobs, controladores y apply.

### 2.4 Servicios y jobs
Se crearán las siguientes piezas de negocio:
- `App\Services\CatalogCsvImportService`
  - abre el archivo CSV por stream,
  - normaliza estructura,
  - valida filas,
  - devuelve lotes de registros para staging.
- `App\Services\CompanyContextService`
  - resuelve y valida el `company_id` autenticado,
  - lo usa como entrada obligatoria de los servicios del import.
- `App\Services\ImportRunApplyService`
  - valida el estado de la corrida,
  - transforma `import_run_items` válidos a `products`,
  - ejecuta la aplicación en una sola transacción.
- `App\Jobs\ProcessImportRunJob`
  - leída por `fopen`/`fgetcsv`,
  - procesamiento por chunks de 1.000 filas,
  - escritura en `import_run_items` con `status` y `errors`.

### 2.5 Modelos y base de datos
Se agregará la capa mínima necesaria:
- `App\Models\ImportRun`
  - `id`, `company_id`, `file_path`, `status`, `total_rows`, `valid_rows`, `rejected_rows`, `created_at`, `updated_at`
- `App\Models\ImportRunItem`
  - `id`, `import_run_id`, `row_number`, `input_data`, `status`, `errors`, `created_at`, `updated_at`
- `App\Models\Product`
  - con `company_id`, `sku`, `price`, `stock`, `deleted_at`, y reglas de multi-tenancy por empresa.

Migraciones:
- `import_runs`
- `import_run_items`
- ajuste de `products` si fuese necesario para tipos y `company_id`.

### 2.6 Decisiones para archivos sucios (`catalogo_sucio.csv`)
Se debe formalizar una capa explícita de manejo de patologías del CSV antes de implementar el job. La decisión no puede quedar solo “en el regex” o “en un intento del servicio”. Debe existir una política de clasificación.

Política recomendada:
- `valid`: fila usable tras normalización y validación de negocio.
- `rejected`: fila descartada por error estructural o de dominio.
- `needs_review`: fila sospechosa pero potencialmente corregible (p.ej. costo con formato raro, SKU duplicado con semántica no clara).

Casos a definición formal:
- SKU vacío o duplicado dentro del mismo archivo.
- Precio con indicadores de moneda, comas decimales o miles.
- Stock con valores no numéricos, vacíos o negativos.
- Columnas extra o faltantes.
- Delimitador alternativo.
- Codificación no UTF-8.
- Fila con salto de línea o contenido multiline.

Cada una de estas reglas debe quedar documentada en `.agents/DOCS/DECISIONS.md` como una entrada por patología con decisión final y por qué se aceptó o rechazó.

---

## 3. Flujo real de ejecución
### 3.1 Crear la corrida
1. El cliente hace `POST /api/import-runs` con archivo CSV.
2. El `ImportRunController` valida la solicitud y el usuario autenticado.
3. Se crea un `ImportRun` en estado `pending` con `company_id` del usuario.
4. Se encola `ProcessImportRunJob`.

### 3.2 Proceso en background
1. El job abre el CSV con flujo secuencial.
2. Lee fila por fila con `fgetcsv`.
3. Normaliza campos y valida reglas de negocio.
4. Guarda cada fila en `import_run_items` como `valid` o `rejected`.
5. Actualiza contadores del `ImportRun` (`total_rows`, `valid_rows`, `rejected_rows`).
6. No toca `products` en esta fase.

### 3.3 Aplicación del staging
1. El cliente invoca `POST /api/import-runs/{id}/apply`.
2. El servicio confirma que la corrida pertenece al mismo `company_id` del usuario.
3. Se seleccionan solo los `import_run_items` con estado `valid`.
4. Se promueven a `products` dentro de una transacción SQL.
5. Si hay conflicto de SKU por empresa, se resuelve con reglas de negocio definidas en `DECISIONS.md`.
6. La corrida pasa a estado `applied` o `failed` según resultado.

---

## 4. Patologías del CSV que decidimos manejar explícitamente
Revisamos `fixtures/catalogo_sucio.csv` y se priorizan estas condiciones:
- SKU nulo o duplicado en mismo archivo,
- precio con separadores no uniformes,
- stock inválido o negativo,
- columnas faltantes o extras,
- delimitador incompatible,
- codificación errónea,
- fila con salto de línea,
- filas que no responden al esquema mínimo.

Cada una de estas patologías debe dejar registro explícito en `.agents/DOCS/DECISIONS.md` con qué se acepta, qué se rechaza y por qué.

---

## 5. Riesgos y límites de alcance
- No se construirá autenticación real ni permisos complejos.
- No se hará mutación directa sobre `products` en la Fase A.
- No se usará `float`/`double` para precios ni stock.
- No se acepta que el cliente mande `company_id` o cualquiera de los valores sensibles del tenant.
- La rama `feat/ai-generated-importer` no será base de código; se revisará sólo como auditoría.

---

## 6. Criterio de aceptación de F01
La funcionalidad solo se considera lista cuando se cumplan estos puntos:
- [x] Existen rutas y controladores para productos y import-runs.
- [x] Existe migración y modelos necesarios para la corrida y staging.
- [x] El job lee CSV por streams y procesa en chunks.
- [x] La validación no rompe el multi-tenancy.
- [x] `products` no se toca antes del `apply`.
- [x] El estado de la corrida queda trazable (`pending`, `processing`, `validated`, `failed`, `applied`).
- [x] Hay tests que validen API, staging y comportamiento del procesamiento en masa.
- [x] `DECISIONS.md` y `AI_AUDIT.md` tienen evidencia verificable.

> Ajuste final al cierre de F01: la fase A del proyecto usa `validated` como estado terminal del staging; la fase B usa `applied` cuando los registros válidos se promueven a `products`. El nombre `completed` no forma parte del contrato implementado ni del flujo validado por tests.

---

## 7. Traza de cambios tras tu revisión
Esta sección queda como registro de evolución del documento a partir de tu revisión.

### Cambio 1 — Controladores delgados y servicios como orquestadores
- **Cambio realizado:** se dejó expresado que los controladores deben ser delgados y que la lógica de negocio debe moverse a servicios.
- **Motivo:** tú señalaste que `ImportRunController` no debe ejecutar negocio por sí mismo.
- **Resultado:** el plan se reforzó con `CompanyContextService`, `CatalogCsvImportService` y `ImportRunApplyService` como piezas de negocio.

### Cambio 2 — Tenant no solo como campo del modelo
- **Cambio realizado:** se ampliaron las decisiones para que el `company_id` no sea solo una columna en el modelo, sino una capa de contexto y validación de tenant.
- **Motivo:** tú pediste manejar mejor la relación con el tenant y la fuga entre compañías.
- **Resultado:** se incorporó la idea de un servicio de contexto de empresa y validación explícita antes de `apply` y durante el staging.

### Cambio 3 — Política formal para CSV sucio
- **Cambio realizado:** se agregó un bloque específico de decisiones para `catalogo_sucio.csv` con estados y casos a definir.
- **Motivo:** la decisión sobre las filas sucias todavía no estaba formalizada.
- **Resultado:** queda claro que cada patología debe registrarse como entrada en `DECISIONS.md` con la decisión final y la razón.

> Este bloque refleja el cambio hecho a partir de tu revisión y debe seguirse actualizando si el plan se modifica.

---

## 8. Gate de aprobación
> Estado actual: `pendiente de aprobación`.
>
> Esta revisión previa autoriza la implementación solo si se aprueba explícitamente el plan anterior. Sin esa aprobación, no se entra a la Fase 3 (Ejecución de Código).
>
> La aprobación no es por intuición; debe validar esta estructura concreta de rutas, servicios, tenant context, job, seguridad y flujo de apply.