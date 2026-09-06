-- ============================================================
-- CONSULTAS UTILES - BIBLIOTECA V3
-- ============================================================

-- 1. Usuarios y roles
SELECT u.id_usuario,
       CONCAT(u.nombres,' ',u.apellidos) AS nombre,
       u.correo,
       GROUP_CONCAT(DISTINCT r.nombre SEPARATOR ', ') AS roles,
       CASE WHEN u.activo=1 THEN 'Activo' ELSE 'Inactivo' END AS estado
FROM usuarios u
LEFT JOIN usuario_rol ur ON ur.id_usuario=u.id_usuario AND ur.activo=1
LEFT JOIN roles r ON r.id_rol=ur.id_rol
GROUP BY u.id_usuario
ORDER BY u.apellidos,u.nombres;

-- 2. Catálogo
SELECT * FROM vw_catalogo ORDER BY titulo;

-- 3. Libros y ejemplares disponibles
SELECT * FROM vw_libros_disponibles ORDER BY titulo,codigo_ejemplar;

-- 4. Reservas activas
SELECT * FROM vw_reservas_activas ORDER BY fecha_reserva DESC;

-- 5. Préstamos activos
SELECT * FROM vw_prestamos_activos ORDER BY fecha_vencimiento;

-- 6. Multas pendientes
SELECT * FROM vw_multas_pendientes ORDER BY monto DESC;

-- 7. Inventario por estado
SELECT ee.nombre AS estado, COUNT(*) AS cantidad
FROM ejemplares e
JOIN estados_ejemplar ee ON ee.id_estado_ejemplar=e.id_estado_ejemplar
WHERE e.activo=1
GROUP BY ee.id_estado_ejemplar,ee.nombre
ORDER BY ee.id_estado_ejemplar;

-- 8. Libros más prestados
SELECT * FROM vw_libros_mas_prestados LIMIT 20;

-- 9. Historial de un usuario
CALL sp_historial_usuario(1);

-- 10. Búsqueda de libros
CALL sp_buscar_libros('software');

-- 11. Resumen para dashboard
SELECT
    (SELECT COUNT(*) FROM usuarios WHERE activo=1) AS usuarios_activos,
    (SELECT COUNT(*) FROM libros WHERE activo=1) AS libros_activos,
    (SELECT COUNT(*) FROM ejemplares WHERE activo=1) AS ejemplares,
    (SELECT COUNT(*) FROM ejemplares WHERE activo=1 AND id_estado_ejemplar=1) AS disponibles,
    (SELECT COUNT(*) FROM reservas WHERE activo=1) AS reservas_activas,
    (SELECT COUNT(*) FROM prestamos WHERE activo=1 AND fecha_devolucion IS NULL) AS prestamos_activos,
    (SELECT COUNT(*) FROM multas WHERE pagada=0) AS multas_pendientes;

-- 12. Vencimientos: ejecutar desde la aplicación/tarea programada
CALL sp_marcar_reservas_vencidas();
CALL sp_marcar_prestamos_vencidos();
