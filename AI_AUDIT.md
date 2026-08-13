# AI_AUDIT

## Aclaratoria gobernanza para agente IA

Previo al registro de la bitácora de interacción, se diseñó e implementó un **Sistema de Agentes y Gobernanza Documental** en el directorio `.agents/` para garantizar la calidad, el aislamiento de contexto y la trazabilidad de cada funcionalidad del proyecto.

Este sistema está compuesto por la siguiente estructura modular:

### Estructura del directorio `.agents/`

```text
.agents/
├── REGLAS_MAESTRAS.md           # Origen de verdad del proyecto, jerarquía de documentos y directrices multi-tenant
├── PROTOCOLO_DE_FASES.md        # Definición del flujo de trabajo obligatorio en 5 fases y gates de aprobación
├── DOCS/
│   ├── DECISIONS.md             # Histórico de decisiones técnicas, hipótesis de la IA y resoluciones del desarrollador
│   └── AI_AUDIT.md              # Registro de hallazgos de seguridad, rendimiento y bitácora de prompts
├── FUNCIONALIDADES/
│   ├── F01_CORE_STAGING_Y_JOB.md# Requisitos y alcance técnico de la ingesta y validación CSV
│   ├── F02_APPLY_Y_CATALOGO.md  # Requisitos para la promoción de datos a productos y lectura del catálogo
│   └── F03_UX_Y_FEEDBACK.md     # Requisitos de polling, estados visuales y barra de progreso
└── TRABAJOS/
    └── F01_STAGING/
        └── REVISION_PREVIA.md   # Diagnóstico de Fase 1 y 2 con gate de aprobación antes de implementar
```


## 6.1 Bitácora de prompts

| Fecha / Funcionalidad | Prompt estructural | Modelo usado | Área impactada | Resultado observado |
|---|---|---|---|---|
| **2026-08-12 / F01 Staging** | "Consulta .agents/REGLAS_MAESTRAS.md y ejecuta la FASE 1 y FASE 2 de .agents/FUNCIONALIDADES/F01_CORE_STAGING_Y_JOB.md siguiendo la metodología de .agents/PROTOCOLO_DE_FASES.md." | MAI-Code-1.1-Flash | `app/Services/CatalogCsvImportService.php`, `app/Jobs/ProcessImportRunJob.php`, `.agents/TRABAJOS/F01_STAGING/REVISION_PREVIA.md` | Se generó el diagnóstico y la arquitectura de staging por streams. La IA intentó parsear precios a `float` en la validación inicial del CSV y leer el `company_id` desde el payload. |
| **2026-08-12 / F01 Execution & Tests** | "Ejecuta la FASE 3 y FASE 4 de F01. Genera las migraciones de import_runs e import_run_items y la suite de tests en Pest/PHPUnit." | MAI-Code-1.1-Flash | `database/migrations/`, `tests/Feature/ImportRunFlowTest.php`, `app/Http/Controllers/ImportRunController.php` | Se construyó la infraestructura de staging. Durante las pruebas en Docker surgió conflicto con la suite por defecto de PHPUnit (`tests/Unit`), requiriendo redirección a `tests/Feature`. |
| **2026-08-12 / F02 Apply y Catálogo** | "Consulta .agents/REGLAS_MAESTRAS.md y ejecuta la FASE 1 y FASE 2 de F02 siguiendo el protocolo de fases. Debe quedar documentado el diagnóstico, plan y gate de aprobación antes de la implementación." | MAI-Code-1.1-Flash | `app/Services/ImportRunApplyService.php`, `app/Http/Controllers/ProductController.php`, `.agents/DOCS/DECISIONS.md` | Se formalizó la promoción atómica de staging a `products`. Se detectaron y corrigieron consultas con operador `ILIKE` incompatibles con el entorno de pruebas SQLite. |
| **2026-08-12 / Gobernanza y Control de Fases** | modifica el flujo de ejecucion de las fases para el agente incluye la edicion del archivo DECISION.md durante la FASE 2 y la edicion del archivo AI_AUDIT.md luego de la ejecucion y pruebas incluyendo correspondientemen la decisiones tomando a nivel de arquitectura y plan de ejecucion y el resultado de la ejecucion de lo planificacdo respecto a lo ejecutado | MAI-Code-1.1-Flash (GitHub Copilot) | `.agents/PROTOCOLO_DE_FASES.md`, `.agents/REGLAS_MAESTRAS.md`, `.agents/DOCS/DECISIONS.md`, `.agents/DOCS/AI_AUDIT.md`, `.agents/TRABAJOS/F01_STAGING/REVISION_PREVIA.md` | Se blindó la gobernanza del proyecto: se estableció la jerarquía de verdad, la obligación de aprobación humana en la Fase 2 antes de picar código, y la estandarización estricta para el historial de decisiones en `DECISIONS.md` y auditoría por hallazgo en `AI_AUDIT.md`. |
| **2026-08-12 / Alcance y Creación de Endpoints** | • "en esta funcionalidad se esta asumiendo que existe el endpoint cuando no es asi dentro de esta tarea deberia estar la creacion en caso de que no exista dicho endpoints" | MAI-Code-1.1-Flash (GitHub Copilot) | `routes/api.php`, `app/Http/Controllers/ImportRunController.php` | Se asumió la responsabilidad de crear el contrato HTTP `POST /api/import-runs` y `GET /api/import-runs/{id}` dentro del alcance de la F01 en lugar de darlo por existente. |
| **2026-08-12 / F01 Ingesta Masiva y Hardening del CSV** | • "Modificar la respuesta para que refleje la cantidad de lineas afectadas o que no cumple las condiciones en los archivos CSV" | MAI-Code-1.1-Flash (GitHub Copilot) | `docker/php/Dockerfile`, `app/Services/CatalogCsvImportService.php`, `app/Services/ImportRunApplyService.php` | Se ajustaron límites de subida en Docker (`upload_max_filesize`) para soportar 100k filas. Se hardenizó el parser para recortar/rellenar celdas inconsistentes y se restringió la promoción en el `apply` para procesar exclusivamente los ítems con estado `valid`. |
| **2026-08-12 / F01/F03 Feedback UX, Polling y Animación** | • "Modifiquemos el UX para que cuando se carguen archivos pesados muestre el polling de espera y se entienda que se sigue procesando el archivo de forma activa, notificar una vez culminado" | MAI-Code-1.1-Flash (GitHub Copilot) | `resources/js/App.vue`, `app/Http/Controllers/ImportRunController.php` | Se implementó el mecanismo de polling continuo y se corrigió la sensación de congelamiento en el frontend mediante una barra de progreso animada durante los estados transitorios (`pending`/`processing`). |

## 6.2 Auditoría de tu propio código generado

### 1) El archivo grande quedaba bloqueado por el contenedor PHP

#### Snippet real observado
```dockerfile
RUN printf '%s\n' \
    'upload_max_filesize = 256M' \
    'post_max_size = 256M' \
    'memory_limit = 512M' \
    > /usr/local/etc/php/conf.d/uploads.ini
```

#### Severidad
Alta

#### Impacto concreto
Los archivos de importación grandes no llegaban al backend porque el contenedor imponía límites de subida bajos. En la práctica, el usuario no podía completar la carga y el proceso no avanzaba aunque el job ya estuviera programado.

#### Diff de la corrección
```diff
- 'upload_max_filesize = 256M'
- 'post_max_size = 256M'
- 'memory_limit = 512M'
+ 'upload_max_filesize = 512M'
+ 'post_max_size = 512M'
+ 'memory_limit = 1024M'
```

#### Resultado
Se eliminó el cuello de botella de subida del contenedor para admitir CSV grandes dentro del flujo real del proyecto.

---

### 2) Filas sucias o mal formadas rompían el parser del CSV

#### Snippet real observado
```php
$record = array_combine($normalizedHeader, $rowValues);

if (! is_array($record)) {
    $record = [];
}
```

#### Severidad
Alta

#### Impacto concreto
Si el archivo tenía filas con columnas faltantes, exceso de columnas o celdas vacías, el proceso caía en el parser o dejaba la importación en un estado inconsistente. Esto fue una patología real del CSV durante la validación.

#### Diff de la corrección
```diff
- $record = array_combine($normalizedHeader, $rowValues);
- if (! is_array($record)) {
-     $record = [];
- }
+ $rowValues = array_pad($rowValues, count($normalizedHeader), '');
+
+ if (count($rowValues) > count($normalizedHeader)) {
+     $rowValues = array_slice($rowValues, 0, count($normalizedHeader));
+ }
+
+ $record = array_combine($normalizedHeader, $rowValues);
+
+ if (! is_array($record)) {
+     $record = [];
+ }
```

#### Resultado
La importación ya no se rompe por filas sucias; se rechazan de forma controlada en lugar de abortar el flujo completo.

---

### 3) El `apply` podía intentar promocionar filas inválidas

#### Snippet real observado
```php
$validRows = $importRun->items()->get();
foreach ($validRows as $item) {
    $this->applyItem($item);
}
```

#### Severidad
Alta

#### Impacto concreto
Se detectó que no se estaba filtrando por filas realmente válidas antes de aplicar al catálogo. Eso significaba que una corrida con filas malas podía dejar mutaciones parciales o estado legalmente inconsistente en el negocio.

#### Diff de la corrección
```diff
- $validRows = $importRun->items()->get();
- foreach ($validRows as $item) {
-     $this->applyItem($item);
- }
+ $validRows = $importRun->items()->where('status', 'valid')->get();
+
+ if ($validRows->isEmpty()) {
+     throw new ValidationException('No hay filas válidas para aplicar.');
+ }
+
+ foreach ($validRows as $item) {
+     $this->applyItem($item);
+ }
```

#### Resultado
Solo se aplican filas que pasaron validación; las no válidas quedan registradas como rechazadas y no se promueven al catálogo.

---

### 4) El estado final mostraba “Validado con errores” aun cuando no había errores reales

#### Snippet real observado
```js
const statusMeta = {
    validated: { color: 'warning', label: 'Validado con errores' },
};
```

#### Severidad
Media

#### Impacto concreto
El usuario veía un mensaje falso al terminar la validación. Si el archivo era limpio, igual aparecía un estado de error, lo cual confundía al operador y hacía perder confianza en la validación.

#### Diff de la corrección
```diff
- const statusMeta = {
-     validated: { color: 'warning', label: 'Validado con errores' },
- };
+ const statusLabel = computed(() => {
+     if (currentStatus.value === 'validated') {
+         return (importRun.value?.rejected_rows ?? 0) > 0
+             ? 'Validado con errores'
+             : 'Validado correctamente';
+     }
+
+     return statusMeta[currentStatus.value]?.label ?? 'Sin corrida activa';
+ });
```

#### Resultado
El estado de UI se volvió consistente con la realidad del archivo: si no hay filas rechazadas, el mensaje es correcto y no engañoso.

---

### 5) La pantalla parecía “colgada” porque no había feedback real de progreso

#### Snippet real observado
```js
if (currentStatus.value === 'pending' || currentStatus.value === 'processing') {
    return 'Procesando archivo. Te avisaremos cuando termine la validación.';
}
```

#### Severidad
Media

#### Impacto concreto
El usuario subía el CSV y el job seguía ejecutándose, pero la vista no daba señales claras de progreso ni de finalización. El flujo se veía congelado aunque el backend estuviera trabajando.

#### Diff de la corrección
```diff
+ const progressByStatus = {
+     pending: 25,
+     processing: 50,
+     validated: 75,
+     applied: 100,
+     failed: 100,
+ };
+
+ function animateProgress(targetValue) {
+     // animación continua mientras el estado está activo
+ }
+
+ function setCompletionNotice(status, payload) {
+     if (status === 'validated') {
+         completionNotice.value = {
+             type: payload.rejected_rows > 0 ? 'warning' : 'success',
+             message: payload.rejected_rows > 0
+                 ? `Importación finalizada con ${payload.rejected_rows} filas rechazadas.`
+                 : 'Importación finalizada correctamente.',
+         };
+     }
+ }
```

#### Resultado
Se agregó polling y animación de progreso para que la UI refleje el estado real del job y no parezca bloqueada.

## 6.3 Auditoría de PR ajeno



```
# [ID] Título del hallazgo
- Archivo:línea
- Severidad: Crítico | Alto | Medio | Cosmético
- Impacto de negocio: (qué se rompe, para quién, en qué escenario)
- Corrección propuesta: (diff o descripción precisa)
- Confianza: Alta | Media | Baja
```


## 6.4 Estimación de ahorro y control humano

### 1. Horas estimadas sin IA vs. con IA
* **Estimación tradicional (sin IA):** **~16 a 24 horas de trabajo** para estructurar migraciones, jobs en background con streaming, normalización de CSV, servicios con aislamiento multi-tenant, controladores, interfaz en Vue con polling y la suite de pruebas automatizadas.
* **Tiempo real de entrega (con IA):** **~7 horas en total** (iniciando a las 12:00 PM).
* **Desglose real del esfuerzo:**
  * **Configuración del sistema de agentes (2 horas):** Ajuste de reglas maestras, fuentes de verdad, jerarquía de gobernanza y protocolos de fases.
  * **Ejecución de funcionalidades (1 hora):** Generación inicial de código para migraciones, servicios, controladores y vistas.
  * **Corrección de errores y depuración (2 horas):** Ajuste de límites de subida en Docker para archivos de 100k filas, depuración del parser para filas sucias, corrección de la barra de progreso/polling en el frontend y ajuste de estados visuales.
  * **Documentación y auditoría (2 horas):** Registro de decisiones en `DECISIONS.md`, auditoría de hallazgos y trazabilidad de prompts en `AI_AUDIT.md`.

---

### 2. En qué partes del trabajo la IA te restó velocidad
* **Asunciones erróneas de arquitectura:** La IA asumía inicialmente que ciertos endpoints o estructuras de carpetas ya existían en el repositorio, requiriendo frenarla y reorientarla para que incluyera la creación de los contratos HTTP en la misma tarea.
* **Falta de contexto de ejecución real:** Intentaba correr comandos directos de PHP sobre el host local en lugar de ejecutarlos dentro del contenedor Docker, o no tomaba en cuenta los límites por defecto del contenedor (`upload_max_filesize`) para procesar archivos pesados de 100k filas.
* **Detalles finos de UX y negocio:** Generó etiquetas estáticas de estado en el frontend (como mostrar *"Validado con errores"* cuando no había ninguna fila rechazada), lo que exigió revisión humana para corregir la lógica condicional basada en datos reales.

---

### 3. Qué tareas se aceleraron y qué parte requiere control humano
* **Tareas aceleradas por la IA:**
  * **Boilerplate e infraestructura:** Escritura rápida de estructuras para migraciones, modelos Eloquent, Jobs en cola y controladores.
  * **Borradores de servicios y pruebas:** Generación de la lógica base para lectura por streams (`fgetcsv`) y esquemas de tests en Pest/PHPUnit.
  * **Generación de documentación:** Redacción rápida de resúmenes de revisión previa y borradores de bitácoras.

* **Parte que requiere estricto control humano:**
  * **Gobernanza y gates de aprobación:** La decisión de bloquear el avance entre fases para validar arquitectura y multi-tenancy antes de picar código.
  * **Garantía de aislamiento multi-tenant:** Validar manualmente que el `company_id` se resuelva únicamente desde el usuario autenticado y jamás desde el payload HTTP o el archivo subido.
  * **Depuración de infraestructura y entorno de ejecución:** Ajustes en Docker, permisos del socket y detección de incompatibilidades en motores de base de datos entre entornos de desarrollo y pruebas.
