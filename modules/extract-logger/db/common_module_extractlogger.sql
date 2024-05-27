-- phpMyAdmin SQL Dump
-- version 5.2.1deb1+jammy2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Creato il: Apr 23, 2024 alle 16:37
-- Versione del server: 10.6.17-MariaDB-1:10.6.17+maria~ubu2204
-- Versione PHP: 8.1.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `ada_common`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `module_extractlogger_log`
--

CREATE TABLE `module_extractlogger_log` (
  `script` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `data` text NOT NULL,
  `getdata` text NOT NULL,
  `postdata` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `module_extractlogger_log`
--
ALTER TABLE `module_extractlogger_log`
  ADD UNIQUE KEY `extractlogger_key` (`script`,`class`);
COMMIT;
