-- Add legacy/Windows-parity columns that the UI selects from edtb_systems.
-- Each is conditional to avoid errors if the column already exists.

ALTER TABLE edtb_systems
  ADD COLUMN IF NOT EXISTS allegiance       VARCHAR(64)     NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS economy          VARCHAR(64)     NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS government       VARCHAR(64)     NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS ruling_faction   VARCHAR(128)    NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS state            VARCHAR(64)     NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS simbad_ref       VARCHAR(128)    NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS population       BIGINT UNSIGNED NULL DEFAULT NULL;
