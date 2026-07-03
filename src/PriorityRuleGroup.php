<?php

declare(strict_types=1);

namespace Lartrix\ThinkPHPRoutePriority;

use think\route\RuleGroup;

class PriorityRuleGroup extends RuleGroup
{
    use PriorityCheck;
}
