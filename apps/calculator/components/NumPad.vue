<template>
  <app title="NumPad" width="328" height="340">
    <!-- 按钮网格 (18 buttons — extracted from App.vue) -->
    <grid x="0" y="0" cols="4" rows="5" cell-w="80" cell-h="60" margin="2">
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

<style>
.btn-num  { background: #323232; color: #ffffff; }
.btn-op   { background: #ff9500; color: #ffffff; }
.btn-eq   { background: #007aff; color: #ffffff; }
.btn-func { background: #505050; color: #ffffff; }
</style>
