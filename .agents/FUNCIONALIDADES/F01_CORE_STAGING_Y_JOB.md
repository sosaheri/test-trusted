# FUNCIONALIDAD 01: Ingesta B2B en Background y Fase A (Staging)

## 1. Descripción de Negocio
Proporcionar un mecanismo de ingesta masiva asíncrona para catálogos CSV. La API debe responder de manera inmediata (`202 Accepted`) al recibir un archivo y delegar el procesamiento a un Job en background (`ProcessImportRunJob`). Este Job analiza las filas en modo streaming y las persiste en la tabla de staging (`import_run_items`), clasificándolas como válidas o rechazadas sin alterar la tabla principal de productos (`products`).

## 2. Insumos Esperados (Inputs / Archivos a Consumir)
- **Fixtures:**
  - `fixtures/catalogo_sucio.csv` (contiene patologías de datos, precios mal formateados, stocks inválidos, SKUs nulos o duplicados).
  - `fixtures/catalogo_100k.csv` (100.000 filas para validación de límite de memoria RAM).
- **Rutas API:** `POST /api/import-runs` (recibe el archivo CSV multipart/form-data). Si esta ruta aún no existe en `routes/api.php`, esta funcionalidad incluye su creación como parte del trabajo requerido.
- **Contexto de Sesión:** `auth()->user()->company_id` para garantizar el aislamiento multi-tenant desde el origen.

## 3. Entregables y Emisiones (Outputs)
- **Base de Datos:**
  - Migración y Modelo Eloquent para `import_runs` (campos: `id`, `company_id`, `file_path`, `status`, `total_rows`, `valid_rows`, `rejected_rows`, `timestamps`).
  - Migración y Modelo Eloquent para `import_run_items` (campos: `id`, `import_run_id`, `row_number`, `data` [jsonb/text], `status` [`valid`, `rejected`], `errors` [jsonb/text], `timestamps`).
- **Backend:**
  - Crear o ajustar el endpoint `POST /api/import-runs` en `routes/api.php` si aún no existe. Este endpoint forma parte de la funcionalidad y no debe asumirse ya implementado.
  - `ImportRunController@store`: Controlador HTTP que valida la subida, registra el `import_run` inicial en estado `pending` y despacha el Job.
  - `ProcessImportRunJob`: Queue Job con lectura por streams (`fgetcsv` / generadores) e inserciones en masa por bloques (*chunks* de 1.000 filas).
- **Carpeta de Trabajo:** `.agents/TRABAJOS/[ID_FUNCIONALIDAD]/REVISION_PREVIA.md`.
- **Documentación de Proyecto:**
  - Matriz de tratamiento para patologías del CSV consolidada en `.agents/DOCS/DECISIONS.md`.
  - Bitácora y registro de diffs en `.agents/DOCS/AI_AUDIT.md`.
- **Pruebas Automatizadas:** Test en Pest/PHPUnit que valide la respuesta de la API, el procesamiento asíncrono y el consumo constante de memoria (< 128 MB) procesando el archivo de 100k filas.

## 4. Ejecución
*Para desarrollar esta funcionalidad, aplica de principio a fin las 5 fases descritas en `.agents/PROTOCOLO_DE_FASES.md`.*

> Importante: si el endpoint `POST /api/import-runs` no existe en el código actual, esta funcionalidad debe incluir la creación del endpoint y su registro de ruta; no se asume que ya está resuelto.