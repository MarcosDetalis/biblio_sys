# Sistema de Gestión de Biblioteca Universitaria

## Versión final para tesis

Este proyecto corresponde a un sistema web MVC desarrollado en PHP para gestionar el catálogo bibliográfico, usuarios, reservas, préstamos, devoluciones y control administrativo de una biblioteca universitaria.

## Arquitectura

- **Backend:** PHP con arquitectura MVC propia.
- **Acceso a datos:** PDO y consultas preparadas.
- **Base de datos:** MySQL/Percona en Clever Cloud.
- **Interfaz:** HTML, CSS, Bootstrap 4, jQuery, DataTables, Select2 y SweetAlert2.
- **Reportes:** FPDF.
- **QR:** lector HTML5 y módulo de reservas.

## Módulos

1. Autenticación y sesiones.
2. Usuarios, tipos de usuario, roles y permisos.
3. Estudiantes y carreras.
4. Autores.
5. Editoriales.
6. Materias.
7. Catálogo de libros.
8. Ejemplares físicos e inventario.
9. Reservas.
10. QR de reserva.
11. Préstamos.
12. Devoluciones.
13. Renovaciones.
14. Multas y pagos.
15. Notificaciones y auditoría.
16. Dashboard y reportes.

## Base de datos

La aplicación utiliza exclusivamente el modelo `biblioteca_v3`. El SQL antiguo del proyecto se conserva en `database/legacy/` y no debe utilizarse para la instalación final.

## Clever Cloud

La configuración actual utiliza credenciales de prueba en `Config/Config.php`, según el requerimiento del entorno de demostración. En producción se recomienda utilizar variables de entorno. El ejemplo recomendado está comentado dentro de ese archivo.

## Usuarios de demostración

Los datos de prueba incluyen usuarios con contraseña común `123456` para facilitar la demostración. Antes de una publicación pública deben sustituirse por credenciales propias.

## Procedimientos almacenados

Las operaciones críticas de reservas, préstamos, devoluciones, renovaciones, QR y multas se centralizan mediante procedimientos almacenados. Se evita depender de triggers y eventos del servidor para mantener compatibilidad con Clever Cloud.

## Inventario de la base de datos

- 44 tablas físicas.
- 7 vistas (`vw_*`).
- 20 procedimientos almacenados.
- Claves foráneas e índices para las operaciones principales.
- `FULLTEXT` para búsqueda de libros.
- Historial de reservas y préstamos.
- Auditoría de operaciones.
- Sesiones y recuperación de contraseña.

## Flujo principal

### Reserva

Usuario → búsqueda de libro → ejemplar disponible → `sp_crear_reserva` → `sp_agregar_reserva_detalle` → `sp_generar_qr`.

### Retiro

Escaneo QR → validación → `sp_confirmar_retiro_qr` → préstamo → detalle de préstamo → ejemplar prestado → reserva finalizada.

### Devolución

`sp_registrar_devolucion` → préstamo devuelto → ejemplar disponible → historial → cálculo de retraso → multa cuando corresponde.

### Renovación

`sp_renovar_prestamo` valida el máximo configurado y registra cada renovación en `renovaciones`.

## Compatibilidad Clever Cloud

La base definitiva no depende de privilegios administrativos para triggers o eventos. Las tareas de vencimiento están disponibles como procedimientos (`sp_marcar_reservas_vencidas` y `sp_marcar_prestamos_vencidos`) para ser ejecutadas por la aplicación o una tarea programada.


## Correcciones de compatibilidad y operación (17/08/2026)

- Se corrigió la selección de estudiantes en Select2: el backend ahora devuelve `id` y `text`, evitando que el resultado aparezca pero no pueda seleccionarse.
- Se aplicó `dropdownParent` a los Select2 de estudiantes, libros, autores, editoriales y materias cuando se muestran dentro de modales Bootstrap.
- Se adaptó el módulo de materias a `biblioteca_v3`: la tabla `materias` no posee `id_carrera`, por lo que la carrera fue eliminada del formulario y de las consultas.
- Se corrigió el registro de estudiantes para utilizar cédula, nombres, apellidos, correo, teléfono y carrera reales de `usuarios`.
- Se corrigió el módulo de autores y se agregó la función `frmAutor()`.
- Se corrigieron los mensajes del módulo de carreras.
- Se rediseñó el formulario de usuarios para seleccionar tipo de usuario, carrera y rol, y para guardar la contraseña con `password_hash()`.
- Se limpian los valores anteriores de autor/editorial/materia al abrir un nuevo libro.
- Se ocultó el spinner antiguo de carga de portada que quedaba visible al abrir el modal de libros.
- Se corrigieron las columnas DataTables de usuarios, estudiantes, materias y autores.

