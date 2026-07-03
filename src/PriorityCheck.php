<?php

declare(strict_types=1);

namespace Lartrix\ThinkPHPRoutePriority;

use think\Request;

trait PriorityCheck
{
    public function check(Request $request, string $url, bool $completeMatch = false)
    {
        if (!$this->checkOption($this->option, $request) || !$this->checkUrl($url)) {
            return false;
        }

        if (!$this->hasParsed) {
            $this->parseGroupRule($this->rule);
        }

        $method = strtolower($request->method());
        $rules = RoutePriorityMatcher::sort($this->getRules($method));
        $option = $this->getOption();

        if (isset($option['complete_match'])) {
            $completeMatch = $option['complete_match'];
        }

        if (!empty($option['merge_rule_regex'])) {
            $result = $this->checkMergeRuleRegex($request, $rules, $url, $completeMatch);

            if (false !== $result) {
                return $result;
            }
        } else {
            foreach ($rules as $item) {
                $result = $item->check($request, $url, $completeMatch);

                if (false !== $result) {
                    return $result;
                }
            }
        }

        $miss = $this->getMissRule($method);
        if ($this->bind) {
            return $this->checkBind($request, $url, $option, $miss);
        }

        if ($miss) {
            return $miss->parseRule($request, '', $miss->getRoute(), $url, $miss->getOption());
        }

        return false;
    }
}
