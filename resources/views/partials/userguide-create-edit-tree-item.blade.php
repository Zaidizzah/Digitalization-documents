@php
    $CURRENT_DATA = $CURRENT_DATA ?? null;

    $RENDER_TREE = function ($nodes, $LEVEL = 0) use (&$RENDER_TREE, $CURRENT_DATA) {
        if (empty($nodes) || (is_countable($nodes) && count($nodes) === 0)) {
            return ['html' => '', 'expanded' => false];
        }

        $HTML = '';
        $expandedAny = false;

        foreach ($nodes as $item) {
            $hasChildren = $item->relationLoaded('children')
                ? $item->children->isNotEmpty()
                : $item->children()->exists();

            $id    = (int) $item->id;
            $title = str_replace("\"", "\\\"", e($item->title));

            $isSelected = $CURRENT_DATA instanceof \App\Models\UserGuides && $CURRENT_DATA->parent_id && (int) $CURRENT_DATA->parent_id === $id;
            $isActive = $CURRENT_DATA instanceof \App\Models\UserGuides && $CURRENT_DATA->id && (int) $CURRENT_DATA->id === $id;
            // dump($isSelected, "Current data id value: {$CURRENT_DATA->id}, Item id value: {$item->id}");

            // render children
            $childHtml = '';
            $childExpanded = false;
            if ($hasChildren) {
                $childResult   = $RENDER_TREE($item->children, $LEVEL + 1);
                $childHtml     = $childResult['html'];
                $childExpanded = $childResult['expanded'];
            }

            if ($isSelected || $isActive || $childExpanded) {
                $expandedAny = true; // propagate ke parent
            }

            $HTML .= "
                <div class=\"user-guides-tree-item " . ($isActive ? 'active' : '') . "\"
                    title=\"{$title}\"
                    role=\"treeitem\"
                    data-level=\"{$LEVEL}\"
                    data-id=\"{$id}\"
                    style=\"margin-left:" . ($LEVEL * 10) . "px\">
                    <div class=\"item-content\">
                        <button
                            class=\"toggle-btn " . ($hasChildren ? ($childExpanded ? 'expanded' : 'collapsed') : 'no-children') . " btn-toggle-children-vibility btn-toggle-children-visibility\"
                            type=\"button\"
                            role=\"button\"
                            data-parent-id=\"{$id}\"
                            aria-controls=\"user-guides-children-{$id}\"" . (!$hasChildren ? ' disabled aria-disabled=\"true\"' : '') . ">
                        </button>

                        <label for=\"user-guides-radio-{$id}\" class=\"radio-label visually-hidden\"></label>
                        <input type=\"radio\"
                            name=\"parent_id\"
                            value=\"{$id}\"
                            class=\"radio-input\"
                            id=\"user-guides-radio-{$id}\" " . ($isActive ? 'checked' : '') . " aria-checked=\"" . ($isActive ? 'true' : 'false') . "\" " . ($isActive ? 'disabled aria-disabled=\"true\"' : 'aria-disabled=\"false\"') . " role=\"radio\">

                        <span class=\"item-title\">{$title}</span>

                        <div class=\"badge-group d-inline-flex gap-2\">
                            <span class=\"badge badge-" . ($item->document_type !== NULL && $item->document_type !== NULL ? "specific" : "general") . "\">" . ($item->document_type !== NULL && $item->document_type !== NULL ? "Document type {$item->document_type->name}" : "General") . "</span>
                            <span class=\"badge badge-" . ($item->is_active ? "active" : "inactive") . "\">" . ($item->is_active ? "Active" : "Inactive") . "</span>
                        </div>
                    </div>
                </div>
            ";

            if ($hasChildren) {
                $HTML .= "<div class=\"children " . ($childExpanded ? 'expanded' : 'collapsed') . "\"
                            id=\"user-guides-children-{$id}\"
                            role=\"group\"
                            aria-expanded=\"" . ($childExpanded ? 'true' : 'false') . "\">";
                $HTML .= $childHtml;
                $HTML .= "</div>";
            }
        }

        return ['html' => $HTML, 'expanded' => $expandedAny];
    };

    // handle paginator
    $NODES = $USER_GUIDES instanceof \Illuminate\Pagination\AbstractPaginator
        ? $USER_GUIDES->getCollection()
        : $USER_GUIDES;

    echo $RENDER_TREE($NODES, 0)['html'];
@endphp