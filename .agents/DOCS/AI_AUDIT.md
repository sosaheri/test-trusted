# AI_AUDIT

## Propósito
Registrar el historial de auditoría del trabajo generado con IA, con evidencia por funcionalidad, hallazgo y decisión humana. El objetivo es mostrar qué sugirió la IA, qué falló, y qué resolución aplicó el desarrollador.

## Estructura obligatoria
### 1) Bitácora de prompts
- **Fecha / funcionalidad**
- **Prompt estructural**
- **Modelo usado**
- **Área impactada**
- **Resultado observado**

### 2) Hallazgos por funcionalidad
Para cada hallazgo, usar exactamente este formato:

```md
### [ID] Título del hallazgo
- Archivo:línea
- Severidad: Crítico | Alto | Medio | Cosmético
- Impacto de negocio: (qué se rompe, para quién, en qué escenario)
- Corrección propuesta: (diff o descripción precisa)
- Confianza: Alta | Media | Baja
```

### 3) Registro de hipótesis IA vs decisión humana
Para cada hallazgo o decisión crítica:
- **Funcionalidad**
- **Qué sugirió la IA**
- **Qué detecté como problema**
- **Qué tomé como decisión final**
- **Por qué**

### 4) Review de la rama ajena
- Revisar `feat/ai-generated-importer` como PR de compañero.
- Documentar fallos reales, no falsos positivos.
- Tener claridad entre lo que es correcto por diseño y lo que es un error real.

## Reglas de documentación
- No dejar auditoría genérica; cada hallazgo debe tener un `ID`, archivo, impacto, corrección y confianza.
- Si la IA propuso algo, debe quedar reflejado qué propuso y qué decisión humana la corrigió.
- Debe quedar claro qué es una sugerencia de IA, qué es una decisión final del desarrollador y qué es una evidencia de validación.
- El documento debe abarcar tanto la auto-auditoría como la revisión de la rama ajena.

## Ejemplo mínimo
### [H01] Fuga de tenant por uso de `company_id` en el Job
- Archivo:App/Jobs/ProcessImportRunJob.php:42
- Severidad: Crítico
- Impacto de negocio: un usuario de una empresa podría leer o mutar datos de otra empresa si el job usa el `company_id` del payload.
- Corrección propuesta: forzar `company_id` desde `auth()->user()->company_id` en la corrida o desde la entidad del `import_run` y no desde el CSV.
- Confianza: Alta

### [H02] Carga completa de CSV en memoria
- Archivo:App/Services/ImportCatalogService.php:18
- Severidad: Alto
- Impacto de negocio: importación de 100k filas puede agotar memoria y colapsar la worker.
- Corrección propuesta: usar streams con `fgetcsv` y chunks de 1.000 filas.
- Confianza: Alta
