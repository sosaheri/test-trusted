# TAREA AISLADA 04: Auditoría del PR Generado por IA (`feat/ai-generated-importer`)

## 1. Descripción de Negocio
Realizar una revisión de código (*Code Review*) rigurosa e independiente sobre la rama `feat/ai-generated-importer`[cite: 3, 4]. El objetivo es identificar fallos críticos de seguridad, fugas multi-tenant, pérdidas de precisión financiera o cuellos de botella de memoria sin usar dicho código como base de la solución propia[cite: 2, 3, 4].

## 2. Insumos Esperados (Inputs)
- Rama Git: `feat/ai-generated-importer`[cite: 3, 4].
- Commits y diffs resultantes de ejecutar `git diff main..feat/ai-generated-importer`.
- Restricciones de negocio declaradas en `.agents/REGLAS_MAESTRAS.md`[cite: 2, 4].

## 3. Entregables y Emisiones (Outputs)
- **Documentación de Auditoría:**
  - Diligentado de la Sección §6.3 en `.agents/DOCS/AI_AUDIT.md` documentando mínimo 3 hallazgos con la estructura estandarizada (ID, Archivo:Línea, Severidad, Impacto de Negocio, Corrección Propuesta y Confianza)[cite: 4].
  - Identificación explícita de decisiones que parecen incorrectas en la rama pero que son deliberadamente correctas según las reglas del ERP para evitar falsos positivos[cite: 4].

## 4. Ejecución
*Revisa los diffs de la rama `feat/ai-generated-importer` y traslada los hallazgos directamente a `.agents/DOCS/AI_AUDIT.md`.*