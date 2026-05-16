<template>
  <app title="AboutDialog" width="328" height="420">
    <!-- 半透明覆盖层 (modal overlay) -->
    <rect x="0" y="0" w="328" h="420" class="dialog-overlay" v-if="showDialog" />

    <!-- 对话框面板背景 -->
    <rect x="24" y="110" w="280" h="200" class="dialog-box" v-if="showDialog" />

    <!-- 对话框标题 -->
    <text x="164" y="135" :bind="dialogTitle" class="dialog-title" align="center" container-w="280" container-x="24" v-if="showDialog" />

    <!-- 分隔线 -->
    <rect x="44" y="155" w="240" h="2" class="dialog-divider" v-if="showDialog" />

    <!-- 对话框内容 -->
    <text x="164" y="180" :bind="dialogContent" class="dialog-text" align="center" container-w="280" container-x="24" v-if="showDialog" />

    <!-- 版本号 -->
    <text x="164" y="215" :bind="dialogVersion" class="dialog-version" align="center" container-w="280" container-x="24" v-if="showDialog" />

    <!-- 关闭按钮 -->
    <grid x="114" y="265" cols="1" rows="1" cell-w="100" cell-h="34" margin="0" v-if="showDialog">
      <btn row="0" col="0" label="Close" class="btn-op" @click="toggleAboutDialog" />
    </grid>
  </app>
</template>

<style>
.dialog-overlay { background: #0a0a0a; }
.dialog-box     { background: #2d2d2d; }
.dialog-title   { font-size: 20px; color: #ffffff; font-weight: bold; }
.dialog-divider { background: #ff9500; }
.dialog-text    { font-size: 15px; color: #cccccc; }
.dialog-version { font-size: 13px; color: #888888; }
.dialog-hint    { font-size: 12px; color: #666666; }
</style>
