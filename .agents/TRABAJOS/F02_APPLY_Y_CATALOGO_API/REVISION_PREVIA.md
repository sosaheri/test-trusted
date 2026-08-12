# Revisión Previa y Validación — Funcionalidad 02 (Apply y Catálogo)

## 1. Diagnóstico de Requisitos de Transacción y Persistencia
- [ ] **Estrategia de Upsert/Insert:** Definir si la promoción a `products` actualiza registros existentes con el mismo SKU o inserta nuevos (`DB::table('products')->upsert(...)`).
- [ ] **Aislamiento Multi-Tenant:** Validar que el `WHERE company_id = ?` esté presente en la consulta de staging y en la verificación de `import_runs`.
- [ ] **Atomicidad:** Envolver la lógica de `apply` dentro de `DB::transaction(function () { ... })`.
- [ ] **Garantía de Decimales:** Verificar que las cadenas de `price` y `stock` se inserten respetando la precisión de PostgreSQL sin pasar por conversiones implícitas de PHP.

## 2. Propuesta Técnica para API de Catálogo (`GET /api/products`)
- [ ] **Paginación Server-side:** Uso de `$query->paginate($perPage)`.
- [ ] **Búsqueda Debounced:** Filtrado por `name` o `sku` con `ILIKE` / `LIKE`.
- [ ] **Aislamiento de Lectura:** Aplicar scope global o filtro explícito por `auth()->user()->company_id`.

## 3. Checklist de Transición
- [ ] Documentar en `.agents/DOCS/DECISIONS.md` el comportamiento de idempotencia y resolución de duplicados en la base de datos.
- [ ] Proceder con la Fase 3 (Ejecución de Código).