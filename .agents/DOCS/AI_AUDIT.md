# AI_AUDIT

## Propósito
Este documento es una traza de la auditoria durante la ejecucion de las tareas se registra en tiempo real y se corrige conforme aparecen hallazgos reales.


### [H01] Fuga de tenant por uso de `company_id` en el Job
- Archivo: App/Jobs/ProcessImportRunJob.php:42
- Severidad: Crítico
- Impacto de negocio: un usuario de una empresa podría leer o mutar datos de otra empresa si el job usa el `company_id` del payload.
- Corrección propuesta: forzar `company_id` desde `auth()->user()->company_id` en la corrida o desde la entidad del `import_run` y no desde el CSV.
- Confianza: Alta

### [H02] Carga completa de CSV en memoria
- Archivo: App/Services/ImportCatalogService.php:18
- Severidad: Alto
- Impacto de negocio: la importación de 100k filas puede agotar memoria y desmontar la worker.
- Corrección propuesta: usar streams con `fgetcsv` y chunks de 1.000 filas.
- Confianza: Alta

### [H03] Pérdida de precisión decimal en precios y stocks
- Archivo: App/Models/Product.php:24
- Severidad: Crítico
- Impacto de negocio: se puede vender con precios incorrectos o inventariar con stock no fiable, afectando finanzas y operaciones.
- Corrección propuesta: usar tipos `DECIMAL(18,4)` y `DECIMAL(14,6)`; prohibir `float` o `double` en la capa de persistencia.
- Confianza: Alta

### [H04] Estado de la corrida desalineado con el flujo real de F01
- Archivo: App/Services/CatalogCsvImportService.php:103
- Severidad: Medio
- Impacto de negocio: si el criterio de cierre usa `completed` pero el flujo real del proyecto usa `validated`, la auditoría y el cierre funcional quedan incongruentes y se puede interpretar mal el estado del import.
- Corrección propuesta: documentar el contrato real del flujo (`pending` -> `processing` -> `validated` -> `applied`/`failed`) y ajustar los gates de aceptación para reflejarlo. La validación del proyecto se hace sobre `validated` como estado terminal de la Fase A.
- Confianza: Alta

### [H05] Validación de volumen por streaming en F01
- Archivo: App/Services/CatalogCsvImportService.php:57
- Severidad: Medio
- Impacto de negocio: un importador que cargue todo el CSV en memoria puede agotar RAM y bloquear la worker, especialmente con 100k filas.
- Corrección propuesta: mantener `fgetcsv` y procesar por chunks de 1.000 filas, como ya se hace en el servicio y se validó por la prueba de flujo de importación.
- Confianza: Alta

### [H06] Filtro de catálogo incompatible con SQLite durante validación de F02
- Archivo: app/Http/Controllers/ProductController.php:23
- Severidad: Medio
- Impacto de negocio: el listado de productos fallaba con 500 en pruebas de validación y podía romper la lectura del catálogo del tenant en entornos no PostgreSQL, incluso cuando la lógica de negocio era correcta.
- Corrección propuesta: reemplazar `ILIKE` sin adaptarlo al driver por una solución cross-driver con `LOWER(name) LIKE LOWER(?)` para SQLite y `ILIKE` para PostgreSQL. Esto conserva la seguridad de tenant sin depender de un único motor de base de datos.
- Confianza: Alta

### [H07] Idempotencia del apply no documentada ni validada en F02
- Archivo: app/Services/ImportRunApplyService.php:18
- Severidad: Alto
- Impacto de negocio: una reejecución del apply podía duplicar productos o dejar el catálogo con registros inconsistentes si se lanzaba más de una vez para la misma corrida.
- Corrección propuesta: agregar early return cuando el `import_run` ya está en `applied`, y cubrir la ruta con tests funcionales que validen `apply` repetido. La corrección quedó probada por la suite del flujo de importación.
- Confianza: Alta

