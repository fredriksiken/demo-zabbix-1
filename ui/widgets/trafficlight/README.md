# Traffic light widget (aggregated worst-state numeric)

This widget aggregates multiple **supported numeric items** into a single traffic-light state using fixed MVP semantics:

## Semantics

- Aggregation mode (fixed): `worst-state-wins`
  - any per-item **red** => widget **red**
  - else any per-item **yellow** => widget **yellow**
  - else all per-item **green** => widget **green**
- Threshold direction (fixed): `higher_is_worse`
  - `value >= red_threshold` => per-item **red**
  - else if `value >= yellow_threshold` => per-item **yellow**
  - else => per-item **green**
- Boundary behavior: inclusive (`>=` for both cutoffs).
- Missing latest value: if the latest numeric value is missing/unavailable for **any** selected item, the widget resolves to **no-data** (no partial traffic-light is shown).

## Supported numeric subset

Only the following item value types are eligible for evaluation:
- `ITEM_VALUE_TYPE_FLOAT`
- `ITEM_VALUE_TYPE_UINT64`

If the widget configuration includes any unsupported/inaccessible items (wrong value type, inaccessible to the current user, etc.), the widget shows the **unsupported items** cover.

## Cover precedence (on refresh)

1. **Invalid configuration**: direction override attempt (payload contains/overrides `direction`).
2. **Invalid configuration**: invalid thresholds (missing/unparseable, or `red_threshold < yellow_threshold`).
3. **Empty selection**: no items selected.
4. **Unsupported items**: unsupported/inaccessible items selected.
5. **No data**: at least one selected item has no latest numeric value available.
6. **Success**: render aggregated traffic-light (`green`/`yellow`/`red`) with per-state counts.

