<template>
    <div ref="reteContainer" class="workflow-editor"></div>
  </template>
  
  <script>
  import { NodeEditor } from "rete";
  import { ConnectionPlugin } from "rete-connection-plugin";
  import { VueRenderPlugin } from "rete-vue-render-plugin"; // Make sure this is the @next version
  import { AreaPlugin } from "rete-area-plugin"; // Use named import, not default import

  export default {
    name: "WorkflowEditor",
    mounted() {
      this.initializeEditor();
    },
    methods: {
      async initializeEditor() {
        const container = this.$refs.reteContainer;
        const editor = new NodeEditor("demo@0.1.0", container);
  
        editor.use(ConnectionPlugin);
        editor.use(VueRenderPlugin);
        editor.use(AreaPlugin);
  
        // Add your custom nodes here
        console.log("Editor initialized");
      }
    }
  };
  </script>
  
  <style scoped>
  .workflow-editor {
    width: 100%;
    height: 600px;
    background: #f0f0f0;
  }
  </style>