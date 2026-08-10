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

// Confirm-and-delete with fetch + CSRF
window.editorDelete = function(url, itemName, redirectUrl) {
  if (!confirm('Delete "' + itemName + '"?\n\nThis cannot be undone.')) return;
  if (!confirm('Are you sure?')) return;
  var token = document.querySelector('input[name="csrf_token"]');
  var body = new URLSearchParams();
  if (token) body.append('csrf_token', token.value);
  fetch(url, { method: 'POST', body: body })
    .then(function() { window.location.href = redirectUrl; })
    .catch(function() { alert('Delete failed.'); });
};
