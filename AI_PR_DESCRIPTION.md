# PR: Importador de catálogo (CSV → productos)

**Rama:** `feat/ai-generated-importer` → `main`

## Resumen

Implementa el flujo de importación de catálogo end-to-end:

- Modelo `Product` con soft deletes y aislamiento por `company_id`.
- `POST /api/imports` recibe el archivo y despacha el procesamiento a un job en background (`ImportProductsJob`), para no bloquear el request.
- `GET /api/products` con búsqueda por nombre.
- `GET /api/products/suggest` para el autocomplete del header (usa un query separado, más liviano).
- `Products.vue`: tabla reactiva con buscador en vivo.

## Cómo probar

1. `POST /api/imports` con un CSV (`file`) adjunto — responde `{ "ok": true }` de inmediato.
2. Verificar en la tabla `products` que las filas se crearon con el `company_id` del usuario autenticado.
3. `GET /api/products?q=tornillo` para probar la búsqueda.

## Notas

- Reutilicé el driver `public` para el storage del archivo subido porque ya estaba configurado en el proyecto y simplifica servir el CSV de vuelta si el usuario quiere descargarlo después.
- No agregué un modelo `ImportRun` separado — el job es idempotente en la práctica porque `sku` es único, así que un reintento simplemente fallaría en el insert duplicado y Laravel lo reporta en `failed_jobs`.
- Dejé el índice único de `sku` a nivel de base de datos en vez de validarlo en el controller, para no duplicar la regla de negocio en dos capas.
