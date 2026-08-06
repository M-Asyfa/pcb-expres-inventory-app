-- Real DB structure + indexes for inventory_pcbexpressjogja
-- This file is safe to run even if real dump (inventory_pcbexpressjogja.sql) is not present.
-- It creates tables with proper indexes if missing.

CREATE DATABASE IF NOT EXISTS inventory_pcbexpressjogja CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE inventory_pcbexpressjogja;

CREATE TABLE IF NOT EXISTS `data_barang` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `nama` text DEFAULT NULL,
  `kategori` text DEFAULT NULL,
  `keterangan_barang` text DEFAULT NULL,
  `nomor_box` varchar(200) NOT NULL DEFAULT '0',
  `nomor_laci` varchar(200) NOT NULL DEFAULT '1',
  `harga` int(11) NOT NULL DEFAULT 0,
  `stock` int(11) NOT NULL DEFAULT 0,
  `batas_stock` int(11) NOT NULL DEFAULT 10,
  PRIMARY KEY (`id`),
  KEY `idx_data_barang_kategori` (`kategori`(50)),
  KEY `idx_data_barang_nomor_box` (`nomor_box`),
  KEY `idx_data_barang_nomor_laci` (`nomor_laci`),
  KEY `idx_data_barang_box_laci` (`nomor_box`,`nomor_laci`),
  KEY `idx_data_barang_stock` (`stock`),
  KEY `idx_data_barang_nama` (`nama`(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `kategori` (
  `kategori` varchar(200) NOT NULL,
  PRIMARY KEY (`kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `log_stock` (
  `id` int(11) NOT NULL,
  `waktu` timestamp NOT NULL DEFAULT current_timestamp(),
  `stock` int(11) NOT NULL,
  KEY `idx_log_stock_id` (`id`),
  KEY `idx_log_stock_waktu` (`waktu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- If table already existed without indexes, add missing indexes (IF NOT EXISTS supported in MariaDB 11)
CREATE INDEX IF NOT EXISTS idx_data_barang_kategori ON data_barang (kategori(50));
CREATE INDEX IF NOT EXISTS idx_data_barang_nomor_box ON data_barang (nomor_box);
CREATE INDEX IF NOT EXISTS idx_data_barang_nomor_laci ON data_barang (nomor_laci);
CREATE INDEX IF NOT EXISTS idx_data_barang_box_laci ON data_barang (nomor_box, nomor_laci);
CREATE INDEX IF NOT EXISTS idx_data_barang_stock ON data_barang (stock);
CREATE INDEX IF NOT EXISTS idx_data_barang_batas_stock ON data_barang (batas_stock);
CREATE INDEX IF NOT EXISTS idx_log_stock_id ON log_stock (id);
CREATE INDEX IF NOT EXISTS idx_log_stock_waktu ON log_stock (waktu);

-- Also ensure normalized demo DB exists for reference (from schema.sql)
CREATE DATABASE IF NOT EXISTS inventory_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
