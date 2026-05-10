<template>
  <!-- VueCalc Calculator Template -->
  <!-- 类 Vue 模板语法: 声明式 UI 描述 -->
  <window title="VueCalc" :width="336" :height="430">
    <!-- 显示区 -->
    <display-panel>
      <expression-text v-if="expression">{{ expression }}</expression-text>
      <result-text :font-size="displayFontSize">{{ display }}</result-text>
    </display-panel>

    <!-- 按钮网格 (数据驱动渲染) -->
    <button-grid :cols="4" :rows="5">
      <button v-for="btn in buttons"
              :label="btn.label"
              :type="btn.type"
              @click="handleButton(btn.label)" />
    </button-grid>
  </window>
</template>

<style>
/* 计算器样式 */
window {
  background: #1e1e1e;
}

display-panel {
  background: #2d2d2d;
  padding: 8px;
  text-align: right;
}

expression-text {
  font-size: 16px;
  color: #969696;
}

result-text {
  font-size: 32px;
  color: #ffffff;
  font-weight: bold;
}

button[type="num"] {
  background: #323232;
  color: #ffffff;
}

button[type="op"] {
  background: #ff9500;
  color: #ffffff;
}

button[type="eq"] {
  background: #007aff;
  color: #ffffff;
}

button[type="func"] {
  background: #505050;
  color: #ffffff;
}
</style>
