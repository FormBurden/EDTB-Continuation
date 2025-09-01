#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
EDTB Linux importer for EDSM nightly dumps.
Populates:
  - edtb_systems        (from systemsPopulated.json.gz)
  - edtb_powers         (distinct powers from powerPlay.json.gz)
  - edtb_systems.power / .power_state (from powerPlay.json.gz)
  - edtb_stations       (from stations.json.gz)

Requires: Python 3.9+, pip install: ijson, PyMySQL, python-dotenv (optional)
  pip install --user ijson PyMySQL python-dotenv

DB config is read from environment variables:
  DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS
DATA_DIR optional (for dumps dir), defaults to ./data

Usage examples:
  python3 scripts/import_edsm.py --quick
  python3 scripts/import_edsm.py --full
"""

import argparse
import gzip
import json
import os
import sys
import time
import urllib.request
from pathlib import Path

try:
    import ijson  # streaming JSON
except ImportError:
    print("Missing dependency: ijson. Install with: pip install --user ijson", file=sys.stderr)
    sys.exit(1)

try:
    import pymysql
except ImportError:
    print("Missing dependency: PyMySQL. Install with: pip install --user PyMySQL", file=sys.stderr)
    sys.exit(1)

def env(key, default=None):
    return os.environ.get(key, default)

def connect_db():
    host = env('DB_HOST', '127.0.0.1')
    port = int(env('DB_PORT', '3306'))
    name = env('DB_NAME', 'edtb')
    user = env('DB_USER', 'edtb')
    pwd  = env('DB_PASS', 'edtbpass')
    return pymysql.connect(host=host, port=port, user=user, password=pwd, database=name, charset='utf8mb4', autocommit=False)

DUMPS = {
    'systems':  'https://www.edsm.net/dump/systemsPopulated.json.gz',
    'stations': 'https://www.edsm.net/dump/stations.json.gz',
    'powers':   'https://www.edsm.net/dump/powerPlay.json.gz',
    # For smoke-tests use small 7-day subset for systems coords if needed
    # 'systems7': 'https://www.edsm.net/dump/systemsWithCoordinates7days.json.gz',
}

def fetch(url, target):
    if target.exists() and target.stat().st_size > 0:
        return
    target.parent.mkdir(parents=True, exist_ok=True)
    with urllib.request.urlopen(url) as r, open(target, 'wb') as f:
        while True:
            chunk = r.read(1024 * 1024)
            if not chunk:
                break
            f.write(chunk)

def chunked(iterable, size):
    batch = []
    for item in iterable:
        batch.append(item)
        if len(batch) >= size:
            yield batch
            batch = []
    if batch:
        yield batch

def ensure_indexes(cur):
    """
    Add useful indexes only if the target is a BASE TABLE and the index does not already exist.
    This avoids crashes when old setups left similarly named VIEWS in place.
    """
    def index_exists(table, index):
        cur.execute(
            "SELECT 1 FROM information_schema.statistics "
            "WHERE table_schema = DATABASE() AND table_name = %s AND index_name = %s LIMIT 1",
            (table, index),
        )
        return cur.fetchone() is not None

    def is_base_table(table):
        cur.execute(
            "SELECT TABLE_TYPE FROM information_schema.tables "
            "WHERE table_schema = DATABASE() AND table_name = %s LIMIT 1",
            (table,),
        )
        row = cur.fetchone()
        return bool(row and row[0] == 'BASE TABLE')

    def add_index(table, index, cols_csv):
        if not is_base_table(table):
            # Skip silently if it's a VIEW or missing
            return
        if index_exists(table, index):
            return
        # Safe because table/index names are fixed literals here
        cur.execute(f"ALTER TABLE {table} ADD INDEX {index} ({cols_csv})")

    add_index('edtb_systems',  'idx_systems_name',       'name')
    add_index('edtb_systems',  'idx_systems_power',      'power')
    add_index('edtb_systems',  'idx_systems_allegiance', 'allegiance')
    add_index('edtb_stations', 'idx_stations_system',    'system_id')
    add_index('edtb_stations', 'idx_stations_name',      'name')

def _norm_dt(val):
    """
    Normalize EDSM timestamps to 'YYYY-MM-DD HH:MM:SS' or return None.
    Accepts epoch ints/floats or ISO strings like '2025-08-31T12:34:56Z' or '2025-08-31 12:34:56.123Z'.
    """
    if val in (None, '', 0):
        return None
    # Epoch seconds
    if isinstance(val, (int, float)):
        try:
            return time.strftime('%Y-%m-%d %H:%M:%S', time.gmtime(int(val)))
        except Exception:
            return None
    # String variants
    if isinstance(val, str):
        s = val.strip()
        if not s:
            return None
        s = s.replace('T', ' ').replace('Z', '')
        if '.' in s:
            s = s.split('.', 1)[0]
        # Only date?
        if len(s) == 10:
            return s + ' 00:00:00'
        # Full datetime at least 19 chars
        if len(s) >= 19:
            return s[:19]
    return None


def import_systems(dump_path, conn):
    print("Importing systems (populated)...")
    # Expected EDSM structure (per API docs): { id, name, coords{x,y,z}, information{allegiance,government,security,economy,population,...} }
    # We'll upsert into edtb_systems.
    sql = (
        "REPLACE INTO edtb_systems "
        "(id, name, allegiance, economy, government, security, power, power_state, x, y, z, population) "
        "VALUES (%s, %s, %s, %s, %s, %s, NULL, NULL, %s, %s, %s, %s)"
    )
    count = 0
    with gzip.open(dump_path, 'rb') as f, conn.cursor() as cur:
        parser = ijson.items(f, 'item')
        for rows in chunked(parser, 1000):
            params = []
            for o in rows:
                sid = o.get('id')
                name = o.get('name')
                coords = o.get('coords') or {}
                x = coords.get('x', 0.0)
                y = coords.get('y', 0.0)
                z = coords.get('z', 0.0)
                info = o.get('information') or {}
                allegiance = info.get('allegiance')
                economy = info.get('economy') or info.get('primaryEconomy')
                government = info.get('government')
                security = info.get('security')
                population = info.get('population') or 0
                params.append((sid, name, allegiance, economy, government, security, x, y, z, population))
            cur.executemany(sql, params)
            conn.commit()
            count += len(rows)
            if count % 100000 == 0:
                print(f"  {count} systems...")
    print(f"Imported {count} systems")

def import_powers(dump_path, conn):
    print("Importing PowerPlay overlay...")
    # EDSM powerPlay.json.gz format is compact; records typically include systemId or id plus power/state strings.
    # We'll try both id/systemId and name/system fields defensively.
    upsert_power = "INSERT IGNORE INTO edtb_powers(name) VALUES (%s)"
    update_sys = "UPDATE edtb_systems SET power = %s, power_state = %s WHERE id = %s"
    update_sys_by_name = "UPDATE edtb_systems SET power = %s, power_state = %s WHERE name = %s"

    pow_count = 0
    sys_updates = 0
    with gzip.open(dump_path, 'rb') as f, conn.cursor() as cur:
        parser = ijson.items(f, 'item')
        for rows in chunked(parser, 2000):
            for o in rows:
                power = o.get('power')
                state = o.get('state') or o.get('powerState')
                sid = o.get('systemId') or o.get('id')
                sname = o.get('system') or o.get('systemName')
                if power:
                    cur.execute(upsert_power, (power,))
                    pow_count += 1
                if sid:
                    cur.execute(update_sys, (power, state, sid))
                    sys_updates += cur.rowcount
                elif sname:
                    cur.execute(update_sys_by_name, (power, state, sname))
                    sys_updates += cur.rowcount
            conn.commit()
    print(f"Upserted ~{pow_count} power names, updated {sys_updates} systems")

def _load_system_ids(conn):
    sids = set()
    with conn.cursor() as cur:
        cur.execute("SELECT id FROM edtb_systems")
        for row in cur.fetchall():
            sids.add(int(row[0]))
    return sids


def import_stations(dump_path, conn):
    print("Importing stations...")
    existing_sids = _load_system_ids(conn)

    sql = (
        "REPLACE INTO edtb_stations "
        "(id, system_id, name, type, max_landing_pad_size, economies, faction, allegiance, government, state, "
        " ls_from_star, commodities_market, outfitting, rearm, refuel, repair, shipyard, "
        " selling_ships, selling_modules, prohibited_commodities, import_commodities, export_commodities, "
        " black_market, is_planetary, outfitting_updated_at, shipyard_updated_at) "
        "VALUES "
        "(%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,"
        " %s,%s,%s,%s,%s,%s,%s,"
        " %s,%s,%s,%s,%s,"
        " %s,%s,%s,%s)"
    )
    count = 0
    skipped = 0
    with gzip.open(dump_path, 'rb') as f, conn.cursor() as cur:
        parser = ijson.items(f, 'item')
        for rows in chunked(parser, 1000):
            params = []
            for o in rows:
                sid = o.get('systemId')
                stid = o.get('id')
                name = o.get('name')

                # Require essentials
                if sid is None or stid is None or not name:
                    skipped += 1
                    continue

                try:
                    sid = int(sid)
                    stid = int(stid)
                except Exception:
                    skipped += 1
                    continue

                # Skip if parent system is not present (avoids FK conflicts later)
                if sid not in existing_sids:
                    skipped += 1
                    continue

                stype = o.get('type')
                maxpad = o.get('maxLandingPadSize')
                if isinstance(maxpad, str) and len(maxpad) > 1:
                    maxpad = maxpad[:1]

                economies = None
                if isinstance(o.get('economies'), list):
                    economies = ", ".join([e for e in o['economies'] if isinstance(e, str) and e])

                faction = o.get('faction')
                allegiance = o.get('allegiance')
                government = o.get('government')
                state = o.get('state')

                ls = o.get('distanceToStar') or o.get('distanceToArrival') or 0
                try:
                    ls = int(ls)
                except Exception:
                    ls = 0

                haveMarket     = 1 if o.get('haveMarket') else 0
                haveOutfitting = 1 if o.get('haveOutfitting') else 0
                haveRearm      = 1 if o.get('haveRearm') else 0
                haveRefuel     = 1 if o.get('haveRefuel') else 0
                haveRepair     = 1 if o.get('haveRepair') else 0
                haveShipyard   = 1 if o.get('haveShipyard') else 0
                blackMarket    = 1 if o.get('haveBlackmarket') else 0

                sellingShips   = ",".join(o.get('sellingShips', [])) if isinstance(o.get('sellingShips'), list) else None
                sellingModules = ",".join(o.get('sellingModules', [])) if isinstance(o.get('sellingModules'), list) else None
                prohib         = ",".join(o.get('prohibitedCommodities', [])) if isinstance(o.get('prohibitedCommodities'), list) else None
                imports        = ",".join(o.get('importCommodities', [])) if isinstance(o.get('importCommodities'), list) else None
                exports        = ",".join(o.get('exportCommodities', [])) if isinstance(o.get('exportCommodities'), list) else None

                dt_outfit  = _norm_dt(o.get('outfittingUpdatedAt'))
                dt_ship    = _norm_dt(o.get('shipyardUpdatedAt'))

                params.append((
                    stid, sid, name, stype, maxpad, economies, faction, allegiance, government, state,
                    ls, haveMarket, haveOutfitting, haveRearm, haveRefuel, haveRepair, haveShipyard,
                    sellingShips, sellingModules, prohib, imports, exports,
                    blackMarket, 1 if o.get('isPlanetary') else 0, dt_outfit, dt_ship
                ))

            if params:
                cur.executemany(sql, params)
                conn.commit()
                count += len(params)
                if count % 50000 == 0:
                    print(f"  {count} stations...")

    print(f"Imported {count} stations (skipped {skipped})")


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--quick', action='store_true', help='Use systemsPopulated + powerPlay + stations (full accuracy, but large); suitable for first run')
    parser.add_argument('--full', action='store_true', help='Same as quick (kept for clarity)')
    parser.add_argument('--only', choices=['systems', 'powers', 'stations'], help='Import only one dataset')
    args = parser.parse_args()

    data_dir = Path(env('DATA_DIR', str(Path(__file__).resolve().parents[1] / 'data')))
    dumps_dir = data_dir / 'dumps'
    dumps_dir.mkdir(parents=True, exist_ok=True)

    targets = {}
    if args.only:
        keys = [args.only]
    else:
        keys = ['systems', 'powers', 'stations']

    for k in keys:
        url = DUMPS[k]
        fname = url.split('/')[-1]
        targets[k] = dumps_dir / fname

    for k in keys:
        print(f"Downloading {k}...")
        fetch(DUMPS[k], targets[k])

    conn = connect_db()
    try:
        with conn.cursor() as cur:
            ensure_indexes(cur)
        conn.commit()

        if not args.only or args.only == 'systems':
            import_systems(targets['systems'], conn)

        if not args.only or args.only == 'powers':
            import_powers(targets['powers'], conn)

        if not args.only or args.only == 'stations':
            import_stations(targets['stations'], conn)

        conn.commit()
    finally:
        conn.close()

    print("Done.")
    return 0

if __name__ == '__main__':
    sys.exit(main())
