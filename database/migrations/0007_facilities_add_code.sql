ALTER TABLE edtb_facilities
  ADD COLUMN code VARCHAR(64) NOT NULL AFTER id,
  ADD UNIQUE KEY uq_facilities_code (code);

-- Backfill codes for the rows already inserted earlier
UPDATE edtb_facilities SET code='black_market'             WHERE name='Black Market';
UPDATE edtb_facilities SET code='commodities_market'       WHERE name='Commodities Market';
UPDATE edtb_facilities SET code='mission_board'            WHERE name='Mission Board';
UPDATE edtb_facilities SET code='contacts'                 WHERE name='Contacts';
UPDATE edtb_facilities SET code='universal_cartographics'  WHERE name='Universal Cartographics';
UPDATE edtb_facilities SET code='outfitting'               WHERE name='Outfitting';
UPDATE edtb_facilities SET code='shipyard'                 WHERE name='Shipyard';
UPDATE edtb_facilities SET code='refuel'                   WHERE name='Refuel';
UPDATE edtb_facilities SET code='repair'                   WHERE name='Repair';
UPDATE edtb_facilities SET code='restock'                  WHERE name='Restock (Munitions)';
UPDATE edtb_facilities SET code='interstellar_factors'     WHERE name='Interstellar Factors';
UPDATE edtb_facilities SET code='material_trader'          WHERE name='Material Trader';
UPDATE edtb_facilities SET code='technology_broker'        WHERE name='Technology Broker';
UPDATE edtb_facilities SET code='engineer_workshop'        WHERE name='Engineer Workshop';
UPDATE edtb_facilities SET code='search_and_rescue'        WHERE name='Search and Rescue';
UPDATE edtb_facilities SET code='crew_lounge'              WHERE name='Crew Lounge';
UPDATE edtb_facilities SET code='livery'                   WHERE name='Livery';
UPDATE edtb_facilities SET code='large_pad'                WHERE name='Large Pad';
UPDATE edtb_facilities SET code='medium_pad'               WHERE name='Medium Pad';
UPDATE edtb_facilities SET code='small_pad'                WHERE name='Small Pad';
