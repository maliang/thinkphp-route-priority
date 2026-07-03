<?php

declare(strict_types=1);

namespace Lartrix\ThinkPHPRoutePriority;

use Closure;
use think\route\Domain;
use think\route\Resource;
use think\route\ResourceRegister;
use think\route\Rule;
use think\route\RuleGroup;

final class Route extends \think\Route
{
    protected function setDefaultDomain(): void
    {
        $domain = new PriorityDomain($this);

        $this->domains['-'] = $domain;
        $this->group = $domain;
    }

    public function domain(string|array $name, $rule = null): Domain
    {
        $domainName = is_array($name) ? array_shift($name) : $name;

        if (!isset($this->domains[$domainName])) {
            $domain = (new PriorityDomain($this, $domainName, $rule, $this->lazy))
                ->removeSlash($this->removeSlash)
                ->mergeRuleRegex($this->mergeRuleRegex);

            $this->domains[$domainName] = $domain;
        } else {
            $domain = $this->domains[$domainName];
            $domain->parseGroupRule($rule);
        }

        if (is_array($name) && !empty($name)) {
            foreach ($name as $item) {
                $this->domains[$item] = $domainName;
            }
        }

        return $domain;
    }

    public function group(string|Closure $name, $route = null): RuleGroup
    {
        if ($name instanceof Closure) {
            $route = $name;
            $name = '';
        }

        return (new PriorityRuleGroup($this, $this->group, $name, $route, $this->lazy))
            ->removeSlash($this->removeSlash)
            ->mergeRuleRegex($this->mergeRuleRegex);
    }

    public function setCrossDomainRule(Rule $rule)
    {
        if (!isset($this->cross)) {
            $this->cross = (new PriorityRuleGroup($this))->mergeRuleRegex($this->mergeRuleRegex);
        }

        $this->cross->addRuleItem($rule);

        return $this;
    }

    public function resource(string $rule, string $route, ?Closure $extend = null)
    {
        $resource = (new Resource($this, $this->group, $rule, $route, $this->rest))->extend($extend);

        if (!$this->lazy) {
            return new ResourceRegister($resource);
        }

        return $resource;
    }
}
