<?php

use native_types;

/**
 * Calculator - 计算器组件
 * 
 * AOT 兼容版本: 直接声明所有属性，修改状态后设置 $this->dirty = true。
 * 不依赖 __get/__set 魔术方法。
 */
class Calculator extends ReactiveComponent
{
    /** 当前显示值 */
    public string $display = '0';

    /** 表达式(显示在右上角) */
    public string $expression = '';

    /** 第一个操作数 */
    public string $operand1 = '';

    /** 当前运算符 */
    public string $operator = '';

    /** 是否开始新输入 */
    public bool $newInput = true;

    /** 是否已输入小数点 */
    public bool $hasDecimal = false;

    public function __construct(string $componentId = 'Calculator')
    {
        $this->template = __DIR__ . '/Calculator.vue';
        parent::__construct($componentId);
    }

    /** 重置计算器状态 */
    public function reset(): void
    {
        $this->display = '0';
        $this->expression = '';
        $this->operand1 = '';
        $this->operator = '';
        $this->newInput = true;
        $this->hasDecimal = false;
        $this->dirty = true;
    }

    /** 输入数字 */
    public function inputDigit(string $digit): void
    {
        if ($this->newInput) {
            $this->display = $digit;
            $this->newInput = false;
            $this->hasDecimal = false;
        } else {
            if ($this->display === '0' && $digit !== '.') {
                $this->display = $digit;
            } else {
                $this->display .= $digit;
            }
        }
        $this->dirty = true;
    }

    /** 输入小数点 */
    public function inputDecimal(): void
    {
        if ($this->newInput) {
            $this->display = '0.';
            $this->newInput = false;
            $this->hasDecimal = true;
        } elseif (!$this->hasDecimal) {
            $this->display .= '.';
            $this->hasDecimal = true;
        }
        $this->dirty = true;
    }

    /** 输入运算符 */
    public function inputOperator(string $op): void
    {
        if ($this->operator !== '' && !$this->newInput) {
            $this->calculate();
        }
        $this->operand1 = $this->display;
        $this->operator = $op;
        $this->expression = $this->operand1 . ' ' . $op;
        $this->newInput = true;
        $this->dirty = true;
    }

    /** 执行计算 */
    public function calculate(): void
    {
        if ($this->operator === '' || $this->operand1 === '') {
            return;
        }

        $a = (float)$this->operand1;
        $b = (float)$this->display;
        $result = 0.0;

        $op = $this->operator;
        if ($op === '+') {
            $result = $a + $b;
        } elseif ($op === '-') {
            $result = $a - $b;
        } elseif ($op === '*') {
            $result = $a * $b;
        } elseif ($op === '/') {
            if ($b == 0.0) {
                $this->display = 'Error';
                $this->expression = '';
                $this->operand1 = '';
                $this->operator = '';
                $this->newInput = true;
                $this->dirty = true;
                return;
            }
            $result = $a / $b;
        }

        if ($result == (float)(int)$result && abs($result) < 1000000000) {
            $this->display = (string)(int)$result;
        } else {
            $this->display = rtrim(rtrim(sprintf('%.8f', $result), '0'), '.');
        }

        $this->expression = '';
        $this->operand1 = '';
        $this->operator = '';
        $this->newInput = true;
        $this->hasDecimal = strpos($this->display, '.') !== false;
        $this->dirty = true;
    }

    /** 退格 */
    public function backspace(): void
    {
        if ($this->newInput || $this->display === 'Error') {
            return;
        }
        if (strlen($this->display) <= 1) {
            $this->display = '0';
            $this->newInput = true;
        } else {
            $last = $this->display[strlen($this->display) - 1];
            if ($last === '.') {
                $this->hasDecimal = false;
            }
            $this->display = substr($this->display, 0, -1);
        }
        $this->dirty = true;
    }

    /** 处理按钮点击 */
    public function handleButton(string $label): void
    {
        if ($label === '') {
            return;
        }
        if ($label === 'C') {
            $this->reset();
        } elseif ($label === '<-') {
            $this->backspace();
        } elseif ($label === '=') {
            $this->calculate();
        } elseif ($label === '+' || $label === '-' || $label === '*' || $label === '/') {
            $this->inputOperator($label);
        } elseif ($label === '.') {
            $this->inputDecimal();
        } else {
            $this->inputDigit($label);
        }
    }
}
