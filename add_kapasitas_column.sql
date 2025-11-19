-- SQL to add kapasitas column to ruang table if it doesn't exist
-- Run this in your MySQL client (phpMyAdmin, MySQL Workbench, or command line)

USE wowok;

-- Add kapasitas column if it doesn't exist
ALTER TABLE ruang 
ADD COLUMN IF NOT EXISTS kapasitas INT(11) NOT NULL DEFAULT 0 AFTER nama_ruang;

-- Verify the change
DESCRIBE ruang;
