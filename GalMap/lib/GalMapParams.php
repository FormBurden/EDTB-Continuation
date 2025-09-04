<?php
/**
 * EDTB - GalMap parameter normalization (Linux-native)
 * New file: GalMap/lib/GalMapParams.php
 *
 * Centralizes GET parsing for GalMap endpoints (2d/3d mode, maxdistance, center coordinates, etc.)
 * so we can replace scattered $_GET[...] usage with a single, validated source of truth.
 *
 * Usage (later pass in existing files):
 *     require_once __DIR__ . '/GalMapParams.php';
 *     $params = \EDTB\GalMap\GalMapParams::fromRequest($_GET);
 *     // $params->mode, $params->maxDistance, $params->centerX/Y/Z
 */
declare(strict_types=1);

namespace EDTB\GalMap;

final class GalMapParams
{
    /** @var '2d'|'3d' */
    public string $mode;
    /** @var int */
    public int $maxDistance;
    /** @var float|null */
    public ?float $centerX;
    /** @var float|null */
    public ?float $centerY;
    /** @var float|null */
    public ?float $centerZ;

    /** Optional system name (if caller passes a text center instead of coords) */
    public ?string $centerSystem;

    /** Additional raw map filters (kept as-is for later extension) */
    public array $raw = [];

    private function __construct(
        string $mode,
        int $maxDistance,
        ?float $cx,
        ?float $cy,
        ?float $cz,
        ?string $centerSystem,
        array $raw
    ) {
        $this->mode         = $mode;
        $this->maxDistance  = $maxDistance;
        $this->centerX      = $cx;
        $this->centerY      = $cy;
        $this->centerZ      = $cz;
        $this->centerSystem = $centerSystem;
        $this->raw          = $raw;
    }

    /**
     * Build from GET/array-like source.
     *
     * Recognized keys:
     *   - mode: '2d'|'3d' (default '2d')
     *   - maxdistance: positive integer (default 50)
     *   - centerX, centerY, centerZ: floats (optional)
     *   - center_system: string (optional; used when caller specifies a named system)
     */
    public static function fromRequest(array $src): self
    {
        $mode = isset($src['mode']) && \is_string($src['mode']) ? \strtolower($src['mode']) : '2d';
        if ($mode !== '3d') {
            $mode = '2d';
        }

        $maxDistance = 50;
        if (isset($src['maxdistance'])) {
            $md = is_numeric($src['maxdistance']) ? (int)$src['maxdistance'] : 50;
            if ($md > 0) {
                $maxDistance = $md;
            }
        }

        // Coords are optional; allow either full trio or none.
        $cx = self::toFloatOrNull($src['centerX'] ?? null);
        $cy = self::toFloatOrNull($src['centerY'] ?? null);
        $cz = self::toFloatOrNull($src['centerZ'] ?? null);

        // Optional text center (e.g., system name) for later geocoding.
        $centerSystem = null;
        if (isset($src['center_system']) && \is_string($src['center_system'])) {
            $centerSystem = \trim($src['center_system']);
            if ($centerSystem === '') {
                $centerSystem = null;
            }
        }

        // Keep raw passthrough keys for future filters (factions, gov, allegiance, etc.)
        $raw = $src;

        return new self($mode, $maxDistance, $cx, $cy, $cz, $centerSystem, $raw);
    }

    private static function toFloatOrNull($v): ?float
    {
        if ($v === null) {
            return null;
        }
        if (\is_string($v)) {
            $v = \str_replace(',', '.', $v);
        }
        return \is_numeric($v) ? (float)$v : null;
    }
}
