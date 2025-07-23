-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 23 Tem 2025, 05:36:22
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `ticaret`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `alacak_verecek`
--

CREATE TABLE `alacak_verecek` (
  `ID` int(11) NOT NULL,
  `ALACAK_VERECEK_TYPE_ID` int(11) NOT NULL,
  `KISI_ID` int(11) NOT NULL,
  `ÜCRET` decimal(10,2) NOT NULL,
  `ACIKLAMA` char(50) NOT NULL,
  `TARIH` date DEFAULT NULL,
  `ODENDI_MI` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `alacak_verecek_type`
--

CREATE TABLE `alacak_verecek_type` (
  `ID` int(11) NOT NULL,
  `NAME` char(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `alacak_verecek_type`
--

INSERT INTO `alacak_verecek_type` (`ID`, `NAME`) VALUES
(1, 'ALACAK'),
(2, 'VERECEK'),
(3, 'Ödeme');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `islem`
--

CREATE TABLE `islem` (
  `ID` int(11) NOT NULL,
  `TEDARIKCI_ID` int(11) NOT NULL,
  `ALICI_ID` int(11) NOT NULL,
  `URUN_ID` int(11) NOT NULL,
  `PLAKA` char(25) DEFAULT NULL,
  `FIRESIZ_TON` decimal(10,3) NOT NULL,
  `FIRELI_TON` decimal(10,3) NOT NULL,
  `ALIS_FIYATI` decimal(10,2) NOT NULL,
  `SATIS_FIYATI` decimal(10,0) NOT NULL,
  `KAR` decimal(10,0) NOT NULL,
  `TARIH` date NOT NULL,
  `ACIKLAMA` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kisiler`
--

CREATE TABLE `kisiler` (
  `ID` int(11) NOT NULL,
  `NAME` varchar(50) NOT NULL,
  `TEL_NO` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `kisiler`
--

INSERT INTO `kisiler` (`ID`, `NAME`, `TEL_NO`) VALUES
(6, 'seyit', NULL),
(7, 'gürcü', NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `urun`
--

CREATE TABLE `urun` (
  `ID` int(11) NOT NULL,
  `NAME` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `urun`
--

INSERT INTO `urun` (`ID`, `NAME`) VALUES
(1, 'KAVURMALIK SOĞAN'),
(2, 'ORTA BOY SOĞAN'),
(3, 'PATATES');

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `alacak_verecek`
--
ALTER TABLE `alacak_verecek`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `ALACAK_VERECEK_TYPE_ID` (`ALACAK_VERECEK_TYPE_ID`),
  ADD KEY `KISI_ID` (`KISI_ID`);

--
-- Tablo için indeksler `alacak_verecek_type`
--
ALTER TABLE `alacak_verecek_type`
  ADD PRIMARY KEY (`ID`);

--
-- Tablo için indeksler `islem`
--
ALTER TABLE `islem`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `TEDARIKCI_ID` (`TEDARIKCI_ID`),
  ADD KEY `ALICI_ID` (`ALICI_ID`),
  ADD KEY `URUN_ID` (`URUN_ID`);

--
-- Tablo için indeksler `kisiler`
--
ALTER TABLE `kisiler`
  ADD PRIMARY KEY (`ID`);

--
-- Tablo için indeksler `urun`
--
ALTER TABLE `urun`
  ADD PRIMARY KEY (`ID`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `alacak_verecek`
--
ALTER TABLE `alacak_verecek`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Tablo için AUTO_INCREMENT değeri `alacak_verecek_type`
--
ALTER TABLE `alacak_verecek_type`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `islem`
--
ALTER TABLE `islem`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `kisiler`
--
ALTER TABLE `kisiler`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Tablo için AUTO_INCREMENT değeri `urun`
--
ALTER TABLE `urun`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `alacak_verecek`
--
ALTER TABLE `alacak_verecek`
  ADD CONSTRAINT `alacak_verecek_ibfk_1` FOREIGN KEY (`ALACAK_VERECEK_TYPE_ID`) REFERENCES `alacak_verecek_type` (`ID`),
  ADD CONSTRAINT `alacak_verecek_ibfk_2` FOREIGN KEY (`KISI_ID`) REFERENCES `kisiler` (`ID`);

--
-- Tablo kısıtlamaları `islem`
--
ALTER TABLE `islem`
  ADD CONSTRAINT `islem_ibfk_1` FOREIGN KEY (`TEDARIKCI_ID`) REFERENCES `kisiler` (`ID`),
  ADD CONSTRAINT `islem_ibfk_2` FOREIGN KEY (`ALICI_ID`) REFERENCES `kisiler` (`ID`),
  ADD CONSTRAINT `islem_ibfk_3` FOREIGN KEY (`URUN_ID`) REFERENCES `urun` (`ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
