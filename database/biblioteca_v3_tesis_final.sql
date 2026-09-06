-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 21-06-2026 a las 03:57:23
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ============================================================
-- VERSION COMPATIBLE CON CLEVER CLOUD (MYSQL/PERCONA)
-- ============================================================
-- Se eliminan triggers y eventos para evitar privilegios administrativos
-- no disponibles en el plan DEV de Clever Cloud. La logica de los triggers
-- se incorporo a los procedimientos de negocio y la logica de vencimientos
-- queda disponible mediante procedimientos de mantenimiento.
--
-- -- CHANGELOG DE CORRECCIONES Y OPTIMIZACIONES SOBRE biblioteca_v2.sql
-- ============================================================
-- BUGS FUNCIONALES CORREGIDOS
--   1. sp_cancelar_reserva: ahora si actualiza id_estado_reserva a
--      'Cancelada' (antes solo marcaba activo=FALSE y el historial
--      de la reserva nunca se registraba).
--   2. sp_confirmar_prestamo: cierra el ciclo marcando la reserva
--      origen como 'Finalizada' y devuelve el id del prestamo creado.
--   3. sp_renovar_prestamo: valida el limite MAX_RENOVACIONES de
--      `parametros` (antes no existia tope) y ahora si registra
--      cada renovacion en la tabla `renovaciones` (antes quedaba
--      vacia). Cambio de firma: se agrego el parametro p_usuario.
--   4. Nuevo procedimiento sp_agregar_prestamo_detalle: no existia
--      forma de asociar ejemplares a un prestamo (si existia el
--      equivalente para reservas), por lo que el flujo reserva ->
--      prestamo no se podia completar.
--   4b. sp_cancelar_reserva, sp_confirmar_prestamo y sp_renovar_prestamo
--      ahora corren dentro de una transaccion explicita (START
--      TRANSACTION/COMMIT). Antes, con autocommit, cada sentencia se
--      confirmaba por separado: un fallo a mitad del procedimiento podia
--      dejar datos a medio escribir, y el SELECT...FOR UPDATE de
--      sp_renovar_prestamo no protegia nada porque su bloqueo se
--      liberaba apenas terminaba esa sentencia.
--
-- ESTRUCTURA
--   6. Tabla `carreras` (huerfana, sin relaciones) conectada via
--      `usuarios.id_carrera` (FK fk_usuario_carrera, NULL permitido).
--   7. `sesiones.token`/`refresh_token` y `recuperacion_password.token`
--      renombrados a *_hash para dejar explicito que deben guardarse
--      hasheados, igual que `usuarios.password_hash`; se les agrego
--      UNIQUE KEY (antes no tenian indice ni eran unicos).
--   8. enum de `ejemplar_movimiento.tipo_movimiento` ampliado con
--      'CANCELACION'.
--   9. Se quito el DEFINER=root@localhost hardcodeado de procedimientos
--      y vistas (las vistas pasan a SQL SECURITY INVOKER) para que el
--      dump sea portable a otros servidores/usuarios.
--
-- OPTIMIZACION DE INDICES
--   10. Eliminados indices redundantes que duplicaban una UNIQUE KEY
--       existente en: libros (isbn, codigo_catalogo), ejemplares
--       (codigo_barra, codigo_ejemplar), usuarios (cedula, correo),
--       prestamos (numero_prestamo), reservas (numero_reserva).
--   11. Agregado FULLTEXT idx_libro_fulltext (titulo, subtitulo,
--       descripcion) y reescrito sp_buscar_libros para usar MATCH...
--       AGAINST en vez de LIKE '%texto%' (que no podia usar indice).
--       OJO: isbn/codigo_catalogo pasan a buscarse con LIKE 'texto%'
--       (prefijo) en vez de '%texto%' (substring) para poder usar su
--       indice; ya no encuentra coincidencias a mitad del codigo.
--   12. Indices nuevos para consultas operativas frecuentes:
--       auditoria(tabla_afectada, registro_id), prestamos(activo,
--       fecha_vencimiento) para detectar vencidos, multas(pagada),
--       notificaciones(id_usuario, leido).
--
-- REGLAS DE NEGOCIO AUTOMATIZADAS (antes solo definidas en catalogos,
-- nunca asignadas por ningun objeto)
--   13. CHECK constraints: montos/costos >= 0, fecha_devolucion no
--       puede ser anterior a fecha_prestamo.
--
-- IMPACTO EN LA APLICACION (cambios de firma/columnas a actualizar
-- en el codigo que consume esta BD):
--   - sp_renovar_prestamo ahora recibe un 3er parametro p_usuario.
--   - sp_confirmar_prestamo ahora retorna un SELECT con id_prestamo.
--   - sesiones.token -> token_hash, sesiones.refresh_token ->
--     refresh_token_hash, recuperacion_password.token -> token_hash.
-- ============================================================

--
-- Base de datos: biblioteca_v3
-- IMPORTANTE: en Clever Cloud no ejecutar CREATE DATABASE ni USE.
-- Seleccione la base de datos del add-on antes de importar este archivo.
--

DELIMITER $$
--
-- Procedimientos
--
CREATE PROCEDURE `sp_agregar_prestamo_detalle` (IN `p_id_prestamo` BIGINT, IN `p_id_ejemplar` BIGINT)
BEGIN
    INSERT INTO prestamo_detalle(id_prestamo,id_ejemplar)
    VALUES(p_id_prestamo,p_id_ejemplar);

    -- Reemplaza al trigger trg_prestamo_ai.
    UPDATE ejemplares
       SET id_estado_ejemplar=3
     WHERE id_ejemplar=p_id_ejemplar;

    INSERT INTO ejemplar_movimiento(id_ejemplar,tipo_movimiento,referencia)
    VALUES(
        p_id_ejemplar,
        'PRESTAMO',
        CONCAT('prestamo #',p_id_prestamo)
    );
END$$


CREATE PROCEDURE `sp_agregar_reserva_detalle` (IN `p_id_reserva` BIGINT, IN `p_id_ejemplar` BIGINT)
BEGIN
    INSERT INTO reserva_detalle(id_reserva,id_ejemplar)
    VALUES(p_id_reserva,p_id_ejemplar);

    -- Reemplaza al trigger trg_reserva_detalle_ai.
    UPDATE ejemplares
       SET id_estado_ejemplar=2
     WHERE id_ejemplar=p_id_ejemplar;

    INSERT INTO ejemplar_movimiento(id_ejemplar,tipo_movimiento,referencia)
    VALUES(
        p_id_ejemplar,
        'RESERVA',
        CONCAT('reserva_detalle #',LAST_INSERT_ID())
    );
END$$


CREATE PROCEDURE `sp_buscar_libros` (IN `p_texto` VARCHAR(200))   BEGIN
    -- OPTIMIZACION: la busqueda por titulo ahora usa el indice FULLTEXT
    -- idx_libro_fulltext en vez de LIKE '%texto%', que no puede usar ningun
    -- indice B-tree y obliga a un escaneo completo de `libros`. isbn y
    -- codigo_catalogo se buscan con LIKE de prefijo ('texto%') para poder
    -- aprovechar sus indices normales (ya no se encuentran coincidencias en
    -- medio del codigo, solo al inicio, que es el uso real en estos campos).
    SELECT l.id_libro,l.codigo_catalogo,l.isbn,l.titulo,
           c.nombre categoria,m.nombre materia
    FROM libros l
    LEFT JOIN categorias c ON l.id_categoria=c.id_categoria
    LEFT JOIN materias m ON l.id_materia=m.id_materia
    WHERE l.activo=TRUE
      AND (MATCH(l.titulo,l.subtitulo,l.descripcion) AGAINST (p_texto IN NATURAL LANGUAGE MODE)
        OR l.isbn LIKE CONCAT(p_texto,'%')
        OR l.codigo_catalogo LIKE CONCAT(p_texto,'%'));
END$$

CREATE PROCEDURE `sp_cancelar_reserva` (IN `p_id_reserva` BIGINT, IN `p_usuario` INT, IN `p_motivo` VARCHAR(255))
BEGIN
    START TRANSACTION;

    UPDATE reservas
       SET activo=FALSE,
           id_estado_reserva=4,
           fecha_cancelacion=NOW()
     WHERE id_reserva=p_id_reserva;

    -- Reemplaza al trigger trg_reserva_estado_au.
    INSERT INTO historial_reserva(
        id_reserva,
        id_estado_reserva,
        fecha_evento
    )
    VALUES(
        p_id_reserva,
        4,
        NOW()
    );

    INSERT INTO reservas_cancelacion(
        id_reserva,
        id_usuario,
        motivo
    )
    VALUES(
        p_id_reserva,
        p_usuario,
        p_motivo
    );

    -- Reemplaza al trigger trg_cancelacion_reserva_ai.
    UPDATE ejemplares e
    JOIN reserva_detalle rd
      ON rd.id_ejemplar=e.id_ejemplar
     AND rd.id_reserva=p_id_reserva
       SET e.id_estado_ejemplar=1
     WHERE rd.estado='RESERVADO';

    UPDATE reserva_detalle
       SET estado='CANCELADO'
     WHERE id_reserva=p_id_reserva
       AND estado='RESERVADO';

    INSERT INTO ejemplar_movimiento(
        id_ejemplar,
        tipo_movimiento,
        referencia,
        observacion
    )
    SELECT
        rd.id_ejemplar,
        'CANCELACION',
        CONCAT('reserva #',p_id_reserva),
        p_motivo
      FROM reserva_detalle rd
     WHERE rd.id_reserva=p_id_reserva;

    COMMIT;
END$$


CREATE PROCEDURE `sp_confirmar_prestamo` (IN `p_numero` VARCHAR(30), IN `p_id_reserva` BIGINT, IN `p_usuario` INT, IN `p_estado` INT, IN `p_venc` DATE)
BEGIN
    DECLARE v_id_prestamo BIGINT UNSIGNED;

    START TRANSACTION;

    INSERT INTO prestamos(
        numero_prestamo,
        id_reserva,
        id_usuario,
        id_estado_prestamo,
        fecha_prestamo,
        fecha_vencimiento,
        activo)
    VALUES(
        p_numero,
        p_id_reserva,
        p_usuario,
        p_estado,
        NOW(),
        p_venc,
        TRUE);

    SET v_id_prestamo = LAST_INSERT_ID();

    IF p_id_reserva IS NOT NULL THEN
        UPDATE reservas
           SET id_estado_reserva = 6,
               activo = FALSE
         WHERE id_reserva = p_id_reserva;

        -- Reemplaza al trigger trg_reserva_estado_au.
        INSERT INTO historial_reserva(
            id_reserva,
            id_estado_reserva,
            fecha_evento
        )
        VALUES(
            p_id_reserva,
            6,
            NOW()
        );
    END IF;

    COMMIT;

    SELECT v_id_prestamo AS id_prestamo;
END$$


CREATE PROCEDURE `sp_crear_reserva` (IN `p_numero` VARCHAR(30), IN `p_usuario` INT UNSIGNED, IN `p_estado` INT UNSIGNED, IN `p_fecha_limite` DATETIME, OUT `p_id_reserva` BIGINT)   BEGIN
    INSERT INTO reservas(
        numero_reserva,
        id_usuario,
        id_estado_reserva,
        fecha_limite_retiro,
        fecha_reserva,
        activo
    )
    VALUES(
        p_numero,
        p_usuario,
        p_estado,
        p_fecha_limite,
        NOW(),
        TRUE
    );

    SET p_id_reserva = LAST_INSERT_ID();
END$$

CREATE PROCEDURE `sp_generar_qr` (IN `p_id_reserva` BIGINT, IN `p_codigo` TEXT, IN `p_hash` VARCHAR(128), IN `p_expira` DATETIME)   BEGIN
    INSERT INTO qr_reserva(
        id_reserva,
        codigo_qr,
        hash_qr,
        fecha_generacion,
        fecha_expiracion)
    VALUES(
        p_id_reserva,
        p_codigo,
        p_hash,
        NOW(),
        p_expira);
END$$

CREATE PROCEDURE `sp_historial_usuario` (IN `p_id_usuario` INT UNSIGNED)   BEGIN
    SELECT *
    FROM vw_historial_usuario
    WHERE id_usuario=p_id_usuario;
END$$

CREATE PROCEDURE `sp_libros_disponibles` ()   BEGIN
    SELECT * FROM vw_libros_disponibles;
END$$

CREATE PROCEDURE `sp_registrar_devolucion` (IN `p_id_prestamo` BIGINT)
BEGIN
    DECLARE v_vencimiento DATE;
    DECLARE v_dias_retraso INT DEFAULT 0;
    DECLARE v_multa_dia DECIMAL(10,2) DEFAULT 5000.00;
    DECLARE v_monto DECIMAL(10,2) DEFAULT 0.00;
    DECLARE v_usuario INT;
    START TRANSACTION;
    SELECT fecha_vencimiento,id_usuario INTO v_vencimiento,v_usuario FROM prestamos WHERE id_prestamo=p_id_prestamo AND fecha_devolucion IS NULL FOR UPDATE;
    IF v_usuario IS NULL THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='El préstamo no existe o ya fue devuelto';
    END IF;
    SET v_dias_retraso=GREATEST(DATEDIFF(CURDATE(),v_vencimiento),0);
    SELECT CAST(valor AS DECIMAL(10,2)) INTO v_multa_dia FROM parametros WHERE grupo_parametro='MULTA' AND nombre='MONTO_POR_DIA' AND activo=1 LIMIT 1;
    SET v_monto=v_dias_retraso*v_multa_dia;
    UPDATE prestamos SET fecha_devolucion=CURDATE(),id_estado_prestamo=2,activo=FALSE WHERE id_prestamo=p_id_prestamo AND fecha_devolucion IS NULL;
    UPDATE ejemplares e JOIN prestamo_detalle pd ON pd.id_ejemplar=e.id_ejemplar AND pd.id_prestamo=p_id_prestamo SET e.id_estado_ejemplar=1 WHERE pd.estado='PRESTADO';
    UPDATE prestamo_detalle SET estado='DEVUELTO' WHERE id_prestamo=p_id_prestamo AND estado='PRESTADO';
    INSERT INTO ejemplar_movimiento(id_ejemplar,tipo_movimiento,referencia,observacion) SELECT pd.id_ejemplar,'DEVOLUCION',CONCAT('prestamo #',p_id_prestamo),'Devolución registrada' FROM prestamo_detalle pd WHERE pd.id_prestamo=p_id_prestamo;
    INSERT INTO historial_prestamo(id_prestamo,id_estado_prestamo,id_usuario,comentario,fecha_evento) VALUES(p_id_prestamo,2,v_usuario,'Devolución registrada',NOW());
    IF v_dias_retraso > 0 AND v_monto > 0 THEN
        INSERT INTO multas(id_prestamo,dias_retraso,monto,motivo,pagada,fecha_registro) VALUES(p_id_prestamo,v_dias_retraso,v_monto,'Devolución fuera de plazo',0,NOW());
    END IF;
    COMMIT;
END$$


CREATE PROCEDURE `sp_renovar_prestamo` (IN `p_id_prestamo` BIGINT, IN `p_nueva_fecha` DATE, IN `p_usuario` INT)   BEGIN
    DECLARE v_fecha_anterior DATE;
    DECLARE v_renovaciones TINYINT UNSIGNED;
    DECLARE v_max_renovaciones INT DEFAULT 2;

    -- CORRECCION: el FOR UPDATE necesita una transaccion explicita para que
    -- el bloqueo tenga efecto; con autocommit (el modo por defecto), un
    -- SELECT ... FOR UPDATE aislado libera el lock apenas termina esa misma
    -- sentencia y no protege nada frente a renovaciones concurrentes.
    START TRANSACTION;

    SELECT fecha_vencimiento, cantidad_renovaciones
      INTO v_fecha_anterior, v_renovaciones
      FROM prestamos
     WHERE id_prestamo = p_id_prestamo
     FOR UPDATE;

    SELECT CAST(valor AS UNSIGNED) INTO v_max_renovaciones
      FROM parametros
     WHERE grupo_parametro='PRESTAMO' AND nombre='MAX_RENOVACIONES' AND activo=1
     LIMIT 1;

    -- CORRECCION: antes no se validaba el limite definido en parametros
    -- (MAX_RENOVACIONES), por lo que un prestamo podia renovarse sin tope.
    IF v_renovaciones >= v_max_renovaciones THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Se alcanzo el numero maximo de renovaciones permitidas';
    END IF;

    UPDATE prestamos
       SET fecha_vencimiento=p_nueva_fecha,
           cantidad_renovaciones=cantidad_renovaciones+1
     WHERE id_prestamo=p_id_prestamo;

    -- CORRECCION: la tabla `renovaciones` existia en el esquema pero ningun
    -- objeto la alimentaba; quedaba vacia aunque hubiera renovaciones reales.
    INSERT INTO renovaciones(
        id_prestamo,
        fecha_vencimiento_anterior,
        nueva_fecha_vencimiento,
        id_usuario)
    VALUES(
        p_id_prestamo,
        v_fecha_anterior,
        p_nueva_fecha,
        p_usuario);

    COMMIT;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria`
--

CREATE TABLE `auditoria` (
  `id_auditoria` bigint(20) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED DEFAULT NULL,
  `tabla_afectada` varchar(80) NOT NULL,
  `accion` enum('INSERT','UPDATE','DELETE','LOGIN','LOGOUT','OTRO') NOT NULL,
  `registro_id` bigint(20) DEFAULT NULL,
  `valor_anterior` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`valor_anterior`)),
  `valor_nuevo` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`valor_nuevo`)),
  `ip` varchar(45) DEFAULT NULL,
  `navegador` varchar(255) DEFAULT NULL,
  `fecha_evento` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Auditoría de operaciones';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `autores`
--

CREATE TABLE `autores` (
  `id_autor` int(10) UNSIGNED NOT NULL,
  `nombres` varchar(120) NOT NULL,
  `apellidos` varchar(120) NOT NULL,
  `nacionalidad` varchar(80) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `biografia` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `autores_libros`
--

CREATE TABLE `autores_libros` (
  `id_autor_libro` bigint(20) UNSIGNED NOT NULL,
  `id_libro` bigint(20) UNSIGNED NOT NULL,
  `id_autor` int(10) UNSIGNED NOT NULL,
  `tipo_autoria` enum('Principal','Coautor','Compilador','Traductor') DEFAULT 'Principal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carreras`
--

CREATE TABLE `carreras` (
  `id_carrera` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id_categoria` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id_categoria`, `nombre`, `descripcion`, `activo`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 'Informática', NULL, 1, '2026-06-20 22:51:02', '2026-06-20 22:51:02'),
(2, 'Ingeniería', NULL, 1, '2026-06-20 22:51:02', '2026-06-20 22:51:02'),
(3, 'Matemática', NULL, 1, '2026-06-20 22:51:02', '2026-06-20 22:51:02'),
(4, 'Administración', NULL, 1, '2026-06-20 22:51:02', '2026-06-20 22:51:02'),
(5, 'Derecho', NULL, 1, '2026-06-20 22:51:02', '2026-06-20 22:51:02'),
(6, 'Educación', NULL, 1, '2026-06-20 22:51:02', '2026-06-20 22:51:02'),
(7, 'Literatura', NULL, 1, '2026-06-20 22:51:02', '2026-06-20 22:51:02');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cola_espera`
--

CREATE TABLE `cola_espera` (
  `id_cola` bigint(20) UNSIGNED NOT NULL,
  `id_libro` bigint(20) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `posicion` int(11) NOT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `notificado` tinyint(1) DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cola de espera por libro';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `colecciones`
--

CREATE TABLE `colecciones` (
  `id_coleccion` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion_qr`
--

CREATE TABLE `configuracion_qr` (
  `id_configuracion_qr` int(10) UNSIGNED NOT NULL,
  `tiempo_expiracion_horas` int(11) DEFAULT 24,
  `algoritmo_hash` varchar(30) DEFAULT 'SHA256',
  `longitud_hash` smallint(6) DEFAULT 64,
  `qr_ancho` smallint(6) DEFAULT 300,
  `qr_alto` smallint(6) DEFAULT 300
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `configuracion_qr`
--

INSERT INTO `configuracion_qr` (`id_configuracion_qr`, `tiempo_expiracion_horas`, `algoritmo_hash`, `longitud_hash`, `qr_ancho`, `qr_alto`) VALUES
(1, 24, 'SHA256', 64, 300, 300);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dias_no_habiles`
--

CREATE TABLE `dias_no_habiles` (
  `id_dia` int(10) UNSIGNED NOT NULL,
  `fecha` date NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `editoriales`
--

CREATE TABLE `editoriales` (
  `id_editorial` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `pais` varchar(100) DEFAULT NULL,
  `sitio_web` varchar(200) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ejemplares`
--

CREATE TABLE `ejemplares` (
  `id_ejemplar` bigint(20) UNSIGNED NOT NULL,
  `id_libro` bigint(20) UNSIGNED NOT NULL,
  `id_ubicacion` int(10) UNSIGNED DEFAULT NULL,
  `id_estado_ejemplar` int(10) UNSIGNED DEFAULT NULL,
  `codigo_ejemplar` varchar(40) DEFAULT NULL,
  `codigo_barra` varchar(60) DEFAULT NULL,
  `fecha_adquisicion` date DEFAULT NULL,
  `costo` decimal(10,2) DEFAULT NULL,
  `observacion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ejemplar_movimiento`
--

CREATE TABLE `ejemplar_movimiento` (
  `id_movimiento` bigint(20) UNSIGNED NOT NULL,
  `id_ejemplar` bigint(20) UNSIGNED NOT NULL,
  `tipo_movimiento` enum('ALTA','RESERVA','CANCELACION','PRESTAMO','RENOVACION','DEVOLUCION','MANTENIMIENTO','BAJA') NOT NULL,
  `referencia` varchar(120) DEFAULT NULL,
  `observacion` varchar(255) DEFAULT NULL,
  `fecha_movimiento` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estados_ejemplar`
--

CREATE TABLE `estados_ejemplar` (
  `id_estado_ejemplar` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `estados_ejemplar`
--

INSERT INTO `estados_ejemplar` (`id_estado_ejemplar`, `nombre`, `descripcion`) VALUES
(1, 'Disponible', 'Puede reservarse'),
(2, 'Reservado', 'Reservado por un usuario'),
(3, 'Prestado', 'Actualmente prestado'),
(4, 'Mantenimiento', 'Fuera de servicio'),
(5, 'Extraviado', 'No disponible'),
(6, 'Baja', 'Retirado del inventario');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estados_prestamo`
--

CREATE TABLE `estados_prestamo` (
  `id_estado_prestamo` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `estados_prestamo`
--

INSERT INTO `estados_prestamo` (`id_estado_prestamo`, `nombre`, `descripcion`) VALUES
(1, 'Activo', 'Préstamo vigente'),
(2, 'Devuelto', 'Libro devuelto'),
(3, 'Vencido', 'Préstamo vencido'),
(4, 'Renovado', 'Préstamo renovado'),
(5, 'Perdido', 'Libro perdido');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estados_reserva`
--

CREATE TABLE `estados_reserva` (
  `id_estado_reserva` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `estados_reserva`
--

INSERT INTO `estados_reserva` (`id_estado_reserva`, `nombre`, `descripcion`) VALUES
(1, 'Pendiente', 'Reserva creada'),
(2, 'Confirmada', 'Reserva confirmada'),
(3, 'Lista para retiro', 'Disponible para retirar'),
(4, 'Cancelada', 'Reserva cancelada'),
(5, 'Vencida', 'No retirada a tiempo'),
(6, 'Finalizada', 'Convertida en préstamo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_prestamo`
--

CREATE TABLE `historial_prestamo` (
  `id_historial` bigint(20) UNSIGNED NOT NULL,
  `id_prestamo` bigint(20) UNSIGNED NOT NULL,
  `id_estado_prestamo` int(10) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED DEFAULT NULL,
  `comentario` varchar(255) DEFAULT NULL,
  `fecha_evento` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historial del prestamo';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_reserva`
--

CREATE TABLE `historial_reserva` (
  `id_historial` bigint(20) UNSIGNED NOT NULL,
  `id_reserva` bigint(20) UNSIGNED NOT NULL,
  `id_estado_reserva` int(10) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED DEFAULT NULL,
  `comentario` varchar(255) DEFAULT NULL,
  `fecha_evento` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historial de cambios de la reserva';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `libros`
--

CREATE TABLE `libros` (
  `id_libro` bigint(20) UNSIGNED NOT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `codigo_catalogo` varchar(40) DEFAULT NULL,
  `titulo` varchar(255) NOT NULL,
  `subtitulo` varchar(255) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `anio_publicacion` year(4) DEFAULT NULL,
  `numero_paginas` int(11) DEFAULT NULL,
  `edicion` varchar(40) DEFAULT NULL,
  `idioma` varchar(40) DEFAULT NULL,
  `portada` varchar(255) DEFAULT NULL,
  `id_editorial` int(10) UNSIGNED DEFAULT NULL,
  `id_categoria` int(10) UNSIGNED DEFAULT NULL,
  `id_materia` int(10) UNSIGNED DEFAULT NULL,
  `id_coleccion` int(10) UNSIGNED DEFAULT NULL,
  `id_restriccion` int(10) UNSIGNED DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `libro_palabra_clave`
--

CREATE TABLE `libro_palabra_clave` (
  `id_libro_palabra` bigint(20) UNSIGNED NOT NULL,
  `id_libro` bigint(20) UNSIGNED NOT NULL,
  `id_palabra` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materias`
--

CREATE TABLE `materias` (
  `id_materia` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `materias`
--

INSERT INTO `materias` (`id_materia`, `nombre`, `descripcion`, `activo`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 'Programación', NULL, 1, '2026-06-20 22:51:02', '2026-06-20 22:51:02'),
(2, 'Base de Datos', NULL, 1, '2026-06-20 22:51:02', '2026-06-20 22:51:02'),
(3, 'Redes', NULL, 1, '2026-06-20 22:51:02', '2026-06-20 22:51:02'),
(4, 'Ingeniería de Software', NULL, 1, '2026-06-20 22:51:02', '2026-06-20 22:51:02'),
(5, 'Inteligencia Artificial', NULL, 1, '2026-06-20 22:51:02', '2026-06-20 22:51:02'),
(6, 'Investigación', NULL, 1, '2026-06-20 22:51:02', '2026-06-20 22:51:02');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `multas`
--

CREATE TABLE `multas` (
  `id_multa` bigint(20) UNSIGNED NOT NULL,
  `id_prestamo` bigint(20) UNSIGNED NOT NULL,
  `dias_retraso` int(11) DEFAULT 0,
  `monto` decimal(10,2) DEFAULT 0.00,
  `motivo` varchar(255) DEFAULT NULL,
  `pagada` tinyint(1) DEFAULT 0,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `fecha_pago` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Multas por retraso o perdida';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id_notificacion` bigint(20) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `mensaje` text NOT NULL,
  `leido` tinyint(1) DEFAULT 0,
  `fecha_envio` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Notificaciones a usuarios';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos_multa`
--

CREATE TABLE `pagos_multa` (
  `id_pago` bigint(20) UNSIGNED NOT NULL,
  `id_multa` bigint(20) UNSIGNED NOT NULL,
  `monto_pagado` decimal(10,2) NOT NULL,
  `forma_pago` enum('EFECTIVO','TRANSFERENCIA','TARJETA') DEFAULT 'EFECTIVO',
  `comprobante` varchar(100) DEFAULT NULL,
  `fecha_pago` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Pagos de multas';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `palabras_clave`
--

CREATE TABLE `palabras_clave` (
  `id_palabra` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `parametros`
--

CREATE TABLE `parametros` (
  `id_parametro` int(10) UNSIGNED NOT NULL,
  `grupo_parametro` varchar(50) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `valor` varchar(255) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Parámetros generales de configuración del sistema';

--
-- Volcado de datos para la tabla `parametros`
--

INSERT INTO `parametros` (`id_parametro`, `grupo_parametro`, `nombre`, `valor`, `descripcion`, `activo`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 'RESERVA', 'MAX_RESERVAS_USUARIO', '3', NULL, 1, '2026-06-20 22:56:44', '2026-06-20 22:56:44'),
(2, 'PRESTAMO', 'MAX_RENOVACIONES', '2', NULL, 1, '2026-06-20 22:56:44', '2026-06-20 22:56:44'),
(3, 'PRESTAMO', 'DIAS_PRESTAMO_ALUMNO', '7', NULL, 1, '2026-06-20 22:56:44', '2026-06-20 22:56:44'),
(4, 'PRESTAMO', 'DIAS_PRESTAMO_PROFESOR', '15', NULL, 1, '2026-06-20 22:56:44', '2026-06-20 22:56:44'),
(5, 'QR', 'HORAS_EXPIRACION', '24', NULL, 1, '2026-06-20 22:56:44', '2026-06-20 22:56:44');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos`
--

CREATE TABLE `permisos` (
  `id_permiso` int(10) UNSIGNED NOT NULL,
  `codigo` varchar(80) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `modulo` varchar(80) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Permisos del sistema';

--
-- Volcado de datos para la tabla `permisos`
--

INSERT INTO `permisos` (`id_permiso`, `codigo`, `nombre`, `descripcion`, `modulo`, `activo`) VALUES
(1, 'USR_VIEW', 'Consultar usuarios', NULL, 'Seguridad', 1),
(2, 'USR_EDIT', 'Administrar usuarios', NULL, 'Seguridad', 1),
(3, 'BOOK_VIEW', 'Consultar libros', NULL, 'Biblioteca', 1),
(4, 'BOOK_EDIT', 'Administrar libros', NULL, 'Biblioteca', 1),
(5, 'RES_CREATE', 'Crear reservas', NULL, 'Reservas', 1),
(6, 'RES_CANCEL', 'Cancelar reservas', NULL, 'Reservas', 1),
(7, 'PRESTAMO', 'Gestionar préstamos', NULL, 'Préstamos', 1),
(8, 'REPORTES', 'Ver reportes', NULL, 'Reportes', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prestamos`
--

CREATE TABLE `prestamos` (
  `id_prestamo` bigint(20) UNSIGNED NOT NULL,
  `numero_prestamo` varchar(30) NOT NULL,
  `id_reserva` bigint(20) UNSIGNED DEFAULT NULL,
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `id_estado_prestamo` int(10) UNSIGNED NOT NULL,
  `fecha_prestamo` datetime NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `fecha_devolucion` date DEFAULT NULL,
  `cantidad_renovaciones` tinyint(3) UNSIGNED DEFAULT 0,
  `observacion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cabecera de prestamos';

--
-- Disparadores `prestamos`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prestamos_estadisticas`
--

CREATE TABLE `prestamos_estadisticas` (
  `id_estadistica` bigint(20) UNSIGNED NOT NULL,
  `anio` smallint(6) DEFAULT NULL,
  `mes` tinyint(4) DEFAULT NULL,
  `total_prestamos` int(11) DEFAULT 0,
  `total_devoluciones` int(11) DEFAULT 0,
  `total_multas` decimal(12,2) DEFAULT 0.00,
  `fecha_generacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Resumen estadistico mensual';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prestamo_detalle`
--

CREATE TABLE `prestamo_detalle` (
  `id_prestamo_detalle` bigint(20) UNSIGNED NOT NULL,
  `id_prestamo` bigint(20) UNSIGNED NOT NULL,
  `id_ejemplar` bigint(20) UNSIGNED NOT NULL,
  `estado` enum('PRESTADO','DEVUELTO','EXTRAVIADO','DANADO') DEFAULT 'PRESTADO',
  `observacion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Detalle de ejemplares prestados';

--
-- Disparadores `prestamo_detalle`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `qr_reserva`
--

CREATE TABLE `qr_reserva` (
  `id_qr` bigint(20) UNSIGNED NOT NULL,
  `id_reserva` bigint(20) UNSIGNED NOT NULL,
  `codigo_qr` text NOT NULL,
  `hash_qr` varchar(128) NOT NULL,
  `fecha_generacion` datetime DEFAULT current_timestamp(),
  `fecha_expiracion` datetime DEFAULT NULL,
  `utilizado` tinyint(1) DEFAULT 0,
  `fecha_utilizacion` datetime DEFAULT NULL,
  `fecha_escaneo` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='QR generado para el retiro';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recuperacion_password`
--

CREATE TABLE `recuperacion_password` (
  `id_recuperacion` bigint(20) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `fecha_expiracion` datetime NOT NULL,
  `utilizado` tinyint(1) DEFAULT 0,
  `fecha_creacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Recuperación de contraseña';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `renovaciones`
--

CREATE TABLE `renovaciones` (
  `id_renovacion` bigint(20) UNSIGNED NOT NULL,
  `id_prestamo` bigint(20) UNSIGNED NOT NULL,
  `fecha_renovacion` datetime DEFAULT current_timestamp(),
  `fecha_vencimiento_anterior` date NOT NULL,
  `nueva_fecha_vencimiento` date NOT NULL,
  `id_usuario` int(10) UNSIGNED DEFAULT NULL,
  `observacion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Renovaciones de prestamos';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reservas`
--

CREATE TABLE `reservas` (
  `id_reserva` bigint(20) UNSIGNED NOT NULL,
  `numero_reserva` varchar(30) NOT NULL,
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `fecha_reserva` datetime DEFAULT current_timestamp(),
  `fecha_limite_retiro` datetime NOT NULL,
  `fecha_cancelacion` datetime DEFAULT NULL,
  `fecha_expiracion` datetime DEFAULT NULL,
  `id_estado_reserva` int(10) UNSIGNED NOT NULL,
  `observacion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Disparadores `reservas`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reservas_cancelacion`
--

CREATE TABLE `reservas_cancelacion` (
  `id_cancelacion` bigint(20) UNSIGNED NOT NULL,
  `id_reserva` bigint(20) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `fecha_cancelacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cancelaciones de reservas';

--
-- Disparadores `reservas_cancelacion`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reserva_detalle`
--

CREATE TABLE `reserva_detalle` (
  `id_reserva_detalle` bigint(20) UNSIGNED NOT NULL,
  `id_reserva` bigint(20) UNSIGNED NOT NULL,
  `id_ejemplar` bigint(20) UNSIGNED NOT NULL,
  `estado` enum('RESERVADO','ENTREGADO','CANCELADO','DEVUELTO') DEFAULT 'RESERVADO',
  `observacion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Detalle de ejemplares reservados';

--
-- Disparadores `reserva_detalle`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `restricciones`
--

CREATE TABLE `restricciones` (
  `id_restriccion` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `restricciones`
--

INSERT INTO `restricciones` (`id_restriccion`, `nombre`, `descripcion`, `activo`) VALUES
(1, 'Sin restricción', 'Uso normal', 1),
(2, 'Solo sala', 'No puede salir de biblioteca', 1),
(3, 'Solo docentes', 'Exclusivo para docentes', 1),
(4, 'Referencia', 'Material de consulta', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id_rol` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Roles del sistema';

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id_rol`, `nombre`, `descripcion`, `activo`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 'Administrador', 'Administración general', 1, '2026-06-20 22:51:01', '2026-06-20 22:51:01'),
(2, 'Bibliotecario', 'Gestión de biblioteca', 1, '2026-06-20 22:51:01', '2026-06-20 22:51:01'),
(3, 'Alumno', 'Usuario alumno', 1, '2026-06-20 22:51:01', '2026-06-20 22:51:01'),
(4, 'Profesor', 'Usuario docente', 1, '2026-06-20 22:51:01', '2026-06-20 22:51:01');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol_permiso`
--

CREATE TABLE `rol_permiso` (
  `id_rol_permiso` bigint(20) UNSIGNED NOT NULL,
  `id_rol` int(10) UNSIGNED NOT NULL,
  `id_permiso` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Permisos asignados a cada rol';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sedes`
--

CREATE TABLE `sedes` (
  `id_sede` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sesiones`
--

CREATE TABLE `sesiones` (
  `id_sesion` bigint(20) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `refresh_token_hash` varchar(255) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `navegador` varchar(255) DEFAULT NULL,
  `sistema_operativo` varchar(120) DEFAULT NULL,
  `fecha_inicio` datetime DEFAULT current_timestamp(),
  `fecha_expiracion` datetime DEFAULT NULL,
  `fecha_cierre` datetime DEFAULT NULL,
  `activa` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sesiones de acceso';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_usuario`
--

CREATE TABLE `tipos_usuario` (
  `id_tipo_usuario` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tipos_usuario`
--

INSERT INTO `tipos_usuario` (`id_tipo_usuario`, `nombre`, `descripcion`, `activo`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 'Alumno', 'Estudiante', 1, '2026-06-20 22:51:01', '2026-06-20 22:51:01'),
(2, 'Profesor', 'Docente', 1, '2026-06-20 22:51:01', '2026-06-20 22:51:01'),
(3, 'Funcionario', 'Personal administrativo', 1, '2026-06-20 22:51:01', '2026-06-20 22:51:01');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ubicaciones`
--

CREATE TABLE `ubicaciones` (
  `id_ubicacion` int(10) UNSIGNED NOT NULL,
  `id_sede` int(10) UNSIGNED DEFAULT NULL,
  `sala` varchar(80) DEFAULT NULL,
  `estanteria` varchar(50) DEFAULT NULL,
  `nivel` varchar(30) DEFAULT NULL,
  `posicion` varchar(30) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `id_tipo_usuario` int(10) UNSIGNED NOT NULL,
  `id_carrera` int(10) UNSIGNED DEFAULT NULL,
  `cedula` varchar(20) NOT NULL,
  `nombres` varchar(120) NOT NULL,
  `apellidos` varchar(120) NOT NULL,
  `correo` varchar(150) NOT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `ultimo_acceso` datetime DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Usuarios del sistema';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_rol`
--

CREATE TABLE `usuario_rol` (
  `id_usuario_rol` bigint(20) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `id_rol` int(10) UNSIGNED NOT NULL,
  `fecha_asignacion` datetime DEFAULT current_timestamp(),
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Relación N:M entre usuarios y roles';

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_catalogo`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_catalogo` (
`id_libro` bigint(20) unsigned
,`codigo_catalogo` varchar(40)
,`isbn` varchar(20)
,`titulo` varchar(255)
,`categoria` varchar(120)
,`materia` varchar(120)
,`editorial` varchar(150)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_historial_usuario`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_historial_usuario` (
`id_usuario` int(10) unsigned
,`usuario` varchar(242)
,`numero_reserva` varchar(30)
,`numero_prestamo` varchar(30)
,`fecha_prestamo` datetime
,`fecha_devolucion` date
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_libros_disponibles`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_libros_disponibles` (
`id_libro` bigint(20) unsigned
,`codigo_catalogo` varchar(40)
,`isbn` varchar(20)
,`titulo` varchar(255)
,`id_ejemplar` bigint(20) unsigned
,`codigo_ejemplar` varchar(40)
,`codigo_barra` varchar(60)
,`estado` varchar(50)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_libros_mas_prestados`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_libros_mas_prestados` (
`id_libro` bigint(20) unsigned
,`titulo` varchar(255)
,`total_prestamos` bigint(21)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_multas_pendientes`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_multas_pendientes` (
`id_multa` bigint(20) unsigned
,`numero_prestamo` varchar(30)
,`cedula` varchar(20)
,`usuario` varchar(242)
,`monto` decimal(10,2)
,`dias_retraso` int(11)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_prestamos_activos`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_prestamos_activos` (
`numero_prestamo` varchar(30)
,`usuario` varchar(242)
,`fecha_prestamo` datetime
,`fecha_vencimiento` date
,`estado` varchar(50)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_reservas_activas`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_reservas_activas` (
`numero_reserva` varchar(30)
,`usuario` varchar(242)
,`fecha_reserva` datetime
,`estado` varchar(50)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_catalogo`
--
DROP TABLE IF EXISTS `vw_catalogo`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `vw_catalogo`  AS SELECT `l`.`id_libro` AS `id_libro`, `l`.`codigo_catalogo` AS `codigo_catalogo`, `l`.`isbn` AS `isbn`, `l`.`titulo` AS `titulo`, `c`.`nombre` AS `categoria`, `m`.`nombre` AS `materia`, `ed`.`nombre` AS `editorial` FROM (((`libros` `l` left join `categorias` `c` on(`l`.`id_categoria` = `c`.`id_categoria`)) left join `materias` `m` on(`l`.`id_materia` = `m`.`id_materia`)) left join `editoriales` `ed` on(`l`.`id_editorial` = `ed`.`id_editorial`)) WHERE `l`.`activo` = 1 ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_historial_usuario`
--
DROP TABLE IF EXISTS `vw_historial_usuario`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `vw_historial_usuario`  AS SELECT `u`.`id_usuario` AS `id_usuario`, concat(`u`.`apellidos`,', ',`u`.`nombres`) AS `usuario`, `r`.`numero_reserva` AS `numero_reserva`, `p`.`numero_prestamo` AS `numero_prestamo`, `p`.`fecha_prestamo` AS `fecha_prestamo`, `p`.`fecha_devolucion` AS `fecha_devolucion` FROM ((`usuarios` `u` left join `reservas` `r` on(`u`.`id_usuario` = `r`.`id_usuario`)) left join `prestamos` `p` on(`r`.`id_reserva` = `p`.`id_reserva`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_libros_disponibles`
--
DROP TABLE IF EXISTS `vw_libros_disponibles`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `vw_libros_disponibles`  AS SELECT `l`.`id_libro` AS `id_libro`, `l`.`codigo_catalogo` AS `codigo_catalogo`, `l`.`isbn` AS `isbn`, `l`.`titulo` AS `titulo`, `e`.`id_ejemplar` AS `id_ejemplar`, `e`.`codigo_ejemplar` AS `codigo_ejemplar`, `e`.`codigo_barra` AS `codigo_barra`, `ee`.`nombre` AS `estado` FROM ((`libros` `l` join `ejemplares` `e` on(`l`.`id_libro` = `e`.`id_libro`)) join `estados_ejemplar` `ee` on(`e`.`id_estado_ejemplar` = `ee`.`id_estado_ejemplar`)) WHERE `ee`.`nombre` = 'Disponible' AND `l`.`activo` = 1 AND `e`.`activo` = 1 ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_libros_mas_prestados`
--
DROP TABLE IF EXISTS `vw_libros_mas_prestados`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `vw_libros_mas_prestados`  AS SELECT `l`.`id_libro` AS `id_libro`, `l`.`titulo` AS `titulo`, count(`pd`.`id_prestamo_detalle`) AS `total_prestamos` FROM ((`libros` `l` join `ejemplares` `e` on(`l`.`id_libro` = `e`.`id_libro`)) join `prestamo_detalle` `pd` on(`e`.`id_ejemplar` = `pd`.`id_ejemplar`)) GROUP BY `l`.`id_libro`, `l`.`titulo` ORDER BY count(`pd`.`id_prestamo_detalle`) DESC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_multas_pendientes`
--
DROP TABLE IF EXISTS `vw_multas_pendientes`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `vw_multas_pendientes`  AS SELECT `m`.`id_multa` AS `id_multa`, `p`.`numero_prestamo` AS `numero_prestamo`, `u`.`cedula` AS `cedula`, concat(`u`.`apellidos`,', ',`u`.`nombres`) AS `usuario`, `m`.`monto` AS `monto`, `m`.`dias_retraso` AS `dias_retraso` FROM ((`multas` `m` join `prestamos` `p` on(`m`.`id_prestamo` = `p`.`id_prestamo`)) join `usuarios` `u` on(`p`.`id_usuario` = `u`.`id_usuario`)) WHERE `m`.`pagada` = 0 ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_prestamos_activos`
--
DROP TABLE IF EXISTS `vw_prestamos_activos`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `vw_prestamos_activos`  AS SELECT `p`.`numero_prestamo` AS `numero_prestamo`, concat(`u`.`apellidos`,', ',`u`.`nombres`) AS `usuario`, `p`.`fecha_prestamo` AS `fecha_prestamo`, `p`.`fecha_vencimiento` AS `fecha_vencimiento`, `ep`.`nombre` AS `estado` FROM ((`prestamos` `p` join `usuarios` `u` on(`p`.`id_usuario` = `u`.`id_usuario`)) join `estados_prestamo` `ep` on(`p`.`id_estado_prestamo` = `ep`.`id_estado_prestamo`)) WHERE `p`.`activo` = 1 ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_reservas_activas`
--
DROP TABLE IF EXISTS `vw_reservas_activas`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `vw_reservas_activas`  AS SELECT `r`.`numero_reserva` AS `numero_reserva`, concat(`u`.`apellidos`,', ',`u`.`nombres`) AS `usuario`, `r`.`fecha_reserva` AS `fecha_reserva`, `er`.`nombre` AS `estado` FROM ((`reservas` `r` join `usuarios` `u` on(`r`.`id_usuario` = `u`.`id_usuario`)) join `estados_reserva` `er` on(`r`.`id_estado_reserva` = `er`.`id_estado_reserva`)) WHERE `r`.`activo` = 1 ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  ADD PRIMARY KEY (`id_auditoria`),
  ADD KEY `idx_auditoria_usuario` (`id_usuario`),
  ADD KEY `idx_auditoria_fecha` (`fecha_evento`),
  ADD KEY `idx_auditoria_tabla` (`tabla_afectada`),
  ADD KEY `idx_auditoria_registro` (`tabla_afectada`,`registro_id`);
-- OPTIMIZACION: registro_id no tenia indice pese a usarse para buscar el
-- historial de un registro especifico; se agrega compuesto con tabla_afectada
-- (consulta tipica: "auditoria de la fila X de la tabla Y").

--
-- Indices de la tabla `autores`
--
ALTER TABLE `autores`
  ADD PRIMARY KEY (`id_autor`),
  ADD KEY `idx_autor_nombre` (`apellidos`,`nombres`);

--
-- Indices de la tabla `autores_libros`
--
ALTER TABLE `autores_libros`
  ADD PRIMARY KEY (`id_autor_libro`),
  ADD UNIQUE KEY `uk_libro_autor` (`id_libro`,`id_autor`),
  ADD KEY `fk_al_autor` (`id_autor`);

--
-- Indices de la tabla `carreras`
--
ALTER TABLE `carreras`
  ADD PRIMARY KEY (`id_carrera`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id_categoria`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `cola_espera`
--
ALTER TABLE `cola_espera`
  ADD PRIMARY KEY (`id_cola`),
  ADD KEY `idx_cola_libro` (`id_libro`),
  ADD KEY `idx_cola_usuario` (`id_usuario`);

--
-- Indices de la tabla `colecciones`
--
ALTER TABLE `colecciones`
  ADD PRIMARY KEY (`id_coleccion`);

--
-- Indices de la tabla `configuracion_qr`
--
ALTER TABLE `configuracion_qr`
  ADD PRIMARY KEY (`id_configuracion_qr`);

--
-- Indices de la tabla `dias_no_habiles`
--
ALTER TABLE `dias_no_habiles`
  ADD PRIMARY KEY (`id_dia`),
  ADD UNIQUE KEY `fecha` (`fecha`);

--
-- Indices de la tabla `editoriales`
--
ALTER TABLE `editoriales`
  ADD PRIMARY KEY (`id_editorial`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `ejemplares`
--
ALTER TABLE `ejemplares`
  ADD PRIMARY KEY (`id_ejemplar`),
  ADD UNIQUE KEY `codigo_ejemplar` (`codigo_ejemplar`),
  ADD UNIQUE KEY `codigo_barra` (`codigo_barra`),
  ADD KEY `fk_ejemplar_ubicacion` (`id_ubicacion`),
  ADD KEY `idx_ejemplar_estado` (`id_estado_ejemplar`),
  ADD KEY `idx_ejemplar_libro` (`id_libro`);
-- OPTIMIZACION: se quitaron idx_ejemplar_barra e idx_ejemplar_codigo
-- (redundantes con las UNIQUE KEY codigo_barra/codigo_ejemplar).

--
-- Indices de la tabla `ejemplar_movimiento`
--
ALTER TABLE `ejemplar_movimiento`
  ADD PRIMARY KEY (`id_movimiento`),
  ADD KEY `fk_movimiento_ejemplar` (`id_ejemplar`);

--
-- Indices de la tabla `estados_ejemplar`
--
ALTER TABLE `estados_ejemplar`
  ADD PRIMARY KEY (`id_estado_ejemplar`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `estados_prestamo`
--
ALTER TABLE `estados_prestamo`
  ADD PRIMARY KEY (`id_estado_prestamo`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `estados_reserva`
--
ALTER TABLE `estados_reserva`
  ADD PRIMARY KEY (`id_estado_reserva`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `historial_prestamo`
--
ALTER TABLE `historial_prestamo`
  ADD PRIMARY KEY (`id_historial`),
  ADD KEY `fk_hp_prestamo` (`id_prestamo`),
  ADD KEY `fk_hp_estado` (`id_estado_prestamo`),
  ADD KEY `fk_hp_usuario` (`id_usuario`);

--
-- Indices de la tabla `historial_reserva`
--
ALTER TABLE `historial_reserva`
  ADD PRIMARY KEY (`id_historial`),
  ADD KEY `fk_hist_reserva` (`id_reserva`),
  ADD KEY `fk_hist_estado` (`id_estado_reserva`),
  ADD KEY `fk_hist_usuario` (`id_usuario`);

--
-- Indices de la tabla `libros`
--
ALTER TABLE `libros`
  ADD PRIMARY KEY (`id_libro`),
  ADD UNIQUE KEY `isbn` (`isbn`),
  ADD UNIQUE KEY `codigo_catalogo` (`codigo_catalogo`),
  ADD KEY `fk_libro_coleccion` (`id_coleccion`),
  ADD KEY `fk_libro_restriccion` (`id_restriccion`),
  ADD KEY `idx_libro_titulo` (`titulo`),
  ADD KEY `idx_libro_editorial` (`id_editorial`),
  ADD KEY `idx_libro_categoria` (`id_categoria`),
  ADD KEY `idx_libro_materia` (`id_materia`),
  ADD FULLTEXT KEY `idx_libro_fulltext` (`titulo`,`subtitulo`,`descripcion`);
-- OPTIMIZACION: se quitaron idx_libro_isbn e idx_libro_catalogo (redundantes:
-- las UNIQUE KEY isbn/codigo_catalogo ya sirven como indice para busquedas
-- por igualdad/prefijo; un indice extra solo agrega costo de escritura).
-- Se agrego FULLTEXT idx_libro_fulltext para sp_buscar_libros.

--
-- Indices de la tabla `libro_palabra_clave`
--
ALTER TABLE `libro_palabra_clave`
  ADD PRIMARY KEY (`id_libro_palabra`),
  ADD UNIQUE KEY `uk_libro_palabra` (`id_libro`,`id_palabra`),
  ADD KEY `idx_lpc_libro` (`id_libro`),
  ADD KEY `idx_lpc_palabra` (`id_palabra`);

--
-- Indices de la tabla `materias`
--
ALTER TABLE `materias`
  ADD PRIMARY KEY (`id_materia`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `multas`
--
ALTER TABLE `multas`
  ADD PRIMARY KEY (`id_multa`),
  ADD KEY `idx_multa_prestamo` (`id_prestamo`),
  ADD KEY `idx_multa_pagada` (`pagada`);
-- OPTIMIZACION: vw_multas_pendientes filtra por pagada=0 sin indice de apoyo.

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id_notificacion`),
  ADD KEY `idx_notificacion_usuario_leido` (`id_usuario`,`leido`);
-- OPTIMIZACION: idx_notificacion_usuario se reemplaza por un compuesto
-- (id_usuario, leido); sigue sirviendo busquedas solo por usuario (prefijo
-- izquierdo) y ademas acelera la consulta tipica "notificaciones no leidas
-- de un usuario".

--
-- Indices de la tabla `pagos_multa`
--
ALTER TABLE `pagos_multa`
  ADD PRIMARY KEY (`id_pago`),
  ADD KEY `fk_pago_multa` (`id_multa`);

--
-- Indices de la tabla `palabras_clave`
--
ALTER TABLE `palabras_clave`
  ADD PRIMARY KEY (`id_palabra`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `parametros`
--
ALTER TABLE `parametros`
  ADD PRIMARY KEY (`id_parametro`),
  ADD UNIQUE KEY `uk_parametro` (`grupo_parametro`,`nombre`);

--
-- Indices de la tabla `permisos`
--
ALTER TABLE `permisos`
  ADD PRIMARY KEY (`id_permiso`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indices de la tabla `prestamos`
--
ALTER TABLE `prestamos`
  ADD PRIMARY KEY (`id_prestamo`),
  ADD UNIQUE KEY `numero_prestamo` (`numero_prestamo`),
  ADD KEY `fk_prestamo_reserva` (`id_reserva`),
  ADD KEY `idx_prestamo_usuario` (`id_usuario`),
  ADD KEY `idx_prestamo_estado` (`id_estado_prestamo`),
  ADD KEY `idx_prestamo_fecha` (`fecha_prestamo`),
  ADD KEY `idx_prestamo_vencimiento` (`activo`,`fecha_vencimiento`);
-- OPTIMIZACION: se quito idx_prestamo_numero (redundante con la UNIQUE KEY
-- numero_prestamo). Se agrego idx_prestamo_vencimiento para acelerar la
-- deteccion de prestamos vencidos (activo=1 AND fecha_vencimiento < hoy),
-- una consulta operativa frecuente que antes no tenia indice optimo.

--
-- Indices de la tabla `prestamos_estadisticas`
--
ALTER TABLE `prestamos_estadisticas`
  ADD PRIMARY KEY (`id_estadistica`);

--
-- Indices de la tabla `prestamo_detalle`
--
ALTER TABLE `prestamo_detalle`
  ADD PRIMARY KEY (`id_prestamo_detalle`),
  ADD KEY `idx_prestamo_detalle_prestamo` (`id_prestamo`),
  ADD KEY `idx_prestamo_detalle_ejemplar` (`id_ejemplar`);

--
-- Indices de la tabla `qr_reserva`
--
ALTER TABLE `qr_reserva`
  ADD PRIMARY KEY (`id_qr`),
  ADD KEY `idx_qr_hash` (`hash_qr`(64)),
  ADD KEY `idx_qr_reserva` (`id_reserva`);

--
-- Indices de la tabla `recuperacion_password`
--
ALTER TABLE `recuperacion_password`
  ADD PRIMARY KEY (`id_recuperacion`),
  ADD UNIQUE KEY `uk_recuperacion_token` (`token_hash`),
  ADD KEY `fk_recuperacion_usuario` (`id_usuario`);

--
-- Indices de la tabla `renovaciones`
--
ALTER TABLE `renovaciones`
  ADD PRIMARY KEY (`id_renovacion`),
  ADD KEY `fk_renovacion_prestamo` (`id_prestamo`),
  ADD KEY `fk_renovacion_usuario` (`id_usuario`);

--
-- Indices de la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD PRIMARY KEY (`id_reserva`),
  ADD UNIQUE KEY `numero_reserva` (`numero_reserva`),
  ADD KEY `idx_reserva_usuario` (`id_usuario`),
  ADD KEY `idx_reserva_estado` (`id_estado_reserva`),
  ADD KEY `idx_reserva_fecha` (`fecha_reserva`);
-- OPTIMIZACION: se quito idx_reserva_numero (redundante con la UNIQUE KEY
-- numero_reserva).

--
-- Indices de la tabla `reservas_cancelacion`
--
ALTER TABLE `reservas_cancelacion`
  ADD PRIMARY KEY (`id_cancelacion`),
  ADD KEY `fk_cancelacion_reserva` (`id_reserva`),
  ADD KEY `fk_cancelacion_usuario` (`id_usuario`);

--
-- Indices de la tabla `reserva_detalle`
--
ALTER TABLE `reserva_detalle`
  ADD PRIMARY KEY (`id_reserva_detalle`),
  ADD KEY `idx_reserva_detalle_reserva` (`id_reserva`),
  ADD KEY `idx_reserva_detalle_ejemplar` (`id_ejemplar`);

--
-- Indices de la tabla `restricciones`
--
ALTER TABLE `restricciones`
  ADD PRIMARY KEY (`id_restriccion`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_rol`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `rol_permiso`
--
ALTER TABLE `rol_permiso`
  ADD PRIMARY KEY (`id_rol_permiso`),
  ADD UNIQUE KEY `uk_rol_permiso` (`id_rol`,`id_permiso`),
  ADD KEY `fk_rol_permiso_permiso` (`id_permiso`);

--
-- Indices de la tabla `sedes`
--
ALTER TABLE `sedes`
  ADD PRIMARY KEY (`id_sede`);

--
-- Indices de la tabla `sesiones`
--
ALTER TABLE `sesiones`
  ADD PRIMARY KEY (`id_sesion`),
  ADD UNIQUE KEY `uk_sesion_token` (`token_hash`),
  ADD KEY `fk_sesion_usuario` (`id_usuario`);
-- OPTIMIZACION: token_hash no tenia indice; las validaciones de sesion
-- (buscar por token en cada request) hacian table scan. Ademas el UNIQUE
-- evita colisiones.

--
-- Indices de la tabla `tipos_usuario`
--
ALTER TABLE `tipos_usuario`
  ADD PRIMARY KEY (`id_tipo_usuario`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `ubicaciones`
--
ALTER TABLE `ubicaciones`
  ADD PRIMARY KEY (`id_ubicacion`),
  ADD KEY `fk_ubicacion_sede` (`id_sede`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `cedula` (`cedula`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD KEY `fk_usuario_tipo` (`id_tipo_usuario`),
  ADD KEY `fk_usuario_carrera` (`id_carrera`),
  ADD KEY `idx_usuario_apellidos` (`apellidos`,`nombres`);
-- OPTIMIZACION: se quitaron idx_usuario_cedula e idx_usuario_correo
-- (redundantes con las UNIQUE KEY cedula/correo). Se agrego indice para la
-- nueva FK fk_usuario_carrera.

--
-- Indices de la tabla `usuario_rol`
--
ALTER TABLE `usuario_rol`
  ADD PRIMARY KEY (`id_usuario_rol`),
  ADD UNIQUE KEY `uk_usuario_rol` (`id_usuario`,`id_rol`),
  ADD KEY `fk_usuario_rol_rol` (`id_rol`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  MODIFY `id_auditoria` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `autores`
--
ALTER TABLE `autores`
  MODIFY `id_autor` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `autores_libros`
--
ALTER TABLE `autores_libros`
  MODIFY `id_autor_libro` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `carreras`
--
ALTER TABLE `carreras`
  MODIFY `id_carrera` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id_categoria` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `cola_espera`
--
ALTER TABLE `cola_espera`
  MODIFY `id_cola` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `colecciones`
--
ALTER TABLE `colecciones`
  MODIFY `id_coleccion` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `configuracion_qr`
--
ALTER TABLE `configuracion_qr`
  MODIFY `id_configuracion_qr` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `dias_no_habiles`
--
ALTER TABLE `dias_no_habiles`
  MODIFY `id_dia` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `editoriales`
--
ALTER TABLE `editoriales`
  MODIFY `id_editorial` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ejemplares`
--
ALTER TABLE `ejemplares`
  MODIFY `id_ejemplar` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ejemplar_movimiento`
--
ALTER TABLE `ejemplar_movimiento`
  MODIFY `id_movimiento` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estados_ejemplar`
--
ALTER TABLE `estados_ejemplar`
  MODIFY `id_estado_ejemplar` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `estados_prestamo`
--
ALTER TABLE `estados_prestamo`
  MODIFY `id_estado_prestamo` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `estados_reserva`
--
ALTER TABLE `estados_reserva`
  MODIFY `id_estado_reserva` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `historial_prestamo`
--
ALTER TABLE `historial_prestamo`
  MODIFY `id_historial` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `historial_reserva`
--
ALTER TABLE `historial_reserva`
  MODIFY `id_historial` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `libros`
--
ALTER TABLE `libros`
  MODIFY `id_libro` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `libro_palabra_clave`
--
ALTER TABLE `libro_palabra_clave`
  MODIFY `id_libro_palabra` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `materias`
--
ALTER TABLE `materias`
  MODIFY `id_materia` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `multas`
--
ALTER TABLE `multas`
  MODIFY `id_multa` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id_notificacion` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pagos_multa`
--
ALTER TABLE `pagos_multa`
  MODIFY `id_pago` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `palabras_clave`
--
ALTER TABLE `palabras_clave`
  MODIFY `id_palabra` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `parametros`
--
ALTER TABLE `parametros`
  MODIFY `id_parametro` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `permisos`
--
ALTER TABLE `permisos`
  MODIFY `id_permiso` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `prestamos`
--
ALTER TABLE `prestamos`
  MODIFY `id_prestamo` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `prestamos_estadisticas`
--
ALTER TABLE `prestamos_estadisticas`
  MODIFY `id_estadistica` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `prestamo_detalle`
--
ALTER TABLE `prestamo_detalle`
  MODIFY `id_prestamo_detalle` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `qr_reserva`
--
ALTER TABLE `qr_reserva`
  MODIFY `id_qr` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `recuperacion_password`
--
ALTER TABLE `recuperacion_password`
  MODIFY `id_recuperacion` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `renovaciones`
--
ALTER TABLE `renovaciones`
  MODIFY `id_renovacion` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `reservas`
--
ALTER TABLE `reservas`
  MODIFY `id_reserva` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `reservas_cancelacion`
--
ALTER TABLE `reservas_cancelacion`
  MODIFY `id_cancelacion` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `reserva_detalle`
--
ALTER TABLE `reserva_detalle`
  MODIFY `id_reserva_detalle` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `restricciones`
--
ALTER TABLE `restricciones`
  MODIFY `id_restriccion` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `rol_permiso`
--
ALTER TABLE `rol_permiso`
  MODIFY `id_rol_permiso` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sedes`
--
ALTER TABLE `sedes`
  MODIFY `id_sede` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sesiones`
--
ALTER TABLE `sesiones`
  MODIFY `id_sesion` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tipos_usuario`
--
ALTER TABLE `tipos_usuario`
  MODIFY `id_tipo_usuario` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `ubicaciones`
--
ALTER TABLE `ubicaciones`
  MODIFY `id_ubicacion` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuario_rol`
--
ALTER TABLE `usuario_rol`
  MODIFY `id_usuario_rol` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `auditoria`
--
ALTER TABLE `auditoria`
  ADD CONSTRAINT `fk_auditoria_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `autores_libros`
--
ALTER TABLE `autores_libros`
  ADD CONSTRAINT `fk_al_autor` FOREIGN KEY (`id_autor`) REFERENCES `autores` (`id_autor`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_al_libro` FOREIGN KEY (`id_libro`) REFERENCES `libros` (`id_libro`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `cola_espera`
--
ALTER TABLE `cola_espera`
  ADD CONSTRAINT `fk_cola_libro` FOREIGN KEY (`id_libro`) REFERENCES `libros` (`id_libro`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cola_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `ejemplares`
--
ALTER TABLE `ejemplares`
  ADD CONSTRAINT `fk_ejemplar_estado` FOREIGN KEY (`id_estado_ejemplar`) REFERENCES `estados_ejemplar` (`id_estado_ejemplar`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ejemplar_libro` FOREIGN KEY (`id_libro`) REFERENCES `libros` (`id_libro`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ejemplar_ubicacion` FOREIGN KEY (`id_ubicacion`) REFERENCES `ubicaciones` (`id_ubicacion`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `ejemplar_movimiento`
--
ALTER TABLE `ejemplar_movimiento`
  ADD CONSTRAINT `fk_movimiento_ejemplar` FOREIGN KEY (`id_ejemplar`) REFERENCES `ejemplares` (`id_ejemplar`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `historial_prestamo`
--
ALTER TABLE `historial_prestamo`
  ADD CONSTRAINT `fk_hp_estado` FOREIGN KEY (`id_estado_prestamo`) REFERENCES `estados_prestamo` (`id_estado_prestamo`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hp_prestamo` FOREIGN KEY (`id_prestamo`) REFERENCES `prestamos` (`id_prestamo`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hp_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `historial_reserva`
--
ALTER TABLE `historial_reserva`
  ADD CONSTRAINT `fk_hist_estado` FOREIGN KEY (`id_estado_reserva`) REFERENCES `estados_reserva` (`id_estado_reserva`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hist_reserva` FOREIGN KEY (`id_reserva`) REFERENCES `reservas` (`id_reserva`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hist_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `libros`
--
ALTER TABLE `libros`
  ADD CONSTRAINT `fk_libro_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_libro_coleccion` FOREIGN KEY (`id_coleccion`) REFERENCES `colecciones` (`id_coleccion`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_libro_editorial` FOREIGN KEY (`id_editorial`) REFERENCES `editoriales` (`id_editorial`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_libro_materia` FOREIGN KEY (`id_materia`) REFERENCES `materias` (`id_materia`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_libro_restriccion` FOREIGN KEY (`id_restriccion`) REFERENCES `restricciones` (`id_restriccion`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `libro_palabra_clave`
--
ALTER TABLE `libro_palabra_clave`
  ADD CONSTRAINT `fk_lpc_libro` FOREIGN KEY (`id_libro`) REFERENCES `libros` (`id_libro`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_lpc_palabra` FOREIGN KEY (`id_palabra`) REFERENCES `palabras_clave` (`id_palabra`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `multas`
--
ALTER TABLE `multas`
  ADD CONSTRAINT `fk_multa_prestamo` FOREIGN KEY (`id_prestamo`) REFERENCES `prestamos` (`id_prestamo`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `fk_notificacion_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pagos_multa`
--
ALTER TABLE `pagos_multa`
  ADD CONSTRAINT `fk_pago_multa` FOREIGN KEY (`id_multa`) REFERENCES `multas` (`id_multa`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `prestamos`
--
ALTER TABLE `prestamos`
  ADD CONSTRAINT `fk_prestamo_estado` FOREIGN KEY (`id_estado_prestamo`) REFERENCES `estados_prestamo` (`id_estado_prestamo`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_prestamo_reserva` FOREIGN KEY (`id_reserva`) REFERENCES `reservas` (`id_reserva`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_prestamo_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `prestamo_detalle`
--
ALTER TABLE `prestamo_detalle`
  ADD CONSTRAINT `fk_pd_ejemplar` FOREIGN KEY (`id_ejemplar`) REFERENCES `ejemplares` (`id_ejemplar`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pd_prestamo` FOREIGN KEY (`id_prestamo`) REFERENCES `prestamos` (`id_prestamo`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `qr_reserva`
--
ALTER TABLE `qr_reserva`
  ADD CONSTRAINT `fk_qr_reserva` FOREIGN KEY (`id_reserva`) REFERENCES `reservas` (`id_reserva`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `recuperacion_password`
--
ALTER TABLE `recuperacion_password`
  ADD CONSTRAINT `fk_recuperacion_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `renovaciones`
--
ALTER TABLE `renovaciones`
  ADD CONSTRAINT `fk_renovacion_prestamo` FOREIGN KEY (`id_prestamo`) REFERENCES `prestamos` (`id_prestamo`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_renovacion_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD CONSTRAINT `fk_reserva_estado` FOREIGN KEY (`id_estado_reserva`) REFERENCES `estados_reserva` (`id_estado_reserva`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reserva_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `reservas_cancelacion`
--
ALTER TABLE `reservas_cancelacion`
  ADD CONSTRAINT `fk_cancelacion_reserva` FOREIGN KEY (`id_reserva`) REFERENCES `reservas` (`id_reserva`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cancelacion_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `reserva_detalle`
--
ALTER TABLE `reserva_detalle`
  ADD CONSTRAINT `fk_detalle_ejemplar` FOREIGN KEY (`id_ejemplar`) REFERENCES `ejemplares` (`id_ejemplar`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detalle_reserva` FOREIGN KEY (`id_reserva`) REFERENCES `reservas` (`id_reserva`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `rol_permiso`
--
ALTER TABLE `rol_permiso`
  ADD CONSTRAINT `fk_rol_permiso_permiso` FOREIGN KEY (`id_permiso`) REFERENCES `permisos` (`id_permiso`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rol_permiso_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `sesiones`
--
ALTER TABLE `sesiones`
  ADD CONSTRAINT `fk_sesion_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `ubicaciones`
--
ALTER TABLE `ubicaciones`
  ADD CONSTRAINT `fk_ubicacion_sede` FOREIGN KEY (`id_sede`) REFERENCES `sedes` (`id_sede`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuario_carrera` FOREIGN KEY (`id_carrera`) REFERENCES `carreras` (`id_carrera`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_usuario_tipo` FOREIGN KEY (`id_tipo_usuario`) REFERENCES `tipos_usuario` (`id_tipo_usuario`) ON UPDATE CASCADE;
-- CORRECCION: la tabla `carreras` existia en el esquema original sin ninguna
-- relacion entrante ni saliente (huerfana). Se conecta a `usuarios` como dato
-- opcional (NULL permitido) porque no todos los tipos de usuario (p.ej.
-- funcionarios) tienen carrera asociada.

--
-- Filtros para la tabla `usuario_rol`
--
ALTER TABLE `usuario_rol`
  ADD CONSTRAINT `fk_usuario_rol_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_usuario_rol_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;
-- --------------------------------------------------------

--
-- ============================================================
-- SECCION NUEVA: CORRECCIONES Y OPTIMIZACIONES ADICIONALES
-- ============================================================
-- Constraints de validacion de datos a nivel de motor (antes dependian
-- 100% de la capa de aplicacion) y automatizacion de los estados
-- 'Vencida' (reservas) / 'Vencido' (prestamos), que existian en los
-- catalogos estados_reserva/estados_prestamo pero ningun objeto los
-- asignaba nunca.
--

--
-- CHECK constraints
--
ALTER TABLE `multas`
  ADD CONSTRAINT `chk_multa_monto` CHECK (`monto` >= 0),
  ADD CONSTRAINT `chk_multa_dias` CHECK (`dias_retraso` >= 0);

ALTER TABLE `ejemplares`
  ADD CONSTRAINT `chk_ejemplar_costo` CHECK (`costo` IS NULL OR `costo` >= 0);

ALTER TABLE `prestamos`
  ADD CONSTRAINT `chk_prestamo_fechas` CHECK (`fecha_devolucion` IS NULL OR `fecha_devolucion` >= DATE(`fecha_prestamo`));

ALTER TABLE `pagos_multa`
  ADD CONSTRAINT `chk_pago_monto` CHECK (`monto_pagado` > 0);

--
-- ============================================================
-- MANTENIMIENTO PARA CLEVER CLOUD
-- ============================================================
-- Los eventos MySQL fueron retirados porque requieren event_scheduler
-- y privilegios de administración del servidor que no deben asumirse
-- en un servicio gestionado.
--
-- La aplicacion puede ejecutar estos procedimientos mediante un cron/job:
--   sp_marcar_reservas_vencidas()  -> cada hora
--   sp_marcar_prestamos_vencidos() -> una vez al dia
--
-- Ejemplo desde la aplicacion:
--   CALL sp_marcar_reservas_vencidas();
--   CALL sp_marcar_prestamos_vencidos();
-- ============================================================

DELIMITER $$

CREATE PROCEDURE `sp_marcar_reservas_vencidas` ()
BEGIN
    START TRANSACTION;

    -- Reemplaza el historial que antes generaba trg_reserva_estado_au.
    INSERT INTO historial_reserva(
        id_reserva,
        id_estado_reserva,
        fecha_evento
    )
    SELECT
        id_reserva,
        5,
        NOW()
      FROM reservas
     WHERE id_estado_reserva IN (1,2,3)
       AND fecha_limite_retiro < NOW();

    UPDATE reservas
       SET id_estado_reserva = 5,
           activo = FALSE,
           fecha_expiracion = NOW()
     WHERE id_estado_reserva IN (1,2,3)
       AND fecha_limite_retiro < NOW();

    UPDATE ejemplares e
    JOIN reserva_detalle rd ON rd.id_ejemplar = e.id_ejemplar
    JOIN reservas r ON r.id_reserva = rd.id_reserva
       SET e.id_estado_ejemplar = 1
     WHERE r.id_estado_reserva = 5
       AND e.id_estado_ejemplar = 2
       AND rd.estado = 'RESERVADO';

    UPDATE reserva_detalle rd
    JOIN reservas r ON r.id_reserva = rd.id_reserva
       SET rd.estado = 'CANCELADO'
     WHERE r.id_estado_reserva = 5
       AND rd.estado = 'RESERVADO';

    COMMIT;
END$$

CREATE PROCEDURE `sp_marcar_prestamos_vencidos` ()
BEGIN
    UPDATE prestamos
       SET id_estado_prestamo = 3
     WHERE activo = TRUE
       AND fecha_devolucion IS NULL
       AND fecha_vencimiento < CURDATE()
       AND id_estado_prestamo <> 3;
END$$

DELIMITER ;

-- --------------------------------------------------------


/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- ============================================================
-- PROCEDIMIENTOS ADICIONALES PARA LA APLICACION FINAL DE TESIS
-- ============================================================
DROP PROCEDURE IF EXISTS `sp_confirmar_reserva`;
DROP PROCEDURE IF EXISTS `sp_generar_multa`;
DROP PROCEDURE IF EXISTS `sp_registrar_pago_multa`;
DROP PROCEDURE IF EXISTS `sp_crear_usuario`;
DROP PROCEDURE IF EXISTS `sp_cambiar_estado_ejemplar`;
DROP PROCEDURE IF EXISTS `sp_registrar_movimiento_ejemplar`;

DELIMITER $$
CREATE PROCEDURE `sp_confirmar_reserva`(IN p_id_reserva BIGINT, IN p_usuario INT)
BEGIN
    START TRANSACTION;
    UPDATE reservas SET id_estado_reserva=3,activo=TRUE WHERE id_reserva=p_id_reserva AND id_estado_reserva IN (1,2);
    INSERT INTO historial_reserva(id_reserva,id_estado_reserva,id_usuario,comentario,fecha_evento)
    VALUES(p_id_reserva,3,p_usuario,'Reserva confirmada y lista para retiro',NOW());
    COMMIT;
END$$

CREATE PROCEDURE `sp_generar_multa`(IN p_id_prestamo BIGINT,IN p_dias_retraso INT,IN p_monto DECIMAL(10,2),IN p_motivo VARCHAR(255))
BEGIN
    INSERT INTO multas(id_prestamo,dias_retraso,monto,motivo,pagada,fecha_registro)
    VALUES(p_id_prestamo,GREATEST(p_dias_retraso,0),GREATEST(p_monto,0),p_motivo,0,NOW());
END$$

CREATE PROCEDURE `sp_registrar_pago_multa`(IN p_id_multa BIGINT,IN p_monto DECIMAL(10,2),IN p_forma_pago VARCHAR(20),IN p_comprobante VARCHAR(100))
BEGIN
    DECLARE v_total DECIMAL(10,2);
    DECLARE v_pagado DECIMAL(10,2);
    START TRANSACTION;
    SELECT monto INTO v_total FROM multas WHERE id_multa=p_id_multa FOR UPDATE;
    SELECT COALESCE(SUM(monto_pagado),0) INTO v_pagado FROM pagos_multa WHERE id_multa=p_id_multa;
    IF v_total IS NULL THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='La multa no existe';
    END IF;
    IF p_monto <= 0 OR v_pagado+p_monto > v_total THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='El pago supera el saldo de la multa';
    END IF;
    INSERT INTO pagos_multa(id_multa,monto_pagado,forma_pago,comprobante,fecha_pago)
    VALUES(p_id_multa,p_monto,p_forma_pago,p_comprobante,NOW());
    IF v_pagado+p_monto >= v_total THEN
        UPDATE multas SET pagada=1,fecha_pago=NOW() WHERE id_multa=p_id_multa;
    END IF;
    COMMIT;
END$$

CREATE PROCEDURE `sp_crear_usuario`(IN p_tipo INT,IN p_carrera INT,IN p_cedula VARCHAR(20),IN p_nombres VARCHAR(120),IN p_apellidos VARCHAR(120),IN p_correo VARCHAR(150),IN p_telefono VARCHAR(30),IN p_password_hash VARCHAR(255),IN p_rol INT)
BEGIN
    DECLARE v_id BIGINT;
    INSERT INTO usuarios(id_tipo_usuario,id_carrera,cedula,nombres,apellidos,correo,telefono,password_hash,activo)
    VALUES(p_tipo,p_carrera,p_cedula,p_nombres,p_apellidos,p_correo,p_telefono,p_password_hash,1);
    SET v_id=LAST_INSERT_ID();
    IF p_rol IS NOT NULL THEN INSERT INTO usuario_rol(id_usuario,id_rol,activo) VALUES(v_id,p_rol,1); END IF;
    SELECT v_id AS id_usuario;
END$$

CREATE PROCEDURE `sp_cambiar_estado_ejemplar`(IN p_id_ejemplar BIGINT,IN p_estado INT,IN p_observacion VARCHAR(255))
BEGIN
    UPDATE ejemplares SET id_estado_ejemplar=p_estado,observacion=p_observacion WHERE id_ejemplar=p_id_ejemplar;
END$$

CREATE PROCEDURE `sp_registrar_movimiento_ejemplar`(IN p_id_ejemplar BIGINT,IN p_tipo VARCHAR(20),IN p_referencia VARCHAR(120),IN p_observacion VARCHAR(255))
BEGIN
    INSERT INTO ejemplar_movimiento(id_ejemplar,tipo_movimiento,referencia,observacion)
    VALUES(p_id_ejemplar,p_tipo,p_referencia,p_observacion);
END$$
DELIMITER ;

-- ============================================================
-- PROCEDIMIENTO PARA EL RETIRO MEDIANTE QR
-- ============================================================
DROP PROCEDURE IF EXISTS `sp_confirmar_retiro_qr`;
DELIMITER $$
CREATE PROCEDURE `sp_confirmar_retiro_qr`(IN p_id_reserva BIGINT,IN p_usuario INT,IN p_vencimiento DATE)
BEGIN
    DECLARE v_usuario INT;
    DECLARE v_numero VARCHAR(30);
    DECLARE v_id_prestamo BIGINT;
    START TRANSACTION;
    SELECT id_usuario INTO v_usuario FROM reservas WHERE id_reserva=p_id_reserva AND id_estado_reserva=3 AND activo=1 FOR UPDATE;
    IF v_usuario IS NULL THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='La reserva no esta lista para retiro';
    END IF;
    SET v_numero=CONCAT('PRE-',DATE_FORMAT(NOW(),'%Y%m%d%H%i%s'),'-',LPAD(FLOOR(RAND()*90)+10,2,'0'));
    INSERT INTO prestamos(numero_prestamo,id_reserva,id_usuario,id_estado_prestamo,fecha_prestamo,fecha_vencimiento,activo)
    VALUES(v_numero,p_id_reserva,v_usuario,1,NOW(),p_vencimiento,1);
    SET v_id_prestamo=LAST_INSERT_ID();
    INSERT INTO prestamo_detalle(id_prestamo,id_ejemplar,estado,observacion)
    SELECT v_id_prestamo,id_ejemplar,'PRESTADO','Retiro confirmado mediante QR'
      FROM reserva_detalle WHERE id_reserva=p_id_reserva AND estado='RESERVADO';
    IF ROW_COUNT()=0 THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='La reserva no tiene ejemplares pendientes';
    END IF;
    UPDATE ejemplares e JOIN reserva_detalle rd ON rd.id_ejemplar=e.id_ejemplar SET e.id_estado_ejemplar=3
     WHERE rd.id_reserva=p_id_reserva AND rd.estado='RESERVADO';
    INSERT INTO ejemplar_movimiento(id_ejemplar,tipo_movimiento,referencia,observacion)
    SELECT id_ejemplar,'PRESTAMO',CONCAT('prestamo #',v_id_prestamo),'Retiro confirmado mediante QR'
      FROM reserva_detalle WHERE id_reserva=p_id_reserva AND estado='RESERVADO';
    UPDATE reserva_detalle SET estado='ENTREGADO' WHERE id_reserva=p_id_reserva AND estado='RESERVADO';
    UPDATE reservas SET id_estado_reserva=6,activo=FALSE WHERE id_reserva=p_id_reserva;
    INSERT INTO historial_reserva(id_reserva,id_estado_reserva,id_usuario,comentario,fecha_evento)
    VALUES(p_id_reserva,6,p_usuario,'Reserva convertida en préstamo mediante QR',NOW());
    UPDATE qr_reserva SET utilizado=1,fecha_utilizacion=NOW(),fecha_escaneo=NOW() WHERE id_reserva=p_id_reserva AND utilizado=0;
    INSERT INTO historial_prestamo(id_prestamo,id_estado_prestamo,id_usuario,comentario,fecha_evento)
    VALUES(v_id_prestamo,1,p_usuario,'Préstamo generado mediante QR',NOW());
    COMMIT;
    SELECT v_id_prestamo AS id_prestamo,v_numero AS numero_prestamo;
END$$
DELIMITER ;
