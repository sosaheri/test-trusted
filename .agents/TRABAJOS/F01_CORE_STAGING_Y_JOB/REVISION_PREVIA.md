# Revisión Previa y Validación — Funcionalidad 01 (Staging)

## 1. Diagnóstico de la Fase 1
### 1.1 Evidencia del proyecto
- `routes/api.php` declara claramente que estas rutas son responsabilidad del candidato:
  - `GET /products`
  - `POST /import-runs`
  - `GET /import-runs/{id}`
  - `POST /import-runs/{id}/apply`
- El starter-kit no incluye ni `Product` ni `ImportRun` modelados ni migrados.
- `users` sí incluye `company_id` desde la migración base, por lo que el aislamiento multi-tenant ya existe en la capa de usuario.
- `Company` documenta explícitamente que el modelo `Product` y su relación con `Company` son responsabilidad del candidato.

### 1.2 Patologías observadas en los fixtures
Revisé el contenido actual de `fixtures/catalogo_sucio.csv` y estas son las condiciones relevantes que impactan el diseño:
- SKU nulo o vacío
- SKU duplicado dentro del mismo archivo
- Precio con formato no estándar: `1,234.56`, `2.500,75`, `$12.99`
- Stock con valores no numéricos: `muchos`, `1.5E+4`, vacíos, negativos
- Campos faltantes: filas sin precio o sin stock
- Filas con columnas extra: `EXTRA_CAMPO_INESPERADO`
- Registros con delimitador distinto: `;` en lugar de `,`
- Encodings problemáticos: `VÃ¡lvula`, `JardÃ­n`, etc.
- Fila con salto de línea dentro del contenido

### 1.3 Conclusión de análisis
La funcionalidad no puede asumir que existe el endpoint ni la entidad. La tarea incluye crear el contrato API y la capa de staging si aún no existe.

---

## 2. Revisión de la Fase 2: propuesta técnica
### 2.1 Hipótesis inicial / sugerencia de IA
- **Qué sugiere la IA:** procesar todo el CSV en una sola carga y luego separar filas válidas vs rechazadas.
- **Riesgo real:** esto puede romper la restricción de memoria para `catalogo_100k.csv` y además mezclar validación con mutación en la misma operación.
- **Conclusión:** la IA puede proponer una solución viable, pero no es aceptable sin reforzar la lógica de streaming, staging y tenant isolation.

### 2.2 Decisión técnica propuesta
- **Estrategia de procesamiento:** leer el CSV con `fopen` + `fgetcsv` o generadores PHP, nunca cargar el archivo completo en memoria.
- **Tamaño de chunk:** trabajar en bloques de 1.000 registros para persistencia incremental.
- **Modelo de staging:** crear `import_runs` e `import_run_items` como tabla intermedia con estados `valid`/`rejected`.
- **Multi-tenancy:** forzar `company_id` desde `auth()->user()->company_id` y nunca desde el CSV o del payload.
- **Regla de negocio no negociable:** `products` no debe tocarse en la Fase A; la mutación real ocurre solo en Apply.

### 2.3 Decisión final del desarrollador
- Se implementará un flujo de importación con Fase A de staging y Fase B de apply.
- La Fase A solo validará y persistirá filas en `import_run_items`.
- La Fase B será una acción explícita que promueva esos registros a `products` dentro de una transacción SQL.
- El endpoint `POST /api/import-runs` formará parte de esta funcionalidad si aún no existe.

---

## 3. Riesgos, límites y decisiones de alcance
- No se debe construir autenticación real, roles ni ERP adicional.
- No se debe realizar ninguna mutación directa sobre `products` durante el staging.
- No se debe aceptar `float`/`double` en precio o stock.
- No se debe aplicar lógica que dependa del payload del cliente para establecer `company_id`.
- La decisión sobre cada patología del CSV debe quedar en `.agents/DOCS/DECISIONS.md`.

---

## 4. Checklist de transición
- [x] Diagnóstico del código base y fixtures realizado.
- [x] Evidencia de Fase 1 documentada.
- [x] Propuesta técnica de Fase 2 documentada.
- [ ] Registrar la decisión formal en `.agents/DOCS/DECISIONS.md`.
- [ ] Registrar la auditoría de IA en `.agents/DOCS/AI_AUDIT.md`.
- [ ] Esperar aprobación explícita para pasar a la Fase 3.

> Estado actual: `pendiente de aprobación`.
> Esta revisión previa no autoriza implementar la Fase 3 hasta que se confirme la decisión final.