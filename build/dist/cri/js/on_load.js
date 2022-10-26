window.onload = function() {
  // Remove &nbsp from footer divs.
  var divs = document.querySelectorAll('div');

  for (i = 0; i < divs.length; ++i) {
    divs[i].innerHTML = divs[i].innerHTML.replace(/\&nbsp;/g, '');
  }

  // Top button.
  window.myButton = document.getElementById("goTopBtn");

  // Collapsibles.
  var coll = document.getElementsByClassName("collapsible");
  var i;

  for (i = 0; i < coll.length; i++) {
    coll[i].addEventListener("click", function() {
      this.classList.toggle("active");
      var content = this.nextElementSibling;
      if (content.style.maxHeight){
        content.style.maxHeight = null;
      } else {
        content.style.maxHeight = content.scrollHeight + "px";
      }
    });
  }
};
