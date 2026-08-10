<?php
/**
 * Theme Init — must run before first paint to prevent FOUC.
 * Include in <head> of every page.
 */
?><script>
(function() {
  var html = document.documentElement;
  var saved = localStorage.getItem('theme');
  if (saved === 'light') html.setAttribute('data-theme', 'light');

  window.toggleTheme = function() {
    var current = html.getAttribute('data-theme');
    if (current === 'light') {
      html.removeAttribute('data-theme');
      localStorage.setItem('theme', 'dark');
    } else {
      html.setAttribute('data-theme', 'light');
      localStorage.setItem('theme', 'light');
    }
  };
})();
</script>