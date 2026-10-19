-- MySQL dump 10.13  Distrib 8.4.11, for Linux (x86_64)
--
-- Host: localhost    Database: dentissa
-- ------------------------------------------------------
-- Server version	8.4.11

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `casos`
--

DROP TABLE IF EXISTS `casos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `casos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `titulo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `orden` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `casos`
--

LOCK TABLES `casos` WRITE;
/*!40000 ALTER TABLE `casos` DISABLE KEYS */;
/*!40000 ALTER TABLE `casos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `citas`
--

DROP TABLE IF EXISTS `citas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `citas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_paciente` bigint unsigned NOT NULL,
  `id_servicio` bigint unsigned DEFAULT NULL,
  `id_tratamiento` bigint unsigned DEFAULT NULL,
  `fecha_hora` datetime NOT NULL,
  `duracion_estimada` int NOT NULL,
  `estado` enum('agendada','confirmada','en_curso','completada','cancelada','no_asistio') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'agendada',
  `motivo` text COLLATE utf8mb4_unicode_ci,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `confirmada_por` bigint unsigned DEFAULT NULL,
  `fecha_confirmacion` timestamp NULL DEFAULT NULL,
  `cancelada_por` bigint unsigned DEFAULT NULL,
  `fecha_cancelacion` timestamp NULL DEFAULT NULL,
  `motivo_cancelacion` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `clave_idempotencia` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `citas_clave_idempotencia_unique` (`clave_idempotencia`),
  KEY `citas_id_paciente_foreign` (`id_paciente`),
  KEY `citas_id_servicio_foreign` (`id_servicio`),
  KEY `citas_id_tratamiento_foreign` (`id_tratamiento`),
  KEY `citas_confirmada_por_foreign` (`confirmada_por`),
  KEY `citas_cancelada_por_foreign` (`cancelada_por`),
  KEY `idx_citas_fecha_hora` (`fecha_hora`),
  KEY `idx_citas_estado` (`estado`),
  CONSTRAINT `citas_cancelada_por_foreign` FOREIGN KEY (`cancelada_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `citas_confirmada_por_foreign` FOREIGN KEY (`confirmada_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `citas_id_paciente_foreign` FOREIGN KEY (`id_paciente`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `citas_id_servicio_foreign` FOREIGN KEY (`id_servicio`) REFERENCES `servicios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `citas_id_tratamiento_foreign` FOREIGN KEY (`id_tratamiento`) REFERENCES `tratamientos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `citas`
--

LOCK TABLES `citas` WRITE;
/*!40000 ALTER TABLE `citas` DISABLE KEYS */;
/*!40000 ALTER TABLE `citas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `historial_clinico`
--

DROP TABLE IF EXISTS `historial_clinico`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `historial_clinico` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_paciente` bigint unsigned NOT NULL,
  `id_cita` bigint unsigned DEFAULT NULL,
  `id_doctor` bigint unsigned NOT NULL,
  `fecha_consulta` date NOT NULL,
  `motivo_consulta` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `diagnostico` text COLLATE utf8mb4_unicode_ci,
  `tratamiento_realizado` text COLLATE utf8mb4_unicode_ci,
  `medicamentos_recetados` text COLLATE utf8mb4_unicode_ci,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `notas_doctor` text COLLATE utf8mb4_unicode_ci,
  `tipo_consulta` enum('primera_vez','seguimiento','urgencia','control') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'primera_vez',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `historial_clinico_id_paciente_foreign` (`id_paciente`),
  KEY `historial_clinico_id_cita_foreign` (`id_cita`),
  KEY `historial_clinico_id_doctor_foreign` (`id_doctor`),
  CONSTRAINT `historial_clinico_id_cita_foreign` FOREIGN KEY (`id_cita`) REFERENCES `citas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `historial_clinico_id_doctor_foreign` FOREIGN KEY (`id_doctor`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `historial_clinico_id_paciente_foreign` FOREIGN KEY (`id_paciente`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `historial_clinico`
--

LOCK TABLES `historial_clinico` WRITE;
/*!40000 ALTER TABLE `historial_clinico` DISABLE KEYS */;
/*!40000 ALTER TABLE `historial_clinico` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `horarios_atencion`
--

DROP TABLE IF EXISTS `horarios_atencion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `horarios_atencion` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `dia_semana` tinyint unsigned NOT NULL,
  `abierto` tinyint(1) NOT NULL DEFAULT '1',
  `hora_apertura` tinyint unsigned NOT NULL DEFAULT '8',
  `hora_cierre` tinyint unsigned NOT NULL DEFAULT '18',
  `capacidad_simultanea` tinyint unsigned NOT NULL DEFAULT '2',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `horarios_atencion_dia_semana_unique` (`dia_semana`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `horarios_atencion`
--

LOCK TABLES `horarios_atencion` WRITE;
/*!40000 ALTER TABLE `horarios_atencion` DISABLE KEYS */;
INSERT INTO `horarios_atencion` VALUES (1,0,0,8,18,2,'2026-10-08 04:50:11','2026-10-08 04:50:11'),(2,1,1,8,18,2,'2026-10-08 04:50:11','2026-10-08 04:50:11'),(3,2,1,8,18,2,'2026-10-08 04:50:11','2026-10-08 04:50:11'),(4,3,1,8,18,2,'2026-10-08 04:50:11','2026-10-08 04:50:11'),(5,4,1,8,18,2,'2026-10-08 04:50:11','2026-10-08 04:50:11'),(6,5,1,8,18,2,'2026-10-08 04:50:11','2026-10-08 04:50:11'),(7,6,1,9,14,2,'2026-10-08 04:50:11','2026-10-08 04:50:11');
/*!40000 ALTER TABLE `horarios_atencion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `imagenes`
--

DROP TABLE IF EXISTS `imagenes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `imagenes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `titulo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('galeria','servicio','tratamiento','testimonio','perfil','historial_clinico','caso') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referencia_id` bigint unsigned DEFAULT NULL,
  `orden` int NOT NULL DEFAULT '0',
  `activa` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `imagenes`
--

LOCK TABLES `imagenes` WRITE;
/*!40000 ALTER TABLE `imagenes` DISABLE KEYS */;
/*!40000 ALTER TABLE `imagenes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lista_espera`
--

DROP TABLE IF EXISTS `lista_espera`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lista_espera` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_servicio` bigint unsigned DEFAULT NULL,
  `id_tratamiento` bigint unsigned DEFAULT NULL,
  `fecha_preferida` date DEFAULT NULL,
  `franja_horaria` enum('cualquiera','manana','tarde') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cualquiera',
  `estado` enum('pendiente','contactada','cerrada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `contactado_por` bigint unsigned DEFAULT NULL,
  `fecha_contacto` timestamp NULL DEFAULT NULL,
  `notas` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lista_espera_id_servicio_foreign` (`id_servicio`),
  KEY `lista_espera_id_tratamiento_foreign` (`id_tratamiento`),
  KEY `lista_espera_contactado_por_foreign` (`contactado_por`),
  KEY `lista_espera_fecha_preferida_index` (`fecha_preferida`),
  KEY `lista_espera_estado_index` (`estado`),
  CONSTRAINT `lista_espera_contactado_por_foreign` FOREIGN KEY (`contactado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lista_espera_id_servicio_foreign` FOREIGN KEY (`id_servicio`) REFERENCES `servicios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lista_espera_id_tratamiento_foreign` FOREIGN KEY (`id_tratamiento`) REFERENCES `tratamientos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lista_espera`
--

LOCK TABLES `lista_espera` WRITE;
/*!40000 ALTER TABLE `lista_espera` DISABLE KEYS */;
/*!40000 ALTER TABLE `lista_espera` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logs_auditoria`
--

DROP TABLE IF EXISTS `logs_auditoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs_auditoria` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_usuario` bigint unsigned DEFAULT NULL,
  `actor_user_id` bigint unsigned DEFAULT NULL,
  `actor_nombre` varchar(201) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `actor_rol` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `accion` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tabla` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registro_id` bigint unsigned DEFAULT NULL,
  `datos_anteriores` json DEFAULT NULL,
  `datos_nuevos` json DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `logs_auditoria_id_usuario_foreign` (`id_usuario`),
  CONSTRAINT `logs_auditoria_id_usuario_foreign` FOREIGN KEY (`id_usuario`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logs_auditoria`
--

LOCK TABLES `logs_auditoria` WRITE;
/*!40000 ALTER TABLE `logs_auditoria` DISABLE KEYS */;
/*!40000 ALTER TABLE `logs_auditoria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mensajes_contacto`
--

DROP TABLE IF EXISTS `mensajes_contacto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mensajes_contacto` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mensaje` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `leido` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mensajes_contacto`
--

LOCK TABLES `mensajes_contacto` WRITE;
/*!40000 ALTER TABLE `mensajes_contacto` DISABLE KEYS */;
/*!40000 ALTER TABLE `mensajes_contacto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_07_16_085039_add_dentissa_fields_to_users_table',1),(5,'2026_07_16_085042_create_pacientes_table',1),(6,'2026_07_16_085043_create_servicios_table',1),(7,'2026_07_16_085044_create_tratamientos_table',1),(8,'2026_07_16_085045_create_servicios_tratamientos_table',1),(9,'2026_07_16_085046_create_citas_table',1),(10,'2026_07_16_085047_create_ofertas_table',1),(11,'2026_07_16_085048_create_testimonios_table',1),(12,'2026_07_16_085049_create_historial_clinico_table',1),(13,'2026_07_16_085050_create_imagenes_table',1),(14,'2026_07_16_085051_create_notificaciones_table',1),(15,'2026_07_16_085052_create_logs_auditoria_table',1),(16,'2026_07_16_100221_add_historial_clinico_to_imagenes_tipo_enum',1),(17,'2026_07_16_212513_create_horarios_atencion_table',1),(18,'2026_07_18_050527_add_curp_rfc_ine_tutor_to_pacientes_table',1),(19,'2026_07_18_050528_create_mensajes_contacto_table',1),(20,'2026_07_18_050529_add_id_externo_meta_to_notificaciones_table',1),(21,'2026_07_18_052826_add_check_constraint_to_testimonios_calificacion',1),(22,'2026_07_18_052827_add_indexes_to_citas_table',1),(23,'2026_07_20_160553_create_casos_table',1),(24,'2026_07_20_160558_add_caso_to_imagenes_tipo_enum',1),(25,'2026_07_22_120000_desacoplar_pacientes_de_usuarios',1),(26,'2026_08_07_190521_agregar_paciente_a_notificaciones_table',1),(27,'2026_08_07_190845_agregar_cita_agendada_a_notificaciones_canal_enum',1),(28,'2026_08_07_192116_create_preguntas_frecuentes_table',1),(29,'2026_08_19_220000_create_solicitudes_urgencia_table',1),(30,'2026_08_19_220001_create_lista_espera_table',1),(31,'2026_10_07_120000_add_cita_to_notificaciones_table',1),(32,'2026_10_13_120000_add_variantes_to_preguntas_frecuentes_table',2),(33,'2026_10_15_000001_add_clave_idempotencia_to_citas_table',3),(34,'2026_10_16_120000_add_actor_snapshot_to_logs_auditoria',4),(35,'2026_10_16_130000_create_notificaciones_sistema_table',5);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notificaciones`
--

DROP TABLE IF EXISTS `notificaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notificaciones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_usuario` bigint unsigned DEFAULT NULL,
  `id_paciente` bigint unsigned DEFAULT NULL,
  `id_cita` bigint unsigned DEFAULT NULL,
  `tipo` enum('whatsapp','email','push') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `canal` enum('cita_agendada','cita_confirmada','cita_recordatorio','cita_cancelada','cita_reagendada','promocion','general') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `titulo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mensaje` text COLLATE utf8mb4_unicode_ci,
  `leido` tinyint(1) NOT NULL DEFAULT '0',
  `fecha_envio` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_lectura` timestamp NULL DEFAULT NULL,
  `id_externo_meta` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notificaciones_id_paciente_foreign` (`id_paciente`),
  KEY `notificaciones_id_usuario_foreign` (`id_usuario`),
  KEY `notificaciones_id_cita_foreign` (`id_cita`),
  CONSTRAINT `notificaciones_id_cita_foreign` FOREIGN KEY (`id_cita`) REFERENCES `citas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `notificaciones_id_paciente_foreign` FOREIGN KEY (`id_paciente`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notificaciones_id_usuario_foreign` FOREIGN KEY (`id_usuario`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notificaciones`
--

LOCK TABLES `notificaciones` WRITE;
/*!40000 ALTER TABLE `notificaciones` DISABLE KEYS */;
/*!40000 ALTER TABLE `notificaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notificaciones_sistema`
--

DROP TABLE IF EXISTS `notificaciones_sistema`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notificaciones_sistema` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `evento` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titulo` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mensaje` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `leida_en` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notificaciones_sistema_user_id_leida_en_created_at_index` (`user_id`,`leida_en`,`created_at`),
  CONSTRAINT `notificaciones_sistema_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notificaciones_sistema`
--

LOCK TABLES `notificaciones_sistema` WRITE;
/*!40000 ALTER TABLE `notificaciones_sistema` DISABLE KEYS */;
INSERT INTO `notificaciones_sistema` VALUES (1,1,'prueba','Prueba de notificaciones en vivo','Aviso interno de prueba. Si ves este aviso, la campana y la bandeja están conectadas.',NULL,'2026-10-16 20:44:30','2026-10-16 20:18:52','2026-10-16 20:44:30'),(2,2,'prueba','Prueba de notificaciones en vivo','Aviso interno de prueba. Si ves este aviso, la campana y la bandeja están conectadas.',NULL,NULL,'2026-10-16 20:18:52','2026-10-16 20:18:52'),(3,1,'prueba_tiempo_real','Prueba de notificación en tiempo real','Esta alerta se envió en vivo desde Dentissa.','http://localhost:8000/notificaciones','2026-10-16 20:44:30','2026-10-16 20:30:33','2026-10-16 20:44:30'),(4,2,'prueba_tiempo_real','Prueba de notificación en tiempo real','Esta alerta se envió en vivo desde Dentissa.','http://localhost:8000/notificaciones',NULL,'2026-10-16 20:30:33','2026-10-16 20:30:33'),(5,1,'prueba_tiempo_real','Aviso emergente de prueba','La notificación llegó en tiempo real.','http://localhost:8000/notificaciones','2026-10-16 20:44:30','2026-10-16 20:33:11','2026-10-16 20:44:30'),(6,2,'prueba_tiempo_real','Aviso emergente de prueba','La notificación llegó en tiempo real.','http://localhost:8000/notificaciones',NULL,'2026-10-16 20:33:11','2026-10-16 20:33:11'),(7,1,'prueba_tiempo_real','Prueba en vivo','Nueva prueba de notificación recibida.','http://localhost:8000/notificaciones','2026-10-16 20:44:30','2026-10-16 20:33:25','2026-10-16 20:44:30'),(8,2,'prueba_tiempo_real','Prueba en vivo','Nueva prueba de notificación recibida.','http://localhost:8000/notificaciones',NULL,'2026-10-16 20:33:25','2026-10-16 20:33:25'),(9,1,'prueba_tiempo_real','Prueba en vivo 2','Se envió otra notificación de prueba.','http://localhost:8000/notificaciones','2026-10-16 20:44:30','2026-10-16 20:33:49','2026-10-16 20:44:30'),(10,2,'prueba_tiempo_real','Prueba en vivo 2','Se envió otra notificación de prueba.','http://localhost:8000/notificaciones',NULL,'2026-10-16 20:33:49','2026-10-16 20:33:49'),(11,1,'prueba_tiempo_real','Prueba de sonido','Aviso enviado para probar el sonido de notificaciones.','http://localhost:8000/notificaciones','2026-10-16 20:44:30','2026-10-16 20:37:07','2026-10-16 20:44:30'),(12,2,'prueba_tiempo_real','Prueba de sonido','Aviso enviado para probar el sonido de notificaciones.','http://localhost:8000/notificaciones',NULL,'2026-10-16 20:37:07','2026-10-16 20:37:07'),(13,1,'prueba_tiempo_real','Prueba de sonido','Nueva prueba para verificar el aviso sonoro.','http://localhost:8000/notificaciones','2026-10-16 20:44:30','2026-10-16 20:42:41','2026-10-16 20:44:30'),(14,2,'prueba_tiempo_real','Prueba de sonido','Nueva prueba para verificar el aviso sonoro.','http://localhost:8000/notificaciones',NULL,'2026-10-16 20:42:41','2026-10-16 20:42:41'),(15,1,'prueba_tiempo_real','Aviso de prueba','Otra notificación de prueba enviada en vivo.','http://localhost:8000/notificaciones','2026-10-16 20:44:30','2026-10-16 20:43:01','2026-10-16 20:44:30'),(16,2,'prueba_tiempo_real','Aviso de prueba','Otra notificación de prueba enviada en vivo.','http://localhost:8000/notificaciones',NULL,'2026-10-16 20:43:01','2026-10-16 20:43:01'),(17,1,'prueba_tiempo_real','Prueba de notificación','Aviso de prueba enviado en tiempo real.','http://localhost:8000/notificaciones','2026-10-16 21:03:20','2026-10-16 20:48:40','2026-10-16 21:03:20'),(18,2,'prueba_tiempo_real','Prueba de notificación','Aviso de prueba enviado en tiempo real.','http://localhost:8000/notificaciones',NULL,'2026-10-16 20:48:40','2026-10-16 20:48:40');
/*!40000 ALTER TABLE `notificaciones_sistema` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ofertas`
--

DROP TABLE IF EXISTS `ofertas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ofertas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `titulo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `id_tratamiento` bigint unsigned DEFAULT NULL,
  `tipo` enum('descuento','paquete','bonificacion') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'descuento',
  `valor_descuento` decimal(5,2) DEFAULT NULL,
  `tipo_valor` enum('porcentaje','monto_fijo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'porcentaje',
  `precio_especial` decimal(10,2) DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `cupo_total` int NOT NULL DEFAULT '0',
  `cupos_utilizados` int NOT NULL DEFAULT '0',
  `imagen` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ofertas_id_tratamiento_foreign` (`id_tratamiento`),
  CONSTRAINT `ofertas_id_tratamiento_foreign` FOREIGN KEY (`id_tratamiento`) REFERENCES `tratamientos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ofertas`
--

LOCK TABLES `ofertas` WRITE;
/*!40000 ALTER TABLE `ofertas` DISABLE KEYS */;
/*!40000 ALTER TABLE `ofertas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pacientes`
--

DROP TABLE IF EXISTS `pacientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pacientes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_usuario` bigint unsigned DEFAULT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `apellido` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `direccion` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ciudad` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ocupacion` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alergias` text COLLATE utf8mb4_unicode_ci,
  `medicamentos_actuales` text COLLATE utf8mb4_unicode_ci,
  `observaciones_generales` text COLLATE utf8mb4_unicode_ci,
  `tipo_sangre` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `seguro_medico` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_seguro` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `curp` char(18) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rfc` varchar(13) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ine_clave` varchar(18) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tutor_id` bigint unsigned DEFAULT NULL,
  `contacto_emergencia` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono_emergencia` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pacientes_curp_unique` (`curp`),
  UNIQUE KEY `pacientes_rfc_unique` (`rfc`),
  UNIQUE KEY `pacientes_ine_clave_unique` (`ine_clave`),
  UNIQUE KEY `pacientes_email_unique` (`email`),
  KEY `pacientes_tutor_id_foreign` (`tutor_id`),
  KEY `pacientes_id_usuario_foreign` (`id_usuario`),
  CONSTRAINT `pacientes_id_usuario_foreign` FOREIGN KEY (`id_usuario`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pacientes_tutor_id_foreign` FOREIGN KEY (`tutor_id`) REFERENCES `pacientes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pacientes`
--

LOCK TABLES `pacientes` WRITE;
/*!40000 ALTER TABLE `pacientes` DISABLE KEYS */;
/*!40000 ALTER TABLE `pacientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `preguntas_frecuentes`
--

DROP TABLE IF EXISTS `preguntas_frecuentes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `preguntas_frecuentes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pregunta` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `respuesta` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `variantes` text COLLATE utf8mb4_unicode_ci,
  `activa` tinyint(1) NOT NULL DEFAULT '1',
  `orden` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `preguntas_frecuentes`
--

LOCK TABLES `preguntas_frecuentes` WRITE;
/*!40000 ALTER TABLE `preguntas_frecuentes` DISABLE KEYS */;
INSERT INTO `preguntas_frecuentes` VALUES (1,'¿Esta información es oficial de Dentissa?','No. Esta es una respuesta temporal de QA para probar el asistente. No contiene información oficial ni médica del consultorio.',NULL,0,0,'2026-10-13 07:49:29','2026-10-13 15:17:40'),(2,'¿Puedo tomar esta respuesta como información real?','No. Es contenido ficticio de prueba. Debe sustituirse por información revisada y aprobada por la clínica antes de publicar el sistema.',NULL,1,0,'2026-10-13 07:49:48','2026-10-13 07:49:48');
/*!40000 ALTER TABLE `preguntas_frecuentes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `servicios`
--

DROP TABLE IF EXISTS `servicios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `servicios` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `imagen` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icono` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `orden` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `servicios_nombre_unique` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `servicios`
--

LOCK TABLES `servicios` WRITE;
/*!40000 ALTER TABLE `servicios` DISABLE KEYS */;
INSERT INTO `servicios` VALUES (1,'Odontología General',NULL,NULL,NULL,1,1,'2026-10-08 04:50:21','2026-10-08 04:50:21'),(2,'Odontología Estética',NULL,NULL,NULL,1,2,'2026-10-08 04:50:21','2026-10-08 04:50:21'),(3,'Ortodoncia',NULL,NULL,NULL,1,3,'2026-10-08 04:50:21','2026-10-08 04:50:21'),(4,'Implantología',NULL,NULL,NULL,1,4,'2026-10-08 04:50:21','2026-10-08 04:50:21'),(5,'Periodoncia',NULL,NULL,NULL,1,5,'2026-10-08 04:50:21','2026-10-08 04:50:21'),(6,'Endodoncia',NULL,NULL,NULL,1,6,'2026-10-08 04:50:21','2026-10-08 04:50:21'),(7,'Odontopediatría',NULL,NULL,NULL,1,7,'2026-10-08 04:50:21','2026-10-08 04:50:21');
/*!40000 ALTER TABLE `servicios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `servicios_tratamientos`
--

DROP TABLE IF EXISTS `servicios_tratamientos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `servicios_tratamientos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_servicio` bigint unsigned NOT NULL,
  `id_tratamiento` bigint unsigned NOT NULL,
  `orden` int NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `servicios_tratamientos_id_servicio_id_tratamiento_unique` (`id_servicio`,`id_tratamiento`),
  KEY `servicios_tratamientos_id_tratamiento_foreign` (`id_tratamiento`),
  CONSTRAINT `servicios_tratamientos_id_servicio_foreign` FOREIGN KEY (`id_servicio`) REFERENCES `servicios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `servicios_tratamientos_id_tratamiento_foreign` FOREIGN KEY (`id_tratamiento`) REFERENCES `tratamientos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `servicios_tratamientos`
--

LOCK TABLES `servicios_tratamientos` WRITE;
/*!40000 ALTER TABLE `servicios_tratamientos` DISABLE KEYS */;
INSERT INTO `servicios_tratamientos` VALUES (1,2,1,1,'2026-10-08 04:50:21'),(2,2,2,2,'2026-10-08 04:50:21'),(3,1,3,1,'2026-10-08 04:50:21'),(4,1,8,2,'2026-10-08 04:50:21'),(5,1,7,3,'2026-10-08 04:50:21'),(6,3,6,1,'2026-10-08 04:50:21'),(7,4,5,1,'2026-10-08 04:50:21'),(8,6,4,1,'2026-10-08 04:50:21');
/*!40000 ALTER TABLE `servicios_tratamientos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `solicitudes_urgencia`
--

DROP TABLE IF EXISTS `solicitudes_urgencia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `solicitudes_urgencia` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `motivo` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `senales_reportadas` json DEFAULT NULL,
  `estado` enum('pendiente','contactada','cerrada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `contactado_por` bigint unsigned DEFAULT NULL,
  `fecha_contacto` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `solicitudes_urgencia_contactado_por_foreign` (`contactado_por`),
  KEY `solicitudes_urgencia_estado_index` (`estado`),
  CONSTRAINT `solicitudes_urgencia_contactado_por_foreign` FOREIGN KEY (`contactado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `solicitudes_urgencia`
--

LOCK TABLES `solicitudes_urgencia` WRITE;
/*!40000 ALTER TABLE `solicitudes_urgencia` DISABLE KEYS */;
/*!40000 ALTER TABLE `solicitudes_urgencia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `testimonios`
--

DROP TABLE IF EXISTS `testimonios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `testimonios` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_paciente` bigint unsigned NOT NULL,
  `id_cita` bigint unsigned DEFAULT NULL,
  `calificacion` tinyint unsigned DEFAULT NULL,
  `comentario` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('pendiente','aprobado','rechazado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `fecha_publicacion` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `testimonios_id_paciente_foreign` (`id_paciente`),
  KEY `testimonios_id_cita_foreign` (`id_cita`),
  CONSTRAINT `testimonios_id_cita_foreign` FOREIGN KEY (`id_cita`) REFERENCES `citas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `testimonios_id_paciente_foreign` FOREIGN KEY (`id_paciente`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_testimonios_calificacion` CHECK (((`calificacion` is null) or (`calificacion` between 1 and 5)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `testimonios`
--

LOCK TABLES `testimonios` WRITE;
/*!40000 ALTER TABLE `testimonios` DISABLE KEYS */;
/*!40000 ALTER TABLE `testimonios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tratamientos`
--

DROP TABLE IF EXISTS `tratamientos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tratamientos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `categoria` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duracion_estimada` int DEFAULT NULL,
  `precio_referencia` decimal(10,2) DEFAULT NULL,
  `sesiones_requeridas` int NOT NULL DEFAULT '1',
  `requiere_autorizacion` tinyint(1) NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `imagen` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tratamientos_nombre_unique` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tratamientos`
--

LOCK TABLES `tratamientos` WRITE;
/*!40000 ALTER TABLE `tratamientos` DISABLE KEYS */;
INSERT INTO `tratamientos` VALUES (1,'Blanqueamiento Dental',NULL,'Estética',90,350.00,1,0,1,NULL,'2026-10-08 04:50:21','2026-10-08 04:50:21'),(2,'Carillas de Porcelana',NULL,'Estética',120,800.00,1,0,1,NULL,'2026-10-08 04:50:21','2026-10-08 04:50:21'),(3,'Limpieza Dental Profunda',NULL,'Preventiva',60,150.00,1,0,1,NULL,'2026-10-08 04:50:21','2026-10-08 04:50:21'),(4,'Endodoncia (Conducto)',NULL,'Restauradora',90,400.00,1,0,1,NULL,'2026-10-08 04:50:21','2026-10-08 04:50:21'),(5,'Implante Dental',NULL,'Quirúrgica',120,1200.00,1,0,1,NULL,'2026-10-08 04:50:21','2026-10-08 04:50:21'),(6,'Ortodoncia (Brackets)',NULL,'Estética',30,600.00,1,0,1,NULL,'2026-10-08 04:50:21','2026-10-08 04:50:21'),(7,'Extracción Simple',NULL,'Quirúrgica',45,100.00,1,0,1,NULL,'2026-10-08 04:50:21','2026-10-08 04:50:21'),(8,'Selladores de Fisuras',NULL,'Preventiva',30,80.00,1,0,1,NULL,'2026-10-08 04:50:21','2026-10-08 04:50:21');
/*!40000 ALTER TABLE `tratamientos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellido` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol` enum('paciente','asistente','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'paciente',
  `foto_perfil` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ultimo_acceso` timestamp NULL DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `telefono_verificado` tinyint(1) NOT NULL DEFAULT '0',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Melissa','López N.','admin@dentissa.com','0000000000','2026-10-08 04:50:27','$2y$12$10YX7mE4jYUdTWUZN0yoIOSsWkAM9xUQUZe4U6PBNF31ssnQ2K1mW','admin',NULL,NULL,1,0,NULL,'2026-10-08 04:50:20','2026-10-08 04:50:27'),(2,'Asistente','Dentissa','asistente@dentissa.com','0000000001','2026-10-08 04:50:27','$2y$12$wXUAFnySZvSiV.6W/4t7.eSGvcXPCJPjTwHMVNiQXAqvighExnEUO','asistente',NULL,NULL,1,0,NULL,'2026-10-08 04:50:21','2026-10-08 04:50:27');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'dentissa'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-16 21:05:23
