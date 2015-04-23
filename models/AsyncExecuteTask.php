<?php
namespace bazilio\async\models;

/**
 * Class AsyncExecuteTask
 *
 * @property object $instance Class instance to execute against
 * @property string $class Class to call. Not required if [[instance]] specified
 * @property string $method Class or instance method to call
 * @property array $arguments Associative array of method's arguments
 */
class AsyncExecuteTask extends AsyncTask
{
    public $instance;
    public $class;
    public $method;
    public $arguments = [];

    public function rules()
    {
        return array(
            [['instance'], 'validateInstance'],
            [['class', 'method'], 'required'],
            [['class', 'method'], 'string'],
            [['class'], 'validateClass'],
            [['method'], 'validateMethod'],
            [['arguments'], 'validateArguments'],
        );
    }

    private function getIsClassValid()
    {
        return class_exists($this->class);
    }

    public function validateInstance($attribute, $params)
    {
        if (empty($this->$attribute)) {
            return;
        }

        if (!is_object($this->$attribute)) {
            $this->addError($attribute, "Instance should be an object, not a " . gettype($this->$attribute));
            return;
        }

        $this->class = get_class($this->$attribute);
    }

    public function validateClass($attribute, $params)
    {
        if (!$this->getIsClassValid()) {
            $this->addError($attribute, "Class {$this->$attribute} does not exist");
        }
    }

    public function validateMethod($attribute, $params)
    {
        if (!$this->getIsClassValid()) {
            return;
        }

        if (!isset(array_flip(get_class_methods($this->class))[$this->$attribute])) {
            $this->addError(
                $attribute,
                "Method {$this->$attribute} of class {$this->class} does not exist"
            );
        }
    }

    public function validateArguments($attribute, $params)
    {
        if (!$this->getIsClassValid()) {
            return;
        }

        if (!isset(array_flip(get_class_methods($this->class))[$this->method])) {
            $this->addError(
                $attribute,
                "Can't validate attributes for not existing method {$this->method} of class {$this->class}"
            );
            return;
        }

        $refFunc = new \ReflectionMethod($this->class, $this->method);
        $userArguments = array_keys($this->{$attribute});
        $missingArguments = [];
        foreach ($refFunc->getParameters() as $param) {
            if (!$param->isOptional() && !in_array($param->getName(), $userArguments)) {
                $missingArguments[] = $param->getName();
            } else {
                // Type hint
                // Notice that array hinting is not supported yet
                if ($param->getClass()
                    && (
                        !is_object($this->{$attribute}[$param->getName()])
                        || get_class($this->{$attribute}[$param->getName()]) !== $param->getClass()->name
                    )
                ) {
                    $this->addError(
                        $attribute,
                        "Method `{$this->method}` param `{$param->getName()}` " .
                        "expects type `{$param->getClass()->name}` but got " . gettype($this->{$attribute}[$param->getName()])
                    );
                }
            }
        }

        if (sizeof($missingArguments)) {
            $this->addError(
                $attribute,
                "Method `{$this->method}` missing required arguments: " . implode(
                    ', ',
                    $missingArguments
                )
            );
        }
    }

    /**
     * Returns the list of attribute names of the model.
     * @return array list of attribute names.
     */
    public function attributeNames()
    {
        return [
            'instance',
            'class',
            'method',
            'arguments'
        ];
    }

    static function nope($return)
    {
        return $return;
    }

    public function execute()
    {
        if ($this->instance) {
            return call_user_func_array(array($this->instance, $this->method), $this->arguments);
        } else {
            return call_user_func_array(array($this->class, $this->method), $this->arguments);
        }
    }

    function __sleep()
    {
        return $this->attributeNames();
    }
}