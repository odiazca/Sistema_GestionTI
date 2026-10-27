-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Versión del servidor:         8.4.3 - MySQL Community Server - GPL
-- SO del servidor:              Win64
-- HeidiSQL Versión:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Volcando estructura de base de datos para sys_gestion_ti
CREATE DATABASE IF NOT EXISTS `sys_gestion_ti` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `sys_gestion_ti`;

-- Volcando estructura para tabla sys_gestion_ti.actividad
CREATE TABLE IF NOT EXISTS `actividad` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int unsigned DEFAULT NULL,
  `accion` varchar(120) NOT NULL,
  `detalle` varchar(200) DEFAULT NULL,
  `icono` varchar(40) NOT NULL DEFAULT 'bi-activity',
  `color` varchar(9) NOT NULL DEFAULT '#4f46e5',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla sys_gestion_ti.actividad: ~13 rows (aproximadamente)
DELETE FROM `actividad`;
INSERT INTO `actividad` (`id`, `usuario_id`, `accion`, `detalle`, `icono`, `color`, `created_at`) VALUES
	(1, 3, 'Resolvió el ticket TCK-01228', 'Servidor de archivos sin espacio', 'bi-check-circle-fill', '#10b981', '2026-10-05 08:20:12'),
	(2, 2, 'Asignó el ticket TCK-01230', 'A: Andrea Quispe', 'bi-person-check-fill', '#4f46e5', '2026-10-05 07:57:12'),
	(3, 6, 'Creó el ticket TCK-01231', 'Impresora no imprime en red', 'bi-plus-circle-fill', '#0ea5e9', '2026-10-05 07:32:12'),
	(4, 4, 'Inició visita técnica', 'Clínica San Gabriel', 'bi-geo-alt-fill', '#f59e0b', '2026-10-05 06:32:12'),
	(5, 1, 'Renovó contrato CT-2026-004', 'Estudio Contable Valdivia', 'bi-file-earmark-check-fill', '#8b5cf6', '2026-10-05 05:32:12'),
	(6, 5, 'Registró ingreso de repuestos', 'Disco SSD 480GB x 5', 'bi-box-seam-fill', '#14b8a6', '2026-10-05 03:32:12'),
	(7, 2, 'Escaló ticket crítico', 'Alerta de temperatura en rack', 'bi-exclamation-triangle-fill', '#ef4444', '2026-10-05 01:32:12'),
	(8, 1, 'Inició sesión', '127.0.0.1', 'bi-box-arrow-in-right', '#64748b', '2026-10-05 11:34:24'),
	(9, 1, 'Cerró sesión', '127.0.0.1', 'bi-box-arrow-left', '#64748b', '2026-10-05 13:16:24'),
	(10, 1, 'Inició sesión', '127.0.0.1', 'bi-box-arrow-in-right', '#64748b', '2026-10-05 13:16:26'),
	(11, 1, 'Configuró el Asistente IA', 'gemini / gemini-3.5-flash-lite', 'bi-stars', '#7c3aed', '2026-10-05 13:20:23'),
	(12, 1, 'Configuró el Asistente IA', 'gemini / gemini-3.5-flash-lite', 'bi-stars', '#7c3aed', '2026-10-05 13:20:28'),
	(13, 1, 'Consulta al Asistente IA', 'ticket pendientes', 'bi-stars', '#7c3aed', '2026-10-05 13:20:46'),
	(14, 1, 'Cerró sesión', '127.0.0.1', 'bi-box-arrow-left', '#64748b', '2026-10-05 13:58:28'),
	(15, 1, 'Inició sesión', '127.0.0.1', 'bi-box-arrow-in-right', '#64748b', '2026-10-05 13:58:30');

-- Volcando estructura para tabla sys_gestion_ti.activos
CREATE TABLE IF NOT EXISTS `activos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `cliente_id` int unsigned NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `tipo` varchar(40) NOT NULL,
  `marca` varchar(40) DEFAULT NULL,
  `modelo` varchar(60) DEFAULT NULL,
  `serie` varchar(60) DEFAULT NULL,
  `ubicacion` varchar(100) DEFAULT NULL,
  `fecha_compra` date DEFAULT NULL,
  `garantia_hasta` date DEFAULT NULL,
  `estado` enum('operativo','en_reparacion','baja') NOT NULL DEFAULT 'operativo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `cliente_id` (`cliente_id`),
  CONSTRAINT `activos_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla sys_gestion_ti.activos: ~48 rows (aproximadamente)
DELETE FROM `activos`;
INSERT INTO `activos` (`id`, `cliente_id`, `codigo`, `tipo`, `marca`, `modelo`, `serie`, `ubicacion`, `fecha_compra`, `garantia_hasta`, `estado`) VALUES
	(1, 1, 'ACT-0001', 'Servidor', 'Dell', 'PowerEdge R450', '7BF3EA2A11', 'Gerencia', '2024-11-17', '2025-11-17', 'en_reparacion'),
	(2, 1, 'ACT-0002', 'Laptop', 'HP', 'ProBook 450 G9', 'EAF3A11DBE', 'Almacén', '2026-06-13', '2027-06-13', 'operativo'),
	(3, 1, 'ACT-0003', 'Impresora', 'Epson', 'L6270', '508F5B9E53', 'Gerencia', '2025-11-10', '2027-11-10', 'operativo'),
	(4, 1, 'ACT-0004', 'PC Escritorio', 'Lenovo', 'ThinkCentre M70', '5D27461186', 'Almacén', '2026-04-06', '2028-04-05', 'operativo'),
	(5, 1, 'ACT-0005', 'Servidor', 'Dell', 'PowerEdge R450', '8CB13BA029', 'Almacén', '2025-01-26', '2027-01-26', 'operativo'),
	(6, 1, 'ACT-0006', 'PC Escritorio', 'Lenovo', 'ThinkCentre M70', '5B7CDC9600', 'Oficina TI', '2026-05-02', '2028-05-01', 'operativo'),
	(7, 2, 'ACT-0007', 'Laptop', 'HP', 'ProBook 450 G9', '2AC5FBB019', 'Sala de servidores', '2026-02-23', '2027-02-23', 'operativo'),
	(8, 2, 'ACT-0008', 'Firewall', 'Fortinet', 'FortiGate 60F', 'C3DDB25F53', 'Recepción', '2025-09-02', '2026-09-02', 'operativo'),
	(9, 2, 'ACT-0009', 'PC Escritorio', 'Lenovo', 'ThinkCentre M70', '30D625DAD3', 'Gerencia', '2025-08-14', '2026-08-14', 'en_reparacion'),
	(10, 2, 'ACT-0010', 'Switch', 'Cisco', 'Catalyst 2960', '2BADDAA696', 'Gerencia', '2026-08-04', '2028-08-03', 'operativo'),
	(11, 2, 'ACT-0011', 'Impresora', 'Epson', 'L6270', '925F90C201', 'Oficina TI', '2024-11-18', '2026-11-18', 'operativo'),
	(12, 2, 'ACT-0012', 'Access Point', 'Ubiquiti', 'UniFi U6 Pro', 'C083722AF0', 'Oficina TI', '2025-04-05', '2027-04-05', 'operativo'),
	(13, 3, 'ACT-0013', 'Firewall', 'Fortinet', 'FortiGate 60F', '64005B1F13', 'Almacén', '2024-09-01', '2026-09-01', 'operativo'),
	(14, 3, 'ACT-0014', 'Laptop', 'Dell', 'Latitude 5440', '2786A3BA88', 'Sala de servidores', '2026-04-20', '2027-04-20', 'en_reparacion'),
	(15, 3, 'ACT-0015', 'Laptop', 'Dell', 'Latitude 5440', '527EE6FF0F', 'Sala de servidores', '2025-05-19', '2027-05-19', 'operativo'),
	(16, 3, 'ACT-0016', 'Switch', 'Cisco', 'Catalyst 2960', 'FA20E9861D', 'Recepción', '2025-04-02', '2027-04-02', 'operativo'),
	(17, 3, 'ACT-0017', 'Access Point', 'Ubiquiti', 'UniFi U6 Pro', '02928AB627', 'Almacén', '2024-12-02', '2025-12-02', 'operativo'),
	(18, 3, 'ACT-0018', 'Access Point', 'Ubiquiti', 'UniFi U6 Pro', '806149E4E5', 'Almacén', '2026-05-13', '2028-05-12', 'operativo'),
	(19, 4, 'ACT-0019', 'Switch', 'Cisco', 'Catalyst 2960', '74472DD62E', 'Recepción', '2025-10-27', '2026-10-27', 'operativo'),
	(20, 4, 'ACT-0020', 'PC Escritorio', 'Lenovo', 'ThinkCentre M70', 'C869156DB7', 'Oficina TI', '2025-11-05', '2026-11-05', 'operativo'),
	(21, 4, 'ACT-0021', 'Router', 'MikroTik', 'RB4011', 'AB30D45EC9', 'Gerencia', '2026-08-03', '2027-08-03', 'operativo'),
	(22, 4, 'ACT-0022', 'Servidor', 'Dell', 'PowerEdge R450', '9577915412', 'Oficina TI', '2025-10-16', '2026-10-16', 'operativo'),
	(23, 4, 'ACT-0023', 'Access Point', 'Ubiquiti', 'UniFi U6 Pro', '20741A64C8', 'Sala de servidores', '2026-04-25', '2027-04-25', 'operativo'),
	(24, 4, 'ACT-0024', 'Laptop', 'HP', 'ProBook 450 G9', '97FE7F0399', 'Recepción', '2025-01-26', '2026-01-26', 'operativo'),
	(25, 5, 'ACT-0025', 'Servidor', 'Dell', 'PowerEdge R450', 'E5153BCF4A', 'Sala de servidores', '2024-12-26', '2025-12-26', 'operativo'),
	(26, 5, 'ACT-0026', 'Laptop', 'HP', 'ProBook 450 G9', 'B1CF9681DF', 'Almacén', '2025-03-25', '2026-03-25', 'en_reparacion'),
	(27, 5, 'ACT-0027', 'Servidor', 'Dell', 'PowerEdge R450', 'E3045BD705', 'Almacén', '2026-01-05', '2028-01-05', 'operativo'),
	(28, 5, 'ACT-0028', 'Switch', 'Cisco', 'Catalyst 2960', 'F0EDC1340A', 'Oficina TI', '2024-10-01', '2026-10-01', 'en_reparacion'),
	(29, 5, 'ACT-0029', 'PC Escritorio', 'Lenovo', 'ThinkCentre M70', '126C5F4680', 'Oficina TI', '2026-07-01', '2028-06-30', 'operativo'),
	(30, 5, 'ACT-0030', 'Impresora', 'Epson', 'L6270', '9DE2D17649', 'Sala de servidores', '2025-04-18', '2026-04-18', 'operativo'),
	(31, 6, 'ACT-0031', 'Router', 'MikroTik', 'RB4011', '624DDABC8D', 'Oficina TI', '2025-02-28', '2026-02-28', 'en_reparacion'),
	(32, 6, 'ACT-0032', 'Router', 'MikroTik', 'RB4011', '886055E9E7', 'Oficina TI', '2025-01-09', '2027-01-09', 'operativo'),
	(33, 6, 'ACT-0033', 'Laptop', 'HP', 'ProBook 450 G9', '7E96925442', 'Oficina TI', '2024-09-07', '2026-09-07', 'operativo'),
	(34, 6, 'ACT-0034', 'Router', 'MikroTik', 'RB4011', '35193ED7EC', 'Oficina TI', '2025-10-13', '2027-10-13', 'operativo'),
	(35, 6, 'ACT-0035', 'Laptop', 'HP', 'ProBook 450 G9', '08B7BBC3A1', 'Almacén', '2024-05-27', '2025-05-27', 'operativo'),
	(36, 6, 'ACT-0036', 'Laptop', 'Dell', 'Latitude 5440', 'B54A34380B', 'Sala de servidores', '2024-05-26', '2026-05-26', 'operativo'),
	(37, 7, 'ACT-0037', 'PC Escritorio', 'Lenovo', 'ThinkCentre M70', '2FFB2F78EC', 'Oficina TI', '2025-04-10', '2026-04-10', 'en_reparacion'),
	(38, 7, 'ACT-0038', 'Firewall', 'Fortinet', 'FortiGate 60F', 'B0DB7E59C2', 'Sala de servidores', '2025-04-03', '2026-04-03', 'operativo'),
	(39, 7, 'ACT-0039', 'Firewall', 'Fortinet', 'FortiGate 60F', '098639D302', 'Gerencia', '2025-09-23', '2026-09-23', 'operativo'),
	(40, 7, 'ACT-0040', 'Switch', 'Cisco', 'Catalyst 2960', 'D75AB56235', 'Gerencia', '2025-12-20', '2026-12-20', 'operativo'),
	(41, 7, 'ACT-0041', 'Laptop', 'HP', 'ProBook 450 G9', 'A938081397', 'Almacén', '2024-08-12', '2026-08-12', 'operativo'),
	(42, 7, 'ACT-0042', 'Switch', 'Cisco', 'Catalyst 2960', 'EDD2040051', 'Almacén', '2024-11-27', '2025-11-27', 'en_reparacion'),
	(43, 8, 'ACT-0043', 'Laptop', 'HP', 'ProBook 450 G9', 'DE71B8498B', 'Gerencia', '2024-05-03', '2026-05-03', 'operativo'),
	(44, 8, 'ACT-0044', 'Laptop', 'Dell', 'Latitude 5440', '6EE362EFE9', 'Almacén', '2025-07-29', '2027-07-29', 'operativo'),
	(45, 8, 'ACT-0045', 'Laptop', 'HP', 'ProBook 450 G9', 'E31CB99DF6', 'Oficina TI', '2025-05-20', '2027-05-20', 'operativo'),
	(46, 8, 'ACT-0046', 'Firewall', 'Fortinet', 'FortiGate 60F', 'D9EE229097', 'Oficina TI', '2026-02-01', '2027-02-01', 'operativo'),
	(47, 8, 'ACT-0047', 'Impresora', 'Epson', 'L6270', '7EDBE1B577', 'Oficina TI', '2025-01-30', '2026-01-30', 'operativo'),
	(48, 8, 'ACT-0048', 'Router', 'MikroTik', 'RB4011', '0366652995', 'Sala de servidores', '2026-05-29', '2028-05-28', 'operativo');

-- Volcando estructura para tabla sys_gestion_ti.categorias
CREATE TABLE IF NOT EXISTS `categorias` (
  `id` tinyint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(60) NOT NULL,
  `icono` varchar(40) NOT NULL DEFAULT 'bi-tag',
  `color` varchar(9) NOT NULL DEFAULT '#4f46e5',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla sys_gestion_ti.categorias: ~8 rows (aproximadamente)
DELETE FROM `categorias`;
INSERT INTO `categorias` (`id`, `nombre`, `icono`, `color`) VALUES
	(1, 'Hardware', 'bi-cpu', '#6366f1'),
	(2, 'Software', 'bi-window-stack', '#0ea5e9'),
	(3, 'Redes y Conectividad', 'bi-wifi', '#10b981'),
	(4, 'Servidores', 'bi-hdd-rack', '#f59e0b'),
	(5, 'Correo y Cuentas', 'bi-envelope-at', '#ec4899'),
	(6, 'Impresoras', 'bi-printer', '#8b5cf6'),
	(7, 'Seguridad', 'bi-shield-lock', '#ef4444'),
	(8, 'Telefonía IP', 'bi-telephone', '#14b8a6');

-- Volcando estructura para tabla sys_gestion_ti.clientes
CREATE TABLE IF NOT EXISTS `clientes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `razon_social` varchar(150) NOT NULL,
  `ruc` varchar(15) DEFAULT NULL,
  `rubro` varchar(80) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `telefono` varchar(25) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `contacto` varchar(100) DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla sys_gestion_ti.clientes: ~8 rows (aproximadamente)
DELETE FROM `clientes`;
INSERT INTO `clientes` (`id`, `razon_social`, `ruc`, `rubro`, `direccion`, `telefono`, `email`, `contacto`, `estado`, `created_at`) VALUES
	(1, 'Clínica San Gabriel', '20512345671', 'Salud', 'Av. Javier Prado 1420, San Isidro', '01 2082305', 'ti@cliente1.pe', 'Dr. Paredes', 1, '2026-10-05 13:32:11'),
	(2, 'Distribuidora Andina S.A.C.', '20498765432', 'Comercio', 'Jr. Cusco 355, Cercado de Lima', '01 6560774', 'ti@cliente2.pe', 'Ing. Rojas', 1, '2026-10-05 13:32:11'),
	(3, 'Colegio Santa Rosa', '20456781239', 'Educación', 'Av. La Marina 2210, San Miguel', '01 5871898', 'ti@cliente3.pe', 'Prof. Linares', 1, '2026-10-05 13:32:11'),
	(4, 'Estudio Contable Valdivia', '20123459876', 'Servicios', 'Calle Los Pinos 128, Miraflores', '01 2273336', 'ti@cliente4.pe', 'CPC Valdivia', 1, '2026-10-05 13:32:11'),
	(5, 'Constructora Pacífico', '20567891230', 'Construcción', 'Av. Argentina 4580, Callao', '01 2617421', 'ti@cliente5.pe', 'Ing. Huamán', 1, '2026-10-05 13:32:11'),
	(6, 'Hotel Mirador del Sol', '20345678901', 'Turismo', 'Malecón Cisneros 810, Miraflores', '01 3491917', 'ti@cliente6.pe', 'Sr. Bendezú', 1, '2026-10-05 13:32:11'),
	(7, 'Transportes Inca Express', '20678912345', 'Transporte', 'Av. Venezuela 1890, Breña', '01 5818397', 'ti@cliente7.pe', 'Sra. Cárdenas', 1, '2026-10-05 13:32:11'),
	(8, 'Farmacias Vida Plus', '20789123456', 'Retail', 'Av. Brasil 1250, Jesús María', '01 2691060', 'ti@cliente8.pe', 'Q.F. Medina', 1, '2026-10-05 13:32:11');

-- Volcando estructura para tabla sys_gestion_ti.configuracion
CREATE TABLE IF NOT EXISTS `configuracion` (
  `clave` varchar(60) NOT NULL,
  `valor` mediumtext,
  PRIMARY KEY (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla sys_gestion_ti.configuracion: ~5 rows (aproximadamente)
DELETE FROM `configuracion`;
INSERT INTO `configuracion` (`clave`, `valor`) VALUES
	('ia_clave', 'g:bbVZLkvhxeSIz0aoEpZbanBXD8A+U2Gsa5dkMPh7f83o6zWL/ZRqruPkqSCTMIqoK3OdV6R30+d2bP6LrHx2I5+QAwebw2Mz5WdeAiknkY5n'),
	('ia_modelo', 'gemini-3.5-flash-lite'),
	('ia_proveedor', 'gemini'),
	('ia_resumen', '1'),
	('ia_url', 'https://generativelanguage.googleapis.com/v1beta/openai');

-- Volcando estructura para tabla sys_gestion_ti.contratos
CREATE TABLE IF NOT EXISTS `contratos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `cliente_id` int unsigned NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `plan` enum('Básico','Business','Premium') NOT NULL DEFAULT 'Básico',
  `sla_respuesta_horas` smallint NOT NULL DEFAULT '8',
  `sla_resolucion_horas` smallint NOT NULL DEFAULT '48',
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `monto_mensual` decimal(10,2) NOT NULL DEFAULT '0.00',
  `estado` enum('vigente','por_vencer','vencido') NOT NULL DEFAULT 'vigente',
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `cliente_id` (`cliente_id`),
  CONSTRAINT `contratos_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla sys_gestion_ti.contratos: ~8 rows (aproximadamente)
DELETE FROM `contratos`;
INSERT INTO `contratos` (`id`, `cliente_id`, `codigo`, `plan`, `sla_respuesta_horas`, `sla_resolucion_horas`, `fecha_inicio`, `fecha_fin`, `monto_mensual`, `estado`) VALUES
	(1, 1, 'CT-2026-001', 'Premium', 2, 24, '2025-12-09', '2026-10-30', 1800.00, 'por_vencer'),
	(2, 2, 'CT-2026-002', 'Business', 4, 36, '2025-12-09', '2026-11-09', 950.00, 'por_vencer'),
	(3, 3, 'CT-2026-003', 'Básico', 8, 72, '2025-12-09', '2027-03-24', 420.00, 'vigente'),
	(4, 4, 'CT-2026-004', 'Premium', 2, 24, '2025-12-09', '2027-05-03', 1800.00, 'vigente'),
	(5, 5, 'CT-2026-005', 'Business', 4, 36, '2025-12-09', '2027-06-12', 950.00, 'vigente'),
	(6, 6, 'CT-2026-006', 'Básico', 8, 72, '2025-12-09', '2027-07-22', 420.00, 'vigente'),
	(7, 7, 'CT-2026-007', 'Premium', 2, 24, '2025-12-09', '2027-08-31', 1800.00, 'vigente'),
	(8, 8, 'CT-2026-008', 'Business', 4, 36, '2025-12-09', '2027-10-10', 950.00, 'vigente');

-- Volcando estructura para tabla sys_gestion_ti.crm_actividades
CREATE TABLE IF NOT EXISTS `crm_actividades` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tipo` enum('llamada','reunion','correo','tarea','visita','nota') NOT NULL DEFAULT 'tarea',
  `asunto` varchar(180) NOT NULL,
  `detalle` text,
  `fecha` datetime DEFAULT NULL,
  `completada` tinyint(1) NOT NULL DEFAULT '0',
  `completada_at` datetime DEFAULT NULL,
  `resultado` text,
  `auto` tinyint(1) NOT NULL DEFAULT '0',
  `cliente_id` int unsigned DEFAULT NULL,
  `lead_id` int unsigned DEFAULT NULL,
  `oportunidad_id` int unsigned DEFAULT NULL,
  `responsable_id` int unsigned DEFAULT NULL,
  `creado_por` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_act_resp` (`responsable_id`,`completada`,`fecha`),
  KEY `idx_act_cli` (`cliente_id`),
  KEY `idx_act_lead` (`lead_id`),
  KEY `idx_act_op` (`oportunidad_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla sys_gestion_ti.crm_actividades: ~15 rows (aproximadamente)
DELETE FROM `crm_actividades`;
INSERT INTO `crm_actividades` (`id`, `tipo`, `asunto`, `detalle`, `fecha`, `completada`, `completada_at`, `resultado`, `auto`, `cliente_id`, `lead_id`, `oportunidad_id`, `responsable_id`, `creado_por`, `created_at`) VALUES
	(1, 'llamada', 'Llamar para confirmar interés en propuesta', NULL, '2026-10-03 09:00:00', 0, NULL, NULL, 0, NULL, 1, NULL, 1, 1, '2026-10-05 13:13:47'),
	(2, 'reunion', 'Reunión de presentación de propuesta', NULL, '2026-10-05 10:30:00', 0, NULL, NULL, 0, 2, NULL, 2, 2, 1, '2026-10-05 13:13:47'),
	(3, 'correo', 'Enviar cotización actualizada', NULL, '2026-10-05 11:00:00', 0, NULL, NULL, 0, 5, NULL, 5, 7, 1, '2026-10-05 13:13:47'),
	(4, 'tarea', 'Preparar demo de monitoreo 24/7', NULL, '2026-10-06 12:30:00', 0, NULL, NULL, 0, 7, NULL, 7, 1, 1, '2026-10-05 13:13:47'),
	(5, 'visita', 'Visita técnica para levantamiento de cableado', NULL, '2026-10-07 13:00:00', 0, NULL, NULL, 0, 3, NULL, 3, 2, 1, '2026-10-05 13:13:47'),
	(6, 'llamada', 'Seguimiento a renovación de contrato', NULL, '2026-10-04 14:30:00', 0, NULL, NULL, 0, 1, NULL, 1, 7, 1, '2026-10-05 13:13:47'),
	(7, 'reunion', 'Negociación final de condiciones', NULL, '2026-10-08 15:00:00', 0, NULL, NULL, 0, 5, NULL, 5, 1, 1, '2026-10-05 13:13:47'),
	(8, 'tarea', 'Revisar propuesta económica con gerencia', NULL, '2026-10-10 16:30:00', 0, NULL, NULL, 0, 4, NULL, 4, 2, 1, '2026-10-05 13:13:47'),
	(9, 'llamada', 'Primer contacto con lead del evento', NULL, '2026-10-05 09:00:00', 0, NULL, NULL, 0, NULL, 3, NULL, 7, 1, '2026-10-05 13:13:47'),
	(10, 'correo', 'Enviar brochure de servicios', NULL, '2026-10-06 10:30:00', 0, NULL, NULL, 0, NULL, 5, NULL, 1, 1, '2026-10-05 13:13:47'),
	(11, 'llamada', 'Calificar necesidades del lead', NULL, '2026-10-02 11:00:00', 0, NULL, NULL, 0, NULL, 2, NULL, 2, 1, '2026-10-05 13:13:47'),
	(12, 'reunion', 'Reunión de kick-off', NULL, '2026-09-25 12:30:00', 1, '2026-09-25 12:30:00', 'Reunión realizada. Cliente interesado, se acordó enviar propuesta.', 0, 2, NULL, 10, 7, 1, '2026-10-05 13:13:47'),
	(13, 'nota', 'Oportunidad ganada: Mantenimiento preventivo anual', NULL, '2026-09-25 13:13:47', 1, '2026-09-07 13:13:47', NULL, 1, 2, NULL, 10, NULL, 1, '2026-10-05 13:13:47'),
	(14, 'nota', 'Oportunidad ganada: Servidor de archivos NAS', NULL, '2026-09-25 13:13:47', 1, '2026-08-24 13:13:47', NULL, 1, 3, NULL, 11, NULL, 2, '2026-10-05 13:13:47'),
	(15, 'nota', 'Oportunidad ganada: Contrato Básico soporte remoto', NULL, '2026-09-25 13:13:47', 1, '2026-07-18 13:13:47', NULL, 1, 4, NULL, 12, NULL, 7, '2026-10-05 13:13:47');

-- Volcando estructura para tabla sys_gestion_ti.crm_contactos
CREATE TABLE IF NOT EXISTS `crm_contactos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `cliente_id` int unsigned NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `cargo` varchar(80) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `telefono` varchar(25) DEFAULT NULL,
  `principal` tinyint(1) NOT NULL DEFAULT '0',
  `notas` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cc_cliente` (`cliente_id`),
  CONSTRAINT `crm_contactos_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla sys_gestion_ti.crm_contactos: ~16 rows (aproximadamente)
DELETE FROM `crm_contactos`;
INSERT INTO `crm_contactos` (`id`, `cliente_id`, `nombre`, `cargo`, `email`, `telefono`, `principal`, `notas`, `created_at`) VALUES
	(1, 1, 'Dr. Paredes', 'Contacto principal', 'ti@cliente1.pe', '01 2082305', 1, NULL, '2026-10-05 18:13:47'),
	(2, 1, 'Roberto Salas', 'Gerente General', 'roberto.salas@clnica.pe', '952082305', 0, NULL, '2026-10-05 18:13:47'),
	(3, 2, 'Ing. Rojas', 'Contacto principal', 'ti@cliente2.pe', '01 6560774', 1, NULL, '2026-10-05 18:13:47'),
	(4, 2, 'Patricia Luna', 'Jefe de TI', 'patricia.luna@distribuidora.pe', '962560774', 0, NULL, '2026-10-05 18:13:47'),
	(5, 3, 'Prof. Linares', 'Contacto principal', 'ti@cliente3.pe', '01 5871898', 1, NULL, '2026-10-05 18:13:47'),
	(6, 3, 'Diego Paredes', 'Administrador', 'diego.paredes@colegio.pe', '973871898', 0, NULL, '2026-10-05 18:13:47'),
	(7, 4, 'CPC Valdivia', 'Contacto principal', 'ti@cliente4.pe', '01 2273336', 1, NULL, '2026-10-05 18:13:47'),
	(8, 4, 'Carmen Rojas', 'Gerente de Operaciones', 'carmen.rojas@estudio.pe', '988273336', 0, NULL, '2026-10-05 18:13:47'),
	(9, 5, 'Ing. Huamán', 'Contacto principal', 'ti@cliente5.pe', '01 2617421', 1, NULL, '2026-10-05 18:13:47'),
	(10, 5, 'Fernando Díaz', 'Contador', 'fernando.d\'iaz@constructora.pe', '964617421', 0, NULL, '2026-10-05 18:13:47'),
	(11, 6, 'Sr. Bendezú', 'Contacto principal', 'ti@cliente6.pe', '01 3491917', 1, NULL, '2026-10-05 18:13:47'),
	(12, 6, 'Silvia Mora', 'Gerente General', 'silvia.mora@hotel.pe', '995491917', 0, NULL, '2026-10-05 18:13:47'),
	(13, 7, 'Sra. Cárdenas', 'Contacto principal', 'ti@cliente7.pe', '01 5818397', 1, NULL, '2026-10-05 18:13:47'),
	(14, 7, 'Héctor Vega', 'Jefe de TI', 'h\'ector.vega@transportes.pe', '931818397', 0, NULL, '2026-10-05 18:13:47'),
	(15, 8, 'Q.F. Medina', 'Contacto principal', 'ti@cliente8.pe', '01 2691060', 1, NULL, '2026-10-05 18:13:47'),
	(16, 8, 'Lorena Campos', 'Administrador', 'lorena.campos@farmacias.pe', '976691060', 0, NULL, '2026-10-05 18:13:47');

-- Volcando estructura para tabla sys_gestion_ti.crm_cotizaciones
CREATE TABLE IF NOT EXISTS `crm_cotizaciones` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) NOT NULL,
  `cliente_id` int unsigned NOT NULL,
  `oportunidad_id` int unsigned DEFAULT NULL,
  `contacto_id` int unsigned DEFAULT NULL,
  `fecha` date NOT NULL,
  `valida_hasta` date NOT NULL,
  `estado` enum('borrador','enviada','aprobada','rechazada','vencida') NOT NULL DEFAULT 'borrador',
  `descuento_pct` decimal(5,2) NOT NULL DEFAULT '0.00',
  `igv_pct` decimal(5,2) NOT NULL DEFAULT '18.00',
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `descuento` decimal(12,2) NOT NULL DEFAULT '0.00',
  `igv` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `condiciones` text,
  `notas` varchar(255) DEFAULT NULL,
  `creado_por` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `idx_cot_estado` (`estado`),
  KEY `cliente_id` (`cliente_id`),
  CONSTRAINT `crm_cotizaciones_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla sys_gestion_ti.crm_cotizaciones: ~5 rows (aproximadamente)
DELETE FROM `crm_cotizaciones`;
INSERT INTO `crm_cotizaciones` (`id`, `codigo`, `cliente_id`, `oportunidad_id`, `contacto_id`, `fecha`, `valida_hasta`, `estado`, `descuento_pct`, `igv_pct`, `subtotal`, `descuento`, `igv`, `total`, `condiciones`, `notas`, `creado_por`, `created_at`) VALUES
	(1, 'COT-0001', 5, 5, 9, '2026-10-02', '2026-10-17', 'enviada', 0.00, 18.00, 24975.00, 0.00, 4495.50, 29470.50, '• Precios en soles (S/). Incluyen IGV.\n• Forma de pago: 50% al aceptar, 50% contra entrega.\n• Plazo de entrega/implementación: según cronograma acordado.\n• Garantía de 12 meses en equipos y 30 días en servicios.', NULL, 1, '2026-10-05 13:13:47'),
	(2, 'COT-0002', 2, 2, 3, '2026-09-30', '2026-10-15', 'enviada', 0.00, 18.00, 35400.00, 0.00, 6372.00, 41772.00, '• Precios en soles (S/). Incluyen IGV.\n• Forma de pago: 50% al aceptar, 50% contra entrega.\n• Plazo de entrega/implementación: según cronograma acordado.\n• Garantía de 12 meses en equipos y 30 días en servicios.', NULL, 1, '2026-10-05 13:13:47'),
	(3, 'COT-0003', 4, 4, 7, '2026-10-05', '2026-10-20', 'borrador', 0.00, 18.00, 11400.00, 0.00, 2052.00, 13452.00, '• Precios en soles (S/). Incluyen IGV.\n• Forma de pago: 50% al aceptar, 50% contra entrega.\n• Plazo de entrega/implementación: según cronograma acordado.\n• Garantía de 12 meses en equipos y 30 días en servicios.', NULL, 1, '2026-10-05 13:13:47'),
	(4, 'COT-0004', 3, 11, 5, '2026-09-05', '2026-09-20', 'aprobada', 0.00, 18.00, 11700.00, 0.00, 2106.00, 13806.00, '• Precios en soles (S/). Incluyen IGV.\n• Forma de pago: 50% al aceptar, 50% contra entrega.\n• Plazo de entrega/implementación: según cronograma acordado.\n• Garantía de 12 meses en equipos y 30 días en servicios.', NULL, 1, '2026-10-05 13:13:47'),
	(5, 'COT-0005', 5, 13, 9, '2026-09-15', '2026-09-30', 'rechazada', 0.00, 18.00, 5500.00, 0.00, 990.00, 6490.00, '• Precios en soles (S/). Incluyen IGV.\n• Forma de pago: 50% al aceptar, 50% contra entrega.\n• Plazo de entrega/implementación: según cronograma acordado.\n• Garantía de 12 meses en equipos y 30 días en servicios.', NULL, 1, '2026-10-05 13:13:47');

-- Volcando estructura para tabla sys_gestion_ti.crm_cotizacion_items
CREATE TABLE IF NOT EXISTS `crm_cotizacion_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `cotizacion_id` int unsigned NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL DEFAULT '1.00',
  `precio_unit` decimal(12,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `cotizacion_id` (`cotizacion_id`),
  CONSTRAINT `crm_cotizacion_items_ibfk_1` FOREIGN KEY (`cotizacion_id`) REFERENCES `crm_cotizaciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla sys_gestion_ti.crm_cotizacion_items: ~11 rows (aproximadamente)
DELETE FROM `crm_cotizacion_items`;
INSERT INTO `crm_cotizacion_items` (`id`, `cotizacion_id`, `descripcion`, `cantidad`, `precio_unit`) VALUES
	(1, 1, 'Laptop corporativa i7 16GB 512GB SSD', 15.00, 1480.00),
	(2, 1, 'Licencia Microsoft 365 Business (anual)', 15.00, 140.00),
	(3, 1, 'Configuración e imagen corporativa', 15.00, 45.00),
	(4, 2, 'Migración de servidores a nube híbrida', 1.00, 24000.00),
	(5, 2, 'Licenciamiento cloud (12 meses)', 1.00, 9600.00),
	(6, 2, 'Capacitación al personal TI', 1.00, 1800.00),
	(7, 3, 'Plan Business · soporte mensual (12 meses)', 12.00, 950.00),
	(8, 4, 'NAS 4 bahías 16 TB', 1.00, 7800.00),
	(9, 4, 'Instalación y configuración', 1.00, 1500.00),
	(10, 4, 'Backup remoto (12 meses)', 1.00, 2400.00),
	(11, 5, 'Auditoría de red y licencias', 1.00, 5500.00);

-- Volcando estructura para tabla sys_gestion_ti.crm_etapas
CREATE TABLE IF NOT EXISTS `crm_etapas` (
  `id` tinyint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(40) NOT NULL,
  `orden` tinyint NOT NULL DEFAULT '0',
  `probabilidad` tinyint NOT NULL DEFAULT '0',
  `color` varchar(9) NOT NULL DEFAULT '#4f46e5',
  `tipo` enum('abierta','ganada','perdida') NOT NULL DEFAULT 'abierta',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla sys_gestion_ti.crm_etapas: ~6 rows (aproximadamente)
DELETE FROM `crm_etapas`;
INSERT INTO `crm_etapas` (`id`, `nombre`, `orden`, `probabilidad`, `color`, `tipo`) VALUES
	(1, 'Prospección', 1, 10, '#64748b', 'abierta'),
	(2, 'Calificación', 2, 25, '#0ea5e9', 'abierta'),
	(3, 'Propuesta', 3, 50, '#8b5cf6', 'abierta'),
	(4, 'Negociación', 4, 75, '#f59e0b', 'abierta'),
	(5, 'Ganada', 5, 100, '#10b981', 'ganada'),
	(6, 'Perdida', 6, 0, '#ef4444', 'perdida');

-- Volcando estructura para tabla sys_gestion_ti.crm_leads
CREATE TABLE IF NOT EXISTS `crm_leads` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) NOT NULL,
  `empresa` varchar(150) NOT NULL,
  `contacto` varchar(100) DEFAULT NULL,
  `cargo` varchar(80) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `telefono` varchar(25) DEFAULT NULL,
  `rubro` varchar(80) DEFAULT NULL,
  `origen` enum('web','referido','llamada','evento','redes','correo','otro') NOT NULL DEFAULT 'web',
  `interes` varchar(200) DEFAULT NULL,
  `estado` enum('nuevo','contactado','calificado','descartado','convertido') NOT NULL DEFAULT 'nuevo',
  `score` tinyint unsigned NOT NULL DEFAULT '0',
  `responsable_id` int unsigned DEFAULT NULL,
  `notas` text,
  `motivo_descarte` varchar(200) DEFAULT NULL,
  `cliente_id` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `idx_lead_estado` (`estado`),
  KEY `idx_lead_resp` (`responsable_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla sys_gestion_ti.crm_leads: ~8 rows (aproximadamente)
DELETE FROM `crm_leads`;
INSERT INTO `crm_leads` (`id`, `codigo`, `empresa`, `contacto`, `cargo`, `email`, `telefono`, `rubro`, `origen`, `interes`, `estado`, `score`, `responsable_id`, `notas`, `motivo_descarte`, `cliente_id`, `created_at`, `updated_at`) VALUES
	(1, 'LD-0001', 'Clínica Santa Elena', 'Dra. Rocío Vidal', 'Directora Médica', 'rvidal@santaelena.pe', '987654321', 'Salud', 'referido', 'Soporte 24/7 y migración de historias clínicas', 'calificado', 95, 1, NULL, NULL, NULL, '2026-10-02 08:13:47', '2026-10-05 13:13:47'),
	(2, 'LD-0002', 'Distribuidora Andina SAC', 'Marco Tello', 'Gerente de Logística', 'mtello@andina.pe', '976543210', 'Comercio', 'web', 'Cotización de red WiFi para almacén', 'contactado', 70, 2, NULL, NULL, NULL, '2026-09-30 09:13:47', '2026-10-05 13:13:47'),
	(3, 'LD-0003', 'Colegio Nuevo Horizonte', 'Ana Beltrán', 'Directora', 'direccion@nuevohorizonte.edu.pe', '965432109', 'Educación', 'evento', 'Laboratorio de cómputo 30 equipos', 'nuevo', 65, 7, NULL, NULL, NULL, '2026-10-04 05:13:47', '2026-10-05 13:13:47'),
	(4, 'LD-0004', 'Transportes Rápido Sur', 'Iván Cabrera', 'Jefe de TI', 'icabrera@rapidosur.pe', '954321098', 'Transporte', 'llamada', 'Mantenimiento preventivo de flota de laptops', 'contactado', 70, 1, NULL, NULL, NULL, '2026-09-27 07:13:47', '2026-10-05 13:13:47'),
	(5, 'LD-0005', 'Hotel Mirador del Valle', 'Sonia Prado', 'Administradora', 'sprado@miradorvalle.pe', '943210987', 'Turismo', 'redes', 'Cableado estructurado y WiFi para huéspedes', 'nuevo', 40, 2, NULL, NULL, NULL, '2026-10-05 08:13:47', '2026-10-05 13:13:47'),
	(6, 'LD-0006', 'Constructora Pilar', 'Raúl Gamarra', 'Gerente General', 'rgamarra@pilar.pe', '932109876', 'Construcción', 'referido', 'Servidor de archivos y respaldo en la nube', 'calificado', 95, 7, NULL, NULL, NULL, '2026-09-24 08:13:47', '2026-10-05 13:13:47'),
	(7, 'LD-0007', 'Bodega El Roble', 'Julia Meza', 'Propietaria', 'julia@elroble.pe', '921098765', 'Retail', 'correo', 'Punto de venta y red básica', 'descartado', 55, 1, NULL, 'Ya cuenta con proveedor de TI', NULL, '2026-09-15 10:13:47', '2026-10-05 13:13:47'),
	(8, 'LD-0008', 'Notaría Villanueva', 'Dr. Pablo Villanueva', 'Notario', 'pvillanueva@notaria.pe', '910987654', 'Servicios', 'web', 'Soporte mensual y ciberseguridad', 'nuevo', 60, 2, NULL, NULL, NULL, '2026-10-03 06:13:47', '2026-10-05 13:13:47');

-- Volcando estructura para tabla sys_gestion_ti.crm_oportunidades
CREATE TABLE IF NOT EXISTS `crm_oportunidades` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) NOT NULL,
  `titulo` varchar(180) NOT NULL,
  `cliente_id` int unsigned NOT NULL,
  `contacto_id` int unsigned DEFAULT NULL,
  `lead_id` int unsigned DEFAULT NULL,
  `etapa_id` tinyint unsigned NOT NULL,
  `tipo` enum('contrato','renovacion','proyecto','equipos','servicio') NOT NULL DEFAULT 'contrato',
  `valor` decimal(12,2) NOT NULL DEFAULT '0.00',
  `probabilidad` tinyint unsigned NOT NULL DEFAULT '10',
  `fecha_cierre_estimada` date DEFAULT NULL,
  `fecha_cierre_real` date DEFAULT NULL,
  `responsable_id` int unsigned DEFAULT NULL,
  `origen` varchar(20) DEFAULT NULL,
  `estado` enum('abierta','ganada','perdida') NOT NULL DEFAULT 'abierta',
  `motivo_perdida` varchar(200) DEFAULT NULL,
  `contrato_id` int unsigned DEFAULT NULL,
  `descripcion` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `idx_op_estado` (`estado`),
  KEY `idx_op_etapa` (`etapa_id`),
  KEY `idx_op_resp` (`responsable_id`),
  KEY `cliente_id` (`cliente_id`),
  CONSTRAINT `crm_oportunidades_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  CONSTRAINT `crm_oportunidades_ibfk_2` FOREIGN KEY (`etapa_id`) REFERENCES `crm_etapas` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla sys_gestion_ti.crm_oportunidades: ~14 rows (aproximadamente)
DELETE FROM `crm_oportunidades`;
INSERT INTO `crm_oportunidades` (`id`, `codigo`, `titulo`, `cliente_id`, `contacto_id`, `lead_id`, `etapa_id`, `tipo`, `valor`, `probabilidad`, `fecha_cierre_estimada`, `fecha_cierre_real`, `responsable_id`, `origen`, `estado`, `motivo_perdida`, `contrato_id`, `descripcion`, `created_at`, `updated_at`) VALUES
	(1, 'OP-0001', 'Renovación contrato Premium · 12 meses', 1, 1, NULL, 4, 'renovacion', 21600.00, 75, '2026-10-17', NULL, 1, 'referido', 'abierta', NULL, NULL, 'Oportunidad generada a partir de conversaciones con el cliente.', '2026-08-14 13:13:47', '2026-10-05 13:13:47'),
	(2, 'OP-0002', 'Migración de servidores a la nube', 2, 3, NULL, 3, 'proyecto', 38500.00, 50, '2026-10-30', NULL, 2, 'referido', 'abierta', NULL, NULL, 'Oportunidad generada a partir de conversaciones con el cliente.', '2026-08-03 13:13:47', '2026-10-05 13:13:47'),
	(3, 'OP-0003', 'Cableado estructurado sede nueva', 3, 5, NULL, 2, 'proyecto', 14200.00, 25, '2026-11-14', NULL, 7, 'web', 'abierta', NULL, NULL, 'Oportunidad generada a partir de conversaciones con el cliente.', '2026-08-05 13:13:47', '2026-10-05 13:13:47'),
	(4, 'OP-0004', 'Contrato Business · soporte mensual', 4, 7, NULL, 3, 'contrato', 11400.00, 50, '2026-10-23', NULL, 1, 'llamada', 'abierta', NULL, NULL, 'Oportunidad generada a partir de conversaciones con el cliente.', '2026-08-20 13:13:47', '2026-10-05 13:13:47'),
	(5, 'OP-0005', 'Venta de 15 laptops corporativas', 5, 9, NULL, 4, 'equipos', 27800.00, 75, '2026-10-12', NULL, 2, 'evento', 'abierta', NULL, NULL, 'Oportunidad generada a partir de conversaciones con el cliente.', '2026-08-21 13:13:47', '2026-10-05 13:13:47'),
	(6, 'OP-0006', 'Upgrade de seguridad perimetral', 6, 11, NULL, 1, 'proyecto', 16900.00, 10, '2026-11-29', NULL, 7, 'web', 'abierta', NULL, NULL, 'Oportunidad generada a partir de conversaciones con el cliente.', '2026-07-13 13:13:47', '2026-10-05 13:13:47'),
	(7, 'OP-0007', 'Contrato Premium 24/7', 7, 13, NULL, 2, 'contrato', 21600.00, 25, '2026-11-04', NULL, 1, 'redes', 'abierta', NULL, NULL, 'Oportunidad generada a partir de conversaciones con el cliente.', '2026-08-04 13:13:47', '2026-10-05 13:13:47'),
	(8, 'OP-0008', 'Renovación contrato Business', 8, 15, NULL, 3, 'renovacion', 11400.00, 50, '2026-10-15', NULL, 2, 'referido', 'abierta', NULL, NULL, 'Oportunidad generada a partir de conversaciones con el cliente.', '2026-08-24 13:13:47', '2026-10-05 13:13:47'),
	(9, 'OP-0009', 'Implementación de telefonía IP', 1, 1, NULL, 1, 'proyecto', 9800.00, 10, '2026-12-04', NULL, 7, 'correo', 'abierta', NULL, NULL, 'Oportunidad generada a partir de conversaciones con el cliente.', '2026-07-08 13:13:47', '2026-10-05 13:13:47'),
	(10, 'OP-0010', 'Mantenimiento preventivo anual', 2, 3, NULL, 5, 'servicio', 8400.00, 100, '2026-09-23', '2026-09-23', 1, 'llamada', 'ganada', NULL, NULL, 'Oportunidad generada a partir de conversaciones con el cliente.', '2026-09-07 13:13:47', '2026-10-05 13:13:47'),
	(11, 'OP-0011', 'Servidor de archivos NAS', 3, 5, NULL, 5, 'equipos', 12600.00, 100, '2026-09-10', '2026-09-10', 2, 'web', 'ganada', NULL, NULL, 'Oportunidad generada a partir de conversaciones con el cliente.', '2026-08-24 13:13:47', '2026-10-05 13:13:47'),
	(12, 'OP-0012', 'Contrato Básico soporte remoto', 4, 7, NULL, 5, 'contrato', 5040.00, 100, '2026-08-26', '2026-08-26', 7, 'referido', 'ganada', NULL, NULL, 'Oportunidad generada a partir de conversaciones con el cliente.', '2026-07-18 13:13:47', '2026-10-05 13:13:47'),
	(13, 'OP-0013', 'Auditoría de red y licencias', 5, 9, NULL, 6, 'servicio', 6500.00, 0, '2026-09-17', '2026-09-17', 1, 'web', 'perdida', 'Decidió postergar el proyecto', NULL, 'Oportunidad generada a partir de conversaciones con el cliente.', '2026-08-14 13:13:47', '2026-10-05 13:13:47'),
	(14, 'OP-0014', 'Cambio de switches core', 6, 11, NULL, 6, 'equipos', 19800.00, 0, '2026-09-02', '2026-09-02', 2, 'evento', 'perdida', 'Precio superior a la competencia', NULL, 'Oportunidad generada a partir de conversaciones con el cliente.', '2026-08-04 13:13:47', '2026-10-05 13:13:47');

-- Volcando estructura para tabla sys_gestion_ti.fac_comprobantes
CREATE TABLE IF NOT EXISTS `fac_comprobantes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tipo` enum('factura','boleta','recibo') NOT NULL DEFAULT 'factura',
  `serie` varchar(6) NOT NULL,
  `numero` int unsigned NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `cliente_id` int unsigned NOT NULL,
  `concepto` enum('mensualidad','servicio','equipos','otro') NOT NULL DEFAULT 'servicio',
  `contrato_id` int unsigned DEFAULT NULL,
  `orden_id` int unsigned DEFAULT NULL,
  `cotizacion_id` int unsigned DEFAULT NULL,
  `periodo` char(7) DEFAULT NULL,
  `fecha_emision` date NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `igv_pct` decimal(5,2) NOT NULL DEFAULT '18.00',
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `igv` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `pagado` decimal(12,2) NOT NULL DEFAULT '0.00',
  `estado` enum('emitida','pagada','anulada') NOT NULL DEFAULT 'emitida',
  `motivo_anulacion` varchar(200) DEFAULT NULL,
  `notas` varchar(500) DEFAULT NULL,
  `creado_por` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  UNIQUE KEY `uq_serie_num` (`serie`,`numero`),
  KEY `idx_fac_cli` (`cliente_id`),
  KEY `idx_fac_est` (`estado`,`fecha_vencimiento`),
  CONSTRAINT `fac_comprobantes_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla sys_gestion_ti.fac_comprobantes: ~31 rows (aproximadamente)
DELETE FROM `fac_comprobantes`;
INSERT INTO `fac_comprobantes` (`id`, `tipo`, `serie`, `numero`, `codigo`, `cliente_id`, `concepto`, `contrato_id`, `orden_id`, `cotizacion_id`, `periodo`, `fecha_emision`, `fecha_vencimiento`, `igv_pct`, `subtotal`, `igv`, `total`, `pagado`, `estado`, `motivo_anulacion`, `notas`, `creado_por`, `created_at`) VALUES
	(1, 'factura', 'F001', 1, 'F001-000001', 1, 'mensualidad', 1, NULL, NULL, '2026-07', '2026-07-05', '2026-07-20', 18.00, 1800.00, 324.00, 2124.00, 2124.00, 'pagada', NULL, NULL, 1, '2026-10-05 18:30:44'),
	(2, 'factura', 'F001', 2, 'F001-000002', 1, 'mensualidad', 1, NULL, NULL, '2026-08', '2026-08-05', '2026-08-20', 18.00, 1800.00, 324.00, 2124.00, 0.00, 'emitida', NULL, NULL, 1, '2026-10-05 18:30:45'),
	(3, 'factura', 'F001', 3, 'F001-000003', 1, 'mensualidad', 1, NULL, NULL, '2026-09', '2026-09-05', '2026-09-20', 18.00, 1800.00, 324.00, 2124.00, 0.00, 'emitida', NULL, NULL, 1, '2026-10-05 18:30:45'),
	(4, 'factura', 'F001', 4, 'F001-000004', 2, 'mensualidad', 2, NULL, NULL, '2026-07', '2026-07-05', '2026-07-20', 18.00, 950.00, 171.00, 1121.00, 1121.00, 'pagada', NULL, NULL, 1, '2026-10-05 18:30:45'),
	(5, 'factura', 'F001', 5, 'F001-000005', 2, 'mensualidad', 2, NULL, NULL, '2026-08', '2026-08-05', '2026-08-20', 18.00, 950.00, 171.00, 1121.00, 1121.00, 'pagada', NULL, NULL, 1, '2026-10-05 18:30:45'),
	(6, 'factura', 'F001', 6, 'F001-000006', 2, 'mensualidad', 2, NULL, NULL, '2026-09', '2026-09-05', '2026-09-20', 18.00, 950.00, 171.00, 1121.00, 560.50, 'emitida', NULL, NULL, 1, '2026-10-05 18:30:45'),
	(7, 'factura', 'F001', 7, 'F001-000007', 3, 'mensualidad', 3, NULL, NULL, '2026-07', '2026-07-05', '2026-07-20', 18.00, 420.00, 75.60, 495.60, 495.60, 'pagada', NULL, NULL, 1, '2026-10-05 18:30:45'),
	(8, 'factura', 'F001', 8, 'F001-000008', 3, 'mensualidad', 3, NULL, NULL, '2026-08', '2026-08-05', '2026-08-20', 18.00, 420.00, 75.60, 495.60, 495.60, 'pagada', NULL, NULL, 1, '2026-10-05 18:30:45'),
	(9, 'factura', 'F001', 9, 'F001-000009', 3, 'mensualidad', 3, NULL, NULL, '2026-09', '2026-09-05', '2026-09-20', 18.00, 420.00, 75.60, 495.60, 495.60, 'pagada', NULL, NULL, 1, '2026-10-05 18:30:45'),
	(10, 'factura', 'F001', 10, 'F001-000010', 4, 'mensualidad', 4, NULL, NULL, '2026-07', '2026-07-05', '2026-07-20', 18.00, 1800.00, 324.00, 2124.00, 2124.00, 'pagada', NULL, NULL, 1, '2026-10-05 18:30:45'),
	(11, 'factura', 'F001', 11, 'F001-000011', 4, 'mensualidad', 4, NULL, NULL, '2026-08', '2026-08-05', '2026-08-20', 18.00, 1800.00, 324.00, 2124.00, 2124.00, 'pagada', NULL, NULL, 1, '2026-10-05 18:30:45'),
	(12, 'factura', 'F001', 12, 'F001-000012', 4, 'mensualidad', 4, NULL, NULL, '2026-09', '2026-09-05', '2026-09-20', 18.00, 1800.00, 324.00, 2124.00, 0.00, 'emitida', NULL, NULL, 1, '2026-10-05 18:30:45'),
	(13, 'factura', 'F001', 13, 'F001-000013', 5, 'mensualidad', 5, NULL, NULL, '2026-07', '2026-07-05', '2026-07-20', 18.00, 950.00, 171.00, 1121.00, 1121.00, 'pagada', NULL, NULL, 1, '2026-10-05 18:30:45'),
	(14, 'factura', 'F001', 14, 'F001-000014', 5, 'mensualidad', 5, NULL, NULL, '2026-08', '2026-08-05', '2026-08-20', 18.00, 950.00, 171.00, 1121.00, 0.00, 'emitida', NULL, NULL, 1, '2026-10-05 18:30:45'),
	(15, 'factura', 'F001', 15, 'F001-000015', 5, 'mensualidad', 5, NULL, NULL, '2026-09', '2026-09-05', '2026-09-20', 18.00, 950.00, 171.00, 1121.00, 560.50, 'emitida', NULL, NULL, 1, '2026-10-05 18:30:45'),
	(16, 'factura', 'F001', 16, 'F001-000016', 6, 'mensualidad', 6, NULL, NULL, '2026-07', '2026-07-05', '2026-07-20', 18.00, 420.00, 75.60, 495.60, 495.60, 'pagada', NULL, NULL, 1, '2026-10-05 18:30:45'),
	(17, 'factura', 'F001', 17, 'F001-000017', 6, 'mensualidad', 6, NULL, NULL, '2026-08', '2026-08-05', '2026-08-20', 18.00, 420.00, 75.60, 495.60, 495.60, 'pagada', NULL, NULL, 1, '2026-10-05 18:30:45'),
	(18, 'factura', 'F001', 18, 'F001-000018', 6, 'mensualidad', 6, NULL, NULL, '2026-09', '2026-09-05', '2026-09-20', 18.00, 420.00, 75.60, 495.60, 495.60, 'pagada', NULL, NULL, 1, '2026-10-05 18:30:45'),
	(19, 'factura', 'F001', 19, 'F001-000019', 7, 'mensualidad', 7, NULL, NULL, '2026-07', '2026-07-05', '2026-07-20', 18.00, 1800.00, 324.00, 2124.00, 2124.00, 'pagada', NULL, NULL, 1, '2026-10-05 18:30:45'),
	(20, 'factura', 'F001', 20, 'F001-000020', 7, 'mensualidad', 7, NULL, NULL, '2026-08', '2026-08-05', '2026-08-20', 18.00, 1800.00, 324.00, 2124.00, 2124.00, 'pagada', NULL, NULL, 1, '2026-10-05 18:30:45'),
	(21, 'factura', 'F001', 21, 'F001-000021', 7, 'mensualidad', 7, NULL, NULL, '2026-09', '2026-09-05', '2026-09-20', 18.00, 1800.00, 324.00, 2124.00, 0.00, 'emitida', NULL, NULL, 1, '2026-10-05 18:30:45'),
	(22, 'factura', 'F001', 22, 'F001-000022', 8, 'mensualidad', 8, NULL, NULL, '2026-07', '2026-07-05', '2026-07-20', 18.00, 950.00, 171.00, 1121.00, 1121.00, 'pagada', NULL, NULL, 1, '2026-10-05 18:30:45'),
	(23, 'factura', 'F001', 23, 'F001-000023', 8, 'mensualidad', 8, NULL, NULL, '2026-08', '2026-08-05', '2026-08-20', 18.00, 950.00, 171.00, 1121.00, 1121.00, 'pagada', NULL, NULL, 1, '2026-10-05 18:30:45'),
	(24, 'factura', 'F001', 24, 'F001-000024', 8, 'mensualidad', 8, NULL, NULL, '2026-09', '2026-09-05', '2026-09-20', 18.00, 950.00, 171.00, 1121.00, 560.50, 'emitida', NULL, NULL, 1, '2026-10-05 18:30:45'),
	(25, 'factura', 'F001', 25, 'F001-000025', 1, 'servicio', NULL, 1, NULL, NULL, '2026-08-28', '2026-09-04', 18.00, 99.00, 17.82, 116.82, 0.00, 'emitida', NULL, NULL, 1, '2026-10-05 18:30:45'),
	(26, 'factura', 'F001', 26, 'F001-000026', 2, 'servicio', NULL, 2, NULL, NULL, '2026-09-24', '2026-10-01', 18.00, 357.00, 64.26, 421.26, 421.26, 'pagada', NULL, NULL, 1, '2026-10-05 18:30:45'),
	(27, 'factura', 'F001', 27, 'F001-000027', 3, 'servicio', NULL, 3, NULL, NULL, '2026-08-25', '2026-09-01', 18.00, 383.00, 68.94, 451.94, 0.00, 'emitida', NULL, NULL, 1, '2026-10-05 18:30:45'),
	(28, 'factura', 'F001', 28, 'F001-000028', 4, 'servicio', NULL, 4, NULL, NULL, '2026-08-06', '2026-08-13', 18.00, 288.50, 51.93, 340.43, 340.43, 'pagada', NULL, NULL, 1, '2026-10-05 18:30:45'),
	(29, 'factura', 'F001', 29, 'F001-000029', 5, 'servicio', NULL, 5, NULL, NULL, '2026-09-10', '2026-09-17', 18.00, 396.50, 71.37, 467.87, 0.00, 'emitida', NULL, NULL, 1, '2026-10-05 18:30:45'),
	(30, 'factura', 'F001', 30, 'F001-000030', 6, 'servicio', NULL, 6, NULL, NULL, '2026-08-26', '2026-09-02', 18.00, 604.50, 108.81, 713.31, 713.31, 'pagada', NULL, NULL, 1, '2026-10-05 18:30:45'),
	(31, 'factura', 'F001', 31, 'F001-000031', 3, 'equipos', NULL, NULL, 4, NULL, '2026-09-07', '2026-10-07', 18.00, 11700.00, 2106.00, 13806.00, 0.00, 'emitida', NULL, NULL, 1, '2026-10-05 18:30:45');

-- Volcando estructura para tabla sys_gestion_ti.fac_items
CREATE TABLE IF NOT EXISTS `fac_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `comprobante_id` int unsigned NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL DEFAULT '1.00',
  `precio_unit` decimal(12,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `comprobante_id` (`comprobante_id`),
  CONSTRAINT `fac_items_ibfk_1` FOREIGN KEY (`comprobante_id`) REFERENCES `fac_comprobantes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla sys_gestion_ti.fac_items: ~38 rows (aproximadamente)
DELETE FROM `fac_items`;
INSERT INTO `fac_items` (`id`, `comprobante_id`, `descripcion`, `cantidad`, `precio_unit`) VALUES
	(1, 1, 'Soporte TI plan Premium · 2026-07', 1.00, 1800.00),
	(2, 2, 'Soporte TI plan Premium · 2026-08', 1.00, 1800.00),
	(3, 3, 'Soporte TI plan Premium · 2026-09', 1.00, 1800.00),
	(4, 4, 'Soporte TI plan Business · 2026-07', 1.00, 950.00),
	(5, 5, 'Soporte TI plan Business · 2026-08', 1.00, 950.00),
	(6, 6, 'Soporte TI plan Business · 2026-09', 1.00, 950.00),
	(7, 7, 'Soporte TI plan Básico · 2026-07', 1.00, 420.00),
	(8, 8, 'Soporte TI plan Básico · 2026-08', 1.00, 420.00),
	(9, 9, 'Soporte TI plan Básico · 2026-09', 1.00, 420.00),
	(10, 10, 'Soporte TI plan Premium · 2026-07', 1.00, 1800.00),
	(11, 11, 'Soporte TI plan Premium · 2026-08', 1.00, 1800.00),
	(12, 12, 'Soporte TI plan Premium · 2026-09', 1.00, 1800.00),
	(13, 13, 'Soporte TI plan Business · 2026-07', 1.00, 950.00),
	(14, 14, 'Soporte TI plan Business · 2026-08', 1.00, 950.00),
	(15, 15, 'Soporte TI plan Business · 2026-09', 1.00, 950.00),
	(16, 16, 'Soporte TI plan Básico · 2026-07', 1.00, 420.00),
	(17, 17, 'Soporte TI plan Básico · 2026-08', 1.00, 420.00),
	(18, 18, 'Soporte TI plan Básico · 2026-09', 1.00, 420.00),
	(19, 19, 'Soporte TI plan Premium · 2026-07', 1.00, 1800.00),
	(20, 20, 'Soporte TI plan Premium · 2026-08', 1.00, 1800.00),
	(21, 21, 'Soporte TI plan Premium · 2026-09', 1.00, 1800.00),
	(22, 22, 'Soporte TI plan Business · 2026-07', 1.00, 950.00),
	(23, 23, 'Soporte TI plan Business · 2026-08', 1.00, 950.00),
	(24, 24, 'Soporte TI plan Business · 2026-09', 1.00, 950.00),
	(25, 25, 'Mano de obra correctiva · OS-2026-001', 2.20, 45.00),
	(26, 26, 'Mano de obra preventiva · OS-2026-002', 2.60, 45.00),
	(27, 26, 'Fuente de poder 500W', 2.00, 120.00),
	(28, 27, 'Mano de obra instalacion · OS-2026-003', 6.40, 45.00),
	(29, 27, 'Memoria RAM DDR4 8GB', 1.00, 95.00),
	(30, 28, 'Mano de obra diagnostico · OS-2026-004', 2.30, 45.00),
	(31, 28, 'Disco SSD 480GB', 1.00, 185.00),
	(32, 29, 'Mano de obra correctiva · OS-2026-005', 6.70, 45.00),
	(33, 29, 'Memoria RAM DDR4 8GB', 1.00, 95.00),
	(34, 30, 'Mano de obra preventiva · OS-2026-006', 4.10, 45.00),
	(35, 30, 'Batería laptop Dell', 2.00, 210.00),
	(36, 31, 'NAS 4 bahías 16 TB', 1.00, 7800.00),
	(37, 31, 'Instalación y configuración', 1.00, 1500.00),
	(38, 31, 'Backup remoto (12 meses)', 1.00, 2400.00);

-- Volcando estructura para tabla sys_gestion_ti.fac_pagos
CREATE TABLE IF NOT EXISTS `fac_pagos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `comprobante_id` int unsigned NOT NULL,
  `fecha` date NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `metodo` enum('efectivo','transferencia','yape','plin','tarjeta','deposito','cheque') NOT NULL DEFAULT 'transferencia',
  `referencia` varchar(80) DEFAULT NULL,
  `notas` varchar(200) DEFAULT NULL,
  `registrado_por` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pag_fecha` (`fecha`),
  KEY `comprobante_id` (`comprobante_id`),
  CONSTRAINT `fac_pagos_ibfk_1` FOREIGN KEY (`comprobante_id`) REFERENCES `fac_comprobantes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla sys_gestion_ti.fac_pagos: ~22 rows (aproximadamente)
DELETE FROM `fac_pagos`;
INSERT INTO `fac_pagos` (`id`, `comprobante_id`, `fecha`, `monto`, `metodo`, `referencia`, `notas`, `registrado_por`, `created_at`) VALUES
	(1, 1, '2026-07-16', 2124.00, 'transferencia', 'OP-4001', NULL, 1, '2026-10-05 18:30:45'),
	(2, 4, '2026-07-16', 1121.00, 'efectivo', 'OP-4004', NULL, 1, '2026-10-05 18:30:45'),
	(3, 5, '2026-08-16', 1121.00, 'plin', 'OP-4005', NULL, 1, '2026-10-05 18:30:45'),
	(4, 6, '2026-09-16', 560.50, 'transferencia', 'OP-4006', NULL, 1, '2026-10-05 18:30:45'),
	(5, 7, '2026-07-16', 495.60, 'transferencia', 'OP-4007', NULL, 1, '2026-10-05 18:30:45'),
	(6, 8, '2026-08-16', 495.60, 'yape', 'OP-4008', NULL, 1, '2026-10-05 18:30:45'),
	(7, 9, '2026-09-16', 495.60, 'deposito', 'OP-4009', NULL, 1, '2026-10-05 18:30:45'),
	(8, 10, '2026-07-16', 2124.00, 'efectivo', 'OP-4010', NULL, 1, '2026-10-05 18:30:45'),
	(9, 11, '2026-08-16', 2124.00, 'plin', 'OP-4011', NULL, 1, '2026-10-05 18:30:45'),
	(10, 13, '2026-07-16', 1121.00, 'transferencia', 'OP-4013', NULL, 1, '2026-10-05 18:30:45'),
	(11, 15, '2026-09-16', 560.50, 'deposito', 'OP-4015', NULL, 1, '2026-10-05 18:30:45'),
	(12, 16, '2026-07-16', 495.60, 'efectivo', 'OP-4016', NULL, 1, '2026-10-05 18:30:45'),
	(13, 17, '2026-08-16', 495.60, 'plin', 'OP-4017', NULL, 1, '2026-10-05 18:30:45'),
	(14, 18, '2026-09-16', 495.60, 'transferencia', 'OP-4018', NULL, 1, '2026-10-05 18:30:45'),
	(15, 19, '2026-07-16', 2124.00, 'transferencia', 'OP-4019', NULL, 1, '2026-10-05 18:30:45'),
	(16, 20, '2026-08-16', 2124.00, 'yape', 'OP-4020', NULL, 1, '2026-10-05 18:30:45'),
	(17, 22, '2026-07-16', 1121.00, 'efectivo', 'OP-4022', NULL, 1, '2026-10-05 18:30:45'),
	(18, 23, '2026-08-16', 1121.00, 'plin', 'OP-4023', NULL, 1, '2026-10-05 18:30:45'),
	(19, 24, '2026-09-16', 560.50, 'transferencia', 'OP-4024', NULL, 1, '2026-10-05 18:30:45'),
	(20, 26, '2026-09-27', 421.26, 'yape', 'OP-4026', NULL, 1, '2026-10-05 18:30:45'),
	(21, 28, '2026-08-09', 340.43, 'efectivo', 'OP-4028', NULL, 1, '2026-10-05 18:30:45'),
	(22, 30, '2026-08-29', 713.31, 'transferencia', 'OP-4030', NULL, 1, '2026-10-05 18:30:45');

-- Volcando estructura para tabla sys_gestion_ti.ia_consultas
CREATE TABLE IF NOT EXISTS `ia_consultas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `pregunta` varchar(500) NOT NULL,
  `titulo` varchar(160) DEFAULT NULL,
  `sql_texto` text NOT NULL,
  `grafico` varchar(255) DEFAULT NULL,
  `favorito` tinyint(1) NOT NULL DEFAULT '0',
  `veces` int NOT NULL DEFAULT '1',
  `creado_en` datetime DEFAULT CURRENT_TIMESTAMP,
  `ultimo_uso` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`,`favorito`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla sys_gestion_ti.ia_consultas: ~0 rows (aproximadamente)
DELETE FROM `ia_consultas`;
INSERT INTO `ia_consultas` (`id`, `usuario_id`, `pregunta`, `titulo`, `sql_texto`, `grafico`, `favorito`, `veces`, `creado_en`, `ultimo_uso`) VALUES
	(1, 1, 'ticket pendientes', 'Tickets pendientes de atención', 'SELECT ticket_id, codigo, fecha, cliente, categoria, prioridad, estado, tecnico, equipo, fecha_limite FROM v_ia_tickets WHERE activo = 1 ORDER BY prioridad DESC, fecha ASC LIMIT 100', '{"tipo":"none","x":"","y":""}', 0, 1, '2026-10-05 13:20:46', '2026-10-05 13:20:46');

-- Volcando estructura para tabla sys_gestion_ti.kb_articulos
CREATE TABLE IF NOT EXISTS `kb_articulos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `categoria_id` tinyint unsigned DEFAULT NULL,
  `titulo` varchar(180) NOT NULL,
  `contenido` mediumtext,
  `vistas` int NOT NULL DEFAULT '0',
  `autor_id` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla sys_gestion_ti.kb_articulos: ~5 rows (aproximadamente)
DELETE FROM `kb_articulos`;
INSERT INTO `kb_articulos` (`id`, `categoria_id`, `titulo`, `contenido`, `vistas`, `autor_id`, `created_at`) VALUES
	(1, 2, 'Cómo restablecer contraseña de Active Directory', '1. Abre «Usuarios y equipos de Active Directory» con una cuenta de administrador.\n2. Busca al usuario y haz clic derecho > Restablecer contraseña.\n3. Define una contraseña temporal y marca «El usuario debe cambiar la contraseña en el siguiente inicio de sesión».\n4. Si la cuenta aparece bloqueada, desmarca «Desbloquear cuenta».\n5. Confirma con el usuario que puede ingresar y cierra el ticket.\n\nNota: nunca envíes la contraseña por correo; comunícala por teléfono.', 126, 1, '2026-10-05 13:32:12'),
	(2, 3, 'Configuración de VPN con FortiClient', '1. Instala FortiClient VPN (versión gratuita) desde el portal de descargas.\n2. Nueva conexión VPN > SSL-VPN.\n3. Gateway remoto: el dominio entregado por el área de TI. Puerto 443.\n4. Autenticación: solicitar usuario y contraseña; si hay token, habilitar doble factor.\n5. Prueba acceso a un recurso interno (ping al servidor de archivos).\n\nProblemas frecuentes: certificado no confiable (importar CA), puerto 443 bloqueado por el proveedor.', 113, 1, '2026-10-05 13:32:12'),
	(3, 6, 'Solución a impresora offline en red', '1. Verifica que la impresora tenga IP y responda al ping.\n2. En el equipo: Panel de control > Dispositivos e impresoras > desmarca «Usar impresora sin conexión».\n3. Revisa que el puerto TCP/IP apunte a la IP correcta (reserva DHCP recomendada).\n4. Reinicia el servicio «Cola de impresión» (spooler).\n5. Si persiste, elimina y vuelve a instalar el controlador oficial.', 230, 1, '2026-10-05 13:32:12'),
	(4, 1, 'Checklist de mantenimiento preventivo', 'Equipos de escritorio y laptops:\n- Limpieza interna y externa, pasta térmica si aplica.\n- Actualizaciones de Windows y antivirus.\n- Estado de disco (SMART) y espacio libre.\n- Revisión de copias de seguridad.\n\nRedes: revisión de switches, etiquetado de patch panel y respaldo de configuración.\nServidores: logs, temperatura, RAID y pruebas de restauración.', 45, 1, '2026-10-05 13:32:12'),
	(5, 4, 'Respaldo con Veeam: buenas prácticas', '- Regla 3-2-1: 3 copias, 2 medios distintos, 1 fuera de sitio.\n- Programar trabajos fuera del horario laboral.\n- Verificar el resultado cada mañana (correo de reporte).\n- Probar la restauración completa cada trimestre.\n- Cifrar los respaldos y proteger las credenciales del repositorio.', 121, 1, '2026-10-05 13:32:12');

-- Volcando estructura para tabla sys_gestion_ti.notif_envios
CREATE TABLE IF NOT EXISTS `notif_envios` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `canal` enum('correo','whatsapp') NOT NULL DEFAULT 'correo',
  `destinatario` varchar(160) NOT NULL,
  `asunto` varchar(200) NOT NULL,
  `cuerpo` mediumtext,
  `estado` enum('enviado','simulado','error') NOT NULL DEFAULT 'simulado',
  `error` varchar(255) DEFAULT NULL,
  `cliente_id` int unsigned DEFAULT NULL,
  `ticket_id` int unsigned DEFAULT NULL,
  `cotizacion_id` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nv_fecha` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla sys_gestion_ti.notif_envios: ~0 rows (aproximadamente)
DELETE FROM `notif_envios`;

-- Volcando estructura para tabla sys_gestion_ti.ordenes_repuestos
CREATE TABLE IF NOT EXISTS `ordenes_repuestos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `orden_id` int unsigned NOT NULL,
  `repuesto_id` int unsigned NOT NULL,
  `cantidad` int NOT NULL DEFAULT '1',
  `precio_unit` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `orden_id` (`orden_id`),
  KEY `repuesto_id` (`repuesto_id`),
  CONSTRAINT `ordenes_repuestos_ibfk_1` FOREIGN KEY (`orden_id`) REFERENCES `ordenes_servicio` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ordenes_repuestos_ibfk_2` FOREIGN KEY (`repuesto_id`) REFERENCES `repuestos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla sys_gestion_ti.ordenes_repuestos: ~5 rows (aproximadamente)
DELETE FROM `ordenes_repuestos`;
INSERT INTO `ordenes_repuestos` (`id`, `orden_id`, `repuesto_id`, `cantidad`, `precio_unit`) VALUES
	(1, 2, 3, 2, 120.00),
	(2, 3, 2, 1, 95.00),
	(3, 4, 1, 1, 185.00),
	(4, 5, 2, 1, 95.00),
	(5, 6, 6, 2, 210.00);

-- Volcando estructura para tabla sys_gestion_ti.ordenes_servicio
CREATE TABLE IF NOT EXISTS `ordenes_servicio` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) NOT NULL,
  `ticket_id` int unsigned DEFAULT NULL,
  `cliente_id` int unsigned NOT NULL,
  `activo_id` int unsigned DEFAULT NULL,
  `tecnico_id` int unsigned DEFAULT NULL,
  `tipo` enum('correctiva','preventiva','instalacion','diagnostico') NOT NULL DEFAULT 'correctiva',
  `estado` enum('pendiente','en_curso','completada','cancelada') NOT NULL DEFAULT 'pendiente',
  `descripcion_trabajo` text,
  `diagnostico` text,
  `solucion` text,
  `horas` decimal(5,2) NOT NULL DEFAULT '0.00',
  `tarifa_hora` decimal(8,2) NOT NULL DEFAULT '45.00',
  `conformidad` tinyint(1) NOT NULL DEFAULT '0',
  `fecha_programada` datetime DEFAULT NULL,
  `fecha_inicio` datetime DEFAULT NULL,
  `fecha_fin` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `cotizacion_id` int unsigned DEFAULT NULL,
  `firma` mediumtext,
  `firma_nombre` varchar(100) DEFAULT NULL,
  `firma_fecha` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `idx_os_estado` (`estado`),
  KEY `cliente_id` (`cliente_id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `activo_id` (`activo_id`),
  KEY `tecnico_id` (`tecnico_id`),
  CONSTRAINT `ordenes_servicio_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  CONSTRAINT `ordenes_servicio_ibfk_2` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ordenes_servicio_ibfk_3` FOREIGN KEY (`activo_id`) REFERENCES `activos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ordenes_servicio_ibfk_4` FOREIGN KEY (`tecnico_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla sys_gestion_ti.ordenes_servicio: ~12 rows (aproximadamente)
DELETE FROM `ordenes_servicio`;
INSERT INTO `ordenes_servicio` (`id`, `codigo`, `ticket_id`, `cliente_id`, `activo_id`, `tecnico_id`, `tipo`, `estado`, `descripcion_trabajo`, `diagnostico`, `solucion`, `horas`, `tarifa_hora`, `conformidad`, `fecha_programada`, `fecha_inicio`, `fecha_fin`, `created_at`, `cotizacion_id`, `firma`, `firma_nombre`, `firma_fecha`) VALUES
	(1, 'OS-2026-001', NULL, 1, NULL, 3, 'correctiva', 'completada', 'Reemplazo de disco duro y reinstalación del sistema', 'Se identificó el origen de la falla tras las pruebas en sitio.', 'Trabajo ejecutado y probado con el usuario. Equipo operativo.', 2.20, 45.00, 1, '2026-08-28 09:00:00', '2026-08-28 09:30:00', '2026-08-28 11:42:00', '2026-10-05 17:50:21', NULL, NULL, NULL, NULL),
	(2, 'OS-2026-002', NULL, 2, NULL, 4, 'preventiva', 'completada', 'Mantenimiento preventivo de equipos de oficina', 'Se identificó el origen de la falla tras las pruebas en sitio.', 'Trabajo ejecutado y probado con el usuario. Equipo operativo.', 2.60, 45.00, 1, '2026-09-24 09:00:00', '2026-09-24 09:30:00', '2026-09-24 12:06:00', '2026-10-05 17:50:21', NULL, NULL, NULL, NULL),
	(3, 'OS-2026-003', NULL, 3, NULL, 5, 'instalacion', 'completada', 'Instalación y configuración de switch PoE', 'Se identificó el origen de la falla tras las pruebas en sitio.', 'Trabajo ejecutado y probado con el usuario. Equipo operativo.', 6.40, 45.00, 1, '2026-08-25 09:00:00', '2026-08-25 09:30:00', '2026-08-25 15:54:00', '2026-10-05 17:50:21', NULL, NULL, NULL, NULL),
	(4, 'OS-2026-004', NULL, 4, NULL, 6, 'diagnostico', 'completada', 'Diagnóstico de lentitud en servidor de archivos', 'Se identificó el origen de la falla tras las pruebas en sitio.', 'Trabajo ejecutado y probado con el usuario. Equipo operativo.', 2.30, 45.00, 1, '2026-08-06 09:00:00', '2026-08-06 09:30:00', '2026-08-06 11:48:00', '2026-10-05 17:50:21', NULL, NULL, NULL, NULL),
	(5, 'OS-2026-005', NULL, 5, NULL, 3, 'correctiva', 'completada', 'Cambio de fuente de poder en estación de trabajo', 'Se identificó el origen de la falla tras las pruebas en sitio.', 'Trabajo ejecutado y probado con el usuario. Equipo operativo.', 6.70, 45.00, 1, '2026-09-10 09:00:00', '2026-09-10 09:30:00', '2026-09-10 16:12:00', '2026-10-05 17:50:21', NULL, NULL, NULL, NULL),
	(6, 'OS-2026-006', NULL, 6, NULL, 4, 'preventiva', 'completada', 'Configuración de punto de acceso WiFi', 'Se identificó el origen de la falla tras las pruebas en sitio.', 'Trabajo ejecutado y probado con el usuario. Equipo operativo.', 4.10, 45.00, 1, '2026-08-26 09:00:00', '2026-08-26 09:30:00', '2026-08-26 13:35:59', '2026-10-05 17:50:21', NULL, NULL, NULL, NULL),
	(7, 'OS-2026-007', NULL, 7, NULL, 5, 'instalacion', 'en_curso', 'Limpieza y mantenimiento de impresoras', NULL, NULL, 0.00, 45.00, 0, '2026-10-04 09:00:00', '2026-10-04 09:30:00', NULL, '2026-10-05 17:50:21', NULL, NULL, NULL, NULL),
	(8, 'OS-2026-008', NULL, 8, NULL, 6, 'diagnostico', 'en_curso', 'Reubicación de puntos de red', NULL, NULL, 0.00, 45.00, 0, '2026-10-04 09:00:00', '2026-10-04 09:30:00', NULL, '2026-10-05 17:50:21', NULL, NULL, NULL, NULL),
	(9, 'OS-2026-009', NULL, 1, NULL, 3, 'correctiva', 'pendiente', 'Reemplazo de disco duro y reinstalación del sistema', NULL, NULL, 0.00, 45.00, 0, '2026-10-07 09:00:00', NULL, NULL, '2026-10-05 17:50:21', NULL, NULL, NULL, NULL),
	(10, 'OS-2026-010', NULL, 2, NULL, 4, 'preventiva', 'pendiente', 'Mantenimiento preventivo de equipos de oficina', NULL, NULL, 0.00, 45.00, 0, '2026-10-10 09:00:00', NULL, NULL, '2026-10-05 17:50:21', NULL, NULL, NULL, NULL),
	(11, 'OS-2026-011', NULL, 3, NULL, 5, 'instalacion', 'pendiente', 'Instalación y configuración de switch PoE', NULL, NULL, 0.00, 45.00, 0, '2026-10-08 09:00:00', NULL, NULL, '2026-10-05 17:50:21', NULL, NULL, NULL, NULL),
	(12, 'OS-2026-012', NULL, 4, NULL, 6, 'diagnostico', 'cancelada', 'Diagnóstico de lentitud en servidor de archivos', NULL, NULL, 0.00, 45.00, 0, '2026-10-05 09:00:00', NULL, NULL, '2026-10-05 17:50:21', NULL, NULL, NULL, NULL);

-- Volcando estructura para tabla sys_gestion_ti.repuestos
CREATE TABLE IF NOT EXISTS `repuestos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `categoria` varchar(60) DEFAULT NULL,
  `stock` int NOT NULL DEFAULT '0',
  `stock_minimo` int NOT NULL DEFAULT '5',
  `precio` decimal(10,2) NOT NULL DEFAULT '0.00',
  `ubicacion` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla sys_gestion_ti.repuestos: ~10 rows (aproximadamente)
DELETE FROM `repuestos`;
INSERT INTO `repuestos` (`id`, `codigo`, `nombre`, `categoria`, `stock`, `stock_minimo`, `precio`, `ubicacion`) VALUES
	(1, 'REP-001', 'Disco SSD 480GB', 'Almacenamiento', 3, 8, 185.00, NULL),
	(2, 'REP-002', 'Memoria RAM DDR4 8GB', 'Memoria', 14, 6, 95.00, NULL),
	(3, 'REP-003', 'Fuente de poder 500W', 'Componentes', 2, 5, 120.00, NULL),
	(4, 'REP-004', 'Cable UTP Cat6 (caja 305m)', 'Redes', 6, 3, 390.00, NULL),
	(5, 'REP-005', 'Tóner HP 105A', 'Consumibles', 4, 6, 260.00, NULL),
	(6, 'REP-006', 'Batería laptop Dell', 'Baterías', 9, 4, 210.00, NULL),
	(7, 'REP-007', 'Switch 24p PoE', 'Redes', 2, 2, 1450.00, NULL),
	(8, 'REP-008', 'Pantalla LED 15.6"', 'Pantallas', 1, 3, 340.00, NULL),
	(9, 'REP-009', 'Conector RJ45 (100u)', 'Redes', 20, 8, 45.00, NULL),
	(10, 'REP-010', 'Pasta térmica', 'Consumibles', 12, 5, 22.00, NULL);

-- Volcando estructura para tabla sys_gestion_ti.roles
CREATE TABLE IF NOT EXISTS `roles` (
  `id` tinyint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(40) NOT NULL,
  `descripcion` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla sys_gestion_ti.roles: ~4 rows (aproximadamente)
DELETE FROM `roles`;
INSERT INTO `roles` (`id`, `nombre`, `descripcion`) VALUES
	(1, 'Administrador', 'Acceso total'),
	(2, 'Supervisor', 'Gestiona equipo y reportes'),
	(3, 'Técnico', 'Atiende tickets y visitas'),
	(4, 'Recepción', 'Registra tickets y clientes'),
	(5, 'Comercial', 'Gestiona CRM, oportunidades y cotizaciones'),
	(6, 'Cliente', 'Acceso al portal de autoservicio del cliente');

-- Volcando estructura para tabla sys_gestion_ti.tickets
CREATE TABLE IF NOT EXISTS `tickets` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) NOT NULL,
  `cliente_id` int unsigned NOT NULL,
  `activo_id` int unsigned DEFAULT NULL,
  `categoria_id` tinyint unsigned NOT NULL,
  `asunto` varchar(180) NOT NULL,
  `descripcion` text,
  `prioridad` enum('baja','media','alta','critica') NOT NULL DEFAULT 'media',
  `estado` enum('abierto','en_proceso','pendiente','resuelto','cerrado') NOT NULL DEFAULT 'abierto',
  `canal` enum('web','correo','telefono','whatsapp','presencial') NOT NULL DEFAULT 'web',
  `tecnico_id` int unsigned DEFAULT NULL,
  `creado_por` int unsigned DEFAULT NULL,
  `fecha_limite` datetime DEFAULT NULL,
  `resuelto_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `idx_estado` (`estado`),
  KEY `idx_created` (`created_at`),
  KEY `cliente_id` (`cliente_id`),
  KEY `categoria_id` (`categoria_id`),
  KEY `tecnico_id` (`tecnico_id`),
  KEY `activo_id` (`activo_id`),
  CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`),
  CONSTRAINT `tickets_ibfk_3` FOREIGN KEY (`tecnico_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_ibfk_4` FOREIGN KEY (`activo_id`) REFERENCES `activos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=231 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla sys_gestion_ti.tickets: ~230 rows (aproximadamente)
DELETE FROM `tickets`;
INSERT INTO `tickets` (`id`, `codigo`, `cliente_id`, `activo_id`, `categoria_id`, `asunto`, `descripcion`, `prioridad`, `estado`, `canal`, `tecnico_id`, `creado_por`, `fecha_limite`, `resuelto_at`, `created_at`, `updated_at`) VALUES
	(1, 'TCK-01001', 8, NULL, 2, 'Sistema ERP muy lento', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'web', 3, 1, '2026-06-06 03:27:42', '2026-06-05 22:54:06', '2026-06-05 03:27:42', '2026-10-05 08:32:12'),
	(2, 'TCK-01002', 8, NULL, 7, 'Bloqueo de cuenta por intentos fallidos', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'correo', 6, 1, '2026-07-22 01:04:31', '2026-07-20 20:16:31', '2026-07-20 01:04:31', '2026-10-05 08:32:12'),
	(3, 'TCK-01003', 5, NULL, 1, 'Reemplazo de disco duro por SSD', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'presencial', 6, 1, '2026-09-06 02:17:21', '2026-09-05 01:48:33', '2026-09-04 02:17:21', '2026-10-05 08:32:12'),
	(4, 'TCK-01004', 2, NULL, 1, 'Reemplazo de disco duro por SSD', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'correo', 6, 1, '2026-06-24 22:27:46', '2026-06-24 16:13:21', '2026-06-23 22:27:46', '2026-10-05 08:32:12'),
	(5, 'TCK-01005', 6, NULL, 6, 'Impresora no imprime en red', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'baja', 'cerrado', 'whatsapp', 4, 1, '2026-08-20 00:24:52', '2026-08-18 21:03:16', '2026-08-17 00:24:52', '2026-10-05 08:32:12'),
	(6, 'TCK-01006', 4, NULL, 6, 'Cambio de tóner', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'correo', 6, 1, '2026-10-05 07:00:13', '2026-10-05 00:17:01', '2026-10-03 07:00:13', '2026-10-05 08:32:12'),
	(7, 'TCK-01007', 7, NULL, 6, 'Impresora no imprime en red', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'resuelto', 'presencial', 6, 1, '2026-07-29 08:07:36', '2026-07-28 15:48:24', '2026-07-27 08:07:36', '2026-10-05 08:32:12'),
	(8, 'TCK-01008', 7, NULL, 1, 'Reemplazo de disco duro por SSD', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'web', 4, 1, '2026-04-26 22:28:53', '2026-04-26 10:28:53', '2026-04-24 22:28:53', '2026-10-05 08:32:12'),
	(9, 'TCK-01009', 1, NULL, 5, 'Alta de nuevo usuario de correo', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'resuelto', 'web', 3, 1, '2026-05-31 02:47:38', '2026-05-30 14:04:26', '2026-05-30 02:47:38', '2026-10-05 08:32:12'),
	(10, 'TCK-01010', 6, NULL, 7, 'Bloqueo de cuenta por intentos fallidos', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'web', 4, 1, '2026-09-02 05:38:44', '2026-09-01 22:41:08', '2026-09-01 05:38:44', '2026-10-05 08:32:12'),
	(11, 'TCK-01011', 1, NULL, 1, 'Pantalla con líneas verticales', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'web', 3, 1, '2026-07-06 02:47:33', '2026-07-06 00:23:33', '2026-07-05 02:47:33', '2026-10-05 08:32:12'),
	(12, 'TCK-01012', 3, NULL, 1, 'Laptop no enciende tras corte de energía', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'baja', 'abierto', 'presencial', 5, 1, '2026-10-05 21:50:09', NULL, '2026-10-02 21:50:09', '2026-10-05 08:32:12'),
	(13, 'TCK-01013', 1, NULL, 8, 'Anexos sin tono en oficina', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'cerrado', 'whatsapp', 5, 1, '2026-08-07 13:43:29', '2026-08-07 17:19:29', '2026-08-07 05:43:29', '2026-10-05 08:32:12'),
	(14, 'TCK-01014', 3, NULL, 3, 'Configurar VPN para trabajo remoto', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'resuelto', 'telefono', 4, 1, '2026-06-06 04:20:12', '2026-06-04 21:37:00', '2026-06-04 04:20:12', '2026-10-05 08:32:12'),
	(15, 'TCK-01015', 2, NULL, 3, 'Sin internet en el segundo piso', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'baja', 'cerrado', 'telefono', 5, 1, '2026-09-12 04:59:31', '2026-09-11 11:42:43', '2026-09-09 04:59:31', '2026-10-05 08:32:12'),
	(16, 'TCK-01016', 4, NULL, 1, 'Reemplazo de disco duro por SSD', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'resuelto', 'correo', 5, 1, '2026-06-07 23:10:15', '2026-06-07 16:27:03', '2026-06-05 23:10:15', '2026-10-05 08:32:12'),
	(17, 'TCK-01017', 4, NULL, 6, 'Impresora no imprime en red', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'resuelto', 'telefono', 5, 1, '2026-09-10 21:57:29', '2026-09-10 19:04:41', '2026-09-08 21:57:29', '2026-10-05 08:32:12'),
	(18, 'TCK-01018', 5, NULL, 7, 'Posible correo de phishing recibido', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'resuelto', 'correo', 6, 1, '2026-05-24 06:13:48', '2026-05-25 04:47:24', '2026-05-22 06:13:48', '2026-10-05 08:32:12'),
	(19, 'TCK-01019', 3, NULL, 4, 'Alerta de temperatura en rack', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'baja', 'cerrado', 'correo', 6, 1, '2026-07-18 23:15:44', '2026-07-16 23:44:32', '2026-07-15 23:15:44', '2026-10-05 08:32:12'),
	(20, 'TCK-01020', 5, NULL, 4, 'Alerta de temperatura en rack', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'correo', 5, 1, '2026-06-19 06:25:41', '2026-06-18 09:47:17', '2026-06-17 06:25:41', '2026-10-05 08:32:12'),
	(21, 'TCK-01021', 5, NULL, 2, 'Instalación de Office 365', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'cerrado', 'telefono', 3, 1, '2026-08-01 08:47:07', '2026-08-01 02:47:07', '2026-08-01 00:47:07', '2026-10-05 08:32:12'),
	(22, 'TCK-01022', 5, NULL, 2, 'Excel se cierra al abrir archivos grandes', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'web', 4, 1, '2026-07-01 08:02:27', '2026-07-02 01:48:03', '2026-06-29 08:02:27', '2026-10-05 08:32:12'),
	(23, 'TCK-01023', 8, NULL, 1, 'Reemplazo de disco duro por SSD', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'resuelto', 'correo', 4, 1, '2026-09-12 00:22:29', '2026-09-11 15:00:53', '2026-09-11 00:22:29', '2026-10-05 08:32:12'),
	(24, 'TCK-01024', 8, NULL, 5, 'Alta de nuevo usuario de correo', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'resuelto', 'whatsapp', 5, 1, '2026-10-01 08:12:49', '2026-09-30 13:29:37', '2026-09-29 08:12:49', '2026-10-05 08:32:12'),
	(25, 'TCK-01025', 1, NULL, 6, 'Cambio de tóner', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'web', 5, 1, '2026-09-05 01:18:24', '2026-09-04 19:32:48', '2026-09-03 01:18:24', '2026-10-05 08:32:12'),
	(26, 'TCK-01026', 6, NULL, 6, 'Cambio de tóner', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'resuelto', 'correo', 6, 1, '2026-08-19 02:31:52', '2026-08-18 19:34:16', '2026-08-18 02:31:52', '2026-10-05 08:32:12'),
	(27, 'TCK-01027', 3, NULL, 5, 'No puedo recibir correos', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'presencial', 6, 1, '2026-09-29 22:55:19', '2026-09-29 14:16:55', '2026-09-27 22:55:19', '2026-10-05 08:32:12'),
	(28, 'TCK-01028', 1, NULL, 3, 'Configurar VPN para trabajo remoto', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'telefono', 3, 1, '2026-06-16 01:18:40', '2026-06-14 14:16:16', '2026-06-14 01:18:40', '2026-10-05 08:32:12'),
	(29, 'TCK-01029', 1, NULL, 5, 'No puedo recibir correos', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'whatsapp', 6, 1, '2026-09-28 23:53:09', '2026-09-27 21:29:09', '2026-09-26 23:53:09', '2026-10-05 08:32:12'),
	(30, 'TCK-01030', 5, NULL, 1, 'Laptop no enciende tras corte de energía', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'presencial', 4, 1, '2026-07-30 06:00:02', '2026-07-30 08:52:50', '2026-07-28 06:00:02', '2026-10-05 08:32:12'),
	(31, 'TCK-01031', 3, NULL, 5, 'Alta de nuevo usuario de correo', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'cerrado', 'whatsapp', 6, 1, '2026-04-22 13:53:18', '2026-04-22 16:02:54', '2026-04-22 05:53:18', '2026-10-05 08:32:12'),
	(32, 'TCK-01032', 4, NULL, 4, 'Alerta de temperatura en rack', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'cerrado', 'web', 4, 1, '2026-09-16 15:23:45', '2026-09-16 09:33:21', '2026-09-16 07:23:45', '2026-10-05 08:32:12'),
	(33, 'TCK-01033', 8, NULL, 6, 'Cambio de tóner', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'telefono', 4, 1, '2026-09-17 05:55:59', '2026-09-16 05:27:11', '2026-09-15 05:55:59', '2026-10-05 08:32:12'),
	(34, 'TCK-01034', 4, NULL, 4, 'Respaldo nocturno falló', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'telefono', 4, 1, '2026-05-01 06:49:14', '2026-04-30 22:39:38', '2026-04-30 06:49:14', '2026-10-05 08:32:12'),
	(35, 'TCK-01035', 8, NULL, 2, 'Instalación de Office 365', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'correo', 3, 1, '2026-04-12 01:38:57', '2026-04-11 16:02:57', '2026-04-10 01:38:57', '2026-10-05 08:32:12'),
	(36, 'TCK-01036', 2, NULL, 3, 'Configurar VPN para trabajo remoto', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'presencial', 5, 1, '2026-05-05 23:53:37', '2026-05-05 09:58:25', '2026-05-04 23:53:37', '2026-10-05 08:32:12'),
	(37, 'TCK-01037', 4, NULL, 7, 'Bloqueo de cuenta por intentos fallidos', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'correo', 6, 1, '2026-09-04 02:01:40', '2026-09-03 18:06:28', '2026-09-03 02:01:40', '2026-10-05 08:32:12'),
	(38, 'TCK-01038', 8, NULL, 1, 'Pantalla con líneas verticales', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'baja', 'resuelto', 'presencial', 5, 1, '2026-09-21 06:10:02', '2026-09-22 00:10:02', '2026-09-18 06:10:02', '2026-10-05 08:32:12'),
	(39, 'TCK-01039', 3, NULL, 7, 'Posible correo de phishing recibido', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'cerrado', 'web', 6, 1, '2026-04-13 07:15:31', '2026-04-13 02:46:43', '2026-04-12 23:15:31', '2026-10-05 08:32:12'),
	(40, 'TCK-01040', 2, NULL, 1, 'Reemplazo de disco duro por SSD', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'cerrado', 'presencial', 3, 1, '2026-09-18 07:45:50', '2026-09-18 07:21:50', '2026-09-17 23:45:50', '2026-10-05 08:32:12'),
	(41, 'TCK-01041', 5, NULL, 3, 'Intermitencia en WiFi de recepción', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'web', 5, 1, '2026-09-19 00:15:57', '2026-09-18 11:03:57', '2026-09-18 00:15:57', '2026-10-05 08:32:12'),
	(42, 'TCK-01042', 2, NULL, 1, 'Laptop no enciende tras corte de energía', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'resuelto', 'presencial', 4, 1, '2026-06-18 06:14:50', '2026-06-17 07:12:26', '2026-06-16 06:14:50', '2026-10-05 08:32:12'),
	(43, 'TCK-01043', 1, NULL, 8, 'Anexos sin tono en oficina', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'baja', 'cerrado', 'telefono', 5, 1, '2026-09-06 00:24:14', '2026-09-04 23:55:26', '2026-09-03 00:24:14', '2026-10-05 08:32:12'),
	(44, 'TCK-01044', 6, NULL, 6, 'Atasco constante de papel', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'whatsapp', 6, 1, '2026-09-03 23:32:01', '2026-09-03 08:24:48', '2026-09-02 23:32:01', '2026-10-05 08:32:12'),
	(45, 'TCK-01045', 5, NULL, 7, 'Posible correo de phishing recibido', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'whatsapp', 5, 1, '2026-07-01 08:06:47', '2026-06-30 22:01:58', '2026-06-30 08:06:47', '2026-10-05 08:32:12'),
	(46, 'TCK-01046', 2, NULL, 2, 'Excel se cierra al abrir archivos grandes', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'presencial', 5, 1, '2026-04-17 21:43:40', '2026-04-17 06:22:04', '2026-04-15 21:43:40', '2026-10-05 08:32:12'),
	(47, 'TCK-01047', 7, NULL, 5, 'Restablecer contraseña de dominio', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'baja', 'cerrado', 'presencial', 3, 1, '2026-05-17 04:02:56', '2026-05-15 10:17:20', '2026-05-14 04:02:56', '2026-10-05 08:32:12'),
	(48, 'TCK-01048', 2, NULL, 4, 'Respaldo nocturno falló', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'presencial', 4, 1, '2026-05-28 05:48:56', '2026-05-29 06:46:32', '2026-05-26 05:48:56', '2026-10-05 08:32:12'),
	(49, 'TCK-01049', 6, NULL, 6, 'Impresora no imprime en red', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'baja', 'resuelto', 'correo', 6, 1, '2026-07-01 06:51:49', '2026-06-30 21:30:13', '2026-06-28 06:51:49', '2026-10-05 08:32:12'),
	(50, 'TCK-01050', 2, NULL, 1, 'Laptop no enciende tras corte de energía', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'telefono', 3, 1, '2026-06-03 22:17:12', '2026-06-03 05:29:12', '2026-06-01 22:17:12', '2026-10-05 08:32:12'),
	(51, 'TCK-01051', 7, NULL, 6, 'Cambio de tóner', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'cerrado', 'correo', 3, 1, '2026-04-19 15:34:48', '2026-04-19 10:22:48', '2026-04-19 07:34:48', '2026-10-05 08:32:12'),
	(52, 'TCK-01052', 2, NULL, 4, 'Alerta de temperatura en rack', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'baja', 'cerrado', 'presencial', 4, 1, '2026-08-09 05:34:02', '2026-08-08 18:02:50', '2026-08-06 05:34:02', '2026-10-05 08:32:12'),
	(53, 'TCK-01053', 5, NULL, 7, 'Bloqueo de cuenta por intentos fallidos', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'presencial', 4, 1, '2026-07-25 03:25:59', '2026-07-25 09:40:22', '2026-07-23 03:25:59', '2026-10-05 08:32:12'),
	(54, 'TCK-01054', 5, NULL, 6, 'Cambio de tóner', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'resuelto', 'telefono', 3, 1, '2026-10-05 16:19:38', '2026-10-05 08:22:12', '2026-10-05 08:19:38', '2026-10-05 08:32:12'),
	(55, 'TCK-01055', 5, NULL, 3, 'Sin internet en el segundo piso', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'correo', 6, 1, '2026-09-14 21:44:10', '2026-09-14 02:32:09', '2026-09-12 21:44:10', '2026-10-05 08:32:12'),
	(56, 'TCK-01056', 8, NULL, 2, 'Instalación de Office 365', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'cerrado', 'web', 5, 1, '2026-05-31 16:01:52', '2026-05-31 14:11:28', '2026-05-31 08:01:52', '2026-10-05 08:32:12'),
	(57, 'TCK-01057', 4, NULL, 4, 'Servidor de archivos sin espacio', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'cerrado', 'telefono', 4, 1, '2026-06-07 08:56:35', '2026-06-07 06:32:35', '2026-06-07 00:56:35', '2026-10-05 08:32:12'),
	(58, 'TCK-01058', 3, NULL, 3, 'Intermitencia en WiFi de recepción', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'presencial', 6, 1, '2026-06-11 07:15:19', '2026-06-11 04:51:19', '2026-06-10 07:15:19', '2026-10-05 08:32:12'),
	(59, 'TCK-01059', 6, NULL, 4, 'Respaldo nocturno falló', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'baja', 'cerrado', 'whatsapp', 5, 1, '2026-08-15 06:09:56', '2026-08-14 10:43:32', '2026-08-12 06:09:56', '2026-10-05 08:32:12'),
	(60, 'TCK-01060', 3, NULL, 2, 'Sistema ERP muy lento', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'presencial', 4, 1, '2026-05-25 02:54:12', '2026-05-24 18:44:36', '2026-05-23 02:54:12', '2026-10-05 08:32:12'),
	(61, 'TCK-01061', 7, NULL, 2, 'Instalación de Office 365', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'cerrado', 'telefono', 3, 1, '2026-06-02 14:11:25', '2026-06-02 09:42:37', '2026-06-02 06:11:25', '2026-10-05 08:32:12'),
	(62, 'TCK-01062', 1, NULL, 3, 'Sin internet en el segundo piso', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'cerrado', 'presencial', 3, 1, '2026-08-17 15:36:24', '2026-08-17 13:50:48', '2026-08-17 07:36:24', '2026-10-05 08:32:12'),
	(63, 'TCK-01063', 7, NULL, 7, 'Bloqueo de cuenta por intentos fallidos', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'cerrado', 'web', 5, 1, '2026-08-18 06:46:37', '2026-08-18 05:20:13', '2026-08-17 22:46:37', '2026-10-05 08:32:12'),
	(64, 'TCK-01064', 2, NULL, 7, 'Bloqueo de cuenta por intentos fallidos', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'whatsapp', 5, 1, '2026-09-13 07:12:56', '2026-09-12 07:41:44', '2026-09-11 07:12:56', '2026-10-05 08:32:12'),
	(65, 'TCK-01065', 2, NULL, 1, 'Pantalla con líneas verticales', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'en_proceso', 'web', 6, 1, '2026-10-05 04:23:22', NULL, '2026-10-03 04:23:22', '2026-10-05 08:32:12'),
	(66, 'TCK-01066', 7, NULL, 7, 'Posible correo de phishing recibido', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'presencial', 6, 1, '2026-08-10 04:51:04', '2026-08-09 17:19:52', '2026-08-08 04:51:04', '2026-10-05 08:32:12'),
	(67, 'TCK-01067', 6, NULL, 5, 'Restablecer contraseña de dominio', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'abierto', 'web', 5, 1, '2026-10-02 03:27:24', NULL, '2026-09-30 03:27:24', '2026-10-05 08:32:12'),
	(68, 'TCK-01068', 6, NULL, 4, 'Alerta de temperatura en rack', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'presencial', 5, 1, '2026-04-30 02:20:26', '2026-04-29 14:49:14', '2026-04-28 02:20:26', '2026-10-05 08:32:12'),
	(69, 'TCK-01069', 8, NULL, 2, 'Instalación de Office 365', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'baja', 'cerrado', 'web', 3, 1, '2026-05-23 05:52:32', '2026-05-21 14:59:44', '2026-05-20 05:52:32', '2026-10-05 08:32:12'),
	(70, 'TCK-01070', 5, NULL, 1, 'Pantalla con líneas verticales', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'telefono', 3, 1, '2026-05-23 22:26:22', '2026-05-23 12:07:10', '2026-05-22 22:26:22', '2026-10-05 08:32:12'),
	(71, 'TCK-01071', 8, NULL, 4, 'Alerta de temperatura en rack', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'correo', 6, 1, '2026-07-05 03:37:49', '2026-07-04 08:54:37', '2026-07-03 03:37:49', '2026-10-05 08:32:12'),
	(72, 'TCK-01072', 5, NULL, 5, 'Alta de nuevo usuario de correo', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'correo', 6, 1, '2026-09-22 07:33:59', '2026-09-22 21:57:59', '2026-09-21 07:33:59', '2026-10-05 08:32:12'),
	(73, 'TCK-01073', 3, NULL, 1, 'Reemplazo de disco duro por SSD', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'web', 4, 1, '2026-09-23 23:23:28', '2026-09-22 16:40:16', '2026-09-21 23:23:28', '2026-10-05 08:32:12'),
	(74, 'TCK-01074', 8, NULL, 5, 'No puedo recibir correos', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'resuelto', 'telefono', 5, 1, '2026-10-07 01:35:28', '2026-10-05 08:22:12', '2026-10-05 01:35:28', '2026-10-05 08:32:12'),
	(75, 'TCK-01075', 5, NULL, 4, 'Respaldo nocturno falló', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'en_proceso', 'correo', 6, 1, '2026-10-05 09:17:27', NULL, '2026-10-05 01:17:27', '2026-10-05 08:32:12'),
	(76, 'TCK-01076', 1, NULL, 7, 'Bloqueo de cuenta por intentos fallidos', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'baja', 'cerrado', 'correo', 5, 1, '2026-08-04 05:03:31', '2026-08-03 17:32:19', '2026-08-01 05:03:31', '2026-10-05 08:32:12'),
	(77, 'TCK-01077', 7, NULL, 7, 'Posible correo de phishing recibido', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'cerrado', 'whatsapp', 4, 1, '2026-09-24 14:19:16', '2026-09-24 10:28:52', '2026-09-24 06:19:16', '2026-10-05 08:32:12'),
	(78, 'TCK-01078', 3, NULL, 6, 'Impresora no imprime en red', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'whatsapp', 4, 1, '2026-08-29 21:42:20', '2026-08-29 06:49:31', '2026-08-27 21:42:20', '2026-10-05 08:32:12'),
	(79, 'TCK-01079', 2, NULL, 5, 'Restablecer contraseña de dominio', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'presencial', 4, 1, '2026-09-07 08:05:59', '2026-09-06 19:22:47', '2026-09-06 08:05:59', '2026-10-05 08:32:12'),
	(80, 'TCK-01080', 3, NULL, 3, 'Configurar VPN para trabajo remoto', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'baja', 'cerrado', 'presencial', 6, 1, '2026-06-29 06:37:58', '2026-06-27 02:04:22', '2026-06-26 06:37:58', '2026-10-05 08:32:12'),
	(81, 'TCK-01081', 5, NULL, 8, 'Anexos sin tono en oficina', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'baja', 'cerrado', 'telefono', 5, 1, '2026-08-06 07:07:21', '2026-08-05 09:31:21', '2026-08-03 07:07:21', '2026-10-05 08:32:12'),
	(82, 'TCK-01082', 8, NULL, 8, 'Anexos sin tono en oficina', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'whatsapp', 5, 1, '2026-06-15 23:59:43', '2026-06-15 05:16:31', '2026-06-13 23:59:43', '2026-10-05 08:32:12'),
	(83, 'TCK-01083', 5, NULL, 6, 'Cambio de tóner', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'correo', 6, 1, '2026-08-01 01:15:58', '2026-07-31 12:03:58', '2026-07-31 01:15:58', '2026-10-05 08:32:12'),
	(84, 'TCK-01084', 8, NULL, 2, 'Instalación de Office 365', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'presencial', 6, 1, '2026-04-24 22:38:47', '2026-04-24 05:50:47', '2026-04-22 22:38:47', '2026-10-05 08:32:12'),
	(85, 'TCK-01085', 5, NULL, 7, 'Posible correo de phishing recibido', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'whatsapp', 5, 1, '2026-08-04 22:44:04', '2026-08-04 00:39:16', '2026-08-02 22:44:04', '2026-10-05 08:32:12'),
	(86, 'TCK-01086', 5, NULL, 4, 'Respaldo nocturno falló', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'resuelto', 'web', 6, 1, '2026-10-06 23:44:49', '2026-10-05 08:22:12', '2026-10-04 23:44:49', '2026-10-05 08:32:12'),
	(87, 'TCK-01087', 1, NULL, 6, 'Atasco constante de papel', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'web', 4, 1, '2026-07-06 22:00:50', '2026-07-06 11:12:50', '2026-07-05 22:00:50', '2026-10-05 08:32:12'),
	(88, 'TCK-01088', 7, NULL, 4, 'Alerta de temperatura en rack', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'resuelto', 'whatsapp', 6, 1, '2026-09-16 21:35:01', '2026-09-16 05:44:37', '2026-09-14 21:35:01', '2026-10-05 08:32:12'),
	(89, 'TCK-01089', 1, NULL, 4, 'Respaldo nocturno falló', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'correo', 5, 1, '2026-09-16 21:29:40', '2026-09-17 03:15:16', '2026-09-15 21:29:40', '2026-10-05 08:32:12'),
	(90, 'TCK-01090', 2, NULL, 1, 'Laptop no enciende tras corte de energía', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'whatsapp', 5, 1, '2026-09-20 22:05:39', '2026-09-20 07:41:38', '2026-09-18 22:05:39', '2026-10-05 08:32:12'),
	(91, 'TCK-01091', 7, NULL, 6, 'Cambio de tóner', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'cerrado', 'web', 5, 1, '2026-09-09 16:30:16', '2026-09-09 12:59:04', '2026-09-09 08:30:16', '2026-10-05 08:32:12'),
	(92, 'TCK-01092', 6, NULL, 4, 'Respaldo nocturno falló', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'telefono', 5, 1, '2026-06-01 02:09:04', '2026-05-31 00:42:40', '2026-05-30 02:09:04', '2026-10-05 08:32:12'),
	(93, 'TCK-01093', 2, NULL, 5, 'No puedo recibir correos', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'abierto', 'correo', 4, 1, '2026-10-04 07:20:49', NULL, '2026-10-03 23:20:49', '2026-10-05 08:32:12'),
	(94, 'TCK-01094', 5, NULL, 3, 'Intermitencia en WiFi de recepción', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'presencial', 4, 1, '2026-07-10 03:14:24', '2026-07-08 20:59:59', '2026-07-08 03:14:24', '2026-10-05 08:32:12'),
	(95, 'TCK-01095', 1, NULL, 2, 'Licencia de antivirus vencida', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'resuelto', 'presencial', 3, 1, '2026-10-03 22:30:31', '2026-10-02 22:30:31', '2026-10-01 22:30:31', '2026-10-05 08:32:12'),
	(96, 'TCK-01096', 3, NULL, 6, 'Cambio de tóner', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'baja', 'cerrado', 'telefono', 6, 1, '2026-08-07 02:51:32', '2026-08-05 06:56:20', '2026-08-04 02:51:32', '2026-10-05 08:32:12'),
	(97, 'TCK-01097', 2, NULL, 6, 'Cambio de tóner', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'cerrado', 'presencial', 5, 1, '2026-07-29 09:25:05', '2026-07-29 08:56:17', '2026-07-29 01:25:05', '2026-10-05 08:32:12'),
	(98, 'TCK-01098', 3, NULL, 2, 'Licencia de antivirus vencida', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'abierto', 'correo', 5, 1, '2026-10-05 23:37:33', NULL, '2026-10-04 23:37:33', '2026-10-05 08:32:12'),
	(99, 'TCK-01099', 2, NULL, 2, 'Instalación de Office 365', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'telefono', 4, 1, '2026-05-03 23:09:55', '2026-05-03 20:17:07', '2026-05-01 23:09:55', '2026-10-05 08:32:12'),
	(100, 'TCK-01100', 4, NULL, 5, 'Restablecer contraseña de dominio', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'correo', 4, 1, '2026-07-25 01:13:21', '2026-07-24 10:06:08', '2026-07-24 01:13:21', '2026-10-05 08:32:12'),
	(101, 'TCK-01101', 1, NULL, 2, 'Instalación de Office 365', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'telefono', 4, 1, '2026-08-16 01:51:31', '2026-08-15 22:01:07', '2026-08-15 01:51:31', '2026-10-05 08:32:12'),
	(102, 'TCK-01102', 3, NULL, 7, 'Posible correo de phishing recibido', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'web', 6, 1, '2026-10-03 03:14:13', '2026-10-01 16:40:37', '2026-10-01 03:14:13', '2026-10-05 08:32:12'),
	(103, 'TCK-01103', 4, NULL, 4, 'Alerta de temperatura en rack', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'resuelto', 'presencial', 3, 1, '2026-06-16 11:25:55', '2026-06-16 08:23:31', '2026-06-16 03:25:55', '2026-10-05 08:32:12'),
	(104, 'TCK-01104', 3, NULL, 6, 'Cambio de tóner', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'whatsapp', 6, 1, '2026-07-15 07:28:34', '2026-07-14 16:06:58', '2026-07-13 07:28:34', '2026-10-05 08:32:12'),
	(105, 'TCK-01105', 3, NULL, 5, 'Alta de nuevo usuario de correo', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'resuelto', 'telefono', 3, 1, '2026-08-05 23:44:20', '2026-08-06 14:37:08', '2026-08-03 23:44:20', '2026-10-05 08:32:12'),
	(106, 'TCK-01106', 4, NULL, 6, 'Impresora no imprime en red', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'whatsapp', 6, 1, '2026-09-09 22:25:44', '2026-09-09 09:13:44', '2026-09-08 22:25:44', '2026-10-05 08:32:12'),
	(107, 'TCK-01107', 3, NULL, 6, 'Cambio de tóner', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'cerrado', 'web', 5, 1, '2026-06-11 09:24:36', '2026-06-11 04:31:48', '2026-06-11 01:24:36', '2026-10-05 08:32:12'),
	(108, 'TCK-01108', 1, NULL, 5, 'Restablecer contraseña de dominio', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'baja', 'en_proceso', 'telefono', 4, 1, '2026-10-02 21:34:49', NULL, '2026-09-29 21:34:49', '2026-10-05 08:32:12'),
	(109, 'TCK-01109', 3, NULL, 4, 'Alerta de temperatura en rack', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'baja', 'resuelto', 'web', 3, 1, '2026-09-01 22:30:03', '2026-09-01 00:54:03', '2026-08-29 22:30:03', '2026-10-05 08:32:12'),
	(110, 'TCK-01110', 3, NULL, 5, 'No puedo recibir correos', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'abierto', 'whatsapp', 6, 1, '2026-09-29 13:36:34', NULL, '2026-09-29 05:36:34', '2026-10-05 08:32:12'),
	(111, 'TCK-01111', 4, NULL, 1, 'Reemplazo de disco duro por SSD', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'telefono', 6, 1, '2026-05-23 23:28:55', '2026-05-22 19:38:31', '2026-05-21 23:28:55', '2026-10-05 08:32:12'),
	(112, 'TCK-01112', 5, NULL, 5, 'No puedo recibir correos', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'cerrado', 'telefono', 6, 1, '2026-07-15 15:07:41', '2026-07-15 12:58:05', '2026-07-15 07:07:41', '2026-10-05 08:32:12'),
	(113, 'TCK-01113', 8, NULL, 4, 'Servidor de archivos sin espacio', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'en_proceso', 'telefono', 6, 1, '2026-05-05 02:00:18', NULL, '2026-05-04 02:00:18', '2026-10-05 08:32:12'),
	(114, 'TCK-01114', 5, NULL, 2, 'Sistema ERP muy lento', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'resuelto', 'web', 6, 1, '2026-08-10 04:02:38', '2026-08-09 13:09:49', '2026-08-08 04:02:38', '2026-10-05 08:32:12'),
	(115, 'TCK-01115', 4, NULL, 3, 'Intermitencia en WiFi de recepción', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'abierto', 'correo', 5, 1, '2026-10-06 01:38:18', NULL, '2026-10-05 01:38:18', '2026-10-05 08:32:12'),
	(116, 'TCK-01116', 6, NULL, 2, 'Sistema ERP muy lento', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'correo', 4, 1, '2026-07-29 07:59:07', '2026-07-28 18:03:55', '2026-07-27 07:59:07', '2026-10-05 08:32:12'),
	(117, 'TCK-01117', 4, NULL, 2, 'Sistema ERP muy lento', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'cerrado', 'correo', 6, 1, '2026-06-01 10:44:49', '2026-06-01 07:32:49', '2026-06-01 02:44:49', '2026-10-05 08:32:12'),
	(118, 'TCK-01118', 5, NULL, 3, 'Sin internet en el segundo piso', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'whatsapp', 5, 1, '2026-09-17 00:40:31', '2026-09-16 13:23:43', '2026-09-16 00:40:31', '2026-10-05 08:32:12'),
	(119, 'TCK-01119', 2, NULL, 4, 'Alerta de temperatura en rack', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'telefono', 4, 1, '2026-06-02 05:50:32', '2026-06-01 13:02:32', '2026-05-31 05:50:32', '2026-10-05 08:32:12'),
	(120, 'TCK-01120', 4, NULL, 3, 'Intermitencia en WiFi de recepción', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'web', 4, 1, '2026-07-20 05:15:49', '2026-07-18 22:03:48', '2026-07-18 05:15:49', '2026-10-05 08:32:12'),
	(121, 'TCK-01121', 4, NULL, 6, 'Cambio de tóner', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'cerrado', 'telefono', 5, 1, '2026-07-05 11:58:51', '2026-07-05 09:49:15', '2026-07-05 03:58:51', '2026-10-05 08:32:12'),
	(122, 'TCK-01122', 4, NULL, 2, 'Licencia de antivirus vencida', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'cerrado', 'correo', 3, 1, '2026-04-16 07:06:46', '2026-04-16 05:45:10', '2026-04-15 23:06:46', '2026-10-05 08:32:12'),
	(123, 'TCK-01123', 3, NULL, 7, 'Posible correo de phishing recibido', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'cerrado', 'whatsapp', 5, 1, '2026-05-13 06:38:36', '2026-05-13 11:17:00', '2026-05-12 22:38:36', '2026-10-05 08:32:12'),
	(124, 'TCK-01124', 6, NULL, 8, 'Anexos sin tono en oficina', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'web', 5, 1, '2026-09-20 01:11:42', '2026-09-19 17:30:54', '2026-09-19 01:11:42', '2026-10-05 08:32:12'),
	(125, 'TCK-01125', 4, NULL, 6, 'Impresora no imprime en red', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'resuelto', 'whatsapp', 4, 1, '2026-07-02 06:01:15', '2026-07-02 02:10:51', '2026-07-01 06:01:15', '2026-10-05 08:32:12'),
	(126, 'TCK-01126', 2, NULL, 1, 'Pantalla con líneas verticales', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'resuelto', 'correo', 5, 1, '2026-04-26 00:40:58', '2026-04-26 12:12:09', '2026-04-25 00:40:58', '2026-10-05 08:32:12'),
	(127, 'TCK-01127', 5, NULL, 5, 'Alta de nuevo usuario de correo', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'web', 5, 1, '2026-07-12 05:07:03', '2026-07-11 21:11:51', '2026-07-11 05:07:03', '2026-10-05 08:32:12'),
	(128, 'TCK-01128', 6, NULL, 8, 'Configurar central IP', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'abierto', 'telefono', 3, 1, '2026-10-04 07:18:11', NULL, '2026-10-03 07:18:11', '2026-10-05 08:32:12'),
	(129, 'TCK-01129', 6, NULL, 5, 'Restablecer contraseña de dominio', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'resuelto', 'web', 5, 1, '2026-09-27 04:26:31', '2026-09-27 01:19:19', '2026-09-26 04:26:31', '2026-10-05 08:32:12'),
	(130, 'TCK-01130', 5, NULL, 1, 'Reemplazo de disco duro por SSD', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'web', 6, 1, '2026-05-08 06:54:38', '2026-05-08 02:06:38', '2026-05-06 06:54:38', '2026-10-05 08:32:12'),
	(131, 'TCK-01131', 1, NULL, 8, 'Anexos sin tono en oficina', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'whatsapp', 5, 1, '2026-09-29 01:21:13', '2026-09-28 17:40:25', '2026-09-28 01:21:13', '2026-10-05 08:32:12'),
	(132, 'TCK-01132', 2, NULL, 1, 'Pantalla con líneas verticales', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'baja', 'cerrado', 'presencial', 4, 1, '2026-06-18 04:29:19', '2026-06-18 23:55:43', '2026-06-15 04:29:19', '2026-10-05 08:32:12'),
	(133, 'TCK-01133', 8, NULL, 7, 'Bloqueo de cuenta por intentos fallidos', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'baja', 'cerrado', 'correo', 6, 1, '2026-08-02 06:01:26', '2026-08-01 12:01:26', '2026-07-30 06:01:26', '2026-10-05 08:32:12'),
	(134, 'TCK-01134', 4, NULL, 7, 'Posible correo de phishing recibido', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'cerrado', 'presencial', 3, 1, '2026-07-11 11:52:37', '2026-07-11 11:28:37', '2026-07-11 03:52:37', '2026-10-05 08:32:12'),
	(135, 'TCK-01135', 7, NULL, 6, 'Impresora no imprime en red', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'resuelto', 'correo', 3, 1, '2026-04-28 00:46:12', '2026-04-27 13:58:12', '2026-04-27 00:46:12', '2026-10-05 08:32:12'),
	(136, 'TCK-01136', 8, NULL, 3, 'Sin internet en el segundo piso', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'whatsapp', 6, 1, '2026-08-30 07:50:50', '2026-08-30 20:19:38', '2026-08-29 07:50:50', '2026-10-05 08:32:12'),
	(137, 'TCK-01137', 1, NULL, 7, 'Posible correo de phishing recibido', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'resuelto', 'presencial', 4, 1, '2026-09-28 03:53:07', '2026-09-27 17:33:55', '2026-09-27 03:53:07', '2026-10-05 08:32:12'),
	(138, 'TCK-01138', 4, NULL, 5, 'No puedo recibir correos', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'baja', 'cerrado', 'presencial', 4, 1, '2026-08-30 00:31:45', '2026-08-28 21:10:09', '2026-08-27 00:31:45', '2026-10-05 08:32:12'),
	(139, 'TCK-01139', 1, NULL, 1, 'Pantalla con líneas verticales', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'cerrado', 'telefono', 5, 1, '2026-04-30 14:30:15', '2026-04-30 10:20:39', '2026-04-30 06:30:15', '2026-10-05 08:32:12'),
	(140, 'TCK-01140', 8, NULL, 7, 'Posible correo de phishing recibido', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'resuelto', 'web', 3, 1, '2026-07-10 01:40:03', '2026-07-09 13:40:03', '2026-07-08 01:40:03', '2026-10-05 08:32:12'),
	(141, 'TCK-01141', 3, NULL, 6, 'Cambio de tóner', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'cerrado', 'telefono', 3, 1, '2026-08-20 05:30:42', '2026-08-20 03:35:30', '2026-08-19 21:30:42', '2026-10-05 08:32:12'),
	(142, 'TCK-01142', 3, NULL, 7, 'Posible correo de phishing recibido', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'whatsapp', 3, 1, '2026-05-03 06:22:43', '2026-05-02 16:27:31', '2026-05-02 06:22:43', '2026-10-05 08:32:12'),
	(143, 'TCK-01143', 6, NULL, 4, 'Alerta de temperatura en rack', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'resuelto', 'whatsapp', 3, 1, '2026-09-03 22:39:03', '2026-09-02 18:19:51', '2026-09-01 22:39:03', '2026-10-05 08:32:12'),
	(144, 'TCK-01144', 3, NULL, 8, 'Anexos sin tono en oficina', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'presencial', 3, 1, '2026-07-21 23:58:50', '2026-07-22 05:44:26', '2026-07-20 23:58:50', '2026-10-05 08:32:12'),
	(145, 'TCK-01145', 1, NULL, 6, 'Atasco constante de papel', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'whatsapp', 3, 1, '2026-06-10 08:27:08', '2026-06-09 03:39:08', '2026-06-08 08:27:08', '2026-10-05 08:32:12'),
	(146, 'TCK-01146', 5, NULL, 3, 'Sin internet en el segundo piso', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'baja', 'cerrado', 'correo', 3, 1, '2026-08-29 07:57:08', '2026-08-27 03:23:32', '2026-08-26 07:57:08', '2026-10-05 08:32:12'),
	(147, 'TCK-01147', 7, NULL, 3, 'Configurar VPN para trabajo remoto', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'web', 6, 1, '2026-06-17 21:34:33', '2026-06-17 08:22:33', '2026-06-16 21:34:33', '2026-10-05 08:32:12'),
	(148, 'TCK-01148', 1, NULL, 2, 'Instalación de Office 365', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'telefono', 4, 1, '2026-10-05 08:01:10', '2026-10-05 08:22:12', '2026-10-03 08:01:10', '2026-10-05 08:32:12'),
	(149, 'TCK-01149', 7, NULL, 3, 'Sin internet en el segundo piso', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'whatsapp', 4, 1, '2026-07-27 22:04:33', '2026-07-28 02:09:21', '2026-07-26 22:04:33', '2026-10-05 08:32:12'),
	(150, 'TCK-01150', 6, NULL, 2, 'Licencia de antivirus vencida', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'telefono', 3, 1, '2026-08-22 06:43:38', '2026-08-21 00:00:26', '2026-08-20 06:43:38', '2026-10-05 08:32:12'),
	(151, 'TCK-01151', 4, NULL, 5, 'Restablecer contraseña de dominio', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'telefono', 3, 1, '2026-06-22 22:39:25', '2026-06-22 12:49:01', '2026-06-21 22:39:25', '2026-10-05 08:32:12'),
	(152, 'TCK-01152', 6, NULL, 1, 'Reemplazo de disco duro por SSD', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'pendiente', 'telefono', 3, 1, '2026-10-03 07:45:53', NULL, '2026-10-02 07:45:53', '2026-10-05 08:32:12'),
	(153, 'TCK-01153', 6, NULL, 5, 'Restablecer contraseña de dominio', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'cerrado', 'web', 5, 1, '2026-09-27 14:32:02', '2026-09-27 08:56:02', '2026-09-27 06:32:02', '2026-10-05 08:32:12'),
	(154, 'TCK-01154', 3, NULL, 6, 'Impresora no imprime en red', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'web', 6, 1, '2026-09-25 03:56:15', '2026-09-24 13:32:15', '2026-09-24 03:56:15', '2026-10-05 08:32:12'),
	(155, 'TCK-01155', 1, NULL, 5, 'No puedo recibir correos', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'cerrado', 'web', 4, 1, '2026-05-30 13:16:21', '2026-05-30 12:28:21', '2026-05-30 05:16:21', '2026-10-05 08:32:12'),
	(156, 'TCK-01156', 4, NULL, 6, 'Impresora no imprime en red', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'whatsapp', 3, 1, '2026-08-10 03:59:54', '2026-08-10 01:50:18', '2026-08-09 03:59:54', '2026-10-05 08:32:12'),
	(157, 'TCK-01157', 7, NULL, 5, 'Alta de nuevo usuario de correo', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'web', 6, 1, '2026-05-16 00:32:19', '2026-05-15 20:13:07', '2026-05-15 00:32:19', '2026-10-05 08:32:12'),
	(158, 'TCK-01158', 8, NULL, 7, 'Posible correo de phishing recibido', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'cerrado', 'presencial', 5, 1, '2026-08-05 12:02:37', '2026-08-05 12:41:01', '2026-08-05 04:02:37', '2026-10-05 08:32:12'),
	(159, 'TCK-01159', 8, NULL, 6, 'Impresora no imprime en red', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'telefono', 3, 1, '2026-09-29 05:55:14', '2026-09-29 04:43:13', '2026-09-28 05:55:14', '2026-10-05 08:32:12'),
	(160, 'TCK-01160', 6, NULL, 2, 'Sistema ERP muy lento', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'en_proceso', 'presencial', 4, 1, '2026-10-01 02:11:28', NULL, '2026-09-29 02:11:28', '2026-10-05 08:32:12'),
	(161, 'TCK-01161', 7, NULL, 3, 'Sin internet en el segundo piso', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'resuelto', 'correo', 4, 1, '2026-09-25 08:22:42', '2026-09-25 07:10:42', '2026-09-25 00:22:42', '2026-10-05 08:32:12'),
	(162, 'TCK-01162', 5, NULL, 6, 'Impresora no imprime en red', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'baja', 'resuelto', 'whatsapp', 5, 1, '2026-06-27 07:10:20', '2026-06-26 13:10:20', '2026-06-24 07:10:20', '2026-10-05 08:32:12'),
	(163, 'TCK-01163', 3, NULL, 5, 'Alta de nuevo usuario de correo', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'whatsapp', 4, 1, '2026-08-28 01:58:52', '2026-08-26 20:42:04', '2026-08-26 01:58:52', '2026-10-05 08:32:12'),
	(164, 'TCK-01164', 1, NULL, 8, 'Anexos sin tono en oficina', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'resuelto', 'web', 6, 1, '2026-06-28 06:56:09', '2026-06-28 02:08:09', '2026-06-26 06:56:09', '2026-10-05 08:32:12'),
	(165, 'TCK-01165', 2, NULL, 7, 'Posible correo de phishing recibido', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'resuelto', 'presencial', 3, 1, '2026-04-30 06:07:21', '2026-04-29 16:55:21', '2026-04-29 06:07:21', '2026-10-05 08:32:12'),
	(166, 'TCK-01166', 4, NULL, 7, 'Bloqueo de cuenta por intentos fallidos', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'baja', 'cerrado', 'web', 3, 1, '2026-08-09 23:49:05', '2026-08-10 21:25:05', '2026-08-06 23:49:05', '2026-10-05 08:32:12'),
	(167, 'TCK-01167', 4, NULL, 8, 'Configurar central IP', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'web', 5, 1, '2026-05-18 06:09:06', '2026-05-18 01:35:30', '2026-05-17 06:09:06', '2026-10-05 08:32:12'),
	(168, 'TCK-01168', 2, NULL, 4, 'Servidor de archivos sin espacio', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'whatsapp', 4, 1, '2026-09-23 08:14:43', '2026-09-22 16:53:07', '2026-09-21 08:14:43', '2026-10-05 08:32:12'),
	(169, 'TCK-01169', 6, NULL, 3, 'Intermitencia en WiFi de recepción', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'cerrado', 'web', 6, 1, '2026-07-17 12:17:53', '2026-07-17 06:41:53', '2026-07-17 04:17:53', '2026-10-05 08:32:12'),
	(170, 'TCK-01170', 6, NULL, 8, 'Anexos sin tono en oficina', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'telefono', 6, 1, '2026-09-21 22:34:14', '2026-09-21 13:12:38', '2026-09-20 22:34:14', '2026-10-05 08:32:12'),
	(171, 'TCK-01171', 8, NULL, 4, 'Alerta de temperatura en rack', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'correo', 3, 1, '2026-06-20 00:14:05', '2026-06-19 21:50:05', '2026-06-19 00:14:05', '2026-10-05 08:32:12'),
	(172, 'TCK-01172', 3, NULL, 3, 'Configurar VPN para trabajo remoto', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'web', 3, 1, '2026-09-29 00:34:24', '2026-09-28 16:53:36', '2026-09-27 00:34:24', '2026-10-05 08:32:12'),
	(173, 'TCK-01173', 7, NULL, 8, 'Configurar central IP', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'web', 6, 1, '2026-09-22 03:45:03', '2026-09-21 11:25:51', '2026-09-20 03:45:03', '2026-10-05 08:32:12'),
	(174, 'TCK-01174', 6, NULL, 4, 'Respaldo nocturno falló', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'correo', 4, 1, '2026-08-18 01:44:21', '2026-08-18 06:03:33', '2026-08-16 01:44:21', '2026-10-05 08:32:12'),
	(175, 'TCK-01175', 7, NULL, 8, 'Anexos sin tono en oficina', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'cerrado', 'presencial', 5, 1, '2026-07-13 14:04:21', '2026-07-13 11:21:09', '2026-07-13 06:04:21', '2026-10-05 08:32:12'),
	(176, 'TCK-01176', 4, NULL, 5, 'No puedo recibir correos', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'web', 6, 1, '2026-07-02 01:22:38', '2026-07-02 08:49:02', '2026-07-01 01:22:38', '2026-10-05 08:32:12'),
	(177, 'TCK-01177', 8, NULL, 1, 'Reemplazo de disco duro por SSD', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'cerrado', 'telefono', 5, 1, '2026-09-30 07:54:08', '2026-09-30 04:32:32', '2026-09-29 23:54:08', '2026-10-05 08:32:12'),
	(178, 'TCK-01178', 4, NULL, 5, 'No puedo recibir correos', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'resuelto', 'telefono', 4, 1, '2026-09-24 03:40:34', '2026-09-23 22:52:34', '2026-09-23 03:40:34', '2026-10-05 08:32:12'),
	(179, 'TCK-01179', 6, NULL, 4, 'Servidor de archivos sin espacio', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'resuelto', 'presencial', 3, 1, '2026-07-28 22:35:13', '2026-07-28 08:11:12', '2026-07-26 22:35:13', '2026-10-05 08:32:12'),
	(180, 'TCK-01180', 2, NULL, 3, 'Intermitencia en WiFi de recepción', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'telefono', 4, 1, '2026-09-01 02:19:43', '2026-08-31 22:00:31', '2026-08-31 02:19:43', '2026-10-05 08:32:12'),
	(181, 'TCK-01181', 1, NULL, 3, 'Configurar VPN para trabajo remoto', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'baja', 'cerrado', 'whatsapp', 4, 1, '2026-09-28 22:10:53', '2026-09-29 20:30:05', '2026-09-25 22:10:53', '2026-10-05 08:32:12'),
	(182, 'TCK-01182', 1, NULL, 3, 'Configurar VPN para trabajo remoto', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'correo', 6, 1, '2026-08-28 02:21:38', '2026-08-27 11:57:38', '2026-08-27 02:21:38', '2026-10-05 08:32:12'),
	(183, 'TCK-01183', 7, NULL, 6, 'Impresora no imprime en red', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'correo', 6, 1, '2026-04-27 01:53:50', '2026-04-26 12:13:02', '2026-04-26 01:53:50', '2026-10-05 08:32:12'),
	(184, 'TCK-01184', 8, NULL, 6, 'Atasco constante de papel', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'correo', 3, 1, '2026-06-04 05:35:34', '2026-06-04 20:13:58', '2026-06-03 05:35:34', '2026-10-05 08:32:12'),
	(185, 'TCK-01185', 7, NULL, 3, 'Sin internet en el segundo piso', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'cerrado', 'web', 3, 1, '2026-07-27 06:39:08', '2026-07-27 04:00:44', '2026-07-26 22:39:08', '2026-10-05 08:32:12'),
	(186, 'TCK-01186', 4, NULL, 6, 'Cambio de tóner', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'web', 5, 1, '2026-06-18 02:43:22', '2026-06-18 05:36:10', '2026-06-16 02:43:22', '2026-10-05 08:32:12'),
	(187, 'TCK-01187', 5, NULL, 3, 'Configurar VPN para trabajo remoto', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'cerrado', 'telefono', 3, 1, '2026-07-21 14:21:50', '2026-07-21 11:57:50', '2026-07-21 06:21:50', '2026-10-05 08:32:12'),
	(188, 'TCK-01188', 8, NULL, 6, 'Atasco constante de papel', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'cerrado', 'whatsapp', 6, 1, '2026-08-15 11:06:48', '2026-08-15 07:45:12', '2026-08-15 03:06:48', '2026-10-05 08:32:12'),
	(189, 'TCK-01189', 7, NULL, 3, 'Sin internet en el segundo piso', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'cerrado', 'presencial', 4, 1, '2026-09-08 13:13:04', '2026-09-08 08:53:52', '2026-09-08 05:13:04', '2026-10-05 08:32:12'),
	(190, 'TCK-01190', 8, NULL, 4, 'Alerta de temperatura en rack', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'resuelto', 'correo', 4, 1, '2026-09-28 01:15:29', '2026-09-27 13:58:41', '2026-09-27 01:15:29', '2026-10-05 08:32:12'),
	(191, 'TCK-01191', 8, NULL, 5, 'Restablecer contraseña de dominio', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'web', 4, 1, '2026-06-01 06:05:05', '2026-06-01 02:00:17', '2026-05-31 06:05:05', '2026-10-05 08:32:12'),
	(192, 'TCK-01192', 7, NULL, 1, 'Reemplazo de disco duro por SSD', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'cerrado', 'whatsapp', 3, 1, '2026-06-14 06:56:42', '2026-06-14 05:11:06', '2026-06-13 22:56:42', '2026-10-05 08:32:12'),
	(193, 'TCK-01193', 4, NULL, 2, 'Excel se cierra al abrir archivos grandes', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'cerrado', 'whatsapp', 6, 1, '2026-07-31 07:59:56', '2026-07-31 06:33:32', '2026-07-30 23:59:56', '2026-10-05 08:32:12'),
	(194, 'TCK-01194', 6, NULL, 6, 'Cambio de tóner', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'resuelto', 'telefono', 6, 1, '2026-07-31 10:09:53', '2026-07-31 06:48:17', '2026-07-31 02:09:53', '2026-10-05 08:32:12'),
	(195, 'TCK-01195', 5, NULL, 2, 'Excel se cierra al abrir archivos grandes', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'baja', 'cerrado', 'whatsapp', 6, 1, '2026-05-10 06:07:38', '2026-05-09 20:02:50', '2026-05-07 06:07:38', '2026-10-05 08:32:12'),
	(196, 'TCK-01196', 1, NULL, 4, 'Alerta de temperatura en rack', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'baja', 'resuelto', 'presencial', 5, 1, '2026-08-25 01:55:47', '2026-08-23 03:50:59', '2026-08-22 01:55:47', '2026-10-05 08:32:12'),
	(197, 'TCK-01197', 7, NULL, 6, 'Impresora no imprime en red', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'presencial', 6, 1, '2026-08-12 01:50:42', '2026-08-12 15:02:42', '2026-08-11 01:50:42', '2026-10-05 08:32:12'),
	(198, 'TCK-01198', 3, NULL, 5, 'Restablecer contraseña de dominio', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'resuelto', 'correo', 5, 1, '2026-08-13 01:26:10', '2026-08-12 09:50:09', '2026-08-12 01:26:10', '2026-10-05 08:32:12'),
	(199, 'TCK-01199', 7, NULL, 2, 'Licencia de antivirus vencida', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'resuelto', 'telefono', 3, 1, '2026-07-31 05:18:50', '2026-07-30 13:42:49', '2026-07-30 05:18:50', '2026-10-05 08:32:12'),
	(200, 'TCK-01200', 4, NULL, 8, 'Anexos sin tono en oficina', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'resuelto', 'correo', 4, 1, '2026-05-02 13:37:26', '2026-05-02 08:44:38', '2026-05-02 05:37:26', '2026-10-05 08:32:12'),
	(201, 'TCK-01201', 3, NULL, 3, 'Configurar VPN para trabajo remoto', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'presencial', 5, 1, '2026-09-28 03:02:55', '2026-09-27 22:43:43', '2026-09-27 03:02:55', '2026-10-05 08:32:12'),
	(202, 'TCK-01202', 7, NULL, 3, 'Intermitencia en WiFi de recepción', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'telefono', 5, 1, '2026-08-05 07:04:21', '2026-08-04 15:57:08', '2026-08-04 07:04:21', '2026-10-05 08:32:12'),
	(203, 'TCK-01203', 3, NULL, 1, 'Laptop no enciende tras corte de energía', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'baja', 'cerrado', 'telefono', 5, 1, '2026-09-15 06:15:56', '2026-09-13 23:18:20', '2026-09-12 06:15:56', '2026-10-05 08:32:12'),
	(204, 'TCK-01204', 7, NULL, 4, 'Respaldo nocturno falló', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'web', 4, 1, '2026-08-11 06:40:47', '2026-08-10 22:16:47', '2026-08-10 06:40:47', '2026-10-05 08:32:12'),
	(205, 'TCK-01205', 6, NULL, 7, 'Bloqueo de cuenta por intentos fallidos', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'web', 3, 1, '2026-09-20 21:39:36', '2026-09-20 09:39:36', '2026-09-18 21:39:36', '2026-10-05 08:32:12'),
	(206, 'TCK-01206', 7, NULL, 7, 'Posible correo de phishing recibido', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'resuelto', 'presencial', 5, 1, '2026-09-05 01:09:23', '2026-09-04 09:04:35', '2026-09-04 01:09:23', '2026-10-05 08:32:12'),
	(207, 'TCK-01207', 2, NULL, 2, 'Sistema ERP muy lento', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'cerrado', 'presencial', 6, 1, '2026-09-22 09:59:51', '2026-09-22 06:23:51', '2026-09-22 01:59:51', '2026-10-05 08:32:12'),
	(208, 'TCK-01208', 7, NULL, 2, 'Excel se cierra al abrir archivos grandes', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'web', 3, 1, '2026-06-16 23:32:40', '2026-06-17 14:25:28', '2026-06-14 23:32:40', '2026-10-05 08:32:12'),
	(209, 'TCK-01209', 5, NULL, 6, 'Cambio de tóner', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'telefono', 3, 1, '2026-05-07 21:50:50', '2026-05-07 14:53:14', '2026-05-06 21:50:50', '2026-10-05 08:32:12'),
	(210, 'TCK-01210', 7, NULL, 6, 'Impresora no imprime en red', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'cerrado', 'correo', 5, 1, '2026-07-30 02:34:24', '2026-07-29 20:19:59', '2026-07-29 02:34:24', '2026-10-05 08:32:12'),
	(211, 'TCK-01211', 5, NULL, 8, 'Configurar central IP', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'resuelto', 'whatsapp', 4, 1, '2026-10-06 01:49:06', '2026-10-05 08:22:12', '2026-10-04 01:49:06', '2026-10-05 08:32:12'),
	(212, 'TCK-01212', 4, NULL, 2, 'Licencia de antivirus vencida', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'baja', 'cerrado', 'telefono', 6, 1, '2026-07-11 06:19:59', '2026-07-09 19:03:11', '2026-07-08 06:19:59', '2026-10-05 08:32:12'),
	(213, 'TCK-01213', 2, NULL, 4, 'Respaldo nocturno falló', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'whatsapp', 3, 1, '2026-06-20 05:51:59', '2026-06-20 02:59:11', '2026-06-18 05:51:59', '2026-10-05 08:32:12'),
	(214, 'TCK-01214', 6, NULL, 6, 'Impresora no imprime en red', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'baja', 'resuelto', 'whatsapp', 5, 1, '2026-09-06 00:29:32', '2026-09-05 00:43:56', '2026-09-03 00:29:32', '2026-10-05 08:32:12'),
	(215, 'TCK-01215', 1, NULL, 3, 'Configurar VPN para trabajo remoto', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'en_proceso', 'whatsapp', 3, 1, '2026-06-02 07:52:54', NULL, '2026-06-01 07:52:54', '2026-10-05 08:32:12'),
	(216, 'TCK-01216', 8, NULL, 7, 'Posible correo de phishing recibido', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'cerrado', 'web', 5, 1, '2026-09-06 07:02:18', '2026-09-05 08:28:42', '2026-09-04 07:02:18', '2026-10-05 08:32:12'),
	(217, 'TCK-01217', 3, NULL, 5, 'No puedo recibir correos', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'resuelto', 'web', 3, 1, '2026-04-22 08:19:03', '2026-04-21 16:14:15', '2026-04-21 08:19:03', '2026-10-05 08:32:12'),
	(218, 'TCK-01218', 1, NULL, 5, 'Alta de nuevo usuario de correo', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'cerrado', 'web', 3, 1, '2026-08-03 11:28:47', '2026-08-03 10:55:11', '2026-08-03 03:28:47', '2026-10-05 08:32:12'),
	(219, 'TCK-01219', 1, NULL, 3, 'Configurar VPN para trabajo remoto', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'abierto', 'web', 4, 1, '2026-10-06 00:32:12', NULL, '2026-10-05 00:32:12', '2026-10-05 08:32:12'),
	(220, 'TCK-01220', 4, NULL, 4, 'Alerta de temperatura en rack', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'pendiente', 'presencial', 5, 1, '2026-10-06 06:32:12', NULL, '2026-10-05 06:32:12', '2026-10-05 08:32:12'),
	(221, 'TCK-01221', 6, NULL, 7, 'Posible correo de phishing recibido', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'pendiente', 'presencial', 6, 1, '2026-10-06 02:32:12', NULL, '2026-10-05 02:32:12', '2026-10-05 08:32:12'),
	(222, 'TCK-01222', 1, NULL, 5, 'Alta de nuevo usuario de correo', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'abierto', 'telefono', 5, 1, '2026-10-06 02:32:12', NULL, '2026-10-05 02:32:12', '2026-10-05 08:32:12'),
	(223, 'TCK-01223', 5, NULL, 1, 'Pantalla con líneas verticales', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'en_proceso', 'presencial', 4, 1, '2026-10-06 06:32:12', NULL, '2026-10-05 06:32:12', '2026-10-05 08:32:12'),
	(224, 'TCK-01224', 7, NULL, 6, 'Impresora no imprime en red', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'abierto', 'correo', 5, 1, '2026-10-06 08:32:12', NULL, '2026-10-05 08:32:12', '2026-10-05 08:32:12'),
	(225, 'TCK-01225', 4, NULL, 3, 'Sin internet en el segundo piso', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'alta', 'en_proceso', 'correo', 6, 1, '2026-10-06 08:32:12', NULL, '2026-10-05 08:32:12', '2026-10-05 08:32:12'),
	(226, 'TCK-01226', 2, NULL, 2, 'Licencia de antivirus vencida', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'pendiente', 'web', 3, 1, '2026-10-06 02:32:12', NULL, '2026-10-05 02:32:12', '2026-10-05 08:32:12'),
	(227, 'TCK-01227', 1, NULL, 5, 'No puedo recibir correos', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'critica', 'abierto', 'presencial', 5, 1, '2026-10-06 00:32:12', NULL, '2026-10-05 00:32:12', '2026-10-05 08:32:12'),
	(228, 'TCK-01228', 5, NULL, 2, 'Instalación de Office 365', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'media', 'pendiente', 'presencial', 5, 1, '2026-10-06 04:32:12', NULL, '2026-10-05 04:32:12', '2026-10-05 08:32:12'),
	(229, 'TCK-01229', 4, NULL, 2, 'Licencia de antivirus vencida', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'baja', 'resuelto', 'correo', 6, 1, '2026-10-06 00:32:12', '2026-10-05 08:12:12', '2026-10-05 00:32:12', '2026-10-05 08:32:12'),
	(230, 'TCK-01230', 8, NULL, 4, 'Alerta de temperatura en rack', 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.', 'baja', 'resuelto', 'whatsapp', 5, 1, '2026-10-06 06:32:12', '2026-10-05 08:12:12', '2026-10-05 06:32:12', '2026-10-05 08:32:12');

-- Volcando estructura para tabla sys_gestion_ti.ticket_comentarios
CREATE TABLE IF NOT EXISTS `ticket_comentarios` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` int unsigned NOT NULL,
  `usuario_id` int unsigned DEFAULT NULL,
  `comentario` text NOT NULL,
  `interno` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  CONSTRAINT `ticket_comentarios_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla sys_gestion_ti.ticket_comentarios: ~0 rows (aproximadamente)
DELETE FROM `ticket_comentarios`;

-- Volcando estructura para tabla sys_gestion_ti.usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `rol_id` tinyint unsigned NOT NULL,
  `nombres` varchar(80) NOT NULL,
  `apellidos` varchar(80) NOT NULL,
  `usuario` varchar(40) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password` varchar(255) NOT NULL,
  `telefono` varchar(25) DEFAULT NULL,
  `cargo` varchar(80) DEFAULT NULL,
  `avatar_color` varchar(9) NOT NULL DEFAULT '#4f46e5',
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  `ultimo_acceso` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `cliente_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`),
  UNIQUE KEY `email` (`email`),
  KEY `rol_id` (`rol_id`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla sys_gestion_ti.usuarios: ~7 rows (aproximadamente)
DELETE FROM `usuarios`;
INSERT INTO `usuarios` (`id`, `rol_id`, `nombres`, `apellidos`, `usuario`, `email`, `password`, `telefono`, `cargo`, `avatar_color`, `estado`, `ultimo_acceso`, `created_at`, `cliente_id`) VALUES
	(1, 1, 'Victor', 'Ramírez', 'admin', 'admin@techdesk.pe', '$2y$10$Rd17gLZggrkoNSGuyutBC.krgun5ZI8c0uQLeEplkQLU7QEwMxS7m', NULL, 'Jefe de Soporte TI', '#4f46e5', 1, '2026-10-05 13:58:30', '2026-10-05 13:32:11', NULL),
	(2, 2, 'Lucía', 'Fernández', 'lfernandez', 'lucia@techdesk.pe', '$2y$10$A538oLJPMHt4glH53QfHWe1JGIHgXf/4F6sMZ9dJ5lR.d1oyFrqpK', NULL, 'Supervisora de Servicio', '#0ea5e9', 1, NULL, '2026-10-05 13:32:11', NULL),
	(3, 3, 'Carlos', 'Mendoza', 'cmendoza', 'carlos@techdesk.pe', '$2y$10$woWajWh47XLqP5O9jQBidePqrk8SJ.PowaFSimqaF/IB5eeGIqwP2', NULL, 'Técnico Sr. Redes', '#10b981', 1, NULL, '2026-10-05 13:32:11', NULL),
	(4, 3, 'Andrea', 'Quispe', 'aquispe', 'andrea@techdesk.pe', '$2y$10$J.Jxt5b707g8XYdGal7ZjelYcMeQ.gY/GDItPdlfmQSjtwShN5UuC', NULL, 'Técnico de Campo', '#f59e0b', 1, NULL, '2026-10-05 13:32:11', NULL),
	(5, 3, 'Jorge', 'Salazar', 'jsalazar', 'jorge@techdesk.pe', '$2y$10$/4q0if5.ssueZSmdmlQ/ZeppJM4Zc33LRuB8Lx0UUnxMzi9/RkaLu', NULL, 'Técnico Servidores', '#ef4444', 1, NULL, '2026-10-05 13:32:11', NULL),
	(6, 3, 'María', 'Torres', 'mtorres', 'maria@techdesk.pe', '$2y$10$My0ldWavHpcAOyXt/QUPo.8hm/xSCq2BITAbgunr.x.FtWrQqWBQG', NULL, 'Técnico Help Desk', '#8b5cf6', 1, NULL, '2026-10-05 13:32:11', NULL),
	(7, 5, 'Valeria', 'Castro', 'vcastro', 'valeria@techdesk.pe', '$2y$10$.h19VOAthiKS25kTbf1Dz.Bb9Mv6y4.dOSFTESB4FfyqLaq3FMhWG', NULL, 'Ejecutiva Comercial', '#ec4899', 1, NULL, '2026-10-05 18:13:47', NULL),
	(8, 6, 'Dr.', 'Paredes', 'cliente.demo', 'portal.demo@cliente.pe', '$2y$10$ZtraAxf8nJAglra1uZiM2OAQQoBTMDfkk6q3cbNoWopjuRAmzht22', NULL, 'Contacto de Clínica San Gabriel', '#0ea5e9', 1, NULL, '2026-10-05 18:43:59', 1);

-- Volcando estructura para tabla sys_gestion_ti.visitas
CREATE TABLE IF NOT EXISTS `visitas` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` int unsigned DEFAULT NULL,
  `cliente_id` int unsigned NOT NULL,
  `tecnico_id` int unsigned DEFAULT NULL,
  `tipo` enum('correctiva','preventiva','instalacion','emergencia') NOT NULL DEFAULT 'correctiva',
  `fecha_programada` datetime NOT NULL,
  `estado` enum('programada','en_curso','completada','cancelada') NOT NULL DEFAULT 'programada',
  `notas` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cliente_id` (`cliente_id`),
  CONSTRAINT `visitas_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla sys_gestion_ti.visitas: ~9 rows (aproximadamente)
DELETE FROM `visitas`;
INSERT INTO `visitas` (`id`, `ticket_id`, `cliente_id`, `tecnico_id`, `tipo`, `fecha_programada`, `estado`, `notas`) VALUES
	(1, NULL, 4, 3, 'correctiva', '2026-10-11 14:00:00', 'programada', 'Visita programada de mantenimiento'),
	(2, NULL, 3, 4, 'emergencia', '2026-10-06 16:00:00', 'programada', 'Visita programada de mantenimiento'),
	(3, NULL, 1, 4, 'emergencia', '2026-10-11 11:00:00', 'programada', 'Visita programada de mantenimiento'),
	(4, NULL, 4, 3, 'correctiva', '2026-10-09 16:00:00', 'programada', 'Visita programada de mantenimiento'),
	(5, NULL, 1, 6, 'instalacion', '2026-10-09 11:00:00', 'programada', 'Visita programada de mantenimiento'),
	(6, NULL, 7, 3, 'instalacion', '2026-10-10 11:00:00', 'programada', 'Visita programada de mantenimiento'),
	(7, NULL, 8, 4, 'preventiva', '2026-10-08 16:00:00', 'programada', 'Visita programada de mantenimiento'),
	(8, NULL, 2, 3, 'instalacion', '2026-10-07 14:00:00', 'programada', 'Visita programada de mantenimiento'),
	(9, NULL, 2, 5, 'preventiva', '2026-10-06 10:00:00', 'programada', 'Visita programada de mantenimiento');

-- Volcando estructura para vista sys_gestion_ti.v_ia_actividades_crm
-- Creando tabla temporal para superar errores de dependencia de VIEW
CREATE TABLE `v_ia_actividades_crm` (
	`tipo` ENUM('llamada','reunion','correo','tarea','visita','nota') NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`asunto` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`fecha` DATETIME NULL,
	`realizada` TINYINT(1) NOT NULL,
	`vencida` INT NOT NULL,
	`cliente` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci',
	`lead_nombre` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci',
	`vendedor` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci'
) ENGINE=MyISAM;

-- Volcando estructura para vista sys_gestion_ti.v_ia_activos
-- Creando tabla temporal para superar errores de dependencia de VIEW
CREATE TABLE `v_ia_activos` (
	`equipo_id` INT UNSIGNED NULL,
	`codigo` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci',
	`cliente` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`tipo` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci',
	`marca` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci',
	`modelo` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci',
	`serie` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci',
	`ubicacion` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci',
	`fecha_compra` DATE NULL,
	`garantia_hasta` DATE NULL,
	`garantia_vigente` INT NULL,
	`estado` ENUM('operativo','en_reparacion','baja') NULL COLLATE 'utf8mb4_0900_ai_ci',
	`num_tickets` BIGINT NULL
) ENGINE=MyISAM;

-- Volcando estructura para vista sys_gestion_ti.v_ia_clientes
-- Creando tabla temporal para superar errores de dependencia de VIEW
CREATE TABLE `v_ia_clientes` (
	`cliente_id` INT UNSIGNED NULL,
	`cliente` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci',
	`ruc` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci',
	`rubro` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci',
	`contacto` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci',
	`telefono` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci',
	`email` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci',
	`activo` TINYINT(1) NULL,
	`registrado` TIMESTAMP NULL,
	`num_tickets` BIGINT NULL,
	`tickets_activos` BIGINT NULL,
	`num_equipos` BIGINT NULL,
	`plan` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci',
	`monto_mensual` DECIMAL(32,2) NULL
) ENGINE=MyISAM;

-- Volcando estructura para vista sys_gestion_ti.v_ia_comprobantes
-- Creando tabla temporal para superar errores de dependencia de VIEW
CREATE TABLE `v_ia_comprobantes` (
	`codigo` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`tipo` ENUM('factura','boleta','recibo') NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`cliente` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`concepto` ENUM('mensualidad','servicio','equipos','otro') NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`fecha` DATE NOT NULL,
	`mes` VARCHAR(1) NULL COLLATE 'utf8mb4_general_ci',
	`vencimiento` DATE NOT NULL,
	`subtotal` DECIMAL(12,2) NOT NULL,
	`igv` DECIMAL(12,2) NOT NULL,
	`total` DECIMAL(12,2) NOT NULL,
	`monto_pagado` DECIMAL(12,2) NOT NULL,
	`saldo` DECIMAL(13,2) NOT NULL,
	`estado_pago` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_general_ci',
	`dias_atraso` INT NULL
) ENGINE=MyISAM;

-- Volcando estructura para vista sys_gestion_ti.v_ia_conocimiento
-- Creando tabla temporal para superar errores de dependencia de VIEW
CREATE TABLE `v_ia_conocimiento` (
	`articulo_id` INT UNSIGNED NOT NULL,
	`titulo` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`categoria` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci',
	`vistas_lectura` INT NOT NULL,
	`autor` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci',
	`fecha` TIMESTAMP NULL
) ENGINE=MyISAM;

-- Volcando estructura para vista sys_gestion_ti.v_ia_contratos
-- Creando tabla temporal para superar errores de dependencia de VIEW
CREATE TABLE `v_ia_contratos` (
	`contrato_id` INT UNSIGNED NOT NULL,
	`codigo` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`cliente` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`plan` ENUM('Básico','Business','Premium') NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`sla_respuesta_horas` SMALLINT NOT NULL,
	`sla_resolucion_horas` SMALLINT NOT NULL,
	`fecha_inicio` DATE NOT NULL,
	`fecha_fin` DATE NOT NULL,
	`dias_para_vencer` INT NULL,
	`monto_mensual` DECIMAL(10,2) NOT NULL,
	`estado` ENUM('vigente','por_vencer','vencido') NOT NULL COLLATE 'utf8mb4_0900_ai_ci'
) ENGINE=MyISAM;

-- Volcando estructura para vista sys_gestion_ti.v_ia_cotizaciones
-- Creando tabla temporal para superar errores de dependencia de VIEW
CREATE TABLE `v_ia_cotizaciones` (
	`codigo` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`cliente` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`fecha` DATE NOT NULL,
	`mes` VARCHAR(1) NULL COLLATE 'utf8mb4_general_ci',
	`valida_hasta` DATE NOT NULL,
	`estado` ENUM('borrador','enviada','aprobada','rechazada','vencida') NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`subtotal` DECIMAL(12,2) NOT NULL,
	`descuento` DECIMAL(12,2) NOT NULL,
	`igv` DECIMAL(12,2) NOT NULL,
	`total` DECIMAL(12,2) NOT NULL,
	`oportunidad` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci'
) ENGINE=MyISAM;

-- Volcando estructura para vista sys_gestion_ti.v_ia_leads
-- Creando tabla temporal para superar errores de dependencia de VIEW
CREATE TABLE `v_ia_leads` (
	`codigo` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`empresa` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`contacto` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci',
	`cargo` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci',
	`rubro` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci',
	`origen` ENUM('web','referido','llamada','evento','redes','correo','otro') NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`estado` ENUM('nuevo','contactado','calificado','descartado','convertido') NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`score` TINYINT UNSIGNED NOT NULL,
	`fecha` DATETIME NOT NULL,
	`mes` VARCHAR(1) NULL COLLATE 'utf8mb4_general_ci',
	`vendedor` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci',
	`motivo_descarte` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci'
) ENGINE=MyISAM;

-- Volcando estructura para vista sys_gestion_ti.v_ia_oportunidades
-- Creando tabla temporal para superar errores de dependencia de VIEW
CREATE TABLE `v_ia_oportunidades` (
	`codigo` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`titulo` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`cliente` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`etapa` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`estado` ENUM('abierta','ganada','perdida') NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`tipo` ENUM('contrato','renovacion','proyecto','equipos','servicio') NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`valor` DECIMAL(12,2) NOT NULL,
	`probabilidad` TINYINT UNSIGNED NOT NULL,
	`valor_ponderado` DECIMAL(16,2) NULL,
	`cierre_estimado` DATE NULL,
	`cierre_real` DATE NULL,
	`mes_cierre` VARCHAR(1) NULL COLLATE 'utf8mb4_general_ci',
	`fecha` DATETIME NOT NULL,
	`vendedor` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci',
	`motivo_perdida` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci'
) ENGINE=MyISAM;

-- Volcando estructura para vista sys_gestion_ti.v_ia_ordenes
-- Creando tabla temporal para superar errores de dependencia de VIEW
CREATE TABLE `v_ia_ordenes` (
	`orden_id` INT UNSIGNED NOT NULL,
	`codigo` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`cliente` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`tecnico` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci',
	`ticket` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci',
	`tipo` ENUM('correctiva','preventiva','instalacion','diagnostico') NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`estado` ENUM('pendiente','en_curso','completada','cancelada') NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`horas` DECIMAL(5,2) NOT NULL,
	`tarifa_hora` DECIMAL(8,2) NOT NULL,
	`mano_obra` DECIMAL(12,2) NOT NULL,
	`total_repuestos` DECIMAL(42,2) NOT NULL,
	`total` DECIMAL(44,2) NOT NULL,
	`conformidad` TINYINT(1) NOT NULL,
	`fecha_programada` DATETIME NULL,
	`fecha_fin` DATETIME NULL,
	`fecha` TIMESTAMP NULL
) ENGINE=MyISAM;

-- Volcando estructura para vista sys_gestion_ti.v_ia_pagos
-- Creando tabla temporal para superar errores de dependencia de VIEW
CREATE TABLE `v_ia_pagos` (
	`fecha` DATE NOT NULL,
	`mes` VARCHAR(1) NULL COLLATE 'utf8mb4_general_ci',
	`monto` DECIMAL(12,2) NOT NULL,
	`metodo` ENUM('efectivo','transferencia','yape','plin','tarjeta','deposito','cheque') NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`comprobante` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`cliente` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci'
) ENGINE=MyISAM;

-- Volcando estructura para vista sys_gestion_ti.v_ia_repuestos
-- Creando tabla temporal para superar errores de dependencia de VIEW
CREATE TABLE `v_ia_repuestos` (
	`repuesto_id` INT UNSIGNED NOT NULL,
	`codigo` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`repuesto` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`categoria` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci',
	`stock` INT NOT NULL,
	`stock_minimo` INT NOT NULL,
	`stock_bajo` INT NOT NULL,
	`precio` DECIMAL(10,2) NOT NULL,
	`valor_stock` DECIMAL(20,2) NOT NULL,
	`ubicacion` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci'
) ENGINE=MyISAM;

-- Volcando estructura para vista sys_gestion_ti.v_ia_tecnicos
-- Creando tabla temporal para superar errores de dependencia de VIEW
CREATE TABLE `v_ia_tecnicos` (
	`usuario_id` INT UNSIGNED NULL,
	`tecnico` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci',
	`rol` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`cargo` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci',
	`activo` TINYINT(1) NULL,
	`ultimo_acceso` DATETIME NULL,
	`tickets_asignados` BIGINT NULL,
	`tickets_activos` BIGINT NULL,
	`tickets_resueltos` BIGINT NULL,
	`horas_promedio_resolucion` DECIMAL(22,1) NULL,
	`ordenes_completadas` BIGINT NULL
) ENGINE=MyISAM;

-- Volcando estructura para vista sys_gestion_ti.v_ia_tickets
-- Creando tabla temporal para superar errores de dependencia de VIEW
CREATE TABLE `v_ia_tickets` (
	`ticket_id` INT UNSIGNED NOT NULL,
	`codigo` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`fecha` DATETIME NOT NULL,
	`dia` DATE NULL,
	`mes` VARCHAR(1) NULL COLLATE 'utf8mb4_general_ci',
	`cliente` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`categoria` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`asunto` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`prioridad` ENUM('baja','media','alta','critica') NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`estado` ENUM('abierto','en_proceso','pendiente','resuelto','cerrado') NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`activo` INT NOT NULL,
	`canal` ENUM('web','correo','telefono','whatsapp','presencial') NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`tecnico` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci',
	`equipo` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci',
	`fecha_limite` DATETIME NULL,
	`resuelto_at` DATETIME NULL,
	`horas_resolucion` DECIMAL(22,1) NULL,
	`fuera_sla` INT NULL
) ENGINE=MyISAM;

-- Volcando estructura para vista sys_gestion_ti.v_ia_uso_repuestos
-- Creando tabla temporal para superar errores de dependencia de VIEW
CREATE TABLE `v_ia_uso_repuestos` (
	`orden` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`fecha` TIMESTAMP NULL,
	`dia` DATE NULL,
	`cliente` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`repuesto` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`categoria` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci',
	`cantidad` INT NOT NULL,
	`precio_unit` DECIMAL(10,2) NOT NULL,
	`importe` DECIMAL(20,2) NOT NULL,
	`estado_orden` ENUM('pendiente','en_curso','completada','cancelada') NOT NULL COLLATE 'utf8mb4_0900_ai_ci'
) ENGINE=MyISAM;

-- Volcando estructura para vista sys_gestion_ti.v_ia_visitas
-- Creando tabla temporal para superar errores de dependencia de VIEW
CREATE TABLE `v_ia_visitas` (
	`visita_id` INT UNSIGNED NOT NULL,
	`fecha_programada` DATETIME NOT NULL,
	`dia` DATE NULL,
	`cliente` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`tecnico` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci',
	`ticket` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci',
	`tipo` ENUM('correctiva','preventiva','instalacion','emergencia') NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`estado` ENUM('programada','en_curso','completada','cancelada') NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`notas` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci'
) ENGINE=MyISAM;

-- Eliminando tabla temporal y crear estructura final de VIEW
DROP TABLE IF EXISTS `v_ia_actividades_crm`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_ia_actividades_crm` AS select `a`.`tipo` AS `tipo`,`a`.`asunto` AS `asunto`,`a`.`fecha` AS `fecha`,`a`.`completada` AS `realizada`,if(((`a`.`completada` = 0) and (`a`.`fecha` < now())),1,0) AS `vencida`,`c`.`razon_social` AS `cliente`,`l`.`empresa` AS `lead_nombre`,concat(`u`.`nombres`,' ',`u`.`apellidos`) AS `vendedor` from (((`crm_actividades` `a` left join `clientes` `c` on((`c`.`id` = `a`.`cliente_id`))) left join `crm_leads` `l` on((`l`.`id` = `a`.`lead_id`))) left join `usuarios` `u` on((`u`.`id` = `a`.`responsable_id`))) where (`a`.`auto` = 0);

-- Eliminando tabla temporal y crear estructura final de VIEW
DROP TABLE IF EXISTS `v_ia_activos`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_ia_activos` AS select `a`.`id` AS `equipo_id`,`a`.`codigo` AS `codigo`,`c`.`razon_social` AS `cliente`,`a`.`tipo` AS `tipo`,`a`.`marca` AS `marca`,`a`.`modelo` AS `modelo`,`a`.`serie` AS `serie`,`a`.`ubicacion` AS `ubicacion`,`a`.`fecha_compra` AS `fecha_compra`,`a`.`garantia_hasta` AS `garantia_hasta`,((`a`.`garantia_hasta` is not null) and (`a`.`garantia_hasta` >= curdate())) AS `garantia_vigente`,`a`.`estado` AS `estado`,(select count(0) from `tickets` `t` where (`t`.`activo_id` = `a`.`id`)) AS `num_tickets` from (`activos` `a` join `clientes` `c` on((`c`.`id` = `a`.`cliente_id`)));

-- Eliminando tabla temporal y crear estructura final de VIEW
DROP TABLE IF EXISTS `v_ia_clientes`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_ia_clientes` AS select `c`.`id` AS `cliente_id`,`c`.`razon_social` AS `cliente`,`c`.`ruc` AS `ruc`,`c`.`rubro` AS `rubro`,`c`.`contacto` AS `contacto`,`c`.`telefono` AS `telefono`,`c`.`email` AS `email`,`c`.`estado` AS `activo`,`c`.`created_at` AS `registrado`,(select count(0) from `tickets` `t` where (`t`.`cliente_id` = `c`.`id`)) AS `num_tickets`,(select count(0) from `tickets` `t` where ((`t`.`cliente_id` = `c`.`id`) and (`t`.`estado` in ('abierto','en_proceso','pendiente')))) AS `tickets_activos`,(select count(0) from `activos` `a` where (`a`.`cliente_id` = `c`.`id`)) AS `num_equipos`,(select `k`.`plan` from `contratos` `k` where ((`k`.`cliente_id` = `c`.`id`) and (`k`.`estado` <> 'vencido')) order by `k`.`fecha_fin` desc limit 1) AS `plan`,(select coalesce(sum(`k`.`monto_mensual`),0) from `contratos` `k` where ((`k`.`cliente_id` = `c`.`id`) and (`k`.`estado` <> 'vencido'))) AS `monto_mensual` from `clientes` `c`;

-- Eliminando tabla temporal y crear estructura final de VIEW
DROP TABLE IF EXISTS `v_ia_comprobantes`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_ia_comprobantes` AS select `f`.`codigo` AS `codigo`,`f`.`tipo` AS `tipo`,`c`.`razon_social` AS `cliente`,`f`.`concepto` AS `concepto`,`f`.`fecha_emision` AS `fecha`,date_format(`f`.`fecha_emision`,'%Y-%m') AS `mes`,`f`.`fecha_vencimiento` AS `vencimiento`,`f`.`subtotal` AS `subtotal`,`f`.`igv` AS `igv`,`f`.`total` AS `total`,`f`.`pagado` AS `monto_pagado`,if((`f`.`estado` = 'anulada'),0,(`f`.`total` - `f`.`pagado`)) AS `saldo`,(case when (`f`.`estado` = 'anulada') then 'anulada' when (`f`.`estado` = 'pagada') then 'pagada' when (`f`.`fecha_vencimiento` < curdate()) then 'vencida' when (`f`.`pagado` > 0) then 'parcial' else 'pendiente' end) AS `estado_pago`,if(((`f`.`estado` = 'emitida') and (`f`.`fecha_vencimiento` < curdate())),(to_days(curdate()) - to_days(`f`.`fecha_vencimiento`)),0) AS `dias_atraso` from (`fac_comprobantes` `f` join `clientes` `c` on((`c`.`id` = `f`.`cliente_id`)));

-- Eliminando tabla temporal y crear estructura final de VIEW
DROP TABLE IF EXISTS `v_ia_conocimiento`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_ia_conocimiento` AS select `k`.`id` AS `articulo_id`,`k`.`titulo` AS `titulo`,`cat`.`nombre` AS `categoria`,`k`.`vistas` AS `vistas_lectura`,concat(`u`.`nombres`,' ',`u`.`apellidos`) AS `autor`,`k`.`created_at` AS `fecha` from ((`kb_articulos` `k` left join `categorias` `cat` on((`cat`.`id` = `k`.`categoria_id`))) left join `usuarios` `u` on((`u`.`id` = `k`.`autor_id`)));

-- Eliminando tabla temporal y crear estructura final de VIEW
DROP TABLE IF EXISTS `v_ia_contratos`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_ia_contratos` AS select `k`.`id` AS `contrato_id`,`k`.`codigo` AS `codigo`,`c`.`razon_social` AS `cliente`,`k`.`plan` AS `plan`,`k`.`sla_respuesta_horas` AS `sla_respuesta_horas`,`k`.`sla_resolucion_horas` AS `sla_resolucion_horas`,`k`.`fecha_inicio` AS `fecha_inicio`,`k`.`fecha_fin` AS `fecha_fin`,(to_days(`k`.`fecha_fin`) - to_days(curdate())) AS `dias_para_vencer`,`k`.`monto_mensual` AS `monto_mensual`,`k`.`estado` AS `estado` from (`contratos` `k` join `clientes` `c` on((`c`.`id` = `k`.`cliente_id`)));

-- Eliminando tabla temporal y crear estructura final de VIEW
DROP TABLE IF EXISTS `v_ia_cotizaciones`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_ia_cotizaciones` AS select `q`.`codigo` AS `codigo`,`c`.`razon_social` AS `cliente`,`q`.`fecha` AS `fecha`,date_format(`q`.`fecha`,'%Y-%m') AS `mes`,`q`.`valida_hasta` AS `valida_hasta`,`q`.`estado` AS `estado`,`q`.`subtotal` AS `subtotal`,`q`.`descuento` AS `descuento`,`q`.`igv` AS `igv`,`q`.`total` AS `total`,`o`.`codigo` AS `oportunidad` from ((`crm_cotizaciones` `q` join `clientes` `c` on((`c`.`id` = `q`.`cliente_id`))) left join `crm_oportunidades` `o` on((`o`.`id` = `q`.`oportunidad_id`)));

-- Eliminando tabla temporal y crear estructura final de VIEW
DROP TABLE IF EXISTS `v_ia_leads`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_ia_leads` AS select `l`.`codigo` AS `codigo`,`l`.`empresa` AS `empresa`,`l`.`contacto` AS `contacto`,`l`.`cargo` AS `cargo`,`l`.`rubro` AS `rubro`,`l`.`origen` AS `origen`,`l`.`estado` AS `estado`,`l`.`score` AS `score`,`l`.`created_at` AS `fecha`,date_format(`l`.`created_at`,'%Y-%m') AS `mes`,concat(`u`.`nombres`,' ',`u`.`apellidos`) AS `vendedor`,`l`.`motivo_descarte` AS `motivo_descarte` from (`crm_leads` `l` left join `usuarios` `u` on((`u`.`id` = `l`.`responsable_id`)));

-- Eliminando tabla temporal y crear estructura final de VIEW
DROP TABLE IF EXISTS `v_ia_oportunidades`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_ia_oportunidades` AS select `o`.`codigo` AS `codigo`,`o`.`titulo` AS `titulo`,`c`.`razon_social` AS `cliente`,`e`.`nombre` AS `etapa`,`o`.`estado` AS `estado`,`o`.`tipo` AS `tipo`,`o`.`valor` AS `valor`,`o`.`probabilidad` AS `probabilidad`,round(((`o`.`valor` * `o`.`probabilidad`) / 100),2) AS `valor_ponderado`,`o`.`fecha_cierre_estimada` AS `cierre_estimado`,`o`.`fecha_cierre_real` AS `cierre_real`,date_format(coalesce(`o`.`fecha_cierre_real`,`o`.`fecha_cierre_estimada`),'%Y-%m') AS `mes_cierre`,`o`.`created_at` AS `fecha`,concat(`u`.`nombres`,' ',`u`.`apellidos`) AS `vendedor`,`o`.`motivo_perdida` AS `motivo_perdida` from (((`crm_oportunidades` `o` join `clientes` `c` on((`c`.`id` = `o`.`cliente_id`))) join `crm_etapas` `e` on((`e`.`id` = `o`.`etapa_id`))) left join `usuarios` `u` on((`u`.`id` = `o`.`responsable_id`)));

-- Eliminando tabla temporal y crear estructura final de VIEW
DROP TABLE IF EXISTS `v_ia_ordenes`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_ia_ordenes` AS select `o`.`id` AS `orden_id`,`o`.`codigo` AS `codigo`,`c`.`razon_social` AS `cliente`,concat(`u`.`nombres`,' ',`u`.`apellidos`) AS `tecnico`,`t`.`codigo` AS `ticket`,`o`.`tipo` AS `tipo`,`o`.`estado` AS `estado`,`o`.`horas` AS `horas`,`o`.`tarifa_hora` AS `tarifa_hora`,round((`o`.`horas` * `o`.`tarifa_hora`),2) AS `mano_obra`,coalesce(`rp`.`total`,0) AS `total_repuestos`,round(((`o`.`horas` * `o`.`tarifa_hora`) + coalesce(`rp`.`total`,0)),2) AS `total`,`o`.`conformidad` AS `conformidad`,`o`.`fecha_programada` AS `fecha_programada`,`o`.`fecha_fin` AS `fecha_fin`,`o`.`created_at` AS `fecha` from ((((`ordenes_servicio` `o` join `clientes` `c` on((`c`.`id` = `o`.`cliente_id`))) left join `usuarios` `u` on((`u`.`id` = `o`.`tecnico_id`))) left join `tickets` `t` on((`t`.`id` = `o`.`ticket_id`))) left join (select `ordenes_repuestos`.`orden_id` AS `orden_id`,sum((`ordenes_repuestos`.`cantidad` * `ordenes_repuestos`.`precio_unit`)) AS `total` from `ordenes_repuestos` group by `ordenes_repuestos`.`orden_id`) `rp` on((`rp`.`orden_id` = `o`.`id`)));

-- Eliminando tabla temporal y crear estructura final de VIEW
DROP TABLE IF EXISTS `v_ia_pagos`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_ia_pagos` AS select `p`.`fecha` AS `fecha`,date_format(`p`.`fecha`,'%Y-%m') AS `mes`,`p`.`monto` AS `monto`,`p`.`metodo` AS `metodo`,`f`.`codigo` AS `comprobante`,`c`.`razon_social` AS `cliente` from ((`fac_pagos` `p` join `fac_comprobantes` `f` on((`f`.`id` = `p`.`comprobante_id`))) join `clientes` `c` on((`c`.`id` = `f`.`cliente_id`))) where (`f`.`estado` <> 'anulada');

-- Eliminando tabla temporal y crear estructura final de VIEW
DROP TABLE IF EXISTS `v_ia_repuestos`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_ia_repuestos` AS select `r`.`id` AS `repuesto_id`,`r`.`codigo` AS `codigo`,`r`.`nombre` AS `repuesto`,`r`.`categoria` AS `categoria`,`r`.`stock` AS `stock`,`r`.`stock_minimo` AS `stock_minimo`,(`r`.`stock` <= `r`.`stock_minimo`) AS `stock_bajo`,`r`.`precio` AS `precio`,round((`r`.`stock` * `r`.`precio`),2) AS `valor_stock`,`r`.`ubicacion` AS `ubicacion` from `repuestos` `r`;

-- Eliminando tabla temporal y crear estructura final de VIEW
DROP TABLE IF EXISTS `v_ia_tecnicos`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_ia_tecnicos` AS select `u`.`id` AS `usuario_id`,concat(`u`.`nombres`,' ',`u`.`apellidos`) AS `tecnico`,`r`.`nombre` AS `rol`,`u`.`cargo` AS `cargo`,`u`.`estado` AS `activo`,`u`.`ultimo_acceso` AS `ultimo_acceso`,(select count(0) from `tickets` `t` where (`t`.`tecnico_id` = `u`.`id`)) AS `tickets_asignados`,(select count(0) from `tickets` `t` where ((`t`.`tecnico_id` = `u`.`id`) and (`t`.`estado` in ('abierto','en_proceso','pendiente')))) AS `tickets_activos`,(select count(0) from `tickets` `t` where ((`t`.`tecnico_id` = `u`.`id`) and (`t`.`estado` in ('resuelto','cerrado')))) AS `tickets_resueltos`,(select round((avg(timestampdiff(MINUTE,`t`.`created_at`,`t`.`resuelto_at`)) / 60),1) from `tickets` `t` where ((`t`.`tecnico_id` = `u`.`id`) and (`t`.`resuelto_at` is not null))) AS `horas_promedio_resolucion`,(select count(0) from `ordenes_servicio` `o` where ((`o`.`tecnico_id` = `u`.`id`) and (`o`.`estado` = 'completada'))) AS `ordenes_completadas` from (`usuarios` `u` join `roles` `r` on((`r`.`id` = `u`.`rol_id`)));

-- Eliminando tabla temporal y crear estructura final de VIEW
DROP TABLE IF EXISTS `v_ia_tickets`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_ia_tickets` AS select `t`.`id` AS `ticket_id`,`t`.`codigo` AS `codigo`,`t`.`created_at` AS `fecha`,cast(`t`.`created_at` as date) AS `dia`,date_format(`t`.`created_at`,'%Y-%m') AS `mes`,`c`.`razon_social` AS `cliente`,`cat`.`nombre` AS `categoria`,`t`.`asunto` AS `asunto`,`t`.`prioridad` AS `prioridad`,`t`.`estado` AS `estado`,(`t`.`estado` in ('abierto','en_proceso','pendiente')) AS `activo`,`t`.`canal` AS `canal`,concat(`u`.`nombres`,' ',`u`.`apellidos`) AS `tecnico`,`a`.`codigo` AS `equipo`,`t`.`fecha_limite` AS `fecha_limite`,`t`.`resuelto_at` AS `resuelto_at`,round((timestampdiff(MINUTE,`t`.`created_at`,`t`.`resuelto_at`) / 60),1) AS `horas_resolucion`,(case when (`t`.`fecha_limite` is null) then NULL when (`t`.`estado` in ('resuelto','cerrado')) then (coalesce(`t`.`resuelto_at`,`t`.`fecha_limite`) > `t`.`fecha_limite`) else (now() > `t`.`fecha_limite`) end) AS `fuera_sla` from ((((`tickets` `t` join `clientes` `c` on((`c`.`id` = `t`.`cliente_id`))) join `categorias` `cat` on((`cat`.`id` = `t`.`categoria_id`))) left join `usuarios` `u` on((`u`.`id` = `t`.`tecnico_id`))) left join `activos` `a` on((`a`.`id` = `t`.`activo_id`)));

-- Eliminando tabla temporal y crear estructura final de VIEW
DROP TABLE IF EXISTS `v_ia_uso_repuestos`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_ia_uso_repuestos` AS select `o`.`codigo` AS `orden`,`o`.`created_at` AS `fecha`,cast(`o`.`created_at` as date) AS `dia`,`c`.`razon_social` AS `cliente`,`r`.`nombre` AS `repuesto`,`r`.`categoria` AS `categoria`,`orp`.`cantidad` AS `cantidad`,`orp`.`precio_unit` AS `precio_unit`,round((`orp`.`cantidad` * `orp`.`precio_unit`),2) AS `importe`,`o`.`estado` AS `estado_orden` from (((`ordenes_repuestos` `orp` join `ordenes_servicio` `o` on((`o`.`id` = `orp`.`orden_id`))) join `repuestos` `r` on((`r`.`id` = `orp`.`repuesto_id`))) join `clientes` `c` on((`c`.`id` = `o`.`cliente_id`)));

-- Eliminando tabla temporal y crear estructura final de VIEW
DROP TABLE IF EXISTS `v_ia_visitas`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_ia_visitas` AS select `v`.`id` AS `visita_id`,`v`.`fecha_programada` AS `fecha_programada`,cast(`v`.`fecha_programada` as date) AS `dia`,`c`.`razon_social` AS `cliente`,concat(`u`.`nombres`,' ',`u`.`apellidos`) AS `tecnico`,`t`.`codigo` AS `ticket`,`v`.`tipo` AS `tipo`,`v`.`estado` AS `estado`,`v`.`notas` AS `notas` from (((`visitas` `v` join `clientes` `c` on((`c`.`id` = `v`.`cliente_id`))) left join `usuarios` `u` on((`u`.`id` = `v`.`tecnico_id`))) left join `tickets` `t` on((`t`.`id` = `v`.`ticket_id`)));

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
