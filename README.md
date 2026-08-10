# Truster Cloud — Starter-kit: Importador de Catálogo B2B

Repositorio base para la prueba técnica asíncrona de Desarrollador Fullstack
Senior (Laravel 10 / PostgreSQL / Vue 3). Lee primero el documento de la
prueba que te enviamos por correo — este README solo cubre cómo levantar
el entorno.

## Qué trae este starter-kit

- `docker-compose.yml` con PHP 8.2, PostgreSQL 15 y Redis.
- Laravel 10 instalado (sin catálogo, sin importador — eso es tu entrega).
- Autenticación **stub** vía Sanctum, con dos empresas semilla y un usuario
  por empresa (ver credenciales abajo).
- `resources/js/components/LegacyProductRow.vue` en Options API — refactor
  obligatorio a `<script setup>` sin romper su contrato de props/emits.
- `fixtures/catalogo_sucio.csv` y `fixtures/catalogo_100k.csv`.
- Rama `feat/ai-generated-importer`: una implementación completa del
  importador generada por IA, para que la revises como el PR de un
  compañero (ver §5.3 del enunciado). **No la uses como base de tu propia
  implementación.**

## Levantar el entorno (< 5 min)

```bash
git clone <este-repo> truster-catalog-import
cd truster-catalog-import
cp .env.example .env

docker compose build
docker compose up -d

docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

Backend arriba en `http://localhost:8000`. El worker de colas
(`truster_catalog_worker`) ya queda corriendo dentro de `docker compose up -d`.

Para el frontend (fuera de los contenedores, necesitas Node 18+ en el host):

```bash
npm install
npm run dev
```

Vite queda en `http://localhost:5173` y proxyea `/api` contra el backend.

## Credenciales semilla

| Empresa | Email | Password | company_id |
|---|---|---|---|
| Empresa Uno C.A. | `ana@empresa-uno.test` | `password` | 1 |
| Empresa Dos C.A. | `beto@empresa-dos.test` | `password` | 2 |

`POST /api/login` con esas credenciales devuelve un Bearer token (Sanctum).
Úsalo para probar el aislamiento cross-tenant con ambos usuarios.

## Fixtures

- `fixtures/catalogo_sucio.csv` — datos deliberadamente patológicos. Ver
  §3.3 del enunciado: decide y documenta en `DECISIONS.md` qué hacer con
  cada patología, no hay respuesta correcta única.
- `fixtures/catalogo_100k.csv` — 100.000 filas limpias, para validar que
  el pipeline no agota memoria ni degrada con volumen.

## Rama con implementación generada por IA

```bash
git checkout feat/ai-generated-importer
```

Contiene migración, Job, controllers y un componente Vue completos.
Revísala con criterio de code review (§5.3 del enunciado) e incorpora tus
hallazgos en `AI_AUDIT.md`. No la mergees ni la uses como punto de partida.

## Qué falta por construir (tu entrega)

Todo lo funcional: esquema de `products` con las restricciones de §3.2,
`ImportRun` con gobernanza de dos fases, el Job de importación, los
endpoints de catálogo/import/apply, y la pantalla maestra en Vue. Revisa
`routes/api.php` para los TODOs de rutas esperadas.
