# AI_AUDIT

## Propósito
Este documento es un entregable crítico y debe mantenerse como trazabilidad viva del proceso de análisis, diseño, implementación y validación. No se trata como un resumen final escrito al final del trabajo; se registra en tiempo real y se corrige conforme aparecen hallazgos reales.

## Estructura obligatoria
### 6.1 Bitácora de prompts
- **Fecha / funcionalidad**
- **Prompt estructural**
- **Modelo usado**
- **Área impactada**
- **Resultado observado**

### 6.2 Auditoría de tu propio código generado
Debe incluir al menos tres fallos reales introducidos por IA y corregidos por el desarrollador. Cada hallazgo debe incluir:
- snippet generado,
- severidad,
- vector de impacto concreto en un ERP multi-tenant,
- el diff de la corrección.

No se aceptan hallazgos triviales de naming o formato; la auditoría debe enfocarse en consecuencias reales como:
- fuga entre tenants,
- pérdida de precisión decimal,
- consumo de memoria,
- SQLi,
- race condition,
- invalidación de idempotencia,
- mutación en producción antes de apply.

### 6.3 Auditoría de PR ajeno
La rama `feat/ai-generated-importer` del repositorio contiene una implementación completa generada por IA (ver sección 3). No se usa como base. Se revisa como un PR de un compañero y se entrega con este formato por hallazgo:

```md
### [ID] Título del hallazgo
- Archivo:línea
- Severidad: Crítico | Alto | Medio | Cosmético
- Impacto de negocio: (qué se rompe, para quién, en qué escenario)
- Corrección propuesta: (diff o descripción precisa)
- Confianza: Alta | Media | Baja
```

Advertencia explícita: el código de esa rama contiene también decisiones que parecen incorrectas y son deliberadamente correctas dadas las restricciones de la sección 4.2. Reportar un falso positivo con severidad alta penaliza. La precisión pesa tanto como la cobertura.

### 6.4 Estimación de ahorro
- Horas estimadas sin IA vs. con IA.
- En qué partes del trabajo la IA te restó velocidad.
- Qué tareas se aceleraron y qué parte requiere control humano.

## Reglas de documentación
- La auditoría se lleva desde la primera hipótesis y no se postergue para el cierre.
- Cada hallazgo debe tener `ID`, archivo, impacto, corrección y confianza.
- Si la IA propuso algo, debe quedar registrado qué propuso y qué decisión humana la corrigió.
- La rama ajena se evalúa con criterio de revisión real, no como comparación con un ideal abstracto.
- Los hallazgos deben ser accionables y medibles.

## Registro mínimo recomendado
### [H01] Fuga de tenant por uso de `company_id` en el Job
- Archivo:App/Jobs/ProcessImportRunJob.php:42
- Severidad: Crítico
- Impacto de negocio: un usuario de una empresa podría leer o mutar datos de otra empresa si el job usa el `company_id` del payload.
- Corrección propuesta: forzar `company_id` desde `auth()->user()->company_id` en la corrida o desde la entidad del `import_run` y no desde el CSV.
- Confianza: Alta

### [H02] Carga completa de CSV en memoria
- Archivo:App/Services/ImportCatalogService.php:18
- Severidad: Alto
- Impacto de negocio: la importación de 100k filas puede agotar memoria y desmontar la worker.
- Corrección propuesta: usar streams con `fgetcsv` y chunks de 1.000 filas.
- Confianza: Alta

### [H03] Pérdida de precisión decimal en precios y stocks
- Archivo:App/Models/Product.php:24
- Severidad: Crítico
- Impacto de negocio: se puede vender con precios incorrectos o inventariar con stock no fiable, afectando finanzas y operaciones.
- Corrección propuesta: usar tipos `DECIMAL(18,4)` y `DECIMAL(14,6)`; prohibir `float` o `double` en la capa de persistencia.
- Confianza: Alta
