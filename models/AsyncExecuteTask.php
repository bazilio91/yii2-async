<?php
namespace bazilio\async\models;

/**
 * Class AsyncExecuteTask
 */
class AsyncExecuteTask extends AsyncTask
{
    public $class;
    public $method;
    public $arguments = [];

    public function rules()
    {
        return array(
            [['class', 'method', 'arguments'], 'required'],
            [['class', 'method'], 'string'],
            [['class'], 'validateClass'],
            [['method'], 'validateMethod'],
            [['arguments'], 'validateArguments'],
        );
    }

    public function validateClass($attribute, $params)
    {
        if (!class_exists($this->$attribute)) {
            $this->addError($attribute, "Class {$this->$attribute} does not exist");
        }
    }

    public function validateMethod($attribute, $params)
    {
        if (!isset(array_flip(get_class_methods($this->class))[$this->$attribute])) {
            $this->addError(
                $attribute,
                "Method {$this->$attribute} of class {$this->class} does not exist"
            );
        }
    }

    public function validateArguments($attribute, $params) {
        if (!isset(array_flip(get_class_methods($this->class))[$this->method])) {
            $this->addError(
                $attribute,
                "Method {$this->method} of class {$this->class} does not exist"
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
        return call_user_func_array(array($this->class, $this->method), $this->arguments);
    }
}