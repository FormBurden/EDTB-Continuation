-- Ensure edtb_systems has all columns the importer writes.
-- Safe to run multiple times.

ALTER TABLE edtb_systems
  ADD COLUMN IF NOT EXISTS name        VARCHAR(128) NOT NULL AFTER id,
  ADD COLUMN IF NOT EXISTS allegiance  VARCHAR(32)  NULL,
  ADD COLUMN IF NOT EXISTS economy     VARCHAR(64)  NULL,
  ADD COLUMN IF NOT EXISTS government  VARCHAR(64)  NULL,
  ADD COLUMN IF NOT EXISTS security    VARCHAR(64)  NULL,
  ADD COLUMN IF NOT EXISTS power       VARCHAR(64)  NULL,
  ADD COLUMN IF NOT EXISTS power_state VARCHAR(64)  NULL,
  ADD COLUMN IF NOT EXISTS x           DOUBLE NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS y           DOUBLE NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS z           DOUBLE NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS population  BIGINT UNSIGNED DEFAULT 0;

-- Make sure id is the PK (older schemas sometimes didn’t have it set)
ALTER TABLE edtb_systems
  ADD PRIMARY KEY IF NOT EXISTS (id);

-- Optional niceties: if the PK existed on a different column, fix it:
-- (Uncomment if needed)
-- ALTER TABLE edtb_systems DROP PRIMARY KEY, ADD PRIMARY KEY (id);
