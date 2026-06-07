-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 27-11-2025 a las 22:57:44
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
--
-- Base de datos: `mas_deforestacion`
--
-- --------------------------------------------------------
--
-- Estructura de tabla para la tabla `reportes_deforestacion`
--
CREATE TABLE `reportes_deforestacion` (
  `id_reporte` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_autoridad` int(11) DEFAULT NULL,
  `tipo_actividad` enum('tala','quema','cambio_uso','extraccion','otra') NOT NULL,
  `municipio` varchar(80) NOT NULL,
  `vereda_zona` varchar(100) NOT NULL,
  `coordenadas` varchar(60) DEFAULT NULL,
  `fecha_observacion` date NOT NULL,
  `hora_observacion` time DEFAULT NULL,
  `hectareas_afectadas` decimal(10,2) DEFAULT NULL,
  `ecosistema` enum('bosque_humedo','bosque_seco','manglar','otro','no_especificado') DEFAULT 'no_especificado',
  `descripcion` text NOT NULL,
  `evidencia_foto` varchar(255) DEFAULT NULL,
  `estado_reporte` enum('registrado','en_revision','en_proceso','cerrado') NOT NULL DEFAULT 'registrado',
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT NULL,
  `fecha_cierre` datetime DEFAULT NULL,
  `observacion_cierre` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
--
-- Estructura de tabla para la tabla `usuarios`
--
CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(60) NOT NULL,
  `apellido` varchar(60) NOT NULL,
  `correo` varchar(120) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `municipio` varchar(80) NOT NULL,
  `vereda_barrio` varchar(100) DEFAULT NULL,
  `tipo_usuario` enum('ciudadano','autoridad','admin') NOT NULL DEFAULT 'ciudadano',
  `especialidad` enum('tala','quema','cambio_uso','extraccion','otra','general') NOT NULL DEFAULT 'general',
  `clave_hash` varchar(255) NOT NULL,
  `creado_por` int(11) DEFAULT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
--
-- Indices de la tabla `reportes_deforestacion`
--
ALTER TABLE `reportes_deforestacion`
  ADD PRIMARY KEY (`id_reporte`),
  ADD KEY `fk_reportes_usuarios` (`id_usuario`),
  ADD KEY `fk_reportes_autoridad` (`id_autoridad`),
  ADD KEY `idx_reportes_municipio` (`municipio`),
  ADD KEY `idx_reportes_tipo` (`tipo_actividad`),
  ADD KEY `idx_reportes_estado` (`estado_reporte`),
  ADD KEY `idx_reportes_fecha` (`fecha_observacion`);
--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD KEY `fk_usuarios_creado_por` (`creado_por`);
--
-- AUTO_INCREMENT de las tablas volcadas
--
--
-- AUTO_INCREMENT de la tabla `reportes_deforestacion`
--
ALTER TABLE `reportes_deforestacion`
  MODIFY `id_reporte` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
--
-- Restricciones para tablas volcadas
--
--
-- Filtros para la tabla `reportes_deforestacion`
--
ALTER TABLE `reportes_deforestacion`
  ADD CONSTRAINT `fk_reportes_autoridad` FOREIGN KEY (`id_autoridad`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reportes_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE;
--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_creado_por` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

