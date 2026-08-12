# DECISIONS

## Propósito
Registrar el historial de decisiones técnicas por funcionalidad, vinculando cada decisión con la evidencia del problema, la hipótesis inicial de IA o análisis técnico y la resolución final aprobada por el desarrollador.

## Estructura obligatoria por funcionalidad
Para cada funcionalidad, documentar un bloque con este formato:

### [FXX] Nombre de la funcionalidad
- **Contexto:** qué problema o requisito se está resolviendo.
- **Fuente de verdad:** qué documento o archivo fue la referencia primaria (`PRUEBA_TECNICA_CANDIDATO.md`, `README.md`, `routes/api.php`, fixtures, etc.).
- **Patologías o casos relevantes:** qué filas o condiciones del CSV sucio impactan esta funcionalidad.
- **Hipótesis inicial / sugerencia de IA:** qué propuso la IA o una primera solución tentativa.
- **Decisión final del desarrollador:** qué se implementó y por qué.
- **Riesgos y límites:** qué quedó fuera, qué se validará después o qué decisión se tomó para contener el alcance.
- **Estado:** `propuesta`, `aprobada`, `implementada`, `validada`.

## Reglas de documentación
- Cada decisión debe indicar la funcionalidad a la que pertenece.
- Las decisiones no pueden quedar como sugerencias generales; deben estar vinculadas a un caso concreto del proyecto.
- Si la IA propone una solución, debe quedar registrado qué sugiere la IA y qué resolución tomó el desarrollador.
- Si una decisión se modifica durante la implementación, se debe registrar el cambio con fecha y motivo, sin perder el histórico previo.
- Se debe documentar también qué se descartó y por qué.

## Registro mínimo recomendado
- Tratamiento de filas duplicadas.
- Manejo de campos faltantes o nulos.
- Validación de tipos y formatos.
- Reglas de exclusión o corrección.
- Regla de multi-tenancy y aislamiento por `company_id`.
- Decisiones de staging, apply, idempotencia y atomicidad.
- Tratamiento de `price`, `stock`, `deleted_at` y `sku` por empresa.

## Ejemplo de formato
### [F01] Staging y Job de importación
- **Contexto:** recibir CSV masivo y procesar sin tocar el catálogo productivo.
- **Fuente de verdad:** `PRUEBA_TECNICA_CANDIDATO.md`, `routes/api.php`, `fixtures/catalogo_sucio.csv`.
- **Patologías o casos relevantes:** filas con SKU nulo, precio mal formateado, stock inválido, duplicados.
- **Hipótesis inicial / sugerencia de IA:** validar y guardar todas las filas en una sola carga antes de dividir por validación.
- **Decisión final del desarrollador:** usar staging con validación por fila y `import_run_items`, separando validos vs rechazados sin tocar `products`.
- **Riesgos y límites:** evitar una validación demasiado agresiva que rechace filas útiles sin contexto.
- **Estado:** `aprobada`
