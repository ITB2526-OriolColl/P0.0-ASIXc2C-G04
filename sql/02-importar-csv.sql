-- Importación del CSV "Llistat d'equipaments d'educació" (OpenData BCN) en crud_db
-- Pasos previos en la terminal (el CSV original viene en UTF-16):
--   curl -L -o equipaments.csv "https://opendata-ajuntament.barcelona.cat/data/dataset/f36b60f2-9541-4d08-b0f9-b0a9313fab3d/resource/29d9ff10-6892-4f16-9012-d5c4997857e7/download"
--   iconv -f UTF-16 -t UTF-8 equipaments.csv | sed 's/\r$//' > equipaments_utf8.csv
--   sudo mysql --local-infile=1 -u root
USE crud_db;
DROP TABLE IF EXISTS equipaments_educacio;
CREATE TABLE equipaments_educacio (
  id INT AUTO_INCREMENT PRIMARY KEY,
  register_id TEXT, name TEXT, institution_id TEXT, institution_name TEXT,
  created TEXT, modified TEXT,
  addresses_roadtype_id TEXT, addresses_roadtype_name TEXT,
  addresses_road_id TEXT, addresses_road_name TEXT,
  addresses_start_street_number TEXT, addresses_end_street_number TEXT,
  addresses_neighborhood_id TEXT, addresses_neighborhood_name TEXT,
  addresses_district_id TEXT, addresses_district_name TEXT,
  addresses_zip_code TEXT, addresses_town TEXT,
  addresses_main_address TEXT, addresses_type TEXT,
  values_id TEXT, values_attribute_id TEXT, values_category TEXT,
  values_attribute_name TEXT, values_value TEXT,
  values_outstanding TEXT, values_description TEXT,
  secondary_filters_id TEXT, secondary_filters_name TEXT,
  secondary_filters_fullpath TEXT, secondary_filters_tree TEXT,
  secondary_filters_asia_id TEXT,
  geo_epgs_25831_x TEXT, geo_epgs_25831_y TEXT,
  geo_epgs_4326_lat TEXT, geo_epgs_4326_lon TEXT,
  estimated_dates TEXT, start_date TEXT, end_date TEXT, timetable TEXT
) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
LOAD DATA LOCAL INFILE '/home/isard/Escritorio/equipaments_utf8.csv' INTO TABLE equipaments_educacio CHARACTER SET utf8mb4 FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"' LINES TERMINATED BY '\n' IGNORE 1 LINES (register_id, name, institution_id, institution_name, created, modified, addresses_roadtype_id, addresses_roadtype_name, addresses_road_id, addresses_road_name, addresses_start_street_number, addresses_end_street_number, addresses_neighborhood_id, addresses_neighborhood_name, addresses_district_id, addresses_district_name, addresses_zip_code, addresses_town, addresses_main_address, addresses_type, values_id, values_attribute_id, values_category, values_attribute_name, values_value, values_outstanding, values_description, secondary_filters_id, secondary_filters_name, secondary_filters_fullpath, secondary_filters_tree, secondary_filters_asia_id, geo_epgs_25831_x, geo_epgs_25831_y, geo_epgs_4326_lat, geo_epgs_4326_lon, estimated_dates, start_date, end_date, timetable);
SELECT COUNT(*) FROM equipaments_educacio;
