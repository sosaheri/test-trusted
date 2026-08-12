# DECISIONS

## Entregable final (mantenido al inicio)

| Archivo | Descripción |
| --- | --- |
| `DECISIONS.md` | Decisiones de arquitectura y trade-offs. Una entrada por patología del CSV sucio. Qué dejaste fuera de alcance y por qué. |

## Propósito del archivo
Registrar la trazabilidad de decisiones técnicas por funcionalidad y por patología de entrada, vinculando cada decisión con la evidencia del problema, la hipótesis inicial y la resolución final adoptada por el desarrollador.

## Regla de mantenimiento
Este documento tiene dos capas:
1. La sección de entregable final, que debe mantenerse fija al inicio del archivo.
2. El historial del proceso, que se va completando durante análisis, diseño, implementación y validación.

La sección final no se reescribe como un resumen genérico; es la versión estable de las decisiones de arquitectura, trade-offs y alcance que se entregan al cierre.

---

## Historial del proceso

### [FXX] Nombre de la funcionalidad
- **Contexto:** qué problema o requisito se está resolviendo.
- **Fuente de verdad:** qué documento o archivo fue la referencia primaria (`PRUEBA_TECNICA_CANDIDATO.md`, `README.md`, `routes/api.php`, fixtures, etc.).
- **Patología del CSV o caso relevante:** qué fila, condición o error del CSV sucio impacta esta decisión.
- **Hipótesis inicial / sugerencia de IA:** qué propuso la IA o una primera solución tentativa.
- **Decisión final del desarrollador:** qué se implementó y por qué.
- **Riesgos y límites:** qué quedó fuera de alcance, qué se descartó y por qué.
- **Estado:** `propuesta`, `aprobada`, `implementada`, `validada`.

## Reglas de documentación
- Cada decisión debe corresponder a una patología concreta del CSV sucio, una funcionalidad o un trade-off de arquitectura.
- Las decisiones no pueden quedar como sugerencias generales; deben estar ligadas a un caso real del proyecto.
- Si la IA propone una solución, debe quedar registrado qué sugirió y qué resolución tomó el desarrollador.
- Si una decisión cambia durante la implementación, se debe registrar el cambio con motivo, sin perder el historial previo.
- Se debe documentar también qué se descartó y por qué.

## Registro mínimo recomendado
- Tratamiento de filas duplicadas.
- Manejo de campos faltantes o nulos.
- Validación de tipos y formatos.
- Reglas de exclusión o corrección.
- Regla de multi-tenancy y aislamiento por `company_id`.
- Decisiones de staging, apply, idempotencia y atomicidad.
- Tratamiento de `price`, `stock`, `deleted_at` y `sku` por empresa.
- Qué quedó fuera de alcance por riesgo, complejidad o restricción funcional.

## Ejemplo de formato
### [F01] Staging y Job de importación
- **Contexto:** recibir CSV masivo y procesar sin tocar el catálogo productivo.
- **Fuente de verdad:** `PRUEBA_TECNICA_CANDIDATO.md`, `routes/api.php`, `fixtures/catalogo_sucio.csv`.
- **Patología del CSV o caso relevante:** SKU nulo, precio mal formateado, stock inválido y filas duplicadas.
- **Hipótesis inicial / sugerencia de IA:** validar y guardar todas las filas en una sola carga antes de segmentarlas por validación.
- **Decisión final del desarrollador:** usar staging con validación por fila y `import_run_items`, separando válidos y rechazados sin tocar `products`.
- **Riesgos y límites:** evitar una validación demasiado agresiva que rechace filas útiles sin contexto.
- **Estado:** `aprobada`

---

## Bitácora de decisiones en curso
- [F01] Se documentó que la Fase A debe quedar separada de la mutación real a `products`.
- [F01] Se registró la necesidad de procesar CSV por streams y chunks por memoria.
- [F01] Se dejó explícito que `company_id` debe obtenerse solo de `auth()->user()->company_id`.
- [F01] Se declaró que la API y la entidad de importación forman parte del alcance si no existen aún.

---

### [F01] Estado del staging y aplicación
- **Contexto:** la Fase A del import debe dejar evidencia de validación sin mutar el catálogo productivo, mientras la Fase B promueve los registros válidos a `products`.
- **Fuente de verdad:** `PRUEBA_TECNICA_CANDIDATO.md`, `app/Services/CatalogCsvImportService.php`, `app/Services/ImportRunApplyService.php`.
- **Patología del CSV o caso relevante:** la corrida debe quedar trazable incluso cuando el CSV es válido y el job se ejecuta de forma automática en cola.
- **Hipótesis inicial / sugerencia de IA:** usar un único estado final para toda la corrida, por ejemplo `completed`.
- **Decisión final del desarrollador:** mantener los estados `pending`, `processing`, `validated`, `failed`, `applied` como contrato real del sistema. `validated` representa la salida de la Fase A; `applied` representa la promesa final del catálogo. Se evita `completed` porque no formaba parte del flujo implementado ni del test de negocio.
- **Riesgos y límites:** cualquier documento o criterio que asuma `completed` como estado del proyecto queda desalineado con la implementación y debe ajustarse a la realidad del flujo validado.
- **Estado:** `validada`

### [F01] Uso de `DECIMAL` y precisión financiera
- **Contexto:** el proyecto exige precisión estricta en precio y stock para mantener integridad contable y operativa.
- **Fuente de verdad:** `PRUEBA_TECNICA_CANDIDATO.md`, reglas de negocio del proyecto, definición de `Product`.
- **Patología del CSV o caso relevante:** precios con comas, decimales mixtos, valores con `$`, y stocks inválidos.
- **Hipótesis inicial / sugerencia de IA:** usar `float` o `double` para normalizar el valor rápidamente.
- **Decisión final del desarrollador:** mantener `price` como `DECIMAL(18,4)` y `stock` como `DECIMAL(14,6)`; normalizar y validar los valores antes de persistir.
- **Riesgos y límites:** cualquier valor no convertible se rechaza; no se permiten tipos de punto flotante en la capa de persistencia ni en el flujo de apply.
- **Estado:** `aprobada`

### [F01] Tratamiento de SKU duplicado y filas conflictivas
- **Contexto:** el CSV sucio puede contener SKU repetidos, vacíos o conflictivos dentro del mismo lote; la importación necesita decidir si se rechaza, se deduplica o se marca para revisión.
- **Fuente de verdad:** `fixtures/catalogo_sucio.csv`, reglas de negocio del proyecto, `PRUEBA_TECNICA_CANDIDATO.md`.
- **Patología del CSV o caso relevante:** SKU nulo, repetido, caracteres raros o columnas mal alineadas.
- **Hipótesis inicial / sugerencia de IA:** aceptar todas las filas y corregirlas en memoria.
- **Decisión final del desarrollador:** aceptar SKU repetidos solo si pertenecen a distinta `company_id` y nunca dentro de la misma empresa; para la misma compañía, el SKU debe ser único en `products` y en la validación de staging debe impedirse la duplicación dentro del mismo lote. Las filas con SKU nulo o inconsistente se rechazan con motivo claro; las filas con SKU duplicado entre una misma empresa se marcan como `rejected` o `needs_review` según la regla de negocio aplicada.
- **Riesgos y límites:** un SKU puede repetirse entre empresas distintas, pero no dentro de la misma compañía. La validación debe hacerse sobre la combinación `company_id + sku`, no sobre el SKU global.
- **Estado:** `aprobada`

### [F01] Manejo de precios con formato no estándar
- **Contexto:** los precios del CSV sucio presentan varios formatos: `1,234.56`, `2.500,75`, `$12.99`, etc.
- **Fuente de verdad:** `fixtures/catalogo_sucio.csv` y reglas de validación contable.
- **Patología del CSV o caso relevante:** valor monetario con símbolo, separador de miles y separador decimal mezclados.
- **Hipótesis inicial / sugerencia de IA:** parsear el valor con una regex simplista y forzar el resultado.
- **Decisión final del desarrollador:** normalizar solo usando el formato estándar aceptado por el proyecto: si el valor contiene símbolo monetario, se elimina; si tiene separador de miles y decimal mixtos, se interpreta por el último separador decimal y el resto se trata como miles. Regla explícita: `1.234,56` => `1234.56`; `1,234.56` => `1234.56`; `$12.99` => `12.99`; si la interpretación no es determinista, se rechaza la fila con motivo claro. La validación final se hace contra `DECIMAL(18,4)`.
- **Riesgos y límites:** no se convierten valores ambiguos sin criterio; la regla se documenta y se aplica de manera consistente en todo el importador.
- **Estado:** `aprobada`

### [F01] Manejo de stock no numérico o negativo
- **Contexto:** el stock puede contener valores vacíos, negativos, alfanuméricos o notación no estándar.
- **Fuente de verdad:** `fixtures/catalogo_sucio.csv`, reglas del proyecto y validación de inventario.
- **Patología del CSV o caso relevante:** `muchos`, `1.5E+4`, vacíos, negativos, valores textuales.
- **Hipótesis inicial / sugerencia de IA:** convertir todo a `float` y aceptar valores razonables.
- **Decisión final del desarrollador:** solo aceptar valores numéricos válidos para `DECIMAL(14,6)`; si el valor es nulo, no numérico o negativo sin justificación, se rechaza con error estructurado.
- **Riesgos y límites:** se evita introducir errores de inventario y se mantiene consistencia a nivel financiero.
- **Estado:** `aprobada`

### [F01] Tratamiento de columnas faltantes o extra
- **Contexto:** el CSV sucio puede incluir filas con columnas faltantes o columnas inesperadas.
- **Fuente de verdad:** `fixtures/catalogo_sucio.csv`, validación de schema del importador.
- **Patología del CSV o caso relevante:** columnas extra `EXTRA_CAMPO_INESPERADO`; campos faltantes en la fila.
- **Hipótesis inicial / sugerencia de IA:** ignorar columnas extra y rellenar faltantes con ceros.
- **Decisión final del desarrollador:** columnas extra se registran como warning o rechazo según la política de schema; columnas faltantes implican rechazo de la fila si el campo crítico no existe.
- **Riesgos y límites:** se evita aceptar datos incompletos que puedan corromper inventario o precios.
- **Estado:** `aprobada`

### [F01] Delimitador y encoding del archivo
- **Contexto:** el CSV puede venir con delimitador distinto y caracteres no UTF-8.
- **Fuente de verdad:** `fixtures/catalogo_sucio.csv` y este proyecto de ERP con datos en español y símbolos locales.
- **Patología del CSV o caso relevante:** archivos con `;` como delimitador; caracteres como `VÃ¡lvula`, `JardÃ­n` y otros codificados incorrectamente.
- **Hipótesis inicial / sugerencia de IA:** usar una lógica no robusta y asumir `,` y UTF-8 por defecto.
- **Decisión final del desarrollador:** detectar encoding y delimitador antes de procesar; si no se puede normalizar sin ambigüedad, la fila o el archivo se rechaza con detalle de error.
- **Riesgos y límites:** no se aceptará un archivo que no pueda interpretarse de manera fiable.
- **Estado:** `aprobada`

### [F01] Filas con salto de línea o multilinea
- **Contexto:** algunos CSV de origen pueden contener contenido multiline dentro de una celda, lo cual rompe la interpretación por filas.
- **Fuente de verdad:** `fixtures/catalogo_sucio.csv` y requisitos de robustez del importador.
- **Patología del CSV o caso relevante:** una fila se extiende en varias líneas y rompe la estructura esperada.
- **Hipótesis inicial / sugerencia de IA:** ignorar el problema y asumir un CSV limpio.
- **Decisión final del desarrollador:** detectar filas con multilinea y rechazarlas con error estructural, salvo que exista una política explícita de normalización y validación avanzada.
- **Riesgos y límites:** la importación masiva debe ser determinista y no depender de heurísticas frágiles.
- **Estado:** `aprobada`


