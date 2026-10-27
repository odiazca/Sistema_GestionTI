SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS ordenes_repuestos, ordenes_servicio, actividad, kb_articulos, repuestos, visitas, ticket_comentarios, tickets, activos, contratos, categorias, clientes, usuarios, roles, configuracion;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE roles (
  id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(40) NOT NULL UNIQUE,
  descripcion VARCHAR(150) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rol_id TINYINT UNSIGNED NOT NULL,
  nombres VARCHAR(80) NOT NULL,
  apellidos VARCHAR(80) NOT NULL,
  usuario VARCHAR(40) NOT NULL UNIQUE,
  email VARCHAR(120) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  telefono VARCHAR(25) NULL,
  cargo VARCHAR(80) NULL,
  avatar_color VARCHAR(9) NOT NULL DEFAULT '#4f46e5',
  estado TINYINT(1) NOT NULL DEFAULT 1,
  ultimo_acceso DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (rol_id) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE clientes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  razon_social VARCHAR(150) NOT NULL,
  ruc VARCHAR(15) NULL,
  rubro VARCHAR(80) NULL,
  direccion VARCHAR(200) NULL,
  telefono VARCHAR(25) NULL,
  email VARCHAR(120) NULL,
  contacto VARCHAR(100) NULL,
  estado TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE categorias (
  id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(60) NOT NULL,
  icono VARCHAR(40) NOT NULL DEFAULT 'bi-tag',
  color VARCHAR(9) NOT NULL DEFAULT '#4f46e5'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE contratos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT UNSIGNED NOT NULL,
  codigo VARCHAR(20) NOT NULL UNIQUE,
  plan ENUM('Básico','Business','Premium') NOT NULL DEFAULT 'Básico',
  sla_respuesta_horas SMALLINT NOT NULL DEFAULT 8,
  sla_resolucion_horas SMALLINT NOT NULL DEFAULT 48,
  fecha_inicio DATE NOT NULL,
  fecha_fin DATE NOT NULL,
  monto_mensual DECIMAL(10,2) NOT NULL DEFAULT 0,
  estado ENUM('vigente','por_vencer','vencido') NOT NULL DEFAULT 'vigente',
  FOREIGN KEY (cliente_id) REFERENCES clientes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE activos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT UNSIGNED NOT NULL,
  codigo VARCHAR(20) NOT NULL UNIQUE,
  tipo VARCHAR(40) NOT NULL,
  marca VARCHAR(40) NULL,
  modelo VARCHAR(60) NULL,
  serie VARCHAR(60) NULL,
  ubicacion VARCHAR(100) NULL,
  fecha_compra DATE NULL,
  garantia_hasta DATE NULL,
  estado ENUM('operativo','en_reparacion','baja') NOT NULL DEFAULT 'operativo',
  FOREIGN KEY (cliente_id) REFERENCES clientes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tickets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(20) NOT NULL UNIQUE,
  cliente_id INT UNSIGNED NOT NULL,
  activo_id INT UNSIGNED NULL,
  categoria_id TINYINT UNSIGNED NOT NULL,
  asunto VARCHAR(180) NOT NULL,
  descripcion TEXT NULL,
  prioridad ENUM('baja','media','alta','critica') NOT NULL DEFAULT 'media',
  estado ENUM('abierto','en_proceso','pendiente','resuelto','cerrado') NOT NULL DEFAULT 'abierto',
  canal ENUM('web','correo','telefono','whatsapp','presencial') NOT NULL DEFAULT 'web',
  tecnico_id INT UNSIGNED NULL,
  creado_por INT UNSIGNED NULL,
  fecha_limite DATETIME NULL,
  resuelto_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_estado (estado), INDEX idx_created (created_at),
  FOREIGN KEY (cliente_id) REFERENCES clientes(id),
  FOREIGN KEY (categoria_id) REFERENCES categorias(id),
  FOREIGN KEY (tecnico_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  FOREIGN KEY (activo_id) REFERENCES activos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ticket_comentarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ticket_id INT UNSIGNED NOT NULL,
  usuario_id INT UNSIGNED NULL,
  comentario TEXT NOT NULL,
  interno TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE visitas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ticket_id INT UNSIGNED NULL,
  cliente_id INT UNSIGNED NOT NULL,
  tecnico_id INT UNSIGNED NULL,
  tipo ENUM('correctiva','preventiva','instalacion','emergencia') NOT NULL DEFAULT 'correctiva',
  fecha_programada DATETIME NOT NULL,
  estado ENUM('programada','en_curso','completada','cancelada') NOT NULL DEFAULT 'programada',
  notas VARCHAR(255) NULL,
  FOREIGN KEY (cliente_id) REFERENCES clientes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE repuestos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(20) NOT NULL UNIQUE,
  nombre VARCHAR(120) NOT NULL,
  categoria VARCHAR(60) NULL,
  stock INT NOT NULL DEFAULT 0,
  stock_minimo INT NOT NULL DEFAULT 5,
  precio DECIMAL(10,2) NOT NULL DEFAULT 0,
  ubicacion VARCHAR(60) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE kb_articulos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  categoria_id TINYINT UNSIGNED NULL,
  titulo VARCHAR(180) NOT NULL,
  contenido MEDIUMTEXT NULL,
  vistas INT NOT NULL DEFAULT 0,
  autor_id INT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE actividad (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NULL,
  accion VARCHAR(120) NOT NULL,
  detalle VARCHAR(200) NULL,
  icono VARCHAR(40) NOT NULL DEFAULT 'bi-activity',
  color VARCHAR(9) NOT NULL DEFAULT '#4f46e5',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE configuracion (
  clave VARCHAR(60) PRIMARY KEY,
  valor TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
