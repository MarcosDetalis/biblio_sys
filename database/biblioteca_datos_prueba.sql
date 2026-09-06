-- ============================================================
-- DATOS DE PRUEBA - BIBLIOTECA_V3 / CLEVER CLOUD
-- ============================================================
-- Objetivo: dejar datos funcionales para probar el sistema.
-- Se cargan al menos 2 registros en cada tabla física.
-- Las vistas (vw_*) no reciben INSERT porque son consultas.
--
-- USUARIOS DE PRUEBA
--   gustavo.alumno@biblioteca.test / 123456
--   maria.profesor@biblioteca.test / 123456
--
-- El password_hash corresponde a 123456 usando bcrypt.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE `auditoria`;
TRUNCATE TABLE `autores_libros`;
TRUNCATE TABLE `cola_espera`;
TRUNCATE TABLE `ejemplar_movimiento`;
TRUNCATE TABLE `historial_prestamo`;
TRUNCATE TABLE `historial_reserva`;
TRUNCATE TABLE `libro_palabra_clave`;
TRUNCATE TABLE `multas`;
TRUNCATE TABLE `notificaciones`;
TRUNCATE TABLE `pagos_multa`;
TRUNCATE TABLE `prestamo_detalle`;
TRUNCATE TABLE `qr_reserva`;
TRUNCATE TABLE `recuperacion_password`;
TRUNCATE TABLE `renovaciones`;
TRUNCATE TABLE `reservas_cancelacion`;
TRUNCATE TABLE `reserva_detalle`;
TRUNCATE TABLE `rol_permiso`;
TRUNCATE TABLE `sesiones`;
TRUNCATE TABLE `usuario_rol`;
TRUNCATE TABLE `prestamos`;
TRUNCATE TABLE `reservas`;
TRUNCATE TABLE `ejemplares`;
TRUNCATE TABLE `libros`;
TRUNCATE TABLE `palabras_clave`;
TRUNCATE TABLE `autores`;
TRUNCATE TABLE `editoriales`;
TRUNCATE TABLE `materias`;
TRUNCATE TABLE `categorias`;
TRUNCATE TABLE `colecciones`;
TRUNCATE TABLE `restricciones`;
TRUNCATE TABLE `ubicaciones`;
TRUNCATE TABLE `sedes`;
TRUNCATE TABLE `usuarios`;
TRUNCATE TABLE `carreras`;
TRUNCATE TABLE `tipos_usuario`;
TRUNCATE TABLE `roles`;
TRUNCATE TABLE `permisos`;
TRUNCATE TABLE `parametros`;
TRUNCATE TABLE `configuracion_qr`;
TRUNCATE TABLE `dias_no_habiles`;
TRUNCATE TABLE `estados_ejemplar`;
TRUNCATE TABLE `estados_prestamo`;
TRUNCATE TABLE `estados_reserva`;
TRUNCATE TABLE `prestamos_estadisticas`;

-- ============================================================
-- 1. CATALOGOS Y SEGURIDAD
-- ============================================================

INSERT INTO `tipos_usuario` (`id_tipo_usuario`,`nombre`,`descripcion`,`activo`) VALUES
(1,'Alumno','Usuario estudiante de la institución',1),
(2,'Profesor','Usuario docente de la institución',1),
(3,'Bibliotecario','Personal encargado de la biblioteca',1),
(4,'Administrador','Administrador del sistema',1);

INSERT INTO `carreras` (`id_carrera`,`nombre`,`descripcion`,`activo`) VALUES
(1,'Ingeniería Informática','Carrera de informática y sistemas',1),
(2,'Administración de Empresas','Carrera del área empresarial',1);

INSERT INTO `roles` (`id_rol`,`nombre`,`descripcion`,`activo`) VALUES
(1,'Administrador','Administración general',1),
(2,'Bibliotecario','Gestión de biblioteca',1),
(3,'Alumno','Usuario alumno',1),
(4,'Profesor','Usuario docente',1);

INSERT INTO `permisos` (`id_permiso`,`codigo`,`nombre`,`descripcion`,`modulo`,`activo`) VALUES
(1,'USR_VIEW','Consultar usuarios',NULL,'Seguridad',1),
(2,'USR_EDIT','Administrar usuarios',NULL,'Seguridad',1),
(3,'BOOK_VIEW','Consultar libros',NULL,'Biblioteca',1),
(4,'BOOK_EDIT','Administrar libros',NULL,'Biblioteca',1),
(5,'RES_CREATE','Crear reservas',NULL,'Reservas',1),
(6,'RES_CANCEL','Cancelar reservas',NULL,'Reservas',1),
(7,'PRESTAMO','Gestionar préstamos',NULL,'Préstamos',1),
(8,'REPORTES','Ver reportes',NULL,'Reportes',1);

INSERT INTO `parametros` (`id_parametro`,`grupo_parametro`,`nombre`,`valor`,`descripcion`,`activo`) VALUES
(1,'RESERVA','MAX_RESERVAS_USUARIO','3','Máximo de reservas activas por usuario',1),
(2,'PRESTAMO','MAX_RENOVACIONES','2','Máximo de renovaciones por préstamo',1),
(3,'PRESTAMO','DIAS_PRESTAMO_ALUMNO','7','Días de préstamo para alumnos',1),
(4,'PRESTAMO','DIAS_PRESTAMO_PROFESOR','15','Días de préstamo para profesores',1),
(5,'QR','HORAS_EXPIRACION','24','Horas de vigencia del QR',1);

INSERT INTO `parametros` (`id_parametro`,`grupo_parametro`,`nombre`,`valor`,`descripcion`,`activo`) VALUES
(11,'MULTA','MONTO_POR_DIA','5000','Monto de multa por día de retraso',1);

INSERT INTO `parametros` (`id_parametro`,`grupo_parametro`,`nombre`,`valor`,`descripcion`,`activo`) VALUES
(6,'SISTEMA','nombre','Biblioteca Universitaria','Nombre institucional mostrado en reportes',1),
(7,'SISTEMA','telefono','021-400000','Teléfono institucional',1),
(8,'SISTEMA','direccion','Av. Universitaria 100','Dirección institucional',1),
(9,'SISTEMA','correo','biblioteca@universidad.edu.py','Correo institucional',1),
(10,'SISTEMA','foto','logo.png','Logo institucional',1);

INSERT INTO `restricciones` (`id_restriccion`,`nombre`,`descripcion`,`activo`) VALUES
(1,'Sin restricción','Uso normal',1),
(2,'Solo sala','No puede salir de biblioteca',1),
(3,'Solo docentes','Exclusivo para docentes',1),
(4,'Referencia','Material de consulta',1);

INSERT INTO `sedes` (`id_sede`,`nombre`,`direccion`,`telefono`,`activo`) VALUES
(1,'Sede Central','Av. Principal 100','021-400001',1),
(2,'Sede Campus Norte','Av. Universitaria 500','021-400002',1);

INSERT INTO `ubicaciones` (`id_ubicacion`,`id_sede`,`sala`,`estanteria`,`nivel`,`posicion`,`descripcion`,`activo`) VALUES
(1,1,'Sala General','E-01','N1','P01','Estantería de informática',1),
(2,2,'Sala Académica','E-02','N2','P03','Estantería de administración',1);

INSERT INTO `categorias` (`id_categoria`,`nombre`,`descripcion`,`activo`) VALUES
(1,'Informática','Programación, sistemas y tecnología',1),
(2,'Administración','Gestión, empresas y negocios',1);

INSERT INTO `materias` (`id_materia`,`nombre`,`descripcion`,`activo`) VALUES
(1,'Programación','Lenguajes y técnicas de programación',1),
(2,'Gestión Empresarial','Administración y gestión de organizaciones',1);

INSERT INTO `colecciones` (`id_coleccion`,`nombre`,`descripcion`,`activo`) VALUES
(1,'Colección Académica','Libros para apoyo académico',1),
(2,'Colección General','Material de consulta general',1);

INSERT INTO `editoriales` (`id_editorial`,`nombre`,`ciudad`,`pais`,`sitio_web`,`activo`) VALUES
(1,'Alfaomega','Ciudad de México','México','https://www.alfaomega.com.mx',1),
(2,'McGraw-Hill Education','Nueva York','Estados Unidos','https://www.mheducation.com',1);

INSERT INTO `autores` (`id_autor`,`nombres`,`apellidos`,`nacionalidad`,`fecha_nacimiento`,`biografia`,`activo`) VALUES
(1,'Robert C.','Martin','Estados Unidos','1952-12-05','Autor y especialista en ingeniería de software.',1),
(2,'Ian','Sommerville','Reino Unido','1951-02-01','Autor de textos sobre ingeniería de software.',1);

INSERT INTO `palabras_clave` (`id_palabra`,`nombre`) VALUES
(1,'programación'),
(2,'software'),
(3,'gestión'),
(4,'empresas');

INSERT INTO `configuracion_qr` (`id_configuracion_qr`,`tiempo_expiracion_horas`,`algoritmo_hash`,`longitud_hash`,`qr_ancho`,`qr_alto`) VALUES
(1,24,'SHA256',64,300,300),
(2,48,'SHA256',64,400,400);

INSERT INTO `dias_no_habiles` (`id_dia`,`fecha`,`descripcion`) VALUES
(1,'2026-08-15','Feriado nacional'),
(2,'2026-12-25','Navidad');

INSERT INTO `estados_ejemplar` (`id_estado_ejemplar`,`nombre`,`descripcion`) VALUES
(1,'Disponible','Puede reservarse'),
(2,'Reservado','Reservado por un usuario'),
(3,'Prestado','Actualmente prestado'),
(4,'Mantenimiento','Fuera de servicio'),
(5,'Extraviado','No disponible'),
(6,'Baja','Retirado del inventario');

INSERT INTO `estados_prestamo` (`id_estado_prestamo`,`nombre`,`descripcion`) VALUES
(1,'Activo','Préstamo vigente'),
(2,'Devuelto','Libro devuelto'),
(3,'Vencido','Préstamo vencido'),
(4,'Renovado','Préstamo renovado'),
(5,'Perdido','Libro perdido');

INSERT INTO `estados_reserva` (`id_estado_reserva`,`nombre`,`descripcion`) VALUES
(1,'Pendiente','Reserva creada'),
(2,'Confirmada','Reserva confirmada'),
(3,'Lista para retiro','Disponible para retirar'),
(4,'Cancelada','Reserva cancelada'),
(5,'Vencida','No retirada a tiempo'),
(6,'Finalizada','Convertida en préstamo');

-- ============================================================
-- 2. USUARIOS
-- ============================================================
-- Password para ambos: 123456
INSERT INTO `usuarios` (`id_usuario`,`id_tipo_usuario`,`id_carrera`,`cedula`,`nombres`,`apellidos`,`correo`,`telefono`,`password_hash`,`activo`) VALUES
(1,1,1,'5000001','Gustavo','Rivas','gustavo.alumno@biblioteca.test','0981000001','$2y$12$.FcfEbV1X81a0SNqZCDKnONxatjGlGg3n876w2I7gG9NEzs3g.2EG',1),
(2,2,2,'5000002','Maria','Gonzalez','maria.profesor@biblioteca.test','0981000002','$2y$12$.FcfEbV1X81a0SNqZCDKnONxatjGlGg3n876w2I7gG9NEzs3g.2EG',1),
(3,3,NULL,'5000003','Carlos','Bibliotecario','carlos.biblioteca@biblioteca.test','0981000003','$2y$12$.FcfEbV1X81a0SNqZCDKnONxatjGlGg3n876w2I7gG9NEzs3g.2EG',1),
(4,4,NULL,'5000004','Ana','Administradora','ana.admin@biblioteca.test','0981000004','$2y$12$.FcfEbV1X81a0SNqZCDKnONxatjGlGg3n876w2I7gG9NEzs3g.2EG',1);

INSERT INTO `usuario_rol` (`id_usuario_rol`,`id_usuario`,`id_rol`,`activo`) VALUES
(1,1,3,1),
(2,2,4,1),
(3,3,2,1),
(4,4,1,1);

INSERT INTO `rol_permiso` (`id_rol_permiso`,`id_rol`,`id_permiso`) VALUES
(1,1,1),(2,1,2),(3,1,3),(4,1,4),(5,1,5),(6,1,6),(7,1,7),(8,1,8),
(9,2,3),(10,2,4),(11,2,5),(12,2,6),(13,2,7),(14,2,8),
(15,3,3),(16,3,5),(17,4,3),(18,4,5),(19,4,6);

-- ============================================================
-- 3. LIBROS Y EJEMPLARES
-- ============================================================

INSERT INTO `libros` (`id_libro`,`isbn`,`codigo_catalogo`,`titulo`,`subtitulo`,`descripcion`,`anio_publicacion`,`numero_paginas`,`edicion`,`idioma`,`portada`,`id_editorial`,`id_categoria`,`id_materia`,`id_coleccion`,`id_restriccion`,`activo`) VALUES
(1,'9780132350884','LIB-0001','Clean Code','A Handbook of Agile Software Craftsmanship','Buenas prácticas para escribir código limpio y mantenible.',2008,464,'1ra','Español',NULL,2,1,1,1,1,1),
(2,'9780132350884X','LIB-0002','Ingeniería de Software','Conceptos y fundamentos','Conceptos fundamentales para el desarrollo de sistemas de software.',2015,800,'9na','Español',NULL,2,1,1,1,1,1);

INSERT INTO `autores_libros` (`id_autor_libro`,`id_libro`,`id_autor`,`tipo_autoria`) VALUES
(1,1,1,'Principal'),
(2,2,2,'Principal');

INSERT INTO `libro_palabra_clave` (`id_libro_palabra`,`id_libro`,`id_palabra`) VALUES
(1,1,1),(2,1,2),(3,2,2),(4,2,1);

INSERT INTO `ejemplares` (`id_ejemplar`,`id_libro`,`id_ubicacion`,`id_estado_ejemplar`,`codigo_ejemplar`,`codigo_barra`,`fecha_adquisicion`,`costo`,`observacion`,`activo`) VALUES
(1,1,1,3,'EJ-0001','784000000001','2026-01-10',180000.00,'Ejemplar actualmente prestado',1),
(2,2,2,1,'EJ-0002','784000000002','2026-01-12',220000.00,'Ejemplar disponible',1),
(3,1,1,2,'EJ-0003','784000000003','2026-02-10',180000.00,'Ejemplar reservado',1),
(4,2,2,1,'EJ-0004','784000000004','2026-02-12',220000.00,'Ejemplar disponible',1);

INSERT INTO `ejemplar_movimiento` (`id_movimiento`,`id_ejemplar`,`tipo_movimiento`,`referencia`,`observacion`) VALUES
(1,1,'ALTA','inventario #1','Alta inicial del ejemplar'),
(2,2,'ALTA','inventario #2','Alta inicial del ejemplar'),
(3,1,'PRESTAMO','prestamo #1','Préstamo activo'),
(4,3,'RESERVA','reserva_detalle #3','Ejemplar reservado');

-- ============================================================
-- 4. RESERVAS
-- ============================================================

INSERT INTO `reservas` (`id_reserva`,`numero_reserva`,`id_usuario`,`fecha_reserva`,`fecha_limite_retiro`,`fecha_cancelacion`,`fecha_expiracion`,`id_estado_reserva`,`observacion`,`activo`) VALUES
(1,'RES-2026-0001',1,'2026-08-10 09:00:00','2026-08-20 18:00:00',NULL,NULL,6,'Reserva convertida en préstamo',0),
(2,'RES-2026-0002',2,'2026-08-11 10:00:00','2026-08-12 18:00:00','2026-08-12 15:00:00',NULL,4,'Cancelada por el usuario',0),
(3,'RES-2026-0003',1,'2026-08-17 08:30:00','2026-08-20 18:00:00',NULL,NULL,3,'Lista para retiro',1),
(4,'RES-2026-0004',2,'2026-08-05 11:00:00','2026-08-06 18:00:00','2026-08-06 17:00:00',NULL,4,'Cancelada por falta de disponibilidad',0);

INSERT INTO `reserva_detalle` (`id_reserva_detalle`,`id_reserva`,`id_ejemplar`,`estado`,`observacion`) VALUES
(1,1,1,'ENTREGADO','Ejemplar entregado y convertido en préstamo'),
(2,2,4,'CANCELADO','Reserva cancelada'),
(3,3,3,'RESERVADO','Esperando retiro'),
(4,4,2,'CANCELADO','Reserva cancelada');

INSERT INTO `reservas_cancelacion` (`id_cancelacion`,`id_reserva`,`id_usuario`,`motivo`) VALUES
(1,2,2,'El usuario canceló la reserva'),
(2,4,2,'No fue posible retirar el ejemplar');

INSERT INTO `historial_reserva` (`id_historial`,`id_reserva`,`id_estado_reserva`,`id_usuario`,`comentario`,`fecha_evento`) VALUES
(1,1,1,1,'Reserva creada','2026-08-10 09:00:00'),
(2,1,6,3,'Reserva convertida en préstamo','2026-08-10 11:00:00'),
(3,3,1,1,'Reserva creada','2026-08-17 08:30:00'),
(4,3,3,3,'Ejemplar listo para retiro','2026-08-17 09:00:00');

INSERT INTO `cola_espera` (`id_cola`,`id_libro`,`id_usuario`,`posicion`,`fecha_registro`,`notificado`,`activo`) VALUES
(1,1,2,1,'2026-08-16 10:00:00',0,1),
(2,2,1,2,'2026-08-16 11:00:00',1,1);

-- ============================================================
-- 5. PRESTAMOS
-- ============================================================

INSERT INTO `prestamos` (`id_prestamo`,`numero_prestamo`,`id_reserva`,`id_usuario`,`id_estado_prestamo`,`fecha_prestamo`,`fecha_vencimiento`,`fecha_devolucion`,`cantidad_renovaciones`,`observacion`,`activo`) VALUES
(1,'PRE-2026-0001',1,1,1,'2026-08-10 11:00:00','2026-08-17',NULL,1,'Préstamo activo de prueba',1),
(2,'PRE-2026-0002',NULL,2,2,'2026-08-01 09:30:00','2026-08-08','2026-08-07',0,'Préstamo devuelto',0);

INSERT INTO `prestamo_detalle` (`id_prestamo_detalle`,`id_prestamo`,`id_ejemplar`,`estado`,`observacion`) VALUES
(1,1,1,'PRESTADO','Ejemplar en poder del usuario'),
(2,2,2,'DEVUELTO','Ejemplar devuelto correctamente');

INSERT INTO `historial_prestamo` (`id_historial`,`id_prestamo`,`id_estado_prestamo`,`id_usuario`,`comentario`,`fecha_evento`) VALUES
(1,1,1,3,'Préstamo creado','2026-08-10 11:00:00'),
(2,1,4,3,'Préstamo renovado','2026-08-14 10:00:00'),
(3,2,1,3,'Préstamo creado','2026-08-01 09:30:00'),
(4,2,2,3,'Libro devuelto','2026-08-07 16:00:00');

INSERT INTO `renovaciones` (`id_renovacion`,`id_prestamo`,`fecha_renovacion`,`fecha_vencimiento_anterior`,`nueva_fecha_vencimiento`,`id_usuario`,`observacion`) VALUES
(1,1,'2026-08-14 10:00:00','2026-08-15','2026-08-17',3,'Primera renovación'),
(2,1,'2026-08-16 10:00:00','2026-08-17','2026-08-19',3,'Segunda renovación de prueba');

-- ============================================================
-- 6. MULTAS Y PAGOS
-- ============================================================

INSERT INTO `multas` (`id_multa`,`id_prestamo`,`dias_retraso`,`monto`,`motivo`,`pagada`,`fecha_registro`,`fecha_pago`) VALUES
(1,2,2,10000.00,'Devolución fuera de plazo',1,'2026-08-07 16:00:00','2026-08-07 16:30:00'),
(2,1,1,5000.00,'Retraso de devolución',0,'2026-08-17 09:00:00',NULL);

INSERT INTO `pagos_multa` (`id_pago`,`id_multa`,`monto_pagado`,`forma_pago`,`comprobante`,`fecha_pago`) VALUES
(1,1,5000.00,'EFECTIVO','REC-0001','2026-08-07 16:30:00'),
(2,1,5000.00,'TRANSFERENCIA','TRF-0002','2026-08-07 17:00:00');

-- ============================================================
-- 7. QR, SESIONES Y RECUPERACION
-- ============================================================

INSERT INTO `qr_reserva` (`id_qr`,`id_reserva`,`codigo_qr`,`hash_qr`,`fecha_generacion`,`fecha_expiracion`,`utilizado`,`fecha_utilizacion`,`fecha_escaneo`) VALUES
(1,3,'QR-RES-0003','hash-prueba-qr-000000000000000000000000000000000001','2026-08-17 09:00:00','2026-08-18 09:00:00',0,NULL,NULL),
(2,1,'QR-RES-0001','hash-prueba-qr-000000000000000000000000000000000002','2026-08-10 10:30:00','2026-08-11 10:30:00',1,'2026-08-10 11:00:00','2026-08-10');

INSERT INTO `sesiones` (`id_sesion`,`id_usuario`,`token_hash`,`refresh_token_hash`,`ip`,`navegador`,`sistema_operativo`,`fecha_inicio`,`fecha_expiracion`,`fecha_cierre`,`activa`) VALUES
(1,1,'session-token-0001','refresh-token-0001','127.0.0.1','Chrome','Windows','2026-08-17 08:00:00','2026-08-17 20:00:00',NULL,1),
(2,2,'session-token-0002','refresh-token-0002','127.0.0.2','Edge','Windows','2026-08-16 08:00:00','2026-08-16 20:00:00','2026-08-16 18:00:00',0);

INSERT INTO `recuperacion_password` (`id_recuperacion`,`id_usuario`,`token_hash`,`fecha_expiracion`,`utilizado`) VALUES
(1,1,'recovery-token-0001','2026-08-20 10:00:00',0),
(2,2,'recovery-token-0002','2026-08-10 10:00:00',1);

-- ============================================================
-- 8. NOTIFICACIONES Y AUDITORIA
-- ============================================================

INSERT INTO `notificaciones` (`id_notificacion`,`id_usuario`,`titulo`,`mensaje`,`leido`) VALUES
(1,1,'Reserva lista','Tu reserva RES-2026-0003 está lista para retiro.',0),
(2,2,'Devolución registrada','Tu devolución PRE-2026-0002 fue registrada correctamente.',1);

INSERT INTO `auditoria` (`id_auditoria`,`id_usuario`,`tabla_afectada`,`accion`,`registro_id`,`valor_anterior`,`valor_nuevo`,`ip`,`navegador`) VALUES
(1,3,'prestamos','INSERT',1,NULL,'{"numero_prestamo":"PRE-2026-0001","usuario":1}','127.0.0.1','Chrome'),
(2,3,'reservas','UPDATE',3,'{"estado":2}','{"estado":3}','127.0.0.1','Chrome');

-- ============================================================
-- 9. ESTADISTICAS
-- ============================================================

INSERT INTO `prestamos_estadisticas` (`id_estadistica`,`anio`,`mes`,`total_prestamos`,`total_devoluciones`,`total_multas`) VALUES
(1,2026,7,12,10,35000.00),
(2,2026,8,8,5,15000.00);

-- ============================================================
-- COMPROBACION RAPIDA
-- ============================================================
-- Las siguientes consultas permiten verificar que las tablas principales
-- quedaron pobladas.
--
-- SELECT COUNT(*) FROM usuarios;
-- SELECT COUNT(*) FROM libros;
-- SELECT COUNT(*) FROM ejemplares;
-- SELECT COUNT(*) FROM reservas;
-- SELECT COUNT(*) FROM prestamos;
-- SELECT COUNT(*) FROM multas;
-- SELECT * FROM vw_catalogo;
-- SELECT * FROM vw_prestamos_activos;
-- SELECT * FROM vw_reservas_activas;

SET FOREIGN_KEY_CHECKS = 1;
