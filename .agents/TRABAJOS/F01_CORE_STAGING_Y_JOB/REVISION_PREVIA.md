# Revisión Previa y Validación — Funcionalidad 01 (Staging)

## 1. Diagnóstico de Fixtures
- [ ] Inspección de `fixtures/catalogo_sucio.csv`:
  - Patologías detectadas en precios (símbolos de moneda, comas como decimales).
  - Patologías en stocks (valores alfanuméricos, negativos, vacíos).
  - Comportamiento ante SKUs faltantes o duplicados intra-archivo.
- [ ] Inspección de `fixtures/catalogo_100k.csv`:
  - Verificación del volumen de datos y estimación de iteraciones por chunk.

## 2. Hipótesis inicial / sugerencia de IA
- **Qué sugiere la IA:** procesar el CSV en un servicio único, validar todo en memoria y luego persistir en lotes.
- **Riesgo detectado:** esta enfoque puede romper la restricción de uso de memoria y degradar el comportamiento para 100k filas.
- **Observación:** la IA puede proponer una solución funcional, pero debe validarse contra la regla de volumen y multi-tenancy.

## 3. Propuesta Técnica y Arquitectura
- [ ] **Estrategia de Streaming:** Confirmar uso de `fopen` y `fgetcsv` con generadores PHP en lugar de cargar el archivo completo en memoria (`file()` o `Slurp`).
- [ ] **Tamaño de Chunk:** Inserción en bloques de 1.000 registros mediante `ImportRunItem::insert()`.
- [ ] **Estructura de Errores:** Definir formato de respuesta en columna `errors` (ej. JSON estructurado `{"sku": ["El campo SKU es obligatorio"]}`).
- [ ] **Multi-tenancy:** Verificar que el `company_id` proviene exclusivamente de `auth()->user()->company_id` y se propaga al `ImportRun`.
- [ ] **Estado del flujo:** la validación previa debe ser aprobada antes de entrar a la Fase 3.

## 4. Decisión final propuesta del desarrollador
- **Decisión:** usar staging + job en background + validación por fila, sin tocar `products` en la primera fase.
- **Motivo:** esto respeta la gobernanza de dos fases, mantiene idempotencia y evita mutar el catálogo real antes de aplicar.
- **Riesgo controlado:** se documentará cada patología en `.agents/DOCS/DECISIONS.md` y se registrará la evidencia en `.agents/DOCS/AI_AUDIT.md`.

## 5. Checklist de transición
- [ ] Volcar la matriz formal de decisiones a `.agents/DOCS/DECISIONS.md`.
- [ ] Registrar el historial de hipótesis IA vs decisión final en `.agents/DOCS/AI_AUDIT.md`.
- [ ] Esperar aprobación antes de iniciar la Fase 3 (Ejecución de Código).
- [ ] Estado final: `pendiente de aprobación` / `aprobada` / `rechazada`.

> Reglas: esta revisión previa no se considera cerrada ni autorizada para pasar a implementación sin aprobación explícita.