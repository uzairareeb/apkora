document.addEventListener('DOMContentLoaded', function () {
  var t = document.querySelector('.nav-toggle'), n = document.getElementById('mainNav');
  if (t && n) t.addEventListener('click', function () {
    var open = n.classList.toggle('open');
    t.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
  var d = document.getElementById('desc'), b = document.getElementById('readmore');
  if (d && b && d.scrollHeight > 380) {
    d.classList.add('collapsed'); b.hidden = false;
    b.addEventListener('click', function () {
      var c = d.classList.toggle('collapsed');
      b.textContent = c ? 'Read more' : 'Show less';
    });
  }
});
