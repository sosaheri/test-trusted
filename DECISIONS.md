# DECISIONS

## deciosiones tomadas respecto incidencias en archivo sucio

### [F01] Tratamiento de SKU duplicado y filas conflictivas
- **Contexto:** el CSV sucio puede contener SKU repetidos, vacíos o conflictivos dentro del mismo lote; la importación necesita decidir si se rechaza, se deduplica o se marca para revisión.
- **Fuente de verdad:** `fixtures/catalogo_sucio.csv`, reglas de negocio del proyecto, `PRUEBA_TECNICA_CANDIDATO.md`.
- **Patología del CSV o caso relevante:** SKU nulo, repetido, caracteres raros o columnas mal alineadas.
- **Hipótesis inicial / sugerencia de IA:** aceptar todas las filas y corregirlas en memoria.
- **Decisión final del desarrollador:** aceptar SKU repetidos solo si pertenecen a distinta `company_id` y nunca dentro de la misma empresa; para la misma compañía, el SKU debe ser único en `products` y en la validación de staging debe impedirse la duplicación dentro del mismo lote. Las filas con SKU nulo o inconsistente se rechazan con motivo claro; las filas con SKU duplicado entre una misma empresa se marcan como `rejected` o `needs_review` según la regla de negocio aplicada.
- **Riesgos y límites:** un SKU puede repetirse entre empresas distintas, pero no dentro de la misma compañía. La validación debe hacerse sobre la combinación `company_id + sku`, no sobre el SKU global.
- **Estado:** `aprobada`

### [F01] Manejo de precios con formato no estándar
- **Contexto:** los precios del CSV sucio presentan varios formatos: `1,234.56`, `2.500,75`, `$12.99`, etc.
- **Fuente de verdad:** `fixtures/catalogo_sucio.csv` y reglas de validación contable.
- **Patología del CSV o caso relevante:** valor monetario con símbolo, separador de miles y separador decimal mezclados.
- **Hipótesis inicial / sugerencia de IA:** parsear el valor con una regex simplista y forzar el resultado.
- **Decisión final del desarrollador:** normalizar solo usando el formato estándar aceptado por el proyecto: si el valor contiene símbolo monetario, se elimina; si tiene separador de miles y decimal mixtos, se interpreta por el último separador decimal y el resto se trata como miles. Regla explícita: `1.234,56` => `1234.56`; `1,234.56` => `1234.56`; `$12.99` => `12.99`; si la interpretación no es determinista, se rechaza la fila con motivo claro. La validación final se hace contra `DECIMAL(18,4)`.
- **Riesgos y límites:** no se convierten valores ambiguos sin criterio; la regla se documenta y se aplica de manera consistente en todo el importador.
- **Estado:** `aprobada`

### [F01] Manejo de stock no numérico o negativo
- **Contexto:** el stock puede contener valores vacíos, negativos, alfanuméricos o notación no estándar.
- **Fuente de verdad:** `fixtures/catalogo_sucio.csv`, reglas del proyecto y validación de inventario.
- **Patología del CSV o caso relevante:** `muchos`, `1.5E+4`, vacíos, negativos, valores textuales.
- **Hipótesis inicial / sugerencia de IA:** convertir todo a `float` y aceptar valores razonables.
- **Decisión final del desarrollador:** solo aceptar valores numéricos válidos para `DECIMAL(14,6)`; si el valor es nulo, no numérico o negativo sin justificación, se rechaza con error estructurado.
- **Riesgos y límites:** se evita introducir errores de inventario y se mantiene consistencia a nivel financiero.
- **Estado:** `aprobada`

### [F01] Tratamiento de columnas faltantes o extra
- **Contexto:** el CSV sucio puede incluir filas con columnas faltantes o columnas inesperadas.
- **Fuente de verdad:** `fixtures/catalogo_sucio.csv`, validación de schema del importador.
- **Patología del CSV o caso relevante:** columnas extra `EXTRA_CAMPO_INESPERADO`; campos faltantes en la fila.
- **Hipótesis inicial / sugerencia de IA:** ignorar columnas extra y rellenar faltantes con ceros.
- **Decisión final del desarrollador:** columnas extra se registran como warning o rechazo según la política de schema; columnas faltantes implican rechazo de la fila si el campo crítico no existe.
- **Riesgos y límites:** se evita aceptar datos incompletos que puedan corromper inventario o precios.
- **Estado:** `aprobada`

### [F01] Delimitador y encoding del archivo
- **Contexto:** el CSV puede venir con delimitador distinto y caracteres no UTF-8.
- **Fuente de verdad:** `fixtures/catalogo_sucio.csv` y este proyecto de ERP con datos en español y símbolos locales.
- **Patología del CSV o caso relevante:** archivos con `;` como delimitador; caracteres como `VÃ¡lvula`, `JardÃ­n` y otros codificados incorrectamente.
- **Hipótesis inicial / sugerencia de IA:** usar una lógica no robusta y asumir `,` y UTF-8 por defecto.
- **Decisión final del desarrollador:** detectar encoding y delimitador antes de procesar; si no se puede normalizar sin ambigüedad, la fila o el archivo se rechaza con detalle de error.
- **Riesgos y límites:** no se aceptará un archivo que no pueda interpretarse de manera fiable.
- **Estado:** `aprobada`

### [F01] Filas con salto de línea o multilinea
- **Contexto:** algunos CSV de origen pueden contener contenido multiline dentro de una celda, lo cual rompe la interpretación por filas.
- **Fuente de verdad:** `fixtures/catalogo_sucio.csv` y requisitos de robustez del importador.
- **Patología del CSV o caso relevante:** una fila se extiende en varias líneas y rompe la estructura esperada.
- **Hipótesis inicial / sugerencia de IA:** ignorar el problema y asumir un CSV limpio.
- **Decisión final del desarrollador:** detectar filas con multilinea y rechazarlas con error estructural, salvo que exista una política explícita de normalización y validación avanzada.
- **Riesgos y límites:** la importación masiva debe ser determinista y no depender de heurísticas frágiles.
- **Estado:** `aprobada`