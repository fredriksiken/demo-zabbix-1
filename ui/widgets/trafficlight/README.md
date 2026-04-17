# Traffic light widget

## Semantics (fixed MVP)
- Aggregation mode: **worst-state-wins**
  - any `red` => widget `red`
  - else any `yellow` => widget `yellow`
  - else => widget `green`
- Threshold direction: **`higher_is_worse`**
- Boundary behavior: **inclusive**
  - `value >= red_threshold` => `red`
  - else if `value >= yellow_threshold` => `yellow`
  - else => `green`

## Supported item types
Only items with numeric `value_type` **float** or **unsigned integer** are supported for evaluation. If the selected patterns match unsupported item types, the widget renders the standard cover for **Unsupported items**.

## Cover precedence (deterministic)
1. Invalid widget configuration (threshold parsing/ordering) => **Please update configuration**
2. Empty item selection => **No data found**
3. Unsupported item types present => **Unsupported items**
4. No latest numeric data available => **No data found**

