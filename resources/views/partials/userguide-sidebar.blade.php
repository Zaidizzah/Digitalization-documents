<aside class="app-userguide-sidebar" role="navigation" aria-label="Sidebar Section of Navigation for User Guide">
    <ul class="app-userguide-sidebar__menu" role="menu">
        @php
            $TREE_CHECK_ACTIVE = function ($item, $currentPath) use (&$TREE_CHECK_ACTIVE) {
                if ($item['path'] === $currentPath) {
                    return true;
                }
                if (!empty($item['children'])) {
                    foreach ($item['children'] as $child) {
                        if ($TREE_CHECK_ACTIVE($child, $currentPath)) {
                            return true;
                        }
                    }
                }
                return false;
            };

            $RENDER_MENU = function ($items, $currentPath) use (&$RENDER_MENU, $TREE_CHECK_ACTIVE) {
                $HTML = '';

                foreach ($items as $item) {
                    $isCurrent = $item['path'] === $currentPath;
                    $isParentOfCurrent = !$isCurrent && $TREE_CHECK_ACTIVE($item, $currentPath);

                    $HTML .= '<li class="app-userguide-sidebar__menu-item ' . ($isCurrent ? 'active' : '') . '" role="menuitem">';
                    $HTML .= '<div class="app-userguide-sidebar__menu-link-wrapper" role="group">';

                    $HTML .= '<a class="app-userguide-sidebar__menu-link ' . ($isCurrent ? 'active' : '') . '" 
                                href="' . route('userguides.show.dynamic', $item['path']) . '" 
                                title="' . e($item['title']) . '">' . e($item['title']) . '</a>';

                    if (!empty($item['children'])) {
                        $HTML .= '<button class="app-userguide-sidebar__menu-toggle" 
                                    type="button" 
                                    title="Toggle Sub-Menu for menu \'' . e($item['title']) . '\'" 
                                    aria-label="Toggle Sub-Menu" 
                                    aria-controls="app-userguide-sidebar__menu-item-children-' . $item['id'] . '">
                                    <i class="bi bi-chevron-down"></i>
                                </button>';
                        $HTML .= '</div>'; // close wrapper

                        $shouldExpand = $isCurrent || $isParentOfCurrent;

                        $HTML .= '<div class="app-userguide-sidebar__menu-item-children ' . ($shouldExpand ? 'expanded' : '') . '" 
                                    id="app-userguide-sidebar__menu-item-children-' . $item['id'] . '" 
                                    role="group" 
                                    aria-expanded="' . ($shouldExpand ? 'true' : 'false') . '">';

                        $HTML .= '<ul class="app-userguide-sidebar__menu-item-children-list" role="menu">';

                        if ($isParentOfCurrent) {
                            $HTML .= $RENDER_MENU($item['children'], $currentPath);
                        } else {
                            $HTML .= $RENDER_MENU($item['children'], null);
                        }

                        $HTML .= '</ul>';
                        $HTML .= '</div>';
                    } else {
                        $HTML .= '</div>'; 
                    }

                    $HTML .= '</li>';
                }

                return $HTML;
            };

            echo $RENDER_MENU($USER_GUIDE_LISTS_MENU, request()->route('path'));
        @endphp
    </ul>
</aside>
