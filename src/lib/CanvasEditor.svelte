<script>
  import { onMount, onDestroy } from 'svelte';
  import * as fabric from 'fabric';

  let canvasEl;
  let fabricCanvas;

  // History stacks
  let history = [];
  let historyStep = -1; // -1 means initial empty state
  const maxHistory = 50; // Limit to prevent memory issues

  export let backgroundColor = '#ffffff';

  // Save current state to history
  function saveState() {
    if (!fabricCanvas) return;

    // Go forward if we're in the middle of history (e.g. after undo)
    if (historyStep < history.length - 1) {
      history = history.slice(0, historyStep + 1);
    }

    const json = fabricCanvas.toJSON(['lockMovementX', 'lockMovementY', 'lockScalingX', 'lockScalingY', 'lockRotation']);

    history.push(json);
    if (history.length > maxHistory) {
      history.shift(); // Remove oldest
    } else {
      historyStep++;
    }
  }

  // Initial empty state
  function initEmptyState() {
    history = [];
    historyStep = -1;
    saveState(); // Save initial empty canvas as step 0
  }

  // Undo
  export function undo() {
    if (historyStep > 0) {
      historyStep--;
      const previousState = history[historyStep];
      fabricCanvas.loadFromJSON(previousState, () => {
        fabricCanvas.renderAll();
      });
    }
  }

  // Redo
  export function redo() {
    if (historyStep < history.length - 1) {
      historyStep++;
      const nextState = history[historyStep];
      fabricCanvas.loadFromJSON(nextState, () => {
        fabricCanvas.renderAll();
      });
    }
  }

  // Check if undo/redo possible
  export function canUndo() {
    return historyStep > 0;
  }

  export function canRedo() {
    return historyStep < history.length - 1;
  }

  onMount(() => {
    fabricCanvas = new fabric.Canvas(canvasEl, {
      width: window.innerWidth - 300,
      height: window.innerHeight - 100,
      backgroundColor,
      preserveObjectStacking: true
    });

    // Save state on these events
    const events = [
      'object:added',
      'object:modified',
      'object:removed',
      'selection:created',
      'selection:updated',
      'selection:cleared',
      'object:moving',
      'object:scaling',
      'object:rotating'
    ];

    events.forEach(event => {
      fabricCanvas.on(event, () => {
        // Throttle saving to avoid too many states during drag
        clearTimeout(window.saveTimeout);
        window.saveTimeout = setTimeout(saveState, 300);
      });
    });

    // Mouse up = good time to save final state
    fabricCanvas.on('mouse:up', saveState);

    // Resize handler
    const resize = () => {
      fabricCanvas.setDimensions({
        width: window.innerWidth - 300,
        height: window.innerHeight - 100
      });
    };
    window.addEventListener('resize', resize);

    // Initialize history with empty state
    initEmptyState();

    onDestroy(() => {
      window.removeEventListener('resize', resize);
      if (fabricCanvas) fabricCanvas.dispose();
    });
  });

  // Public methods
  export function addText(text = 'Double-click to edit', color = '#000000') {
    if (!fabricCanvas) return;
    const textbox = new fabric.Textbox(text, {
      left: 100,
      top: 100,
      fontSize: 40,
      fill: color,
      editable: true,
      fontFamily: 'Arial'
    });
    fabricCanvas.add(textbox);
    fabricCanvas.setActiveObject(textbox);
    saveState(); // Save immediately
  }

  export function addImage(dataUrl) {
  if (!fabricCanvas) return;

  // Create a native HTML Image element
  const imgEl = new Image();
  imgEl.onload = function () {
    // Once loaded, create Fabric image from the element
    const fImg = new fabric.Image(imgEl, {
      left: (fabricCanvas.getWidth() - imgEl.width) / 2,
      top: (fabricCanvas.getHeight() - imgEl.height) / 2
    });

    // Scale it down if too big
    const maxDim = Math.max(imgEl.width, imgEl.height);
    if (maxDim > 600) {
      fImg.scale(600 / maxDim);
    }

    fabricCanvas.add(fImg);
    fabricCanvas.setActiveObject(fImg);
    fabricCanvas.renderAll();
    saveState();
  };

  imgEl.onerror = function () {
    console.error('Failed to load uploaded image');
  };

  // Set the data URL as source (triggers onload)
  imgEl.src = dataUrl;
}

  export function exportPNG() {
    if (!fabricCanvas) return '';
    return fabricCanvas.toDataURL({
      format: 'png',
      multiplier: 2 // Higher quality
    });
  }

  export function clear() {
    if (!fabricCanvas) return;
    fabricCanvas.clear();
    fabricCanvas.backgroundColor = backgroundColor;
    fabricCanvas.renderAll();
    initEmptyState(); // Reset history
  }
</script>

<canvas bind:this={canvasEl}></canvas>

<style>
  canvas {
    border: 1px solid #ccc;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    border-radius: 8px;
  }
</style>