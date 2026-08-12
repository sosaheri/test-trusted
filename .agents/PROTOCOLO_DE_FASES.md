# PROTOCOLO_DE_FASES.md — Flujo Obligatorio de Trabajo por Funcionalidad

Este protocolo establece la metodología estándar de 5 fases secuenciales que debe cumplir estrictamente cualquier funcionalidad descrita en `.agents/FUNCIONALIDADES/` antes de considerarse completada.

---

## FASE 1: Análisis (Analysis & Discovery)
**Objetivo:** Comprender las entradas, restricciones de negocio y patologías de datos sin modificar código ni estructuras.

1. Inspeccionar los requisitos de negocio y archivos de entrada (`fixtures/`, esquemas existentes, o código legacy).
2. Identificar casos de borde, limitaciones de memoria, tipos de datos requeridos y posibles colisiones (ej. multi-tenancy).
3. Confirmar las entradas y salidas especificadas en el archivo de la funcionalidad.

---

## FASE 2: Planificación y Validación Previa (Design & Staging)
**Objetivo:** Diseñar la solución técnica y documentar las decisiones formales **antes** de escribir código.

1. Crear la carpeta de trabajo del módulo: `.agents/TRABAJOS/[ID_FUNCIONALIDAD]/`.
2. Crear y completar el archivo `.agents/TRABAJOS/[ID_FUNCIONALIDAD]/REVISION_PREVIA.md` con:
   - Diagnóstico técnico de la funcionalidad.
   - Propuesta de arquitectura/modelado de base de datos.
   - Checklist de validación de invariantes (Multi-tenant, tipos `DECIMAL`, memoria).
3. Registrar las decisiones técnicas de la funcionalidad en `.agents/DOCS/DECISIONS.md`.
   - Debe mantener una sección inicial estable como entregable final.
   - Debe incluir patrón por cada patología del CSV sucio.
   - Debe documentar trade-offs, descartes y decisiones por empresa/tenant.
   - Debe reflejar si la solución usa `staging`, `apply`, `soft deletes`, `idempotencia` o `atomicidad`.
4. Si la funcionalidad tiene ambigüedad o conflicto entre requerimientos, resolverlo con evidencia y documentarlo en `DECISIONS.md`; no dejarlo implícito.

> Regla de trazabilidad: `DECISIONS.md` no se maneja como resumen final suelto; se mantiene con un bloque fijo de entregable y un historial operativo del proceso.

---

## FASE 3: Ejecución de Código (Implementation)
**Objetivo:** Construir la solución técnica respetando los lineamientos de `.agents/REGLAS_MAESTRAS.md`.

1. Implementar la capa de persistencia (Migraciones, Modelos Eloquent, índices PostgreSQL).
2. Construir la lógica de negocio backend (Jobs, Controllers, Servicios, Streams/Chunks).
3. Desarrollar/refactorizar componentes frontend (Vue 3 Composition API, Vuetify, Pinia) si aplica a la funcionalidad.
4. Durante el desarrollo, seguir actualizando la documentación de decisiones y evidencias en `.agents/DOCS/DECISIONS.md` si surgen cambios sobre la estrategia diseñada.

---

## FASE 4: Pruebas y Verificación de Negocio (Testing)
**Objetivo:** Garantizar mediante tests automáticos que la solución cumple con todas las invariantes de negocio.

1. Escribir y ejecutar tests automáticos (Pest / PHPUnit).
2. Validar expresamente:
   - **Aislamiento Multi-Tenant:** Ninguna consulta o acción filtra datos entre diferentes `company_id`.
   - **Gobernanza e Idempotencia:** Reejecutar procesos o endpoints produce resultados consistentes sin duplicaciones.
   - **Precisión Decimal:** Manejo estricto de `DECIMAL(18,4)` y `DECIMAL(14,6)`.
   - **Consumo de Memoria:** Procesamiento eficiente con streaming para datasets masivos (`catalogo_100k.csv`).
3. Si una prueba revela que una hipótesis técnica era incorrecta, registrar la corrección en `.agents/DOCS/DECISIONS.md` y en `.agents/DOCS/AI_AUDIT.md` si la corrección tuvo impacto en la calidad final.

---

## FASE 5: Auditoría y Cierre de Entregables (Audit & Documentation)
**Objetivo:** Registrar el impacto del desarrollo y actualizar la documentación de auditoría exigida.

1. Registrar en `.agents/DOCS/AI_AUDIT.md` (§6.1):
   - Los prompts clave o estrategias utilizadas durante el desarrollo.
2. Registrar en `.agents/DOCS/AI_AUDIT.md` (§6.2):
   - Errores, bugs o alucinaciones generados por la IA durante el proceso, detallando severidad, impacto ERP y el *diff* de la corrección.
3. Registrar en `.agents/DOCS/AI_AUDIT.md` (§6.3):
   - El review crítico a la rama `feat/ai-generated-importer`, con hallazgos, severidad y corrección propuesta.
   - El formato obligatorio por hallazgo es:

```md
### [ID] Título del hallazgo
- Archivo:línea
- Severidad: Crítico | Alto | Medio | Cosmético
- Impacto de negocio: (qué se rompe, para quién, en qué escenario)
- Corrección propuesta: (diff o descripción precisa)
- Confianza: Alta | Media | Baja
```

4. Registrar en `.agents/DOCS/AI_AUDIT.md` (§6.4):
   - Estimación de ahorro y la parte del trabajo donde la IA restó velocidad.
5. Verificar que los archivos finales en `.agents/DOCS/` reflejen fielmente lo construido.
6. El cierre no es completo si `DECISIONS.md` o `AI_AUDIT.md` están incompletos o no tienen evidencia verificable.

> Regla de trazabilidad: `AI_AUDIT.md` debe cubrir el proceso completo y no se postergará exclusivamente a la etapa final; se actualiza conforme aparecen hallazgos, pruebas y revisiones.

---

## Regla de documentación obligatoria
Para cada funcionalidad desarrollada, el agente debe dejar evidencia explícita en los documentos de auditoría y decisiones. El código no sustituye la documentación de arquitectura y auditoría. La solución se considera incompleta si no hay trazabilidad desde la funcionalidad hasta la decisión final y la evidencia de validación.