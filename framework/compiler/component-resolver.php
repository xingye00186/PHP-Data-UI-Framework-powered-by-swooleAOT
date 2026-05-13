<?php
/**
 * v5 M2: Component resolution helpers
 * 
 * Shared utility functions for component reference resolution.
 * Used by sfc-compiler.php and available for testing.
 */

/**
 * Apply coordinate offset to a template node.
 * Recursively handles GridNode's buttons (coordinates are computed later in lowering).
 */
function applyOffset(TemplateNode $node, int $offsetX, int $offsetY): void
{
    if ($node instanceof RectNode) {
        $node->x += $offsetX;
        $node->y += $offsetY;
    } elseif ($node instanceof TextNode) {
        $node->x += $offsetX;
        $node->y += $offsetY;
        if ($node->hasContainer) {
            $node->containerX += $offsetX;
        }
    } elseif ($node instanceof GridNode) {
        $node->x += $offsetX;
        $node->y += $offsetY;
    }
    // Other node types don't have coordinates to offset
}

/**
 * Apply prop bindings from parent component to child node.
 * Maps parent's :prop="value" to child's :bind="key" where key matches prop name.
 * 
 * e.g., parent: <child :value="display" />
 *       child:  <text :bind="value" .../>
 *       result: child's bind becomes "display"
 */
function applyPropBindings(TemplateNode $node, array $props): void
{
    // Build bind mapping: child's bind key → parent's bind value
    // e.g., :value="display" → mapping['value'] = 'display'
    $bindMapping = [];
    foreach ($props as $key => $value) {
        if (strlen($key) > 0 && $key[0] === ':') {
            $childBindKey = substr($key, 1);  // 'value'
            $bindMapping[$childBindKey] = $value; // 'display'
        }
    }

    if (count($bindMapping) === 0) {
        return;
    }

    if ($node instanceof TextNode) {
        if (isset($bindMapping[$node->bind])) {
            $node->bind = $bindMapping[$node->bind];
        }
    }
    // Future: extend to other node types with bindable properties
}
