# REGLAS_MAESTRAS.md — Gobernanza y Especificación del Proyecto

## 1. Visión General de la Plataforma
- **Stack:** Laravel 10 (PHP 8.2), PostgreSQL 15, Redis, Vue 3.5 (Composition API) + Vuetify 3 + Pinia, Vite.
- **Entorno Execution:** Ejecución dentro de contenedores Docker (`app`, `truster_catalog_worker`, `postgres`, `redis`).
- **Autenticación Stub (Sanctum):**
  - Empresa Uno C.A. (`company_id: 1` | `ana@empresa-uno.test` | `password`)
  - Empresa Dos C.A. (`company_id: 2` | `beto@empresa-dos.test` | `password`)

## 2. Fuente de Verdad del Proyecto
La fuente de verdad no es un único archivo, sino la combinación de estos artefactos:

1. **`PRUEBA_TECNICA_CANDIDATO.md`** — contrato funcional y criterios de evaluación.
2. **`README.md`** — entorno, setup y alcance base del starter-kit.
3. **`routes/api.php`** — contrato técnico real del backend.
4. **`fixtures/`** — casos reales de entrada, patologías y volumen esperado.
5. **Código base y legado** — compatibilidad de contratos, props/emits y estructura del proyecto.

Los fixtures no reemplazan la prueba ni el código base: son evidencia de casos límite para validar la implementación.

## 3. Mapa del Repositorio e Insumos
- **Metodología de Trabajo:** `PROTOCOLO_DE_FASES.md` (Flujo obligatorio de 5 fases).
- **Especificación de Funcionalidades:** Carpeta `FUNCIONALIDADES/` (`F01_CORE_STAGING_Y_JOB.md`, etc.).
- **Revisiones Previas y Planificación:** Carpeta `TRABAJOS/[ID_FUNCIONALIDAD]/REVISION_PREVIA.md`.
- **Entregables Finales Auditoría/Documentación:** Carpeta `DOCS/` (`DECISIONS.md`, `AI_AUDIT.md`, `WALKTHROUGH.md`).
- **Fixtures (CSV de prueba):** Carpeta `fixtures/` (`catalogo_sucio.csv`, `catalogo_100k.csv`, `catalogo_preexistente.csv`).
- **Componente Legacy a Refactorizar:** `resources/js/components/LegacyProductRow.vue`.
- **Documentación obligatoria por funcionalidad:** `.agents/DOCS/DECISIONS.md` y `.agents/DOCS/AI_AUDIT.md`.

## 4. Invariantes Críticas de Negocio
1. **Multi-Tenancy:** `company_id` es obligatorio en todas las consultas/inserciones. Debe extraerse ÚNICAMENTE de `auth()->user()->company_id`. Jamás del payload o archivo subido.
2. **Tipos Financieros:** El precio (`price`) debe ser `DECIMAL(18,4)` y el stock (`stock`) debe ser `DECIMAL(14,6)`. Queda estrictamente PROHIBIDO el uso de `float` o `double`.
3. **Manejo Eficiente de Memoria:** Procesamiento por streams (`fgetcsv` / generadores) e inserciones en bloques (*chunks*) de máximo 1.000 filas.
4. **Gobernanza de 2 Fases:** 
   - *Fase A (Staging):* Guardado en `import_run_items` con validación suave.
   - *Fase B (Apply):* Promoción atómica a `products` envuelta en `DB::transaction`.
5. **Idempotencia:** Reintentar un Job o invocar `POST /apply` múltiples veces sobre la misma corrida debe producir el mismo resultado sin duplicar ni romper datos.
6. **Soft Deletes:** `deleted_at` activo en `products`. Un SKU borrado lógicamente no bloquea la creación de un nuevo SKU idéntico para la misma empresa.

## 5. Fuera de Alcance
- Autenticación real de usuarios, gestión de roles/permisos, CI/CD, deploy a producción, i18n, dark mode, WebSockets.
- Módulos de edición/borrado manual de productos, categorías, impuestos o exportaciones ERP adicionales.

## 6. Reglas de documentación obligatoria
Cada funcionalidad debe dejar evidencia explícita en:

- `.agents/DOCS/DECISIONS.md` — entregable final con decisiones de arquitectura y trade-offs; mantiene una sección inicial estable y un historial del proceso por patología y funcionalidad.
- `.agents/DOCS/AI_AUDIT.md` — entregable crítico con bitácora de prompts, auditoría de código generado por IA, revisión de PR ajena y estimación de ahorro. Debe mantenerse como trazabilidad en curso, no como documento "de cierre".

No se considera entregada una funcionalidad si no tiene trazabilidad documental de decisión, auditoría y validación.

## 7. Modo de Operación
Para desarrollar cualquier módulo, consulta el archivo correspondiente en `FUNCIONALIDADES/`, sigue el flujo definido en `PROTOCOLO_DE_FASES.md` y documenta la decisión final en `DECISIONS.md` y `AI_AUDIT.md` antes de cerrar la funcionalidad.