#!/usr/bin/env python3
import argparse, os, sys, time
try:
    import ijson
except ImportError:
    sys.stderr.write("Missing dependency: ijson. Install with: python -m pip install --user ijson\n")
    sys.exit(1)

def parse_args():
    ap = argparse.ArgumentParser(description="Stream-filter EDSM systemsWithCoordinates.json into TSV by radius.")
    ap.add_argument("--file", required=True, help="Path to systemsWithCoordinates.json (unzipped).")
    g = ap.add_mutually_exclusive_group(required=True)
    g.add_argument("--center-name", help="Center system name. 'Sol' is supported as (0,0,0).")
    g.add_argument("--cx", type=float, help="Center X (ly)")
    ap.add_argument("--cy", type=float, help="Center Y (ly)")
    ap.add_argument("--cz", type=float, help="Center Z (ly)")
    ap.add_argument("--radius", type=float, required=True, help="Radius in ly")
    ap.add_argument("--out", required=True, help="Output TSV path (name\tx\ty\tz)")
    ap.add_argument("--progress-every", type=float, default=2.0, help="Seconds between progress prints (default 2s)")
    return ap.parse_args()

def resolve_center(args):
    if args.center_name:
        name = args.center_name.strip().lower()
        if name == "sol":
            return 0.0, 0.0, 0.0
        sys.stderr.write("Only 'Sol' is supported for --center-name. For others, pass --cx/--cy/--cz from your DB.\n")
        sys.exit(2)
    if args.cx is None or args.cy is None or args.cz is None:
        sys.stderr.write("When not using --center-name, you must pass --cx --cy --cz.\n")
        sys.exit(2)
    return float(args.cx), float(args.cy), float(args.cz)

def main():
    args = parse_args()
    cx, cy, cz = resolve_center(args)
    r2 = float(args.radius) * float(args.radius)
    path = args.file
    try:
        size = os.path.getsize(path)
    except OSError:
        size = None

    processed = matched = 0
    last_t = time.time()

    with open(path, 'rb', buffering=1024*1024) as f, open(args.out, 'w', buffering=1024*1024) as out:
        for obj in ijson.items(f, 'item'):  # top-level array items
            processed += 1
            coords = obj.get('coords')
            if not coords:
                continue
            try:
                x = float(coords.get('x')); y = float(coords.get('y')); z = float(coords.get('z'))
            except (TypeError, ValueError):
                continue
            dx = x - cx; dy = y - cy; dz = z - cz
            if (dx*dx + dy*dy + dz*dz) <= r2:
                name = obj.get('name')
                if name:
                    matched += 1
                    out.write(f"{name}\t{x}\t{y}\t{z}\n")

            # progress line (updates every few seconds)
            now = time.time()
            if now - last_t >= args.progress_every:
                try:
                    pos = f.tell()
                except Exception:
                    pos = 0
                pct = (pos/size*100.0) if (size and size>0) else 0.0
                sys.stderr.write(f"\rProcessed: {processed:,}  Matched: {matched:,}  File: {pct:5.1f}%")
                sys.stderr.flush()
                last_t = now

    sys.stderr.write(f"\nDone. Processed {processed:,} systems; matched {matched:,}. TSV: {args.out}\n")

if __name__ == "__main__":
    main()
