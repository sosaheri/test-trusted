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
