/**
 * Editor Shared JS — auto-slug and delete helpers used by both editors.
 */
// Auto-generate slug/ID from title input
window.autoSlug = function(titleInputId, targetInputId, defaultPrefix) {
  var titleInput = document.getElementById(titleInputId);
  var targetInput = document.getElementById(targetInputId);
  if (!titleInput || !targetInput) return;
  var touched = false;
  targetInput.addEventListener('input', function() { touched = true; });
  titleInput.addEventListener('input', function() {
    if (touched) return;
    var slug = titleInput.value.replace(/[^a-zA-Z0-9\s-]/g, '').trim().toLowerCase().replace(/\s+/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
    if (!slug) slug = (defaultPrefix || 'item') + '-' + new Date().toISOString().slice(0,10);
    targetInput.value = slug;
  });
};

// Confirm-and-delete with fetch
window.editorDelete = function(url, itemName, redirectUrl) {
  if (!confirm('Delete "' + itemName + '"?\n\nThis cannot be undone.')) return;
  if (!confirm('Are you sure?')) return;
  fetch(url, { method: 'POST' })
    .then(function() { window.location.href = redirectUrl; })
    .catch(function() { alert('Delete failed.'); });
};
