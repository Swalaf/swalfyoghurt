<script>
/* Studio preview bridge: lets the builder's “Click to edit” pick elements. Not included in published apps. */
(function () {
  var on = false, hover = null, style = document.createElement('style');
  style.textContent = '[data-studio-hover]{outline:1px dashed #9A8CF5!important;outline-offset:3px!important;cursor:pointer!important}[data-studio-sel]{outline:2px solid #7C6CF0!important;outline-offset:3px!important}';
  document.documentElement.appendChild(style);
  function describe(el) {
    var text = (el.innerText || el.value || el.getAttribute('alt') || '').trim().replace(/\s+/g, ' ').slice(0, 80);
    return { tag: el.tagName.toLowerCase(), text: text, id: el.id || '', cls: (el.className && el.className.baseVal === undefined ? el.className : '').toString().slice(0, 60) };
  }
  window.addEventListener('message', function (e) {
    if (!e.data || e.data.type !== 'studio-edit') return;
    on = !!e.data.on;
    if (!on) document.querySelectorAll('[data-studio-hover],[data-studio-sel]').forEach(function (n) { n.removeAttribute('data-studio-hover'); n.removeAttribute('data-studio-sel'); });
  });
  document.addEventListener('mouseover', function (e) { if (!on) return; hover && hover.removeAttribute('data-studio-hover'); hover = e.target; hover.setAttribute('data-studio-hover', ''); }, true);
  document.addEventListener('click', function (e) {
    if (!on) return;
    e.preventDefault(); e.stopPropagation();
    document.querySelectorAll('[data-studio-sel]').forEach(function (n) { n.removeAttribute('data-studio-sel'); });
    e.target.setAttribute('data-studio-sel', '');
    parent.postMessage({ type: 'studio-selected', el: describe(e.target) }, '*');
  }, true);
})();
</script>
