# Prueba Técnica Asíncrona — Desarrollador Fullstack Senior
**Truster Cloud · Laravel 10 / PostgreSQL / Vue 3**

---

## 1. Contexto

Truster Cloud es una plataforma ERP/CRM **B2B multi-tenant** en producción. Soporta operaciones críticas de terceros: facturación fiscal, inventario y logística. Stack: **Vue 3.5 (Composition API) + Vuetify 3 + Pinia**, **Laravel 10 / PHP 8.2**, **PostgreSQL**, sobre **VPS autoadministrado**.

En este puesto el código se escribe con asistencia de IA de forma cotidiana y obligatoria. No evaluamos tu velocidad tecleando: evaluamos tu **criterio de arquitectura** y tu **capacidad de auditar y rechazar** lo que la IA propone. Un desarrollador senior que acepta código generado sin auditarlo es, en nuestro contexto, un riesgo operativo directo.

## 2. Formato

**Desafío take-home asíncrono. Sin entrevista técnica previa ni posterior obligatoria.**

- Presupuesto declarado: **4 horas efectivas**.
- Plazo de entrega: **48 horas** desde la recepción de este documento.
- Uso de IA: **obligatorio y sin restricción de herramienta o modelo**.
- Canal de dudas: **tecnologia@trusterconsulting.com**. Preguntar está bien visto; el enunciado tiene ambigüedades deliberadas.

La evaluación se realiza sobre el repositorio entregado. Si el material es concluyente, avanzamos a oferta o cierre sin llamada adicional. Si quedan puntos abiertos, agendamos una sesión de 30 minutos para resolverlos.

## 3. Acceso al repositorio base

Repositorio: **https://github.com/truster-consulting/fullstack-developer-test**

```bash
git clone https://github.com/truster-consulting/fullstack-developer-test.git
cd fullstack-developer-test
cp .env.example .env
```

El `README.md` del repositorio tiene el detalle exacto de cómo levantar el
entorno (Docker Compose: PHP 8.2 + PostgreSQL 15 + Redis) y las credenciales
de los dos usuarios semilla (`company_id` 1 y 2). Debería tomarte menos de
5 minutos.

El repositorio tiene dos ramas:

- **`main`** — el starter-kit: Laravel 10 instalado, autenticación stub,
  el componente `LegacyProductRow.vue` a refactorizar, y los fixtures.
  Es tu punto de partida.
- **`feat/ai-generated-importer`** — una implementación completa del
  importador de catálogo **generada por IA**. No la uses como base de tu
  propio trabajo. Revísala como si fuera el pull request de un compañero:
  este ejercicio de auditoría es la sección **5.3** de este documento y
  pesa 30% de la nota junto con tu propia auto-auditoría.

## 4. El desafío: Importador de Catálogo B2B con gobernanza de dos fases

### 4.1 Requerimiento funcional

Un usuario autenticado de una empresa carga un `.csv` de catálogo (`name`, `sku`, `price`, `stock`). El sistema debe:

1. Aceptar el archivo y responder **inmediatamente** con el identificador de una **corrida de importación** (`import_run`). Nunca procesar dentro del ciclo request/response.
2. Procesar en background vía **Queues/Jobs**, tolerando archivos de **100.000 filas** sin agotar memoria.
3. Operar bajo **gobernanza de dos fases**, replicando el modelo de ajustes de nuestro Kardex:
   - **Fase A — Staging:** el archivo se parsea y valida hacia una tabla intermedia. Nada toca el catálogo productivo. La corrida queda en estado `validated` con resumen: filas válidas, filas rechazadas y motivo por fila.
   - **Fase B — Aplicación:** una acción explícita del usuario (`POST /import-runs/{id}/apply`) promueve el staging al catálogo real. Solo aquí se muta `products`.
4. Exponer el catálogo del tenant con **paginación, ordenamiento y búsqueda resueltos en servidor**.

### 4.2 Restricciones de datos no negociables

Son reglas reales del ERP. Impleméntalas en el esquema, no en la capa de aplicación:

- **Aislamiento por `company_id`** en todas las tablas operativas. Un usuario no puede leer, escribir, contar ni provocar colisión alguna con datos de otra empresa.
- **`sku` único por empresa**, no globalmente. El mismo SKU puede existir en dos empresas distintas y ambos deben poder crearse.
- **Soft deletes obligatorios** (`deleted_at`). Un SKU borrado lógicamente no debe bloquear la creación de un SKU nuevo con el mismo código.
- **`price`:** `DECIMAL(18,4)`. **`stock`:** `DECIMAL(14,6)`. Prohibido `float`/`double` en cualquier punto del pipeline, incluido el parseo.
- **Idempotencia:** subir dos veces el mismo archivo, o reintentar un job fallido, no puede duplicar ni corromper registros. Un `apply` ejecutado dos veces sobre la misma corrida debe ser inocuo.
- **Atomicidad:** un fallo de red o de worker a mitad de la Fase B no puede dejar el catálogo en estado intermedio inconsistente.

### 4.3 El archivo sucio

`fixtures/catalogo_sucio.csv` contiene datos deliberadamente patológicos. `fixtures/catalogo_preexistente.csv` es un catálogo limpio pensado para importarse primero, de forma que puedas probar colisiones reales contra datos ya aplicados (no solo intra-archivo).

**No te diremos qué hacer con cada caso.** Esa es la evaluación. Toma una decisión por cada patología, impleméntala de forma consistente y **justifícala en `DECISIONS.md`**. Criterio de aprobación: el importador no crashea, no coacciona datos en silencio, y produce un reporte de rechazos accionable para el usuario final.

`fixtures/catalogo_100k.csv` valida el requisito de volumen.

### 4.4 Frontend

SPA mínima en Vue 3 + Vuetify 3 que resuelva **todo el flujo en una sola pantalla maestra** (usa drawers o diálogos, no navegación entre vistas):

- Carga del archivo con feedback de progreso de la corrida (polling aceptable; justifica la frecuencia).
- Tabla del catálogo con paginación **server-side** y búsqueda con debounce.
- Drawer de resultados: resumen, listado de rechazos y acción de `apply`.
- **Refactor obligatorio:** `resources/js/components/LegacyProductRow.vue` viene en Options API. Migralo a Composition API con `<script setup>` sin alterar su contrato de props/emits (documentado en el propio archivo).

## 5. Entregables

Repositorio Git (**no aplastes los commits** — el historial es parte de la evaluación) con:

| Archivo | Contenido |
|---|---|
| `README.md` | Cómo levantar y correr en menos de 5 minutos. Comandos exactos. |
| `DECISIONS.md` | Decisiones de arquitectura y trade-offs. Una entrada por patología del CSV sucio. Qué dejaste fuera de alcance y por qué. |
| `AI_AUDIT.md` | Ver sección 6. Es el entregable de mayor peso. |
| `WALKTHROUGH` | Video sin editar de **5–10 min** (Loom, Drive o similar). Ver sección 7. |
| Tests | Caminos críticos, no cobertura total. |

## 6. `AI_AUDIT.md` — entregable crítico

Cuatro secciones obligatorias.

**6.1 Bitácora de prompts.** Los prompts estructurales (no los triviales), con modelo utilizado y en qué parte del código impactó cada uno.

**6.2 Auditoría de tu propio código generado.** Mínimo **tres** fallos que la IA introdujo y que corregiste. Por cada uno: snippet generado, severidad, vector de impacto **concreto en un ERP multi-tenant**, y el diff de tu corrección. Buscamos fallos con consecuencia real —fuga entre tenants, pérdida de precisión decimal, consumo de memoria, SQLi, race condition—, no formateo ni naming.

**6.3 Auditoría de PR ajeno.** La rama `feat/ai-generated-importer` del repositorio contiene una implementación completa generada por IA (ver sección 3). **No la uses como base.** Revísala como si fuera el PR de un compañero y entrega el review con este formato por hallazgo:

```
### [ID] Título del hallazgo
- Archivo:línea
- Severidad: Crítico | Alto | Medio | Cosmético
- Impacto de negocio: (qué se rompe, para quién, en qué escenario)
- Corrección propuesta: (diff o descripción precisa)
- Confianza: Alta | Media | Baja
```

Advertencia explícita: **el código de esa rama contiene también decisiones que parecen incorrectas y son deliberadamente correctas dadas las restricciones de la sección 4.2.** Reportar un falso positivo con severidad alta penaliza. La precisión pesa tanto como la cobertura.

**6.4 Estimación de ahorro.** Horas estimadas sin IA vs. con IA, y en qué partes del trabajo la IA te **restó** velocidad.

## 7. Video walkthrough (obligatorio)

5 a 10 minutos, grabación de pantalla continua y **sin editar**, cubriendo:

1. Recorrido por tu decisión de arquitectura principal y por qué descartaste la alternativa.
2. Cómo garantizas el aislamiento de tenant dentro del Job en background.
3. Un hallazgo de la sección 6.3 explicado con el código en pantalla.
4. **En vivo, sin cortes:** qué archivos tocarías y qué cambiarías si mañana el catálogo pasara a ser stock por almacén (`warehouse_id`). No lo implementes; explícalo navegando tu propio código.

No buscamos producción audiovisual. Buscamos que puedas moverte por tu propio repositorio sin dudar.

## 8. Criterios de evaluación

| Dimensión | Peso |
|---|---|
| Auditoría de IA (`AI_AUDIT.md` 6.2 y 6.3) | 30% |
| Integridad multi-tenant y modelo de datos | 25% |
| Idempotencia, atomicidad y manejo del CSV sucio | 18% |
| Calidad de código y tests de caminos críticos | 12% |
| Walkthrough: dominio real del código propio | 8% |
| Frontend (server-side, pantalla única, refactor) | 5% |
| DX y documentación | 2% |

**Descalificación automática**, con independencia del resto:

1. Endpoint o consulta que permita leer o mutar datos de otra empresa.
2. `sku` con restricción única global en lugar de compuesta por tenant.
3. `float`/`double` en precio o stock, en cualquier punto del pipeline.
4. Lectura del CSV completo en memoria.
5. Concatenación de input de usuario en SQL.
6. Archivo cargado accesible públicamente.
7. `AI_AUDIT.md` genérico, sin snippets ni diffs verificables.
8. Ausencia de video walkthrough.

## 9. Fuera de alcance

No lo construyas: autenticación real, gestión de usuarios y roles, CI/CD, deploy, Docker de producción, i18n, dark mode, WebSockets, edición o borrado de productos, categorías, impuestos, imágenes, exportación, y cualquier módulo ERP adicional.

**Construirlo resta puntos.** Interpretar y contener el alcance es parte de la evaluación.

## 10. Nota sobre el presupuesto de tiempo

No esperamos que termines todo en 4 horas. Esperamos que **priorices bien y documentes lo que quedó fuera** en `DECISIONS.md`. Un entregable parcial con criterio explícito puntúa por encima de uno completo y frágil.

## 11. Cómo entregar

1. Crea un repositorio **propio** (puede ser privado) partiendo de un clon de `main` — no trabajes sobre un fork público del repositorio de Truster Cloud.
2. Desarrolla ahí, con historial de commits normal (sin aplastar).
3. Si el repositorio es privado, agrega como colaborador a **tecnologia@trusterconsulting.com** o al usuario de GitHub que te indiquemos al confirmar recepción de esta prueba.
4. Envía el link del repositorio y del video walkthrough por correo a **tecnologia@trusterconsulting.com** antes de que venzan las 48 horas.

Cualquier duda sobre el alcance, escribe al mismo correo. Preguntar está bien visto.
