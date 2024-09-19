import './bootstrap';
import Drawflow from 'drawflow';
import 'drawflow/dist/drawflow.min.css'; // Import Drawflow CSS globally



document.addEventListener('DOMContentLoaded', function () {
    var editor = new Drawflow(document.getElementById("drawflow"));
    editor.start();

    // Function to save only connections
    function exportConnections() {
        const data = editor.export(); // Export the Drawflow editor data
        const connections = [];
    
        // Loop through nodes in the editor
        for (let nodeId in data.drawflow.Home.data) {
            const node = data.drawflow.Home.data[nodeId];
            const nodeName = node.name; // Get the name of the current node
            const nodeData = node.data; // Get the data of the current node
    
            if (node.outputs) {
                for (let output in node.outputs) {
                    node.outputs[output].connections.forEach(connection => {
                        const targetNode = editor.getNodeFromId(connection.node); // Get the connected input node
    
                        connections.push({
                            outputNodeId: nodeId,               // Output node ID
                            outputNodeName: nodeName,           // Output node name
                            outputNodeData: nodeData,           // Output node data
                            inputNodeId: connection.node,       // Input node ID
                            inputNodeName: targetNode.name,     // Input node name
                            inputNodeData: targetNode.data,     // Input node data
                            outputClass: output,                // Output class
                            inputClass: connection.input        // Input class
                        });
                    });
                }
            }
        }
    
        return connections;
    }

    function exportStepByStepFlow() {
        const data = editor.export(); // Export the Drawflow editor data
        const flow = [];
        const visited = new Set(); // To keep track of visited nodes and avoid loops
    
        // Function to recursively traverse nodes based on connections
        function traverse(nodeId) {
            if (visited.has(nodeId)) {
                return; // Avoid processing the same node multiple times
            }
    
            const node = data.drawflow.Home.data[nodeId];
            visited.add(nodeId); // Mark node as visited
    
            // Add node information to the flow
            flow.push({
                nodeId: nodeId,               // Node ID
                nodeName: node.name,          // Node name
                nodeData: node.data,          // Node data (like prompt for GPT Call, etc.)
            });
    
            // Check if the node has any outputs and follow the connections
            if (node.outputs) {
                for (let output in node.outputs) {
                    node.outputs[output].connections.forEach(connection => {
                        // Recursively follow the connections to the next node
                        traverse(connection.node);
                    });
                }
            }
        }
    
        // Start from a node that has no inputs or any starting node (assuming node ID "1" for this example)
        const startingNodeId = findStartingNode(data);
        if (startingNodeId) {
            traverse(startingNodeId); // Start traversal from the starting node
        }
    
        return flow; // Return the step-by-step flow of nodes
    }
    
    // Helper function to find a node with no incoming connections (the starting node)
    function findStartingNode(data) {
        const allNodes = data.drawflow.Home.data;
        const connectedNodes = new Set();
    
        // Collect all nodes that are inputs (nodes that have connections leading to them)
        for (let nodeId in allNodes) {
            const node = allNodes[nodeId];
            if (node.outputs) {
                for (let output in node.outputs) {
                    node.outputs[output].connections.forEach(connection => {
                        connectedNodes.add(connection.node);
                    });
                }
            }
        }
    
        // Find a node that is not in connectedNodes, meaning it has no inputs
        for (let nodeId in allNodes) {
            if (!connectedNodes.has(nodeId)) {
                return nodeId; // This is the starting node
            }
        }
    
        return null; // If no starting node is found, return null
    }

    // Function to save the entire editor data
    function saveEditorData() {
        var json = exportStepByStepFlow(); // Export the Drawflow editor JSON
        var inputField = document.getElementById('data.blocks');
        inputField.value = JSON.stringify(json);
        inputField.dispatchEvent(new Event('input'));
    }
    let gptdata = { prompt: "Generate JSON", model: "text-davinci-003" };
    // Add a GPT Call node with a text input for the prompt and a dropdown for model selection
    editor.addNode("GPT Call", 0, 1, 100, 50, "gpt-call", gptdata,
        `
        <div style="min-width: 400px;">
            <div>GPT Call</div>
            <div>
                <label>Prompt: </label>
                <input type="text" class="node-prompt" placeholder="Enter prompt" value="Generate JSON" />
            </div>
            <div>
                <label>Model: </label>
                <select class="node-model">
                    <option value="text-davinci-003">text-davinci-003</option>
                    <option value="gpt-3.5-turbo">gpt-3.5-turbo</option>
                    <option value="gpt-4">gpt-4</option>
                </select>
            </div>
        </div>
        `);

    // Add JSON Validator and Return Data nodes
    editor.addNode("JSON Validator", 1, 1, 300, 50, "json-validator", {}, `<div>JSON Validator</div>`);
    editor.addNode("Return Data", 1, 0, 500, 50, "return-data", {}, `<div>Return Data</div>`);

    const promptInput = document.querySelector('.node-prompt');
    const modelSelect = document.querySelector('.node-model');

    // Update node data when prompt input changes
    promptInput.addEventListener('input', function(e) {
        gptdata.prompt = promptInput.value;
        saveEditorData(); // Save after changes
    });

    // Update node data when model is changed
    modelSelect.addEventListener('change', function(e) {
        gptdata.model = modelSelect.value;
        saveEditorData(); // Save after changes
    });

    editor.on('connectionCreated', function(connection) {
        saveEditorData(); // Save on connection creation
        console.log('Connections exported: ', exportConnections()); // Export only connections
    });

    editor.on('connectionRemoved', function(connection) {
        saveEditorData(); // Save on connection removal
        console.log('Connections exported: ', exportConnections()); // Export only connections
    });

    editor.on('nodeMoved', function(id) {
        saveEditorData(); // Save when nodes are moved
    });

});