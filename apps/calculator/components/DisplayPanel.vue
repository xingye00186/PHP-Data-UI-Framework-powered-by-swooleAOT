<template>
  <app title="DisplayPanel" width="320" height="72">
    <rect x="0" y="0" w="320" h="72" class="display-bg" />
    <text x="0" y="32" :bind="value" class="display-text" align="right" container-w="320" container-x="0" />
  </app>
</template>

<style>
.display-bg { background: #2d2d2d; }
.display-text { font-size: 32px; color: #ffffff; font-weight: bold; }
</style>
