CREATE TABLE `Empresa` (
  `id` bigint PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `empresa` varchar(80) NOT NULL,
  `slogan` text NOT NULL,
  `sigla` varchar(200) NOT NULL,
  `telefono` varchar(11) NOT NULL,
  `celular` varchar(11) NOT NULL,
  `email` varchar(80) NOT NULL,
  `direccion` text NOT NULL,
  `responsable` varchar(80) NOT NULL,
  `latitud` varchar(80) NOT NULL,
  `longitud` varchar(80) NOT NULL,
  `objeto` text NOT NULL,
  `mision` text NOT NULL,
  `vision` text NOT NULL,
  `facebook` varchar(40) NOT NULL,
  `instagram` varchar(40) NOT NULL,
  `tiktok` varchar(40) NOT NULL,
  `linkedin` varchar(40) NOT NULL,
  `carrito` ENUM ('activo', 'inactivo') NOT NULL DEFAULT 'activo',
  `tipo_cambio` decimal(10,2) NOT NULL,
  `logo_cuadrado` varchar(80) NOT NULL,
  `logo_largo` varchar(80) NOT NULL,
  `baner_inicio` varchar(80) NOT NULL,
  `icono` varchar(40) NOT NULL,
  `titulo_cierre` varchar(80) NOT NULL,
  `mensaje_cierre` text NOT NULL,
  `titulo_inicio` varchar(80) NOT NULL,
  `mensaje_inicio` text NOT NULL,
  `dominio` varchar(200) NOT NULL,
  `smtp_correo` varchar(100) NOT NULL,
  `correo_institucional` varchar(80) NOT NULL,
  `pwd_institucional` varchar(80) NOT NULL
);

CREATE TABLE `Formulario` (
  `id` bigint PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `formulario` varchar(40) NOT NULL,
  `descripcion` text,
  `ruta` varchar(40),
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `FormularioModulo` (
  `id` bigint PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `id_modulo` bigint NOT NULL,
  `id_formulario` bigint NOT NULL,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `FormularioPermiso` (
  `id` bigint PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `id_rol` bigint NOT NULL,
  `id_modulo` bigint NOT NULL,
  `id_formulario` bigint NOT NULL,
  `puede_crear` tinyint NOT NULL DEFAULT '0',
  `puede_leer` tinyint NOT NULL DEFAULT '0',
  `puede_editar` tinyint NOT NULL DEFAULT '0',
  `puede_eliminar` tinyint NOT NULL DEFAULT '0',
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `migrations` (
  `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int NOT NULL
);

CREATE TABLE `Modulo` (
  `id` bigint PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `modulo` varchar(40) NOT NULL,
  `descripcion` text,
  `icono` text,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `ModuloRol` (
  `id` bigint PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `id_rol` bigint NOT NULL,
  `id_modulo` bigint NOT NULL,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `personal_access_tokens` (
  `id` bigint PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text,
  `last_used_at` timestamp,
  `expires_at` timestamp,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `Rol` (
  `id` bigint PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `rol` varchar(40) NOT NULL,
  `descripcion` text,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `Sucursal` (
  `id` bigint PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `sucursal` varchar(40) NOT NULL,
  `empresa` varchar(40) NOT NULL,
  `responsable` varchar(40) NOT NULL,
  `direccion` varchar(80) NOT NULL,
  `longitud` varchar(40),
  `latitud` varchar(40),
  `telefono` varchar(10),
  `celular` varchar(10),
  `email` varchar(40),
  `pais` varchar(20) NOT NULL,
  `ciudad` varchar(20) NOT NULL,
  `localidad` varchar(30),
  `imagen` varchar(255),
  `qr` varchar(255),
  `estado` ENUM ('activo', 'inactivo') NOT NULL DEFAULT 'activo',
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `User` (
  `id` bigint PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `usuario` varchar(40) NOT NULL,
  `password` varchar(80) NOT NULL,
  `ci` varchar(12) NOT NULL,
  `nombres` varchar(40) NOT NULL,
  `apellido_paterno` varchar(50),
  `apellido_materno` varchar(50),
  `genero` ENUM ('masculino', 'femenino') NOT NULL,
  `fecha_nac` date NOT NULL,
  `email` varchar(80),
  `telefono` varchar(10),
  `celular` varchar(20),
  `direccion` varchar(50),
  `expedido` ENUM ('lpz', 'cbba', 'or', 'pt', 'tj', 'scz', 'bn', 'pd', 'ch', 'qr', 'ext'),
  `codigo_qr` text,
  `verificacion` varchar(40),
  `foto` varchar(80),
  `estado` ENUM ('activo', 'inactivo') NOT NULL DEFAULT 'activo',
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `UserRol` (
  `id` bigint PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `id_user` bigint NOT NULL,
  `id_rol` bigint NOT NULL,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `UserSucursal` (
  `id` bigint PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `id_user` bigint NOT NULL,
  `id_sucursal` bigint NOT NULL,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE UNIQUE INDEX `formulario_modulo_id_modulo_id_formulario_unique` ON `FormularioModulo` (`id_modulo`, `id_formulario`);

CREATE INDEX `formulario_modulo_id_formulario_foreign` ON `FormularioModulo` (`id_formulario`);

CREATE UNIQUE INDEX `formulario_permiso_id_rol_id_modulo_id_formulario_unique` ON `FormularioPermiso` (`id_rol`, `id_modulo`, `id_formulario`);

CREATE INDEX `formulario_permiso_id_modulo_foreign` ON `FormularioPermiso` (`id_modulo`);

CREATE INDEX `formulario_permiso_id_formulario_foreign` ON `FormularioPermiso` (`id_formulario`);

CREATE UNIQUE INDEX `modulo_rol_id_rol_id_modulo_unique` ON `ModuloRol` (`id_rol`, `id_modulo`);

CREATE INDEX `modulo_rol_id_modulo_foreign` ON `ModuloRol` (`id_modulo`);

CREATE UNIQUE INDEX `personal_access_tokens_token_unique` ON `personal_access_tokens` (`token`);

CREATE INDEX `personal_access_tokens_tokenable_type_tokenable_id_index` ON `personal_access_tokens` (`tokenable_type`, `tokenable_id`);

CREATE INDEX `personal_access_tokens_expires_at_index` ON `personal_access_tokens` (`expires_at`);

CREATE UNIQUE INDEX `rol_rol_unique` ON `Rol` (`rol`);

CREATE UNIQUE INDEX `user_usuario_unique` ON `User` (`usuario`);

CREATE UNIQUE INDEX `user_ci_unique` ON `User` (`ci`);

CREATE UNIQUE INDEX `user_rol_id_user_id_rol_unique` ON `UserRol` (`id_user`, `id_rol`);

CREATE INDEX `user_rol_id_rol_foreign` ON `UserRol` (`id_rol`);

CREATE UNIQUE INDEX `user_sucursal_id_user_id_sucursal_unique` ON `UserSucursal` (`id_user`, `id_sucursal`);

CREATE INDEX `user_sucursal_id_sucursal_foreign` ON `UserSucursal` (`id_sucursal`);

ALTER TABLE `FormularioModulo` ADD CONSTRAINT `formulario_modulo_id_formulario_foreign` FOREIGN KEY (`id_formulario`) REFERENCES `Formulario` (`id`) ON DELETE CASCADE;

ALTER TABLE `FormularioModulo` ADD CONSTRAINT `formulario_modulo_id_modulo_foreign` FOREIGN KEY (`id_modulo`) REFERENCES `Modulo` (`id`) ON DELETE CASCADE;

ALTER TABLE `FormularioPermiso` ADD CONSTRAINT `formulario_permiso_id_formulario_foreign` FOREIGN KEY (`id_formulario`) REFERENCES `Formulario` (`id`) ON DELETE CASCADE;

ALTER TABLE `FormularioPermiso` ADD CONSTRAINT `formulario_permiso_id_modulo_foreign` FOREIGN KEY (`id_modulo`) REFERENCES `Modulo` (`id`) ON DELETE CASCADE;

ALTER TABLE `FormularioPermiso` ADD CONSTRAINT `formulario_permiso_id_rol_foreign` FOREIGN KEY (`id_rol`) REFERENCES `Rol` (`id`) ON DELETE CASCADE;

ALTER TABLE `ModuloRol` ADD CONSTRAINT `modulo_rol_id_modulo_foreign` FOREIGN KEY (`id_modulo`) REFERENCES `Modulo` (`id`) ON DELETE CASCADE;

ALTER TABLE `ModuloRol` ADD CONSTRAINT `modulo_rol_id_rol_foreign` FOREIGN KEY (`id_rol`) REFERENCES `Rol` (`id`) ON DELETE CASCADE;

ALTER TABLE `UserRol` ADD CONSTRAINT `user_rol_id_rol_foreign` FOREIGN KEY (`id_rol`) REFERENCES `Rol` (`id`) ON DELETE CASCADE;

ALTER TABLE `UserRol` ADD CONSTRAINT `user_rol_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `User` (`id`) ON DELETE CASCADE;

ALTER TABLE `UserSucursal` ADD CONSTRAINT `user_sucursal_id_sucursal_foreign` FOREIGN KEY (`id_sucursal`) REFERENCES `Sucursal` (`id`) ON DELETE CASCADE;

ALTER TABLE `UserSucursal` ADD CONSTRAINT `user_sucursal_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `User` (`id`) ON DELETE CASCADE;
