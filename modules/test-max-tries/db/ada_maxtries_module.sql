-- phpMyAdmin SQL Dump
-- version 5.2.1-5.fc41
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Creato il: Dic 19, 2024 alle 15:30
-- Versione del server: 10.11.10-MariaDB
-- Versione PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `ada_provider1`
--

-- --------------------------------------------------------

--
-- Copia struttura delle tabelle
--

CREATE TABLE IF NOT EXISTS `module_maxtries_history_nodi` LIKE `history_nodi`;
ALTER TABLE `module_maxtries_history_nodi` ADD `trycount` int(5) UNSIGNED NOT NULL;
ALTER TABLE `module_maxtries_history_nodi` ADD `createdate` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `module_maxtries_history_nodi` ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `module_maxtries_history_esercizi` LIKE `history_esercizi`;
ALTER TABLE `module_maxtries_history_esercizi` ADD `trycount` int(5) UNSIGNED NOT NULL;
ALTER TABLE `module_maxtries_history_esercizi` ADD `createdate` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `module_maxtries_history_esercizi` ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `module_maxtries_log_classi` LIKE `log_classi`;
ALTER TABLE `module_maxtries_log_classi` ADD `trycount` int(5) UNSIGNED NOT NULL;
ALTER TABLE `module_maxtries_log_classi` ADD `createdate` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `module_maxtries_log_classi` ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `module_maxtries_log_videochat` LIKE `log_videochat`;
ALTER TABLE `module_maxtries_log_videochat` ADD `trycount` int(5) UNSIGNED NOT NULL;
ALTER TABLE `module_maxtries_log_videochat` ADD `createdate` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `module_maxtries_log_videochat` ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `module_maxtries_history_test` LIKE `module_test_history_test`;
ALTER TABLE `module_maxtries_history_test` ADD `trycount` int(5) UNSIGNED NOT NULL;
ALTER TABLE `module_maxtries_history_test` ADD `createdate` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `module_maxtries_history_test` ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `module_maxtries_history_answer` LIKE `module_test_history_answer`;
ALTER TABLE `module_maxtries_history_answer` ADD `trycount` int(5) UNSIGNED NOT NULL;
ALTER TABLE `module_maxtries_history_answer` ADD `createdate` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `module_maxtries_history_answer` ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `module_maxtries_count`
--

CREATE TABLE IF NOT EXISTS `module_maxtries_count` (
  `id_utente_studente` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `id_istanza_corso` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `count` int(5) UNSIGNED NOT NULL,
  `lastupdate` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_utente_studente`,`id_istanza_corso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `module_maxtries_count`
--
ALTER TABLE `module_maxtries_count`
  ADD CONSTRAINT `module_maxtries_count_ibfk_1` FOREIGN KEY (`id_utente_studente`) REFERENCES `utente` (`id_utente`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `module_maxtries_count_ibfk_2` FOREIGN KEY (`id_istanza_corso`) REFERENCES `istanza_corso` (`id_istanza_corso`) ON DELETE CASCADE ON UPDATE CASCADE;

COMMIT;
