import { createApp } from 'vue';
import WorkflowEditor from './components/WorkflowEditor.vue';

document.addEventListener('DOMContentLoaded', () => {
    const app = createApp({
        components: {
            'workflow-editor': WorkflowEditor, // Ensure it's properly registered
        }
    });

    app.mount('#app'); // Mounting the app to the correct element
});