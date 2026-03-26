<?php

namespace AndreasElia\PostmanGenerator\Processors;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use ReflectionParameter;
use ReflectionUnionType;
use App;
use ReflectionClass;
use ReflectionFunction;

class FormDataProcessor
{
    public function process($reflectionMethod): Collection
    {
        $rules = collect();

        /** @var ReflectionParameter $rulesParameter */
        $rulesParameter = collect($reflectionMethod->getParameters())
            ->first(function ($value) {
                $value = $value->getType();

                /** This is not ideal, but we need to handle Union Types in some way, so take the first type rule */
                if ($value instanceof ReflectionUnionType) {
                    $value = $value->getTypes()[0];
                }

                return $value && is_subclass_of($value->getName(), FormRequest::class);
            });

        if ($rulesParameter) {
            $rulesParameter = $rulesParameter->getType()->getName();
            $class = new ReflectionClass($rulesParameter);
            if (!$class->isInstantiable()) {
                $binds = App::getBindings();
                /** @var Closure $concrete */
                $concrete = $binds[$rulesParameter]['concrete'];
                $closureReflection = new ReflectionFunction($concrete);
                $staticVariables = $closureReflection->getStaticVariables();
                $rulesParameter = $staticVariables['concrete'];
            }
            $class = new $rulesParameter;

            $classRules = method_exists($class, 'rules') ? $class->rules() : [];

            foreach ($classRules as $fieldName => $rule) {
                if (is_string($rule)) {
                    $rule = preg_split('/\s*\|\s*/', $rule);
                }

                $printRules = config('api-postman.print_rules');

                $rules->push([
                    'name' => $fieldName,
                    'description' => $printRules ? $rule : '',
                ]);

                if (is_array($rule) && in_array('confirmed', $rule)) {
                    $rules->push([
                        'name' => $fieldName.'_confirmation',
                        'description' => $printRules ? $rule : '',
                    ]);
                }
            }
        }

        return $rules;
    }
}