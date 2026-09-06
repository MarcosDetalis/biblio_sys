# Base de datos de la Biblioteca

## Archivos principales

- `biblioteca_v3_tesis_final.sql`: estructura definitiva compatible con Clever Cloud. Incluye tablas, relaciones, índices, vistas y 19 procedimientos almacenados.
- `biblioteca_datos_prueba.sql`: datos de demostración relacionados para probar el sistema.
- `legacy/biblioteca_mvc_legacy.sql`: SQL original del proyecto, conservado únicamente como referencia histórica.

## Orden de instalación

1. Crear/seleccionar el add-on MySQL de Clever Cloud.
2. Importar `biblioteca_v3_tesis_final.sql`.
3. Importar `biblioteca_datos_prueba.sql`.
4. Verificar las vistas `vw_*`.

La versión definitiva no utiliza `CREATE TRIGGER`, `CREATE EVENT` ni `SET GLOBAL`, porque la aplicación se diseñó para funcionar sin privilegios administrativos del servidor.
- `consultas_utiles.sql`: consultas operativas y de diagnóstico para la presentación y pruebas.
