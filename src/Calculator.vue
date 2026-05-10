<template>
  <app title="VueCalc - Reactive Data-Driven Calculator" width="328" height="420">
    <!-- 应用背景 -->
    <rect x="0" y="0" w="328" h="420" class="app-bg" />
    
    <!-- 显示区域背景 -->
    <rect x="4" y="4" w="320" h="72" class="display-bg" />
    
    <!-- 表达式文本（小号，左上角，灰色） -->
    <text x="10" y="10" :bind="expression" class="expr-text" align="left" />
    
    <!-- 显示值文本（大号，右对齐，白色粗体） -->
    <text y="36" :bind="display" class="display-text" align="right" container-w="320" container-x="4" />
    
    <!-- 按钮网格 -->
    <grid x="0" y="80" cols="4" rows="5" cell-w="80" cell-h="60" margin="2">
      <btn row="0" col="0" label="C"  class="btn-func" @click="reset" />
      <btn row="0" col="1" label="<-" class="btn-func" @click="backspace" />
      <btn row="0" col="2" label="/"  class="btn-op"   @click="handleButton('/')" />
      <btn row="0" col="3" label="*"  class="btn-op"   @click="handleButton('*')" />
      
      <btn row="1" col="0" label="7"  class="btn-num"  @click="handleButton('7')" />
      <btn row="1" col="1" label="8"  class="btn-num"  @click="handleButton('8')" />
      <btn row="1" col="2" label="9"  class="btn-num"  @click="handleButton('9')" />
      <btn row="1" col="3" label="-"  class="btn-op"   @click="handleButton('-')" />
      
      <btn row="2" col="0" label="4"  class="btn-num"  @click="handleButton('4')" />
      <btn row="2" col="1" label="5"  class="btn-num"  @click="handleButton('5')" />
      <btn row="2" col="2" label="6"  class="btn-num"  @click="handleButton('6')" />
      <btn row="2" col="3" label="+"  class="btn-op"   @click="handleButton('+')" />
      
      <btn row="3" col="0" label="1"  class="btn-num"  @click="handleButton('1')" />
      <btn row="3" col="1" label="2"  class="btn-num"  @click="handleButton('2')" />
      <btn row="3" col="2" label="3"  class="btn-num"  @click="handleButton('3')" />
      <btn row="3" col="3" label="="  class="btn-eq"   @click="calculate" />
      
      <btn row="4" col="0" label="0"  class="btn-num"  @click="handleButton('0')" />
      <btn row="4" col="1" label="."  class="btn-num"  @click="handleButton('.')" />
    </grid>
  </app>
</template>

<script lang="php">

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
</script>

<style>
.app-bg      { background: #1e1e1e; }
.display-bg  { background: #2d2d2d; }
.expr-text   { font-size: 16px; color: #969696; }
.display-text { font-size: 32px; color: #ffffff; font-weight: bold; }
.btn-num     { background: #323232; color: #ffffff; }
.btn-op      { background: #ff9500; color: #ffffff; }
.btn-eq      { background: #007aff; color: #ffffff; }
.btn-func    { background: #505050; color: #ffffff; }
</style>
