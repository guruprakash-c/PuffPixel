<script>
  import CanvasEditor from '$lib/CanvasEditor.svelte';

  let editor;
  let color = '#000000';
  let previewUrl = '';
  let uploadedPreview = '';

  function handleImageUpload(e) {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (ev) => {
      uploadedPreview = ev.target.result;
      editor.addImage(ev.target.result);
    };
    reader.readAsDataURL(file);
  }

  function exportDesign() {
    previewUrl = editor.exportPNG();
  }

  // Keyboard shortcuts
  function handleKeydown(e) {
    if (e.ctrlKey || e.metaKey) {
      if (e.key === 'z') {
        e.preventDefault();
        editor.undo();
      } else if (e.key === 'y' || (e.shiftKey && e.key === 'Z')) {
        e.preventDefault();
        editor.redo();
      }
    }
  }
</script>

<svelte:window on:keydown={handleKeydown} />

<svelte:head>
  <title>Mini Canva with Undo/Redo - SvelteKit</title>
</svelte:head>

<div class="container">
  <aside class="sidebar">
    <h2>Mini Canva</h2>

    <section>
      <h3>Add Elements</h3>
      <button on:click={() => editor.addText('New Text', color)}>Add Text</button>
      <button on:click={() => document.getElementById('imgUpload').click()}>
        Upload Image
      </button>
      <input type="file" id="imgUpload" accept="image/*" on:change={handleImageUpload} style="display:none" />
      {#if uploadedPreview}
        <section>
            <h3>Uploaded Preview</h3>
            <img src={uploadedPreview} alt="Uploaded" style="max-width:100%; border:1px solid #ccc; border-radius:8px;" />
        </section>
      {/if}
    </section>

    <section>
      <h3>Color</h3>
      <input type="color" bind:value={color} />
    </section>

    <section>
      <h3>History</h3>
      <button 
        on:click={editor.undo} 
        disabled={!editor?.canUndo()}>
        ↺ Undo (Ctrl+Z)
      </button>
      <button 
        on:click={editor.redo} 
        disabled={!editor?.canRedo()}>
        ↻ Redo (Ctrl+Y)
      </button>
    </section>

    <section>
      <h3>Actions</h3>
      <button on:click={exportDesign}>Export as PNG</button>
      <button on:click={() => editor.clear()}>Clear Canvas</button>
    </section>

    {#if previewUrl}
      <section>
        <h3>Preview & Download</h3>
        <img src={previewUrl} alt="Exported design" style="max-width:100%; border:1px solid #ddd; border-radius:8px;" />
        <br />
        <a href={previewUrl} download="my-design.png">Download PNG</a>
      </section>
    {/if}
  </aside>

  <main class="canvas-area">
    <CanvasEditor bind:this={editor} />
  </main>
</div>

<style>
  :global(body) { margin:0; font-family: system-ui, sans-serif; background:#f0f2f5; }
  .container { display: flex; height: 100vh; }
  .sidebar {
    width: 300px;
    background: white;
    padding: 20px;
    overflow-y: auto;
    border-right: 1px solid #ddd;
    box-shadow: 2px 0 10px rgba(0,0,0,0.05);
  }
  .sidebar h2 { text-align: center; color: #333; margin-bottom: 20px; }
  .sidebar section { margin-bottom: 30px; }
  .sidebar h3 { margin: 15px 0 10px; color: #555; font-size: 1.1em; }
  button {
    width: 100%;
    padding: 12px;
    margin: 8px 0;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
  }
  button:hover:not(:disabled) { background: #0056b3; }
  button:disabled { background: #ccc; cursor: not-allowed; }
  .canvas-area {
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: center;
    background: #e9ecef;
  }
</style>